<?php
namespace MotoInstall;

use MotoInstall;

class InstallationFile
{
    const TYPE__ENGINE = 'engine';
    const TYPE__THEME = 'theme';
    const TYPE__PLUGIN = 'plugin';
    protected $_data;

    protected $_path = '';

    protected $_archiveContentFolder = '';

    public function __construct($data)
    {
        $this->setFromArray($data);
    }
    public function setFromArray($data)
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Bad "data" - must be a array');
        }
        unset($data['_relativePath']);
        $this->_data = new DataBag($data);

        if ($this->_data->has('build')) {
            $this->_data->set('build', (int) $this->_data->get('build'));
        }

        $type = $this->getType();
        $uid = (string) $this->getUid();
        $this->_path = '@installationTempDir/install_' . $type;

        if ($type === 'engine') {
            if (empty($uid)) {
                $uid = 'core';
            }
            $this->_path .= '__' . $uid;
            $this->_archiveContentFolder = 'site';
        }

        if ($type === 'theme') {
            $this->_path .= '__' . $uid;
            $this->_archiveContentFolder = 'site';
        }

        if ($type === 'plugin') {
            $this->_path .= '__' . $uid;
        }

        $this->_data->set('uid', $uid);

        $this->_path .= '.zip';
    }
    public function getType()
    {
        return $this->_data->get('type', 'unknown');
    }
    public function getUid()
    {
        return $this->_data->get('uid');
    }
    public function getUrl()
    {
        return $this->_data->get('url');
    }
    public function getFileInfo()
    {
        return $this->_data->get('file');
    }
    public function getPath()
    {
        return $this->_path;
    }
    public function getAbsolutePath()
    {
        return MotoInstall\System::getAbsolutePath($this->_path);
    }
    public function getRelativePath()
    {
        return MotoInstall\System::getRelativePath($this->_path);
    }
    public function isFileExists()
    {
        return file_exists($this->getAbsolutePath());
    }
    public function isFileDownloaded()
    {
        if (!$this->isFileExists()) {
            return false;
        }

        return ($this->getRemainingDownloadSize() === 0);
    }
    public function getDownloadedFileStats()
    {
        $path = $this->getAbsolutePath();
        clearstatcache(true, $path);
        $exists = file_exists($path);
        $result = array(
            'exists' => $exists,
            'size' => null,
            'md5' => null,
            'sha1' => null,
        );
        if ($exists) {
            $result['size'] = filesize($path);
            $result['md5'] = md5_file($path);
            if ($this->_data->has('file.sha1')) {
                $result['sha1'] = sha1_file($path);
            }
        }

        return $result;

    }
    public function getRemainingDownloadSize()
    {
        $result = (int) $this->_data->get('file.size');
        if ($this->isFileExists()) {
            $result -= filesize($this->getAbsolutePath());
        }

        return $result;
    }
    public function isFileSizeValid()
    {
        if (!$this->isFileExists()) {
            return false;
        }

        return (((int) $this->_data->get('file.size')) === filesize($this->getAbsolutePath()));
    }
    public function isFileHashValid()
    {
        if (!$this->isFileExists()) {
            return false;
        }

        $path = $this->getAbsolutePath();
        if ($this->_data->get('file.md5') !== md5_file($path)) {
            return false;
        }
        if ($this->_data->has('file.sha1') && $this->_data->get('file.sha1') !== sha1_file($path)) {
            return false;
        }

        return true;
    }
    public function isDownloadedFileValid()
    {
        if (!$this->isFileExists()) {
            return false;
        }

        $path = $this->getAbsolutePath();
        if (((int) $this->_data->get('file.size')) !== filesize($path)) {
            return false;
        }
        if ($this->_data->get('file.md5') !== md5_file($path)) {
            return false;
        }
        if ($this->_data->has('file.sha1') && $this->_data->get('file.sha1') !== sha1_file($path)) {
            return false;
        }

        return true;
    }
    public function isEngine()
    {
        return $this->_data->get('type') === static::TYPE__ENGINE;
    }
    public function isTheme()
    {
        return $this->_data->get('type') === static::TYPE__THEME;
    }
    public function isPlugin()
    {
        return $this->_data->get('type') === static::TYPE__PLUGIN;
    }
    protected function _getTempDestinationPath()
    {
        if ($this->isPlugin()) {
            $path = '@pluginsDir/' . str_replace('@', '/', $this->getUid());
            $relativePath = System::getRelativePath($path, '@installationFilesDir');
            $relativePath = '@installationFilesDir/' . $relativePath;

            return $relativePath;
        }

        return '@installationFilesDir';
    }
    public function extractToInstallationFolder()
    {
        $tempFolder = $this->getAbsolutePath() . '_temp';
        $this->_extractToTemp($tempFolder);

        $destinationPath = $this->_getTempDestinationPath();
        $this->_copyFromTempToAll($tempFolder . '/' . $this->_archiveContentFolder, $destinationPath);

        Util::deleteDir($tempFolder, true);

        return true;
    }
    protected function _extractToTemp($tempDir)
    {
        $file = $this;
        $archive = new \ZipArchive();
        $isOpened = $archive->open($file->getAbsolutePath());
        if ($isOpened !== true) {
            $errors = array(
                'resource' => $file,
                'zip_code' => $isOpened,
            );
            if (MotoInstall\System::isDebug()) {
                $errors['_full_info__'] = $file->toArray(true);
            }

            throw new MotoInstall\Exception('ARCHIVE_ERROR', 400, null, $errors);
        }

        MotoInstall\Util::deleteDir($tempDir);
        MotoInstall\Util::createDir($tempDir);
        $status = $archive->extractTo($tempDir);
        if (!$status) {
            $errors = array(
                'resource' => $file,
                'zip_status' => $archive->getStatusString(),
            );
            if (MotoInstall\System::isDebug()) {
                $errors['_full_info__'] = $file->toArray(true);
            }
            $archive->close();

            throw new MotoInstall\Exception('ARCHIVE_NOT_EXTRACTED', 400, null, $errors);
        }

        $archive->close();

        return true;
    }
    protected function _copyFromTempToAll($tempPath, $path)
    {
        $absoluteTempPath = System::getAbsolutePath($tempPath);
        $absolutePath = System::getAbsolutePath($path);

        MotoInstall\Util::createDir($absolutePath);
        MotoInstall\Util::copyDir($absoluteTempPath, $absolutePath);
    }
    public function toArray($full = false)
    {
        $absolutePath = $this->getAbsolutePath();
        clearstatcache(true, $absolutePath);
        $result = $this->_data->toArray();
        $result['file_exists'] = $this->isFileExists();
        $result['downloaded'] = $this->isFileDownloaded();
        $result['downloading'] = $result['file_exists'] && !$result['downloaded'];
        $result['download_size'] = $this->_data->get('file.size');
        if ($result['file_exists']) {
            $result['downloaded_size'] = filesize($absolutePath);
        } else {
            $result['downloaded_size'] = 0;
        }
        $result['local_file_valid'] = $this->isDownloadedFileValid();
        $result['downloaded_file_stats'] = $this->getDownloadedFileStats();
        $result['remaining_download'] = $this->getRemainingDownloadSize();

        if (MotoInstall\System::isDevelopmentStage()) {
            $result['_relativePath'] = MotoInstall\System::getRelativePath($this->_path);
        }

        if ($full) {
            return $result;
        }

        return Util::arrayOnly($result, array(
            'type',
            'uid',
            'label',
            'name',
            'version',
            'build',
            'file_exists',
            'downloaded',
            'downloading',
            'download_size',
            'downloaded_size',
            'remaining_download',
        ));
    }
}
