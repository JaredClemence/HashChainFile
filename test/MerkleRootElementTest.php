<?php

require_once realpath(__DIR__ . "/../autoload.php");

use PHPUnit\Framework\TestCase;
use JRC\HashChainFile\DataElement\MerkleTreeElement;
use JRC\binn\core\BinaryStringAtom;

/**
 * Description of MerkleRootElementTest
 *
 * @author jaredclemence
 */
class MerkleRootElementTest extends TestCase{
    /**
     * 
     * @param type $charArray
     * @dataProvider provideTreeGenerationCases
     */
    public function testElementTreeGeneration( $charArray ){
        $dataString = $this->makeString( $charArray );
        $tree = new MerkleTreeElement( $dataString );
        $elementArray = $tree->getLeafs();
        $maxKey = null;
        foreach( $elementArray as $key=>$element ){
            $maxKey = $key;
            /* @var $element MerkleTreeElement */
            $hash = $element->getHash();
            $readableHash = BinaryStringAtom::createHumanReadableHexRepresentation($hash);
            $elementSource = $charArray[ $key ];
            $sourceString = $this->makeString([$elementSource]);
            $expectedHash = hash( "sha256", $sourceString, true );
            $readableExpectedHash = BinaryStringAtom::createHumanReadableHexRepresentation($expectedHash);
            $this->assertEquals( $readableExpectedHash, $readableHash, "The leaf element in position $key does not have the expected hash result." );
        }
        //remaining elements are null based
        $nullString = $this->makeString(["\x00"]);
        $expectedHash = hash( "sha256", $nullString, true );
        $readableExpectedHash = BinaryStringAtom::createHumanReadableHexRepresentation($expectedHash);
        for( $i = $maxKey + 1; $i < count( $elementArray ); $i++ ){
            $element = $elementArray[ $i ];
            $hash = $element->getHash();
            $readableHash = BinaryStringAtom::createHumanReadableHexRepresentation($hash);
            $this->assertEquals( $readableExpectedHash, $readableHash, "The leaf element in position $i does not have the expected hash result." );
        }
    }
    
    public function provideTreeGenerationCases(){
        return [
            "Single Element Tree" =>[["\x01"]],
            "Two Element Tree"=>[["\x01","\x02"]],
            "Three Element Tree" =>[["\x01","\x02","\x03"]],
            "Four Element Tree" =>[["\x01","\x02","\x03", "\x04"]],
        ];
    }

    private function makeString($array) {
        $string = "";
        foreach( $array as $char ){
            $charString = "";
            for( $i =0; $i < 32; $i++ ){
                $charString .= $char;
            }
            assert(strlen( $charString) == 32);
            $string .= $charString;
        }
        return $string;
    }

}
