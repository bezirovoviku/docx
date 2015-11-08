<?php
require_once('../src/Docx/Generator.php');
require_once('../src/Docx/Generator/Filter.php');
require_once('../src/Docx/Generator/Filters/FormatFilter.php');
require_once('../src/Docx/Generator/Filters/Upper.php');
require_once('../src/Docx/Generator/Filters/Lower.php');
require_once('../src/Docx/Generator/Filters/Date.php');
require_once('../src/Docx/Generator/Filters/Number.php');
require_once('../src/Docx/ParseException.php');
require_once('../src/Docx/Document.php');

$input_data = array(
	array(
		'nadpis' => 'TEXT',
		'number' => 1564727.564,
		'date' => '2015-11-05 11:30',
		'format' => 'd.m.Y H:i:s',
		'items' => array(
			array('name' => 'Test 1'),
			array('name' => 'Test 2')
		)
	),
	array(
		'nadpis' => 'TEXT 2',
		'number' => '135486753132',
		'date' => time(),
		'format' => 'd.m.Y H:i:s',
		'items' => array(
			array('name' => 'Test 85'),
			array('name' => 'Text 2')
		)
	)
);

$generator = new Docx\Generator();
$generator->addFilters();
$generator->setTemplate('data/template.docx');
$generator->setTmp('./tmp/');
$generator->generateArchive($input_data, 'tmp/archive.zip');