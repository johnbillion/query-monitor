/*

© 2013 John Blackbourn

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

		number    = parseFloat( number );
		num_float = number.toFixed( decimals );
		num_int   = Math.floor( number );
		num_str   = num_int.toString();
		fraction  = num_float.substring( num_float.indexOf( '.' ) + 1, num_float.length );

		var o = '';

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

	if ( window.qm ) {

		$('#wp-admin-bar-query-monitor')
			.addClass(qm.top.classname)
			.find('a').eq(0)
			.html(qm.top.title)
		;

		$.each( qm.sub, function( i, el ) {

			new_menu = $('#wp-admin-bar-query-monitor-placeholder')
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

			new_menu.appendTo('#wp-admin-bar-query-monitor ul');

		} );

		$('#wp-admin-bar-query-monitor').find('a').click(function(e){
			$('.qm').show();
			$('#qm').css('cursor','auto').unbind('click');
		});

		$('#qm').click(function(e){
			$('.qm').show();
			$('#qm').css('cursor','auto').unbind('click');
			$('html,body').scrollTop($("#qm").offset().top-$('#wpadminbar').outerHeight());
		});

		$('#wp-admin-bar-query-monitor,#wp-admin-bar-query-monitor-default').show();

	}

	$('#qm').find('select.qm-filter').change(function(e){

		filter = $(this).attr('data-filter');
		table  = $(this).closest('table');
		tr     = table.find('tbody tr[data-qm-' + filter + ']');
		val    = $(this).val().replace(/[[\]()'"]/g, "\\$&");
		total  = tr.removeClass('qm-hide-' + filter).length;

		if ( $(this).val() != '' ) {
			$(this).addClass('qm-filter-show');
			tr.not('[data-qm-' + filter + '="' + val + '"]').addClass('qm-hide-' + filter);
		} else {
			$(this).removeClass('qm-filter-show');
		}

		time = 0;
		matches = tr.filter(':visible');
		matches.each(function(i){
			time += parseFloat( $(this).attr('data-qm-time') );
		});
		time = QM_i18n.number_format( time, 4 );

		results = table.find('.qm-queries-shown').removeClass('qm-hide');
		results.find('.qm-queries-number').text(matches.length);
		results.find('.qm-queries-time').text(time);

		$(this).blur();

	});

	$( document ).ajaxSuccess( function( event, response, options ) {

		var errors, key, error, text;

		if ( errors = response.getResponseHeader( 'X-QM-Errors' ) ) {

			errors = $.parseJSON( errors );

			for ( key in errors ) {
				error = $.parseJSON( response.getResponseHeader( 'X-QM-Error-' + errors[key] ) );
				console.log( '=== PHP Error ===' );
				console.log( options );
				console.log( error );

				$('#wp-admin-bar-query-monitor')
					.addClass('qm-'+error.type)
					.find('a').first().append('<span class="qm-ajax-'+ error.type +'"> / AJAX: '+ error.type +'</span>')
				;
			}

		}

		return event;

	} );

} );
