<?php
namespace MotoInstall\Actions;

use MotoInstall;

class CheckNetworkConnectionAction extends MotoInstall\AbstractAction
{
    const CHECKER_NETWORK_CONNECTION = 'network_connection';
    public function processRequest(MotoInstall\Api\Request $request)
    {
        $resourceName = $request->get('resource');
        if (empty($resourceName)) {
            throw new MotoInstall\Exception('RESOURCE_IS_EMPTY');
        }

        $resource = MotoInstall\System::config('networkRequirements.resources.' . $resourceName);
        if (empty($resource)) {
            throw new MotoInstall\Exception('RESOURCE_NOT_FOUND', 404);
        }

        $httpClient = new MotoInstall\HttpClient();
        $data = $httpClient->get($resource['url']);

        $requirements = array('url' => $resource['url'], 'resource' => $resourceName);

        if ($data->get('status')) {
            if (MotoInstall\System::isDebug()) {
                $requirements['_http_response_'] = $data->toArray();
            }

            return $this->returnPassed(self::CHECKER_NETWORK_CONNECTION, $requirements);
        }

        $current = array(
            'http_code' => $data->get('info.http_code'),
            'http_error' => $data->get('error'),
        );

        if (MotoInstall\System::isDebug()) {
            $current['_http_response_'] = $data->toArray();
        }

        return $this->returnFailed(self::CHECKER_NETWORK_CONNECTION, $requirements, $current);
    }
    protected function returnFailed($checker, $requirements = null, $current = null)
    {
        $result = array(
            'checker' => $checker,
            'status' => false,
        );

        if ($requirements !== null) {
            $result['requirements'] = $requirements;
        }

        if ($current !== null) {
            $result['current'] = $current;
        }

        return $result;
    }
    protected function returnPassed($checker, $current = null)
    {
        $result = array(
            'checker' => $checker,
            'status' => true,
        );

        if ($current !== null) {
            $result['current'] = $current;
        }

        return $result;
    }
    public function checkRequest(MotoInstall\Api\Request $request)
    {
        return true;
    }
}
