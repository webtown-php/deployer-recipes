<?php

namespace Deployer;

use Deployer\Server\Local;
use Deployer\Task\Context;
const FILE_CHECK_EXISTS = 'a';
const FILE_CHECK_IS_FILE = 'f';
const FILE_CHECK_IS_DIR = 'd';
const FILE_CHECK_IS_SYMBOLIC_LINK = 'h';
const FILE_CHECK_IS_READABLE = 'h';
const FILE_CHECK_IS_WRITABLE = 'w';
const FILE_CHECK_IS_EXECUTABLE = 'x';

/**
 * Check the file or directory exists.
 *
 * @param $file
 * @param string $checkType
 * @return bool
 */
function fileExists($file, $checkType = FILE_CHECK_IS_FILE)
{
    $file = parse($file);
    // The `run()` throw error on some check in local mode!
    if (Context::get()->getServer() instanceof Local) {
        switch ($checkType) {
            case FILE_CHECK_EXISTS:
                return file_exists($file);
            case FILE_CHECK_IS_FILE:
                return is_file($file);
            case FILE_CHECK_IS_DIR:
                return is_dir($file);
            case FILE_CHECK_IS_SYMBOLIC_LINK:
                return is_link($file);
            case FILE_CHECK_IS_READABLE:
                return is_readable($file);
            case FILE_CHECK_IS_WRITABLE:
                return is_writable($file);
            case FILE_CHECK_IS_EXECUTABLE:
                return is_executable($file);
        }
    }

    $response = run("if [[ -{$checkType} {$file} ]] ; then echo \"1\" ; else echo \"0\" ; fi");

    return $response->toString() == '1';
}
