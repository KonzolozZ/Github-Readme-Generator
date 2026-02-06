<?php
/**
 * API Végpont: Generálás
 * Fájl helye: /api/generate.php
 * Funkció: Fogadja a fájlokat JSON formátumban és meghívja a Gemini service-t.
 */

header('Content-Type: application/json');
require_once '../config.php';
require_once '../classes/GeminiService.php';

try {
    // Csak POST kérést fogadunk
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Csak POST kérés engedélyezett.");
    }

    // JSON body beolvasása
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['files']) || !is_array($input['files'])) {
        throw new Exception("Érvénytelen adatszerkezet. 'files' tömb szükséges.");
    }

    $files = $input['files'];

    // Service példányosítása és hívása
    $geminiService = new GeminiService(GEMINI_API_KEY);
    $result = $geminiService->generateReadme($files);

    echo json_encode([
        'success' => true,
        'data' => $result
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Utolsó módosítás: 2026. február 06. 14:47:00