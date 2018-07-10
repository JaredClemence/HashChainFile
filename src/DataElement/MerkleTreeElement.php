<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace JRC\HashChainFile\DataElement;

/**
 * Description of MerkleTreeElement
 *
 * @author jaredclemence
 */
class MerkleTreeElement {

    private $root;
    private $leftElement;
    private $rightElement;

    public function __construct($data = "") {
        if (strlen($data) <= 32) {
            $this->createMerkleTreeLeaf($data);
        } else {
            $this->createMerkleTreeNode($data);
        }
    }
    
    public function getHash(){
        return $this->root;
    }
    
    public function getLeafs(){
        if( $this->leftElement != null ){
            $leftData = $this->leftElement->getLeafs();
            $rightData = $this->rightElement->getLeafs();
            return array_merge($leftData,$rightData);
        }else{
            return [$this];
        }
    }

    private function padDataToLength($data) {
        while (strlen($data) < 32) {
            $data .= "\x00";
        }
        return $data;
    }

    private function hash($expandedData) {
        $sha256 = hash("sha256", $expandedData, true);
        return $sha256;
    }

    private function createMerkleTreeLeaf($data) {
        $expandedData = $this->padDataToLength($data);
        $this->root = $this->hash($expandedData);
        $this->leftElement = null;
        $this->rightElement = null;
    }

    private function createMerkleTreeNode($data) {
        list($leftPart, $rightPart) = $this->separateDataIntoLeftAndRightParts($data);
        $this->leftElement = new MerkleTreeElement($leftPart);
        $this->rightElement = new MerkleTreeElement($rightPart);
        $binaryAddition = $this->addRoots( $this->leftElement->getHash(), $this->rightElement->getHash() );
        $hash = $this->hash($binaryAddition);
        $this->root = $hash;
    }

    private function addRoots($hash1, $hash2) {
        return $hash1 . $hash2;
    }

    private function separateDataIntoLeftAndRightParts($data) {
        $length = strlen( $data );
        $completeUnits = ceil( $length / 32 );
        if( $completeUnits %2 != 0 ){
            $completeUnits += 1;
        }
        $bytes = ($completeUnits / 2) * 32;
        $leftString = substr( $data, 0, $bytes );
        $rightString = substr( $data, $bytes );
        assert( strlen( $leftString ) >= strlen( $rightString ) );
        assert( strlen( $leftString ) < strlen( $data ) );
        return [$leftString, $rightString ];
    }

}
