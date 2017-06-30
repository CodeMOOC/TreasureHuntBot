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
const TEXT_FAILURE_GENERAL = "Whops! This is embarassing… Something went wrong!\nTry again later, please.";
const TEXT_FAILURE_GROUP_NOT_FOUND = "You don’t seem to be registered to the game. 🤔";
const TEXT_FAILURE_GROUP_ALREADY_ACTIVE = "You’re ready to play.";
const TEXT_FAILURE_GROUP_INVALID_STATE = "Your team appears not to be ready to play. 🙁 Please follow the instructions you were given.";
const TEXT_FAILURE_QUERY = "Query failed.";

// Response to "/help"
const TEXT_CMD_HELP = "You’ll find more information on the %GAME_CHANNEL% channel.";

// Responses to "/start"
const TEXT_CMD_START_NEW = "Hi, %FULL_NAME%! Welcome to the treasure hunt game. In order to participate you must register, following the instructions given by the organizers.";
const TEXT_CMD_START_REGISTERED = "Welcome back, %FULL_NAME%! This is the treasure hunt bot.";

const TEXT_CMD_START_PRIZE_INVALID = "Hmmm, you appear to have found the prize… but a bit <i>too</i> soon! 🤔";
const TEXT_CMD_START_PRIZE_TOOLATE = "Really sorry. The team “%WINNING_GROUP%” has reached the prize before you! 😱";

const TEXT_CMD_START_LOCATION_REACHED_FIRST = "Let’s begin! 🎉";
const TEXT_CMD_START_LOCATION_REACHED = [
    "Good, you found the right place!",
    "Great, that’s the right code!",
    "Found!",
    "👍",
    "Location reached!"
];
const TEXT_CMD_START_LOCATION_REACHED_LAST = "Well done, you have reached the <b>last location</b>!";
const TEXT_CMD_START_LOCATION_UNEXPECTED = "I don’t need to know where you are! Please follow the instructions.";
const TEXT_CMD_START_LOCATION_WRONG = "It looks like you scanned the wrong code! Did you end up in the wrong place? 😒";

const TEXT_CMD_START_WRONG_PAYLOAD = "Sorry, didn’t get that. Please scan in the codes using a QR Code scanner.";

// First response after receiving "/start REGISTER_CODE" command
const TEXT_CMD_REGISTER_CONFIRM = "Welcome to the <b>Treasure Hunt Game</b>! 🎉 Let’s start by registering your team.";
const TEXT_CMD_REGISTER_REGISTERED = "Your registration request has been received. 👍";

// States and messages for the registration process
const TEXT_REGISTRATION_NEW_STATE = "Are you really ready? To be sure, I’ll ask you a simple coding question. (The rules are based on the <a href=\"http://codemooc.org/codyroby/\">CodyRoby</a> game.)";
const TEXT_REGISTRATION_NEW_STATE_CAPTION = "Where does Roby end up, following the instructions on the cards? (A, B, or C)";
const TEXT_REGISTRATION_NEW_RESPONSE_CORRECT = "<i>Right!</i>\nYou appear to be a sentient human. (Or a very well-made robot, who knows. 🤖)";
const TEXT_REGISTRATION_NEW_RESPONSE_WRONG = "<i>Wrong!</i>\nCheck and try again.";

const TEXT_REGISTRATION_VERIFIED_STATE = "Please choose a name for your team. Something terrifying, perhaps. What name do you choose?";
const TEXT_REGISTRATION_VERIFIED_RESPONSE_OK = "Ok, “%GROUP_NAME%” sounds good! You are team number %GROUP_COUNT%.";
const TEXT_REGISTRATION_VERIFIED_RESPONSE_INVALID = "That doesn’t look like a valid name. What name do you choose for your team?";

