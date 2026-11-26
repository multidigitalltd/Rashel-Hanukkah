/**
 * Rashel Hanukkah: the dashboard's "media" tab.
 *
 * Opens the WordPress media library for a slot or a gallery list and writes
 * the chosen attachment IDs into the form. Nothing is stored until the form
 * is saved; the server checks every ID again.
 *
 * Expects window.rshAdmin (labels) and wp.media.
 */
(function () {
	'use strict';

	var text = window.rshAdmin || {};
	var media = window.wp && window.wp.media;

	if (!media) {
		return;
	}

	/**
	 * Tell screen-reader users what just happened.
	 *
	 * @param {string} message Message.
	 */
	function speak(message) {
		if (window.wp.a11y && message) {
			window.wp.a11y.speak(message);
		}
	}

	/**
	 * Open the media library.
	 *
	 * @param {string}   title    Dialog title.
	 * @param {string}   type     "image" or "video".
	 * @param {boolean}  multiple Allow more than one choice.
	 * @param {Function} done     Called with the chosen attachments (plain objects).
	 */
	function choose(title, type, multiple, done) {
		var frame = media({
			title: title,
			button: { text: text.use },
			library: { type: type },
			multiple: multiple
		});

		frame.on('select', function () {
			done(frame.state().get('selection').toJSON());
		});
		frame.open();
	}

	/**
	 * A small address for an image attachment.
	 *
	 * @param {Object} attachment Attachment.
	 * @param {string} size       Preferred size.
	 * @return {string}
	 */
	function thumb(attachment, size) {
		var sizes = attachment.sizes || {};

		return (sizes[size] || sizes.medium || sizes.thumbnail || sizes.full || attachment).url || '';
	}

	/* Single slots -------------------------------------------------------- */

	Array.prototype.forEach.call(document.querySelectorAll('[data-rsh-media]'), function (slot) {
		var type = slot.getAttribute('data-rsh-type');
		var input = slot.querySelector('[data-rsh-media-id]');
		var state = slot.querySelector('[data-rsh-media-state]');
		var reset = slot.querySelector('[data-rsh-media-reset]');
		var pick = slot.querySelector('[data-rsh-media-pick]');

		function show(url, id) {
			var image = slot.querySelector('[data-rsh-media-thumb]');
			var name = slot.querySelector('[data-rsh-media-name]');

			if (image) {
				image.src = url;
			}
			if (name) {
				name.textContent = url.split('/').pop();
			}

			input.value = id;
			state.textContent = id ? text.fromMedia : text.original;
			reset.hidden = !id;
		}

		pick.addEventListener('click', function () {
			choose('video' === type ? text.pickVideo : text.pickImage, type, false, function (chosen) {
				if (chosen[0]) {
					show('video' === type ? chosen[0].url : thumb(chosen[0], 'medium'), String(chosen[0].id));
					speak(text.fromMedia);
				}
			});
		});

		reset.addEventListener('click', function () {
			show(slot.getAttribute('data-rsh-default'), '');
			speak(text.original);
			pick.focus();
		});
	});

	/* Gallery lists ------------------------------------------------------- */

	Array.prototype.forEach.call(document.querySelectorAll('[data-rsh-list]'), function (list) {
		var items = list.querySelector('[data-rsh-list-items]');
		var template = list.querySelector('[data-rsh-list-template]');
		var add = list.querySelector('[data-rsh-list-add]');
		var max = parseInt(list.getAttribute('data-rsh-max'), 10) || 60;

		function ids() {
			return Array.prototype.map.call(items.querySelectorAll('[data-rsh-list-id]'), function (input) {
				return input.value;
			});
		}

		function append(attachment) {
			var item = template.content.firstElementChild.cloneNode(true);
			var image = item.querySelector('[data-rsh-list-thumb]');

			image.src = thumb(attachment, 'thumbnail');
			image.alt = attachment.alt || '';
			item.querySelector('[data-rsh-list-id]').value = String(attachment.id);
			item.querySelector('[data-rsh-list-alt]').textContent = attachment.alt || text.noAlt;
			items.appendChild(item);
		}

		add.addEventListener('click', function () {
			choose(text.pickImages, 'image', true, function (chosen) {
				var present = ids();
				var full = false;

				chosen.forEach(function (attachment) {
					if (present.indexOf(String(attachment.id)) !== -1) {
						return;
					}
					if (present.length >= max) {
						full = true;
						return;
					}

					append(attachment);
					present.push(String(attachment.id));
				});

				speak(full ? text.full : text.added);
			});
		});

		items.addEventListener('click', function (event) {
			var button = event.target.closest('button');
			var item = button && button.closest('[data-rsh-list-item]');

			if (!item) {
				return;
			}

			if (button.hasAttribute('data-rsh-list-remove')) {
				var neighbour = item.nextElementSibling || item.previousElementSibling;

				item.remove();
				speak(text.removed);
				(neighbour ? neighbour.querySelector('button') : add).focus();
			} else if (button.hasAttribute('data-rsh-list-up') && item.previousElementSibling) {
				items.insertBefore(item, item.previousElementSibling);
				button.focus();
				speak(text.moved);
			} else if (button.hasAttribute('data-rsh-list-down') && item.nextElementSibling) {
				items.insertBefore(item.nextElementSibling, item);
				button.focus();
				speak(text.moved);
			}
		});
	});
})();
