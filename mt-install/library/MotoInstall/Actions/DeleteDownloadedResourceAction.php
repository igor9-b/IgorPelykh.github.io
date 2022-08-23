<?php
namespace MotoInstall\Actions;

use MotoInstall;

class DeleteDownloadedResourceAction extends MotoInstall\AbstractAction
{
    public function processRequest(MotoInstall\Api\Request $request)
    {
        $list = MotoInstall\InstallationFileList::getInstance();
        $list->loadFromStorage();

        $resource = $list->getFile($request->get('resource.type'), $request->get('resource.uid'));
        if (!$resource) {
            throw new MotoInstall\Exception('REQUIRED_RESOURCE_NOT_EXISTS', 404);
        }
        if (!$resource->isFileExists()) {
            throw new MotoInstall\Exception('DOWNLOADED_FILE_NOT_EXISTS', 404);
        }

        @unlink($resource->getAbsolutePath());

        $response =  array(
            'resource' => $resource,
        );
        if (MotoInstall\System::isDebug()) {
            $response['_full_info__'] = $resource->toArray(true);
        }

        return $response;
    }
}
