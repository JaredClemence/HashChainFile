<?php

namespace JRC\HashChainFile;

use JRC\HashChainFile\FileComponent\Header;
use JRC\HashChainFile\FileComponent\Body;
use JRC\binn\BinnSpecification;

/**
 * Description of HashChainFile
 *
 * @author jaredclemence
 */
class HashChainFile extends \stdClass {
    /** @var Header */
    public $header;
    /** @var Body */
    public $body;
    
    private $readonly;
    
    public function __set( $attribute, $value ){
        if( $this->readonly ) return;
        
        $this->body->$attribute = $value;
    }
    
    public function __get( $attribute ){
        $value = null;
        if( isset( $this->body->$attribute ) ){
            $value = $this->body->$attribute;
        }
        return $value;
    }
    
    public function __construct( $customHeaders = null ) {
        if( $customHeaders === null ){
            $timestamp = \microtime( true );
            $customHeaders = compact('timestamp');
        }
        $this->readonly = true;
        $this->header = new Header();
        $this->body = new Body();
        $this->setHeaderValues( $customHeaders );
    }
    
    public function getFileHeader(){
        return $this->header;
    }
    
    public function getHeaderValue( $field ){
        return $this->header->{$field};
    }
    
    public function getFileBody(){
        return $this->body;
    }

    public function setHeaderValues(array $customHeaders) {
        foreach( $customHeaders as $key => $value ){
            $this->header->{$key} = $value;
        }
    }
    
    public function makeWriteable(){
        $this->body = $this->body->getWritableCopy();
        $this->readonly = false;
    }
    
    public function replaceContent( Header $header, Body $body ){
        $this->header = $header;
        $this->body = $body;
    }

    public function getFileContent() {
        $binnSpec = new BinnSpecification();
        return $binnSpec->write($this);
    }

}
