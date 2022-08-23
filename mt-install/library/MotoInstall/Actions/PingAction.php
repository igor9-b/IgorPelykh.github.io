<?php
namespace MotoInstall\Actions;

use MotoInstall;

class PingAction extends MotoInstall\AbstractAction
{
    public function processRequest(MotoInstall\Api\Request $request)
    {
        return [
            'time' => time(),
            'microtime' => microtime(1),
            'rand' => mt_rand(10000000, 99999999),
            'request' => array(
                'query' => $request->getQuery()->toArray(),
                'headers' => $request->getHeaders()->toArray(),
                'browser' => $request->getServerValue('HTTP_USER_AGENT'),
                'ip' => $request->getServerValue('REMOTE_ADDR'),
            ),
        ];
    }
    public function checkRequest(MotoInstall\Api\Request $request)
    {
        return true;
    }
}
