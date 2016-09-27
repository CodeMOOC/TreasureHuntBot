<?php

/**
 * Created by PhpStorm.
 * User: SAVERI0
 * Date: 21/09/2016
 * Time: 19:06
 */
require('../vendor/autoload.php');
require("PDFUtils.php");

class LocationPDFGenerator
{

    public static function GenerateLocationPDF($pdf_destination_path, $qr_code_file_path, $game_name, $loc_name = null, $loc_image = null, $loc_text = null, $loc_lat = null, $loc_lng = null) {
        //Instanciation of inherited class
        $pdf = new PDF();
        //$pdf->AliasNbPages();
        $pdf->AddPage("L");
        $pdf->SetFont('Times','',12);

        $pdf->centreImage($qr_code_file_path,(PDFUtils::A4_WIDTH/2) - (PDFUtils::A4_MARGIN_X/2));
        $pdf->SetX((PDFUtils::A4_WIDTH/2) - (PDFUtils::A4_MARGIN_X/2));
        $pdf->MultiCell(PDFUtils::A4_WIDTH/2, 5, "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum",1);

        //list($width, $height) = PDFUtils::Instance()->resizeToFit("../tmp/map.jpg");

        //$x = PDFUtils::Instance()->centreWidth($width, (PDFUtils::A4_WIDTH/2) - (PDFUtils::A4_MARGIN_X/2));

        //$pdf->SetX((PDFUtils::A4_WIDTH/2) - (PDFUtils::A4_MARGIN_X/2));

        //$pdf->Image("../tmp/map.jpg", $x, 50, $width, $height);

        //$pdf->centreImage("../tmp/map.jpg",(PDFUtils::A4_WIDTH/2) - (PDFUtils::A4_MARGIN_X/2));
        $pdf->Output("F", $pdf_destination_path);
    }

}

class PDF extends FPDF
{

    function centreImage($img, $canvas_width = PDFUtils::A4_WIDTH,  $canvas_height = PDFUtils::A4_HEIGHT) {

        list($width, $height) = PDFUtils::Instance()->resizeToFit($img);
        list($x, $y, $w, $h) = PDFUtils::Instance()->centre($width, $height,$canvas_width, $canvas_height);

        $this->Image($img, $x, $y, $w, $h);
    }

}

