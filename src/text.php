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
const TEXT_FAILURE_GROUP_NOT_FOUND = "Non mi sembra tu sia registrato al gioco. 🤔\nSegui le [indicazioni descritte su sito](http://codemooc.org/urbino-code-hunting/) e riprova!";
const TEXT_FAILURE_GROUP_ALREADY_ACTIVE = "Sei già pronto per giocare.";
const TEXT_FAILURE_GROUP_INVALID_STATE = "Sembra che il tuo gruppo non sia pronto per giocare. 🙁 Segui le istruzioni che ti sono state date.";

// Response to "/help"
const TEXT_CMD_HELP = "Trovi le informazioni sul [sito della caccia al tesoro](http://codemooc.org/urbino-code-hunting/) oppure sul canale @urbinocodehunting.";

// Response to "/reset"
const TEXT_CMD_RESET = "Comando di reset, non implementato.";

// Responses to "/start"
const TEXT_CMD_START_NEW = "Ciao, %FULL_NAME%! Benvenuto alla caccia al tesoro *Urbino Code Hunting Game*. Per partecipare è necessario registrarsi, seguendo le [indicazioni descritte sul sito](http://codemooc.org/urbino-code-hunting/).";
const TEXT_CMD_START_REGISTERED = "Bentornato, %FULL_NAME%! Questo è il bot dedicato alla caccia al tesoro *Urbino Code Hunting Game*.";

const TEXT_CMD_START_PRIZE_INVALID = "Hmmm, sembra che abbiate trovato il premio, ma che _non_ dovevate ancora trovarlo! 🤔";
const TEXT_CMD_START_PRIZE_TOOLATE = "Mi dispiace, ma il gruppo “%GROUP%” ha raggiunto il premio prima di voi! 😱";

const TEXT_CMD_START_LOCATION_REACHED = "Bravi, siete nel posto giusto!";
const TEXT_CMD_START_LOCATION_UNEXPECTED = "Ok! Ma ora non mi serve sapere dove sei! Segui le istruzioni per piacere.";
const TEXT_CMD_START_LOCATION_WRONG = "Sembra che tu abbia scansionato il QRCode sbagliato! Forse sei finito nel posto sbagliato? 😒😒😒";

const TEXT_CMD_START_WRONG_PAYLOAD = "Non ho capito! Forse hai scritto a mano un link ma sarebbe bene che usassi i link contenuti nei QRCode così come sono.";

// First response after receiving "/start REGISTER_CODE" command
const TEXT_CMD_REGISTER_CONFIRM = "Benvenuto a *Urbino Code Hunting*! 🎉 Cerchiamo ora di registrare il tuo gruppo.";
const TEXT_CMD_REGISTER_REGISTERED = "La tua domanda di registrazione è stata ricevuta. 👍";

// States and messages for the registration process
const TEXT_REGISTRATION_NEW_STATE = "Ma sei veramente pronto per il gioco? Per esserne certi ti farò una domanda semplice per iniziare. (Le regole sono basate su [CodyRoby](http://codemooc.org/codyroby/), che sicuramente conoscerai.)";
const TEXT_REGISTRATION_NEW_STATE_CAPTION = "Dove arriva Roby seguendo le indicazioni delle carte? (A, B, o C)";
const TEXT_REGISTRATION_NEW_RESPONSE_CORRECT = "_Esatto!_\nSei un umano senziente quindi. (Oppure un robot piuttosto abile, chissà. 🤖)";
const TEXT_REGISTRATION_NEW_RESPONSE_WRONG = "_Sbagliato!_\nVerifica attentamente e ritenta.";

const TEXT_REGISTRATION_VERIFIED_STATE = "Ora devi soltanto assegnare un nome avvincente al tuo gruppo. Qualcosa che incuta terrore agli avversari, forse. Che nome scegli?";
const TEXT_REGISTRATION_VERIFIED_RESPONSE_OK = "Ok, “%GROUP%” suona bene! Questo è il %COUNT%° gruppo a registrarsi per l’evento.";
const TEXT_REGISTRATION_VERIFIED_RESPONSE_INVALID = "Non mi sembra un nome valido. Come vuoi che il tuo gruppo si chiami?";

const TEXT_REGISTRATION_NAME_STATE = "Il gruppo “%GROUP_NAME%” è prenotato. Nei prossimi giorni riceverai un messaggio per confermare la partecipazione della tua squadra. Solo se risponderai a quel messaggio di conferma tra i primi 30 la tua squadra sarà effettivamente registrata e potrà partecipare… non rimane che aspettare e studiare il [regolamento](http://codemooc.org/urbino-code-hunting/)! ⏰";

