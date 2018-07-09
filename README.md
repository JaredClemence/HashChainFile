#HashChainFile

A hash chain file is intended to use blockchain-like headers to link file versions in succession. 
Each file that is altered is stored as a new hash. The file headers point to the 
previous file version. This implementation stores all files in the same directory.

## Benefits

  * The HashChainFile uses a file hash as a file name, which allows for **faster file-access**. Either the requested file exists or it does not. No search is necessary.
  * The HashChainFile stores previous versions in separate files which **reduces the read time and transfer time** of current file versions.
  * The HashChainFile uses a MerkleRoot to 

##File Header Hash

-------------------------------
| Field | Length | Description |
-------------------------------
|MixedContent| N Bytes | User defined header fields. This data should be consistent across files to identify related content sets. |
--------------------------------
|previous_hash | 32 Bytes | Hash of previous file header. |
---------------------------------
| merkle_root | 32 Bytes | Hash at root of a merkle tree that verifies file integrity |
----------------------------------

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
    $hashFile = new \JRC\HashChainFile( $customHeader );

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

