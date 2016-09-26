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

const TEXT_UNNAMED_GROUP = "Unnamed";
const TEXT_FAILURE_GENERAL = "This is embarassing… Something went wrong!\nAn error report has already been sent out.";
const TEXT_FAILURE_GROUP_NOT_FOUND = "It doesn’t look like you are registered to the game. 🤔";
const TEXT_FAILURE_GROUP_ALREADY_ACTIVE = "You’re ready to play.";
const TEXT_FAILURE_GROUP_INVALID_STATE = "It looks like your team is not ready to play. 🙁 Follow previous instructions.";

// Response to "/help"
const TEXT_CMD_HELP = "In order to register and perform other actions you will need specific QR Codes. If anything gets messed up, use /reset to start over.";

// Response to "/reset"
const TEXT_CMD_RESET = "Your state has been reset.";

// Responses to "/start"
const TEXT_CMD_START_NEW = "Hello, %FULL_NAME%! Welcome to the *Treasure Hunt Demo* bot. In order to play, you’ll need to register first, which can be done through a QR Code you should have received. 😉\nAlso, you may want to [subscribe to this bot’s public channel](https://telegram.me/codehuntingdemo).";
const TEXT_CMD_START_REGISTERED = "Welcome back, %FULL_NAME%!";

const TEXT_CMD_START_PRIZE_INVALID = "Hmmm, sembra che abbiate trovato il premio, ma che _non_ dovevate ancora trovarlo! 🤔";
const TEXT_CMD_START_PRIZE_TOOLATE = "Mi dispiace, ma il gruppo “%GROUP%” ha raggiunto il premio prima di voi! 😱";

const TEXT_CMD_START_LOCATION_REACHED = "Great, you’re in the right place!";
const TEXT_CMD_START_LOCATION_UNEXPECTED = "Ok! But I don’t need to know were you are right now. Follow the previous instructions.";
const TEXT_CMD_START_LOCATION_WRONG = "It looks like you scanned the wrong QR Code! Did you end up in the wrong place? 😒";

const TEXT_CMD_START_WRONG_PAYLOAD = "Didn’t get that. Did you try to input a QR Code’s code via copy and paste? Please use as QR Code scanner.";

// First response after receiving "/start REGISTER_CODE" command
const TEXT_CMD_REGISTER_CONFIRM = "Welcome to the *Treasure Hunt demo*! 🎉 Let’s try to register your team for the game.";
const TEXT_CMD_REGISTER_REGISTERED = "Your registration request has been received. 👍";

// States and messages for the registration process
const TEXT_REGISTRATION_NEW_STATE = "Are you truly ready for the game? Just to be sure, I’ll send you a very easy game to get started. (My questions are always based on the [CodyRoby](http://codemooc.org/codyroby/) gaming cards.)";
const TEXT_REGISTRATION_NEW_STATE_CAPTION = "Where does Roby end up based on the cards displayed? (A, B, o C)";
const TEXT_REGISTRATION_NEW_RESPONSE_CORRECT = "_Right!_\nYou’re a sentient human. (Or a well-disguised robot, who knows. 🤖)";
const TEXT_REGISTRATION_NEW_RESPONSE_WRONG = "_Wrong!_\nCheck the picture carefully and try again.";

const TEXT_REGISTRATION_VERIFIED_STATE = "Please pick a nice name for your team. Something not too serious, perhaps. What’s the name?";
const TEXT_REGISTRATION_VERIFIED_RESPONSE_OK = "Ok, “%GROUP%” sounds great! This is the %COUNT%th group to try this demo.";
const TEXT_REGISTRATION_VERIFIED_RESPONSE_INVALID = "That doesn’t look like a valid team name. What name do you want to assign to your team?";

