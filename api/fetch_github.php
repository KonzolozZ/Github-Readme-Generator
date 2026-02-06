<?php
/**
 * API Végpont: GitHub Letöltés
 * Fájl helye: /api/fetch_github.php
 * Funkció: GitHub URL alapján letölti a fájlokat, reCAPTCHA ellenőrzéssel.
 */

ob_start();

header('Content-Type: application/json');
require_once '../config.php';
require_once '../classes/GitHubService.php';
require_once '../classes/RecaptchaService.php';

try {
    writeLog("GitHub fetch API called.");

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Csak POST kérés engedélyezett.");
    }

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    // 1. reCAPTCHA Ellenőrzés
    $recaptchaToken = $input['recaptcha_token'] ?? null;
    $recaptchaService = new RecaptchaService(RECAPTCHA_SECRET_KEY);
    
    if (!$recaptchaService->verify($recaptchaToken)) {
        throw new Exception("Robot ellenőrzés sikertelen (reCAPTCHA). Kérlek próbáld újra.");
    }

    // 2. Adatok feldolgozása
    if (!isset($input['url']) || empty($input['url'])) {
        throw new Exception("URL megadása kötelező.");
    }

    $url = trim($input['url']);
    writeLog("Fetching URL: " . $url);

    $gitHubService = new GitHubService();
    $files = $gitHubService->fetchRepo($url);

    writeLog("Fetch successful, file count: " . count($files));

    ob_clean();
    echo json_encode([
        'success' => true,
        'files' => $files
    ]);

} catch (Exception $e) {
    writeLog("Error in fetch_github.php: " . $e->getMessage());
    ob_clean();
    http_response_code(400); 
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Utolsó módosítás: 2026. február 06. 17:16:00