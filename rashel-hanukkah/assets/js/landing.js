/**
 * Rashel Hanukkah: landing page behaviour.
 *
 * Everything here is an enhancement. The page is complete without this file:
 * the questions are stacked, both course lists are visible and the countdown
 * shows the time the page was rendered.
 *
 * Motion (the pinned questions, the reveals, the heading effect) runs only
 * while <html> carries "rsh-motion". The inline boot script sets it when
 * neither the system nor the accessibility toolbar asked for reduced motion,
 * and the toolbar's "stop animations" removes it on the spot.
 *
 * Expects window.rshLanding = { deadline } (Unix milliseconds).
 */
(function () {
	'use strict';

	var root = document.documentElement;
	var config = window.rshLanding || {};

	/**
	 * May the page move right now? Read every time, because the accessibility
	 * toolbar can switch motion off and on while the page is open.
	 *
	 * @return {boolean}
	 */
	function motion() {
		return root.classList.contains('rsh-motion');
	}

	/**
	 * Run a function for every element that matches a selector.
	 *
	 * @param {string}   selector CSS selector.
	 * @param {Function} fn       Called with the element and its index.
	 */
	function each(selector, fn) {
		Array.prototype.forEach.call(document.querySelectorAll(selector), fn);
	}

	/* ------------------------------------------------------------------ */
	/* Countdown                                                           */
	/* ------------------------------------------------------------------ */

	/**
	 * Zero-pad a number to two digits.
	 *
	 * @param {number} value Number.
	 * @return {string}
	 */
	function pad(value) {
		return (value < 10 ? '0' : '') + value;
	}

	function tick() {
		var left = Math.max(0, Math.floor((config.deadline - Date.now()) / 1000));
		var days = Math.floor(left / 86400);
		left -= days * 86400;
		var hours = Math.floor(left / 3600);
		left -= hours * 3600;
		var minutes = Math.floor(left / 60);
		var parts = { days: pad(days), hours: pad(hours), minutes: pad(minutes), seconds: pad(left - minutes * 60) };

		each('[data-rsh-unit]', function (node) {
			var value = parts[node.getAttribute('data-rsh-unit')];
			if (value !== undefined && node.textContent !== value) {
				node.textContent = value;
			}
		});
	}

	if (typeof config.deadline === 'number' && config.deadline > 0) {
		tick();
		window.setInterval(tick, 1000);
	}

	/* ------------------------------------------------------------------ */
	/* Course tabs                                                         */
	/* ------------------------------------------------------------------ */

	(function tabs() {
		var list = document.querySelector('[data-rsh-tabs] [role="tablist"]');
		if (!list) {
			return;
		}

		var buttons = Array.prototype.slice.call(list.querySelectorAll('[role="tab"]'));

		/**
		 * Show one category and hide the other.
		 *
		 * @param {HTMLElement} active The tab to select.
		 * @param {boolean}     focus  Move focus to it (keyboard navigation).
		 */
		function select(active, focus) {
			buttons.forEach(function (button) {
				var on = button === active;
				var panel = document.getElementById(button.getAttribute('aria-controls'));

				button.setAttribute('aria-selected', on ? 'true' : 'false');
				button.tabIndex = on ? 0 : -1;
				if (panel) {
					panel.hidden = !on;
				}
			});

			if (focus) {
				active.focus();
			}
		}

		buttons.forEach(function (button, index) {
			button.addEventListener('click', function () {
				select(button, false);
			});

			button.addEventListener('keydown', function (event) {
				var next = null;

				// Right-to-left: the left arrow moves to the next tab.
				if (event.key === 'ArrowLeft') {
					next = buttons[(index + 1) % buttons.length];
				} else if (event.key === 'ArrowRight') {
					next = buttons[(index - 1 + buttons.length) % buttons.length];
				} else if (event.key === 'Home') {
					next = buttons[0];
				} else if (event.key === 'End') {
					next = buttons[buttons.length - 1];
				}

				if (next) {
					event.preventDefault();
					select(next, true);
				}
			});
		});

		select(buttons[0], false);
	})();

	/* ------------------------------------------------------------------ */
	/* Sticky bottom bar                                                   */
	/* ------------------------------------------------------------------ */

	var bar = document.querySelector('[data-rsh-bar]');
	var barVisible = false;

	function updateBar() {
		if (!bar) {
			return;
		}

		var visible = window.scrollY > 900;
		if (visible === barVisible) {
			return;
		}

		barVisible = visible;
		bar.classList.toggle('is-visible', visible);
		// The toolbar launcher, the privacy notice and the footer make room for the bar.
		root.style.setProperty('--rsh-bar-space', visible ? bar.offsetHeight + 'px' : '0px');
	}

	/* ------------------------------------------------------------------ */
	/* Hook: pinned questions and photo parallax                           */
	/* ------------------------------------------------------------------ */

	var hook = document.querySelector('[data-rsh-hook]');
	var questions = hook ? Array.prototype.slice.call(hook.querySelectorAll('[data-rsh-q]')) : [];
	var photos = hook ? Array.prototype.slice.call(hook.querySelectorAll('[data-rsh-step]')) : [];
	var stage = hook ? hook.querySelector('.rsh-hook__stage') : null;
	var lastStep = 0;
	var lastProgress = -1;

	function updateHook() {
		// Without motion the questions are stacked by the stylesheet and nothing follows the scroll.
		if (!hook || !motion()) {
			return;
		}

		var box = hook.getBoundingClientRect();
		var span = box.height - window.innerHeight;
		var progress = span > 0 ? Math.min(1, Math.max(0, -box.top / span)) : 0;
		var step = progress < 0.22 ? 1 : progress < 0.52 ? 2 : 3;
		var rounded = Math.round(progress * 200) / 200;

		if (rounded !== lastProgress) {
			lastProgress = rounded;
			stage.style.setProperty('--rsh-p', String(rounded));
		}

		if (step === lastStep) {
			return;
		}
		lastStep = step;

		questions.forEach(function (question, index) {
			question.classList.toggle('is-active', index === step - 1);
			question.classList.toggle('is-past', index < step - 1);
		});

		photos.forEach(function (photo) {
			photo.classList.toggle('is-on', step >= parseInt(photo.getAttribute('data-rsh-step'), 10));
		});
	}

	/* ------------------------------------------------------------------ */
	/* Scroll                                                              */
	/* ------------------------------------------------------------------ */

	var queued = false;

	function onScroll() {
		if (queued) {
			return;
		}
		queued = true;
		window.requestAnimationFrame(function () {
			queued = false;
			updateBar();
			updateHook();
		});
	}

	window.addEventListener('scroll', onScroll, { passive: true });
	window.addEventListener('resize', onScroll, { passive: true });
	window.addEventListener('load', onScroll);
	onScroll();
	// Fonts and images can move the layout after the first measure.
	window.setTimeout(onScroll, 300);
	window.setTimeout(onScroll, 1200);

	/* ------------------------------------------------------------------ */
	/* Reveals and the heading effect                                      */
	/* ------------------------------------------------------------------ */

	// Observed even while motion is off, so content is already revealed if the visitor turns it back on.
	if (!('IntersectionObserver' in window)) {
		each('[data-rsh-reveal]', function (node) {
			node.classList.add('is-in');
		});
		each('[data-rsh-h1]', function (node) {
			node.classList.add('is-lit');
		});
		return;
	}

	var reveal = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			if (entry.isIntersecting) {
				entry.target.classList.add('is-in');
				reveal.unobserve(entry.target);
			}
		});
	}, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

	each('[data-rsh-reveal]', function (node) {
		reveal.observe(node);
	});

	var heading = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			if (entry.isIntersecting) {
				entry.target.classList.add('is-lit');
				heading.unobserve(entry.target);
			}
		});
	}, { threshold: 0.4 });

	each('[data-rsh-h1]', function (node) {
		heading.observe(node);
	});
})();