const TEXT_REGISTRATION_NAME_STATE = "Il gruppo “%GROUP_NAME%” è prenotato. Nei prossimi giorni riceverai un messaggio per confermare la partecipazione della tua squadra. Solo se risponderai a quel messaggio di conferma tra i primi 30 la tua squadra sarà effettivamente registrata e potrà partecipare… non rimane che aspettare e studiare il [regolamento](http://codemooc.org/urbino-code-hunting/)! ⏰";

const TEXT_REGISTRATION_CONFIRMED_STATE = "Now, tell me how many people will play in your team (including yourself)?";
const TEXT_REGISTRATION_CONFIRMED_RESPONSE_INVALID = "Only digits, please.";
const TEXT_REGISTRATION_CONFIRMED_RESPONSE_TOOFEW = "Your team cannot have less than *2* participants!";
const TEXT_REGISTRATION_CONFIRMED_RESPONSE_TOOMANY = "Your team cannot have more than *6* participants!";
const TEXT_REGISTRATION_CONFIRMED_RESPONSE_OK = "Perfect. That’s %NUMBER% people in your team then!";

const TEXT_REGISTRATION_NUMBER_STATE = "Please send a nice picture that will represent your team. Any logo, picture or even a team selfie will do!";
const TEXT_REGISTRATION_NUMBER_RESPONSE_OK = "Nice picture! Team “%GROUP_NAME%” is registered correctly and ready to play. 👍";
const TEXT_REGISTRATION_NUMBER_RESPONSE_INVALID = "Something didn’t work right. Please send a picture for your team.";

const TEXT_REGISTRATION_READY_STATE = "Proceed to the _activation_ step.";

// Advancement notifications
const TEXT_ADVANCEMENT_CONFIRMED = "📢 È giunto il momento di completare la registrazione per il gruppo “%GROUP%”!\nPer prima cosa dimmi quante persone parteciperanno al gioco (te incluso), per piacere.";
const TEXT_ADVANCEMENT_ACTIVATED = "Tutto ok! 👍\nSeguite le istruzioni.";

// States and responses for the game
const TEXT_GAME_LOCATION_STATE_FIRST = "Wait for further instructions please.";
const TEXT_GAME_LOCATION_STATE = "Reach the following location and scan the QR Code you’ll find there.";

const TEXT_GAME_SELFIE_STATE = "Please send a _selfie_ of your team! (Grimacing allowed.)";
const TEXT_GAME_SELFIE_RESPONSE_OK = "Very nice! Here’s the coding puzzle you need to solve.";
const TEXT_GAME_SELFIE_FORWARD_CAPTION = "Team “%GROUP%” reaches location nr. %INDEX%!";

const TEXT_GAME_PUZZLE_STATE = "Send in the answer to the coding puzzle you just received.";
const TEXT_GAME_PUZZLE_RESPONSE_WAIT = "Please wait %SECONDS% seconds more before trying again.";
const TEXT_GAME_PUZZLE_RESPONSE_WRONG = "Hm… Not really! 😩 I’m afraid you got it wrong this time. Please wait 60 seconds before trying again… just the right amount of time to do some more thinking! 😉";

const TEXT_GAME_LAST_LOCATION_STATE = "You’re almost there… Reach the following location!";

const TEXT_GAME_LAST_PUZZLE_STATE = "You reached the last location. In the _real_ game, you would now receive some last, incredibly difficult, puzzle to solve. In this demo however, just scan in the “victory” code! 😉";

const TEXT_GAME_WON = "🎉 *Well done!* 🎉\nTeam “%GROUP_NAME%”, you have completed the demo treasure hunt and won! 🏆";
const TEXT_GAME_WON_CHANNEL = "*Team “%GROUP_NAME%” has completed the game!* 🎉";

// Default response for anything else
const TEXT_FALLBACK_RESPONSE = "Sorry, I didn’t get what you mean. Use the commands /start or /help to start.";
const TEXT_UNREQUESTED_PHOTO = "Thanks for the picture. However, please send photos only when requested. 😉";
const TEXT_UNSUPPORTED_OTHER = "Whoa! I’m not so smart, yet!\nI only handle text messages as the moment.";
