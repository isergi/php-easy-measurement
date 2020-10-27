<?php

/**
 * Keeps different measurements by keys.
 * @package EasyMeasure
 * @author Serg Pavlov isergi <mail@isergi.ru>
 */
class EasyMeasure 
{

	// Measurement Types
	const MEASURE_MEMORY	 = 'memory',
		  MEASURE_TIME		 = 'time';

	// Measurement Completed or not
	const MEASURE_STOP_YN_Y	 = 'Y',
		  MEASURE_STOP_YN_N	 = 'N';

	// Private list of measurements
	private static $_measure_list = [];
	
	// Log file for the output
	private static $_log_file	  = null;

	/**
	 * Setups log file for the output debug
	 *
	 * @param string $file_path path to the file for the output
	 *
	 * @return void
	 */
	public static function attachLogFile(string $file_path) : void
	{
		self::$_log_file = $file_path;
	}

	/**
	 * Starts new measurement with a specific key
	 *
	 * @param string $key new measurement key
	 *
	 * @return void
	 */
	public static function measureStart(string $key) : void
	{
		self::$_measure_list[ $key ] = [
			self::MEASURE_TIME	 => microtime(true),
			self::MEASURE_MEMORY => memory_get_usage(true),
			'is_measured'		 => self::MEASURE_STOP_YN_N
		];
	}

	/**
	 * Stops an existing measurement with a specific key
	 *
	 * @param string $key measurement key
	 * @param bool $print_r if the function needs to print a stopped measurement
	 *
	 * @return void
	 */
	public static function measureStop(string $key, $print_r = false) : void
	{
		self::$_measure_list[ $key ] = [
			self::MEASURE_TIME	 => self::_calculate_time_by_key($key),
			self::MEASURE_MEMORY => self::_calculate_memory_by_key($key),
			'is_measured'		 => self::MEASURE_STOP_YN_Y
		];

		if ($print_r) {
			self::printOne($key);
		}
	}

	/**
	 * Prints all collected list of measurements
	 *
	 * @return void Output prints all measurement list
	 */
	public static function printAll() : void
	{
		$collected_list = [];
		foreach (self::$_measure_list as $measurement_key => $measurement_value) {
			$collected_list[ $measurement_key ] = self::_calc_measurement_value($measurement_key);
		}
		self::_print_r($collected_list);
	}

	/**
	 * Prints one specific measurement
	 * 
	 * @param string $key new measurement key
	 * @param bool $only_calculate if the function needs only return calculated measurement
	 *
	 * @return void Output prints key of measurement list
	 */
	public static function printOne(string $key) : void
	{
		$measurement_item = self::_calc_measurement_value($key);
		self::_print_r($measurement_item);
	}

	/**
	 * Returns calculated measurement by selected key
	 * 
	 * @param string $key new measurement key
	 *
	 * @return ?array
	 */
	private static function _calc_measurement_value(string $key): ?array
	{
		$measurement_item = null;
		if (!empty(self::$_measure_list[ $key ])) {
			if (self::$_measure_list[ $key ]['is_measured'] == self::MEASURE_STOP_YN_Y) {
				$measurement_item = [
					self::MEASURE_TIME	 => self::$_measure_list[ $key ][ self::MEASURE_TIME ],
					self::MEASURE_MEMORY => self::$_measure_list[ $key ][ self::MEASURE_MEMORY ],
				];
			} else {
				$measurement_item = [
					self::MEASURE_TIME	 => self::_calculate_time_by_key($key),
					self::MEASURE_MEMORY => self::_calculate_memory_by_key($key)
				];
			}
		}

		return $measurement_item;
	}

	/**
	 * Calculates measurement time by selected key
	 * 
	 * @param string $key new measurement key
	 *
	 * @return float
	 */
	private static function _calculate_time_by_key(string $key) : string
	{
		$time = 0;

		if (isset(self::$_measure_list[ $key ])) {
			$time = 
					(self::$_measure_list[ $key ]['is_measured'] == self::MEASURE_STOP_YN_Y) 
				?
					self::$_measure_list[ $key ][ self::MEASURE_TIME ]
				:
					microtime(true) - self::$_measure_list[ $key ][ self::MEASURE_TIME ];
		}
		
		return round($time, 2) . ' Sec';
	}

	/**
	 * Calculates measurement memory by selected key
	 * 
	 * @param string $key new measurement key
	 *
	 * @return float
	 */
	private static function _calculate_memory_by_key(string $key) : string
	{
		$unit = ['B','Kb','Mb','Gb','Tb','PB!!!'];
		
		$memory = 0;

		if (isset(self::$_measure_list[ $key ])) {
			$memory = 
					(self::$_measure_list[ $key ]['is_measured'] == self::MEASURE_STOP_YN_Y) 
				?
					self::$_measure_list[ $key ][ self::MEASURE_MEMORY ]
				:
					memory_get_usage(true) - self::$_measure_list[ $key ][ self::MEASURE_MEMORY ];
		}

		$postfix = 1;
		 if ($memory < 0) {
			$memory *=-1;
			$postfix = -1;
		 } elseif ($memory == 0) {
			$postfix = 0;
			$memory	 = 1;
		 }

		return @round($memory/pow(1024,($i=floor(log($memory,1024)))), 2) * $postfix.' '.$unit[$i];
	}

	/**
	 * Prints any data like a print_r function
	 * @param mixed ... Any data to be printed
	 */
	private static function _print_r() : void
	{
		$args = func_get_args();
		echo '<ol style="font-family: Courier; font-size: 12px; border: 1px solid #dedede; background-color: #efefef; float: left; padding-right: 20px;">';
		foreach ($args as $v) {
			echo '<li><pre>' . htmlspecialchars(print_r($v, true)) . "\n" . '</pre></li>';
		}
		echo '</ol><div style="clear:left;"></div>';

		if (!is_null(self::$_log_file)) {
			$log_time = str_repeat('-', 10) . ' [' . date('Y-m-d H:i:s') . '] ' . str_repeat('-', 10) . "\r\n" . PHP_EOL;
			file_put_contents(self::$_log_file, $log_time . print_r($v, true), FILE_APPEND | LOCK_EX);
		}
	}
}