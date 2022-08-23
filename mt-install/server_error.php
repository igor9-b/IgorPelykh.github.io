<?php
$showError = false;
$min = '7.0.0';
$max = '7.5.0';
if (version_compare(PHP_VERSION, $min, '<') || version_compare(PHP_VERSION, $max, '>=')) {
    $exception = new \Exception('PHP_VERSION_NOT_COMPATIBILITY');
}
if (isset($exception)) {
    $showError = true;
}
if (!$showError) {
    return true;
}

$errorMessage = '';
switch($exception->getMessage()) {
    case 'PHP_VERSION_NOT_COMPATIBILITY':
        $errorMessage = 'Your PHP version is outdated. Our template\'s requirements are:<br/><strong>PHP version 7.0, 7.1, 7.2, 7.3 or 7.4.</strong><br/><br/> Please contact your hosting provider and kindly ask them to switch your PHP version up to the recommended version.';
        break;
    case 'SET_INCLUDE_PATH_FAILED':
        $errorMessage = '<strong>Include path</strong> function doesn\'t work. Please contact your hosting provider for additional help.';
        break;
    case 'TEMP_FOLDER_NOT_WRITABLE':
        $errorMessage = 'Folder <strong>mt-install/temp</strong> is not writable. Please set correct permissions for the following folder.';
        break;
    case 'EXTENSION_NOT_LOADED':
        $errors = $exception->getErrors();
        $errorMessage = 'PHP extensions <strong>';
        if (array_key_exists('extensions', $errors)) {
            $errorMessage .= implode(',', (array) $errors['extensions']);
        } else {
            $errorMessage .= print_r($errors, true);
        }
        $errorMessage .= '</strong> are not available. Please verify your server configuration. Make sure that extensions are enabled.';
        break;
    default:
        $errorMessage = $exception->getMessage();
        break;
}

$now = time();
?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Installation Error</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="shortcut icon" href="./images/favicon.ico?t=<?php echo $now?>" type="image/x-icon" />
        <link href='//fonts.googleapis.com/css?family=Open+Sans:400,600,700&subset=latin,cyrillic' rel='stylesheet' type='text/css'>
        <link rel="stylesheet" href="./css/style.min.css?t=<?php echo $now?>">
        <style type="text/css">
            body {
                min-height: 100vh;
            }

            .version-php-app .popup {
                position: absolute;
                top: 50%;
                left: 50%;
                -webkit-transform: translate(-50%, -50%);
                transform: translate(-50%, -50%);
                text-align: center;
                color: #fff;
                width: 312px;
                height: auto;
                background-color: #c12525;
                border: 1px #aa1313 solid;
                padding: 25px 40px;
                box-sizing: border-box;
                box-shadow: inset 0 0 0 1px #cb3e3e;
            }

            .version-php-app .icon {
                height: 35px;
                background-image: url('data:image/png;base64, iVBORw0KGgoAAAANSUhEUgAAACQAAAAjCAYAAAD8BaggAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA61pVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMC1jMDYwIDYxLjEzNDc3NywgMjAxMC8wMi8xMi0xNzozMjowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wUmlnaHRzPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvcmlnaHRzLyIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcFJpZ2h0czpNYXJrZWQ9IkZhbHNlIiB4bXBNTTpPcmlnaW5hbERvY3VtZW50SUQ9InhtcC5kaWQ6MEU5MjhFM0IzNURGRTMxMThDODdDRUZGNDk2MkI3NTQiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6NjY5QzBBNkY1MzdCMTFFNEJFNkU4QzgzQjAwRUVFRDMiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6NjY5QzBBNkU1MzdCMTFFNEJFNkU4QzgzQjAwRUVFRDMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTMyBXaW5kb3dzIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InV1aWQ6MTQ5QUE4MEVCQjUyRTQxMUE0MERFMzhBN0FDMzkxNkUiIHN0UmVmOmRvY3VtZW50SUQ9InV1aWQ6RTMxNUQ3QTU4QzUwRTQxMThERjBEQ0E5OEEyOEUxNjUiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz4Xk5QxAAACnklEQVR42tSYy2sUQRCHdyYPMySuQlgJBhaNbyEY8HVQEIXoSbwIAUUQIkJEPHjyFBAV0ZMoiiD4DxjQeBE8iLkIAT3oSY1BDQQDGhU0hGjM+NVSC+2ws9OzuzNZCz7mQXX3b/pRXdOO7/uZGNYEHnRBD2yELDTAb/gOb+AljOm7+TgNNFr6eSqgD/bCBlhSxn8GXsATeACvYc6qJemhMjRBN9yFOb8y+wJXYBU0RLRXVlAWBmDar42NwxForURQJ9z0k7FLsCyOIBEz5Cdr13QEIgUthdt+8vYHLkJLlKAzfno2AyfKCdoG3/x07S1sMgW5uvpbYRCWZ9K1dXAWmosvRJCjQe9gROFJ2K8B8Sj8rNKvaAdguxkYJVjdsujevsB4X67Sz5zgF8wha4dDFt37PuI5rp85SjtVhyMPW2ClhaB+414202NV+pm2XnW4srl2W07Ak7Aa3sEO2Fqln2k5yBcFrYmxKnqVWvmZ2UQHtLl6s9jm6BwqCPIy9WESCz0RNFsngiSpmxVBU3UgRvLoaQmiMqnHYxSU3hxRvmqMkesvDf+dOiflukexrXeqENWJjr0WUfoRHIbmqBQ0gCRi/TAaUf8H2Ce7hhRaAZMhjlLR7pgiwpAPGgtp5zG0g9OoYzcMA0YXSvedgvshXZzX4ZAVulmvn2FCh3BEn00b0vqknavG6l6AUdVR2Fwd2GWofQ75El+4Fs7DK8tc5ymcg1yJunrgo/pNaPv/JGjyJ/AQ7oEXKJzTbGC+wiTsBwxCW6DeDngGd8y5aTp0lfiS41phLeyTLiCz/pbgaIRNQMmRbiSQskovn477X+bCcMK59PUwQW6JFZTVQ4IkbSH0bOB/+HOty3/7RTv9cCwPrFI7H7IVlNoJ2l8BBgBhH2dK1FZ3eAAAAABJRU5ErkJggg==');
                background-position: top center;
                background-repeat: no-repeat;
            }

            .version-php-app h1 {
                font-size: 24px;
                font-weight: normal;
                padding: 11px 0;
            }

            .version-php-app .error-text {
                font-size: 13px;
                line-height: 17px;
            }
        </style>
    </head>
    <body class="guest-app version-php-app">

    <div class="popup">
        <div class="icon"></div>
        <h1>Server error</h1>
        <div class="error-text">
            <?php echo $errorMessage?>
        </div>
    </div>
    </body>
    </html>
<?php
exit();
