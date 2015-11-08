<?php
namespace Docx\Generator;

interface Filter
{
	/**
	 * Returns tag used to identify filter
	 *
	 * @return string tag identifing filter
	 */
	public function getTag();
	
	/**
	 * Filter given arguments and return result
	 *
	 * @param \Docx\Generator $generator generator calling this filter
	 * @param array           $context   replacing context
	 * @param array           $arguments arguments passed to filter
	 * @param string|null     $input     pipe input (if present)
	 * @return string result
	 */
	public function filter($generator, $context, $arguments, $input);
}