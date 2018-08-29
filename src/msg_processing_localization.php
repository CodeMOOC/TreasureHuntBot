<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Localization message processing.
 */

require_once(dirname(__FILE__) . '/model/context.php');

const MEMORY_LOCALIZATION_KEY = "localizationProcess";

function msg_processing_localization_set_language_code($context, $code) {
    localization_set_locale_and_persist($context, $code);

    $context->comm->reply("Language set to <code>{$code}</code>. 👍");
}

function msg_processing_localization($context) {
    if(isset($context->memory[MEMORY_LOCALIZATION_KEY])) {
        if($context->is_callback()) {
            msg_processing_localization_set_language_code($context, $context->callback->data);
        }
        else {
            msg_processing_localization_set_language_code($context, $context->message->text);
        }

        $context->memory[MEMORY_LOCALIZATION_KEY] = null;
        return true;
    }
    else if($context->is_message()) {
        if($context->message->text === '/setlanguage') {
            $lang_keyboard = array();
            $i = 0;
            foreach(LANGUAGE_NAME_MAP as $code => $lang) {
                if($i % 3 == 0) {
                    $lang_keyboard[] = array();
                }
                $lang_keyboard[sizeof($lang_keyboard) - 1][] = array(
                    'text' => $lang,
                    'callback_data' => $code
                );

                $i++;
            }

            // TODO: localization
            $context->comm->reply('What language do you speak?', null, array(
                'reply_markup' => array(
                    'inline_keyboard' => $lang_keyboard
                )
            ));

            $context->memory[MEMORY_LOCALIZATION_KEY] = true;
            return true;
        }
    }

    return false;
}
