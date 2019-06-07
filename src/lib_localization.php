<?php
/**
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Support library for localization.
 */

require_once(dirname(__FILE__) . '/model/context.php');
require_once(dirname(__FILE__) . '/lib_log.php');

// This array maps ISO language codes to locales installed on the local system,
// which match the locales printed by the `locale -a` command.
// Language codes are matched exactly for regional codes, and then approximately
// using the first two characters.
const LANGUAGE_LOCALE_MAP = array(
    //'de' => 'de_DE.utf8',
    'el' => 'el_GR.utf8',
    'en-US' => 'en_US.utf8',
    'es' => 'es_ES.utf8',
    'en' => 'en_US.utf8',
    //'fr' => 'fr_FR.utf8',
    //'hr' => 'hr_HR.utf8',
    'hu' => 'hu_HU.utf8',
    'it' => 'it_IT.utf8',
    //'nl' => 'nl_NL.utf8'
    //'ru' => 'ru_RU.utf8',
    //'sk' => 'sk_SK.utf8',
    'sl' => 'sl_SI.utf8',
    'sv' => 'sv_SE.utf8'
);

// This array maps ISO language codes to user-readable representations of the
// language, localized to the target language.
const LANGUAGE_NAME_MAP = array(
    'el' => 'Greek 🇬🇷',
    'en-US' => 'English 🇺🇸',
    'es' => 'Español 🇪🇸',
    //'hr' => 'Hrvatski 🇭🇷',
    'hu' => 'Magyar 🇭🇺',
    'it' => 'Italiano 🇮🇹',
    //'ru' => 'Русский 🇷🇺',
    //'sk' => 'Slovenčina 🇸🇰',
    'sl' => 'Slovenščina 🇸🇮',
    'sv' => 'Svenska 🇸🇪'
);

function localization_get_locale_for_iso($iso_code) {
    if(array_key_exists($iso_code, LANGUAGE_LOCALE_MAP)) {
        // Exact match
        return LANGUAGE_LOCALE_MAP[$iso_code];
    }
    else if(mb_strlen($iso_code) > 2 && array_key_exists(mb_substr($iso_code, 0, 2), LANGUAGE_LOCALE_MAP)) {
        // Match with base 2-character ISO code
        return LANGUAGE_LOCALE_MAP[mb_substr($iso_code, 0, 2)];
    }
    else {
        // No match found :(
        return LANGUAGE_LOCALE_MAP['en'];
    }
}

/**
 * Sets current locale by language ISO code.
 */
function localization_set_locale($locale_iso_code) {
    $locale = localization_get_locale_for_iso($locale_iso_code);

    putenv('LC_ALL=' . $locale);
    if(setlocale(LC_ALL, $locale) === false) {
        Logger::error("Failed to set locale to {$locale}", __FILE__);
    }

    return $locale;
}

/**
 * Sets current locale and persists selection in user context.
 */
function localization_set_locale_and_persist($context, $locale_iso_code) {
    $locale = localization_set_locale($locale_iso_code);

    db_perform_action(sprintf(
        'UPDATE `identities` SET `language` = \'%s\' WHERE `id` = %d',
        db_escape($locale),
        $context->get_internal_id()
    ));

    Logger::debug("Language code persisted to {$locale}", __FILE__, $context);

    return $locale;
}

function localization_safe_gettext($msgid, $domain) {
    textdomain($domain);

    $value = gettext($msgid);
    if(!$value || $value === $msgid) {
        // No value in translation, default to EN and try again
        $previous_locale = setlocale(LC_ALL, "0");

        setlocale(LC_ALL, LANGUAGE_LOCALE_MAP['en']);
        $value = gettext($msgid);

        setlocale(LC_ALL, $previous_locale);
    }

    return $value;
}

/**
 * Get translated string.
 */
function __($msgid, $domain = 'text') {
    return localization_safe_gettext($msgid, $domain);
}

/**
 * Echoes translated string.
 */
function _e($msgid, $domain = 'text') {
    echo localization_safe_gettext($msgid, $domain);
}

// Load text domains
$target_dir = (dirname(__FILE__) . '/../translation');

bindtextdomain('text', $target_dir);
bind_textdomain_codeset('text', 'UTF-8');

bindtextdomain('admin', $target_dir);
bind_textdomain_codeset('admin', 'UTF-8');

bindtextdomain('riddles', $target_dir);
bind_textdomain_codeset('riddles', 'UTF-8');

// Set default locale
localization_set_locale('');
