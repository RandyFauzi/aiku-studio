<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
$f = '/home/u361393239/domains/aiku.appku.site/aiku-studio/storage/logs/laravel.log';
if (file_exists($f)) {
    echo implode('', array_slice(file($f), -40));
} else {
    echo 'No log file';
}
