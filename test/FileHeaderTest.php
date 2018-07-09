<?php

require_once realpath(__DIR__ . "/../autoload.php");

use PHPUnit\Framework\TestCase;
use JRC\HashChainFile\HashChainFile;
use JRC\binn\BinnSpecification;

/**
 * Description of FileHeaderTest
 *
 * @author jaredclemence
 */
class FileHeaderTest extends TestCase {
    public function testHash(){
        $binnSpec = new BinnSpecification();
        $binnString = $binnSpec->write( $fileHeader );
        $sha256 = hash( "sha256", $binnString, true );
        $this->assertEquals( $sha256, $fileHeader->getHash(), "The header returns a sha256 binary string hash of the binn representation of the file header object." );
    }
    
    public function testMerkleRoot(){
        
    }

    /**
     * This test verifies that the code suggested in README.md performs the expected action.
     * 
     * Expectation - a custom file format has a header, and the header has the fields and field values 
     * that are passed into the constructor.
     */
    public function testCustomFileHeaderCreation() {
        //This is the data that I choose to use in the header.
        $originalLastName = "Javier";
        $originalFirstName = "Xavier";
        $originalDateOfBirth = new \DateTime("2010-10-02 00:00:00-07:00", new \DateTimeZone("America/Los_Angeles"));
        $originalDateOfVisit = new \DateTime("2018-07-28 10:30:00-07:00", new \DateTimeZone("America/Los_Angeles"));

        //I package it in an array.
        $customHeader = compact("originalLastName", "originalFirstName", "originalDateOfBirth", "originalDateOfVisit");

        //I create a new file instance.
        $hashFile = new HashChainFile($customHeader);
        
        $this->assertHashFileHasCustomHeader( $hashFile, $customHeader );
    }

    private function assertHashFileHasCustomHeader(HashChainFile $hashFile, array $customHeader) {
        $header = $hashFile->getFileHeader();
        foreach( $customHeader as $attributeName=>$value ){
            $this->assertObjectHasAttribute( $attributeName, $header, "The custom header is missing a header attribute." );
            if( is_string( $value ) ){
                $this->assertEquals( $value, $header->{$attributeName}, "The value in the custom header matches the expected value." );
            }else if( is_object( $value ) ){
                $className = get_object_vars( $customHeader[$attributeName] );
                $this->assertInstanceOf( $className, $value, "The header object type is not the expected type." );
            }
        }
    }

}
