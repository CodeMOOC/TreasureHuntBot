<?php
/**/
CONST MSG_START = "/start";
CONST MSG_END = "/end";

function parseMsgIn($text = "", $chat_id)
{
    if (validInput($text, MSG_START)) {
        echo 'Received /start command!' . PHP_EOL;
        telegram_send_message($chat_id, 'This is your first Telegram bot, welcome!');
    } else if (validInput($text, '/lorenz')) {
        echo $text . ' puzza' . PHP_EOL;
        telegram_send_message($chat_id, '/lorenz puzza!');
    } else if (validInput($text, MSG_END)) {
        echo 'Received /end command!' . PHP_EOL;
        telegram_send_message($chat_id, 'Goodbye!');
    } else {
        // Not a recognized message
        echo "Received message: $text" . PHP_EOL;
        telegram_send_message($chat_id, $text);
    }
}

function validInput($text = '', $command = ''){
    return (strpos(mb_strtolower($text), $command) === 0);
}