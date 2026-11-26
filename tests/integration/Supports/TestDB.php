<?php declare(strict_types = 1);

namespace QM\Tests\Supports;

/**
 * A wpdb drop-in that mimics HyperDB and LudicrousDB, which have a `save_queries` property.
 */
class TestDB extends \wpdb {

	/**
	 * @var bool
	 */
	public $save_queries = false;

	public function __construct() {}

}
