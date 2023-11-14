/* eslint-disable */

/**
 * Front-end functionality for Query Monitor.
 *
 * @package query-monitor
 */

var QM_i18n = {

	// http://core.trac.wordpress.org/ticket/20491

	number_format : function( number, decimals ) {

		if ( isNaN( number ) ) {
			return;
		}

		if ( ! decimals ) {
			decimals = 0;
		}

		number = parseFloat( number );

		var num_float = number.toFixed( decimals ),
			num_int = Math.floor( number ),
			num_str = num_int.toString(),
			fraction = num_float.substring( num_float.indexOf( '.' ) + 1, num_float.length ),
			o = '';

		if ( num_str.length > 3 ) {
			for ( i = num_str.length; i > 3; i -= 3 ) {
				o = qm_number_format.thousands_sep + num_str.slice( i - 3, i ) + o;
			}
			o = num_str.slice( 0, i ) + o;
		} else {
			o = num_str;
		}

		if ( decimals ) {
			o = o + qm_number_format.decimal_point + fraction;
		}

		return o;

	}

};

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

		if ( $('#qm-fatal').length ) {
			console.error(qm_l10n.fatal_error + ': ' + $('#qm-fatal').attr('data-qm-message') );

			if ( $('#wp-admin-bar-query-monitor').length ) {
				$('#wp-admin-bar-query-monitor')
					.addClass('qm-error')
					.find('a').eq(0)
					.text(qm_l10n.fatal_error);

				var fatal_container = document.createDocumentFragment();

				// @TODO:
				var fatal_message_menu = $('#wp-admin-bar-query-monitor-placeholder')
					.clone()
					.attr('id','wp-admin-bar-qm-fatal-message');

				fatal_message_menu
					.find('a').eq(0)
					.text($('#qm-fatal').attr('data-qm-message'))
					.attr('href','#qm-fatal');

				fatal_container.appendChild( fatal_message_menu.get(0) );

				// @TODO:
				var fatal_file_menu = $('#wp-admin-bar-query-monitor-placeholder')
					.clone()
					.attr('id','wp-admin-bar-qm-fatal-file');

				fatal_file_menu
					.find('a').eq(0)
					.text($('#qm-fatal').attr('data-qm-file') + ':' + $('#qm-fatal').attr('data-qm-line'))
					.attr('href','#qm-fatal');

				fatal_container.appendChild( fatal_file_menu.get(0) );

				$('#wp-admin-bar-query-monitor ul').append(fatal_container);
			}
		}

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

		var p = localStorage.getItem( container_position_key );
		var h = localStorage.getItem( container_height_key );
		var w = localStorage.getItem( container_width_key );
		if ( p === 'right' ) {
			if ( w !== null ) {
				if ( w < minwidth ) {
					w = minwidth;
				}
				if ( w > maxwidth ) {
					w = maxwidth;
				}
				container.width( w );
			}
		} else if ( p !== 'right' && h !== null ) {
			if ( h < minheight ) {
				h = minheight;
			}
			if ( h > maxheight ) {
				h = maxheight;
			}
			container.height( h );
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
