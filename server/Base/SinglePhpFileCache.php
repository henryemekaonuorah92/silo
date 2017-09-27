<?php

namespace Silo\Base;

use Doctrine\Common\Cache\PhpFileCache;

/**
 * Special case of PhpFileCache which only accept one id inside !
 */
class SinglePhpFileCache extends PhpFileCache
{
    private $filename;

    private $lockedId;

    /**
     * {@inheritdoc}
     */
    public function __construct($file, $id = null, $umask = 0002)
    {
        $fileInfo = new \SplFileInfo($file);
        $this->filename = $fileInfo->getFilename();
        parent::__construct($fileInfo->getPath(), $fileInfo->getExtension(), $umask);

        $this->lockedId = $id;
    }

    /**
     * @param string $id
     *
     * @return string
     */
    protected function getFilename($id)
    {
        if ($this->lockedId !== $id) {
            throw new \Exception(__CLASS__." cannot cache a key that is not ".$this->lockedId.", got $id");
        }
        return $this->directory
            . DIRECTORY_SEPARATOR
            . $this->filename;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        return $this->doFetch($id);
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        return $this->doSave($id, $data, $lifeTime);
    }
}
