/**
 * Related Cards Slider for Pokedex entries.
 * 
 * Initializes slick slider and equalHeight logic for related card carousels.
 * 
 * @package Primetime_Pokedex
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		
		/**
		 * Calculate and set equal heights for card images.
		 */
		function equalHeight() {
			var highest = 0;
			
			$('.card-wrap img').delay(1000).each(function() {
				if ($(this).outerHeight() > highest) {
					highest = $(this).outerHeight();
				}
			});
			
			$('.card-wrap').each(function() {
				$(this).outerHeight((highest + 50) + 'px');
			});
			
			$('.slick-list').css('height', (highest + 90) + 'px');
		}
		
		// Debounced resize handler
		var doit;
		window.onresize = function() {
			clearTimeout(doit);
			doit = setTimeout(equalHeight, 100);
		};
		
		// Initialize slick slider on related cards
		$('.related-cards').on('init', function(event, slick) {
			equalHeight();
		});
		
		$('.related-cards').on('breakpoint', function(event, slick, breakpoint) {
			equalHeight();
		});
		
		$('.related-cards').slick({
			infinite: true,
			speed: 300,
			slidesToShow: 5,
			centerMode: true,
			slidesToScroll: 1,
			prevArrow: '<span class="slick-prev"><</span>',
			nextArrow: '<span class="slick-next">></span>',
			responsive: [
				{
					breakpoint: 1024,
					settings: {
						slidesToShow: 3,
						slidesToScroll: 1
					}
				},
				{
					breakpoint: 600,
					settings: {
						slidesToShow: 1,
						slidesToScroll: 1
					}
				}
			]
		});
		
		// Custom next button handler
		$('.custom-next').on('click', function() {
			$('.related-cards').slick('slickSetOption', {
				slidesToScroll: 5,
				responsive: [
					{
						breakpoint: 1024,
						settings: {
							slidesToShow: 3,
							slidesToScroll: 3
						}
					}
				]
			}, true).slick('slickNext').slick('slickSetOption', {
				slidesToScroll: 1,
				responsive: [
					{
						breakpoint: 1024,
						settings: {
							slidesToShow: 3,
							slidesToScroll: 1
						}
					}
				]
			}, true);
		});
		
		// Custom prev button handler
		$('.custom-prev').on('click', function() {
			$('.related-cards').slick('slickSetOption', {
				slidesToScroll: 5,
				responsive: [
					{
						breakpoint: 1024,
						settings: {
							slidesToShow: 3,
							slidesToScroll: 3
						}
					}
				]
			}, true).slick('slickPrev').slick('slickSetOption', {
				slidesToScroll: 1,
				responsive: [
					{
						breakpoint: 1024,
						settings: {
							slidesToShow: 3,
							slidesToScroll: 1
						}
					}
				]
			}, true);
		});
		
	});
	
})(jQuery);
