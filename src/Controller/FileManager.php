<?php

namespace JRC\HashChainFile\Controller;

use \JRC\HashChainFile\HashChainFile;

/**
 * Description of HashChainFileManager
 *
 * @author jaredclemence
 */
class FileManager {
    private $pathToDirectory;
    
    /**
     * 
     * @param string $hashId
     * @return HashChainFile
     */
    public function getFile( string $hashId ) : HashChainFile {}
    
    /**
     * 
     * @param HashChainFile $file
     */
    public function putFile( HashChainFile $file ){}

}
