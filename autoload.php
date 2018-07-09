<?php

$composerAutoload = realpath( __DIR__ . "/vendor/autoload.php" );
if( $composerAutoload ){
    require_once $composerAutoload;
}

/**
 * This extracts the file definitions from the composer.json file and makes 
 * autoload functions based on the data found there. If a class fails to 
 * autoload, double check the fully qualified psr-4 name, and check the 
 * namespace definition in composer.json.
 */

function makePsr4AbstractFunction($namespace, $basePath, $namespaceLength){
    $function = function( $class ) use ( $namespace, $basePath, $namespaceLength ) {
        $result = false;
        if( substr( $class, 0, $namespaceLength ) == $namespace ){
            $relPath = str_replace($namespace, "", $class);
            $relPath = str_replace('\\', '/', $relPath);
            $filePath = $basePath . '/' . $relPath . '.php';
            $realpath = realpath( $filePath );
            if( $realpath ){
                require_once $realpath;
                $result = true;
            }
        }
        return $result;
    };
    return $function;
}

function makePsr4AutoloadFunction( $namespace, $relPath ){
    $root = __DIR__;
    $basePath = realpath( $root .'/' . $relPath );
    $namespaceLength = strlen( $namespace );
    $function = makePsr4AbstractFunction($namespace, $basePath, $namespaceLength);
    return $function;
}

$json = file_get_contents(__DIR__ . '/composer.json');
$spec = json_decode( $json );
$psr4 = $spec->autoload->{'psr-4'};
foreach( $psr4 as $namespace=>$relPath ){
    $function = makePsr4AutoloadFunction( $namespace, $relPath );
    spl_autoload_register($function);
}