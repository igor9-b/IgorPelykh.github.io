<?php

return array(
    'debugLevel' => 'warning',

    'paths' => array(
        'pluginsDir' => '@website/mt-content/plugins',
        'productInformationFile' => '@website/mt-content/storage/website/product_information.php',
        'productSettingsFile' => '@website/mt-includes/config/settings.php',
    ),

    'serverRequirements' => array(
        'php_version' => array(
            '700' => array(
                'min' => '7.0.0',
                'recommended' => '7.0.0',
            ),
            '701' => array(
                'min' => '7.1.0',
                'recommended' => '7.1.0',
            ),
            '702' => array(
                'min' => '7.2.03',
                'recommended' => '7.2.10',
            ),
            '703' => array(
                'min' => '7.3.02',
                'recommended' => '7.3.08',
            ),
            '704' => array(
                'min' => '7.4.0',
                'recommended' => '7.4.0',
            ),
        ),
        'php_extensions' => array(
            'curl',
            'spl',
            'openssl',
            'pdo_mysql',
            'mbstring',
            'iconv',
            'tokenizer',
            'zip',
        ),
        'php_classes' => array(
            'ZipArchive',
        ),
        'php_functions' => array(
            'json_encode',
            'json_decode',
            'session_start',
            'session_name',
            'session_status',
            'xml_parser_create',
            'iconv_mime_decode',
            'file_get_contents',
            'file_put_contents',
            'clearstatcache',
            'chmod',
            'curl_init',
            'curl_setopt',
            'curl_setopt_array',
            'curl_exec',
            'curl_getinfo',
            'curl_errno',
            'curl_error',
            'openssl_cipher_iv_length',
            'openssl_encrypt',
            'openssl_decrypt',
            'fopen',
            'fclose',
            'get_resource_type',
            'stream_get_meta_data',
        ),
        'php_settings' => array(
            'mbstring_func_overload',
            'network_disk',
        ),
        'disk_resources' => array(
            array(
                'type' => 'dir',
                'path' => '@website/mt-admin',
            ),
            array(
                'type' => 'dir',
                'path' => '@website/mt-content',
            ),
            array(
                'type' => 'dir',
                'path' => '@website/mt-content/temp/update',
            ),
            array(
                'type' => 'dir',
                'path' => '@website/mt-content/storage/website',
            ),
            array(
                'type' => 'dir',
                'path' => '@website/mt-content/storage/website/widgets',
            ),
            array(
                'type' => 'dir',
                'path' => '@website/mt-content/storage/sitemaps',
            ),
            array(
                'type' => 'dir',
                'path' => '@website/mt-content/storage/plugins',
            ),
            array(
                'type' => 'dir',
                'path' => '@website/mt-content/plugins',
            ),
            array(
                'type' => 'dir',
                'path' => '@website/mt-content/themes',
            ),
            array(
                'type' => 'dir',
                'path' => '@website/mt-content/uploads',
            ),
            array(
                'type' => 'dir',
                'path' => '@website/mt-content/temp/twig',
            ),
            array(
                'type' => 'dir',
                'path' => '@website/mt-includes',
            ),
            array(
                'type' => 'file',
                'path' => '@website/.htaccess',
            ),
            array(
                'type' => 'file',
                'path' => '@website/api.php',
            ),
            array(
                'type' => 'file',
                'path' => '@website/app.php',
            ),
            array(
                'type' => 'file',
                'path' => '@website/common.php',
            ),
            array(
                'type' => 'file',
                'path' => '@website/index.php',
            ),
            array(
                'type' => 'dir',
                'path' => '@installationTempDir',
            ),
        ),
    ),

    'networkRequirements' => array(
        'resources' => array(
            'accountPanel' => array(
                'url' => 'http://accounts.cms-guide.com/panel/',
            ),
            'installChecker' => array(
                'url' => 'https://accounts.motocms.com/install/product/',
            ),
            'updateStorage' => array(
                'url' => 'http://updates.motocms.com/moto3/latest/release.json',
            ),
        ),
    ),
    'installationRequirements' => array(
        'minFiles' => 100,
        'files' => array(
            'index.php',
            'mt-admin/index.php',
            'mt-includes/config/base.php',
            'mt-includes/config/settings.php',
        ),
        'dirs' => array(
            'mt-admin',
            'mt-content/themes',
            'mt-content/uploads',
            'mt-includes/library',
        ),
    ),

    'product' => array(
        'name' => 'moto3',
        'label' => 'MotoCMS',
        'controlPanelPath' => '@website/mt-admin/',
    ),

    'application' => array(
        'title' => 'Installation',
    ),

    'externalModules' => array(
        'licenseCenter' => array(
            'appId' => 'fe48426ca765e0b28980a7ae9bcdd415',
            'url' => 'https://accounts.motocms.com/install/licence-key',
        ),
    ),

    'httpClient' => array(
        'settings' => array(
            'connectionTimeout' => 5,
            'executionTimeout' => 15,
        ),
    ),
);
