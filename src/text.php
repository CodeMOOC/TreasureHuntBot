<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Text strings.
 *
 * Generally, all strings can make use of the following variables:
 * %FULL_NAME% User's full name
 * %GROUP_NAME% Group's name
 * Additional variables are available for some strings.
 *
 * Most messages (except photo captions, for instance) may
 * use Markdown encoding for formatting.
 * You may also use most Unicode emojis in the text.
 */

const TEXT_UNNAMED_GROUP = "Senza nome";

// Response to "/help"
const TEXT_CMD_HELP = "Messaggio di aiuto.";

// Response to "/reset"
const TEXT_CMD_RESET = "Comando di reset, non implementato.";

// Responses to "/start"
const TEXT_CMD_START_NEW = "Ciao, %FULL_NAME%! Benvenuto alla caccia al tesoro *Urbino Code Hunting Game*. Per partecipare è necessario registrarsi, seguendo le [indicazioni descritte sul sito](http://codemooc.org/urbino-code-hunting/).";
const TEXT_CMD_START_REGISTERED = "Bentornato, %FULL_NAME%! Sei già registrato per l'evento.";

// First response after receiving "/start REGISTER_CODE" command
const TEXT_CMD_REGISTER_CONFIRM = "Perfetto, ti sei ora registrato per l'evento! 🎉";
const TEXT_CMD_REGISTER_REGISTERED = "Sei registrato per l'evento. 👍";

// States and messages for the registration process
const TEXT_REGISTRATION_STATE_NEW = "Ma sei veramente pronto per il gioco? Per esserne certi ti farò una domanda semplice per iniziare. (Le regole sono basate su [CodyRoby](http://codemooc.org/codyroby/), che sicuramente conoscerai.)";
const TEXT_REGISTRATION_STATE_NEW_CAPTION = "Dove arriva Roby seguendo le indicazioni delle carte? (A, B, o C)";
const TEXT_REGISTRATION_RESPONSE_CORRECT = "_Esatto!_\nSei un umano senziente quindi. (Oppure un robot piuttosto abile, chissà. 🤖)";
const TEXT_REGISTRATION_RESPONSE_WRONG = "_Sbagliato!_\nVerifica attentamente e ritenta.";

const TEXT_REGISTRATION_STATE_VERIFIED = "Ora devi soltanto assegnare un nome avvincente al tuo gruppo. Qualcosa che incuta terrore agli avversari, forse. Che nome scegli?";
const TEXT_REGISTRATION_RESPONSE_VERIFIED_OK = "Ok, _\"%NAME%\"_ suona bene!";
const TEXT_REGISTRATION_RESPONSE_VERIFIED_INVALID = "Non mi sembra un nome valido. Come vuoi che il tuo gruppo si chiami?";

const TEXT_REGISTRATION_STATE_NAME = "Sei attualmente registrato per l'evento col gruppo _\"%GROUP_NAME%\"_. Riceverai le prossime istruzioni nei prossimi giorni… non rimane che aspettare. ⏰";

// Default response for anything else
const TEXT_FALLBACK_RESPONSE = "Scusa, non ho capito cosa intendi. Usa i comandi /start o /help per conversare.";

 ?>
