<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Registration process message processing.
 */

require_once('lib.php');
require_once('model/context.php');

/**
 * Handles the group's current registration state,
 * sending out a question to the user if needed.
 * @return bool True if handled, false if no need.
 */
function msg_processing_handle_group_state($context) {
    if(null === $context->get_group_id()) {
        //No group
        return false;
    }
    if(null === $context->get_group_state()) {
        //No state
        return false;
    }

    switch($context->get_group_state()) {
        case 'new':
            //Needs to send the captcha question
            $context->reply("Ma sei veramente pronto per il gioco? Per esserne certi ti farò una domanda semplice per iniziare. (Le regole sono basate su [CodyRoby](http://codemooc.org/codyroby/), che sicuramente conoscerai.)");

            telegram_send_photo(
                $context->get_chat_id(),
                'images/quiz-captcha.png',
                "Dove arriva Roby seguendo le indicazioni delle carte? (A, B, o C)"
            );
            return true;

        case 'reg_verified':
            //Needs to ask for group name
            $context->reply("Ora devi soltanto assegnare un nome avvincente al tuo gruppo. Qualcosa che incuta terrore agli avversari, forse. Che nome scegli?");
            return true;

        case 'reg_name':
            $context->reply("Sei registrato col gruppo _\"{$context->get_group_name()}\"_. Riceverai le prossime istruzioni nei prossimi giorni… non rimane che aspettare. ⏰");
            return true;
    }

    return false;
}

/**
 * Handles the user's response if needed by the registration state.
 * @return bool True if handled, false otherwise.
 */
function msg_processing_handle_group_response($context) {
    if(null === $context->get_group_id()) {
        //No group
        return false;
    }
    if(null === $context->get_group_state()) {
        //No state
        return false;
    }

    switch($context->get_group_state()) {
        case 'new':
            if('c' === $context->get_response()) {
                $context->reply("_Esatto!_\nSei un umano senziente quindi. (Oppure un robot piuttosto abile, chissà. 🤖)");

                bot_update_group_state($context, 'reg_verified');

                msg_processing_handle_group_state($context);
            }
            else {
                $context->reply("_Sbagliato!_\nVerifica attentamente e ritenta.");
            }
            return true;

        case 'reg_verified':
            if($context->get_response()) {
                $name = ucwords($context->get_response());

                bot_update_group_name($context, $name);
                bot_update_group_state($context, 'reg_name');

                $context->reply("Ok, _\"{$name}\"_ suona bene!");

                msg_processing_handle_group_state($context);
            }
            else {
                $context->reply("Non mi sembra un nome valido. Come vuoi che il tuo gruppo si chiami?");
            }
            return true;

        case 'reg_name':
            msg_processing_handle_group_state($context);

            return true;
    }

    return false;
}

?>
