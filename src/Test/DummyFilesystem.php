<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2017.02.17.
 * Time: 16:39
 */

namespace Webtown\DeployerRecipesBundle\Test;

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
     * Megnézzük-e valójában, hogy létezik-e a fájl?
     *
     * @var bool
     */
    protected $allowOriginalExists;

    /**
     * @var array
     */
    protected $dumpedFiles = [];

    public function __construct($existingFiles = [], $allowOriginalExists = true)
    {
        $this->existingFiles = $existingFiles;
        $this->allowOriginalExists = $allowOriginalExists;
    }

    public function exists($file)
    {
        return in_array($file, $this->existingFiles) || ($this->allowOriginalExists && parent::exists($file));
    }

    public function dumpFile($filename, $content)
    {
        $this->dumpedFiles[AbstractTemplateTestCase::getRealPath($filename)] = $content;
    }

    public function getDumpedFiles()
    {
        return $this->dumpedFiles;
    }
}
