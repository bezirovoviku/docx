<?php
namespace Docx\Generator\Filters;

abstract class FormatFilter implements \Docx\Generator\Filter
{
	protected function format($value) {
		return null;
	}
	
	public function filter($generator, $context, $arguments, $input) {
		if (count($arguments))
			return strtolower($arguments[0]);
		return strtolower($input);
	}
}