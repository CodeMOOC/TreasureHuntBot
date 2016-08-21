<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Text strings.
 *
 * Generally, all strings can make use of the following variables:
 * $context (Context class)
 * $message (Incoming message as JSON payload)
 *
 * Most messages (except photo captions, for instance) may
 * use Markdown encoding for formatting.
 */

// Response to "/help"
const TEXT_CMD_HELP = "Messaggio di aiuto.";

// Response to "/reset"
const TEXT_CMD_RESET = "Comando di reset, non implementato.";

// Responses to "/start"
const TEXT_CMD_START_NEW = "Ciao, {$context->get_message()->get_full_sender_name()}! Benvenuto alla caccia al tesoro *Urbino Code Hunting Game*. Per partecipare è necessario registrarsi, secondo le [modalità descritte sul sito](http://codemooc.org/urbino-code-hunting/), inviando il comando /register in questa chat.";
const TEXT_CMD_START_REGISTERED = "Ciao, {$context->get_message()->get_full_sender_name()}! Sei già registrato con il gruppo _'{$context->get_group_name()}'_.";

// First response after receiving "/start REGISTER_CODE" command
const TEXT_CMD_REGISTER_CONFIRM = "Ok, ti sei registrato per l'evento!";
const TEXT_CMD_REGISTER_REGISTERED = "Sei già registrato con il gruppo _'{$context->get_group_name()}'_, non c'è bisogno di registrarsi nuovamente.";

// States and messages for the registration process
const TEXT_REGISTRATION_STATE_NEW = "Ma sei veramente pronto per il gioco? Per esserne certi ti farò una domanda semplice per iniziare. (Le regole sono basate su [CodyRoby](http://codemooc.org/codyroby/), che sicuramente conoscerai.)";
const TEXT_REGISTRATION_STATE_NEW_CAPTION = "Dove arriva Roby seguendo le indicazioni delle carte? (A, B, o C)";
const TEXT_REGISTRATION_RESPONSE_CORRECT = "_Esatto!_\nSei un umano senziente quindi. (Oppure un robot piuttosto abile, chissà. 🤖)";
const TEXT_REGISTRATION_RESPONSE_WRONG = "_Sbagliato!_\nVerifica attentamente e ritenta.";

const TEXT_REGISTRATION_STATE_VERIFIED = "Ora devi soltanto assegnare un nome avvincente al tuo gruppo. Qualcosa che incuta terrore agli avversari, forse. Che nome scegli?";
const TEXT_REGISTRATION_RESPONSE_VERIFIED_OK = "Ok, _\"{$name}\"_ suona bene!";
const TEXT_REGISTRATION_RESPONSE_VERIFIED_INVALID = "Non mi sembra un nome valido. Come vuoi che il tuo gruppo si chiami?";

const TEXT_REGISTRATION_STATE_NAME = "Sei registrato col gruppo _\"{$context->get_group_name()}\"_. Riceverai le prossime istruzioni nei prossimi giorni… non rimane che aspettare. ⏰";

// Default response for anything else
const TEXT_FALLBACK_RESPONSE = "Scusa, non ho capito cosa intendi. Usa i comandi /start o /help per conversare.";

 ?>
