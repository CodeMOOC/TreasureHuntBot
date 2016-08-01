<?php
/**/
CONST MSG_START = "/start";
CONST MSG_END = "/end";

function parseMsgIn($configuration)
{
    if (validInput($configuration->text, '/lorenz')) {
        echo $configuration->text . ' puzza' . PHP_EOL;
        telegram_send_message($configuration->chat_id, '/lorenz puzza!');
    } else {
        // Not a recognized message
        echo "Received message: $configuration->text" . PHP_EOL;
        telegram_send_message($configuration->chat_id, $configuration->text);
    }
}

function validInput($text = '', $command = ''){
    return (strpos(mb_strtolower($text), $command) === 0);
}
