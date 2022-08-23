<?php
namespace MotoInstall;

use MotoInstall;

class HttpClient
{
    protected static $_defaultOptions = array(
        'curl' => array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 6,
            CURLOPT_USERAGENT => 'MotoInstaller/1.0.0',
            CURLOPT_PROXY => '',
        ),
    );
    protected $_headers = array(
        'Accept' => '',
    );
    protected static $_curlErrors = array(
        1 => 'CURLE_UNSUPPORTED_PROTOCOL',
        2 => 'CURLE_FAILED_INIT',
        3 => 'CURLE_URL_MALFORMAT',
        4 => 'CURLE_URL_MALFORMAT_USER',
        5 => 'CURLE_COULDNT_RESOLVE_PROXY',
        6 => 'CURLE_COULDNT_RESOLVE_HOST',
        7 => 'CURLE_COULDNT_CONNECT',
        22 => 'CURLE_HTTP_NOT_FOUND',
        23 => 'CURLE_WRITE_ERROR',
        24 => 'CURLE_MALFORMAT_USER',
        26 => 'CURLE_READ_ERROR',
        27 => 'CURLE_OUT_OF_MEMORY',
        28 => 'CURLE_OPERATION_TIMEOUTED',
        33 => 'CURLE_HTTP_RANGE_ERROR',
        34 => 'CURLE_HTTP_POST_ERROR',
        35 => 'CURLE_SSL_CONNECT_ERROR',
        37 => 'CURLE_FILE_COULDNT_READ_FILE',
        40 => 'CURLE_LIBRARY_NOT_FOUND',
        41 => 'CURLE_FUNCTION_NOT_FOUND',
        42 => 'CURLE_ABORTED_BY_CALLBACK',
        43 => 'CURLE_BAD_FUNCTION_ARGUMENT',
        44 => 'CURLE_BAD_CALLING_ORDER',
        45 => 'CURLE_HTTP_PORT_FAILED',
        46 => 'CURLE_BAD_PASSWORD_ENTERED',
        47 => 'CURLE_TOO_MANY_REDIRECTS',
        48 => 'CURLE_UNKNOWN_TELNET_OPTION',
        49 => 'CURLE_TELNET_OPTION_SYNTAX',
        50 => 'CURLE_OBSOLETE',
        51 => 'CURLE_SSL_PEER_CERTIFICATE',
        52 => 'CURLE_GOT_NOTHING',
        53 => 'CURLE_SSL_ENGINE_NOTFOUND',
        54 => 'CURLE_SSL_ENGINE_SETFAILED',
        55 => 'CURLE_SEND_ERROR',
        56 => 'CURLE_RECV_ERROR',
        57 => 'CURLE_SHARE_IN_USE',
        58 => 'CURLE_SSL_CERTPROBLEM',
        59 => 'CURLE_SSL_CIPHER',
        60 => 'CURLE_SSL_CACERT',
        61 => 'CURLE_BAD_CONTENT_ENCODING',
        63 => 'CURLE_FILESIZE_EXCEEDED',
    );
    public static function createCURLClient($options = array())
    {
        $ch = curl_init();
        curl_setopt_array($ch, static::$_defaultOptions['curl']);

        if (is_array($options)) {
            curl_setopt_array($ch, $options);
        }

        $settings = MotoInstall\System::config('httpClient.curl_options');
        if (is_array($settings)) {
            curl_setopt_array($ch, $settings);
        }

        $settings = MotoInstall\System::config('httpClient.settings');
        if (is_array($settings)) {
            if (array_key_exists('connectionTimeout', $settings)) {
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int) $settings['connectionTimeout']);
            }
            if (array_key_exists('executionTimeout', $settings)) {
                curl_setopt($ch, CURLOPT_TIMEOUT, (int) $settings['executionTimeout']);
            }
        }

        return $ch;
    }
    public function addHeader($name, $value)
    {
        $this->_headers[$name] = $value;

        return $this;
    }
    protected function _fillHeaders($ch)
    {
        if (is_array($this->_headers)) {
            $curlHeaders = array();
            foreach ($this->_headers as $name => $value) {
                $curlHeaders[] = $name . ':' . $value;
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        }
    }
    protected function _createHttpClient()
    {
        $ch = static::createCURLClient();
        $this->_fillHeaders($ch);

        return $ch;
    }
    public function get($url)
    {
        if (!is_string($url)) {
            throw new \InvalidArgumentException('Bad "url" - must be a string');
        }

        $ch = $this->_createHttpClient();

        curl_setopt($ch, CURLOPT_URL, $url);

        return $this->_execRequest($ch);
    }
    public function download($url, $path, $params = array())
    {
        $uid = microtime(1) . '_' . mt_rand(10000, 90000);
        $logUid = 'HttpClient.download';

        $logger = System::logger();
        if (!is_string($url)) {
            throw new \InvalidArgumentException('Bad "url" - must be a string');
        }
        if (!is_string($path)) {
            throw new \InvalidArgumentException('Bad "path" - must be a string');
        }
        if (is_array($params)) {
            $params = new DataBag($params);
        }
        if (!($params instanceof DataBag)) {
            throw new \InvalidArgumentException('Bad "params" - must be a array or DataBag');
        }

        $absoluteFilePath = System::getAbsolutePath($path);
        $relativeFilePath = System::getRelativePath($path);

        clearstatcache(true, $absoluteFilePath);
        if (file_exists($absoluteFilePath) && !is_writable($absoluteFilePath)) {
            throw new \InvalidArgumentException('File "' . $relativeFilePath . '" not writable');
        }

        $absoluteDirPath = dirname($absoluteFilePath);
        $relativeDirPath = dirname($relativeFilePath);
        clearstatcache(true, $absoluteDirPath);
        if (file_exists($absoluteDirPath)) {
            if (!is_dir($absoluteDirPath)) {
                throw new \InvalidArgumentException('Directory "' . $relativeDirPath . '"is not "Folder"');
            }
        } else {
            Util::createDir($absoluteDirPath);
        }
        if (!is_writable($absoluteDirPath)) {
            throw new \InvalidArgumentException('Directory "' . $relativeDirPath . '"is not writable');
        }

        $postfix = '.' . $uid . '.tmp';
        $absoluteTempFilePath = $absoluteFilePath . $postfix;
        $relativeTempFilePath = $relativeFilePath . $postfix;
        $tempStream = fopen($absoluteTempFilePath, 'w+b');
        $logger->debug($logUid . ' : Try to open stream to file [ ' . $relativeTempFilePath . ' ]');

        if (!Tester::isResource($tempStream, 'HttpClientTempFile')) {
            throw new \RuntimeException('Could create temp file');
        }

        $ch = $this->_createHttpClient();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_FILE, $tempStream);

        $logger->debug($logUid . ' : Start download url [ ' . $url . ' ]');

        $range = null;
        clearstatcache(true, $absoluteFilePath);
        if (file_exists($absoluteFilePath)) {
            $range = filesize($absoluteFilePath) . '-';
        }

        if (is_string($range)) {
            $logger->debug($logUid . ' : Set HTTP range [ ' . $range . ' ]');
            curl_setopt($ch, CURLOPT_RANGE, $range);
        }

        $result = $this->_execRequest($ch);
        $httpCode = $result->get('info.http_code');
        $logger->debug($logUid . ' : Request complete with http code [ ' . $httpCode . ' ]');
        if (!$result->get('status')) {
            if ($httpCode >= 200 && $httpCode < 300) {
                if (in_array($result->get('error.number'), array(CURLE_OPERATION_TIMEOUTED, CURLE_ABORTED_BY_CALLBACK))) {
                    $result->set('status', true);
                }
            }
        }
        fclose($tempStream);
        usleep(50);

        if (!$result->get('status')) {
            @unlink($absoluteTempFilePath);
            $logger->warning($logUid . ' : Request was finished with error', $result->get('error'));

            return $result;
        }

        $body = file_get_contents($absoluteTempFilePath);
        if (!$body) {
            @unlink($absoluteTempFilePath);
            $logger->error($logUid . ' : Cant get downloaded content');
            throw new \RuntimeException('Cant get downloaded content');
        }

        $logger->debug($logUid . ' : Downloaded content size [ ' . strlen($body) . ' ]');
        $logger->debug($logUid . ' : Try to open stream [ ' . $relativeFilePath . ' ]');
        clearstatcache(true, $absoluteFilePath);
        if (file_exists($absoluteFilePath)) {
            $destination = fopen($absoluteFilePath, 'r+b');
        } else {
            $destination = fopen($absoluteFilePath, 'w+b');
        }
        if (!Tester::isResource($destination, 'HttpClientDestinationFile')) {
            $logger->debug($logUid . ' : Open stream failed [ ' . $relativeFilePath . ' ]');
            throw new \RuntimeException('Could open destination file');
        }

        $contentRange = $result->get('headers.Content-Range');
        if (preg_match('/bytes[\s]*(\d+)[\s]*-[\s]*(\d+)[\s]*\/?[\s]*(\d*)/i', $contentRange, $match)) {
            $position = $match[1];
            $logger->debug($logUid . ' : Set file position [ ' . $position . ' ]');
            $seek = fseek($destination, $position);
            $logger->debug($logUid . ' : fseek() => [ ' . var_export($seek, true) . ' ]');
        }

        fwrite($destination, $body);
        fclose($destination);
        $logger->debug($logUid . ' : Remove temp file [ ' . $relativeTempFilePath . ' ]');
        @unlink($absoluteTempFilePath);
        $result->set('body', null);

        return $result;
    }
    protected function _execRequest($ch)
    {
        $headerData = (object) array(
            'content' => '',
        );

        $headerHandler = function ($ch, $header) use ($headerData) {
            $headerData->content .= $header;

            return strlen($header);
        };

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, $headerHandler);

        $content = curl_exec($ch);
        $info = curl_getinfo($ch);
        $info = Tester::sanitizeCurlInfo($info);
        $errorNumber = curl_errno($ch);
        $errorNumber = Tester::sanitizeCurlErrorNumber($errorNumber);
        $errorMessage = curl_error($ch);
        $errorMessage = Tester::sanitizeCurlErrorMessage($errorMessage, $errorNumber);

        $body = null;
        $header = static::getLastHeader($headerData->content);
        $headers = static::convertHttpHeadToArray($header);
        if ($content !== false) {
            $body = $content;
        }

        $response = new MotoInstall\DataBag(array(
            'url' => Util::getValue($info, 'url'),
            'status' => (int) Util::getValue($info, 'http_code') >= 200 && (int) Util::getValue($info, 'http_code') < 300,
            'info' => $info,
            'header' => $header,
            'headers' => $headers,
            'body' => $body,
        ));
        if ($errorNumber > 0) {
            $response->set('status', false);
            $response->set('error', array(
                'number' => $errorNumber,
                'name' => Util::getValue(static::$_curlErrors, $errorNumber),
                'message' => $errorMessage,
            ));
        }

        return $response;
    }
    public static function getLastHeader($header)
    {
        if (!is_string($header)) {
            throw new \InvalidArgumentException('Bad "header" - must be a string');
        }

        do {
            $repeat = false;
            $parts = preg_split('/(?:\r?\n){2}/m', $header, 2);
            if (count($parts) > 1 && preg_match("/^HTTP\/1\.[01](.*?)\r\n/mi", $parts[1])) {
                $repeat = true;
                $header = $parts[1];
            }
        } while ($repeat);

        return $header;
    }
    public static function convertHttpHeadToArray($header, $exceptHeaders = array())
    {
        if (is_array($header)) {
            return $header;
        }
        if (!is_string($header)) {
            return null;
        }

        $exceptHeaders = (array) $exceptHeaders;
        $headers = array();
        $parts = explode("\n", $header);
        foreach ($parts as $str) {
            $str = trim($str);
            if (empty($str)) {
                continue;
            }
            if (preg_match('/^([^:]+):(.*)$/', $str, $match)) {
                $name = $match[1];
                $value = trim($match[2]);
                if (!in_array($name, $exceptHeaders)) {
                    $headers[$name] = $value;
                }
            }
        }

        return $headers;
    }

}
