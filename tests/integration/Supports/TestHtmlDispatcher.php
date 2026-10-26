<?php declare(strict_types = 1);

namespace QM\Tests\Supports;

class TestHtmlDispatcher extends \QM_Dispatcher_Html {

	/**
	 * @param array<string, mixed[]> $panel_menu
	 * @return void
	 */
	public static function run_back_compat( array &$panel_menu ): void {
		parent::apply_panel_menu_back_compat( $panel_menu );
	}

}
