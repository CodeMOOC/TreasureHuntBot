<?php
/**/
CONST MSG_START = "/start";
CONST MSG_END = "/end";
CONST MSG_COMPONENTS = "/componenti";
CONST MSG_NAME = "/nome";
CONST REGISTRATION_CODE = "0000000000000000";
CONST CONV_START = 0;
CONST CONV_PARTICIPANTS = 1;
CONST CONV_NAME = 2;
CONST CONV_END = 3;

function parseMsgIn($configuration)
{
    echo 'received text ' . $configuration->text . PHP_EOL;
    if(validInput($configuration->text, MSG_START)){
        startCommand($configuration);
    } else if(validInput($configuration->text, MSG_COMPONENTS)) {
        parseComponentsNumber($configuration);
    } else if(validInput($configuration->text, MSG_NAME)) {
        parseName($configuration);
    } else {
        // Not a recognized message
        echo "Received message: $configuration->text" . PHP_EOL;
        telegram_send_message($configuration->chat_id, $configuration->text);
    }
}

function parsePhotoIn($configuration)
{
    // TODO
    // Check if conversation point is requesting a photo
    echo "checking to see if photo is selfie for group registration..." . PHP_EOL;
    $conversation = db_getConversationList($configuration);

    echo "conversation status: " . $conversation . PHP_EOL;
    if(isset($conversation)){
        switch ($conversation){
            case CONV_START:
                break;
            case CONV_PARTICIPANTS:
                break;
            case CONV_NAME:
                // TODO: save picture and complete signup
                if(savePhoto($configuration)){
                    //completeSignup($configuration);
                } else {
                    // TODO: photo wasn't saved. Ask for it again.
                }
                break;
            case CONV_END:
                break;
            default:
                break;
        }
    } else {
        // TODO: conversation never started - what to do?
        echo "conversation with user has not been initiated" . PHP_EOL;
    }
}

function validInput($text = '', $command = '')
{
    return (strpos(mb_strtolower($text), $command) === 0);
}

function parseCommand($cmd, $text)
{
    $tmp = str_replace($text, "", $cmd);
    return trim($tmp);
}

function startCommand($configuration)
{
    // Parse command to get code
    $code = parseCommand($configuration->text, MSG_START);
    echo "received code " . $code . PHP_EOL;

    if($code == REGISTRATION_CODE) {
        // 1 If it's the registration code, insert into DB data and save user ID and Name
        createNewGroup($configuration);
    } else {
        $conversation = db_getConversationList($configuration);

        if(!empty($conversation)) {
            // TODO
        }
    }
}

/**
 * Function createNewGroup
 * Step 1 - Create new group and start conversation tracking with user
 *
 * Parameters:
 *     @param Configuration $configuration - message data
 */
function createNewGroup($configuration)
{
    $name = $configuration->from[TELEGRAM_FIRSTNAME] . " " . $configuration->from[TELEGRAM_LASTNAME];
    $date = new DateTime('NOW');
    $dateString = $date->format("Y-m-d H:i:s");
    $sql = "INSERT INTO groups (name, leader_telegram_id, leader_name, registration, participants_count, photo_path) 
            VALUES ('new_group_registration', '$configuration->from_id', '$name', '$dateString', '1', 'images/default.jpg')";

    if(db_perform_action($sql) > 0){
        $val = CONV_START;
        $sql = "INSERT INTO conversation (telegram_user_id, state) 
            VALUES ('$configuration->from_id', $val)";

        if (db_perform_action($sql) > 0) {
            // 1 Save user ID and Name
            echo "created new conversation with user " . $configuration->from_id . PHP_EOL;
            // 2.a Ask user for participant number
            echo "created new group for conversation id " . $configuration->from_id . PHP_EOL;
            $msg = $configuration->from[TELEGRAM_FIRSTNAME] . ", hai avviato la procedura di registrazione del tuo gruppo! Indicami il numero di componenti nella tua squadra (da 1 a 6 persone) in questo modo: " . MSG_COMPONENTS . " 6, e al posto di 6 inserisci il numero di componenti!";
            telegram_send_message($configuration->chat_id, $msg);
        } else {
            // TODO: coversation row exists or wasn't created.
            echo "failed creating conversation." . PHP_EOL;
        }
    } else {
        // TODO: group exists or row wasn't created.
        echo "failed creating group." . PHP_EOL;
    }
}

/**
 * Function parseComponentsNumber
 *  Step 2 - Get components number from user and request next step of group registration (group name)
 *
 * Parameters:
 *     @param Configuration $configuration - message data
 */
