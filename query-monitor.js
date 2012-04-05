
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

} );
