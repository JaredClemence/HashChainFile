<?php

namespace JRC\HashChainFile;

use JRC\HashChainFile\FileComponent\Header;
use JRC\HashChainFile\FileComponent\Body;
/**
 * Description of HashChainFile
 *
 * @author jaredclemence
 */
class HashChainFile extends \stdClass {
    /** @var Header */
    private $header;
    /** @var Body */
    private $body;
    
    private $readonly;
    
    public function __set( $attribute, $value ){
        if( $this->readonly ) return;
        
        $this->body->$attribute = $value;
    }
    
    public function __construct( $customHeaders ) {
        $this->readonly = true;
        $this->header = new Header();
        $this->body = new Body();
        $this->setHeaderValues( $customHeaders );
    }
    
    public function getFileHeader(){
        return $this->header;
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
        $this->body->disableWriteOnce();
        $this->body->disableAutomaticCloning();
        $this->readonly = false;
    }

}
