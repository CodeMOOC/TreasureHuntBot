<?php
/**
 * Created by PhpStorm.
 * User: SAVERI0
 * Date: 21/09/2016
 * Time: 19:16
 */

include('QRCodeGenerator.php');
include('LocationPDFGenerator.php');

$qrcode_path = QRCodeGenerator::GenerateQrCode("SjwHMn3tXfGqUsW3", "treasurehuntdemobot");
$qrcode_path = QRCodeGenerator::GenerateQrCode("pLR9c8Asorwmm3cp", "treasurehuntdemobot");
$qrcode_path = QRCodeGenerator::GenerateQrCode("FwFEuPPX7eq2Wdfg", "treasurehuntdemobot");
$qrcode_path = QRCodeGenerator::GenerateQrCode("2FKG7N38EzXYYVnj", "treasurehuntdemobot");
$qrcode_path = QRCodeGenerator::GenerateQrCode("ZUM6sZGFoq2FzXfG", "treasurehuntdemobot");
$qrcode_path = QRCodeGenerator::GenerateQrCode("dYp8pEwsHrAuX3UH", "treasurehuntdemobot");

//LocationPDFGenerator::GenerateLocationPDF("../tmp/daje.pdf",$qrcode_path, "Gioco di prova");
