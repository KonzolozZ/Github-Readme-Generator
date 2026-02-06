<?php
/**
 * Gemini Service Osztály
 * Fájl helye: /classes/GeminiService.php
 * Funkció: Kommunikáció a Google Gemini API-val a README generálásához.
 * Verzió: 1.1.4 - GitHub URL kinyerése virtuális fájlból javítva.
 */

declare(strict_types=1);

class GeminiService
{
    private string $apiKey;
    private string $model = 'gemini-2.5-flash';
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private int $maxContentLength = 200000;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * README generálása a fájlok alapján
     */
    public function generateReadme(array $files): array
    {
        if (empty($files)) {
            throw new Exception("Nincsenek fájlok megadva a generáláshoz.");
        }

        // 1. Detektáljuk, hogy GitHub repóról van-e szó
        $isGithub = false;
        foreach ($files as $file) {
            if ($file['name'] === '__FILE_STRUCTURE_SUMMARY__') {
                $isGithub = true;
                break;
            }
        }

        // 2. Kinyerjük a Repó URL-t (virtuális fájlból vagy package.json-ból)
        $repoUrl = $this->extractRepoUrl($files);

        $filesSummary = $this->formatProjectFiles($files);
        $prompt = $this->generateReadmePrompt($filesSummary, $isGithub, $repoUrl);

        $response = $this->callGeminiApi($prompt);

        return $this->parseResponse($response);
    }

