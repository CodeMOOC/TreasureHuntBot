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
    $context->memory[MEMORY_CREATION_MIN_LOCATIONS] = null;
    $context->memory[MEMORY_CREATION_MIN_DISTANCE] = null;
    $context->memory[MEMORY_CREATION_CHANNEL_TESTED] = null;
    $context->memory[MEMORY_CREATION_CHANNEL_NAME] = null;
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
        $context->comm->reply(
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
        );
    }
}

$msg_processing_creation_handlers = array(
    GAME_STATE_NEW => function($context) {
        if($context->callback) {
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
                $context->memory[MEMORY_CREATION_CHANNEL_TESTED] = true;

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

        if(!isset($context->memory[MEMORY_CREATION_CHANNEL_TESTED])) {
            // Channel not tested yet
            if($context->message) {
                $channel_name = $context->message->text;
                $context->memory[MEMORY_CREATION_CHANNEL_NAME] = $channel_name;

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

                    $handler_func($context, $context->memory[MEMORY_CREATION_CHANNEL_NAME]);
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
        $result = bot_creation_set_email($context, $context->message->text);
        if($result === 'invalid') {
            $context->comm->reply("This doesn't look like a valid e-mail. Try again:");
            return;
        }
        else if($result === false) {
            $context->comm->reply(__('failure_general'));
            return;
        }

        $context->comm->reply("Let's start defining the locations of your game. 🗺️");
        $context->comm->reply("Send me the geographical position of where your game will <b>start</b> (this is where all players will gather just before the game).");
    },

    GAME_STATE_LOCATIONS_FIRST => function($context) {
        if(!$context->message || !$context->message->is_location()) {
            $context->comm->reply("Please send a geographical location (use Telegram's <i>share</i> button).");
            return;
        }

        bot_creation_set_start($context, $context->message->latitude, $context->message->longitude);

        $context->comm->reply("Very well. Now send the position of the <b>end</b> location (this is where players will go to complete the game).");
    },

    GAME_STATE_LOCATIONS_LAST => function($context) {
        if(!$context->message || !$context->message->is_location()) {
            $context->comm->reply("Please send a geographical location (use Telegram's <i>share</i> button).");
            return;
        }

        bot_creation_set_end($context, $context->message->latitude, $context->message->longitude);

        $context->comm->reply("Now, we'll have to create other locations that will be randomly selected by me during the game. You'll have to provide %NUM_LOCATIONS% locations at least. (<a href=\"https://github.com/CodeMOOC/TreasureHuntBot/wiki/Setting-up-game-locations\">More information</a>.)", array(
            '%NUM_LOCATIONS%' => $context->memory[MEMORY_CREATION_MIN_LOCATIONS]
        ));
        $context->comm->reply("Start sending the geo-position for the first location.");
    },

    GAME_STATE_LOCATIONS => function($context) {
        if($context->message) {
            list($stop_conditions_met, $count) = bot_creation_check_location_conditions($context);

            // User is sending an update (of any kind)
            if($context->message->is_location()) {
                $context->memory[MEMORY_CREATION_LOCATION_LAT] = $context->message->latitude;
                $context->memory[MEMORY_CREATION_LOCATION_LNG] = $context->message->longitude;
            }
            else {
                $context->memory[MEMORY_CREATION_LOCATION_NAME] = $context->message->text;
            }

            // Write out current location status from memory
            $reply_text = "📍 Location <b>%NEXT_INDEX%</b> at %POSITION%, “%NAME%”.";
            if(!isset($context->memory[MEMORY_CREATION_LOCATION_LAT])) {
                $reply_text .= ' ' . "Send the geo-position for the location.";
            }
            else if(!isset($context->memory[MEMORY_CREATION_LOCATION_NAME])) {
                $reply_text .= ' ' . "Write the name of the location.";
            }
            else {
                $reply_text .= ' ' . "All set? Send in a new position or a new name to update, otherwise tap on <i>Save</i>.";
            }

            $buttons = array();
            if(isset($context->memory[MEMORY_CREATION_LOCATION_LAT]) && isset($context->memory[MEMORY_CREATION_LOCATION_NAME])) {
                $buttons[] = array(array("text" => "💾 Save location", "callback_data" => "save"));
            }
            if($stop_conditions_met) {
                $buttons[] = array(array("text" => "Done", "callback_data" => "stop"));
            }

            $context->comm->reply($reply_text, array(
                '%NEXT_INDEX%' => $count + 1,
                '%POSITION%' => (isset($context->memory[MEMORY_CREATION_LOCATION_LAT])) ? sprintf("%.2F,%.2F", $context->memory[MEMORY_CREATION_LOCATION_LAT], $context->memory[MEMORY_CREATION_LOCATION_LNG]) : '???',
                '%NAME%' => (isset($context->memory[MEMORY_CREATION_LOCATION_NAME])) ? $context->memory[MEMORY_CREATION_LOCATION_NAME] : '???'
            ), array("reply_markup" => array(
                "inline_keyboard" => $buttons
            )));
        }
        else if($context->callback) {
            if($context->callback->data === 'stop') {
                // Attempt to terminate locations phase
                $result = bot_creation_stop_location($context);
                if($result === 'conditions_not_met') {
                    $context->comm->reply("Minimum number of locations or minimum distance requirements not met.");
                    return;
                }
                else if($result === false) {
                    $context->comm->reply(__('failure_general'));
                    return;
                }

                $context->comm->reply("Done! All locations have been registered.");
                msg_processing_handle_game_creation($context); // reentrant
            }
            else if($context->callback->data === 'save') {
                // Store current location from memory
                if(bot_creation_save_location(
                    $context,
                    $context->memory[MEMORY_CREATION_LOCATION_LAT],
                    $context->memory[MEMORY_CREATION_LOCATION_LNG],
                    $context->memory[MEMORY_CREATION_LOCATION_NAME]
                ) === false) {
                    $context->comm->reply(__('failure_general'));
                }
                else {
                    list($stop_conditions_met, $count) = bot_creation_check_location_conditions($context);

                    $buttons = (!$stop_conditions_met) ? null : array("reply_markup" => array(
                        "inline_keyboard" => array(
                            array(
                                array("text" => "I'm done", "callback_data" => "stop")
                            )
                        )
                    ));
                    $context->comm->reply("Ok! Send the next position please.", null, $buttons);

                    $context->memory[MEMORY_CREATION_LOCATION_LAT] = null;
                    $context->memory[MEMORY_CREATION_LOCATION_LNG] = null;
                    $context->memory[MEMORY_CREATION_LOCATION_NAME] = null;
                }
            }
        }
    },

    GAME_STATE_READY => function($context) {
        if($context->callback) {
            if($context->callback->data === 'activate') {
                bot_creation_activate($context);

                $context->comm->reply("Your game '%GAME_NAME%' is now active and players can start registering!");

                return;
            }
        }

        $context->comm->reply(
            "Your game '%GAME_NAME%' is ready to be activated.",
            null,
            array("reply_markup" => array(
                "inline_keyboard" => array(
                    array(
                        array("text" => "Let's go!", "callback_data" => "activate")
                    )
                )
            ))
        );
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

    if($context->callback) {
        if(!$context->verify_callback()) {
            Logger::debug("Ignoring callback: does not match last inline keyboard", __FILE__, $context);
            return;
        }
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