---
title: Create your own game
---

<p class="lead">
The <i>Code Hunting Games</i> bot allows any user to create and manage a treasure hunt game, anywhere and with any number of participating players.
</p>

Before you start, check whether you [satisfy the game's requirements](/en/requirements).

## Creation process

In order to start the game creation process, scan the following QR&nbsp;Code with your smartphone.
It will automatically forward you to the game creation process.

<div class="picture">
    <a href="https://t.me/treasurehuntbot?start=free-to-play">
        <img src="/assets/images/qrcode-free-code-hunting.jpg" alt="Free game creation code" />
    </a>
    <div class="didascaly">Creation code for “free to play” game sessions.</div>
</div>

Note that games created using this QR&nbsp;Code above will generate a new independent game, which will not be associated to any event (so-called “free to play” games).

If you want to join the **global Code&nbsp;Week&nbsp;2019 event**, please refer to the [specific Code&nbsp;Week game creation instructions](/codeweek2019).

### Basic game information

<div class="anim-guide">
    <img src="/assets/images/qrcode-scan.gif" />
    <div class="didascaly">QR&nbsp;Code scanning and creation of a new game.</div>
</div>

The bot will ask for confirmation and then collect the following information:

1. You game’s **name**: you may pick whatever you like, it will be displayed by users and used to generate the completion certificates.
1. A public **Telegram channel** where the bot will automatically publish the progress of the game. This is optional. The channel must be sent as “@channelname” and the bot `@treasurehuntbot` must be added as an administrator to the channel. [Read the Wiki for further details](https://github.com/CodeMOOC/TreasureHuntBot/wiki/Setting-up-a-public-channel).
1. An **e-mail address** at which we can reach you, just in case.

### Start and end location

Every game session requires **two basic locations**: the starting location (where teams gather before starting the game) and the end location (the last location assigned to teams in order to complete the game).

The **starting location** must be specified as a simple geographical position.
Use the “share location” feature of Telegram inside the conversation with the bot: i.e., click on the *attachment* icon and tap on “location”.
*Note:* it is possible to send any location, not only where you are physically at the moment.
[Read the Wiki for further information](https://github.com/CodeMOOC/TreasureHuntBot/wiki/Setting-up-game-locations).

<div class="anim-guide">
    <img src="/assets/images/share-location.gif" alt="Setting the start and end location through the share location feature in Telegram" />
    <div class="didascaly">Setting the start and end locations of your game.</div>
</div>

The **end location** also requires a geographical position and can, optionally, also contain a picture (which can be sent as a photo attachment through Telegram).
If the location has a picture, it will be used *instead* of the geographical position as a hint for players of the destination to reach.
The same behavior also applied to intermediate locations.

### Intermediate locations

The creation process now requires a sequence of intermediate locations (at least&nbsp;8 or more).
Intermediate locations are assigned randomly to each team, during the game.

Each intermediate location has the following properties:

* A **geographical position**, mandatory. In the case of indoor games, where the geographical position is not required, it is possible to simply use an approximate position.
* A **name**, mandatory. Will be used to identify the location when communicating with organizers (never shown publicly).
* A **picture**, optional. Like above (see the “end location”), if a picture is supplied it will be used *instead* of the geographical position when sending a new destination to players.

[More information about game locations on the Wiki](https://github.com/CodeMOOC/TreasureHuntBot/wiki/Setting-up-game-locations).

### Activation and installation

Once the required number of intermediate locations has been added, the creation process can be stopped.
The bot will then **generate QR&nbsp;Codes for the game**, which will be sent as a ZIP&nbsp;package through Telegram.
The ZIP contains PDFs of QR&nbsp;Codes for each location and for registering to the game.

Once the QR&nbsp;Code package has been transferred, remember to **activate the bot**, by clicking on the “activation” button.
This ensures that the game is ready and that the generated QR&nbsp;Codes can be used.

After **printing** the QR&nbsp;Codes (and, optionally, having them plasticized), the codes must be physicall installed in proximity of the given locations.
*Note:* please make sure that the code matches the location that you originally specified to the bot (or that it matches the hint given by the location's picture).
Players will receive only the geographical location or the picture as their next destination to reach: it shouldn't be too hard to find the QR&nbsp;Code.

## Game management

*More information coming soon.*

## Issues?

For any kind of problem with the game or the bot, please let us know.
You may add bugs or other issues through the [issue tracking](https://github.com/CodeMOOC/TreasureHuntBot/issues) system on GitHub.

Have fun with the game! ✌
