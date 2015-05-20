<?php
require_once('../src/Docx/Generator.php');
require_once('../src/Docx/ParseException.php');
require_once('../src/Docx/Document.php');

$input_data = array(
	array(
		'nadpis' => 'TEXT',
		'items' => array(
			array('name' => 'Test 1'),
			array('name' => 'Test 2')
		)
	),
	array(
		'nadpis' => 'TEXT 2',
		'items' => array(
			array('name' => 'Test 85'),
			array('name' => 'Text 2')
		)
	)
);

$generator = new Docx\Generator();
$generator->setTemplate('data/template.docx');
$generator->setTmp('./tmp/');
$generator->generateArchive($input_data, 'tmp/archive.zip');