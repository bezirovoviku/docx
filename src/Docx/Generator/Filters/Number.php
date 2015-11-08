<?php
namespace Docx\Generator\Filters;

/**
 * Formats input number
 *
 * Examples: {$number|format} or {$number|format eu} or {$number|format eu 5}
 */
class Number implements \Docx\Generator\Filter
{
	///@var array $formats avaible number formats
	protected $formats = [
		'eu' => [',', ' '],
		'eu2' => [',', '.'],
		'en' => ['.', ',']
	];
	
	public function getTag() {
		return 'number';
	}
	
	public function filter($generator, $context, $arguments, $input) {
		//Validate arguments
		if (count($arguments) > 2)
			throw new ParseException("Date filter expects maximum of 2 arguments");
		
		//Default values
		$value = $input;
		$format = 'en';
		$decimals = 0;
		
		//Load arguments
		if (count($arguments) > 0)
			$format = $arguments[0];
		if (count($arguments) > 1)
			$decimals = (int)$arguments[1];
		
		//Load format data
		$format = $this->formats[$format];
		
		//Format number
		return number_format($value, $decimals, $format[0], $format[1]);
	}
}