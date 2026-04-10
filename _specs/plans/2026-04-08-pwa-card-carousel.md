# PWA Card Carousel Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the static QR code and contact info sections on the PWA card with a two-slide looping carousel (QR code + contact details).

**Architecture:** A new `render_carousel()` method on `Card_Renderer` wraps QR and contact content into a scroll-snap carousel with dot indicators. CSS handles swipe physics, minimal inline JS handles looping and dot state via IntersectionObserver. The default standalone template section list changes to `['header', 'carousel', 'footer']`.

**Tech Stack:** PHP 8.2, CSS scroll-snap, vanilla JS (IntersectionObserver), Brain\Monkey/PHPUnit

---

## File Map

| File                              | Action | Responsibility                                                                                                                                               |
| --------------------------------- | ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `includes/Card_Renderer.php`      | Modify | Add `render_carousel()`, `render_carousel_slide_qr()`, `render_carousel_slide_contact()`. Register hooks in `register_defaults()`. Update `SINGLE_SECTIONS`. |
| `assets/css/card.css`             | Modify | Add carousel track, slide, dot styles                                                                                                                        |
| `assets/js/carousel.js`           | Create | Inline JS for looping + dot state (IntersectionObserver)                                                                                                     |
| `templates/card-standalone.php`   | Modify | Inline carousel JS alongside SW registration JS                                                                                                              |
| `tests/php/Card_RendererTest.php` | Modify | Add tests for carousel hook firing and slide hooks                                                                                                           |

---

## Task 1: Carousel CSS

**Files:**

- Modify: `assets/css/card.css:112-119` (after `.pikari-team-card__qr` styles)

- [ ] **Step 1: Add carousel styles to card.css**

Append after the existing `.pikari-team-card__qr svg` rule (line ~119):

```css
/* Carousel */
.pikari-team-card__carousel {
	position: relative;
}
.pikari-team-card__carousel-track {
	display: flex;
	overflow-x: auto;
	scroll-snap-type: x mandatory;
	-webkit-overflow-scrolling: touch;
	scrollbar-width: none;
}
.pikari-team-card__carousel-track::-webkit-scrollbar {
	display: none;
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
	padding: 0.75rem 0 1.25rem;
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

- [ ] **Step 2: Commit**

```bash
cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team
git add assets/css/card.css
git commit -m "feat: add carousel CSS with scroll-snap and dot indicators"
```

---

## Task 2: Carousel JS

**Files:**

- Create: `assets/js/carousel.js`

- [ ] **Step 1: Create the carousel JS file**

```js
(function () {
	var track = document.querySelector('.pikari-team-card__carousel-track');
	if (!track) {
		return;
	}

	var slides = track.querySelectorAll('.pikari-team-card__slide');
	var dots = document.querySelectorAll('.pikari-team-card__dot');

	if (slides.length < 2) {
		return;
	}

	// Update active dot via IntersectionObserver.
	var observer = new IntersectionObserver(
		function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					var index = Number(entry.target.dataset.slide);
					dots.forEach(function (dot, i) {
						dot.classList.toggle('active', i === index);
						dot.setAttribute('aria-selected', i === index ? 'true' : 'false');
					});
				}
			});
		},
		{ root: track, threshold: 0.5 }
	);

	slides.forEach(function (slide) {
		observer.observe(slide);
	});

	// Dot click navigation.
	dots.forEach(function (dot) {
		dot.addEventListener('click', function () {
			var index = Number(dot.dataset.slide);
			track.scrollTo({
				left: slides[index].offsetLeft - track.offsetLeft,
				behavior: 'smooth',
			});
		});
	});

	// Looping: when scroll settles at an edge, jump to the opposite end.
	var scrollTimer;
	track.addEventListener('scroll', function () {
		clearTimeout(scrollTimer);
		scrollTimer = setTimeout(function () {
			var maxScroll = track.scrollWidth - track.clientWidth;
			if (track.scrollLeft <= 0) {
				// At the start — loop to last slide.
				requestAnimationFrame(function () {
					track.style.scrollBehavior = 'auto';
					track.scrollLeft = maxScroll;
					track.style.scrollBehavior = '';
				});
			} else if (track.scrollLeft >= maxScroll - 1) {
				// At the end — loop to first slide.
				requestAnimationFrame(function () {
					track.style.scrollBehavior = 'auto';
					track.scrollLeft = 0;
					track.style.scrollBehavior = '';
				});
			}
		}, 150);
	});
})();
```

- [ ] **Step 2: Commit**

```bash
cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team
git add assets/js/carousel.js
git commit -m "feat: add carousel JS with looping and IntersectionObserver dots"
```

---

## Task 3: Card_Renderer — Carousel Methods and Hook Registration

**Files:**

- Modify: `includes/Card_Renderer.php`
- Modify: `tests/php/Card_RendererTest.php`

- [ ] **Step 1: Write the failing tests**

Add to `tests/php/Card_RendererTest.php`:

```php
public function test_register_defaults_hooks_carousel(): void {
    Actions\expectAdded( 'pikari_team_card_carousel' )->once();
    Actions\expectAdded( 'pikari_team_carousel_slide_qr' )->once();
    Actions\expectAdded( 'pikari_team_carousel_slide_contact' )->once();

    // Existing hooks still registered.
    Actions\expectAdded( 'pikari_team_card_header' )->once();
    Actions\expectAdded( 'pikari_team_card_contact' )->once();
    Actions\expectAdded( 'pikari_team_card_address' )->once();
    Actions\expectAdded( 'pikari_team_card_social' )->once();
    Actions\expectAdded( 'pikari_team_card_qr' )->once();
    Actions\expectAdded( 'pikari_team_card_footer' )->once();

    Card_Renderer::register_defaults();
}

