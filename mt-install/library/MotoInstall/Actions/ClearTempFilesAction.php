<?php
namespace MotoInstall\Actions;

use MotoInstall;

class ClearTempFilesAction extends MotoInstall\AbstractAction
{
    public function processRequest(MotoInstall\Api\Request $request)
    {
        MotoInstall\Util::deleteDir(MotoInstall\System::getAbsolutePath('@installationFilesDir'), true);
        $list = MotoInstall\InstallationFileList::getInstance();
        $list->loadFromStorage();
        foreach($list->all() as $file) {
            @unlink($file->getAbsolutePath());
        }

        return [
            'done' => true,
        ];
    }
    public function checkRequest(MotoInstall\Api\Request $request)
    {
        return true;
    }

}
