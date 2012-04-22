
var QM_i18n = {

	// http://core.trac.wordpress.org/ticket/20491

	number_format : function( number, decimals ) {

		if ( !decimals )
			decimals = 0;

		number    = ( number < 1 ) ? 0 : parseFloat( number );
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


		var n1 = '';
		if ( isNaN(n) )
			return;
		n = n < 1 ? '0' : n.toString();
		if ( n.length > 3 ) {
			while ( n.length > 3 ) {
				n1 = thousandsSeparator + n.substr(n.length - 3) + n1;
				n = n.substr(0, n.length - 3);
			}
			n = n + n1;
		}


	}

};

jQuery( function($) {

	if ( window.qm ) {

		$('#wp-admin-bar-query-monitor')
			.addClass(qm.top.class)
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

			if ( ( typeof el.meta != 'undefined' ) && ( typeof el.meta.class != 'undefined' ) )
				new_menu.addClass(el.meta.class);

			new_menu.appendTo('#wp-admin-bar-query-monitor ul');

		} );

		$('#wp-admin-bar-query-monitor,#wp-admin-bar-query-monitor-default').show();

	}

	$('#qm-authentication').show();

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

} );
