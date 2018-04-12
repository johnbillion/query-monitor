/*
Copyright 2009-2017 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

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
			num_int   = Math.floor( number ),
			num_str   = num_int.toString(),
			fraction  = num_float.substring( num_float.indexOf( '.' ) + 1, num_float.length ),
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
		var minheight = 100;
		var maxheight = ( $(window).height() - 50 );
		var container = $('#query-monitor');
		var container_storage_key = 'qm-container-height';
		var container_pinned_key = 'qm-container-pinned';

		if ( $('#query-monitor').hasClass('qm-peek') ) {
			minheight = 27;
		}

		$('#query-monitor').removeClass('qm-no-js').addClass('qm-js');

		var link_click = function(e){
			show_panel( $( this ).attr('href') );
			e.preventDefault();
		};

		var show_panel = function( panel ) {
			$('#query-monitor').addClass('qm-show').removeClass('qm-hide');
			$( '.qm' ).removeClass('qm-panel-show');
			$('#qm-panels').scrollTop(0);
			$( panel ).addClass('qm-panel-show');

			if ( container.height() < minheight ) {
				container.height( minheight );
			}

			$('#qm-panel-menu').find('a').removeClass('qm-selected-menu');
			var selected_menu = $('#qm-panel-menu').find('a[href="' + panel + '"]').addClass('qm-selected-menu');

			if ( selected_menu.length ) {
				var selected_menu_pos = selected_menu.position();
				var menu_height = $('#qm-panel-menu').height();
				var menu_scroll = $('#qm-panel-menu').scrollTop();

				if ( ( selected_menu_pos.top > ( menu_height + menu_scroll ) ) || ( selected_menu_pos.top < menu_scroll ) ) {
					$('#qm-panel-menu').scrollTop( selected_menu_pos.top - ( menu_height / 2 ) );
				}
			}

			$('.qm-title-heading select').val(panel);

			if ( localStorage.getItem( container_pinned_key ) ) {
				localStorage.setItem( container_pinned_key, panel );
			}

			$( panel ).find('.qm-filter').change();

		};

		if ( $('#wp-admin-bar-query-monitor').length ) {

			var admin_bar_menu_container = document.createDocumentFragment();

			if ( window.qm && window.qm.menu ) {
				$('#wp-admin-bar-query-monitor')
					.addClass(qm.menu.top.classname)
					.find('a').eq(0)
					.html(qm.menu.top.title)
				;

				$.each( qm.menu.sub, function( i, el ) {

					var new_menu = $('#wp-admin-bar-query-monitor-placeholder')
						.clone()
						.attr('id','wp-admin-bar-' + el.id)
					;
					new_menu
						.find('a').eq(0)
						.html(el.title)
						.attr('href',el.href)
					;

					if ( ( typeof el.meta != 'undefined' ) && ( typeof el.meta.classname != 'undefined' ) ) {
						new_menu.addClass(el.meta.classname);
					}

					admin_bar_menu_container.appendChild( new_menu.get(0) );

				} );

				$('#wp-admin-bar-query-monitor ul').append(admin_bar_menu_container);
			}

			$('#wp-admin-bar-query-monitor').find('a').on('click',link_click);

			$('#wp-admin-bar-query-monitor,#wp-admin-bar-query-monitor-default').show();

		} else {
			$('#query-monitor').addClass('qm-peek').removeClass('qm-hide');
			$('#qm-overview').addClass('qm-panel-show');
		}

		$('#qm-panel-menu').find('a').on('click',link_click);

		$('#query-monitor').find('.qm-filter').on('change',function(e){

			var filter = $(this).attr('data-filter'),
				table  = $(this).closest('table'),
				tr     = table.find('tbody tr[data-qm-' + filter + ']'),
				// Escape the following chars with a backslash before passing into jQ selectors: [ ] ( ) ' " \
				val    = $(this).val().replace(/[[\]()'"\\]/g, "\\$&"),
				total  = tr.removeClass('qm-hide-' + filter).length,
				hilite = $(this).attr('data-highlight'),
				time   = 0;

			key = $(this).attr('id');
			if ( val ) {
				localStorage.setItem( key, $(this).val() );
			} else {
				localStorage.removeItem( key );
			}

			if ( hilite ) {
				table.find('tr').removeClass('qm-highlight');
			}

			if ( $(this).val() !== '' ) {
				if ( hilite ) {
					tr.filter('[data-qm-' + hilite + '*="' + val + '"]').addClass('qm-highlight');
				}
				tr.not('[data-qm-' + filter + '*="' + val + '"]').addClass('qm-hide-' + filter);
				$(this).addClass('qm-highlight');
			} else {
				$(this).removeClass('qm-highlight');
			}

			var matches = tr.filter(':visible');
			matches.each(function(i){
				var row_time = $(this).attr('data-qm-time');
				if ( row_time ) {
					time += parseFloat( row_time );
				}
			});
			if ( time ) {
				time = QM_i18n.number_format( time, 4 );
			}

			var results = table.find('.qm-items-shown');
			results.find('.qm-items-number').text( QM_i18n.number_format( matches.length, 0 ) );
			results.find('.qm-items-time').text(time);

			if ( table.find('.qm-filter.qm-highlight').length ) {
				results.removeClass('qm-hide');
			} else {
				results.addClass('qm-hide');
			}
		});

		$('#query-monitor').find('.qm-filter').each(function () {
			var key = $(this).attr('id');
			var value = localStorage.getItem( key );
			if ( value !== null ) {
				// Escape the following chars with a backslash before passing into jQ selectors: [ ] ( ) ' " \
				var val = value.replace(/[[\]()'"\\]/g, "\\$&");
				if ( ! $(this).find('option[value="' + val + '"]').length ) {
					$('<option>').attr('value',value).text(value).appendTo(this);
				}
				$(this).val(value).change();
			}
		});

		$('#query-monitor').find('.qm-filter-trigger').on('click',function(e){
			var filter = $(this).data('qm-filter'),
				value  = $(this).data('qm-value'),
				target = $(this).data('qm-target');
			$('#qm-' + target).find('.qm-filter').not('[data-filter="' + filter + '"]').val('').removeClass('qm-highlight').change();
			$('#qm-' + target).find('[data-filter="' + filter + '"]').val(value).addClass('qm-highlight').change();
			show_panel( '#qm-' + target );
			e.preventDefault();
		});

		$('#query-monitor').find('.qm-toggle').on('click',function(e){
			var el = $(this);
			var currentState = el.attr('aria-expanded');
			var newState = 'true';
			if (currentState === 'true') {
				newState = 'false';
			}
			el.attr('aria-expanded', newState);
			var toggle = $(this).closest('td').find('.qm-toggled');
			if ( currentState === 'true' ) {
				if ( toggle.length ) {
					toggle.slideToggle(200,function(){
						el.closest('td').removeClass('qm-toggled-on');
						el.text(el.attr('data-on'));
					});
				} else {
					el.closest('td').removeClass('qm-toggled-on');
					el.text(el.attr('data-on'));
				}
			} else {
				el.closest('td').addClass('qm-toggled-on');
				el.text(el.attr('data-off'));
				toggle.slideToggle(200);
			}
			e.preventDefault();
		});

		$('#query-monitor').find('.qm-highlighter').on('mouseenter',function(e){

			var subject = $(this).data('qm-highlight');
			var table   = $(this).closest('table');

			if ( ! subject ) {
				return;
			}

			$(this).addClass('qm-highlight');

			$.each( subject.split(' '), function( i, el ){
				table.find('tr[data-qm-subject="' + el + '"]').addClass('qm-highlight');
			});

		}).on('mouseleave',function(e){

			$(this).removeClass('qm-highlight');
			$(this).closest('table').find('tr').removeClass('qm-highlight');

		});

		$( document ).ajaxSuccess( function( event, response, options ) {

			var errors = response.getResponseHeader( 'X-QM-php_errors-error-count' );

			if ( ! errors ) {
				return event;
			}

			errors = parseInt( errors, 10 );

			if ( window.console ) {
				console.group( qm_l10n.ajax_error );
			}

			for ( var key = 1; key <= errors; key++ ) {

				error = $.parseJSON( response.getResponseHeader( 'X-QM-php_errors-error-' + key ) );

				if ( window.console ) {
					console.error( error );
				}

				if ( $('#wp-admin-bar-query-monitor').length ) {
					if ( ! qm.ajax_errors[error.type] ) {
						$('#wp-admin-bar-query-monitor')
							.addClass('qm-' + error.type)
							.find('a').first().append('<span class="ab-label qm-ajax-' + error.type + '"> &nbsp; Ajax: ' + error.type + '</span>')
						;
					}
				}

				qm.ajax_errors[error.type] = true;

			}

			if ( window.console ) {
				console.groupEnd();
			}

			return event;

		} );

		$('.qm-auth').on('click',function(e){
			var action = $(this).data('action');

			$.ajax(qm_l10n.ajaxurl,{
				type : 'POST',
				data : {
					action : 'qm_auth_' + action,
					nonce  : qm_l10n.auth_nonce[action]
				},
				success : function(response){
					alert( response.data );
				},
				dataType : 'json',
				xhrFields: {
					withCredentials: true
				}
			});

			e.preventDefault();
		});

		$.qm.tableSort({target: $('.qm-sortable')});

		var startY, resizerHeight, toolbarHeight;

		toolbarHeight = $('#wpadminbar').outerHeight();

		$(document).on('mousedown', '#qm-title', function(event) {
			resizerHeight = $(this).outerHeight() - 1;
			startY        = container.outerHeight() + event.clientY;

			$(document).on('mousemove', qm_do_resizer_drag);
			$(document).on('mouseup', qm_stop_resizer_drag);
		});

		function qm_do_resizer_drag(event) {
			var h = ( startY - event.clientY );
			if ( h >= resizerHeight && h < ( $(window).height() - toolbarHeight ) ) {
				container.height( h );
			}
		}

		function qm_stop_resizer_drag(event) {
			$(document).off('mousemove', qm_do_resizer_drag);
			$(document).off('mouseup', qm_stop_resizer_drag);

			localStorage.setItem( container_storage_key, container.height() );
		}

		var h = localStorage.getItem( container_storage_key );
		if ( h !== null && ! $('#query-monitor').hasClass('qm-peek') ) {
			if ( h < minheight ) {
				h = minheight;
			}
			if ( h > maxheight ) {
				h = maxheight;
			}
			container.height( h );
		}

		$(window).on('resize', function(){
			var maxheight = ( $(window).height() - toolbarHeight );
			var h = container.height();

			if ( h < minheight ) {
				container.height( minheight );
			}
			if ( h > maxheight ) {
				container.height( maxheight );
			}
			localStorage.setItem( container_storage_key, container.height() );
		});

		$('.qm-button-container-close').click(function(){
			$('#query-monitor').removeClass('qm-show');
			localStorage.removeItem( container_pinned_key );
			$('.qm-button-container-pin').removeClass( 'qm-button-active' );
		});

		$('.qm-button-container-pin').click(function(){
			if ( $(this).hasClass( 'qm-button-active' ) ) {
				localStorage.removeItem( container_pinned_key );
			} else {
				localStorage.setItem( container_pinned_key, '#' + $('.qm-panel-show').first().attr('id') );
			}

			$(this).toggleClass( 'qm-button-active' );
		});

		$('.qm-button-container-settings').click(function(){
			show_panel( '#qm-settings' );
		});

		var pinned = localStorage.getItem( container_pinned_key );
		if ( pinned && $( pinned ).length ) {
			show_panel( pinned );
			$('.qm-button-container-pin').addClass( 'qm-button-active' );
		}

		$('.qm-title-heading select').change(function(){
			show_panel( $(this).val() );
		});

	} );

	/**
	 * Table sorting library.
	 *
	 * This is a modified version of jQuery table-sort v0.1.1
	 * https://github.com/gajus/table-sort
	 *
	 * Licensed under the BSD.
	 * https://github.com/gajus/table-sort/blob/master/LICENSE
	 *
	 * Author: Gajus Kuizinas <g.kuizinas@anuary.com>
	 */
	(function ($) {
		$.qm = $.qm || {};
		$.qm.tableSort = function (settings) {
			// @param	object	columns	NodeList table colums.
			// @param	integer	row_width	defines the number of columns per row.
			var table_to_array	= function (columns, row_width) {
				columns = Array.prototype.slice.call(columns, 0);

				var rows      = [];
				var row_index = 0;

				for (var i = 0, j = columns.length; i < j; i += row_width) {
					var row	= [];

					for (var k = 0; k < row_width; k++) {
						var e = columns[i + k];
						var data = e.dataset.qmSortWeight;

						if (data === undefined) {
							data = e.textContent || e.innerText;
						}

						var number = parseFloat(data);

						data = isNaN(number) ? data : number;

						row.push(data);
					}

					rows.push({index: row_index++, data: row});
				}

				return rows;
			};

			if ( ! settings.target || ! ( settings.target instanceof $) ) {
				throw 'Target is not defined or it is not instance of jQuery.';
			}

			settings.target.each(function () {
				var table = $(this);

				table.find('.qm-sortable-column').on('click', function (e) {
					var desc = ! $(this).hasClass('qm-sorted-desc');
					var index = $(this).index();

					table.find('thead th').removeClass('qm-sorted-asc qm-sorted-desc');

					if ( desc ) {
						$(this).addClass('qm-sorted-desc');
					} else {
						$(this).addClass('qm-sorted-asc');
					}

					table.find('tbody').each(function () {
						var tbody = $(this);
						var rows = this.rows;
						var columns = this.getElementsByTagName('td');

						if (this.data_matrix === undefined) {
							this.data_matrix = table_to_array(columns, $(rows[0]).find('td').length);
						}

						var data = this.data_matrix;

						data.sort(function (a, b) {
							if (a.data[index] == b.data[index]) {
								return 0;
							}

							return (desc ? a.data[index] > b.data[index] : a.data[index] < b.data[index]) ? -1 : 1;
						});

						// Detach the tbody to prevent unnecessary overhead related
						// to the browser environment.
						tbody = tbody.detach();

						// Convert NodeList into an array.
						rows = Array.prototype.slice.call(rows, 0);

						var last_row = rows[data[data.length - 1].index];

						for (var i = 0, j = data.length - 1; i < j; i++) {
							tbody[0].insertBefore(rows[data[i].index], last_row);

							// Restore the index.
							data[i].index = i;
						}

						// Restore the index.
						data[data.length - 1].index = data.length - 1;

						table.append(tbody);
					});
					e.preventDefault();
				});
			});
		};
	})(jQuery);

}

if ( ( 'undefined' === typeof jQuery ) || ! jQuery ) {
	window.addEventListener('load', function() {
		/* Fallback for running without jQuery (`QM_NO_JQUERY`) */
		document.getElementById( 'query-monitor' ).className += ' qm-broken';
		console.error( document.getElementById( 'qm-broken' ).textContent );
		var menu_item = document.getElementById( 'wp-admin-bar-query-monitor' );
		if ( menu_item ) {
			menu_item.addEventListener( 'click', function() {
				document.getElementById( 'query-monitor' ).className += ' qm-show';
			} );
		}
	} );
}
