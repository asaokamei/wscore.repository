<?php
if (defined('VENDOR_DIRECTORY')) {
    return;
}

ini_set("session.use_cookies", 0);
ini_set("session.cache_limiter", '');

if (file_exists(__DIR__ . '/../vendor/')) {
    define('VENDOR_DIRECTORY', __DIR__ . '/../vendor/');
} elseif (file_exists(__DIR__ . '/../../../../vendor/')) {
    define('VENDOR_DIRECTORY', __DIR__ . '/../../../../vendor/');
} else {
    die('vendor directory not found');
}

/** @noinspection PhpIncludeInspection */
require_once(VENDOR_DIRECTORY . 'autoload.php');
$loader = new \Composer\Autoload\ClassLoader();

$loader->addPsr4('tests\\', __DIR__);
$loader->register();
