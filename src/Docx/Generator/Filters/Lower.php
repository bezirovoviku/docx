<?php
namespace Docx\Generator\Filters;

/**
 * Converts text to lower case
 *
 * Examples: {$value|lower} or {lower $value}
 */
class Lower extends FormatFilter
{
	public function getTag() {
		return 'lower';
	}
	
	public function format($value) {
		return strtolower($value);
	}
}