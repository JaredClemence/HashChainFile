<?php

namespace JRC\HashChainFile\FileComponent;

use JRC\binn\BinnSpecification;
use JRC\HashChainFile\DataElement\MerkleTreeElement;

/**
 * Description of FilePart
 *
 * @author jaredclemence
 */
class FilePart extends \stdClass {

    private $writeOnce;
    public $userCustomData;
    private $automaticCloning;

    public function __construct() {
        $this->writeOnce = false;
        $this->userCustomData = new \stdClass();
        $this->enableAutomaticCloning();
    }
    
    public function __isset( $name ){
        return isset( $this->$name ) || isset( $this->userCustomData->$name );
    }

    public function __clone() {
        $this->userCustomData = clone $this->userCustomData;
    }

    protected function enableWriteOnce() {
        $this->writeOnce = true;
    }

    protected function disableWriteOnce() {
        $this->writeOnce = false;
    }

    public function getWritableCopy() {
        $clone = clone $this;
        $clone->disableWriteOnce();
        $clone->disableAutomaticCloning();
        return $clone;
    }

    public function disableAutomaticCloning() {
        $this->automaticCloning = false;
    }

    public function enableAutomaticCloning() {
        $this->automaticCloning = true;
    }
    
    public function readContentFromGenericObject( $object ){
        foreach( $object as $key=>$value ){
            $this->$key = $value;
        }
    }

    /**
     * This method ensures that we can make FilePart objects writeOnce enabled.
     * To protect against references that can be altered at a later time, we clone 
     * and duplicate all items that might have references to other locations.
     * 
     * This makes this a memory intensive object. Remove from memory as soon as possible.
     * 
     * @param string $attribute
     * @param mixed $value
     */
    public function __set($attribute, $value) {
        $attributeIsNotSet = !isset($this->userCustomData->{$attribute});
        $writeOnceIsDisabled = $this->writeOnce === false;
        if (!isset($this->userCustomData->{$attribute}) || $this->writeOnce === false) {
            $value = $this->getUniqueValueReference($value);
            $this->userCustomData->{$attribute} = $value;
        }
    }

    private function getUniqueValueReference($value) {
        if ($this->automaticCloning == true) {
            $value = $this->performAutomaticCloning($value);
        }
        return $value;
    }

    private function performAutomaticCloning($value) {
        if (is_array($value)) {
            foreach ($value as &$valueSlot) {
                $valueSlot = $this->getUniqueValueReference($valueSlot);
            }
            unset($valueSlot);
        }else if (is_object($value)) {
            $value = clone $value;
        }
        return $value;
    }

    public function __get($attribute) {
        $value = null;
        if (isset($this->userCustomData->{$attribute})) {
            $value = $this->userCustomData->{$attribute};
        }
        return $value;
    }

    /**
     * @return string
     */
    public function getHash(): string {
        $binaryRepresentationOfSelf = $this->getBinaryRepresentationOfSelf();
        return $this->makeHash($binaryRepresentationOfSelf);
    }

    /**
     * @return string
     */
    public function getMerkleRoot(): string {
        $binarySelf = $this->getBinaryRepresentationOfSelf();
        $merkleTree = new MerkleTreeElement($binarySelf);
        return $merkleTree->getHash();
    }

    private function getBinaryRepresentationOfSelf() {
        $binnSpec = new BinnSpecification();
        $binaryRepresentationOfSelf = $binnSpec->write($this);
        return $binaryRepresentationOfSelf;
    }

    private function makeHash($binaryString) {
        return hash("sha256", $binaryString, true);
    }

}
