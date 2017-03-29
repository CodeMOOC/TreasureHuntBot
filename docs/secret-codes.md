# Secret codes

The Treasure Hunt Bot makes use of the Telegram *deep link* feature in order to detect the location that users have reached. Deep links to the bot are usually printed out as *QR Codes* and then hidden somewhere nearby the physical location.
Other secret codes (such as registration or victory codes) are similarly encoded and can also be printed as *QR Codes*.

All secret codes are represented by a variable-length alphanumeric sequence. Currently they are stored in the database as a **binary ASCII strings** of **up to 20 characters**.
Random codes can easily be generated using password generators, such as [KeePass](http://keepass.info/).

Global uniqueness of these codes is ensured by the ```code_lookup``` database table: this table maps each single code to its particular type and its relative event, game, or location.
Currently, secret codes can be of the following types:
* **Creation**: linked to an event, allow the creation of new games.
* **Registration**: linked to a game, allow users to register to the game.
* **Location**: linked to a game and a location, are used to identify locations and signal when users have reached them.
* **Victory**: linked to an event, are used to signal when users reach the last location/solve the last puzzle and win the game.