const TEXT_REGISTRATION_CONFIRMED_STATE = "Mi puoi scrivere quanti componenti ci sono nel tuo gruppo?";
const TEXT_REGISTRATION_CONFIRMED_RESPONSE_INVALID = "Specifica il numero di partecipanti in cifre, per favore.";
const TEXT_REGISTRATION_CONFIRMED_RESPONSE_TOOFEW = "Il numero minimo di partecipanti per squadra è *2*!";
const TEXT_REGISTRATION_CONFIRMED_RESPONSE_TOOMANY = "Il numero massimo di partecipanti per squadra è *6*!";
const TEXT_REGISTRATION_CONFIRMED_RESPONSE_OK = "Hai appena confemato per %NUMBER% partecipanti!";

const TEXT_REGISTRATION_NUMBER_STATE = "Mi puoi mandare ora una foto o un’immagine da usare come icona del gruppo?";
const TEXT_REGISTRATION_NUMBER_RESPONSE_OK = "Bella foto! Il gruppo “%GROUP_NAME%” è confermato. 👍";
const TEXT_REGISTRATION_NUMBER_RESPONSE_INVALID = "Si è verificato un errore. Invia un’immagine da usare come icona del gruppo, per favore.";

const TEXT_REGISTRATION_READY_STATE = "Ci vediamo *venerdì 26 agosto* alle *20:30* nel cortile del Collegio Raffaello per l’inizio della caccia al tesoro!";

// Advancement notifications
const TEXT_ADVANCEMENT_CONFIRMED = "📢 È giunto il momento di completare la registrazione per il gruppo “%GROUP%”!\nPer prima cosa dimmi quante persone parteciperanno al gioco (te incluso), per piacere.";
const TEXT_ADVANCEMENT_ACTIVATED = "Tutto ok! 👍\nSeguite le istruzioni.";

// States and responses for the game
const TEXT_GAME_LOCATION_STATE_FIRST = "Aspettate altre istruzioni per piacere.";
const TEXT_GAME_LOCATION_STATE = "Raggiungete il punto assegnato e trovate il QRCode.";

const TEXT_GAME_SELFIE_STATE = "Mandatemi un _selfie_ del gruppo per dimostrare di esserci tutti! (Sono ammesse smorfie.)";
const TEXT_GAME_SELFIE_RESPONSE_OK = "Bellissima foto! Ecco l’indovinello da risolvere a questa tappa.";
const TEXT_GAME_SELFIE_FORWARD_CAPTION = "Il gruppo “%GROUP%” raggiunge la %INDEX%° tappa!";

const TEXT_GAME_PUZZLE_STATE = "Scrivete qui la risposta al quesito appena inviato.";
const TEXT_GAME_PUZZLE_RESPONSE_WAIT = "Dovete aspettare altri %SECONDS% secondi prima di poter rispondere.";
const TEXT_GAME_PUZZLE_RESPONSE_WRONG = "Ehm… Non proprio! 😩 Purtroppo avete sbagliato e dovrete aspettare un minuto prima di poter dare un’altra risposta…\nGiusto il tempo di pensare alla soluzione corretta! 😉";

const TEXT_GAME_LAST_LOCATION_STATE = "Manca pochissimo per trovare il tesoro… Raggiungete la posizione assegnata!";

const TEXT_GAME_LAST_PUZZLE_STATE = "Siete giunti all’ultimo quesito… ora, con la mappa in mano, avete tutto il necessario per raggiungere la meta finale! Considerate *attentamente* lo svolgimento del gioco fino a questo momento e saprete trovare il percorso che vi porterà al tesoro! 😉";

const TEXT_GAME_WON = "🎉 *Complimenti!* 🎉\n“%GROUP_NAME%”, siete stati i primi a trovare il tesoro ed avete vinto! Godetevi il premio! 🏆";
const TEXT_GAME_WON_CHANNEL = "*Il gruppo “%GROUP_NAME%” ha raggiunto la tappa finale e vince il gioco!* 🎉";

// Default response for anything else
const TEXT_FALLBACK_RESPONSE = "Scusami, non ho capito cosa intendi. Usa i comandi /start o /help per conversare.";
const TEXT_UNREQUESTED_PHOTO = "Grazie per la foto! Ma inviami i tuoi _selfie_ solo quando richiesto espressamente dal gioco. 😉";
const TEXT_UNSUPPORTED_OTHER = "Piano Piano! Non sono così intelligente ancora!\n\nPurtroppo non gestisco questo tipo di messaggi.\nInviami solo messaggi testuali o foto per piacere.";
