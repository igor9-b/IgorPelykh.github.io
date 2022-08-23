<?php
require_once __DIR__ . '/common.php';
$now = time();
?>
<!DOCTYPE html>
<html ng-app="application">
<head>
    <meta charset="utf-8">
    <title ng-bind-template="{{ 'APPLICATION.PAGE_TITLE' | translate }}"><?php echo MotoInstall\System::config('application.title')?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="./images/favicon.ico?t=<?php echo $now ?>" type="image/x-icon">
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Source+Sans+Pro:ital,wght@0,200;0,300;0,400;0,600;0,700;0,900;1,200;1,300;1,400;1,600;1,700;1,900&display=swap">
    <link rel="stylesheet" href="./css/style.min.css?t=<?php echo $now ?>">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="./fonts/MotoPreInstallIcons.woff2?t=<?php echo $now ?>">
    <style id="MotoPreInstallIconsFontFace">
        @font-face {
            font-family: MotoPreInstallIcons;
            src: url(./fonts/MotoPreInstallIcons.eot?t=<?php echo $now ?>);
            src: url(./fonts/MotoPreInstallIcons.eot?t=<?php echo $now ?>) format('embedded-opentype'),
                url(./fonts/MotoPreInstallIcons.woff2?t=<?php echo $now ?>) format('woff2'),
                url(./fonts/MotoPreInstallIcons.woff?t=<?php echo $now ?>) format('woff'),
                url(./fonts/MotoPreInstallIcons.ttf?t=<?php echo $now ?>) format('truetype');
            font-weight: normal;
            font-style: normal;
        }
    </style>
</head>
<body class="dark-ui">
    <div id="content-wrapper" class="content-wrapper view-animate" ng-cloak>
        <ui-view class="content"></ui-view>
        <div ng-if="error.visible" class="errors-block-wrapper">
            <div class="errors-block">
                <div class="errors-block-icon"></div>
                <div class="errors-block-message">
                    <span ng-if="error.message">{{ ::error.message | translate }} </span>
                    <span translate>{{ ::'APPLICATION.ERROR_MESSAGE.CONTACT_SUPPORT_TEAM' }}</span>
                </div>
                <div ng-if="error.isRetriable" class="errors-block-buttons">
                    <button class="btn btn-primary" ng-click="error.retryFn()">{{ ::'APPLICATION.BUTTON.RETRY' | translate }}</button>
                </div>
            </div>
            <div class="errors-block-overlay"></div>
        </div>
        <div class="install-theme-switcher">
            <div class="moto-ui-theme-switcher">
                <div class="moto-ui-theme-switcher__area" ng-click="switchUiTheme()">
                    <div class="moto-ui-theme-switcher__state moto-ui-theme-switcher__state_light">
                        <div class="moto-ui-theme-switcher__state-icon moto-ui-theme-switcher__state-icon_current moto-ui-icon">theme_light_active</div>
                        <div class="moto-ui-theme-switcher__state-icon moto-ui-icon">theme_light</div>
                    </div>
                    <div class="moto-ui-theme-switcher__pin"></div>
                    <div class="moto-ui-theme-switcher__state moto-ui-theme-switcher__state_dark">
                        <div class="moto-ui-theme-switcher__state-icon moto-ui-theme-switcher__state-icon_current moto-ui-icon">theme_dark_active</div>
                        <div class="moto-ui-theme-switcher__state-icon moto-ui-icon">theme_dark</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="./js/assets.min.js?t=<?php echo $now ?>" type="text/javascript" data-cfasync="false"></script>
    <script src="./js/templates.min.js?t=<?php echo $now ?>" type="text/javascript" data-cfasync="false"></script>
    <script src="./js/translations.min.js?t=<?php echo $now ?>" type="text/javascript" data-cfasync="false"></script>
    <script type="text/javascript" data-cfasync="false">
        angular.module('application.config.value', ['ng']).constant('application.config.value', <?php echo json_encode(MotoInstall\System::getFrontendConfig())?> );
    </script>
    <script src="./js/application.min.js?t=<?php echo $now ?>" type="text/javascript" data-cfasync="false"></script>
</body>
</html>
