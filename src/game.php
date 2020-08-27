<?php
/**
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Game data.
 */

const STATE_NEW             = 0;   // newly registered, captcha given
const STATE_REG_VERIFIED    = 1;   // verified as human user, name asked
const STATE_REG_NAME        = 2;   // name registered, participants asked
const STATE_REG_NUMBER      = 3;   // number of participants given, selfie asked
const STATE_REG_READY       = 10;  // avatar given, ready to play
const STATE_GAME_LOCATION   = 30;  // [puzzle solved], location assigned, waiting for qr code
const STATE_GAME_SELFIE     = 32;  // location reached, qr code scanned, waiting for selfie
const STATE_GAME_PUZZLE     = 34;  // selfie taken, puzzle assigned
const STATE_GAME_LAST_LOC   = 40;  // last location assigned, waiting for qr code
const STATE_GAME_LAST_SELF  = 45;  // last location reached, asked for selfie
const STATE_GAME_LAST_PUZ   = 50;  // selfie taken, final puzzle assigned
                                   //   multiple last puzzle stages
const STATE_GAME_WON        = 99;  // final qrcode scanned, victory
const STATE_FEEDBACK        = 128; // won, asked for feedback
                                   //   multiple feedback states
const STATE_CERT_SENT       = 160; // won, certificate sent
const STATE_INVALID         = -1;  // mysterious invalid state

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
    STATE_GAME_LAST_SELF,
    STATE_GAME_LAST_PUZ,
    STATE_GAME_WON,
    STATE_FEEDBACK,
    STATE_CERT_SENT,
    STATE_INVALID
);

const STATE_MAP         = array(
    0   => 'STATE_NEW',
    1   => 'STATE_REG_VERIFIED',
    2   => 'STATE_REG_NAME',
    3   => 'STATE_REG_NUMBER',
    10  => 'STATE_REG_READY',
    30  => 'STATE_GAME_LOCATION',
    32  => 'STATE_GAME_SELFIE',
    34  => 'STATE_GAME_PUZZLE',
    40  => 'STATE_GAME_LAST_LOC',
    45  => 'STATE_GAME_LAST_SELF',
    50  => 'STATE_GAME_LAST_PUZ',
    99  => 'STATE_GAME_WON',
    128 => 'STATE_FEEDBACK',
    160 => 'STATE_CERT_SENT'
);

const STATE_READABLE_MAP         = array(
    -1  => 'Not playing',
    0   => 'Newly registered',
    1   => 'Registering team (set name)',
    2   => 'Registering team (set participants)',
    3   => 'Registering team (send avatar)',
    10  => 'Ready to play',
    30  => 'Reaching a location',
    32  => 'Snapping a selfie',
    34  => 'Solving a puzzle',
    40  => 'Reaching last location',
    45  => 'Snapping last selfie',
    50  => 'Solving last puzzle',
    99  => 'Completed the game'
);

const GAME_STATE_NEW                = 0;   // newly created, asked for confirmation
const GAME_STATE_REG_NAME           = 1;   // confirmed, asked for name
const GAME_STATE_REG_CHANNEL        = 5;   // name ok, asked for channel
const GAME_STATE_REG_EMAIL          = 10;  // channel ok, asked for e-mail
const GAME_STATE_LOCATIONS_FIRST    = 30;  // asked for first location
const GAME_STATE_LOCATIONS_LAST     = 40;  // asked for last location
const GAME_STATE_LOCATIONS          = 50;  // asked for location
const GAME_STATE_GENERATION         = 120;  // generating QR Codes
const GAME_STATE_READY              = 127; // all set, ready to open
const GAME_STATE_ACTIVE             = 128; // ready to accept users, play, etc.
const GAME_STATE_DEAD               = 255; // game is over

const GAME_STATE_ALL                = array(
    GAME_STATE_NEW,
    GAME_STATE_REG_NAME,
    GAME_STATE_REG_CHANNEL,
    GAME_STATE_REG_EMAIL,
    GAME_STATE_LOCATIONS_FIRST,
    GAME_STATE_LOCATIONS_LAST,
    GAME_STATE_LOCATIONS,
    GAME_STATE_GENERATION,
    GAME_STATE_READY,
    GAME_STATE_ACTIVE,
    GAME_STATE_DEAD
);

