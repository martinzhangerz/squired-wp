(function ($) {

	'use strict';

	window.BuddyBossThemeBbpScrubber = {

		total: 0,
		onscroll_update: true,
		scrubber_height_usable: 0,
		scrubber_height: 0,
		currentnum: 1,
		scrolltimer: null,
		handleani: null, // keep handle any end event.
		draggie: null,

		init: function () {
			var self = window.BuddyBossThemeBbpScrubber;

			self.total = $('.scrubberpost').length;
			self.scrubber_height = $('#reply-timeline-container').outerHeight();
			self.scrubber_height_usable = self.scrubber_height - $('#handle').outerHeight();
            
            if ( self.total < 3 ) {
                $('.scrubber').addClass( 'light' );
            }

			self.init_draggabilly();

			window.addEventListener('scroll', self.onScrubberScroll);

			$('.scrubber').on('click', '.firstpostbtn', function (e) {
				e.preventDefault();
				self.goToPost(1, 'first');
			});

			$('.scrubber').on('click', '.lastpostbtn', function (e) {
				e.preventDefault();
				self.goToPost(self.total, 'last');
			});
		},

		init_draggabilly: function () {
			var self = window.BuddyBossThemeBbpScrubber;

			self.draggie = $('#handle').draggabilly({
				axis: 'y',
				grid: [1, 1],
				containment: '#reply-timeline-container'
			});

			self.draggie.on('dragEnd', function () {
				// make sure handle is not in animate mode.
				$('#handle').removeClass('animate');

				var index = '';
				if (self.currentnum === 1) {
					index = 'first';
				} else if (self.currentnum === self.total) {
					index = 'last';
				}
				self.goToPost(self.currentnum, index);

			});

			self.draggie.on('dragMove', function () {
				self.update_move();
			});

			self.draggie.on('scroll', function () {
				self.update_move();
			});

			self.updateDataOnFront();
		},

		updateDataOnFront: function () {
			var self = window.BuddyBossThemeBbpScrubber;
			self.total = $('.scrubberpost').length;
			$('#currentpost').text(self.currentnum);
			$('#totalposts').text(self.total);

			var current_element = document.getElementsByClassName('scrubberpost')[self.currentnum-1];
			$('.scrubber #date').text($(current_element).data('date'));
		},

		goToPost: function (post, index) {
			var self = window.BuddyBossThemeBbpScrubber;

			var elements = document.getElementsByClassName('scrubberpost');

			if (!elements.length) {
				return false;
			}

			self.total = elements.length;
			self.currentnum = post;

			if ((post > elements.length) || self.total !== 1 && post === self.total) {
				post = self.total - 1;
				index = 'last';
			} else if (post === 1 && index !== 'last') {
				post = 0;
				index = 'first';
			} else if (post === 1 && index === 'last') {
				post = 0;
			} else {
				post = post - 1;
			}

			if (post === 0) {
				self.currentnum = post + 1; // update the num depending on last one index.
			} else if (post === self.total) {
				self.currentnum = self.total;
			}

			var force = false;
			if (index === 'last') {
				post = self.total - 1;
			} else if (index === 'first') {
				post = 0;
				force = true;
			}

			self.onscroll_update = false; // disable on scroll update

			var ele = 0;
			if (typeof elements[post] === 'undefined') {
				ele = elements[elements.length];
			} else {
				ele = elements[post];

				// Highlight Post
				$(ele).parent().addClass('highlight');
			}

			self.update_handle(force);

			$('html, body').animate({
				scrollTop: $(ele).offset().top - (window.innerHeight / 2)
			}, 600, function () {

				// Remove Post Highlight
				setTimeout(function () {
					$(ele).parent().removeClass('highlight');
					self.onscroll_update = true; // enable on scroll update
				}, 200);


			});

		},

		update_move: function () {

			var self = window.BuddyBossThemeBbpScrubber;

			self.total = $('.scrubberpost').length;
			self.scrubber_height_usable = self.scrubber_height - $('#handle').outerHeight();

			// calculating correct y pos of handler.
			var total_val = self.scrubber_height_usable;
			var transform_top = document.getElementById('handle').style.transform.split(',')[1];
			transform_top = typeof transform_top !== 'undefined' ? transform_top : 0;
			var correct_y = parseFloat(document.getElementById('handle').style.top) + parseFloat(transform_top);
			var each_row_size = parseFloat(total_val / self.total);

			for (var i = 1; i <= self.total; i++) {

				if (
					(each_row_size * i) > correct_y &&
					(each_row_size * (i - 1)) < correct_y
				) {
					self.currentnum = i; // update current screen.
					self.updateDataOnFront();
				}

			}

		},

		/**
		 * update position of handle depending on current num.
		 */
		update_handle: function (force) {

			var self = window.BuddyBossThemeBbpScrubber;
			var handle = $('#handle');

			if (!handle.length) {
				return false;
			}

			self.updateDataOnFront();
			self.scrubber_height_usable = self.scrubber_height - handle.outerHeight();

			var total_val = self.scrubber_height_usable;
			var each_row_size = total_val / self.total;
			if (self.currentnum === 1 && (self.total !== 1 || force)) {
				each_row_size = 0;
			}

			handle.addClass('animate');
			handle.css('top', each_row_size * (self.currentnum) + 'px');

			clearTimeout(self.handleani);
			self.handleani = setTimeout(function () {
				if (!handle.length) {
					handle.removeClass('animate');
				}
			}, 2000);

		},

		onScrubberScroll: function () {

			var self = window.BuddyBossThemeBbpScrubber;

			// if scroll update if false by force then don't do anything
			if (!self.onscroll_update) {
				return false;
			}

			var elements = document.getElementsByClassName('scrubberpost');

			if (!elements.length) {
				return false;
			}

			// check if scroll is less than first element, set to first element
			if ((window.scrollY + window.innerHeight.height / 2) < elements[0].getBoundingClientRect().y || window.scrollY === 0) {
				self.currentnum = 1;
				self.update_handle(true);
				return false;
			}

			// check if document scroll height is matched with current scroll, set to last element
			if ((window.scrollY + window.innerHeight) >= document.body.scrollHeight ) {
				self.currentnum = self.total;
				var force = self.total === 1 ? false : true;
				self.update_handle(force);
				return false;
			}

			// check all elements top position and return element which has top less than half of window height
			var inViewLast = self.currentnum;
			var update = false;
			for (var i = 0; i < elements.length; i++) {
				if (elements[i].getBoundingClientRect().y < (window.innerHeight / 2)) {
					update = true;
					inViewLast = i; // always overwrite so store last.
				}
			}

			// if return number is more than total element on page, return last
			if ((inViewLast + 1) > self.total) {
				inViewLast = self.total - 1;
			}

			if (update) {
				self.currentnum = inViewLast + 1; // update the num depending on last one index.
				self.update_handle(false);
			}

		}

	};

	$(document).on('ready', function () {
		window.BuddyBossThemeBbpScrubber.init();
	});

})(jQuery);
