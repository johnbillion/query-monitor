<?php declare(strict_types = 1);
/**
 * Class representing a component.
 *
 * @package query-monitor
 */

/**
 * @phpstan-type QM_Component_Array array{
 *   type: string,
 *   name: string,
 *   context: string,
 * }
 * @property-read string $name
 */
class QM_Component implements JsonSerializable {
	public const TYPE_ALTIS_VENDOR = 'altis-vendor';
	public const TYPE_CORE = 'core';
	public const TYPE_DROPIN = 'dropin';
	public const TYPE_MU_PLUGIN = 'mu-plugin';
	public const TYPE_MU_VENDOR = 'mu-vendor';
	public const TYPE_OTHER = 'other';
	public const TYPE_PHP = 'php';
	public const TYPE_PLUGIN = 'plugin';
	public const TYPE_STYLESHEET = 'stylesheet';
	public const TYPE_TEMPLATE = 'template';
	public const TYPE_THEME = 'theme';
	public const TYPE_UNKNOWN = 'unknown';
	public const TYPE_VIP_CLIENT_MU_PLUGIN = 'vip-client-mu-plugin';
	public const TYPE_VIP_PLUGIN = 'vip-plugin';
	public const TYPE_VIP_SHARED_PLUGIN = 'vip-shared-plugin';

	/**
	 * @var string
	 */
	public $type;

	/**
	 * @var string
	 */
	public $context;

	/**
	 * @var string
	 */
	public $file;

	public function __construct( string $context, string $file = '', string $type = '' ) {
		$this->context = $context;
		$this->file = $file;
		$this->type = $type;
	}

	public function get_name(): string {
		if ( isset( $this->name ) ) {
			return $this->name;
		}

		return sprintf(
			$this->type,
			$this->context
		);
	}

	final public function get_id(): string {
		return "{$this->type}-{$this->context}";
	}

	final public function is_plugin(): bool {
		return ( $this->type === self::TYPE_PLUGIN );
	}

	final public function is_core(): bool {
		return ( $this->type === self::TYPE_CORE );
	}

	/**
	 * @param QM_Component[] $components
	 */
	final public static function has_non_core( array $components ): bool {
		foreach ( $components as $component ) {
			if ( ! $component->is_core() ) {
				return true;
			}
		}

		return false;
	}

	final public static function from( string $type, string $context = '', string $file = '' ): QM_Component {
		switch ( $type ) {
			case self::TYPE_ALTIS_VENDOR:
				return new QM_Component_Altis_Vendor( $context, $file, $type );
			case self::TYPE_PLUGIN:
				return new QM_Component_Plugin( $context, $file, $type );
			case self::TYPE_MU_PLUGIN:
				return new QM_Component_MU_Plugin( $context, $file, $type );
			case self::TYPE_MU_VENDOR:
				return new QM_Component_MU_Vendor( $context, $file, $type );
			case self::TYPE_VIP_SHARED_PLUGIN:
			case self::TYPE_VIP_PLUGIN:
			case self::TYPE_VIP_CLIENT_MU_PLUGIN:
				return new QM_Component_VIP_Plugin( $context, $file, $type );
			case self::TYPE_STYLESHEET:
				return new QM_Component_Stylesheet( $context, $file, $type );
			case self::TYPE_TEMPLATE:
				return new QM_Component_Template( $context, $file, $type );
			case self::TYPE_OTHER:
				return new QM_Component_Other( $context, $file, $type );
			case self::TYPE_CORE:
				return new QM_Component_Core( $context, $file, $type );
			case self::TYPE_DROPIN:
				return new QM_Component_Dropin( $context, $file, $type );
			case self::TYPE_PHP:
				return new QM_Component_PHP( $context, $file, $type );
		}

		return new QM_Component_Unknown( $context, $file, $type );
	}

	/**
	 * @return mixed
	 */
	public function __get( string $key ) {
		if ( 'name' === $key ) {
			return $this->get_name();
		}
	}

	/**
	 * @phpstan-return QM_Component_Array
	 * @return array<string, string>
	 */
	public function toArray(): array {
		return array(
			'type' => $this->type,
			'name' => $this->name,
			'context' => $this->context,
		);
	}
	/**
	 * @phpstan-return QM_Component_Array
	 * @return array<string, string>
	 */
	public function jsonSerialize(): array {
		return $this->toArray();
	}

	public static function sort( QM_Component $a, QM_Component $b ): int {
		return strcasecmp( $a->get_name(), $b->get_name() );
	}
}
