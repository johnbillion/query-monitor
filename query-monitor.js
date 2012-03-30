
jQuery( function($) {

	if ( window.qm ) {

		$('#wp-admin-bar-query_monitor')
			.addClass(qm.top.class)
			.find('a').eq(0)
			.html(qm.top.title)
		;

		$.each( qm.sub, function( i, el ) {

			new_menu = $('#wp-admin-bar-query_monitor_placeholder')
				.clone()
				.attr('id','wp-admin-'+el.id)
			;
			new_menu
				.find('a').eq(0)
				.html(el.title)
				.attr('href',el.href)
			;

			if ( ( typeof el.meta != 'undefined' ) && ( typeof el.meta.class != 'undefined' ) )
				new_menu.addClass(el.meta.class);

			new_menu.appendTo('#wp-admin-bar-query_monitor-default');

		} );

		$('#wp-admin-bar-query_monitor,#wp-admin-bar-query_monitor-default').show();

	}

} );
