<?php
/**
 * API Végpont: GitHub Letöltés
 * Fájl helye: /api/fetch_github.php
 * Funkció: GitHub URL alapján letölti a fájlokat és visszaadja a frontendnek.
 */

// Kimenet pufferelés indítása, hogy elkapjuk a PHP warningokat
ob_start();

header('Content-Type: application/json');
require_once '../config.php';
require_once '../classes/GitHubService.php';

try {
    writeLog("GitHub fetch API called.");

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Csak POST kérés engedélyezett.");
    }

    $rawInput = file_get_contents('php://input');
    writeLog("Raw input: " . $rawInput);

    $input = json_decode($rawInput, true);

    if (!isset($input['url']) || empty($input['url'])) {
        throw new Exception("URL megadása kötelező.");
    }

    $url = trim($input['url']);
    writeLog("Fetching URL: " . $url);

    $gitHubService = new GitHubService();
    $files = $gitHubService->fetchRepo($url);

    writeLog("Fetch successful, file count: " . count($files));

    // Puffer törlése és JSON küldése
    ob_clean();
    echo json_encode([
        'success' => true,
        'files' => $files
    ]);

} catch (Exception $e) {
    writeLog("Error in fetch_github.php: " . $e->getMessage());
    
    // Puffer törlése és Hiba JSON küldése
    ob_clean();
    http_response_code(400); 
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Utolsó módosítás: 2026. február 06. 16:30:00