public function test_render_fires_carousel_hook_for_single_context(): void {
    $this->mock_member_data();

    Actions\expectDone( 'pikari_team_card_header' )->once();
    Actions\expectDone( 'pikari_team_card_carousel' )->once();
    Actions\expectDone( 'pikari_team_card_footer' )->once();

    // These should NOT fire in the default single section list.
    Actions\expectDone( 'pikari_team_card_contact' )->never();
    Actions\expectDone( 'pikari_team_card_address' )->never();
    Actions\expectDone( 'pikari_team_card_qr' )->never();

    Card_Renderer::render( 1, 'single' );
}

public function test_render_carousel_fires_slide_hooks(): void {
    $this->mock_member_data();

    Actions\expectDone( 'pikari_team_carousel_slide_qr' )->once();
    Actions\expectDone( 'pikari_team_carousel_slide_contact' )->once();

    Card_Renderer::render_carousel(
        \Pikari\Team\Template_Tags::get_member_data( 1 ),
        'single'
    );
}
```

Also update the existing `test_register_defaults_hooks_into_all_sections` test to include the 3 new hooks (carousel + 2 slides), changing the expectation from 6 to 9 hooks. Replace it:

```php
public function test_register_defaults_hooks_into_all_sections(): void {
    Actions\expectAdded( 'pikari_team_card_header' )->once();
    Actions\expectAdded( 'pikari_team_card_contact' )->once();
    Actions\expectAdded( 'pikari_team_card_address' )->once();
    Actions\expectAdded( 'pikari_team_card_social' )->once();
    Actions\expectAdded( 'pikari_team_card_qr' )->once();
    Actions\expectAdded( 'pikari_team_card_footer' )->once();
    Actions\expectAdded( 'pikari_team_card_carousel' )->once();
    Actions\expectAdded( 'pikari_team_carousel_slide_qr' )->once();
    Actions\expectAdded( 'pikari_team_carousel_slide_contact' )->once();

    Card_Renderer::register_defaults();
}
```

And update `test_render_fires_all_section_hooks_for_single_context` to expect the new default sections:

```php
public function test_render_fires_all_section_hooks_for_single_context(): void {
    $this->mock_member_data();

    Actions\expectDone( 'pikari_team_card_header' )->once();
    Actions\expectDone( 'pikari_team_card_carousel' )->once();
    Actions\expectDone( 'pikari_team_card_footer' )->once();

    Card_Renderer::render( 1, 'single' );
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && vendor/bin/phpunit tests/php/Card_RendererTest.php -v`
Expected: FAIL

- [ ] **Step 3: Implement the carousel methods**

In `includes/Card_Renderer.php`:

**Update `SINGLE_SECTIONS` (line 24):**

```php
private const SINGLE_SECTIONS = [ 'header', 'carousel', 'footer' ];
```

**Add to `register_defaults()` (after the existing `add_action` calls, around line 75):**

```php
add_action( 'pikari_team_card_carousel', [ self::class, 'render_carousel' ], 10, 2 );
add_action( 'pikari_team_carousel_slide_qr', [ self::class, 'render_carousel_slide_qr' ], 10, 2 );
add_action( 'pikari_team_carousel_slide_contact', [ self::class, 'render_carousel_slide_contact' ], 10, 2 );
```

**Add three new methods before the closing `}` of the class:**

```php
/**
 * Renders the carousel wrapper with QR and contact slides.
 *
 * @param array  $data    Member data.
 * @param string $context Rendering context.
 */
