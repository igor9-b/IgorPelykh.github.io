<?php
namespace MotoInstall\Actions;

use MotoInstall;

class DownloadRequiredResourceAction extends MotoInstall\AbstractAction
{
    public function processRequest(MotoInstall\Api\Request $request)
    {
        $list = MotoInstall\InstallationFileList::getInstance();
        $list->loadFromStorage();

        $resource = $list->getFile($request->get('resource.type'), $request->get('resource.uid'));
        if (!$resource) {
            throw new MotoInstall\Exception('REQUIRED_RESOURCE_NOT_EXISTS', 404);
        }
        if ($resource->isDownloadedFileValid()) {
            $response = array(
                'resource' => $resource,
            );
            if (MotoInstall\System::isDebug()) {
                $response['_full_info__'] = $resource->toArray(true);
            }

            return $response;
        }

        $remainingDownload = $resource->getRemainingDownloadSize();
        if ($remainingDownload <= 0) {
            if ($remainingDownload < 0) {
                MotoInstall\System::logger()->warning('[Download] Downloaded file biggest that need');
            } else {
                MotoInstall\System::logger()->warning('[Download] Hash of downloaded file invalid');
            }
            MotoInstall\System::logger()->debug('[Download] Dump ', array(
                'resource' => $resource->toArray(),
            ));
            MotoInstall\System::logger()->warning('[Download] Trying to download again');
            @unlink($resource->getAbsolutePath());
        }
        clearstatcache(true, $resource->getAbsolutePath());
        $httpClient = new MotoInstall\HttpClient();
        $result = $httpClient->download($resource->getUrl(), $resource->getPath());

        if (!$result->get('status')) {
            MotoInstall\System::logger()->warning('[Download] Downloading failed with errors, HTTP code is "' . $result->get('info.http_code') . '"');
            $errors = array(
                'http_url' => $result->get('info.url'),
                'http_code' => $result->get('info.http_code'),
                'http_error' => $result->get('error'),
                'resource' => $resource,
            );
            MotoInstall\System::logger()->debug('[Download] Errors ', $errors);
            throw new MotoInstall\Exception('DOWNLOADING_FAILED', 400, null, $errors);
        }

        clearstatcache(true, $resource->getAbsolutePath());

        $response = array(
            'resource' => $resource,
        );
        if (MotoInstall\System::isDebug()) {
            $response['_full_info__'] = $resource->toArray(true);
        }

        if ($resource->isDownloadedFileValid()) {
            MotoInstall\System::logger()->debug('[Download] File downloaded and valid');

            return $response;
        }

        $remainingDownload = $resource->getRemainingDownloadSize();
        if ($remainingDownload > 0) {
            MotoInstall\System::logger()->debug('[Download] Need download more "' . $resource->getRemainingDownloadSize() . '" bytes');

            return $response;
        }

        if ($remainingDownload < 0) {
            MotoInstall\System::logger()->warning('[Download] Downloaded file biggest that need');
        } else {
            MotoInstall\System::logger()->warning('[Download] Hash of downloaded file invalid');
        }
        $errors = array(
            'resource' => $resource,
            'requirements' => $resource->getFileInfo(),
            'current' => $resource->getDownloadedFileStats(),
        );
        MotoInstall\System::logger()->debug('[Download] Errors ', $errors);
        if (MotoInstall\System::isDebug()) {
            $errors['_full_info__'] = $resource->toArray(true);
        }

        throw new MotoInstall\Exception('INVALID_DOWNLOADED_FILE', 400, null, $errors);
    }
}
