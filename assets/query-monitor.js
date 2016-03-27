/*
Copyright 2009-2016 John Blackbourn

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

		if ( isNaN( number ) )
			return;

		if ( !decimals )
			decimals = 0;

		number = parseFloat( number );

		var num_float = number.toFixed( decimals ),
			num_int   = Math.floor( number ),
			num_str   = num_int.toString(),
			fraction  = num_float.substring( num_float.indexOf( '.' ) + 1, num_float.length ),
			o = '';

		if ( num_str.length > 3 ) {
			for ( i = num_str.length; i > 3; i -= 3 )
				o = qm_locale.number_format.thousands_sep + num_str.slice( i - 3, i ) + o;
			o = num_str.slice( 0, i ) + o;
		} else {
			o = num_str;
		}

		if ( decimals )
			o = o + qm_locale.number_format.decimal_point + fraction;

		return o;

	}

};

jQuery( function($) {

	if ( !window.qm )
		return;

	var is_admin = $('body').hasClass('wp-admin');

	$('#qm').removeClass('qm-no-js').addClass('qm-js');

	if ( $('#wp-admin-bar-query-monitor').length ) {

		var container = document.createDocumentFragment();

		$('#wp-admin-bar-query-monitor')
			.addClass(qm.menu.top.classname)
			.find('a').eq(0)
			.html(qm.menu.top.title)
		;

		$.each( qm.menu.sub, function( i, el ) {

			var new_menu = $('#wp-admin-bar-query-monitor-placeholder')
				.clone()
				.attr('id','wp-admin-bar-'+el.id)
			;
			new_menu
				.find('a').eq(0)
				.html(el.title)
				.attr('href',el.href)
			;

			if ( ( typeof el.meta != 'undefined' ) && ( typeof el.meta.classname != 'undefined' ) )
				new_menu.addClass(el.meta.classname);

			container.appendChild( new_menu.get(0) );

		} );

		$('#wp-admin-bar-query-monitor ul').append(container);

		$('#wp-admin-bar-query-monitor').find('a').on('click',function(e){
			var paused = true;

			if ( is_admin ) {
				$('#wpfooter').css('position','relative');
			}
			if ( window.infinite_scroll && infinite_scroll.contentSelector ) {
				// Infinite Scroll plugin

				$( infinite_scroll.contentSelector ).infinitescroll('pause');

			} else if ( window.infiniteScroll && infiniteScroll.scroller ) {
				// Jetpack Infinite Scroll module

				infiniteScroll.scroller.check = function(){
					return false;
				};

			} else if ( window.wp && wp.themes && wp.themes.RunInstaller ) {
				// Infinite scrolling on Appearance -> Add New screens

				var view = wp.themes.RunInstaller.view.view;
				view.stopListening( view.parent, 'theme:scroll' );

			} else {
				paused = false;
			}

			if ( paused && window.console ) {
				console.debug( qm_l10n.infinitescroll_paused );
			}

			$('#qm').show();
		});

		$('#wp-admin-bar-query-monitor,#wp-admin-bar-query-monitor-default').show();

	}

	$('#qm').find('.qm-filter').on('change',function(e){

		var filter = $(this).attr('data-filter'),
			table  = $(this).closest('table'),
			tr     = table.find('tbody tr[data-qm-' + filter + ']'),
			val    = $(this).val().replace(/[[\]()'"\\]/g, "\\$&"),
			total  = tr.removeClass('qm-hide-' + filter).length,
			hilite = $(this).attr('data-highlight'),
			time   = 0;

		if ( hilite ) {
			table.find('tr').removeClass('qm-highlight');
		}

		if ( $(this).val() !== '' ) {
			if ( hilite ) {
				tr.filter('[data-qm-'+hilite+'*="' + val + '"]').addClass('qm-highlight');
			}
			tr.not('[data-qm-' + filter + '*="' + val + '"]').addClass('qm-hide-' + filter);
		}

		var matches = tr.filter(':visible');
		matches.each(function(i){
			var row_time = $(this).attr('data-qm-time');
			if ( row_time )
				time += parseFloat( row_time );
		});
		if ( time )
			time = QM_i18n.number_format( time, 4 );

		var results = table.find('.qm-items-shown').removeClass('qm-hide');
		results.find('.qm-items-number').text( QM_i18n.number_format( matches.length, 0 ) );
		results.find('.qm-items-time').text(time);

		$(this).blur();

	});

	$('#qm').find('.qm-filter-trigger').on('click',function(e){
		var filter = $(this).data('qm-filter'),
		    value  = $(this).data('qm-value'),
		    target = $(this).data('qm-target');
		$('#qm-' + target).find('.qm-filter').not('[data-filter="' + filter + '"]').val('').change();
		$('#qm-' + target).find('[data-filter="' + filter + '"]').val(value).change();
		$('html, body').scrollTop( $(this).closest('.qm').offset().top );
		$('html, body').animate({
			scrollTop: $('#qm-' + target).offset().top
		}, 500);
		e.preventDefault();
	});

	$('#qm').find('.qm-toggle').on('click',function(e){
		var el = $(this);
		$(this).closest('td').find('.qm-toggled').slideToggle(100,function(){
			if ( el.attr('data-off') == el.text() )
				el.text(el.attr('data-on'));
			else
				el.text(el.attr('data-off'));
		});
		e.preventDefault();
	});

	$('#qm').find('.qm-highlighter').on('mouseenter',function(e){

		var subject = $(this).data('qm-highlight');
		var table   = $(this).closest('table');

		if ( !subject ) {
			return;
		}

		$(this).addClass('qm-highlight');

		$.each( subject.split(' '), function( i, el ){
			table.find('tr[data-qm-subject="'+el+'"]').addClass('qm-highlight');
		});

	}).on('mouseleave',function(e){

		$(this).removeClass('qm-highlight');
		$(this).closest('table').find('tr').removeClass('qm-highlight');

	});

	$( document ).ajaxSuccess( function( event, response, options ) {

		var errors = response.getResponseHeader( 'X-QM-error-count' );

		if ( !errors )
			return event;

		errors = parseInt( errors, 10 );

		for ( var key = 1; key <= errors; key++ ) {

			error = $.parseJSON( response.getResponseHeader( 'X-QM-error-' + key ) );

			if ( window.console ) {
				console.debug( '=== ' + qm_l10n.ajax_error + ' ===' );
				console.debug( error );
			}

			if ( $('#wp-admin-bar-query-monitor').length ) {
				if ( ! qm.ajax_errors[error.type] ) {
					$('#wp-admin-bar-query-monitor')
						.addClass('qm-'+error.type)
						.find('a').first().append('<span class="ab-label qm-ajax-'+ error.type +'"> &nbsp; AJAX: '+ error.type +'</span>')
					;
				}
			}

			qm.ajax_errors[error.type] = true;

		}

		return event;

	} );

	if ( is_admin ) {
		$('#qm').detach().appendTo('#wpwrap');
	}

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

	$.qm.tableSort({target: $('.qm-sortable'), debug: false});

} );

/**
 * This is a modified version of:
 * 
 * jQuery table-sort v0.1.1
 * https://github.com/gajus/table-sort
 *
 * Licensed under the BSD.
 * https://github.com/gajus/table-sort/blob/master/LICENSE
 *
 * Author: Gajus Kuizinas <g.kuizinas@anuary.com>
 */
