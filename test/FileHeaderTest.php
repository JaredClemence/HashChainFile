<?php

require_once realpath(__DIR__ . "/../autoload.php");

use PHPUnit\Framework\TestCase;
use JRC\HashChainFile\HashChainFile;
use JRC\binn\BinnSpecification;
use JRC\HashChainFile\FileComponent\Header;

/**
 * Description of FileHeaderTest
 *
 * @author jaredclemence
 */
class FileHeaderTest extends TestCase {
    public function testHash(){
        $fileHeader = new Header();
        $fileHeader->originalLastName = "Jimenez";
        $fileHeader->originalFirstName = "Xavier";
        $fileHeader->dateOfBirth = "2017-10-12";
        $fileHeader->dateOfVisit = "2018-07-09";
        $fileHeader->arrayData  = [ new stdClass(), new DateTime("now") ];
        $binnSpec = new BinnSpecification();
        $binnString = $binnSpec->write( $fileHeader );
        $sha256 = hash( "sha256", $binnString, true );
        $this->assertEquals( $sha256, $fileHeader->getHash(), "The header returns a sha256 binary string hash of the binn representation of the file header object." );
    }
    
    public function testWriteOnce(){
        $expectedResult = "ABC";
        $fileHeader = new Header();
        $fileHeader->testFieldOne = $expectedResult;
        $fileHeader->testFieldOne = "Changed Data";
        $this->assertEquals( $expectedResult, $fileHeader->testFieldOne, "The header attributes must not change once they are set." );
    }
    
    public function testPreviousHashAlteration(){
        $hashAlteration1 = hash( "sha256", "aabbcc", true );
        $hashAlteration2 = hash( "sha256", "eebbdd", true );
        $hashAlteration3 = hash( "sha256", "eebbee", true );
        
        $header = new Header();
        $oldHash = $header->getPreviousHash();
        $header->setNewPreviousHash( $oldHash, $hashAlteration1 );
        $this->assertEquals( $hashAlteration1, $header->getPreviousHash(), "The previous hash must be alterable under controlled circumstances." );
        
        $oldHash2 = $header->getPreviousHash();
        $header->setNewPreviousHash( $oldHash2, $hashAlteration2 );
        $this->assertEquals( $hashAlteration2, $header->getPreviousHash(), "The previous hash must be alterable under controlled circumstances." );
        
        $oldHash3 = $header->getMerkleRootReferenceValue();
        $header->setMerkleRootReferenceValue( $oldHash3 . "\x00", $hashAlteration3 );
        $this->assertNotEquals( $hashAlteration3, $header->getMerkleRootReferenceValue(), "The previous hash must NOT be altered when bad data is provided." );
    }
    
    public function testMerkleRootAlteration(){
        $hashAlteration1 = hash( "sha256", "aabbcc", true );
        $hashAlteration2 = hash( "sha256", "eebbdd", true );
        $hashAlteration3 = hash( "sha256", "eebbee", true );
        
        $header = new Header();
        $oldHash = $header->getMerkleRootReferenceValue();
        $header->setMerkleRootReferenceValue( $oldHash, $hashAlteration1 );
        $this->assertEquals( $hashAlteration1, $header->getMerkleRootReferenceValue(), "The previous merkle root reference must be alterable under controlled circumstances." );
        
        $oldHash2 = $header->getMerkleRootReferenceValue();
        $header->setMerkleRootReferenceValue( $oldHash2, $hashAlteration2 );
        $this->assertEquals( $hashAlteration2, $header->getMerkleRootReferenceValue(), "The previous merkle root reference must be alterable under controlled circumstances." );
        
        $oldHash3 = $header->getMerkleRootReferenceValue();
        $header->setMerkleRootReferenceValue( $oldHash3 . "\x00", $hashAlteration3 );
        $this->assertNotEquals( $hashAlteration3, $header->getMerkleRootReferenceValue(), "The previous merkle root reference must NOT be altered when bad data is provided." );
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
            if( is_string( $value ) ){
                $this->assertEquals( $value, $header->{$attributeName}, "The value in the custom header matches the expected value." );
            }else if( is_object( $value ) ){
                $className = get_class( $customHeader[$attributeName] );
                $this->assertInstanceOf( $className, $value, "The header object type is not the expected type." );
            }
        }
    }

}
