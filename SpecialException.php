<?php

class SpecialException extends Exception
{
    private $context;

    public function __construct ( $message = "", $code = 0, Throwable $previous = null, $context = null ) {
        parent::__construct( $message, $code, $previous );
        $this->context = $context;
    }
}