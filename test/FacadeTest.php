<?php

require_once realpath(__DIR__ . "/../autoload.php");

use PHPUnit\Framework\TestCase;
use JRC\HashChainFile\Facade;
use JRC\HashChainFile\HashChainFile;

use Faker\Factory;

/**
 * Description of FacadeTest
 *
 * @author jaredclemence
 */
class FacadeTest extends TestCase {
    /** @var Facade */
    private $facade;
    
    static private $tempFileHandles;
    
    /**
     * @beforeClass
     */
    public static function setUpBeforeClass(){
        self::$tempFileHandles = [];
    }
    
    /**
     * @afterClass
     */
    public static function tearDownAfterClass(){
        self::closeAllTempFiles();
    }
    
    public function setUp(){
        $this->facade = new Facade();
    }
    
    public function testFileCreation(){
        $expectedFileBodyContent = $this->makeDataContainer();
        $header = $this->makeAFileHeader();
        $hashFile = $this->initializeANewFile( $header, $expectedFileBodyContent );
        $hashReference = $this->saveFileToDisk( $hashFile );
        $this->assertHashReferenceIsHumanReadable( $hashReference );
        $this->assertHashReferenceEqualsHashOfHeader( $hashReference, $hashFile );
        $newlyReadFile = $this->readFileFromDisk( $hashReference );
        $this->assertHashReferenceEqualsHashOfHeader( $hashReference, $newlyReadFile );
        $this->assertNewlyReadFileIsFirstFileInVersionChain( $newlyReadFile );
        $this->assertBodyOfNewlyReadFileMatchesExpectations( $expectedFileBodyContent, $newlyReadFile );
        return compact('hashReference','expectedFileBodyContent','header');
    }
    
    /**
     * 
     * @param type $hashReference
     * @depends testFileCreation
     */
    public function testChainManagement( $passedData ){
        $hashReference = null; $expectedFileBodyContent = null; $header = null;
        extract( $passedData, EXTR_OVERWRITE );
        $file = $this->readFileFromDisk($hashReference);
        $this->makeFileWritable( $file );
        $this->performChainBreakingFileAlterationsXTimes( $file, $expectedFileBodyContent, 15 );
        $newFileHash = $this->saveFileToDisk($file);
        $this->assertFilePointsToPreviousFile( $newFileHash, $hashReference );
        $this->assertReopenedFileHasCustomHeaderFields($file, $header );
        $this->assertEquals( 1, $file->getChainHeight(), "The chain height should increment by one between file versions." );
        $this->assertTrue( $file->isMerkleRootValid(), "The merkle root is valid." );
    }
    
    private function assertReopenedFileHasCustomHeaderFields($file, $header ){
        foreach( $header as $attributeName=>$expectedValue ){
            $value = $this->facade->getFileHeaderValue($file, $attributeName);
            $this->assertEquals( $expectedValue, $value, "The custom header values match expected values." );
        }
    }

    private function makeDataContainer() {
        $object = new stdClass();
        $object->fieldA = "A text field like every other.";
        $object->fieldB = 5;
        $object->fieldD = new \DateTime("2018-10-01 10:23:00-08:00", new \DateTimeZone("America/Los_Angeles") );
        return $object;
    }

    private function makeAFileHeader() {
        $header = [];
        $header["created_by"] = "Jared Clemence";
        $header["created_on"] = "2018-07-10";
        return $header;
    }

    private function initializeANewFile($header, $object) {
        $hashFile = $this->facade->makeHashFile( $header, $object );
        return $hashFile;
    }

    private function saveFileToDisk(HashChainFile $hashFile) {
        $hashReference = $this->facade->getFileReferenceId($hashFile);
        $fileContent = $this->requestBinaryDataForCreatingANewFile($hashFile);
        $fileHandle = $this->createTemporaryFileHandleForReference( $hashReference );
        fwrite($fileHandle, $fileContent);
        return $hashReference;
    }

    public static function closeAllTempFiles() {
        foreach( self::$tempFileHandles as $key => $tmpFile ){
            fclose($tmpFile);
        }
        self::$tempFileHandles = [];
    }

    private function createTemporaryFileHandleForReference($hashReference) {
        $fileHandle = tmpfile();
        self::$tempFileHandles[ $hashReference ] = $fileHandle;
        return $fileHandle;
    }

    private function readFileFromDisk($fileHash) {
        $fileContent = $this->readTempFileUsingReference( $fileHash );
        $file = $this->facade->parseFileData( $fileContent );
        return $file;
    }

