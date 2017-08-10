<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Main frontend application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/fontawesome.css?ver=4.4.6',
        'css/animate.css',
        'css/owl.carousel.css',
        'css/jcarousel.connected-carousels.css',
        'css/style.css',
    ];
    public $js = [
        'js/jquery.js',
        'js/jquery-migrate.min.js',
        'js/jquery-2.1.1.min.js',
        'js/wow.min.js',
        'js/owl.carousel.js',
        'js/jquery.jcarousel.min.js',
        'js/jcarousel.connected-carousels.js',
        'js/script.js',
        'js/main.js',
        'js/tp.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
//        'yii\bootstrap\BootstrapAsset',
    ];
}
