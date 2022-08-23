<?php
namespace MotoInstall;

use MotoInstall;

class InstallationFileList
{
    protected $_list = array();

    protected $_storageFile = '@installationTempDir/installation.list';
    protected static $_instance;

    protected function __construct()
    {
        $this->_storageFile = MotoInstall\System::getAbsolutePath($this->_storageFile);
    }
    public static function getInstance()
    {
        if (!static::$_instance) {
            static::$_instance = new static();
        }
        return static::$_instance;
    }
    public function loadFromStorage()
    {
        if (!file_exists($this->_storageFile)) {
            throw new MotoInstall\Exception('STORAGE_FILE_NOT_EXISTS');
        }
        $data = file_get_contents($this->_storageFile);
        $data = json_decode($data, true);
        if (!is_array($data) || !array_key_exists('content', $data) || !is_array($data['content'])) {
            throw new MotoInstall\Exception('STORAGE_FILE_IS_CORRUPTED');
        }
        $content = $data['content'];
        $this->setFromArray($content);

        return true;
    }
    public function deleteNotValidFiles()
    {
        foreach ($this->_list as $file) {
            if ($file->isFileExists() && !$file->isDownloadedFileValid()) {
                $absoluteFilePath = $file->getAbsolutePath();
                @unlink($absoluteFilePath);
                clearstatcache(true, $absoluteFilePath);
            }
        }
    }
    public function saveToStorage()
    {
        $data = array(
            'created_at' => time(),
            'content' => $this->toArray(true),
        );

        if (!Util::filePutContents($this->_storageFile, json_encode($data, JSON_PRETTY_PRINT))) {
            throw new MotoInstall\Exception('CANT_SAVE_STORAGE_FILE');
        }
    }
    public function setFromArray($list)
    {
        if (!is_array($list)) {
            return false;
        }
        $this->_list = array();
        foreach ($list as $file) {
            $this->addFile($file);
        }

        return true;
    }
    public function addFile($file)
    {
        if (is_array($file)) {
            $file = new InstallationFile($file);
        }
        if ($file instanceof InstallationFile) {
            $this->_list[] = $file;

            return true;
        }

        return false;
    }
    public function getFile($type, $uid)
    {
        switch ($type) {
            case 'engine':
                return $this->getEngine();
            case 'theme':
                return $this->getTheme($uid);
            case 'plugin':
                return $this->getPlugin($uid);
        }

        return null;
    }
    public function getEngine()
    {
        foreach ($this->_list as $file) {
            if ($file->isEngine()) {
                return $file;
            }
        }

        return null;
    }
    public function getThemes()
    {
        $result = array();
        foreach ($this->_list as $file) {
            if ($file->isTheme()) {
                $result[] = $file;
            }
        }

        return $result;
    }
    public function getTheme($uid)
    {
        $uid = trim((string) $uid);
        foreach ($this->_list as $file) {
            if ($file->isTheme() && $file->getUid() === $uid) {
                return $file;
            }
        }

        return null;
    }
    public function getPlugins()
    {
        $result = array();
        foreach ($this->_list as $file) {
            if ($file->isPlugin()) {
                $result[] = $file;
            }
        }

        return $result;
    }
    public function getPlugin($uid)
    {
        $uid = trim((string) $uid);
        foreach ($this->_list as $file) {
            if ($file->isPlugin() && $file->getUid() === $uid) {
                return $file;
            }
        }

        return null;
    }
    public function all()
    {
        return $this->_list;
    }
    public function toArray($full = false)
    {
        return Util::toArray($this->_list, $full);
    }
}