    private function readTempFileUsingReference($fileHash) {
        $content = "";
        if( isset( self::$tempFileHandles[$fileHash] ) ){
            $handle = self::$tempFileHandles[$fileHash];
            fseek( $handle, 0 );
            while( $nextNBytes = fread( $handle, 64 ) ){
                $content .= $nextNBytes;
            }
            fseek( $handle, 0 );
        }
        return $content;
    }

    private function assertNewlyReadFileIsFirstFileInVersionChain($file) {
        $expectedHash = "";
        for( $i=0; $i < 32; $i++ ) $expectedHash .= "00";
        
        $previousFileHash = $this->facade->readPreviousFileHash( $file );
        $this->assertEquals( $expectedHash, $previousFileHash, "The previous file hash references a null byte string of 32 characters." );
    }

    private function assertBodyOfNewlyReadFileMatchesExpectations($object, HashChainFile $file ) {
        foreach( $object as $key=>$value ){
            $fileValue = $file->{$key};
            $this->assertEquals( $value, $fileValue, "The file value for $key matches the value that was set in the test." );
        }
    }

    /**
     * This method is intended to try to break a file chain by making a file go through 
     * several alterations and requesting a file content conversion after each change.
     * 
     * In theory, this could break a file chain if the following happens:
     * 1. A file is read from the system (FileA).
     * 2. File is updated.
     * 3. User requests file content.
     * 4. System thinks a new version is being saved and updates the previous hash pointer of FileB to point to FileA.
     * 5. User makes additional changes before saving.
     * 6. User requests file content.
     * 7. System thinks a new version is again created, and generates FileC with a pointer to FileB.
     * 8. User saves FileC, which points to FileB, but FileB was never saved.
     * 
     * In the above scenario, FileC and FileA exist on the system, but nothing exists to link these files together.
     * 
     * Expected behavior should overcome this with the following:
     * 1. A file is read from the system (FileA).
     * 2. File is updated.
     * 3. User requests file content.
     * 4. System thinks a new version is being saved and updates the previous hash pointer of FileB to point to FileA.
     * 5. System locks the previous pointer to FileA on THIS OBJECT. This lock remains in effect until a new instance of the file object is created by reading FileB file content into a new object.
     * 5. User makes additional changes to FileB before saving.
     * 6. User requests file content.
     * 7. System thinks a new version is again created, and generates FileC, but the system observes a lock on the previous file hash.
     * 8. System generates file content fore FileC pointing to FileA because of lock.
     * 9. User saves FileC, which points to FileA. FileB may or may not have been saved.
     * 
     * In this second scenario, FileC links to FileA, so version history is maintained. If FileB is not saved, nothing is lost.
     * If FileB IS saved, then FileB becomes an "uncle" (a version of the file that is outside of the main file chain).
     * 
     * FileB or FileC can be opened and read into a new file instance in order to continue one of the two chains.
     * 
     * @param HashChainFile $file
     * @param \stdClass $expectedFileBodyContent
     * @param int $count
     */
    private function performChainBreakingFileAlterationsXTimes(HashChainFile $file, \stdClass $expectedFileBodyContent, int $count) {
        for( $i = 0; $i < $count; $i++ ){
            $this->alterFileContent( $file, $expectedFileBodyContent );
            $this->requestBinaryDataForCreatingANewFile( $file );
            //we skip the file write.
        }
    }

    private function alterFileContent($file, $expectedFileBodyContent) {
        $faker = Factory::create();
        $rand_key = $faker->name();
        $value = implode( " ", $faker->words() );
        
        if( $faker->randomDigit % 2 == 0 ){
            $objectArray = get_object_vars($expectedFileBodyContent);
            $rand_key = array_rand( $objectArray );
        }
        
        $file->$rand_key = $value;
        $expectedFileBodyContent->$rand_key = $value;
    }

    private function requestBinaryDataForCreatingANewFile($file) {
        return $this->facade->getBinaryContentForFileStorage($file);
    }

    private function assertFilePointsToPreviousFile($newFileHash, $hashReference) {
        $file = $this->readFileFromDisk($newFileHash);
        $prevHashRef = $this->facade->readPreviousFileHash($file);
        $this->assertEquals( $hashReference, $prevHashRef, "The file should reference it's predecessor with the value '$hashReference'" );
    }

    private function assertHashReferenceIsHumanReadable($hashReference) {
        $this->assertRegExp( '/[a-f0-9]+/', $hashReference, "The hash should be returned to the user in a readable string and not as raw binary." );
    }

    private function makeFileWritable($file) {
        $this->facade->makeFileWriteable($file);
    }

    private function assertHashReferenceEqualsHashOfHeader($hashReference, HashChainFile $hashFile) {
        $headerHash = $hashFile->getHeaderHash();
        $this->assertEquals( $hashReference, $headerHash, "The hash value matches the expected value." );
    }
}
