<?php
/**
 * Konfigurációs fájl
 * Fájl helye: /config.php
 * Funkció: Alapvető beállítások és API kulcsok tárolása. .env fájl beolvasása.
 */

// Hibakezelés beállítása fejlesztéshez (élesben kapcsoljuk ki, ha JSON API-t használunk, mert bezavarhat)
ini_set('display_errors', 0); // Kikapcsoljuk a kimenetre írást
ini_set('log_errors', 1); // Bekapcsoljuk a fájlba logolást
error_reporting(E_ALL);

// .env fájl betöltése manuálisan (külső könyvtár nélkül)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Kommentek kihagyása
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Kulcs-érték párok feldolgozása
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Idézőjelek eltávolítása az érték elejéről és végéről
            if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
                $value = substr($value, 1, -1);
            } elseif (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1) {
                $value = substr($value, 1, -1);
            }

            // Csak akkor állítjuk be, ha még nincs beállítva (környezeti változók prioritása)
            if (!getenv($name)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// API Kulcsok
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: 'YOUR_GEMINI_API_KEY_HERE');
define('RECAPTCHA_SITE_KEY', getenv('RECAPTCHA_SITE_KEY') ?: '');
define('RECAPTCHA_SECRET_KEY', getenv('RECAPTCHA_SECRET_KEY') ?: '');

// Alkalmazás verzió
define('APP_VERSION', '1.1.5');

// Időzóna
date_default_timezone_set('Europe/Budapest');

// Logolás funkció
function writeLog($message) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        if (!mkdir($logDir, 0777, true)) {
            return; // Nem tudtunk könyvtárat létrehozni, csendben kilépünk
        }
    }
    $logFile = $logDir . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    // Objektum vagy tömb esetén olvasható formátum
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

// Utolsó módosítás: 2026. február 06. 17:16:00