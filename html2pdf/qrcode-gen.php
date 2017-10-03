<?php 
require 'vendor/autoload.php';

use Endroid\QrCode\QrCode;

$baseurl = $argv[1];
$string = $argv[2];
$output_file = $argv[3];

if(!file_exists('qrcodes/'.$string.'.png')){
    $qrCode = new QrCode($baseurl.$string);
    $qrCode->setMargin(0)->setSize(1000);

    // Save it to a file
    $qrCode->writeFile($output_file);
}
