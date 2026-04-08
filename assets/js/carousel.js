( function() {
	const track = document.querySelector( '.pikari-team-card__carousel-track' );
	if ( ! track ) {
		return;
	}

	const slides = track.querySelectorAll( '.pikari-team-card__slide' );

	if ( slides.length < 2 ) {
		return;
	}

	const dots = document.querySelectorAll( '.pikari-team-card__dot' );

	// Update active dot via IntersectionObserver.
	const observer = new IntersectionObserver(
		function( entries ) {
			entries.forEach( function( entry ) {
				if ( entry.isIntersecting ) {
					const index = Number( entry.target.dataset.slide );
					dots.forEach( function( dot, i ) {
						dot.classList.toggle( 'active', i === index );
						dot.setAttribute( 'aria-selected', i === index ? 'true' : 'false' );
					} );
				}
			} );
		},
		{ root: track, threshold: 0.5 }
	);

	slides.forEach( function( slide ) {
		observer.observe( slide );
	} );

	// Dot click navigation.
	dots.forEach( function( dot ) {
		dot.addEventListener( 'click', function() {
			const index = Number( dot.dataset.slide );
			track.scrollTo( {
				left: slides[ index ].offsetLeft - track.offsetLeft,
				behavior: 'smooth',
			} );
		} );
	} );

	// Looping: when scroll settles at an edge, jump to the opposite end.
	let scrollTimer;
	track.addEventListener( 'scroll', function() {
		clearTimeout( scrollTimer );
		scrollTimer = setTimeout( function() {
			const maxScroll = track.scrollWidth - track.clientWidth;
			if ( track.scrollLeft <= 0 ) {
				// At the start — loop to last slide.
				requestAnimationFrame( function() {
					track.style.scrollBehavior = 'auto';
					track.scrollLeft = maxScroll;
					track.style.scrollBehavior = '';
				} );
			} else if ( track.scrollLeft >= maxScroll - 1 ) {
				// At the end — loop to first slide.
				requestAnimationFrame( function() {
					track.style.scrollBehavior = 'auto';
					track.scrollLeft = 0;
					track.style.scrollBehavior = '';
				} );
			}
		}, 150 );
	} );
}() );
