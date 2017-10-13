---
title: Create
---

The *Code Hunting Games* bot has been designed to let users create their own games, either for “free play” games or to participate in a large-scale multi-game event (such as the planned game for *CodeWeek 2017*).

# Overview for organizers

In this phase the game is invite-only: please check if organizers have sent you a link to create your own game.
If so, follow the link on a device where you have Telegram installed (notice that creating a game has the [same requirements of playing the game](/play)).
You will be automatically forwarded to the game creation process through the bot.

<div class="anim-guide">
<img src="/assets/images/qrcode-scan.gif" width="400" alt="QR Code scanning process with registration of the game" />
<div class="didascaly">QR&nbsp;Code scanning and creation of a new game.</div>
</div>

The bot will ask the following information during the creation process:

1. You game’s **name**: you may pick whatever you like. In the case of organized events, we would suggest to pick something related to the event itself.
2. A public **Telegram channel** where the bot will automatically publish the progress of teams in your game. This is optional. [Read the Wiki for further details](https://github.com/CodeMOOC/TreasureHuntBot/wiki/Setting-up-a-public-channel).
3. An **e-mail address** at which we can reach you, just in case.
4. The **start location** (i.e., where all teams will meet at the start of the game).
5. The **end location** (i.e., where all teams will be sent at the end of the game).
6. A sequence of **intermediate locations**. The minimum number of locations depends on the game you are organizing, usually you’ll need at least 8 locations. Each location is composed of a geographical position and a name (which can be set freely and will be visible *only* to you). All games have a minimum number of locations that are needed to setup the game.

*Note:* locations must be provided using the “share location” right in the Telegram conversation. Click on the *attachment* icon and tap on “location”. [Read the Wiki for further information](https://github.com/CodeMOOC/TreasureHuntBot/wiki/Setting-up-game-locations).

<div class="anim-guide">
<img src="/assets/images/share-location.gif" width="400" alt="Setting the start and end location through the share location feature in Telegram" />
<div class="didascaly">Setting the start and end locations of your game.</div>
</div>

Once you’re done, the bot will generate a **ZIP package of QR Codes** (in PDF).
You will then be able to print out the PDFs and hide the QR Codes at the actual physical locations you specified.

In the end, remember to **activate** the game by clicking on the “activate” button in your conversation with the bot. (QR Codes provided in your game package will not work until the game is activated.)

Have fun! ✌

# Trouble?

If you're having issues setting up your game, please let us know.
You may also submit bug or error reports through the [issue tracker](https://github.com/CodeMOOC/TreasureHuntBot/issues) on GitHub.