public static function render_carousel( array $data, string $context ): void {
    echo '<div class="pikari-team-card__carousel">';
    echo '<div class="pikari-team-card__carousel-track">';

    echo '<div class="pikari-team-card__slide" data-slide="0">';
    /**
     * Fires to render the QR code carousel slide.
     *
     * @param array  $data    Structured member data.
     * @param string $context Rendering context.
     */
    do_action( 'pikari_team_carousel_slide_qr', $data, $context );
    echo '</div>';

    echo '<div class="pikari-team-card__slide" data-slide="1">';
    /**
     * Fires to render the contact info carousel slide.
     *
     * @param array  $data    Structured member data.
     * @param string $context Rendering context.
     */
    do_action( 'pikari_team_carousel_slide_contact', $data, $context );
    echo '</div>';

    echo '</div>'; // .carousel-track

    echo '<div class="pikari-team-card__carousel-dots" role="tablist">';
    echo '<button class="pikari-team-card__dot active" role="tab" aria-selected="true" aria-label="' . esc_attr__( 'QR Code', 'pikari-team' ) . '" data-slide="0"></button>';
    echo '<button class="pikari-team-card__dot" role="tab" aria-selected="false" aria-label="' . esc_attr__( 'Contact Info', 'pikari-team' ) . '" data-slide="1"></button>';
    echo '</div>';

    echo '</div>'; // .carousel
}

/**
 * Default QR carousel slide content.
 *
 * @param array  $data    Member data.
 * @param string $context Rendering context.
 */
public static function render_carousel_slide_qr( array $data, string $context ): void {
    if ( ! class_exists( QR_Code::class ) || empty( $data['post_id'] ) ) {
        return;
    }

    $qr  = new QR_Code();
    $svg = $qr->generate_qr_svg( (int) $data['post_id'] );

    if ( '' === $svg ) {
        return;
    }

    echo '<div class="pikari-team-card__qr">';
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG generated by plugin.
    echo $svg;
    echo '</div>';
}

/**
 * Default contact carousel slide content (phone, cell, email, website, address).
 *
 * @param array  $data    Member data.
 * @param string $context Rendering context.
 */
