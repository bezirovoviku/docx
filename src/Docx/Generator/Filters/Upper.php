<?php
namespace Docx\Generator\Filters;

/**
 * Converts text to upper case
 *
 * Examples: {$value|upper} or {upper $value}
 */
class Upper extends FormatFilter
{
	public function getTag() {
		return 'upper';
	}
	
	public function format($value) {
		return strtoupper($value);
	}
}