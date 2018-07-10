<?php

namespace JRC\HashChainFile;

use JRC\HashChainFile\HashChainFile;
use JRC\HashChainFile\Helper\FileReader;

/**
 * The Facade provides a more intuative interface for users who want to use this 
 * package without learning about all the classes and details of the objects inside.
 * 
 * The objects may be called directly by the user. In that case, use the facade's code to 
 * see how one should interact with the classes in this library.
 *
 * @author jaredclemence
 */
class Facade {
    /**
     * This method creates the first instance of a hash file.
     * 
     * The hash file returned by this method is the first instance in a version chain.
     * It's previous hash value will be null.
     * 
     * @param array $header
     * @param stdClass $object
     * @returns HashChainFile
     */
    public function makeHashFile( $header, $object = null ) : HashChainFile{
        $file = new HashChainFile( $header );
        if( $object ){
            foreach( $object as $attribute=>$value ){
                $file->$attribute = $value;
            }
        }
        return $file;
    }
    
    /**
     * Inspects the file to get the hash that should be used for referencing this 
     * file object in its current state.
     * 
     * Note: Changing the body of a file will cause a change in the header hash, 
     * because the header includes a merkle root of the file content.
     * 
     * Note: It is recommended that the file be stored according to its header hash for 
     * easy retrieval.
     * 
     * @param HashChainFile $file
     * @return string
     */
    public function getFileReferenceId( HashChainFile $file ){
        return $file->getHeaderHash();
    }
    
    /**
     * This returns a deterministic binary output that represents the header and 
     * body of the file using a system-independent format.
     * 
     * Note: The system-independent format means that data is stripped of all class 
     * identity and will be returned to you as generic PHP objects.
     * 
     * Note: The system-independent format can be read by any language and not just PHP, 
     * all that is required is that the system utilize a BINN format parser to read 
     * the generic data into it's own languages native types.
     * 
     * Note: To protect the version chain, a file instance will only update the previous 
     * version hash (in the header) once during its lifetime. This protects against a file version referencing 
     * file content that was never written to disk.  If multiple versions of the file 
     * will be used in a single session, write the file content to disk after each call to this method, 
     * and then read the file content back into a new file object again.
     *
     * @param HashChainFile $file
     * @return string
     */
    public function getBinaryContentForFileStorage( HashChainFile $file ){
        return $file->getFileContent();
    }
    
    /**
     * Sometimes it is important to read the values stored in the header. This method 
     * allows you to do that.
     * 
     * Note: Custom header values cannot be changed once they are written. This allows them 
     * to remain consistent from one version of the file to the next.
     * 
     * Note: The header values that DO change are managed internally. They include the 
     * previous file hash, and the merkle root.  These values are updated every time the 
     * file content is retrieved.
     * 
     * @param HashChainFile $file
     * @param type $headerAttributeName
     * @return type
     */
    public function getFileHeaderValue( HashChainFile $file, $headerAttributeName ){
        return $file->getHeaderValue($headerAttributeName);
    }
    
    /**
     * This method allows one to change a file that has been read from a disk file.
     * 
     * Files default to a read-only status. If you desire the ability to modify the 
     * body of a file, call this method to make the necessary adjustments.
     * 
     * Remember that the hash and file content will change, so the changed file SHOULD BE
     * saved in a new location from where it was read.
     * 
     * @param HashChainFile $file
     * @return void
     */
    public function makeFileWriteable( HashChainFile $file ){
        $file->makeWriteable();
    }
    
    /**
     * Convert a binary string of a HashChainFile back into a HashChainFile object.
     * 
     * This reader fills the object with generic data types. Data can then be read from 
     * non-PHP sources. Provided they are encoded with this format specification.
     * 
     * Note: If the data received from another source IS NOT in HashChainFile format, then 
     * use a generic BINN format reader to parse BINN data into a file.
     * 
     * Note: Files are returned in read-only format. Call `makeFileWritable` on this facade 
     * to make the file editable.
     * 
     * @param string $content
     * @return HashChainFile
     */
    public function parseFileData( string $content ) : HashChainFile {
        $reader = new FileReader();
        return $reader->readFileContent($content);
    }
    
    public function readPreviousFileHash( HashChainFile $file ){
        $prevHash = $file->getPreviousFileReference();
        return $prevHash;
    }
    
    /**
     * Read the chain height of the file.
     * 
     * A file starts at height 0. The next version is 1. The version after that is 2.
     * 
     * Multiple instances can exist at the same height, but only one should be selected 
     * to grow the official file version chain.
     * 
     * @param HashChainFile $file
     * @return int
     */
    public function getFileHeight( HashChainFile $file ){
        return $file->getChainHeight();
    }
    
    /**
     * Verify the merkleRoot in the header matches the merkleRoot generated by the body.
     * 
     * @param HashChainFile $file
     * @return boolean
     */
    public function validateFileIntegrity( HashChainFile $file ){
        return $file->isMerkleRootValid();
    }
}
