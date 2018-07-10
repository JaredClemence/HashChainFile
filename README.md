# HashChainFile

A hash chain file is intended to use blockchain-like headers to link file versions in succession. 
Each file that is altered is stored as a new hash. The file headers point to the 
previous file version. This implementation stores all files in the same directory.

## Benefits

  * The HashChainFile uses a file hash as a file name, which allows for **faster file-access**. Either the requested file exists or it does not. No search is necessary.
  * The HashChainFile stores previous versions in separate files which **reduces the read time and transfer time** of current file versions.
  * The HashChainFile uses a MerkleRoot to 

## File Header Hash

| Field           | Length   | Description                                                                                               |
|-----------------|----------|-----------------------------------------------------------------------------------------------------------|
| [Mixed Content] | n Bytes  | User defined header fields. This data should be consistent across files to identify related content sets. |
| previous_hash   | 32 Bytes | Hash of previous file header.                                                                             |
| merkle_root     | 32 Bytes | Hash at root of a merkle tree that verifies file integrity.                                               |

### Defining the header fields.

The user can define header fields that identify unique files. For example, in one 
implementation, a patient name, a date of birth, and a date of visit identify related files. 
Once set, this value should not be changed. For example, in the patient name example, 
it is frequent that a patient corrects the spelling of the name. In this case, 
the patient name will remain as originally written in the file header, even though it 
may change in the file body.

Header fields are defined on file initialization with an array. The example here uses 
the patient name concept presented above as an example.

    //This is the data that I choose to use in the header.
    $originalLastName = "Javier";
    $originalFirstName = "Xavier";
    $originalDateOfBirth = new \DateTime( "2010-10-02 00:00:00-07:00", new \DateTimeZone("America/Los_Angeles") );
    $originalDateOfVisit = new \DateTime( "2018-07-28 10:30:00-07:00", new \DateTimeZone("America/Los_Angeles") );

    //I package it in an array.
    $customHeader = compact( "originalLastName", "originalFirstName", "originalDateOfBirth", "originalDateOfVisit" );

    //I create a new file instance.
    $hashFile = new \JRC\HashChainFile\HashChainFile( $customHeader );

    //All versions of this file will contain a header with the fields "originalLastName", "originalFirstName", "originalDateOfBirth", and "originalDateOfVisit".
    //The original reference in previous_hash will be a 32 Byte string of null bytes.
    //For newer PHP coders, this means that there will be a string where each character is "\x00" (that is one character not four---check the PHP manuals).

### Security Concerns

Note, the file format does not handle security concerns. If multiple people edit an old version of a 
file and then save it, the new version of the file will point to the previous version. For this reason 
it is up to YOU to track which version of the file reflects the correct current version.

By pointing to the "correct" current version ("correct" as you define it), you will create a chain 
that traces file history.  The file versions outside that chain (called "Uncles" in blockchain) will not be 
found on the file iteration, because there are no forward pointers. To find uncles, one must identify the 
files that are NOT referenced in a current file chain.

## Facade Use

To make this library easier to use, I have created a facade that makes it more intuitive.
The classes may be used directly, but until you are familiar with the library, use the facade 
to make things more simple.

Note: Look at the FacadeTest.php file for examples of how the facade can be used 
with HashChainFile instances.

### Initialize a facade

In the examples that follow, we will use a `$facade` variable which is initialized here:

    $facade = new \JRC\HashChainFile\Facade();

### Initialize a new file

Files are created as new instances, or they are read from an existing file content. To create a new file chain, 
use the following code as a guide:

    $headers = [];
    $headers["Content-Type"] = "text/html";
    $headers["Company"] = "Pediatric Heart Center";
    //Headers can be set to any value. They are used to make empty files unique, and once 
    //set they cannot change.
    //NOTE: If the header values are left empty, a timestamp value is set automatically to prevent duplication.

    $file = $facade->makeHashFile( $header );
    //new files are automatically set to be writable objects

### Reading a file from existing content

The HashChainFile content is a BINN formatted object. You can use any BINN format 
parser to unpack the file. A BINN reader will return the file as a nested stdClass object.

In order to take advantage of this library, you should use the facade to pack the BINN data 
into an instance of a HashChainFile.

    //read file data into a content variable. We will use $fileContent.
    $fileInstance = $facade->parseFileData( $fileContent );

    //files are READONLY by default. To edit the file, use this method:
    $facade->makeFileWriteable( $fileInstance );

### Writing to a file

File instances automatically separate header content from body content. I use 
magic methods to capture attributes dynamically. All attributes that you attempt to 
define will be stored on the `body` object and kept separate from the file header.

In short: You don't have to worry about writing and reading from a file instance.
Just use the file as a stdClass object, but remember that your specific class instances 
will be rewritten as stdClass objects in the file. All custom classes will be stripped 
and only publicly accessible values will be written to the file.

Tip: Read generic data from a file into your custom classes. When you save, write 
from your classes into the file using generic objects. Don't use the file to store 
object instances directly.

    //data can be written to the file. All data written to the file is appended to the file body.
    $file->content = "Some text here.";
    $file->separatedContent = "Other text here.";
    $file->encryptedContent = "put encrypted content here";
    //these attributes are arbitrary, call them whatever you like.

    $newHeaderHash = $facade->getFileReferenceId( $file );
    $newFileContent = $facade->getBinaryContentForFileStorage( $file );
    //Any change to the file content will produce a new hash reference...use the reference id to store the file for quick access.
    //The file content generated must not be altered if it is to be used.
    //Atering the file content will cause the merkle root to change, which will invalidate the file structure.

### Reading header values

When you must read a header value, use the following method:

    $headerValue = $facade->getFileHeaderValue( $file, $attributeName );

### Get reference to previous file version

Files are linked together by the hashes of their headers.

    $previousFileHash = $facade->readPreviousFileHash( $file );
    $previousFileContent = ""; //load this value with the file content referenced by $previousFileHash
    $previousFile = $facade->parseFileData( $previousFileContent );
    $previousFileHash2 = $facade->readPreviousFileHash( $previousFile );
    $nextFileContent2 = ""; //load the next value again.
    //using this method, you can iterate from a current file version back through all the versions to the initial file.