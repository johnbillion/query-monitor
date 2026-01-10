<?php declare(strict_types = 1);
/**
 * Abstract output class for HTML pages.
 *
 * @package query-monitor
 */

abstract class QM_Output_Html extends QM_Output {

	protected static $file_link_format = null;

	protected $current_id = null;
	protected $current_name = null;

	public function name() {
		return $this->collector->id;
	}

	public function admin_menu( array $menu ) {
		$menu[ $this->collector->id() ] = $this->menu( array(
			'title' => esc_html( $this->name() ),
		) );
		return $menu;
	}

	public function get_output() {
		ob_start();
		$this->output();
		return (string) ob_get_clean();
	}

	protected function before_tabular_output( $id = null, $name = null ) {
		$id   = $id ?? $this->collector->id();
		$name = $name ?? $this->name();

		$this->current_id   = $id;
		$this->current_name = $name;

		printf(
			'<div class="qm" id="%1$s" role="tabpanel" aria-labelledby="%1$s-caption" tabindex="-1">' . "\n",
			esc_attr( $id )
		);

		echo '<table class="qm-sortable">';
		printf(
			'<caption class="qm-screen-reader-text"><h2 id="%1$s-caption">%2$s</h2></caption>' . "\n",
			esc_attr( $id ),
			esc_html( $name )
		);
	}

	protected function after_tabular_output() {
		echo '</table></div>';
		$this->output_concerns();
	}

	protected function before_non_tabular_output( $id = null, $name = null ) {
		$id   = $id ?? $this->collector->id();
		$name = $name ?? $this->name();

		$this->current_id   = $id;
		$this->current_name = $name;

		printf(
			'<div class="qm qm-non-tabular" id="%1$s" role="tabpanel" aria-labelledby="%1$s-caption" tabindex="-1">' . "\n",
			esc_attr( $id )
		);

		echo '<div class="qm-boxed">';
		printf(
			'<h2 class="qm-screen-reader-text" id="%1$s-caption">%2$s</h2>' . "\n",
			esc_attr( $id ),
			esc_html( $name )
		);
	}

	protected function after_non_tabular_output() {
		echo '</div></div>';
		$this->output_concerns();
	}

	/**
	 * ✅ FINAL CORRECTED FUNCTION
	 */
	protected static function build_toggler( $context = '' ) {

		$label = __( 'Toggle more information', 'query-monitor' );

		if ( $context ) {
			$label = sprintf(
				__( 'Toggle more information about %s', 'query-monitor' ),
				$context
			);
		}

		return '<button class="qm-toggle" data-on="+" data-off="-" aria-expanded="false" aria-label="' .
			esc_attr( $label ) .
			'"><span aria-hidden="true">+</span></button>';
	}

	protected static function build_filter_trigger( $target, $filter, $value, $label ) {
		return sprintf(
			'<button class="qm-filter-trigger" data-qm-target="%1$s" data-qm-filter="%2$s" data-qm-value="%3$s">%4$s%5$s</button>',
			esc_attr( $target ),
			esc_attr( $filter ),
			esc_attr( $value ),
			$label,
			QueryMonitor::icon( 'filter' )
		);
	}

	protected static function build_link( $href, $label ) {
		return sprintf(
			'<a href="%1$s" class="qm-link">%2$s%3$s</a>',
			esc_attr( $href ),
			$label,
			QueryMonitor::icon( 'external' )
		);
	}

	protected function menu( array $args ) {
		return array_merge( array(
			'id'   => esc_attr( "query-monitor-{$this->collector->id}" ),
			'href' => esc_attr( '#' . $this->collector->id() ),
		), $args );
	}
}
