<?php namespace Clockwork\DataSource;

/**
 * Data source interface, all data sources must implement this interface
 */
interface ExtraDataSourceInterface
{
	/**
	 * @return array
	 */
	public function getData();

	/**
	 * @return string
	 */
	public function getKey();
}
