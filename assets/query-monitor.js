/*

Copyright 2013 John Blackbourn

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
			$('#qm').show();
		});

		$('#wp-admin-bar-query-monitor,#wp-admin-bar-query-monitor-default').show();

	}

	$('#qm').find('select.qm-filter').on('change',function(e){

		var filter = $(this).attr('data-filter'),
			table  = $(this).closest('table'),
			tr     = table.find('tbody tr[data-qm-' + filter + ']'),
			val    = $(this).val().replace(/[[\]()'"]/g, "\\$&"),
			total  = tr.removeClass('qm-hide-' + filter).length,
			time   = 0;

		if ( $(this).val() !== '' )
			tr.not('[data-qm-' + filter + '*="' + val + '"]').addClass('qm-hide-' + filter);

		var matches = tr.filter(':visible');
		matches.each(function(i){
			var row_time = $(this).attr('data-qm-time');
			if ( row_time )
				time += parseFloat( row_time );
		});
		if ( time )
			time = QM_i18n.number_format( time, 4 );

		var results = table.find('.qm-items-shown').removeClass('qm-hide');
		results.find('.qm-items-number').text(matches.length);
		results.find('.qm-items-time').text(time);

		$(this).blur();

	});

	$('#qm').find('.qm-toggle').on('click',function(e){
		var el = $(this);
		$(this).parent().find('.qm-toggled').toggle(0,function(){
			if ( '+' == el.text() )
				el.text('-');
			else
				el.text('+');
		});
		e.preventDefault();
	});

	$( document ).ajaxSuccess( function( event, response, options ) {

		var errors = response.getResponseHeader( 'X-QM-Errors' );

		if ( !errors )
			return event;

		errors = parseInt( errors, 10 );

		for ( var key = 1; key <= errors; key++ ) {

			error = $.parseJSON( response.getResponseHeader( 'X-QM-Error-' + key ) );

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

} );
