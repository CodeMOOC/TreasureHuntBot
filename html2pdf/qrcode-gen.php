<?php
require(dirname(__FILE__) . '/vendor/autoload.php');

use Endroid\QrCode\QrCode;

$url = $argv[1];
$output_file = $argv[2];

$qrCode = new QrCode($url);
$qrCode->setMargin(0)->setSize(1000);

// Save it to a file
$qrCode->writeFile($output_file);
