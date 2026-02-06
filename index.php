<?php
/**
 * Főoldal
 * Fájl helye: /index.php
 * Funkció: A felhasználói felület megjelenítése nyelvválasztással.
 */

require_once 'config.php';
$lang = require 'lang.php';

// Nyelv kezelése GET paraméterből
$currentLang = isset($_GET['lang']) && array_key_exists($_GET['lang'], $lang) ? $_GET['lang'] : 'hu';
$t = $lang[$currentLang];
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['title'] ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Fira+Code&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <!-- JavaScript adatok átadása -->
    <script>
        window.langData = <?= json_encode($t) ?>;
    </script>
</head>
<body>

    <!-- Navbar / Language Switcher & Support -->
    <nav class="container pt-4 pb-2">
        <div class="d-flex justify-content-end gap-3 align-items-center">
            <!-- Support Button -->
            <a href="https://buymeacoffee.com/pandafix" target="_blank" class="btn btn-sm btn-dark border border-secondary text-white fw-bold rounded-pill px-3 d-flex align-items-center transition-all hover-scale" style="text-decoration: none;">
                <img src="https://cdn.buymeacoffee.com/buttons/bmc-new-btn-logo.svg" alt="Buy me a coffee" style="height: 16px;" class="me-2">
                <span><?= $t['support'] ?></span>
            </a>

            <!-- Language Switcher -->
            <div class="lang-switcher bg-surface rounded-pill p-1 border border-secondary d-inline-flex">
                <a href="?lang=hu" class="btn btn-sm rounded-pill <?= $currentLang === 'hu' ? 'btn-primary-custom' : 'text-secondary-custom' ?>">
                    HU
                </a>
                <a href="?lang=en" class="btn btn-sm rounded-pill <?= $currentLang === 'en' ? 'btn-primary-custom' : 'text-secondary-custom' ?>">
                    EN
                </a>
            </div>
        </div>
    </nav>

    <div class="container d-flex flex-column justify-content-center align-items-center py-4" style="min-height: 80vh;">
        
        <!-- Header -->
        <header class="text-center mb-5">
            <div class="mb-3">
                <i class="bi bi-markdown-fill" style="font-size: 3rem; color: var(--primary-color);"></i>
            </div>
            <h1 class="display-5 fw-bold text-white mb-2">
                <?= $t['title'] ?>
            </h1>
            <p class="lead text-secondary-custom mx-auto" style="max-width: 600px;">
                <?= $t['subtitle'] ?>
            </p>
        </header>

        <!-- Main Content Area -->
        <main class="w-100" style="max-width: 850px;">
            
            <!-- Input Section (Upload / GitHub) -->
            <div id="inputSection">
                <!-- Tabs -->
                <div class="d-flex justify-content-center mb-4 gap-3">
                    <button id="tabUpload" class="btn btn-primary-custom px-4 rounded-3 shadow-sm">
                        <i class="bi bi-folder2-open me-2"></i><?= $t['tab_upload'] ?>
                    </button>
                    <button id="tabGithub" class="btn btn-outline-custom px-4 rounded-3">
                        <i class="bi bi-github me-2"></i><?= $t['tab_github'] ?>
                    </button>
                </div>

                <!-- Upload Container -->
                <div id="uploadContainer" class="drop-zone p-5 rounded-4 text-center shadow-lg">
                    <input type="file" id="fileInput" class="d-none" multiple webkitdirectory directory>
                    <div class="mb-4 p-4 rounded-circle bg-surface d-inline-block shadow-inner">
                        <i class="bi bi-cloud-arrow-up-fill text-primary-custom" style="font-size: 3.5rem;"></i>
                    </div>
                    <h3 class="h4 fw-bold text-white mb-2"><?= $t['drag_drop'] ?></h3>
                    <p class="text-secondary-custom mb-4"><?= $t['or_click'] ?></p>
                    
                    <button class="btn btn-primary-custom px-5 py-2 rounded-pill" onclick="document.getElementById('fileInput').click()">
                        <?= $t['select_folder'] ?>
                    </button>
                    
                    <div class="mt-4 pt-3 border-top border-secondary">
                        <small class="text-secondary-custom">
                            <i class="bi bi-shield-lock me-1"></i> <?= $t['processing'] ?>
                        </small>
                    </div>
                </div>

                <!-- GitHub Container -->
                <div id="githubInputContainer" class="p-5 rounded-4 bg-surface shadow-lg d-none border border-secondary">
                    <label for="githubUrl" class="form-label text-white fw-semibold mb-3">
                        <i class="bi bi-link-45deg me-2"></i><?= $t['enter_url'] ?>
                    </label>
                    <div class="input-group input-group-lg mb-3">
                        <span class="input-group-text bg-dark border-secondary text-secondary">https://</span>
                        <input type="text" class="form-control bg-dark text-white border-secondary" id="githubUrl" placeholder="github.com/user/repo">
                    </div>
                    <div class="d-grid">
                         <button class="btn btn-primary-custom btn-lg" type="button" id="fetchGithubBtn">
                            <?= $t['fetch_btn'] ?> <i class="bi bi-arrow-right ms-2"></i>
                         </button>
                    </div>
                    <p class="text-secondary-custom small mt-3 mb-0 text-center"><?= $t['example_url'] ?></p>
                </div>
            </div>

            <!-- Loading Section -->
            <div id="loadingSection" class="text-center d-none py-5">
                <div class="spinner-border text-primary-custom mb-4" role="status"></div>
                <h4 id="loadingText" class="text-white fw-light"><?= $t['generating'] ?></h4>
            </div>

            <!-- File List Section (Before Generation) -->
            <div id="fileListSection" class="bg-surface p-4 rounded-4 shadow-lg border border-secondary d-none">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 fw-bold text-white mb-0">
                        <i class="bi bi-file-earmark-text me-2 text-primary-custom"></i><?= $t['files_loaded'] ?>
                    </h3>
                    <span class="badge bg-dark border border-secondary text-secondary-custom rounded-pill" id="fileCountBadge">0 files</span>
                </div>
                
                <div id="fileListDisplay" class="file-list-container mb-4 pe-2">
                    <!-- Files will be injected here -->
                </div>
                
                <div class="d-flex gap-3">
                    <button id="backBtn" class="btn btn-outline-custom flex-grow-0 px-4">
                        <?= $t['back_btn'] ?>
                    </button>
                    <button id="generateBtn" class="btn btn-primary-custom flex-grow-1">
                        <i class="bi bi-stars me-2"></i> <?= $t['generate_btn'] ?>
                    </button>
                </div>
            </div>

            <!-- Result Section -->
            <div id="resultSection" class="d-none w-100">
                <!-- Toolbar -->
                <div class="bg-surface rounded-top-4 border border-bottom-0 border-secondary p-3 d-flex justify-content-between align-items-center">
                    <div class="nav nav-pills" id="langTabs" role="tablist">
                         <button id="btnLangEn" class="nav-link active px-3 py-1 me-2" data-bs-toggle="pill">English</button>
                         <button id="btnLangHu" class="nav-link px-3 py-1" data-bs-toggle="pill">Magyar</button>
                    </div>
                    <div class="d-flex gap-2">
                        <button id="btnCopy" class="btn btn-sm btn-outline-custom" title="<?= $t['copy_btn'] ?>">
                            <i class="bi bi-clipboard"></i> <span class="d-none d-sm-inline ms-1"><?= $t['copy_btn'] ?></span>
                        </button>
                        <button id="btnDownload" class="btn btn-sm btn-outline-custom" title="<?= $t['download_btn'] ?>">
                            <i class="bi bi-download"></i> <span class="d-none d-sm-inline ms-1"><?= $t['download_btn'] ?></span>
                        </button>
                    </div>
                </div>

                <!-- Preview Area -->
                <div id="readmePreview" class="markdown-preview p-5 rounded-bottom-4 shadow-lg" style="min-height: 500px;">
                    <!-- Markdown content goes here -->
                </div>

                <div class="text-center mt-5">
                    <button id="btnReset" class="btn btn-outline-custom px-5 rounded-pill">
                        <i class="bi bi-arrow-counterclockwise me-2"></i> <?= $t['back_btn'] ?>
                    </button>
                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer class="mt-5 text-center text-secondary-custom">
            <p class="small opacity-75 mb-0"><?= $t['footer'] ?></p>
        </footer>

    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
<!-- Utolsó módosítás: 2026. február 06. 15:07:00 -->