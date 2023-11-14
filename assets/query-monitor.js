/* eslint-disable */

/**
 * Front-end functionality for Query Monitor.
 *
 * @package query-monitor
 */

if ( window.jQuery ) {

	jQuery( function($) {
		var toolbarHeight = $('#wpadminbar').length ? $('#wpadminbar').outerHeight() : 0;
		var minheight = 100;
		var maxheight = ( $(window).height() - toolbarHeight );
		var minwidth = 300;
		var maxwidth = $(window).width();
		var container = $('#query-monitor-main');
		var body = $('body');
		var body_margin = body.css('margin-bottom');
		var container_height_key = 'qm-container-height';
		var container_position_key = 'qm-container-position';
		var container_width_key = 'qm-container-width';
		var startY, startX, resizerHeight;

		$(document).on('mousedown touchstart', '.qm-resizer', function(event) {
			event.stopPropagation();

			resizerHeight = $(this).outerHeight() - 1;
			startY = container.outerHeight() + ( event.clientY || event.originalEvent.targetTouches[0].pageY );
			startX = container.outerWidth() + ( event.clientX || event.originalEvent.targetTouches[0].pageX );

			if ( ! container.hasClass('qm-show-right') ) {
				$(document).on('mousemove touchmove', qm_do_resizer_drag_vertical);
			} else {
				$(document).on('mousemove touchmove', qm_do_resizer_drag_horizontal);
			}

			$(document).on('mouseup touchend', qm_stop_resizer_drag);
		});

		function qm_do_resizer_drag_vertical(event) {
				var h = ( startY - ( event.clientY || event.originalEvent.targetTouches[0].pageY ) );
				if ( h >= resizerHeight && h <= maxheight ) {
					container.height( h );
					body.css( 'margin-bottom', 'calc( ' + body_margin + ' + ' + h + 'px )' );
				}
		}

		function qm_do_resizer_drag_horizontal(event) {
				var w = ( startX - event.clientX );
				if ( w >= minwidth && w <= maxwidth ) {
					container.width( w );
				}
				body.css( 'margin-bottom', '' );
		}

		function qm_stop_resizer_drag(event) {
			$(document).off('mousemove touchmove', qm_do_resizer_drag_vertical);
			$(document).off('mousemove touchmove', qm_do_resizer_drag_horizontal);
			$(document).off('mouseup touchend', qm_stop_resizer_drag);

			if ( ! container.hasClass('qm-show-right') ) {
				localStorage.removeItem( container_position_key );
				localStorage.setItem( container_height_key, container.height() );
			} else {
				localStorage.setItem( container_position_key, 'right' );
				localStorage.setItem( container_width_key, container.width() );
			}
		}

		$(window).on('resize', function(){
			var h = container.height();
			var w = container.width();

			maxheight = ( $(window).height() - toolbarHeight );
			maxwidth = $(window).width();

			if ( h < minheight ) {
				container.height( minheight );
			}
			if ( h > maxheight ) {
				container.height( maxheight );
			}
			localStorage.setItem( container_height_key, container.height() );

			if ( w > $(window).width() ) {
				container.width( minwidth );
				localStorage.setItem( container_width_key, container.width() );
			}
			if ( $(window).width() < 960 ) {
				container.removeClass('qm-show-right');
				localStorage.removeItem( container_position_key );
			}
		});
	} );

}
