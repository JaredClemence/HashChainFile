<?php

namespace JRC\HashChainFile\Helper;

use \JRC\HashChainFile\HashChainFile;

/**
 * Description of FileReader
 *
 * @author jaredclemence
 */
class FileReader {
    /**
     * @param string $byteString
     * @return HashChainFile
     */
    public function readFileContent( string $byteString ) : HashChainFile {
        $file = new HashChainFile();
        return $file;
    }
}
