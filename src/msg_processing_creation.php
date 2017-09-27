<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Game creation process message processing.
 */

/**
 * Purge the bot's memory from previous registration attempts.
 */
function msg_processing_purge_game_creation($context) {
    $context->memory['creation_channel_tested'] = null;
    $context->memory['creation_channel_name'] = null;
}

/**
 * Initializes game creation for a given event.
 */
function msg_processing_init_game_creation($context, $event_id) {
    msg_processing_purge_game_creation($context);

    if(!bot_creation_init($context, $event_id)) {
        $context->comm->reply(__('failure_general'));
    }
    else {
        $context->memorize_callback($context->comm->reply(
            "Welcome to the game creation process. Do you want to proceed creating a new game for the '%EVENT_NAME%' event?",
            null,
            array("reply_markup" => array(
                "inline_keyboard" => array(
                    array(
                        array("text" => "Yes!", "callback_data" => "confirm"),
                        array("text" => "Cancel", "callback_data" => "cancel")
                    )
                )
            ))
        ));
    }
}

$msg_processing_creation_handlers = array(
    GAME_STATE_NEW => function($context) {
        if($context->callback) {
            if(!$context->verify_callback()) {
                return;
            }

            if($context->callback->data !== 'confirm') {
                bot_creation_abort($context);
                $context->comm->reply("Nevermind then.");

                return;
            }
        }
        else if($context->message) {
            if($context->message->get_response() != 'ok') {
                $context->comm->reply("Say <b>ok</b>?");
                return;
            }
        }

        bot_creation_confirm($context);

        $context->comm->reply("Ok! What's the name of your game?");
    },

    GAME_STATE_REG_NAME => function($context) {
        $text = ($context->message) ? $context->message->text : '';

        $result = bot_creation_set_name($context, $text);
        if($result === 'not_set') {
            $context->comm->reply("Please provide a name for your game.");
            return;
        }
        else if($result === 'too_short') {
            $context->comm->reply("A bit too short?");
            return;
        }
        else if($result === false) {
            $context->comm->reply(__('failure_general'));
            return;
        }

        $context->comm->reply(
            "Ok, name set. Please provide the name of <i>public Telegram channel</i> you will be using during your game.\nCheck out the <a href=\"https://github.com/CodeMOOC/TreasureHuntBot/wiki/Setting-up-a-public-channel\">online guide</a> for more information.",
            null,
            array("reply_markup" => array(
                "inline_keyboard" => array(
                    array(
                        array("text" => "Skip channel", "callback_data" => "skip")
                    )
                )
            ))
        );
    },

    GAME_STATE_REG_CHANNEL => function($context) {
        $handler_func = function($context, $channel_name) {
            $result = bot_creation_set_channel($context, $channel_name);
            if($result === 'invalid') {
                $context->comm->reply(
                    "Invalid channel name. Try another?",
                    null,
                    array("reply_markup" => array(
                        "inline_keyboard" => array(
                            array(
                                array("text" => "Skip channel", "callback_data" => "skip")
                            )
                        )
                    ))
                );
                return;
            }
            else if($result === 'fail_send') {
                $context->comm->reply(
                    "I could not write to the channel. Make sure I have been added as an administrator (check out the <a href=\"https://github.com/CodeMOOC/TreasureHuntBot/wiki/Setting-up-a-public-channel\">online guide</a> for more information). Try again or provide a different channel name.",
                    null,
                    array("reply_markup" => array(
                        "inline_keyboard" => array(
                            array(
                                array("text" => "Try again", "callback_data" => "tryagain")
                            ),
                            array(
                                array("text" => "Skip channel", "callback_data" => "skip")
                            )
                        )
                    ))
                );
                return;
            }
            else if($result === false) {
                $context->comm->reply(__('failure_general'));
                return;
            }
            else {
                $context->memory['creation_channel_tested'] = true;

                $context->comm->reply(
                    "Great! Do you with to publish the <i>selfies</i> of your participants on the public channel automatically?",
                    null,
                    array("reply_markup" => array(
                        "inline_keyboard" => array(
                            array(
                                array("text" => "Yes, show them", "callback_data" => "show"),
                                array("text" => "No selfies!", "callback_data" => "hide")
                            )
                        )
                    ))
                );
            }
        };

        if(!isset($context->memory['creation_channel_tested'])) {
            // Channel not tested yet
            if($context->message) {
                $channel_name = $context->message->text;
                $context->memory['creation_channel_name'] = $channel_name;

                $context->comm->reply("Testing now…");

                $handler_func($context, $channel_name);
            }
            else if($context->callback) {
                if($context->callback->data === 'skip') {
                    bot_creation_update_state($context, GAME_STATE_REG_EMAIL);

                    $context->comm->reply("Ok. Please provide an e-mail address I can use for further communications with you:");
                }
                else if($context->callback->data === 'tryagain') {
                    $context->comm->reply("Testing again…");

                    $handler_func($context, $context->memory['creation_channel_name']);
                }
            }
        }
        else {
            if($context->callback) {
                if(bot_creation_set_channel_censorship($context, $context->callback->data === 'hide') === false) {
                    $context->comm->reply(__('failure_general'));
                    return;
                }
                else {
                    $context->comm->reply("Ok. Please provide an e-mail address I can use for further communications with you:");
                }
            }
            else {
                $context->comm->reply(__('fallback_response'));
            }
        }
    },

    GAME_STATE_REG_EMAIL => function($context) {

    },

    GAME_STATE_LOCATIONS_FIRST => function($context) {

    },

    GAME_STATE_LOCATIONS_LAST => function($context) {

    },

    GAME_STATE_LOCATIONS => function($context) {

    }
);

/**
 * Handles the game's current registration process.
 * @param Context $context - message context.
 * @return bool True if handled, false otherwise.
 */
function msg_processing_handle_game_creation($context) {
    global $msg_processing_creation_handlers;

    if(!$context->game || !$context->game->is_admin) {
        return false;
    }

    $game_state = $context->game->game_state;
    Logger::debug("Handling action for game #{$context->game->game_id}, state " . GAME_STATE_MAP[$game_state], __FILE__, $context);

    if(isset($msg_processing_creation_handlers[$game_state])) {
        call_user_func($msg_processing_creation_handlers[$game_state], $context);
        return true;
    }
    else {
        Logger::debug("No callback to handle state " . GAME_STATE_MAP[$game_state], __FILE__, $context);
    }

    return false;
}
