<?php
namespace Docx\Converter;

/**
 * This convertor uses openoffice/libreoffice headless to convert to pdf
 */
class OPDF implements \Docx\Converter
{
	///@var string $path path to libreoffice binary 
	protected $path;
	
	/**
	 * @param string $path OPTIONAL path to libreoffice binary
	 */
	public function __construct($path = 'soffice') {
		$this->path = $path;
	}
	
	/**
	 * Creates temporary path
	 *
	 * @param string $dir root where path should be made
	 * @param string $prefix prefix for created path
	 * @return string temporary path
	 */
	protected function tempdir($dir = false, $prefix = 'opdf') {
		$tempfile = tempnam($dir ? $dir : sys_get_temp_dir(), $prefix);
		if (file_exists($tempfile)) {
			unlink($tempfile);
		}
		mkdir($tempfile);
		if (is_dir($tempfile)) {
			return $tempfile;
		}
		return null;
	}

	/**
	 * Converts document to different format.
	 *
	 * @param \Docx\Document $document document to be converted
	 * @param string         $filename target filename, if not set, contents of converted file will be returned
	 * @return bool|string either success of writing file or file contents
	 * @throws \Exception
	 */
	public function save(\Docx\Document $document, $filename = null) {
		//Prepare temp path, which will be used to store files for openoffice
		$temp = $this->tempdir();
	
		if (!$temp || !is_dir($temp)) {
			throw new \Exception("Failed to create temp path.");
		}
		
		//Prepare correct paths 
		$import = "$temp/document.docx";
		$export = "$temp/document.pdf";

		//Save document, so command can use it
		if (!$document->save($import))
			throw new \Exception("Failed to export DOCX document.");
		
		//Call libreoffice
		$command = escapeshellarg($this->path) . " --headless -convert-to pdf " . escapeshellarg($import) . " -outdir " . escapeshellarg($temp);
		$output = "";
		$code = 0;
		exec($command, $output, $code);
		
		//Handle error outputs
		if ($code != 0) {
			//Clean after us
			unlink($import);
			unlink($export);
			rmdir($temp);
			
			throw new \Exception("Failed to convert document. Error code: $code.");
		}
		
		//Remove original document
		unlink($import);
		
		//Save to file or return converted contents
		if ($filename) {
			//Move exported to target path
			if (!rename($export, $filename)) {
				//Cleanup
				unlink($export);
				rmdir($temp);
				
				throw new \Exception("Failed to move result document to target.");
			}
			
			//Remove empty directory
			rmdir($temp);
			
			return true;
		} else {
			//Load exported contents, so we can delete it
			$content = file_get_contents($export);
			
			//Cleanup
			unlink($export);
			rmdir($temp);
			
			//Return actual content
			return $content;
		}
	}
	
	public function getExtension() {
		return 'pdf';
	}
}