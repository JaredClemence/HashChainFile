<?php

require_once realpath(__DIR__ . "/../autoload.php");

use PHPUnit\Framework\TestCase;

use JRC\HashChainFile\HashChainFile;
use JRC\HashChainFile\Helper\FileReader;
use Faker\Factory;

/**
 * Description of HachChainFileTest
 *
 * @author jaredclemence
 */
class HashChainFileTest extends TestCase {
    private $testFile;
    
    public function setUp(){
        $this->testFile = $this->generateTestFile();
    }
    
    public function testReadOnly(){
        $expectation = "Some data";
        $this->testFile->newField = $expectation;
        $this->assertNotEquals( $expectation, $this->testFile->newField, "The file generated from binary data is automatically set to read only status." );
        return $this->testFile;
    }
    
    public function testIsSet(){
        $faker = Factory::create();
        for( $i = 0; $i < 5; $i++ ){
            $key = $faker->word;
            $value = $faker->word;
            $this->assertFalse( isset( $this->$key ) );
            $this->$key = $value;
            $this->assertTrue( isset( $this->$key ) );
        }
    }
    
    /**
     * @depends testReadOnly
     */
    public function testEnableWrite( HashChainFile $file ){
        $file->makeWriteable();
        $expectation = "Some data";
        $file->newAttribute = $expectation;
        $this->assertEquals( $expectation, $file->newAttribute, "The file is writable after the makeWritable function is called." );
    }
    
    public function testReadHeader(){
        $value = $this->testFile->getHeaderValue("created_by");
        $expectation = "Jared";
        $this->assertEquals( $expectation, $value, "The header value can be read by using the getHeaderValue method" );
        $this->testFile->makeWriteable();
        $this->testFile->created_by = "John";
        
        $valueAfterOverwrite = $this->testFile->getHeaderValue("created_by");
        $this->assertEquals( $expectation, $valueAfterOverwrite, "The header value is not overwritten by data written to the file directly." );
    }
    
    public function testAutomaticHeaderUpdates(){
        $hash = $this->testFile->getHeaderHash();
        $binnRepresentation = $this->testFile->getFileContent();
        
        $this->testFile->unsuccessfulWrite = "BadData";
        
        $newHash = $this->testFile->getHeaderHash();
        $newBinnRepresentation = $this->testFile->getFileContent();
        
        $this->assertEquals( $hash, $newHash, "The header hash does not change when the file content does not change." );
        $this->assertEquals( $binnRepresentation, $newBinnRepresentation, "The binn representation does not change when the file content does not change." );
    }
    
    private function generateTestFile(){
        $file = $this->makeFile();
        $binary = $file->getFileContent();
        
        $tmpDir = sys_get_temp_dir();
        $reader = new FileReader();
        return $reader->readFileContent($binary);
    }

    private function makeFile() {
        $header = [
            "created_by"=>"Jared",
            "number_field"=>1
        ];
        $house = $this->makeHouse();
        $house2 = $this->makeHouse();
        
        $file = new HashChainFile($header);
        $file->makeWriteable();
        $file->house = $house;
        $file->allHouses = [ $house, $house2 ];
        
        return $file;
    }

    public function makeHouse() {
        $house = new House();
        $room1 = new Room();
        $room2 = new Room();
        $room3 = new Room();
        $room1->setSize(500);
        $room1->setName("Living Room");
        $room2->setSize(124);
        $room2->setName("Bedroom 1");
        $room3->setSize(124);
        $room3->setName("Bedroom 2");
        $house->addRoom($room1);
        $house->addRoom($room2);
        $house->addRoom($room3);
        return $house;
    }

}

class House {
    public $rooms;
    public function __construct(){
        $this->rooms = [];
    }
    public function __clone(){
        foreach( $this->rooms as &$room ){
            $room = clone $room;
        }
        unset( $room );
    }
    public function addRoom( Room $room ){
        $name = $room->getName();
        $this->rooms[ $name ] = $room;
    }
}

class Room {
    public $name;
    public $size;
    
    public function setName( $name ){
        $this->name = $name;
    }
    public function setSize( $size ){
        $this->size = $size;
    }

    public function getName() {
        return $this->name;
    }
    public function getSize(){
        return $this->size;
    }

}
