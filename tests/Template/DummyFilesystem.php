<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2017.02.17.
 * Time: 16:39
 */

namespace Tests\Webtown\DeployerRecipesBundle\Template;

use Symfony\Component\Filesystem\Filesystem;

class DummyFilesystem extends Filesystem
{
    /**
     * Már létező fájlok. Ha erre hívják meg az exists() fv-t, akkor true-val tér vissza.
     *
     * @var array
     */
    protected $existingFiles = [];

    /**
     * @var array
     */
    protected $dumpedFiles = [];

    public function __construct($existingFiles = [])
    {
        $this->existingFiles = $existingFiles;
    }

    public function exists($file)
    {
        return file_exists($file) || in_array($file, $this->existingFiles);
    }

    public function dumpFile($filename, $content)
    {
        $this->dumpedFiles[$filename] = $content;
    }

    public function getDumpedFiles()
    {
        return $this->dumpedFiles;
    }
}
