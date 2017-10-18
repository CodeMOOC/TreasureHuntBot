<?php
require_once(dirname(__FILE__) . '/vendor/autoload.php');

// reference the Dompdf namespace
use Dompdf\Dompdf;

$output_file = $argv[1];     //output PDF path
$data_participants = $argv[2];
$data_team_name = $argv[3];
$data_action = $argv[4];
$data_game_name = $argv[5];
$data_identifier = $argv[6];

// instantiate and use the dompdf class
$input_file = dirname(__FILE__) . '/template-certificate.html';
$dompdf = new Dompdf();
$content = file_get_contents($input_file);

//populate html template
$content = str_replace("%root%", realpath(dirname(__FILE__) . '/../'), $content);
$content = str_replace("%NUMBER%", $data_participants, $content);
$content = str_replace("%TEAM_NAME%", $data_team_name, $content);
$content = str_replace("%ACTION%", $data_action, $content);
$content = str_replace("%GAME_NAME%", $data_game_name, $content);
$content = str_replace("%BASE_GEN_FILE%", $data_identifier, $content);

echo $content . PHP_EOL;

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
else {
    echo 'OK';
}

exit(0);