const GAME_STATE_MAP                = array(
    0       => 'GAME_STATE_NEW',
    1       => 'GAME_STATE_REG_NAME',
    5       => 'GAME_STATE_REG_CHANNEL',
    10      => 'GAME_STATE_REG_EMAIL',
    30      => 'GAME_STATE_LOCATIONS_FIRST',
    40      => 'GAME_STATE_LOCATIONS_LAST',
    50      => 'GAME_STATE_LOCATIONS',
    120     => 'GAME_STATE_GENERATION',
    127     => 'GAME_STATE_READY',
    128     => 'GAME_STATE_ACTIVE',
    255     => 'GAME_STATE_DEAD'
);

const GAME_STATE_READABLE_MAP                = array(
    0       => 'New',
    1       => 'Being configured (set name)',
    5       => 'Being configured (set channel)',
    10      => 'Being configured (set e-mail)',
    30      => 'Being configured (set first location)',
    40      => 'Being configured (set last location)',
    50      => 'Being configured (set location)',
    120     => 'Generating QR Codes',
    127     => 'Ready',
    128     => 'Active',
    255     => 'Terminated'
);

const EVENT_STATE_NEW               = 0;   // placeholder for event creation process
const EVENT_STATE_REGISTRATION      = 128; // event takes game creations and user registrations
const EVENT_STATE_OPEN_FOR_ALL      = 192; // open for all, takes registrations, can play
const EVENT_STATE_PLAYING           = 224; // event does not take registrations, allows play
const EVENT_STATE_DEAD              = 255; // over and out, all games are terminated

const EVENT_STATE_ALL                = array(
    EVENT_STATE_NEW,
    EVENT_STATE_REGISTRATION,
    EVENT_STATE_OPEN_FOR_ALL,
    EVENT_STATE_PLAYING,
    EVENT_STATE_DEAD
);

const EVENT_STATE_MAP               = array(
    0       => 'EVENT_STATE_NEW',
    128     => 'EVENT_STATE_REGISTRATION',
    192     => 'EVENT_STATE_OPEN_FOR_ALL',
    224     => 'EVENT_STATE_PLAYING',
    255     => 'EVENT_STATE_DEAD'
);

const GAME_EVENT_ID_CODEWEEK_2019 = 13;

const GAME_LAST_PUZZLE_1_IMAGE = '../riddles/codeweek-2018/europe.jpg';
const GAME_LAST_PUZZLE_1_SOLUTION = 'c5';

const GAME_LAST_PUZZLE_2_IMAGE = '../riddles/codeweek-2018/france.jpg';
const GAME_LAST_PUZZLE_2_SOLUTION = 'd5';

const GAME_LAST_PUZZLE_3_IMAGE = '../riddles/codeweek-2018/pascal.jpg';
const GAME_LAST_PUZZLE_3_SOLUTION = 'pascal';

const EVENT_IDS_WITH_FINAL_PUZZLE = array(
    2,
    7,
    8,
    10,
    11,
    12,
    13
);

function map_state_to_string($array, $val) {
    $values = array_keys($array);
    $states = array_values($array);

    for($i = 0; $i < sizeof($array); $i++) {
        if($values[$i] == $val) {
            return $states[$i];
        }
        else if($values[$i] > $val && $i > 0) {
            return $states[$i - 1];
        }
    }

    return $states[$i - 1];
}

/**
 * Checks whether users can register to a game.
 */
function game_check_can_register($event_state, $game_state) {
    if($event_state < EVENT_STATE_REGISTRATION) {
        return 'unallowed_event_not_ready';
    }
    else if($event_state === EVENT_STATE_DEAD) {
        return 'unallowed_event_over';
    }

    if($game_state === GAME_STATE_DEAD) {
        return 'unallowed_game_over';
    }
    else if($game_state < GAME_STATE_ACTIVE) {
        return 'unallowed_game_not_ready';
    }

    return true;
}

/**
 * Checks whether users can create games for an event.
 */
function event_check_can_create($event_state) {
    if($event_state < EVENT_STATE_REGISTRATION) {
        return 'unallowed_not_open';
    }
    else if($event_state >= EVENT_STATE_PLAYING) {
        return 'unallowed_event_over';
    }

    return true;
}

/**
 * Checks whether users can play in a game.
 */
function game_check_can_play($event_state, $game_state) {
    if($event_state < EVENT_STATE_OPEN_FOR_ALL) {
        return 'unallowed_event_not_ready';
    }
    else if($event_state === EVENT_STATE_DEAD) {
        return 'unallowed_event_over';
    }

    if($game_state < GAME_STATE_ACTIVE) {
        return 'unallowed_game_not_ready';
    }
    else if($game_state === GAME_STATE_DEAD) {
        return 'unallowed_game_over';
    }

    return true;
}
