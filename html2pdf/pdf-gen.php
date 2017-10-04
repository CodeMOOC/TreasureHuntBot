<?php
require_once(dirname(__FILE__) . '/vendor/autoload.php');

// reference the Dompdf namespace
use Dompdf\Dompdf;

$input_file = $argv[1];
$output_file = $argv[2];
$data_image = $argv[3];      //qr-code image path
$data_lat = $argv[4];        //latitude
$data_long = $argv[5];       //longitude
$data_name = $argv[6];       //name
$data_id = $argv[7];         //loc-id
$data_qr_content = $argv[8]; //qr-content

// instantiate and use the dompdf class
$dompdf = new Dompdf();
$content = file_get_contents($input_file);

//populate html template
$content = str_replace("%root%", dirname(__FILE__), $content);
$content = str_replace("%image%", $data_image, $content);
$content = str_replace("%loc-latitude%", $data_lat, $content);
$content = str_replace("%loc-longitude%", $data_long, $content);
$content = str_replace("%loc-name%", $data_name, $content);
$content = str_replace("%loc-id%", $data_id, $content);
$content = str_replace("%qr-content%", $data_qr_content, $content);

$dompdf->loadHtml($content);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'landscape');

// Render the HTML as PDF
$dompdf->render();

$pdf_gen = $dompdf->output();

if(!file_put_contents($output_file, $pdf_gen)){
    echo 'Not OK!';
    exit(1);
}
else{
    echo 'OK';
}

exit(0);
