# PWA Card Carousel — Design Spec

## Goal

Replace the static QR code and contact info sections on the standalone PWA card with a two-slide looping carousel. The user swipes left or right to toggle between the QR code and contact details. The card stays clean by default (QR visible first) with contact info one swipe away.

## Slides

**Slide 1 — QR Code:** Centered QR SVG with optional logo overlay (existing rendering). This is the default visible slide.

**Slide 2 — Contact Info:** Phone, cell, email, website as tappable links, followed by formatted address. Stacked vertically. Uses the same data and markup patterns as the existing `render_contact` and `render_address` callbacks.

## Architecture

### New hook: `pikari_team_card_carousel`

A new static method `Card_Renderer::render_carousel()` registered on the `pikari_team_card_carousel` action hook at priority 10 with 2 args (`$data`, `$context`).

This callback:

1. Renders the carousel container with scroll-snap track
2. Calls `do_action( 'pikari_team_carousel_slide_qr', $data, $context )` for slide 1
3. Calls `do_action( 'pikari_team_carousel_slide_contact', $data, $context )` for slide 2
4. Renders dot indicators

Default callbacks for the slide hooks reuse existing rendering logic from `render_qr`, `render_contact`, and `render_address` (or call them directly).

### Section list change

The default standalone template section list changes from:

```php
['header', 'contact', 'address', 'social', 'qr', 'footer']
```

to:

```php
['header', 'carousel', 'footer']
```

The existing `qr`, `contact`, `address`, and `social` hooks and their default callbacks remain registered and functional. Theme developers who don't want the carousel can filter `pikari_team_card_sections` back to the original list.

### Theme customization

Theme developers can:

- **Replace the carousel entirely:** `remove_action( 'pikari_team_card_carousel', ... )` and add their own
- **Replace individual slides:** unhook `pikari_team_carousel_slide_qr` or `pikari_team_carousel_slide_contact` and add custom callbacks
- **Disable the carousel:** filter `pikari_team_card_sections` to use the flat section list instead
- **Add slides:** hook into additional `pikari_team_carousel_slide_*` actions (requires overriding `render_carousel` to fire them)

## Markup

```html
<div class="pikari-team-card__carousel">
	<div class="pikari-team-card__carousel-track">
		<div class="pikari-team-card__slide" data-slide="0">
			<!-- QR code content -->
		</div>
		<div class="pikari-team-card__slide" data-slide="1">
			<!-- Contact info content -->
		</div>
	</div>
	<div class="pikari-team-card__carousel-dots" role="tablist">
		<button
			class="pikari-team-card__dot active"
			role="tab"
			aria-selected="true"
			aria-label="QR Code"
			data-slide="0"
		></button>
		<button
			class="pikari-team-card__dot"
			role="tab"
			aria-selected="false"
			aria-label="Contact Info"
			data-slide="1"
		></button>
	</div>
</div>
```

## CSS

Applied via the plugin's `assets/css/card.css` (and overridable by theme via `pikari_team_card_css_file` filter).

```css
.pikari-team-card__carousel-track {
	display: flex;
	overflow-x: auto;
	scroll-snap-type: x mandatory;
	-webkit-overflow-scrolling: touch;
	scrollbar-width: none; /* Firefox */
}

.pikari-team-card__carousel-track::-webkit-scrollbar {
	display: none; /* Chrome/Safari */
}

.pikari-team-card__slide {
	flex: 0 0 100%;
	scroll-snap-align: center;
	min-height: 200px;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 1.5rem;
}

.pikari-team-card__carousel-dots {
	display: flex;
	justify-content: center;
	gap: 0.5rem;
	padding: 1rem 0;
}

.pikari-team-card__dot {
	width: 8px;
	height: 8px;
	border-radius: 50%;
	border: none;
	background: #ccc;
	padding: 0;
	cursor: pointer;
	transition: background 0.2s;
}

.pikari-team-card__dot.active {
	background: var(--pikari-brand-color, #0073aa);
}
```

## JavaScript

Inlined in the standalone template's `<script>` block, after the service worker registration. Vanilla JS, no dependencies.

**IntersectionObserver for dot state:**
Observes each `.pikari-team-card__slide` at 50% threshold. When a slide becomes majority-visible, updates the `active` class and `aria-selected` on the corresponding dot.

**Dot click navigation:**
Each dot calls `track.scrollTo({ left: slideWidth * index, behavior: 'smooth' })`.

**Looping:**
On `scrollend` event (or `scroll` with debounce for browsers without `scrollend`):

- If scrolled to the very end (last slide fully visible), jump to the first slide without animation (`behavior: 'instant'`)
- If scrolled to the very beginning (first slide fully visible) and swipe direction was backward, jump to the last slide without animation

The loop jump uses `requestAnimationFrame` to avoid visual flicker. The observer handles dot state automatically after the jump.

**Estimated size:** ~30 lines, well under 1KB.

## Accessibility

- Dots use `role="tablist"` / `role="tab"` with `aria-selected`
- Each dot has an `aria-label` describing its content
- Carousel is swipeable via touch, scrollable via mouse/trackpad, and navigable via dot buttons
- Contact links within slide 2 are standard `<a>` elements with `href` (tel:, mailto:, https://)

## Files Changed

| File                              | Change                                                                                                                                    |
| --------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| `includes/Card_Renderer.php`      | Add `render_carousel()`, `render_carousel_slide_qr()`, `render_carousel_slide_contact()` static methods. Register defaults for new hooks. |
| `assets/css/card.css`             | Add carousel, track, slide, dot styles                                                                                                    |
| `templates/card-standalone.php`   | Add carousel JS in the `<script>` block. Update default section list to include `carousel`.                                               |
| `tests/php/Card_RendererTest.php` | Add tests for carousel hook firing                                                                                                        |

## What This Does NOT Change

- The `pikari_team_card_qr`, `pikari_team_card_contact`, and `pikari_team_card_address` hooks remain registered with their default callbacks. They are simply not in the default section list when the carousel is active.
- The `pikari_team_card_css_file` filter still works — theme CSS can override all carousel styling.
- The `pikari_team_qr_options` and `pikari_team_qr_svg` filters still apply to the QR code rendered inside the carousel.
- VCard download, service worker, manifest — all unchanged.
