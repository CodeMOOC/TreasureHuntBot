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
const TEXT_FAILURE_GENERAL = "Oh! Questo è imbarazzante… Qualcosa è andato storto!\nChi di dovere è stato avvertito e si sta occupando dell’errore.";
const TEXT_FAILURE_GROUP_NOT_FOUND = "Non mi sembra tu sia registrato al gioco. 🤔";
const TEXT_FAILURE_GROUP_ALREADY_ACTIVE = "Sei già pronto per giocare.";
const TEXT_FAILURE_GROUP_INVALID_STATE = "Sembra che il tuo gruppo non sia pronto per giocare. 🙁 Segui le istruzioni che ti sono state date.";

// Response to "/help"
const TEXT_CMD_HELP = "Trovi le informazioni sul canale @uniurblab.";

// Responses to "/start"
const TEXT_CMD_START_NEW = "Ciao, %FULL_NAME%! Benvenuto alla caccia al tesoro <b>Urbino Code Hunting Game</b>. Per partecipare è necessario registrarsi, seguendo le indicazioni date dagli organizzatori dell’evento.";
const TEXT_CMD_START_REGISTERED = "Bentornato, %FULL_NAME%! Questo è il bot dedicato alla caccia al tesoro <b>Urbino Code Hunting Game</b>.";

const TEXT_CMD_START_PRIZE_INVALID = "Hmmm, sembra che abbiate trovato il premio, ma che <i>non</i> dovevate ancora trovarlo! 🤔";
const TEXT_CMD_START_PRIZE_TOOLATE = "Mi dispiace, ma il gruppo “%WINNING_GROUP%” ha raggiunto il premio prima di voi! 😱";

const TEXT_CMD_START_LOCATION_REACHED = "Bravi, siete nel posto giusto!";
const TEXT_CMD_START_LOCATION_UNEXPECTED = "Ok! Ma ora non mi serve sapere dove sei! Segui le istruzioni per piacere.";
const TEXT_CMD_START_LOCATION_WRONG = "Sembra che tu abbia scansionato il QRCode sbagliato! Forse sei finito nel posto sbagliato? 😒";

const TEXT_CMD_START_WRONG_PAYLOAD = "Non ho capito! Forse hai scritto a mano un link? Sarebbe bene che usassi i link contenuti nei QR Code così come sono.";

// First response after receiving "/start REGISTER_CODE" command
const TEXT_CMD_REGISTER_CONFIRM = "Benvenuto a <b>Urbino Code Hunting</b>! 🎉 Cerchiamo di registrare il tuo gruppo al gioco.";
const TEXT_CMD_REGISTER_REGISTERED = "La tua domanda di registrazione è stata ricevuta. 👍";

// States and messages for the registration process
const TEXT_REGISTRATION_NEW_STATE = "Ma siete veramente pronti per il gioco? Per esserne certi farò una domanda semplice semplice per iniziare. (Le regole sono basate su <a href=\"http://codemooc.org/codyroby/\">CodyRoby</a>.)";
const TEXT_REGISTRATION_NEW_STATE_CAPTION = "Dove arriva Roby seguendo le indicazioni delle carte? (A, B, o C)";
const TEXT_REGISTRATION_NEW_RESPONSE_CORRECT = "<i>Esatto!</i>\nSei un umano senziente quindi. (Oppure un robot piuttosto abile, chissà. 🤖)";
const TEXT_REGISTRATION_NEW_RESPONSE_WRONG = "<i>Sbagliato!</i>\nVerifica attentamente e ritenta.";

const TEXT_REGISTRATION_VERIFIED_STATE = "Ora dovete soltanto assegnare un nome avvincente al vostro gruppo. Qualcosa che incuta terrore agli avversari, forse. Che nome scegli?";
const TEXT_REGISTRATION_VERIFIED_RESPONSE_OK = "Ok, “%GROUP_NAME%” suona bene! Siete il %GROUP_COUNT%° gruppo a registrarsi per il gioco.";
const TEXT_REGISTRATION_VERIFIED_RESPONSE_INVALID = "Non mi sembra un nome valido. Come volete che il vostro gruppo si chiami?";

const TEXT_REGISTRATION_NAME_STATE = "Mi puoi scrivere quanti componenti ci sono nel gruppo?";
const TEXT_REGISTRATION_NAME_RESPONSE_INVALID = "Specifica il numero di partecipanti in cifre, per favore.";
const TEXT_REGISTRATION_NAME_RESPONSE_TOOFEW = "Il numero minimo di partecipanti per squadra è <b>2</b>!";
const TEXT_REGISTRATION_NAME_RESPONSE_TOOMANY = "Il numero massimo di partecipanti per squadra è <b>10</b>!";
const TEXT_REGISTRATION_NAME_RESPONSE_OK = "Hai appena confermato per %NUMBER% partecipanti!";

