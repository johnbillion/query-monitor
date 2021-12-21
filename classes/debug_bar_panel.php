<?php
/**
 * Mock 'Debug Bar' panel class.
 *
 * @package query-monitor
 */

abstract class Debug_Bar_Panel {

	/**
	 * @var string
	 */
	public $_title = '';

	/**
	 * @var bool
	 */
	public $_visible = true;

	/**
	 * @param string $title
	 */
	public function __construct( $title = '' ) {
		$this->title( $title );

		if ( $this->init() === false ) {
			$this->set_visible( false );
			return;
		}

		add_filter( 'debug_bar_classes', array( $this, 'debug_bar_classes' ) );
	}

	/**
	 * Initializes the panel.
	 *
	 * @return false|void
	 */
	public function init() {}

	/**
	 * @return void
	 */
	public function prerender() {}

	/**
	 * Renders the panel.
	 *
	 * @return void
	 */
	public function render() {}

	/**
	 * @return bool
	 */
	public function is_visible() {
		return $this->_visible;
	}

	/**
	 * @param bool $visible
	 * @return void
	 */
	public function set_visible( $visible ) {
		$this->_visible = $visible;
	}

	/**
	 * @param string|null $title
	 * @return string|void
	 */
	public function title( $title = null ) {
		if ( ! isset( $title ) ) {
			return $this->_title;
		}
		$this->_title = $title;
	}

	/**
	 * @param array<int, string> $classes
	 * @return array<int, string>
	 */
	public function debug_bar_classes( $classes ) {
		return $classes;
	}

	/**
	 * @param string $title
	 * @return void
	 */
	public function Debug_Bar_Panel( $title = '' ) {
		self::__construct( $title );
	}

}
