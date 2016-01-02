# Docx template generator
This is simple template system for docx files. It uses text replacement, allows cyclical replacements and basic filters.

## Installation
You can install this library using composer

```
composer require bezirovoviku/docx
```

## Requirements

LibreOffice (2+) - only for OPDF converter

## Usage

First you will need to include the generator class

```php
use Docx\Generator;
```

then use generator class as follows

```php
//Creates generator
$generator = new Generator();
//Adds basic filters
$generator->addFilters();
//Sets temporary folder used to store documents
$generator->setTmp('/tmp');
//Sets path to template file
$generator->setTemplate('template.docx');
//Generates zip archive from specified data
$generator->generateArchive(json_decode($this->data, true), 'archive.zip');
```

## Creating custom filters

To create custom filter, you will need to implement \Docx\Generator\Filter interace

```php
class MyFilter implements \Docx\Generator\Filter
{
	/**
	 * Returns tag used to identify filter
	 *
	 * @return string tag identifing filter
	 */
	public function getTag() {
	  return 'myfilter';
	}
	
	/**
	 * Filter given arguments and return result
	 *
	 * @param \Docx\Generator $generator generator calling this filter
	 * @param array           $context   replacing context
	 * @param array           $arguments arguments passed to filter
	 * @param string|null     $input     pipe input (if present)
	 * @return string result
	 */
	public function filter($generator, $context, $arguments, $input) {
	  return strtoupper($input);
	}
}
```

then you will need to add instance of this filter to generator instance, like so:

```php
$generator = new Generator();
$generator->addFilter(new MyFilter());
```

now, in this generator instance, filter MyFilter will be accessible by {myfilter} syntax

## Exporting pdf

This requires libreoffice installed

```php
//Creates generator
$generator = new Generator();
//PDF converter
$converter = new Convertor\OPDF();
//Adds basic filters
$generator->addFilters();
//Sets temporary folder used to store documents
$generator->setTmp('/tmp');
//Sets path to template file
$generator->setTemplate('template.docx');
//Generates zip archive from specified data, converts using converter
$generator->generateArchive(json_decode($this->data, true), 'archive.zip', $converter);
```