    /**
     * Repó URL kinyerése a fájlokból
     */
    private function extractRepoUrl(array $files): ?string
    {
        // 1. Virtuális fájl (GitHubService adja hozzá)
        foreach ($files as $file) {
            if ($file['name'] === '__GITHUB_URL__') {
                return trim($file['content']);
            }
        }

        // 2. package.json (Fallback, ha nem GitHubService-ből jött, de van package.json)
        foreach ($files as $file) {
            if ($file['name'] === 'package.json') {
                $data = json_decode($file['content'], true);
                if (json_last_error() === JSON_ERROR_NONE && isset($data['repository'])) {
                    $url = null;
                    if (is_string($data['repository'])) {
                        $url = $data['repository'];
                    } elseif (isset($data['repository']['url'])) {
                        $url = $data['repository']['url'];
                    }

                    if ($url) {
                        $url = preg_replace('/^git\+/', '', $url);
                        $url = preg_replace('/\.git$/', '', $url);
                        if (!str_starts_with($url, 'http')) {
                            $url = "https://github.com/" . $url;
                        }
                        return $url;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Fájlok formázása stringgé a prompt számára
     */
    private function formatProjectFiles(array $files): string
    {
        $combinedContent = "Project file structure:\n";
        $structureFound = false;
        
        // 1. lépés: Speciális fájlok kezelése
        foreach ($files as $key => $file) {
            if ($file['name'] === '__FILE_STRUCTURE_SUMMARY__') {
                $combinedContent .= $file['content'] . "\n\n";
                unset($files[$key]);
                $structureFound = true;
            }
            // Eltávolítjuk a URL fájlt a promptból, hogy ne zavarja az AI-t
            if ($file['name'] === '__GITHUB_URL__') {
                unset($files[$key]);
            }
        }

        // Ha nem volt külön struktúra fájl (pl. mappafeltöltés), generáljuk a listából
        if (!$structureFound) {
            $fileTree = [];
            foreach ($files as $file) {
                $fileTree[] = "- " . $file['path'];
            }
            $combinedContent .= implode("\n", $fileTree) . "\n\n";
        }

        $combinedContent .= "Key file contents:\n\n";

        // Prioritási lista
        $priorityFiles = [
            'package.json', 'pom.xml', 'build.gradle', 'requirements.txt', 'pyproject.toml',
            'Gemfile', 'composer.json', 'go.mod', 'Cargo.toml', 'docker-compose.yml', 'Dockerfile',
            'vite.config.ts', 'tsconfig.json', 'README.md'
        ];

        // Rendezés prioritás szerint
        usort($files, function ($a, $b) use ($priorityFiles) {
            $aName = $a['name'];
            $bName = $b['name'];
            $aIsPriority = in_array($aName, $priorityFiles);
            $bIsPriority = in_array($bName, $priorityFiles);

            if ($aIsPriority && !$bIsPriority) return -1;
            if (!$aIsPriority && $bIsPriority) return 1;
            return 0;
        });

        foreach ($files as $file) {
            // Tartalom tisztítása
            $content = str_replace(["\r\n", "\r"], "\n", $file['content']);

            if (strlen($combinedContent) + strlen($content) > $this->maxContentLength) {
                continue;
            }
            
            if ($this->isBinary($content)) {
                continue;
            }

            $combinedContent .= "--- FILE: {$file['path']} ---\n{$content}\n\n";
        }

        return $combinedContent;
    }

    /**
     * Prompt összeállítása
     */
    private function generateReadmePrompt(string $filesSummary, bool $isGithub, ?string $repoUrl): string
    {
        $headerInstruction = "";
        
        if ($isGithub) {
            $urlPlaceholder = $repoUrl ? $repoUrl : "https://github.com/USER/REPO";
            $headerInstruction = "
        6.  **GitHub Header:** Since this is a GitHub repository, START the content (before the Title) with the following specific navigation badges using the repository URL: `$urlPlaceholder`.
            Do NOT display the raw URL text \"Github link: ...\" at the top. Only use the URL to construct the links.
            Use the correct default branch (usually `main` or `master`).

            Format:
            ```markdown
            # README [EN/HU]
            [![en](https://img.shields.io/badge/lang-en-red.svg)]($urlPlaceholder/blob/main/README.md)
            [![hu](https://img.shields.io/badge/lang-hu-green.svg)]($urlPlaceholder/blob/main/README-HU.md)
            ```
            (Ensure the second link points to `README-HU.md`).
            ";
        }

        return "
        You are an expert software engineer specializing in creating professional and engaging GitHub README.md files.
        Your task is to analyze the following project files and generate a comprehensive README in TWO languages: English and Hungarian.

        **Instructions:**
        1.  **Analyze the Code:** Infer the project's purpose, main language, framework, and key dependencies from the file structure and content.
        2.  **Generate a Professional README:** The README should be well-structured, clear, and visually appealing.
        3.  **Bilingual JSON Output:** The output must be a valid JSON object with two keys: \"en\" (English) and \"hu\" (Hungarian). The values must be the complete Markdown content strings. Use `\\n` for newlines.
        4.  **Include Relevant Badges:** After the custom GitHub header (if applicable), add standard project badges (License, Language, etc.) under the Project Title.
        5.  **Structure and Embellish:** Structure the README with the following sections. Use relevant emojis for titles to make it more engaging.
            * **Project Title:** An H1 header for the project name.
            * **Description:** A short, compelling paragraph describing the project.
            * ✨ **Features / Funkciók:** A bulleted list of key features.
            * 📚 **Tech Stack / Technológia:** A list of the main technologies, frameworks, and libraries used.
            * 🚀 **Installation / Telepítés:** A step-by-step guide on how to get the development environment running. Include code blocks for commands.
            * ▶️ **Usage / Használat:** How to use the application. Provide code examples.
            * 🤝 **Contributing / Hozzájárulás:** A brief statement on how to contribute.
            * 📝 **License / Licenc:** State the project's license.
        {$headerInstruction}

        **Project Files Data:**
        {$filesSummary}
        ";
    }

    /**
     * Gemini API hívás curl segítségével
     */
    private function callGeminiApi(string $prompt): array
    {
        $url = $this->apiUrl . $this->model . ':generateContent?key=' . $this->apiKey;

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'en' => ['type' => 'STRING'],
                        'hu' => ['type' => 'STRING']
                    ],
                    'required' => ['en', 'hu']
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("Curl hiba: " . $error);
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            // Próbáljuk meg kinyerni a hibaüzenetet a válaszból
            $errorMsg = "API hiba (HTTP $httpCode)";
            $jsonResp = json_decode($response, true);
            if (isset($jsonResp['error']['message'])) {
                $errorMsg .= ": " . $jsonResp['error']['message'];
            }
            throw new Exception($errorMsg);
        }

        return json_decode($response, true);
    }

    /**
     * Válasz feldolgozása
     */
    private function parseResponse(array $response): array
    {
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $response['candidates'][0]['content']['parts'][0]['text'];
            $parsed = json_decode($text, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $parsed;
            } else {
                return [
                    'en' => "# Error parsing response\nRaw text: " . $text,
                    'hu' => "# Hiba a válasz feldolgozásakor\nNyers szöveg: " . $text
                ];
            }
        }
        
        throw new Exception("Nem sikerült választ kinyerni az API-ból.");
    }

    /**
     * Egyszerű bináris fájl ellenőrzés
     */
    private function isBinary(string $content): bool
    {
        return preg_match('~[^\x20-\x7E\t\r\n]~', substr($content, 0, 1000)) > 0;
    }
}

// Utolsó módosítás: 2026. február 06. 18:30:00