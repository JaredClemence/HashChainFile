<?php

namespace JRC\HashChainFile\Helper;

use \JRC\HashChainFile\HashChainFile;
use JRC\binn\BinnSpecification;
use JRC\HashChainFile\FileComponent\Header;
use JRC\HashChainFile\FileComponent\Body;

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
    public function readFileContent( string $byteString ) : HashChainFile {$binnSpec = new BinnSpecification();
        $rawDataContainer = $binnSpec->read($byteString);
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