const TEXT_REGISTRATION_NUMBER_STATE = "Mi puoi mandare ora una foto o un’immagine da usare come icona del gruppo?";
const TEXT_REGISTRATION_NUMBER_RESPONSE_OK = "Bella foto! Il gruppo “%GROUP_NAME%” è registrato. 👍";
const TEXT_REGISTRATION_NUMBER_RESPONSE_INVALID = "Si è verificato un errore. Invia un’immagine da usare come icona del gruppo, per favore.";

const TEXT_REGISTRATION_READY_STATE = "Tutto ok! Assicuratevi di essere iscritti al canale @uniurblab e seguite le istruzioni che vi verranno date.";

// Advancement notifications
const TEXT_ADVANCEMENT_CONFIRMED = "📢 È giunto il momento di completare la registrazione per il gruppo “%GROUP%”!\nPer prima cosa dimmi quante persone parteciperanno al gioco (te incluso), per piacere.";
const TEXT_ADVANCEMENT_ACTIVATED = "Tutto ok! 👍\nSeguite le istruzioni.";

// States and responses for the game
const TEXT_GAME_LOCATION_STATE = "Raggiungete il punto assegnato e trovate il QR Code!";

const TEXT_GAME_SELFIE_STATE = "Mandatemi un <i>selfie</i> del gruppo per dimostrare di esserci tutti! (Sono ammesse smorfie.)";
const TEXT_GAME_SELFIE_RESPONSE_OK = "Bellissima foto! Ecco l’indovinello da risolvere a questa tappa.";
const TEXT_GAME_SELFIE_FORWARD_CAPTION = "Il gruppo “%GROUP_NAME%” raggiunge la %INDEX%° tappa!";

const TEXT_GAME_PUZZLE_STATE = "Scrivete qui la risposta al quesito appena inviato.";
const TEXT_GAME_PUZZLE_RESPONSE_WAIT = "Dovete aspettare altri %SECONDS% secondi prima di poter rispondere.";
const TEXT_GAME_PUZZLE_RESPONSE_WRONG = "Ehm… Non proprio! 😩 Purtroppo avete sbagliato e dovrete aspettare un minuto prima di poter dare un’altra risposta…\nGiusto il tempo di pensare alla soluzione corretta! 😉";

const TEXT_GAME_LAST_LOCATION_STATE = "Manca pochissimo per trovare il tesoro… Raggiungete la posizione assegnata!";

const TEXT_GAME_LAST_PUZZLE_STATE = "Siete giunti all’ultimo quesito… ora, con la mappa in mano, avete tutto il necessario per raggiungere la meta finale! Considerate *attentamente* lo svolgimento del gioco fino a questo momento e saprete trovare il percorso che vi porterà al tesoro! 😉";

const TEXT_GAME_WON = "🎉 <b>Complimenti!</b> 🎉\n“%GROUP_NAME%”, siete stati i primi a trovare il tesoro ed avete vinto! Godetevi il premio! 🏆";
const TEXT_GAME_WON_CHANNEL = "<b>Il gruppo “%GROUP_NAME%” ha raggiunto la tappa finale e vince il gioco!</b> 🎉";

// Default response for anything else
const TEXT_FALLBACK_RESPONSE = "Scusami, non ho capito cosa intendi.";
const TEXT_UNREQUESTED_PHOTO = "Grazie per la foto! Ma inviami i tuoi <i>selfie</i> solo quando richiesto espressamente dal gioco. 😉";
const TEXT_UNSUPPORTED_OTHER = "Piano Piano! Non sono così intelligente ancora!\nPurtroppo non gestisco questo tipo di messaggi. Inviami solo messaggi testuali o foto per piacere.";

const TEXT_DEACTIVATED = "Al momento non ci sono cacce al tesoro attive. Presto torneremo con altre novità, nel frattempo <a href=\"http://informatica.uniurb.it/una-caccia-al-tesoro-guidata-da-un-bot/\">leggi la storia di questo bot</a>.\n<i>A presto!</i>\n\n🇬🇧 No treasure hunt game running at the moment. We’ll be back soon, in the meanti me you can <a href=\"http://informatica.uniurb.it/en/treasurehuntbot/\">read the story of this bot</a>.\n<i>Stay tuned!</i>";
