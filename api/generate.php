<?php
/**
 * API Végpont: Generálás
 * Fájl helye: /api/generate.php
 * Funkció: Fogadja a fájlokat JSON formátumban és meghívja a Gemini service-t, reCAPTCHA ellenőrzéssel.
 */

ob_start();

header('Content-Type: application/json');
require_once '../config.php';
require_once '../classes/GeminiService.php';
require_once '../classes/RecaptchaService.php';

try {
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

    // 2. Adatok ellenőrzése
    if (!isset($input['files']) || !is_array($input['files'])) {
        throw new Exception("Érvénytelen adatszerkezet. 'files' tömb szükséges.");
    }

    $files = $input['files'];

    // 3. Service hívás
    $geminiService = new GeminiService(GEMINI_API_KEY);
    $result = $geminiService->generateReadme($files);

    ob_clean();
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);

} catch (Exception $e) {
    writeLog("Error in generate.php: " . $e->getMessage());
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Utolsó módosítás: 2026. február 06. 17:16:00