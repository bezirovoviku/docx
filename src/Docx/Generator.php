<?php
namespace Docx;

use Docx\Document;
use Docx\ParseException;

/**
 * This class manages generation of individual documents
 */
class Generator
{
	///@var \Docx\Document $template template used for generation
	protected $template;
	
	///@var string $tmp path to tmp folder
	protected $tmp;
	
	protected $regexForeach = '/{\s*foreach\s+([^\s]*)\s+as\s+([^\s]*)\s*}/i';
	protected $regexForeachEnd = '/{\s*\/foreach\s*}/i';
	protected $regexReplace = '/\{([^\/][^}]*)\}/';
	
	public function __construct() {
		
	}
	
	/**
	 * Sets template used for generation
	 *
	 * @param string $path path to template docx file
	 */
	public function setTemplate($path) {
		$this->template = new Document($path);
	}
	
	/**
	 * Sets tmp path used for generating archive
	 *
	 * @param string $tmp path to tmp folder
	 */
	public function setTmp($tmp) {
		$this->tmp = $tmp;
	}
	
	/**
	 * Generates document using specified data
	 *
	 * @param array  $data data for template
	 * @param string $path path to new document
	 */
	public function generate($data, $path) {
		//Get template body, save unaltered copy
		$template = $this->template->getDocument();
		$body = $template->cloneNode(true);
		
		//Now do replacing
		$this->replace($body, $data);
		
		//Save newly created document
		$this->template->setDocument($body);
		$this->template->save($path);
		
		//Reset template body
		$this->template->setDocument($template);
	}
	
	/**
	 * Generates archive of documents based on specified data.
	 *
	 * @param array  $data array where each element is data for one document
	 * @param string $path where to save generated archive
	 * @param string $type document type, pdf or docx (don't use pdf now)
	 */
	public function generateArchive($data, $path, $type = 'docx') {
		//Prepare result archive
		$archive = new \ZipArchive();
		if (!$archive->open($path, \ZipArchive::CREATE)) {
			throw new \Exception("Failed to open result archive.");
		}

		//Create documents and add them to archive
		foreach($data as $index => $docdata) {
			$this->generate($docdata, "{$this->tmp}/document$index.docx");
			$archive->addFile("{$this->tmp}/document$index.docx", "document$index.docx");
		}
		
		//Write documents to archive
		$archive->close();
		
		//For some reason, files are written to archive when closing, so delete after closing archive
		foreach($data as $index => $docdata) {
			unlink("{$this->tmp}/document$index.docx");
		}
	}
	
	/**
	 * Main recursive replacing function, manages loops and simple replacing
	 *
	 * @throws ParseException  exception thrown when there is any problem with template (usually problem with cycles)
	 * @param string  $body    document to be replaced
	 * @param array   $context replacement values
	 * @param boolean $do      OPTIONAL should function actually do the replacement (used for recursion)
	 */
	protected function replace($body, $context, $do = true) {		
		//We only search inside text tags
		$texts = $body->getElementsByTagName('t');
		
		//Find cycles
		foreach($texts as $element) {
			$matches = array();
			
			//Is there cycle starter here
			if (preg_match($this->regexForeach, $element->nodeValue, $matches)) {
				//Load cycle values
				//@TODO: Warning for inexistent values?
				$replace_array = array();
				if (isset($context[$matches[1]]))
					$replace_array = $context[$matches[1]];
				
				$replace_name = $matches[2];
				
				//Find parent p (always there)
				$parent = $element;
				while(true) {
					$parent = $parent->parentNode;
					if ($parent == null) {
						throw new ParseException('Foreach is incorectly placed.');
					}
					if ($parent->nodeName == 'w:p')
						break;
				}
				
				//Special clause for row
				if ($parent->parentNode->nodeName == 'w:tc' &&
					$parent->parentNode->parentNode->nodeName == 'w:tr') {
					$parent = $parent->parentNode->parentNode;
				}
				
				//Next element
				$next = $parent->nextSibling;
				
				//Find cycle body and ending element
				$to_repeat = $this->getCycleBody($next);
				
				//When there is any body
				if (count($to_repeat)) {
					//Ending tag element, save for deleting later
					$last = $to_repeat[count($to_repeat)-1]->nextSibling;

					//Repeat elements inside cycles
					foreach($replace_array as $key => $value) {
						//Save values if we're overriding existing
						$tmp1 = isset($context[$replace_name]) ? $context[$replace_name] : null;
						$tmp2 = isset($context['index']) ? $context['index'] : null;
						
						//Load values into context
						$context[$replace_name] = $value;
						$context['index'] = $key;
						
						//Clone elements and append them
						foreach($to_repeat as $item) {
							$item = $item->cloneNode(true);
							$this->replace($item, $context);
							$parent->parentNode->insertBefore($item, $next);
						}
						
						//Restore or clean values
						if ($tmp1 == null)
							unset($context[$replace_name]);
						else
							$context[$replace_name] = $tmp1;
						
						if ($tmp2 == null)
							unset($context['index']);
						else
							$context['index'] = $tmp2;
					}
					
					//Remove body template
					foreach($to_repeat as $item)
						$parent->parentNode->removeChild($item);
					
					//Remove starting and ending tag
					$parent->parentNode->removeChild($last);
					$parent->parentNode->removeChild($parent);
				} else {
					//Empty cycle ... weird
					$parent->parentNode->removeChild($next);
					$parent->parentNode->removeChild($parent);
				}
				
				//Call this method again, there are some tags unexplored!
				$this->replace($body, $context);			
				return;
			}
		}
		
		//Upon reaching this, there isn't any cycle left in element
		foreach($texts as $element) {
			$element->nodeValue = $this->replaceText($element->nodeValue, $context);
		}
	}
	
	/**
	 * Actually replaces any template values inside text
	 *
	 * @param string $body    text
	 * @param array  $context replacing context (what to replace)
	 * @return string text with context replaced
	 */
	protected function replaceText($body, $context) {
		while(preg_match($this->regexReplace, $body, $matches)) {
			//Entire match
			$match = $matches[0];
			//Only tag content
			$content = $matches[1];
			
			//Split to object parts
			$parts = explode('.', $content);
			
			//Now try to find said parts
			$value = $context;
			foreach($parts as $part) {
				if (isset($value[$part])) {
					$value = $value[$part];
				} else {
					$value = '';
					break;
				}
			}
			
			//Replace the tag
			$body = str_replace($match, $value, $body);
		}
		
		return $body;
	}
	
	/**
	 * Finds all elements that are between starting and ending tag
	 *
	 * @throws ParseException when ending tag was not found
	 * @param DOMElement $starter
	 * @return array of DOMElements
	 */
	protected function getCycleBody($starter) {
		$item = $starter;
		$items = array();
		$opened = 1;
		
		while($item != null) {		
			$texts = $item->getElementsByTagName('t');
			foreach($texts as $text) {
				if (preg_match($this->regexForeach, $text->nodeValue)) {
					$opened++;
				}
				if (preg_match($this->regexForeachEnd, $text->nodeValue)) {
					$opened--;
					if ($opened == 0) {
						return $items;
					}
				}
			}
			$items[] = $item;
			$item = $item->nextSibling;
		}
		
		throw new ParseException('Ending foreach tag not found.');
	}
}