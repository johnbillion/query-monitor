<?php declare(strict_types = 1);

namespace QM\Tests;

class DispatcherHtmlTest extends Test {

	/** @var \QM_Dispatcher_Html|null $html */
	protected $html = null;

	public function _before(): void {

		parent::_before();

		$admin = self::factory()->user->create_and_get( array(
			'role' => 'administrator',
		) );

		if ( is_multisite() ) {
			grant_super_admin( $admin->ID );
		}

		wp_set_current_user( $admin->ID );

		/** @var \QM_Dispatcher_Html $html */
		$html = \QM_Dispatchers::get( 'html' );

		$this->html = $html;
		$this->html->init();

	}
}
