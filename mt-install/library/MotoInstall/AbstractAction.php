<?php
namespace MotoInstall;

use MotoInstall;

class AbstractAction
{
    protected $_app;

    public function __construct($app)
    {
        $this->_app = $app;
    }
    public function processRequest(MotoInstall\Api\Request $request)
    {
        return array();
    }
    public function checkRequest(MotoInstall\Api\Request $request)
    {
        if (MotoInstall\System::isProductAlreadyDownloaded()) {
            throw new MotoInstall\Exception('PRODUCT_IS_ALREADY_DOWNLOADED');
        }
    }
}
