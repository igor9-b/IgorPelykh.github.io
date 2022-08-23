<?php
namespace MotoInstall\Actions;

use MotoInstall;

class ExtractInstallationFileAction extends MotoInstall\AbstractAction
{
    public function processRequest(MotoInstall\Api\Request $request)
    {
        $list = MotoInstall\InstallationFileList::getInstance();
        $list->loadFromStorage();

        $resource = $list->getFile($request->get('resource.type'), $request->get('resource.uid'));
        if (!$resource) {
            throw new MotoInstall\Exception('REQUIRED_RESOURCE_NOT_EXISTS', 404);
        }

        if (!$resource->isDownloadedFileValid()) {
            $errors = array(
                'resource' => $resource,
                'requirements' => $resource->getFileInfo(),
                'current' => $resource->getDownloadedFileStats(),
            );
            if (MotoInstall\System::isDebug()) {
                $errors['_full_info__'] = $resource->toArray(true);
            }

            throw new MotoInstall\Exception('INVALID_DOWNLOADED_FILE', 400, null, $errors);
        }

        $resource->extractToInstallationFolder();

        return [
            'resource' => $resource,
        ];
    }
}