public static function render_carousel_slide_contact( array $data, string $context ): void {
    $has_contact = $data['has_phone'] || $data['has_cell'] || $data['has_email'] || $data['has_website'];

    if ( $has_contact ) {
        echo '<div class="pikari-team-card__contact">';

        if ( $data['has_phone'] ) {
            echo '<a href="tel:' . esc_attr( $data['phone'] ) . '" class="pikari-team-card__link">';
            echo '<span class="pikari-team-card__label">' . esc_html__( 'Phone', 'pikari-team' ) . '</span>';
            echo '<span class="pikari-team-card__value">' . esc_html( $data['phone'] ) . '</span>';
            echo '</a>';
        }

        if ( $data['has_cell'] ) {
            echo '<a href="tel:' . esc_attr( $data['cell'] ) . '" class="pikari-team-card__link">';
            echo '<span class="pikari-team-card__label">' . esc_html__( 'Cell', 'pikari-team' ) . '</span>';
            echo '<span class="pikari-team-card__value">' . esc_html( $data['cell'] ) . '</span>';
            echo '</a>';
        }

        if ( $data['has_email'] ) {
            echo '<a href="mailto:' . esc_attr( $data['email'] ) . '" class="pikari-team-card__link">';
            echo '<span class="pikari-team-card__label">' . esc_html__( 'Email', 'pikari-team' ) . '</span>';
            echo '<span class="pikari-team-card__value">' . esc_html( $data['email'] ) . '</span>';
            echo '</a>';
        }

        if ( $data['has_website'] ) {
            echo '<a href="' . esc_url( $data['website'] ) . '" class="pikari-team-card__link" target="_blank" rel="noopener noreferrer">';
            echo '<span class="pikari-team-card__label">' . esc_html__( 'Website', 'pikari-team' ) . '</span>';
            echo '<span class="pikari-team-card__value">' . esc_html( $data['website'] ) . '</span>';
            echo '</a>';
        }

        echo '</div>';
    }

    if ( $data['has_address'] ) {
        $address = Template_Tags::get_formatted_address_from_data( $data );

        echo '<div class="pikari-team-card__address">';
        echo '<span class="pikari-team-card__label">' . esc_html__( 'Address', 'pikari-team' ) . '</span>';
        echo '<span class="pikari-team-card__value">' . esc_html( $address ) . '</span>';
        echo '</div>';
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && vendor/bin/phpunit tests/php/Card_RendererTest.php -v`
Expected: All tests PASS

- [ ] **Step 5: Run full test suite**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && composer test`
Expected: All tests PASS

- [ ] **Step 6: Commit**

```bash
cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team
git add includes/Card_Renderer.php tests/php/Card_RendererTest.php
git commit -m "feat: add carousel rendering with QR and contact slide hooks"
```

---

## Task 4: Inline Carousel JS in Standalone Template

**Files:**

- Modify: `templates/card-standalone.php:119-133`

- [ ] **Step 1: Add carousel JS inline**

In `templates/card-standalone.php`, inside the existing `<script>` block (after the SW registration JS include at line ~132), add:

```php
        <?php
        $carousel_file = PIKARI_TEAM_DIR . 'assets/js/carousel.js';
        if ( file_exists( $carousel_file ) ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.Security.EscapeOutput.OutputNotEscaped
            echo file_get_contents( $carousel_file );
        }
        ?>
```

Insert this BEFORE the closing `</script>` tag, after the SW register include block.

- [ ] **Step 2: Verify the standalone template renders correctly**

Run: `cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team && php -l templates/card-standalone.php`
Expected: No syntax errors

- [ ] **Step 3: Commit**

```bash
cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team
git add templates/card-standalone.php assets/js/carousel.js
git commit -m "feat: inline carousel JS in standalone PWA template"
```

---

## Task 5: Update Theme Card Customization

**Files:**

- Modify: Operation Smile theme file `/Users/steveariss/Sites/operation-smile/canada/wp-content/themes/osc-2024/framework/pikari-team-card.php`
- Modify: Operation Smile theme CSS `/Users/steveariss/Sites/operation-smile/canada/wp-content/themes/osc-2024/src/styles/pikari-team-card.css`

- [ ] **Step 1: Update the theme section filter**

In `pikari-team-card.php`, the `osc_pikari_card_sections` filter currently returns `['header', 'qr', 'footer']`. Update it to include the carousel:

```php
function osc_pikari_card_sections($sections)
{
    return ['header', 'carousel', 'footer'];
}
```

- [ ] **Step 2: Add carousel styles to the theme CSS**

In `src/styles/pikari-team-card.css`, add carousel styles after the existing QR code styles. These override the plugin defaults with Operation Smile branding:

```css
/* Carousel */
.pikari-team-card__carousel {
	position: relative;
}

.pikari-team-card__carousel-track {
	display: flex;
	overflow-x: auto;
	scroll-snap-type: x mandatory;
	-webkit-overflow-scrolling: touch;
	scrollbar-width: none;
}

.pikari-team-card__carousel-track::-webkit-scrollbar {
	display: none;
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
	padding: 0.75rem 0 1.25rem;
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
	background: var(--color-legacyblue);
}
```

- [ ] **Step 3: Build theme CSS**

Run: `cd /Users/steveariss/Sites/operation-smile/canada/wp-content/themes/osc-2024 && npm run build`
Expected: Build succeeds, `pikari-team-card.*.css` output in `public/css/`

- [ ] **Step 4: Sync plugin to DDEV**

```bash
rsync -av --delete --exclude=node_modules --exclude=.git --exclude=tests --exclude=_log --exclude=_specs --exclude=_playground /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team/ ~/Sites/operation-smile/canada/wp-content/plugins/pikari-team/
```

- [ ] **Step 5: Verify with Playwright**

Navigate to `https://operationsmile.ddev.site/card/mark-climie-elliott/` and take a screenshot. Verify:

- Carousel renders with QR code visible as first slide
- Dot indicators visible below
- No console errors

- [ ] **Step 6: Commit plugin changes**

```bash
cd /Users/steveariss/Sites/pikari/wordpress-plugins/pikari-team
git add -A
git commit -m "feat: complete PWA card carousel with looping swipe"
```
