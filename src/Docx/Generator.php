<?php
namespace Docx;

/**
 * This class manages generation of individual documents
 */
class Generator
{
	///@var \Docx\Document $template template used for generation
	protected $template;
	
	///@var string $tmp path to tmp folder
	protected $tmp;
	
	///@var string $regexForeach regular expression used to find foreach
	protected $regexForeach = '/{\s*foreach\s+([^\s]*)\s+as\s+([^\s]*)\s*}/i';
	///@var string $regexForeachEnd regular expression used to find foreach end
	protected $regexForeachEnd = '/{\s*\/foreach\s*}/i';
	///@var string $regexForeach regular expression used to find any template tag
	protected $regexReplace = '/\{([^\/][^}]*)\}/';
	
	///@var \Docx\Generator\Filter[] $filters available replacement filters
	protected $filters = array();
	
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
	 * Automatically adds basic filters
	 */
	public function addFilters() {
		$this->addFilter(new Generator\Filters\Upper);
		$this->addFilter(new Generator\Filters\Lower);
		$this->addFilter(new Generator\Filters\Date);
		$this->addFilter(new Generator\Filters\Number);
	}
	
	/**
	 * Adds new filter to generator
	 *
	 * @param \Docx\Docx\Generator\Filter $filter filter added
	 */
	public function addFilter($filter) {
		$this->filters[$filter->getTag()] = $filter;
	}
	
	/**
	 * Tries to find filter by its tag
	 *
	 * @param string $tag filter tag
	 * @return \Docx\Docx\Generator\Filter|null
	 */
	public function getFilter($tag) {
		return isset($this->filters[$tag]) ? $this->filters[$tag] : null;
	}
	
	/**
	 * Generates document using specified data
	 *
	 * @param array  $data data for template
	 * @param string $path path to new document
	 */
	public function generate($data, $path) {
		//Save template body
		$template = $this->template->getDocument();
		
		//Get template body
		$body = new \DOMDocument();
		$body->loadXML($this->clear($this->template->getBody()));
		
		//Now do replacing
		$this->replace($body, $data);
		
		//Save newly created document
		$this->template->setDocument($body);
		$this->template->save($path);
		
		//Reset template body
		$this->template->setDocument($template);
	}
	
	/**
	 * Clears XML tags from our template tags
	 *
	 * @param string $body
	 * @return string cleared body
	 */
	protected function clear($body) {
		$offset = 0;
		$copy = $body;
		while(preg_match($this->regexReplace, $body, $matches, PREG_OFFSET_CAPTURE, $offset)) {
			//Entire match
			$match = $matches[0][0];
			//Match position
			$offset = $matches[0][1] + strlen($match);
			$copy = str_replace($match, strip_tags($match), $copy);
		}
		return $copy;
	}
	
	/**
	 * Generates archive of documents based on specified data.
	 *
	 * @param array  $data array where each element is data for one document
	 * @param string $path where to save generated archive
	 * @param \Docx\Converter $converter OPTIONAL document converter
	 */
	public function generateArchive($data, $path, $converter = null) {
		//Create directory if nonexistent
		@mkdir(dirname($path), 0770, true);

		//Prepare result archive
		$archive = new \ZipArchive();
		if (!$archive->open($path, \ZipArchive::CREATE)) {
			throw new \Exception("Failed to open result archive.");
		}

		//Create documents and add them to archive
		foreach($data as $index => $docdata) {
			//Generate file
			$file = "{$this->tmp}/document$index.docx";
			$this->generate($docdata, $file);
			
			//Convert if needed
			$ext = "docx";
			if ($converter) {
				$converter->save(new Document($file), $file);
				$ext = $converter->getExtension();
			}
			
			//Put to archive
			$archive->addFile($file, "document$index.$ext");
		}
		
		//Write documents to archive
		$archive->close();
		
		//For some reason, files are written to archive when closing, so delete after closing archive
		foreach($data as $index => $docdata) {
			unlink("{$this->tmp}/document$index.docx");
		}

		//Check if archive was really created
		if (!file_exists($path)) {
			throw new \Exception("Failed to create result archive.");
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
			
			//Get tag result
			$value = $this->parseTag($context, $content);
			
			//Replace the tag
			$body = str_replace($match, $value, $body);
		}
		
		return $body;
	}
	
	/**
	 * Parse tag contents and return tag value
	 *
	 * @param array  $context replacing context
	 * @param string $tag     tag content
	 * @return string parset tag value
	 */
	protected function parseTag($context, $tag) {
		//@TODO: str_getcsv feels bad
		
		//Split to pipes
		//$tags = str_getcsv(trim($tag), '|');
		preg_match_all('/\*(?:\\\\.|[^\\\\\*])*\*|[^|]+/', $tag, $matches);
		$tags = $matches[0];
		
		//Resulting value
		$out = null;
		
		//Go trought pipes, pass value along
		foreach($tags as $tag) {
			//Split to arguments
			//$tag = str_getcsv(trim($tag), ' ');
			preg_match_all('/\*(?:\\\\.|[^\\\\\*])*\*|\S+/', $tag, $matches);
			$tag = $matches[0];
			
			//Basic method or just variable name
			$filter = array_shift($tag);
			
			//Temp value to store filter object if found
			$obj = null;
			
			//If the first arguments isn't first name
			if (substr($filter, 0, 1) == '$' || ($obj = $this->getFilter($filter)) === null) {
				//No arguments, this is probably just variable
				if (count($tag) == 0) {
					$out = $this->findVariable($context, $filter);
					continue;
				}
				
				throw new ParseException("There is no such filter '$filter'");
			}
			
			//Store filter object into right variable
			$filter = $obj;
			
			//Prepare filter arguments
			$arguments = array();
			foreach($tag as $arg) {
				//Not spaces needed!
				$arg = trim(trim($arg), '*');
				
				//If its variable (variables as arguments must have variable sign)
				if (substr($arg, 0, 1) == '$')
					$arg = $this->findVariable($context, substr($arg, 1));
				
				$arguments[] = $arg;
			}
			
			//Play filer
			$out = $filter->filter($this, $context, $arguments, $out);
		}
		
		return $out;
	}
	
	/**
	 * Tries to find variable value by its name
	 *
	 * @param array  $context  replacing context
	 * @param string $variable variable name
	 * @return string|null value or null if not found
	 */
	protected function findVariable($context, $variable) {
		//Remove dolar sign if present
		if (substr($variable, 0, 1) == '$')
			$variable = substr($variable, 1);
		
		//Split by dots
		$parts = explode('.', $variable);
		
		//Find actual value in nested objects if needed
		$value = $context;
		foreach($parts as $part) {
			if (isset($value[$part])) {
				$value = $value[$part];
			} else {
				return null;
			}
		}
		
		return $value;
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