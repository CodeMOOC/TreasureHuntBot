<?php
/**
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Game logic.
 */

const STATE_NEW             = 0;  // newly registered, captcha given
const STATE_REG_VERIFIED    = 1;  // verified as human user, name asked
const STATE_REG_NAME        = 2;  // name registered, participants asked
const STATE_REG_NUMBER      = 3;  // number of participants given, selfie asked
const STATE_REG_READY       = 10; // avatar given, ready to play
const STATE_GAME_LOCATION   = 30; // [puzzle solved], location assigned, waiting for qr code
const STATE_GAME_SELFIE     = 32; // location reached, qr code scanned, waiting for selfie
const STATE_GAME_PUZZLE     = 34; // selfie taken, puzzle assigned
const STATE_GAME_LAST_LOC   = 40; // last location assigned, waiting for qr code
const STATE_GAME_LAST_PUZ   = 50; // qr code scanned, last puzzle assigned
const STATE_GAME_WON        = 99; // final qrcode scanned, victory

const STATE_ALL             = array(
    STATE_NEW,
    STATE_REG_VERIFIED,
    STATE_REG_NAME,
    STATE_REG_NUMBER,
    STATE_REG_READY,
    STATE_GAME_LOCATION,
    STATE_GAME_SELFIE,
    STATE_GAME_PUZZLE,
    STATE_GAME_LAST_LOC,
    STATE_GAME_LAST_PUZ,
    STATE_GAME_WON
);

const GAME_STATE_NEW                = 0; // newly created
const GAME_STATE_REG_NAME           = 1; // name registered
const GAME_STATE_REG_LOCATION       = 2; // location given [ask for address confirmation]
const GAME_STATE_REG_LOCATION_OK    = 3; // location name confirmed [ask for email]
const GAME_STATE_REG_EMAIL          = 4; // e-mail given
const GAME_STATE_REG_CHANNEL        = 5; // channel given
const GAME_STATE_LOCATION           = 10; // collecting info about a location
const GAME_STATE_LOCATION_OK        = 11; // accepting a location as completed
const GAME_STATE_READY              = 20; // all info collected
const GAME_STATE_ACTIVE             = 128; // ready to accept users, play, etc.
const GAME_STATE_DEAD               = 255; // game is over
