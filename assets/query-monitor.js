/**
 * Front-end functionality for Query Monitor.
 *
 * @package query-monitor
 */

var QM_i18n = {

	// https://core.trac.wordpress.org/ticket/20491

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
		var container_pinned_key = 'qm-' + ( $('body').hasClass('wp-admin') ? 'admin' : 'front' ) + '-container-pinned';
		var container_position_key = 'qm-container-position';
		var container_width_key = 'qm-container-width';

		if ( container.hasClass('qm-peek') ) {
			minheight = 27;
		}

		container.removeClass('qm-no-js').addClass('qm-js');

		var theme = localStorage.getItem( 'qm-theme' );
		if ( theme ) {
			container.attr('data-theme', theme);
			$('.qm-theme-toggle[value="' + theme + '"]').prop('checked', true);
		}

		if ( $('#qm-fatal').length ) {
			console.error(qm_l10n.fatal_error + ': ' + $('#qm-fatal').attr('data-qm-message') );

			if ( $('#wp-admin-bar-query-monitor').length ) {
				$('#wp-admin-bar-query-monitor')
					.addClass('qm-error')
					.find('a').eq(0)
					.text(qm_l10n.fatal_error);

				var fatal_container = document.createDocumentFragment();

				var fatal_message_menu = $('#wp-admin-bar-query-monitor-placeholder')
					.clone()
					.attr('id','wp-admin-bar-qm-fatal-message');

				fatal_message_menu
					.find('a').eq(0)
					.text($('#qm-fatal').attr('data-qm-message'))
					.attr('href','#qm-fatal');

				fatal_container.appendChild( fatal_message_menu.get(0) );

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

		var link_click = function(e){
			var href = $( this ).attr('href') || $( this ).data('qm-href');

			if ( '#qm-fatal' === href ) {
				return;
			}

			show_panel( href );
			$(href).focus();
			$('#wp-admin-bar-query-monitor').removeClass('hover');
			e.preventDefault();
		};

		var stripes = function( table ) {
			table.each(function() {
				$(this).find('tbody tr').removeClass('qm-odd').not('[class*="qm-hide-"]').filter(':even').addClass('qm-odd');
			} );
		};

		var show_panel = function( panel ) {
			container.addClass('qm-show').removeClass('qm-hide');
			$( '.qm' ).removeClass('qm-panel-show');
			$('#qm-panels').scrollTop(0);
			$( panel ).addClass('qm-panel-show');

			if ( container.height() < minheight ) {
				container.height( minheight );
			}

			if ( container.hasClass('qm-show-right') ) {
				body.css( 'margin-bottom', '' );
			} else {
				body.css( 'margin-bottom', 'calc( ' + body_margin + ' + ' + container.height() + 'px )' );
			}

			$('#qm-panel-menu').find('button').removeAttr('aria-selected');
			$('#qm-panel-menu').find('li').removeClass('qm-current-menu');
			var selected_menu = $('#qm-panel-menu').find('[data-qm-href="' + panel + '"]').attr('aria-selected',true);

			if ( selected_menu.length ) {
				var selected_menu_top = selected_menu.position().top - 27;
				var menu_height = $('#qm-panel-menu').height();
				var menu_scroll = $('#qm-panel-menu').scrollTop();
				selected_menu.closest('#qm-panel-menu > ul > li').addClass('qm-current-menu');

				var selected_menu_off_bottom = ( selected_menu_top > ( menu_height ) );
				var selected_menu_off_top = ( selected_menu_top < 0 );

				if ( selected_menu_off_bottom || selected_menu_off_top ) {
					$('#qm-panel-menu').scrollTop( selected_menu_top + menu_scroll - ( menu_height / 2 ) + ( selected_menu.outerHeight() / 2 ) );
				}
			}

			$('.qm-title-heading select').val(panel);

			localStorage.setItem( container_pinned_key, panel );

			var filters = $( panel ).find('.qm-filter');

			if ( filters.length ) {
				filters.trigger('change');
			} else {
				stripes( $(panel).find('table') );
			}

		};

		if ( $('#wp-admin-bar-query-monitor').length ) {

			var admin_bar_menu_container = document.createDocumentFragment();

			if ( window.QueryMonitorData && window.QueryMonitorData.menu ) {
				$('#wp-admin-bar-query-monitor')
					.addClass(QueryMonitorData.menu.top.classname)
					.attr('dir','ltr')
					.find('a').eq(0)
					.html(QueryMonitorData.menu.top.title);

				$.each( QueryMonitorData.menu.sub, function( i, el ) {

					var new_menu = $('#wp-admin-bar-query-monitor-placeholder')
						.clone()
						.attr('id','wp-admin-bar-' + el.id);
					new_menu
						.find('a').eq(0)
						.html(el.title)
						.attr('href',el.href);

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
			container.addClass('qm-peek').removeClass('qm-hide');
			$('#qm-overview').addClass('qm-panel-show');
		}

		$('#qm-panel-menu').find('button').on('click',link_click);

		container.find('.qm-filter').on('change',function(e){

			var filter = $(this).attr('data-filter'),
				table = $(this).closest('table'),
				tr = table.find('tbody tr[data-qm-' + filter + ']'),
				// Escape the following chars with a backslash before passing into jQ selectors: [ ] ( ) ' " \
				val = $(this).val().replace(/[[\]()'"\\]/g, "\\$&"),
				total = tr.removeClass('qm-hide-' + filter).length,
				hilite = $(this).attr('data-highlight'),
				time = 0;

			key = $(this).attr('id');
			if ( val ) {
				sessionStorage.setItem( key, $(this).val() );
			} else {
				sessionStorage.removeItem( key );
			}

			if ( hilite ) {
				table.find('tr').removeClass('qm-highlight');
			}

			if ( $(this).val() !== '' ) {
				if ( hilite ) {
					tr.filter('[data-qm-' + hilite + '*="' + val + '"]').addClass('qm-highlight');
				}
				tr.not('[data-qm-' + filter + '*="' + val + '"]').addClass('qm-hide-' + filter);
				$(this).closest('th').addClass('qm-filtered');
			} else {
				$(this).closest('th').removeClass('qm-filtered');
			}

			var matches = tr.filter(':visible');
			var filtered_count = 0;
			var total_count = 0;
			matches.each(function(i){
				var row_time = $(this).attr('data-qm-time');
				if ( row_time ) {
					time += parseFloat( row_time );
				}

				var row_count = $(this).attr('data-qm-count');
				if ( row_count ) {
					filtered_count += parseFloat( row_count );
				} else {
					filtered_count++;
				}
			});
			if ( time ) {
				time = QM_i18n.number_format( time, 4 );
			}

			tr.each(function(i){
				var row_count = $(this).attr('data-qm-count');
				if ( row_count ) {
					total_count += parseFloat( row_count );
				} else {
					total_count++;
				}
			});

			if ( table.find('.qm-filtered').length ) {
				var count = filtered_count + ' / ' + total_count;
			} else {
				var count = filtered_count;
			}

			table.find('.qm-items-number').text(count);
			table.find('.qm-items-time').text(time);

			stripes(table);
		});

		container.find('.qm-filter').each(function () {
			var key = $(this).attr('id');
			var value = sessionStorage.getItem( key );
			if ( value !== null ) {
				// Escape the following chars with a backslash before passing into jQ selectors: [ ] ( ) ' " \
				var val = value.replace(/[[\]()'"\\]/g, "\\$&");
				if ( ! $(this).find('option[value="' + val + '"]').length ) {
					$('<option>').attr('value',value).text(value).appendTo(this);
				}
				$(this).val(value).trigger('change');
			}
		});

		container.find('.qm-filter-trigger').on('click',function(e){
			var filter = $(this).data('qm-filter'),
				value = $(this).data('qm-value'),
				target = $(this).data('qm-target');
			$('#qm-' + target).find('.qm-filter').not('[data-filter="' + filter + '"]').val('').removeClass('qm-highlight').trigger('change');
			$('#qm-' + target).find('[data-filter="' + filter + '"]').val(value).addClass('qm-highlight').trigger('change');
			show_panel( '#qm-' + target );
			$('#qm-' + target).focus();
			e.preventDefault();
		});

		container.find('.qm-toggle').on('click',function(e){
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
					toggle.slideToggle(150,function(){
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
				toggle.slideToggle(150);
			}
			e.preventDefault();
		});

		container.find('.qm-highlighter').on('mouseenter',function(e){

			var subject = $(this).data('qm-highlight');
			var table = $(this).closest('table');

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

		$('.qm').find('tbody a,tbody button').on('focus',function(e){
			$(this).closest('tr').addClass('qm-hovered');
		}).on('blur',function(e){
			$(this).closest('tr').removeClass('qm-hovered');
		});

		container.find('.qm table').on('sorted.qm',function(){
			stripes( $(this) );
		});

		$( document ).ajaxSuccess( function( event, response, options ) {

			var errors = response.getResponseHeader( 'X-QM-php-errors-count' );

			if ( ! errors ) {
				return event;
			}

			errors = parseInt( errors, 10 );

			if ( window.console ) {
				console.group( qm_l10n.ajax_error );
			}

			for ( var key = 1; key <= errors; key++ ) {

				error = JSON.parse( response.getResponseHeader( 'X-QM-php-errors-error-' + key ) );

				if ( window.console ) {
					switch ( error.type ) {
						case 'warning':
							console.error( error );
							break;
						default:
							console.warn( error );
							break;
					}
				}

				if ( $('#qm-php_errors').find('[data-qm-key="' + error.key + '"]').length ) {
					continue;
				}

				if ( $('#wp-admin-bar-query-monitor').length ) {
					if ( ! QueryMonitorData.ajax_errors[error.type] ) {
						$('#wp-admin-bar-query-monitor')
							.addClass('qm-' + error.type)
							.find('a').first().append('<span class="ab-label qm-ajax-' + error.type + '"> &nbsp; Ajax: ' + error.type + '</span>');
					}
				}

				QueryMonitorData.ajax_errors[error.type] = true;

			}

			if ( window.console ) {
				console.groupEnd();
			}

			$( '#qm-ajax-errors' ).show();

			return event;

		} );

		$('.qm-auth').on('click',function(e){
			var state = $('#qm-settings').data('qm-state');
			var action = ( 'off' === state ? 'on' : 'off' );

			$.ajax(qm_l10n.ajaxurl,{
				type : 'POST',
				context : this,
				data : {
					action : 'qm_auth_' + action,
					nonce  : qm_l10n.auth_nonce[action]
				},
				success : function(response){
					$(this).text( $(this).data('qm-text-' + action) );
					$('#qm-settings').attr('data-qm-state',action).data('qm-state',action);
				},
				dataType : 'json',
				xhrFields: {
					withCredentials: true
				}
			});

			e.preventDefault();
		});

		var editorSuccessIndicator = $('#qm-editor-save-status');
		editorSuccessIndicator.hide();

		$('.qm-editor-button').on('click',function(e){
			var state = $('#qm-settings').data('qm-state');
			var editor = $('#qm-editor-select').val();

			$.ajax(qm_l10n.ajaxurl,{
				type : 'POST',
				context : this,
				data : {
					action : 'qm_editor_set',
					nonce  : qm_l10n.auth_nonce['editor-set'],
					editor : editor
				},
				success : function(response){
					if (response.success) {
						editorSuccessIndicator.show();
					}
				},
				dataType : 'json',
				xhrFields: {
					withCredentials: true
				}
			});

			e.preventDefault();
		});

		$('.qm-theme-toggle').on('click',function(e){
			container.attr('data-theme',$(this).val());
			localStorage.setItem('qm-theme',$(this).val());
		});

		$.qm.tableSort({target: $('.qm-sortable')});

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
		if ( ! container.hasClass('qm-peek') ) {
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
				container.addClass('qm-show-right');
			} else if ( p !== 'right' && h !== null ) {
				if ( h < minheight ) {
					h = minheight;
				}
				if ( h > maxheight ) {
					h = maxheight;
				}
				container.height( h );
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

		$('.qm-button-container-close').on('click',function(){
			container.removeClass('qm-show').height('').width('');
			body.css( 'margin-bottom', '' );
			localStorage.removeItem( container_pinned_key );
		});

		$('.qm-button-container-settings,a[href="#qm-settings"]').on('click',function(){
			show_panel( '#qm-settings' );
			$('#qm-settings').focus();
		});

		$('.qm-button-container-position').on('click',function(){
			container.toggleClass('qm-show-right');

			if ( container.hasClass('qm-show-right') ) {
				var w = localStorage.getItem( container_width_key );

				if ( w !== null && w < $(window).width() ) {
					container.width( w );
				}

				body.css( 'margin-bottom', '' );

				localStorage.setItem( container_position_key, 'right' );
			} else {
				body.css( 'margin-bottom', 'calc( ' + body_margin + ' + ' + container.height() + 'px )' );

				localStorage.removeItem( container_position_key );
			}
		});

		var pinned = localStorage.getItem( container_pinned_key );
		if ( pinned && $( pinned ).length ) {
			show_panel( pinned );
		}

		$('.qm-title-heading select').on('change',function(){
			show_panel( $(this).val() );
			$($(this).val()).focus();
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
			// @param	object	columns	NodeList table columns.
			// @param	integer	row_width	defines the number of columns per row.
			var table_to_array = function (columns, row_width) {
				columns = Array.prototype.slice.call(columns, 0);

				var rows = [];
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

					table.find('thead th').removeClass('qm-sorted-asc qm-sorted-desc').removeAttr('aria-sort');

					if ( desc ) {
						$(this).addClass('qm-sorted-desc').attr('aria-sort','descending');
					} else {
						$(this).addClass('qm-sorted-asc').attr('aria-sort','ascending');
					}

					table.find('tbody').each(function () {
						var tbody = $(this);
						var rows = this.rows;
						var columns = this.querySelectorAll('th,td');

						if (this.data_matrix === undefined) {
							this.data_matrix = table_to_array(columns, $(rows[0]).find('th,td').length);
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

					table.trigger('sorted.qm');

					e.preventDefault();
				});
			});
		};
	})(jQuery);

}

window.addEventListener('load', function() {
	var main = document.getElementById( 'query-monitor-main' );
	var ceased = document.getElementById( 'query-monitor-ceased' );
	var broken = document.getElementById( 'qm-broken' );
	var menu_item = document.getElementById( 'wp-admin-bar-query-monitor' );

	if ( ( 'undefined' === typeof jQuery ) || ! window.jQuery ) {
		/* Fallback for running without jQuery (`QM_NO_JQUERY`) or when jQuery is broken */

		if ( main ) {
			main.className += ' qm-broken';
		}

		if ( broken ) {
			console.error( broken.textContent );
		}

		if ( 'undefined' === typeof jQuery ) {
			console.error( 'QM error from JS: undefined jQuery' );
		} else if ( ! window.jQuery ) {
			console.error( 'QM error from JS: no jQuery' );
		}

		if ( menu_item && main ) {
			menu_item.addEventListener( 'click', function() {
				main.className += ' qm-show';
			} );
		}
	}

	if ( ! main ) {
		if ( ceased ) {
			// QM was ceased
			console.info( 'QM: collection and output was ceased' );
		} else {
			// QM's output has disappeared
			console.error( 'QM error from JS: QM output does not exist' );
		}
	}
} );