function parseComponentsNumber($configuration){
    // 2.b Save group number of participants
    $num = parseCommand($configuration->text, MSG_COMPONENTS);
    $sql = "UPDATE groups 
                SET participants_count = '$num'
                WHERE leader_telegram_id = $configuration->from_id";
    if (db_perform_action($sql) > 0) {
        echo "updated group data." . PHP_EOL;
        // Update conversation
        $val = CONV_PARTICIPANTS;
        $sql = "UPDATE conversation  
            SET state = $val
            WHERE telegram_user_id = $configuration->from_id";
        if (db_perform_action($sql) > 0) {
            echo "updated conversation." . PHP_EOL;
            $msg = "Hai creato un gruppo da " . $num . " persone! Ora dimmi come vuoi chiamare il tuo gruppo: scrivi /nome NomeGruppo, ed al posto di NomeGruppo scrivi il nome del tuo gruppo!";
            telegram_send_message($configuration->chat_id, $msg);
        } else {
            // TODO: didn't update conversation data
            echo "failed updating conversation." . PHP_EOL;
        }
    } else {
        // TODO: couldn't update group participant data
        echo "failed updating group participant number." . PHP_EOL;
    }
}

/**
 * Function parseName
 *  Step 3 - Get group name from user and request next step of group registration (photo)
 *
 * Parameters:
 *     @param Configuration $configuration - message data
 */
function parseName($configuration)
{
    // 3.b Save group name
    $name = parseCommand($configuration->text, MSG_NAME);
    $sql = "UPDATE groups 
                SET name = '$name'
                WHERE leader_telegram_id = $configuration->from_id";
    if (db_perform_action($sql) > 0) {
        echo "updated group." . PHP_EOL;
        // Update conversation
        $val = CONV_NAME;
        $sql = "UPDATE conversation  
            SET state = $val
            WHERE telegram_user_id = $configuration->from_id";
        if (db_perform_action($sql) > 0) {
            echo "updated conversation." . PHP_EOL;
            // 4.a Ask user for selfie of group
            $msg = "Il tuo gruppo è stato salvato come " . $name . "! Manca solo un'ultima cosa: inviami un selfie con tutti i membri del tuo gruppo!";
            telegram_send_message($configuration->chat_id, $msg);
        } else {
            // TODO: didn't update conversation data
            echo "failed updating conversation." . PHP_EOL;
        }
    } else {
        // TODO: couldn't update group participant data
        echo "failed updating group participant number." . PHP_EOL;
    }
}

/**
 * Function savePhoto
 *  Step 4: Get photo from Telegram, save on server and update group data to complete registration
 *
 * Parameters:
 *  @param Configuration $configuration - message data
 *  @return string photo path
 */
function savePhoto($configuration)
{
    $picturePath = downloadPhoto($configuration);

    if(empty($picturePath))
        return false;

    // update database
    $sql = "UPDATE groups 
                SET photo_path = '$picturePath'
                WHERE leader_telegram_id = $configuration->from_id";
    if (db_perform_action($sql) > 0) {
        echo "updated group." . PHP_EOL;
        $var = CONV_END;
        $sql = "UPDATE conversation  
            SET state = $var
            WHERE telegram_user_id = $configuration->from_id";
        if (db_perform_action($sql) > 0) {
            echo "updated conversation." . PHP_EOL;
            $msg = "Grazie per il selfie! Il tuo gruppo è ora registrato. Ci rivediamo alla caccia al tesoro!";
            telegram_send_message($configuration->chat_id, $msg);
        } else {
            // TODO: didn't update conversation data
            echo "failed updating conversation with end of conversation status." . PHP_EOL;
        }

    } else {
        // TODO: didn't update group data
        echo "failed updating group with photo path." . PHP_EOL;
    }
    return true;
}

function downloadPhoto($configuration){
    // Get file path
    $filePath = getFilePath(getClient(), $configuration->photo[3]['file_id']);

    if(empty($filePath)) {
        echo "photo file path not received." . PHP_EOL;
        return null;
    }

    // Get photo
    $picturePath = getPicture(getClient(), $filePath, $configuration->photo[3]['file_id']);
    if(empty($picturePath)) {
        echo "Photo was not saved." . PHP_EOL;
        return null;
    }

    return $picturePath;
}

function db_getConversationList($configuration)
{
    // Get conversation list
    echo "getting conversation: \n";
    echo "conversation user id: " . $configuration->from_id . PHP_EOL;
    $sql = "SELECT state FROM conversation WHERE telegram_user_id = '$configuration->from_id'";
    return db_scalar_query($sql);
}