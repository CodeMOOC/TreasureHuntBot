<?php
/**
 * Created by PhpStorm.
 * User: SAVERI0
 * Date: 21/09/2016
 * Time: 17:56
 */

require('../vendor/autoload.php');

use PHPQRCode\QRcode;

class QRCodeGenerator {

    const TELEGRAM_DEEP_LINK_TEMPLATE = "https://telegram.me/%s?start=%s";
    const TELEGRAM_PREFIX = "https://telegram.me/";



    static private function CheckTelegramDeepLinkFormat($url){

        $length = strlen(self::TELEGRAM_PREFIX);
        return (substr($url, 0, $length) === self::TELEGRAM_PREFIX);
    }

    private static function MakeTelegramDeepLink($bot_name, $code)
    {
        $url = sprintf(self::TELEGRAM_DEEP_LINK_TEMPLATE, $bot_name, ltrim($code, "\\"));

        return $url;
    }

    static public function GenerateQrCode($code, $bot_name = ""){

        if(!self::CheckTelegramDeepLinkFormat($code)){
            $code = self::MakeTelegramDeepLink($bot_name,$code);
        }


        $tmp_file_name = substr($code, stripos($code, '=')+1);
        $relative_path_tmp_file_name = "../tmp/$tmp_file_name.png";

        if (!file_exists($tmp_file_name)) {
            QRcode::png($code, $relative_path_tmp_file_name, 'H', 50, 2);
        }

        return $relative_path_tmp_file_name;
    }
}


QRCodeGenerator::GenerateQrCode("dajetanto", "miobot");

//QRCodeGenerator::GenerateQrCode("https://telegram.me/miobot?start=dajetanto");