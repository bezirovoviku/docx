<?php
namespace Docx\Generator\Filters;

/**
 * Prints input date with input format
 *
 * Examples: {$date|date *d-m-Y H:i:s*} or {date *d-m-Y H:i:s* $date}
 */
class Date implements \Docx\Generator\Filter
{
	public function getTag() {
		return 'date';
	}
	
	public function filter($generator, $context, $arguments, $input) {
		//Validate arguments
		if (count($arguments) != 2 &&
			count($arguments) != 1)
			throw new ParseException("Date filter expects two arguments or one argument with input");
		
		$value = $input;
		$format = $arguments[0];
		
		if (count($arguments) == 2) {
			$value = $arguments[1];
		}
		
		//Parse date if needed
		if (!is_numeric($value))
			$value = strtotime($value);
		
		return date($format, $value);
	}
}