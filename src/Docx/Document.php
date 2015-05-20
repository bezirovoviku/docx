<?php
namespace Docx;

/**
 * This class manages operations with docx documents
 */
class Document
{
	///@var string $path path to this document
	protected $path;
	///@var string $body contents of document.xml
	protected $body;
	protected $document;

	/**
	 * Creates new docx document
	 *
	 * @param string $filename OPTIONAL docx file path
	 */
	public function __construct($path = null) {
		if ($path) {
			$this->path = $path;
			$this->load();
		}
	}
	
	/**
	 * Creates copy of this document but with same path. Use with caution.
	 *
	 * @return /Docx/Document copy of document
	 */
	public function copy() {
		return new Document($this->path);
	}
	
	/**
	 * Loads base document xml, which is used for storing body
	 */
	protected function load() {
		if (!file_exists($this->path)) {
			throw new Exception("File '$this->path' doesn't exists.");
		}
		
		$zip = new \ZipArchive();

		if (!$zip->open($this->path)) {
			throw new Exception("File '$this->path' is corrupt or not a package.");
		}
		
		$this->body = $zip->getFromName('word/document.xml');
		
		if ($this->body === false) {
			throw new Exception("Failed to open '$this->filename/word/document.xml'.");
		}
		
		$this->document = \DOMDocument::loadXML($this->body);
	}
	
	/**
	 * Returns document body contents
	 *
	 * @return string body contents
	 */
	public function getBody() {
		return $this->body;
	}
	
	/**
	 * Returns parsed document as DOMDocument
	 *
	 * @return DOMDocument document
	 */
	public function getDocument() {
		return $this->document;
	}
	
	/**
	 * Sets document body contents
	 *
	 * @param string $body body contents
	 */
	public function setBody($body) {
		$this->body = $body;
	}
	
	/**
	 * Sets documents DOMDocument and updates body
	 *
	 * @param DOMDocument $document
	 */
	public function setDocument(\DOMDocument $document) {
		$this->document = $document;
		$this->body = $document->saveXML();
	}
	
	/**
	 * Saves document
	 *
	 * @param string $path OPTIONAL path where to save document, this document path by default
	 */
	public function save($path = null) {
		if ($path == null)
			$path = $this->path;
		
		if ($this->path != $path)
			if (!copy($this->path, $path))
				throw new \Exception("Failed to copy '$this->path' to '$path'");
		
		$zip = new \ZipArchive();

		if (!$zip->open($path)) {
			throw new \Exception("File '$this->path' is corrupt or not a package.");
		}
		
		$zip->deleteName('word/document.xml');
		$zip->addFromString('word/document.xml', $this->body);
		
		return $zip->close();
	}
}