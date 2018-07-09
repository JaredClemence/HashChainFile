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
}
