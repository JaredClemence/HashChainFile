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
    private $isInitialFileVersion;
    private $lockedPreviousHashReference;

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
        $this->lockedPreviousHashReference = null;
        $this->header = new Header();
        $this->body = new Body();
        $this->readonly = false;
        $this->isInitialFileVersion = true;
        $this->setHeaderValues( $customHeaders );
    }
    
    public function getFileHeader(){
        return $this->header;
    }
    
    public function getHeaderValue( $field ){
        return $this->header->{$field};
    }
    
    public function getPreviousFileReference(){
        $hash = $this->header->getPreviousHash();
        return $this->convertBinaryToReadableHex($hash);
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
    
    public function makeReadOnly(){
        $this->readonly = true;
    }
    
    public function markFileReadFromContent(){
        $this->isInitialFileVersion = false;
    }
    
    public function replaceContent( Header $header, Body $body ){
        $this->header = $header;
        $this->body = $body;
    }

    public function getFileContent() {
        $this->balanceHeader();
        return $this->writeBinaryContent();
    }

    private function writeBinaryContent() {
        $binnSpec = new BinnSpecification();
        return $binnSpec->write($this);
    }

    private function balanceHeader() {
        $bodyReference = $this->header->getMerkleRootReferenceValue();
        $currentRoot = $this->body->getMerkleRoot();
        if( $currentRoot != $bodyReference ){
            $this->updatePreviousHeaderHash();
            $this->updateMerkleRoot();
        }
    }

    private function updatePreviousHeaderHash() {
        $currentHash = $this->header->getHash();
        $oldHash = $this->header->getPreviousHash();
        if( $this->canUpdatePreviousHash( $oldHash ) ){
            //only update previous version if this is not the first version of the file
            //and if this file has not been updated already.
            $this->header->setNewPreviousHash($oldHash, $currentHash);
            $this->lockedPreviousHashReference = $currentHash;
        }
    }
    
    private function getNullHash(){
        $nullHash = "";
        for( $i=0; $i<32; $i++ ) $nullHash .= "\x00";
        return $nullHash;
    }

    private function updateMerkleRoot() {
        $root = $this->body->getMerkleRoot();
        $oldRoot = $this->header->getMerkleRootReferenceValue();
        $this->header->setMerkleRootReferenceValue($oldRoot, $root);
    }

    public function getHeaderHash() {
        $this->balanceHeader();
        $hash = $this->header->getHash();
        return $this->convertBinaryToReadableHex( $hash );
    }

    private function convertBinaryToReadableHex($hash) {
        $string = "";
        $length = strlen( $hash );
        for( $i=0;$i<$length;$i++){
            $char = $hash[$i];
            $value = ord( $char );
            $hex = dechex( $value );
            $string .= $hex;
        }
        return $string;
    }

    private function canUpdatePreviousHash($oldHash) {
        $hasNullPointer = $this->hasNullPointer();
        $isInitialVersion = $this->isInitialFileVersion;
        $hasLockedPointer = $this->hasLockedPointer();
        
        $notInitialFileVersion = !$hasNullPointer || !$isInitialVersion;
        $notLocked = $hasLockedPointer === false;
        
        $canUpdate = $notInitialFileVersion && $notLocked;
        
        return $canUpdate;
    }

    private function hasNullPointer() {
        $nullHash = $this->getNullHash();
        $prevPointer = $this->header->getPreviousHash();
        return $nullHash == $prevPointer;
    }

    private function hasLockedPointer() {
        return $this->lockedPreviousHashReference !== null;
    }

}
