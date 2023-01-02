<?php declare(strict_types = 1);
/**
 * Fallback data transfer object for third-pary collectors that don't extend
 * the new QM_DataCollector class.
 *
 * @package query-monitor
 */

#[AllowDynamicProperties]
class QM_Data_Fallback extends QM_Data {}
