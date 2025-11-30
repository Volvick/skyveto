<?php
// File: i18n.php (FINAL, ROBUST VERSION)
// Location: /common/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default to English if no language is set
$lang_code = $_SESSION['lang'] ?? 'en';

// Define paths for language files
$english_lang_file = ROOT_PATH . '/lang/en.php';
$selected_lang_file = ROOT_PATH . '/lang/' . $lang_code . '.php';

// Always load English first as a safe fallback. This guarantees $translations is always a valid array.
$translations = require $english_lang_file;

// If the user's selected language is not English and the file exists, try to load it.
if ($lang_code !== 'en' && file_exists($selected_lang_file)) {
    // Use a variable to capture the output of require
    $user_translations = require $selected_lang_file;

    // CRITICAL CHECK: Ensure the loaded file actually returned an array.
    // This prevents errors if the file is corrupted, empty, or has a syntax/encoding error.
    if (is_array($user_translations)) {
        // Merge the user's language over the default English.
        // Any missing translations in the user's language will show up in English instead of being blank.
        $translations = array_merge($translations, $user_translations);
    }
}

/**
 * Global translation function.
 *
 * @param string $key The key of the string to translate.
 * @return string The translated string or the key itself if not found.
 */
function __t($key) {
    global $translations;
    // If a translation exists for the key, return it. Otherwise, return the key itself.
    return $translations[$key] ?? $key;
}