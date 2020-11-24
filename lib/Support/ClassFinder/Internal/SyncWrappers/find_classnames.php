<?php

function throwJsonError(\Throwable $e): void {
    echo json_encode([
        'error' => [
            'message' => $e->getMessage(),
            'class' => get_class($e),
            'code' => $e->getCode(),
        ],
    ]);
    exit(255);
}

if (sizeof($argv) < 2) {
    throwJsonError(new \RuntimeException("Not enough arguments!"));
}

[$scriptFilename, $appRealpath, $findNamespace, $findOptions] = $argv;

if (!is_dir($appRealpath)) {
    throwJsonError(new \RuntimeException("Invalid app realpath!"));
} else {
    if (!is_file($appRealpath . DIRECTORY_SEPARATOR . "/autoload.php")) {
        throwJsonError(new \RuntimeException("Invalid app realpath! Can't find /vendor/autoload.php file!"));
    }
}

require_once $appRealpath . DIRECTORY_SEPARATOR . "/autoload.php";

if (empty($findNamespace)) {
    $findNamespace = "\\";
}

try {

    $result = \HaydenPierce\ClassFinder\ClassFinder::getClassesInNamespace($findNamespace, $findOptions);

    $result = json_encode([
        'result' => $result,
    ]);
    
    echo $result;
    exit(0);
    
} catch (\Throwable $e) {
    throwJsonError($e);
}