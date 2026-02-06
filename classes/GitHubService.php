<?php
/**
 * GitHub Service Osztály
 * Fájl helye: /classes/GitHubService.php
 * Funkció: Fájlok lekérése és feldolgozása GitHub repóból.
 * Verzió: 1.1.0 - Formázás egységesítése a mappás feltöltéssel.
 */

declare(strict_types=1);

class GitHubService
{
    private int $maxFiles = 15;
    private array $priorityFiles = [
        'package.json', 'pom.xml', 'build.gradle', 'requirements.txt', 'pyproject.toml',
        'Gemfile', 'composer.json', 'go.mod', 'Cargo.toml', 'docker-compose.yml', 'Dockerfile',
        'README.md', 'LICENSE', 'tsconfig.json', 'vite.config.ts', 'next.config.js', 'index.php', 'composer.lock', 'artisan'
    ];

    /**
     * Repo fájlok lekérése URL alapján
     */
    public function fetchRepo(string $repoUrl): array
    {
        writeLog("GitHubService::fetchRepo started with: " . $repoUrl);

        // URL tisztítása és validálása
        $owner = '';
        $repo = '';

        $cleanUrl = preg_replace('#^https?://(www\.)?#', '', $repoUrl);
        $cleanUrl = str_replace('.git', '', $cleanUrl);

        if (strpos($cleanUrl, 'github.com/') === 0) {
            $parts = explode('/', substr($cleanUrl, 11));
        } else {
            $parts = explode('/', $cleanUrl);
        }

        $parts = array_filter($parts);
        $parts = array_values($parts);

        if (count($parts) >= 2) {
            $owner = $parts[0];
            $repo = $parts[1];
        } else {
            throw new Exception("Érvénytelen formátum. Helyes formátum: user/repo vagy https://github.com/user/repo");
        }

        writeLog("Parsed Owner: $owner, Repo: $repo");

        // 1. Alapértelmezett branch lekérése
        $repoInfoUrl = "https://api.github.com/repos/$owner/$repo";
        $repoData = $this->callGitHubApi($repoInfoUrl);
        
        if (!isset($repoData['default_branch'])) {
             throw new Exception("Nem sikerült lekérni a repó adatait (default_branch).");
        }
        
        $defaultBranch = $repoData['default_branch'];
        writeLog("Default branch: $defaultBranch");

        // 2. Fájl fa lekérése (rekurzív)
        $treeUrl = "https://api.github.com/repos/$owner/$repo/git/trees/$defaultBranch?recursive=1";
        $treeData = $this->callGitHubApi($treeUrl);
        
        if (!isset($treeData['tree'])) {
            throw new Exception("Nem sikerült lekérni a fájlstruktúrát. (Lehet, hogy a repó üres?)");
        }

        // Csak a fájlokat (blob) tartsuk meg
        $allFiles = array_filter($treeData['tree'], function($item) {
            return $item['type'] === 'blob';
        });

        // 3. Fájlok kiválasztása fontosság szerint
        $selectedFiles = $this->prioritizeFiles($allFiles);
        writeLog("Selected " . count($selectedFiles) . " priority files out of " . count($allFiles) . " total.");

        // 4. Tartalom letöltése
        $processedFiles = [];
        foreach ($selectedFiles as $file) {
            $rawUrl = "https://raw.githubusercontent.com/$owner/$repo/$defaultBranch/" . $file['path'];
            $content = $this->fetchRawContent($rawUrl);
            
            if ($content !== null) {
                // Bináris szűrés (null byte)
                if (strpos($content, "\0") !== false) {
                     writeLog("Skipping binary file: " . $file['path']);
                     continue;
                }

                $processedFiles[] = [
                    'name' => basename($file['path']),
                    'path' => $file['path'],
                    'content' => $content
                ];
            } else {
                writeLog("Failed to fetch content for: " . $file['path']);
            }
        }

        // Hozzáadunk egy virtuális fájlt a teljes struktúrával
        // FONTOS: Mostantól listajeles (- ) formátumban, hogy az AI ugyanazt lássa, mint mappás feltöltésnél!
        if (count($allFiles) > 0) {
            $paths = array_column($allFiles, 'path');
            
            // Limitálás
            if (count($paths) > 500) {
                $paths = array_slice($paths, 0, 500);
                $paths[] = "... (és további " . (count($allFiles) - 500) . " fájl)";
            }
            
            // Formázás javítása: minden sor elé kötőjel
            $formattedPaths = array_map(function($path) {
                return strpos($path, '...') === 0 ? $path : "- " . $path;
            }, $paths);
            
            $processedFiles[] = [
                'name' => '__FILE_STRUCTURE_SUMMARY__',
                'path' => '__FILE_STRUCTURE_SUMMARY__',
                'content' => implode("\n", $formattedPaths)
            ];
        }

        return $processedFiles;
    }

    private function callGitHubApi(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'GitHub-Readme-Generator-PHP');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            writeLog("Curl error calling $url: $error");
            throw new Exception("Hálózati hiba a GitHub elérésekor.");
        }

        if ($httpCode === 404) {
            throw new Exception("A GitHub repó nem található vagy privát ($url).");
        }

        if ($httpCode === 403) {
             throw new Exception("GitHub API rate limit túllépés.");
        }

        if ($httpCode !== 200) {
            writeLog("GitHub API error $httpCode for $url. Response: $response");
            throw new Exception("GitHub API hiba: $httpCode.");
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Hibás JSON válasz a GitHub-tól.");
        }

        return $decoded;
    }

    private function fetchRawContent(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'GitHub-Readme-Generator-PHP');
        
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        return $content;
    }

    private function prioritizeFiles(array $files): array
    {
        usort($files, function($a, $b) {
            $aName = basename($a['path']);
            $bName = basename($b['path']);
            $aP = in_array($aName, $this->priorityFiles);
            $bP = in_array($bName, $this->priorityFiles);

            if ($aP && !$bP) return -1;
            if (!$aP && $bP) return 1;
            return 0;
        });

        return array_slice($files, 0, $this->maxFiles);
    }
}

// Utolsó módosítás: 2026. február 06. 17:00:00