(function ($) {
	$.qm = $.qm || {};
	$.qm.tableSort = function (options) {
		var settings = $.extend({
			'debug': false
		}, options);
		
		// @param	object	columns	NodeList table colums.
		// @param	integer	row_width	defines the number of columns per row.
		var table_to_array	= function (columns, row_width) {
			if (settings.debug) {
				console.time('table to array');
			}
		
			columns = Array.prototype.slice.call(columns, 0);
			
			var rows      = [];
			var row_index = 0;
			
			for (var i = 0, j = columns.length; i < j; i += row_width) {
				var row	= [];
				
				for (var k = 0, l = row_width; k < l; k++) {
					var e = columns[i+k];
					
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
			
			if (settings.debug) {
				console.timeEnd('table to array');
			}
			
			return rows;
		};
		
		if (!settings.target || !settings.target instanceof $) {
			throw 'Target is not defined or it is not instance of jQuery.';
		}
		
		settings.target.each(function () {
			var table = $(this);
			
			table.find('.qm-sort').on('click', function (e) {
				var desc = $(this).hasClass('qm-sort-desc');
				
				var index = $(this).closest('th').index();
 
				table.find('th').removeClass('qm-sorted-asc qm-sorted-desc');

				if ( desc )
					$(this).closest('th').addClass('qm-sorted-desc');
				else
					$(this).closest('th').addClass('qm-sorted-asc');

				table.find('tbody:not(.qm-sort-no)').each(function () {
					var tbody = $(this);
					
					var rows = this.rows;
					
					var anomalies = $(rows).has('[colspan]').detach();
					
					var columns = this.getElementsByTagName('td');
					
					if (this.data_matrix === undefined) {
						this.data_matrix = table_to_array(columns, $(rows[0]).find('td').length);
					}
					
					var data = this.data_matrix;
					
					if (settings.debug) {
						console.time('sort data');
					}
					
					data.sort(function (a, b) {
						if (a.data[index] == b.data[index]) {
							return 0;
						}
						
						return (desc ? a.data[index] > b.data[index] : a.data[index] < b.data[index]) ? -1 : 1;
					});
										
					if (settings.debug) {
						console.timeEnd('sort data');
						console.time('build table');
					}
					
					// Will use this to re-attach the tbody object.
					var table = tbody.parent();
					
					// Detach the tbody to prevent unnecassy overhead related
					// to the browser environment.
					tbody = tbody.detach();
					
					// Convert NodeList into an array.
					rows = Array.prototype.slice.call(rows, 0);
					
					var last_row = rows[data[data.length-1].index];
					
					for (var i = 0, j = data.length-1; i < j; i++) {
						tbody[0].insertBefore(rows[data[i].index], last_row);
						
						// Restore the index.
						data[i].index = i;
					}
					
					// // Restore the index.
					data[data.length-1].index = data.length-1;
					
					tbody.prepend(anomalies);
					
					table.append(tbody);
					
					
					if (settings.debug) {
						console.timeEnd('build table');
					}
				});
				e.preventDefault();
			});
		});
	};
})(jQuery);
