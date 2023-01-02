<?php declare(strict_types = 1);
/**
 * Timer that collects timing and memory usage.
 *
 * @package query-monitor
 */

class QM_Timer {

	/**
	 * @var array<string, mixed>|null
	 * @phpstan-var array{
	 *   time: float,
	 *   memory: int,
	 *   data: mixed[]|null,
	 * }|null
	 */
	protected $start = null;

	/**
	 * @var array<string, mixed>|null
	 * @phpstan-var array{
	 *   time: float,
	 *   memory: int,
	 *   data: mixed[]|null,
	 * }|null
	 */
	protected $end = null;

	/**
	 * @var QM_Backtrace|null
	 */
	protected $trace = null;

	/**
	 * @var array<string, array<string, mixed>>
	 * @phpstan-var array<string, array{
	 *   time: float,
	 *   memory: int,
	 *   data: mixed[]|null,
	 * }>
	 */
	protected $laps = array();

	/**
	 * @param mixed[] $data
	 * @return self
	 */
	public function start( array $data = null ) {
		$this->trace = new QM_Backtrace();
		$this->start = array(
			'time' => microtime( true ),
			'memory' => memory_get_usage(),
			'data' => $data,
		);
		return $this;
	}

	/**
	 * @param mixed[] $data
	 * @return self
	 */
	public function stop( array $data = null ) {

		$this->end = array(
			'time' => microtime( true ),
			'memory' => memory_get_usage(),
			'data' => $data,
		);

		return $this;

	}

	/**
	 * @param mixed[] $data
	 * @param string $name
	 * @return self
	 */
	public function lap( array $data = null, $name = null ) {

		$lap = array(
			'time' => microtime( true ),
			'memory' => memory_get_usage(),
			'data' => $data,
		);

		if ( ! isset( $name ) ) {
			$i = sprintf(
				/* translators: %s: Timing lap number */
				__( 'Lap %s', 'query-monitor' ),
				number_format_i18n( count( $this->laps ) + 1 )
			);
		} else {
			$i = $name;
		}

		$this->laps[ $i ] = $lap;

		return $this;

	}

	/**
	 * @return mixed[]
	 */
	public function get_laps() {

		$laps = array();
		$prev = $this->start;

		foreach ( $this->laps as $lap_id => $lap ) {

			$lap['time_used'] = $lap['time'] - $prev['time'];
			$lap['memory_used'] = $lap['memory'] - $prev['memory'];

			$laps[ $lap_id ] = $lap;
			$prev = $lap;

		}

		return $laps;

	}

	/**
	 * @return float
	 */
	public function get_time() {
		return $this->end['time'] - $this->start['time'];
	}

	/**
	 * @return int
	 */
	public function get_memory() {
		return $this->end['memory'] - $this->start['memory'];
	}

	/**
	 * @return float
	 */
	public function get_start_time() {
		return $this->start['time'];
	}

	/**
	 * @return int
	 */
	public function get_start_memory() {
		return $this->start['memory'];
	}

	/**
	 * @return float
	 */
	public function get_end_time() {
		return $this->end['time'];
	}

	/**
	 * @return int
	 */
	public function get_end_memory() {
		return $this->end['memory'];
	}

	/**
	 * @return QM_Backtrace
	 */
	public function get_trace() {
		return $this->trace;
	}

	/**
	 * @param mixed[] $data
	 * @return self
	 */
	public function end( array $data = null ) {
		return $this->stop( $data );
	}

}
