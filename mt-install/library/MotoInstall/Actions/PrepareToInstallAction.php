<?php
namespace MotoInstall\Actions;

use MotoInstall;

class PrepareToInstallAction extends MotoInstall\AbstractAction
{
    public function processRequest(MotoInstall\Api\Request $request)
    {
        $productInfo = $request->get('productInfo');
        $this->_updateProductInformation($productInfo);
        $files = $request->get('files');
        $list = MotoInstall\InstallationFileList::getInstance();
        $list->setFromArray($files);
        $list->deleteNotValidFiles();
        $list->saveToStorage();

        MotoInstall\Util::deleteDir(MotoInstall\System::getAbsolutePath('@installationFilesDir'));

        return [
            'resources' => $list,
        ];
    }

    protected function _updateProductInformation($info)
    {
        $info = (array) $info;
        if (empty($info)) {
            return false;
        }
        $info = json_encode($info);
        $info = json_decode($info, true);
        if (empty($info['product_id'])) {
            return false;
        }
        if (empty($info['template'])) {
            return false;
        }
        if (empty($info['token'])) {
            return false;
        }
        if (empty($info['request_sign'])) {
            return false;
        }

        $filePath = MotoInstall\System::getAbsolutePath('@productInformationFile');
        $content = '<?php return ' . var_export($info, true) . ';';

        return MotoInstall\Util::filePutContents($filePath, $content);
    }
}
