<?php
namespace Docx;

/**
 * Exception thrown when there is parse error in template file
 */
class ParseException extends \Exception
{
    public function __construct($message, $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}