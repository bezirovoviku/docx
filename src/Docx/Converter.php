<?php
namespace Docx;

interface Converter
{	
	/**
	 * Converts document to different format.
	 *
	 * @param \Docx\Document $document document to be converted
	 * @param string         $filename target filename, if not set, contents of converted file will be returned
	 * @return bool|string either success of writing file or file contents
	 */
	public function save(\Docx\Document $document, $filename = null);
	
	/**
	 * Returns expected result extension
	 *
	 * @return string extension
	 */
	public function getExtension();
}