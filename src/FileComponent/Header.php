<?php

namespace JRC\HashChainFile\FileComponent;

use \JRC\HashChainFile\FileComponent\FilePart;

/**
 * Description of Header
 *
 * @author jaredclemence
 */
class Header extends FilePart {

    public $previous_hash;
    public $merkle_root;

    public function __construct() {
        parent::__construct();
        $sixteenBytes = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
        $this->previous_hash = $sixteenBytes . $sixteenBytes;
        $this->merkle_root = $sixteenBytes . $sixteenBytes;
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

    public function setNewPreviousHash($oldHash, $newHash) {
        if ($this->getPreviousHash() == $oldHash) {
            $this->previous_hash = $newHash;
        }
    }

}