const TEXT_REGISTRATION_NAME_STATE = "How many people are there in your team (yourself included)?";
const TEXT_REGISTRATION_NAME_RESPONSE_INVALID = "Just send the number, please.";
const TEXT_REGISTRATION_NAME_RESPONSE_TOOFEW = "A team must be at least composed of <b>2</b> people!";
const TEXT_REGISTRATION_NAME_RESPONSE_TOOMANY = "The maximum allowed number of people per team is <b>20</b>!";
const TEXT_REGISTRATION_NAME_RESPONSE_OK = "Your team of %NUMBER% players is confirmed!";

const TEXT_REGISTRATION_NUMBER_STATE = "Please send an avatar or a picture of your team.";
const TEXT_REGISTRATION_NUMBER_RESPONSE_OK = "Looking good! The team “%GROUP_NAME%” is registered. 👍";
const TEXT_REGISTRATION_NUMBER_RESPONSE_INVALID = "Whops. Please send an image of your team.";

const TEXT_REGISTRATION_READY_STATE = "Everything ok! Please make sure to be registered to the %GAME_CHANNEL% channel and follow the instructions.";

// Advancement notifications
const TEXT_ADVANCEMENT_CONFIRMED = "📢 È giunto il momento di completare la registrazione per il gruppo “%GROUP%”!\nPer prima cosa dimmi quante persone parteciperanno al gioco (te incluso), per piacere.";
const TEXT_ADVANCEMENT_ACTIVATED = "Tutto ok! 👍\nSeguite le istruzioni.";

// States and responses for the game
const TEXT_GAME_LOCATION_STATE = "Reach the assigned location and look for a QR Code!";

const TEXT_GAME_SELFIE_STATE = "Send a nice <i>selfie</i> of your team! (Grimaces are allowed. Make sure you fit in the center of the picture.)";
const TEXT_GAME_SELFIE_RESPONSE_BADGE = "Nice picture! Here’s your badge for the reached location.";
const TEXT_GAME_SELFIE_RESPONSE_OK = "Nice picture! Here’s the quiz for this location.";
const TEXT_GAME_SELFIE_FORWARD_CAPTION = "Team “%GROUP_NAME%” reached location number %INDEX%!";

const TEXT_GAME_PUZZLE_STATE = "Write the answer to the quiz here.";
const TEXT_GAME_PUZZLE_RESPONSE_WAIT = "Please wait for %SECONDS% more seconds before giving your answer.";
const TEXT_GAME_PUZZLE_RESPONSE_WRONG = "Err… not really! 😩 I’m afraid your answer is not correct. Please wait for 1 minute before trying again… and think it through this time! 😉";

const TEXT_GAME_LAST_LOCATION_STATE = "Quick, get to the <i>last</i> location!";

const TEXT_GAME_LAST_PUZZLE_STATE = "Siete giunti all’ultimo quesito… ora, con la mappa in mano, avete tutto il necessario per raggiungere la meta finale! Considerate *attentamente* lo svolgimento del gioco fino a questo momento e saprete trovare il percorso che vi porterà al tesoro! 😉";

const TEXT_GAME_WON = "🎉 <b>Congratulations!</b> 🎉\nTeam “%GROUP_NAME%”, you have found the last location and have won the game! 🏁";
const TEXT_GAME_WON_CHANNEL = "<b>Team “%GROUP_NAME%” has reached the last location!</b> 🏁";

// Default response for anything else
const TEXT_FALLBACK_RESPONSE = "Sorry, I didn’t understand your request.";
const TEXT_UNREQUESTED_PHOTO = "Thanks for the picture! Please send photos only when requested. 😉";
const TEXT_UNSUPPORTED_OTHER = "Hold on, hold on! I’n not <i>this</i> smart, yet, and I cannot handle these messages. Please only send text or photos.";

const TEXT_DEACTIVATED = "No treasure hunt game running at the moment. We’ll be back soon, in the meantime you can <a href=\"http://informatica.uniurb.it/en/treasurehuntbot/\">read the story of this bot</a>.\n<i>Stay tuned!</i>";
