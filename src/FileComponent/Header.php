<?php

namespace JRC\HashChainFile\FileComponent;

use \JRC\HashChainFile\FileComponent\FilePart;

/**
 * The header is a smaller part of the file structure that can be referenced for 
 * information about file content.
 * 
 *   - The header is the portion that is hashed to create the file reference.
 *   - The header contains a Merkle root that helps identify changes to the body.
 *   - The header contains a hash of the previous file's header to verify that two blocks are related in time-sequence.
 *   - The header contains a version count so that issues of chain length can be quickly resolved.
 *  
 * By making the header separate, we allow manipulations to be performed on the body later 
 * without affecting this publicly readable component. For example, the body can be signed or encrypted separately 
 * without affecting the header.
 *
 * @author jaredclemence
 */
class Header extends FilePart {

    public $previous_hash;
    public $merkle_root;
    public $chain_height;
    public $timestamp;

    public function __construct() {
        parent::__construct();
        $sixteenBytes = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
        $this->previous_hash = $sixteenBytes . $sixteenBytes;
        $this->merkle_root = $sixteenBytes . $sixteenBytes;
        $this->chain_height = 0;
        $this->enableWriteOnce();
    }

    public function getPreviousHash() {
        return $this->previous_hash;
    }

    public function getMerkleRootReferenceValue() {
        return $this->merkle_root;
    }

    public function setMerkleRootReferenceValue($oldHash, $newHash) {
        if ($this->getMerkleRootReferenceValue() == $oldHash) {
            $this->merkle_root = $newHash;
        }
    }
    
    public function updateTimestamp(){
        //uses default zone
        $date = new \DateTime("now");
        $this->timestamp = $date->format("r");
    }

    public function setNewPreviousHash($oldHash, $newHash) {
        if ($this->getPreviousHash() == $oldHash) {
            $this->previous_hash = $newHash;
            $this->increaseChainHeight();
        }
    }

    private function increaseChainHeight() {
        $this->chain_height++;
    }

    public function getChainHeight() {
        return $this->chain_height;
    }

}
