<?php

namespace JRC\HashChainFile\Controller;

use \JRC\HashChainFile\HashChainFile;
use \JRC\binn\BinnSpecification;
use JRC\HashChainFile\FileComponent\Header;
use JRC\HashChainFile\FileComponent\Body;

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

    public function readBinaryData($binary) {
        $binnSpec = new BinnSpecification();
        $rawDataContainer = $binnSpec->read($binary);
        $file = new HashChainFile();
        $header = $this->readRawDataIntoHeader( $rawDataContainer );
        $body = $this->readRawDataIntoBody( $rawDataContainer );
        $file->replaceContent($header, $body);
        return $file;
    }

    private function readRawDataIntoHeader($rawDataContainer) {
        $reconstitutedHeader = $rawDataContainer->header;
        $header = new Header();
        $header->readContentFromGenericObject($reconstitutedHeader);
        return $header;
    }

    private function readRawDataIntoBody($rawDataContainer) {
        $reconstitutedBody = $rawDataContainer->body;
        $body = new Body();
        $body->readContentFromGenericObject( $reconstitutedBody );
        return $body;
    }

}
