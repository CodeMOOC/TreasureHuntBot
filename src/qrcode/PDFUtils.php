<?php

/**
 * Created by PhpStorm.
 * User: SAVERI0
 * Date: 23/09/2016
 * Time: 14:30
 */
class PDFUtils
{

    private static $instance = null;

    public static function Instance(){
        if(self::$instance == null){
            self::$instance = new PDFUtils();
        }

        return self::$instance;
    }

    private function __construct()
    {}


    const DPI = 96;
    const MM_IN_INCH = 25.4;
    const A4_HEIGHT = 210;
    const A4_WIDTH = 297;
    const A4_MARGIN_X = 20;
    const A4_MARGIN_Y = 30;

    // tweak these values (in pixels)
    const MAX_WIDTH = 800;
    const MAX_HEIGHT = 500;

    function pixelsToMM($val) {
        return $val * self::MM_IN_INCH / self::DPI;
    }

    function resizeToFit($imgFilename) {
        list($width, $height) = getimagesize($imgFilename);
        $widthScale = self::MAX_WIDTH / $width;
        $heightScale = self::MAX_HEIGHT / $height;
        $scale = min($widthScale, $heightScale);
        return array(
            round($this->pixelsToMM($scale * $width)),
            round($this->pixelsToMM($scale * $height))
        );
    }

    function centre($width, $height, $canvas_width = self::A4_WIDTH,  $canvas_height = self::A4_HEIGHT) {
        return array(
            $this->centreWidth($width, $canvas_width),
            $this->centreHeight($height, $canvas_height),
            $width,
            $height
        );
    }

    function centreWidth($width, $canvas_width = self::A4_WIDTH) {
        return ($canvas_width - $width) / 2;
    }

    function centreHeight($height, $canvas_height = self::A4_HEIGHT) {
        return ($canvas_height - $height) / 2;
    }

}