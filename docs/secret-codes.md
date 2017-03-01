# Secret codes

The Treasure Hunt Bot makes use of the Telegram _deep link_ feature in order to detect the location that users have reached. Deep links to the bot are usually printed out as QR Codes and then hidden somewhere nearby the physical location.
Other secret codes (such as registration or victory codes) are similarly encoded and can be printed as QR Codes.

All secret codes are composed of a simple 8-character alphanumeric code. For instance, ```TKYpGaLR```. This part of the secret code is the “secret”.
The rest of the secret code includes unique IDs and other identifying strings, depending on the kind of secret code.

## Registration code

```
reg-<game ID>-<secret>
```

## Victory code

```
win-<event ID>-<secret>
```

## Location code

```
loc-<game ID>-<location ID>-<secret>
```
