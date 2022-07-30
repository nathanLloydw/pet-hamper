<?php

class Rex_Feed_Exception extends Exception
{
    private $_details;

    function __construct ( $message, $code = 0,  Exception $previous = null, $details = array() ) {

        parent::__construct($message, $code,  $previous );
        $this->_details = $details;
    }

    public function getDetails() {

        return $this->_details;
    }
}