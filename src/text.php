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
const TEXT_FAILURE_GENERAL = "Qualcosa è andato storto. Chi di dovere è stato avvertito.";

// Response to "/help"
const TEXT_CMD_HELP = "Messaggio di aiuto.";

// Response to "/reset"
const TEXT_CMD_RESET = "Comando di reset, non implementato.";

// Responses to "/start"
const TEXT_CMD_START_NEW = "Ciao, %FULL_NAME%! Benvenuto alla caccia al tesoro *Urbino Code Hunting Game*. Per partecipare è necessario registrarsi, seguendo le [indicazioni descritte sul sito](http://codemooc.org/urbino-code-hunting/).";
const TEXT_CMD_START_REGISTERED = "Bentornato, %FULL_NAME%! Questo è il bot dedicato alla caccia al tesoro *Urbino Code Hunting Game*.";

// First response after receiving "/start REGISTER_CODE" command
const TEXT_CMD_REGISTER_CONFIRM = "Perfetto! 🎉 Cerchiamo ora di registrare il tuo gruppo.";
const TEXT_CMD_REGISTER_REGISTERED = "La tua domanda di registrazione è stata avviata. 👍";

// States and messages for the registration process
const TEXT_REGISTRATION_NEW_STATE = "Ma sei veramente pronto per il gioco? Per esserne certi ti farò una domanda semplice per iniziare. (Le regole sono basate su [CodyRoby](http://codemooc.org/codyroby/), che sicuramente conoscerai.)";
const TEXT_REGISTRATION_NEW_STATE_CAPTION = "Dove arriva Roby seguendo le indicazioni delle carte? (A, B, o C)";
const TEXT_REGISTRATION_NEW_RESPONSE_CORRECT = "_Esatto!_\nSei un umano senziente quindi. (Oppure un robot piuttosto abile, chissà. 🤖)";
const TEXT_REGISTRATION_NEW_RESPONSE_WRONG = "_Sbagliato!_\nVerifica attentamente e ritenta.";

const TEXT_REGISTRATION_VERIFIED_STATE = "Ora devi soltanto assegnare un nome avvincente al tuo gruppo. Qualcosa che incuta terrore agli avversari, forse. Che nome scegli?";
const TEXT_REGISTRATION_VERIFIED_RESPONSE_OK = "Ok, “%GROUP%” suona bene! Questo è il %COUNT%° gruppo a registrarsi per l’evento.";
const TEXT_REGISTRATION_VERIFIED_RESPONSE_INVALID = "Non mi sembra un nome valido. Come vuoi che il tuo gruppo si chiami?";

const TEXT_REGISTRATION_NAME_STATE = "La tua partecipazione col gruppo “%GROUP_NAME%” è stata prenotata. Nei prossimi giorni riceverai un messaggio per corfermare la partecipazione della tua squadra. Solo se risponderai a quel messaggio di conferma tra i primi 30 la tua squadra sarà effettivamente registrata e potrà partecipare… non rimane che aspettare e studiare il [regolamento](http://codemooc.org/urbino-code-hunting/)! ⏰";

const TEXT_REGISTRATION_CONFIRMED_STATE = "Mi puoi scrivere quanti componenti ci sono nel tuo gruppo?";
const TEXT_REGISTRATION_CONFIRMED_RESPONSE_INVALID = "Specifica il numero di partecipanti in cifre, per favore.";
const TEXT_REGISTRATION_CONFIRMED_RESPONSE_TOOFEW = "Il numero minimo di partecipanti per squadra è *2*!";
const TEXT_REGISTRATION_CONFIRMED_RESPONSE_TOOMANY = "Il numero massimo di partecipanti per squadra è *6*!";
const TEXT_REGISTRATION_CONFIRMED_RESPONSE_OK = "Hai appena confemato per %NUMBER% partecipanti!";

const TEXT_REGISTRATION_NUMBER_STATE = "Mi puoi mandare ora una foto o un’immagine da usare come icona del gruppo?";
const TEXT_REGISTRATION_NUMBER_RESPONSE_OK = "Bella foto! Il gruppo “%GROUP%” è confermato. 👍";
const TEXT_REGISTRATION_NUMBER_RESPONSE_INVALID = "Si è verificato un errore. Invia un’immagine da usare come icona del gruppo, per favore.";

const TEXT_REGISTRATION_READY_STATE = "Ci vediamo *venerdì 26 agosto* alle *20:30* nel cortile del Collegio Raffaello per l’inizio della caccia al tesoro!";

// Default response for anything else
const TEXT_FALLBACK_RESPONSE = "Scusa, non ho capito cosa intendi. Usa i comandi /start o /help per conversare.";
const TEXT_UNSUPPORTED_PHOTO = " Ti prego di non inviarmi foto direttamente. Invia i _selfie_, quando richiesto, nella chat di gruppo.";
const TEXT_UNREQUESTED_PHOTO = "Grazie per la foto, ma inviami i tuoi _selfie_ solo quando richiesto espressamente dal gioco.";
const TEXT_UNSUPPORTED_OTHER = "Non gestisco questo tipo di messaggi, inviami solo messaggi testuali o foto.";

