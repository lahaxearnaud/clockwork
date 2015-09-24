<?php namespace Clockwork\DataSource;

/**
 * Data source interface, all data sources must implement this interface
 */
interface ExtraDataSourceInterface extends DataSourceInterface
{

	/**
	 * @return string
	 */
	public function getKey();
}
