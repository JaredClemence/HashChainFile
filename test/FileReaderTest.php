<?php

require_once realpath(__DIR__ . "/../autoload.php");

use PHPUnit\Framework\TestCase;
use JRC\HashChainFile\HashChainFile;
use JRC\HashChainFile\Helper\FileReader;

/**
 * Description of FileReaderTest
 *
 * @author jaredclemence
 */
class FileReaderTest extends TestCase {
    public function testAFileReadWritesSameBinaryOutput(){
        $file = $this->makeFile();
        /* @var $file HashChainFile */
        $fileHash = $file->getHeaderHash();
        $fileContent = $file->getFileContent();
        
        $reader = new FileReader();
        $readFile = $reader->readFileContent($fileContent);
        $readFileContent = $readFile->getFileContent();
        
        $this->assertEquals( $fileContent, $readFileContent, "The file content is exactly the same each time it is read and written when the object is not changed." );
    }
    
    public function testSavingFileDoesNotChangeHash(){
        $file = $this->makeFile();
        /* @var $file HashChainFile */
        $fileHash = $file->getHeaderHash();
        $fileContent = $file->getFileContent();
        
        $reader = new FileReader();
        $readFile = $reader->readFileContent($fileContent);
        $readFileHash = $readFile->getHeaderHash();
        
        $this->assertEquals( $fileHash, $readFileHash, "The file hash does not change between write and read." );
    }

    private function makeFile() {
        $header = [
            "created_by" => "Jared Clemence",
            "created_on" => "2018-07-10"
        ];
        $file = new HashChainFile( $header );
        $file->fieldA = "ABC";
        $file->fieldB = "CDE";
        $file->fieldC = "DEF";
        return $file;
    }

}
