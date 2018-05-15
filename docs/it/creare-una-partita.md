---
title: Creare una partita
---

<p class="lead">
Il bot di <i>Code Hunting Games</i> permette a qualsiasi utente di organizzare e gestire una propria caccia al tesoro, in maniera del tutto indipendente, per un qualsiasi numero di giocatori.
</p>

Prima di iniziare, verificare innanzitutto i [requisiti del gioco](/it/requisiti).

## Processo di creazione

Per iniziare a creare una nuova partita di *Code Hunting Games*, scannerizzare il seguente QR&nbsp;Code:

<div class="picture">
    <a href="https://t.me/treasurehuntbot?start=free-to-play">
        <img src="/assets/images/qrcode-free-code-hunting.jpg" alt="Codice per la creazione di partite libere" />
    </a>
    <div class="didascaly">Codice per la creazione di partite libere.</div>
</div>

### Informazioni di base

<div class="anim-guide">
    <img src="/assets/images/qrcode-scan.gif" />
    <div class="didascaly">Esempio di scansione del codice di creazione e richiesta informazioni di base.</div>
</div>

Il bot chiederà conferma della creazione di una nuova partita, dopodché chiederà le seguenti informazioni:

1. **Nome** della partita (verrà visualizzato dagli utenti ed utilizzato per la creazione dei certificati).
1. Nome breve del **canale Telegram** utilizzato per la comunicazione automatica degli aggiornamenti della partita. Questo è opzionale. Il nome va fornito nella forma “@nomecanale”. Il bot `@treasurehuntbot` deve essere preventivamente aggiunto come amministratore al canale, [leggere il Wiki per altre informazioni](https://github.com/CodeMOOC/TreasureHuntBot/wiki/Setting-up-a-public-channel).
1. Indirizzo **e-mail** degli organizzatori (non verrà mai condiviso, ma solo utilizzato internamente per eventuali comunicazioni di servizio).

### Luogo di inizio e di fine

Ogni partita necessita di **due luoghi fondamentali**, uno per la partenza (è il luogo dove tutte le squadre si raduneranno prima di iniziare a giocare) ed uno per la conclusione del gioco (è l’ultima tappa che verrà assegnata ad ogni squadra al termine della partita).

La **tappa di partenza** va specificata come sola posizione geografica.
Utilizzare la funzione “condividi” di Telegram per condividere una posizione.
*Nota bene:* è possibile indicare una posizione in cui non ci si trova attualmente, spostando il punto di Telegram sulla mappa.
[Leggi il Wiki per altre informazioni](https://github.com/CodeMOOC/TreasureHuntBot/wiki/Setting-up-game-locations).

<div class="anim-guide">
    <img src="/assets/images/share-location.gif" alt="Inviare una posizione geografica tramite Telegram" />
    <div class="didascaly">Invio di una posizione geografica tramite la funzionalità “condividi” di Telegram.</div>
</div>

La **tappa finale** richiede una posizione geografica e può, opzionalmente, anche essere corredata con un’immagine (che va inviata a Telegram come allegato fotografico).
Come per le tappe intermedie (vedi sotto), se alla tappa viene associata un’immagine, questa verrà utilizzata *invece* della posizione geografica come indizio sul luogo da raggiungere.

### Tappe intermedie

Successivamente il processo di creazione richiede la generazione di una sequenza di tappe intermedie (almeno&nbsp;8 o più).
Le tappe intermedie verranno selezionate casualmente a tutte le squadre che partecipano al gioco, durante lo svolgimento della partita.

Ogni tappa va creata specificando:

* Una **posizione geografica**, obbligatoria (nel caso di partite al chiuso o in cui la posizione geografica non è importante, è possibile specificare una posizione approssimativa).
* Un **nome**, obbligatorio, che verrà utilizzato solo nella comunicazione con gli organizzatori.
* Un’**immagine**, opzionalmente (come nel caso della tappa finale, l’immagine verrà utilizzata al posto della posizione geografica nel fornire la prossima destinazione da raggiungere alle squadre).

[Altre informazioni sul WiKi](https://github.com/CodeMOOC/TreasureHuntBot/wiki/Setting-up-game-locations).

### Attivazione e installazione

Una volta inserito il numero minimo di tappe, il processo di creazione può essere terminato.
Il bot **genererà quindi i QR&nbsp;Code**, che verranno trasferiti tramite Telegram come pacchetto&nbsp;ZIP, contenente un&nbsp;PDF per ogni luogo ed un&nbsp;PDF per la registrazione al gioco.

Una volta ricevuto il pacchetto&nbsp;ZIP, accertarsi di cliccare sul pulsante “attivazione” del bot per fare in modo che la partita appena creata sia attiva ed i QR&nbsp;Code vengano attivati correttamente.

Dopo aver **stampato** i PDF trasmessi dal bot (ed averli opzionalmente plastificati), i codici delle tappe vanno installati fisicamente in prossimità delle tappe.
*Nota bene:* va rispettata la corrispondenza tra posizione geografica (o indizio sulla base di immagine) indicata per la tappa e la posizione effettiva del QR&nbsp;Code stampato, in modo da non rendere troppo difficoltosa l’individuazione della tappa da parte delle squadre.

## Gestione

*Più informazioni a breve.*

## Problemi?

Per qualsiasi tipo di problema con il gioco o con il bot, facci sapere.
È possibile segnalare bug o problemi tramite il sistema di [issue tracking](https://github.com/CodeMOOC/TreasureHuntBot/issues) di GitHub.

Buon divertimento! ✌
