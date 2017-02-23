<?php

namespace Deployer;

use Deployer\Server\Local;
use Deployer\Task\Context;
use Symfony\Component\Console\Input\InputOption;

set('maintenance_template', 'maintenance.html.tpl'); // maintenance template file path, eg: temp/maintenance.html.tpl
set('maintenance_target', 'maintenance.html');

// maintenance options
option('disable-maintenance', null, InputOption::VALUE_NONE, 'Disable the maintenance.');
option('keep-maintenance-file', null, InputOption::VALUE_NONE, 'Disable the maintenance.');
option('maintenance-file', null, InputOption::VALUE_OPTIONAL, 'Set the maintenance file. Override the `maintenance_template` parameter.');

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

task('maintenance:lock', function () {
    if (!input()->getOption('disable-maintenance') && get('maintenance_template')) {
        $basePath = fileExists('{{deploy_path}}/release', FILE_CHECK_IS_SYMBOLIC_LINK)
            ? get('release_path')
            : get('current');
        if (input()->getOption('maintenance-file')) {
            set('maintenance_template', input()->getOption('maintenance-file'));
        }
        if (fileExists('{{deploy_path}}/current', FILE_CHECK_IS_SYMBOLIC_LINK)) {
            if (fileExists("{$basePath}/{{maintenance_template}}") && !fileExists('{{current}}/{{maintenance_target}}')) {
                run("cp {$basePath}/{{maintenance_template}} {{current}}/{{maintenance_target}}");
            }
        }
    } else {
        writeln('"Create maintenance file" is <comment>disabled</comment>.');
    }
})->desc('Create maintenance file (if enable)');

task('maintenance:unlock', function () {
    if (!input()->getOption('keep-maintenance-file')) {
        run('rm -f {{current}}/{{maintenance_target}}');
    } else {
        writeln('<comment>Keep</comment> the maintenance file!');
    }
})->desc('Remove maintenance file (if exists)');
