/**
 * Alkalmazás Logika
 * Fájl helye: /assets/js/app.js
 * Funkció: Kezeli a fájlfeltöltést, GitHub betöltést, UI interakciókat és a generálást.
 * Módosítva: URL tisztítás és javított JSON hibakezelés.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Állapotváltozók
    let loadedFiles = [];
    let generatedContent = { en: '', hu: '' };
    let currentResultLang = 'en';

    // Konfiguráció: Kihagyandó mappák és fájlméret limit
    const IGNORED_PATHS = ['node_modules', '.git', '.idea', '.vscode', 'dist', 'build', 'vendor', 'composer.lock', 'package-lock.json', 'yarn.lock'];
    const MAX_FILE_SIZE = 1024 * 1024; // 1MB limit

    // Elemek biztonságos lekérése
    const getEl = (id) => document.getElementById(id);

    const elements = {
        dropZone: getEl('dropZone'),
        fileInput: getEl('fileInput'),
        githubInputContainer: getEl('githubInputContainer'),
        uploadContainer: getEl('uploadContainer'),
        tabUpload: getEl('tabUpload'),
        tabGithub: getEl('tabGithub'),
        githubUrlInput: getEl('githubUrl'),
        fetchGithubBtn: getEl('fetchGithubBtn'),
        inputSection: getEl('inputSection'),
        fileListSection: getEl('fileListSection'),
        fileListDisplay: getEl('fileListDisplay'),
        fileCountBadge: getEl('fileCountBadge'),
        generateBtn: getEl('generateBtn'),
        backBtn: getEl('backBtn'),
        loadingSection: getEl('loadingSection'),
        loadingText: getEl('loadingText'),
        resultSection: getEl('resultSection'),
        readmePreview: getEl('readmePreview'),
        btnLangEn: getEl('btnLangEn'),
        btnLangHu: getEl('btnLangHu'),
        btnCopy: getEl('btnCopy'),
        btnDownload: getEl('btnDownload'),
        btnReset: getEl('btnReset')
    };

    // Ellenőrizzük, hogy a kritikus elemek megvannak-e
    if (!elements.fileInput || !elements.fileListSection) {
        console.error("Kritikus DOM elemek hiányoznak. Ellenőrizd az index.php szerkezetét.");
        return;
    }

    // --- Segédfüggvény: Bootstrap Hiba Modal ---
    function showError(message) {
        let modalEl = document.getElementById('appErrorModal');
        const titleText = window.langData ? window.langData.error_title : "Error";

        // Ha még nem létezik a modal a DOM-ban, létrehozzuk
        if (!modalEl) {
            const modalHTML = `
                <div class="modal fade" id="appErrorModal" tabindex="-1" aria-hidden="true" style="z-index: 10000;">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="background-color: #1e293b; color: #f8fafc; border: 1px solid #334155; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
                            <div class="modal-header" style="border-bottom: 1px solid #334155;">
                                <h5 class="modal-title text-danger fw-bold">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i><span id="appErrorTitle">${titleText}</span>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p id="appErrorBody" class="mb-0" style="color: #cbd5e1; font-size: 1rem; line-height: 1.5; word-break: break-word;"></p>
                            </div>
                            <div class="modal-footer" style="border-top: 1px solid #334155; padding: 0.75rem;">
                                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">OK</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            modalEl = document.getElementById('appErrorModal');
        }

        // Tartalom frissítése
        const modalTitle = document.getElementById('appErrorTitle');
        const modalBody = document.getElementById('appErrorBody');
        
        if (modalTitle) modalTitle.textContent = titleText;
        if (modalBody) modalBody.textContent = message;

        // Megjelenítés
        if (typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            // Fallback
            alert(message);
        }
    }

    // --- GitHub URL handling (Tisztítás beillesztéskor) ---
    if (elements.githubUrlInput) {
        elements.githubUrlInput.addEventListener('input', (e) => {
            let val = e.target.value;
            // Ha a felhasználó beillesztett egy teljes URL-t (pl. https://github.com/user/repo)
            // A UI-ban már van "https://" prefix, ezért le kell vágni.
            
            // 1. https:// vagy http:// levágása
            if (val.startsWith('https://')) {
                val = val.substring(8);
            } else if (val.startsWith('http://')) {
                val = val.substring(7);
            }
            
            // Ha most már úgy néz ki, hogy "github.com/...", azt is levághatnánk,
            // de a placeholder szerint "github.com/user/repo" a várt formátum, 
            // így a user/repo részre van szükségünk, de a backend kezeli a github.com-ot is.
            // A kérés az volt, hogy "automatikusan vágja le a https:// előtagot".
            
            if (val !== e.target.value) {
                e.target.value = val;
            }
        });
    }

    // --- Tab Kezelés ---
    if (elements.tabUpload && elements.tabGithub) {
        elements.tabUpload.addEventListener('click', () => switchTab('upload'));
        elements.tabGithub.addEventListener('click', () => switchTab('github'));
    }

    function switchTab(mode) {
        if (!elements.uploadContainer || !elements.githubInputContainer) return;
        
        if (mode === 'upload') {
            elements.tabUpload.classList.add('btn-primary-custom');
            elements.tabUpload.classList.remove('btn-outline-custom');
            elements.tabGithub.classList.remove('btn-primary-custom');
            elements.tabGithub.classList.add('btn-outline-custom');
            elements.uploadContainer.classList.remove('d-none');
            elements.githubInputContainer.classList.add('d-none');
        } else {
            elements.tabGithub.classList.add('btn-primary-custom');
            elements.tabGithub.classList.remove('btn-outline-custom');
            elements.tabUpload.classList.remove('btn-primary-custom');
            elements.tabUpload.classList.add('btn-outline-custom');
            elements.githubInputContainer.classList.remove('d-none');
            elements.uploadContainer.classList.add('d-none');
        }
    }

    // --- Drag & Drop ---
    if (elements.dropZone) {
        elements.dropZone.addEventListener('click', () => elements.fileInput.click());

        ['dragenter', 'dragover'].forEach(eventName => {
            elements.dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                elements.dropZone.classList.add('drag-active');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            elements.dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                elements.dropZone.classList.remove('drag-active');
            }, false);
        });

        elements.dropZone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            handleFiles(files);
        });
    }

    if (elements.fileInput) {
        elements.fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });
    }

    // --- Fájl Feldolgozás ---
    async function handleFiles(fileList) {
        if (!fileList || fileList.length === 0) return;

        showLoading(true, window.langData ? window.langData.processing : "Feldolgozás...");

        // setTimeout használata, hogy a UI frissülhessen a nehéz művelet előtt
        setTimeout(async () => {
            try {
                loadedFiles = [];
                const filesArray = Array.from(fileList);
                
                // Szűrés
                const relevantFiles = filesArray.filter(file => {
                    const path = (file.webkitRelativePath || file.name).replace(/\\/g, '/');
                    const isIgnored = IGNORED_PATHS.some(ignore => path.includes('/' + ignore + '/') || path.startsWith(ignore + '/'));
                    const isTooLarge = file.size > MAX_FILE_SIZE;
                    const isBinary = /\.(png|jpg|jpeg|gif|ico|pdf|zip|tar|gz|exe|dll|so|dylib|bin|class|jar)$/i.test(path);
                    return !isIgnored && !isTooLarge && !isBinary;
                });

                if (relevantFiles.length === 0) {
                    throw new Error("Nem találtam feldolgozható szöveges fájlt (vagy minden fájl túl nagy/tiltott mappában van).");
                }

                const promises = relevantFiles.map(file => {
                    return new Promise((resolve) => {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            resolve({
                                name: file.name,
                                path: file.webkitRelativePath || file.name,
                                content: e.target.result
                            });
                        };
                        reader.onerror = () => resolve(null); 
                        reader.readAsText(file);
                    });
                });

                const results = await Promise.all(promises);
                loadedFiles = results.filter(f => f !== null);

                if (loadedFiles.length === 0) {
                    throw new Error("Sikertelen fájlolvasás.");
                }

                showLoading(false);
                displayFiles();
            } catch (error) {
                console.error("Fájlkezelési hiba:", error);
                showError("Hiba: " + error.message);
                showLoading(false);
                restoreInputView();
            }
            if (elements.fileInput) elements.fileInput.value = '';
        }, 100);
    }

    // --- GitHub Fetch ---
    if (elements.fetchGithubBtn) {
        elements.fetchGithubBtn.addEventListener('click', async () => {
            const url = elements.githubUrlInput ? elements.githubUrlInput.value.trim() : '';
            if (!url) {
                showError("Kérlek adj meg egy URL-t!");
                return;
            }

            showLoading(true, window.langData ? window.langData.generating : "Letöltés...");

            try {
                const response = await fetch('api/fetch_github.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ url: url })
                });

                // Először szövegként olvassuk ki, hogy lássuk a PHP hibát, ha nem JSON
                const textResponse = await response.text();
                
                try {
                    const data = JSON.parse(textResponse);
                    if (data.success) {
                        loadedFiles = data.files;
                        showLoading(false);
                        displayFiles();
                    } else {
                        throw new Error(data.message || "Ismeretlen hiba történt.");
                    }
                } catch (jsonError) {
                    // Ha a JSON parse elhasal, valószínűleg PHP fatal error vagy warning került a kimenetre
                    console.error("Nem valid JSON válasz:", textResponse);
                    // Levágjuk a hibaüzenetet, ha túl hosszú
                    const displayError = textResponse.length > 300 ? textResponse.substring(0, 300) + "..." : textResponse;
                    throw new Error("Szerver hiba (nem JSON válasz): " + displayError);
                }

            } catch (error) {
                console.error("GitHub fetch hiba:", error);
                showError((window.langData ? window.langData.error_github_fetch : "Hiba") + "\n" + error.message);
                showLoading(false);
                restoreInputView();
            }
        });
    }

    // --- Megjelenítés ---
    function displayFiles() {
        if (elements.inputSection) elements.inputSection.classList.add('d-none');
        if (elements.fileListSection) elements.fileListSection.classList.remove('d-none');
        
        if (elements.fileCountBadge) elements.fileCountBadge.textContent = `${loadedFiles.length} files`;
        
        let html = '';
        const displayLimit = 100;
        loadedFiles.slice(0, displayLimit).forEach(file => {
            html += `
                <div class="file-item">
                    <i class="bi bi-file-earmark-code me-3 text-secondary"></i>
                    <span class="text-secondary-custom small text-truncate" title="${file.path}">${file.path}</span>
                </div>
            `;
        });
        
        if (loadedFiles.length > displayLimit) {
            html += `<div class="text-center text-secondary p-2">...és még ${loadedFiles.length - displayLimit} fájl.</div>`;
        }

        if (elements.fileListDisplay) elements.fileListDisplay.innerHTML = html;
    }

    function restoreInputView() {
        if (elements.inputSection) elements.inputSection.classList.remove('d-none');
        if (elements.uploadContainer && elements.uploadContainer.parentElement) {
            elements.uploadContainer.parentElement.classList.remove('d-none');
        }
    }

    // --- Vissza gomb ---
    if (elements.backBtn) {
        elements.backBtn.addEventListener('click', () => {
            if (elements.fileListSection) elements.fileListSection.classList.add('d-none');
            restoreInputView();
            loadedFiles = [];
        });
    }

    // --- Generálás ---
    if (elements.generateBtn) {
        elements.generateBtn.addEventListener('click', async () => {
            if (loadedFiles.length === 0) {
                showError(window.langData ? window.langData.error_no_files : "Nincs fájl kiválasztva!");
                return;
            }

            showLoading(true, window.langData ? window.langData.generating : "Generálás...");
            if (elements.fileListSection) elements.fileListSection.classList.add('d-none');

            try {
                const response = await fetch('api/generate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ files: loadedFiles })
                });

                const textResponse = await response.text();
                let resData;

                try {
                    resData = JSON.parse(textResponse);
                } catch (e) {
                     const displayError = textResponse.length > 300 ? textResponse.substring(0, 300) + "..." : textResponse;
                     throw new Error("Szerver hiba (Generate - nem JSON): " + displayError);
                }

                if (resData.success) {
                    generatedContent = resData.data;
                    showLoading(false);
                    showResult();
                } else {
                    throw new Error(resData.message);
                }

            } catch (error) {
                console.error("Generálási hiba:", error);
                
                let errorMsg = error.message;
                if (errorMsg.includes("API key not valid") || errorMsg.includes("API_KEY_INVALID")) {
                    const isHu = document.documentElement.lang === 'hu';
                    errorMsg = isHu 
                        ? "Érvénytelen API kulcs! A rendszer nem tudott kommunikálni az AI-val. Kérlek ellenőrizd a 'config.php' fájlban a 'GEMINI_API_KEY' beállítást." 
                        : "Invalid API Key! The system could not communicate with the AI. Please check the 'GEMINI_API_KEY' setting in 'config.php'.";
                }

                showError(errorMsg);
                showLoading(false);
                if (elements.fileListSection) elements.fileListSection.classList.remove('d-none');
            }
        });
    }

    function showResult() {
        if (elements.resultSection) elements.resultSection.classList.remove('d-none');
        renderMarkdown('en');
    }

    function renderMarkdown(lang) {
        currentResultLang = lang;
        const content = generatedContent[lang] || "Nincs elérhető tartalom.";
        
        if (elements.btnLangEn && elements.btnLangHu) {
            if (lang === 'en') {
                elements.btnLangEn.classList.add('active', 'bg-primary-custom', 'text-white');
                elements.btnLangEn.classList.remove('text-secondary-custom');
                elements.btnLangHu.classList.remove('active', 'bg-primary-custom', 'text-white');
                elements.btnLangHu.classList.add('text-secondary-custom');
            } else {
                elements.btnLangHu.classList.add('active', 'bg-primary-custom', 'text-white');
                elements.btnLangHu.classList.remove('text-secondary-custom');
                elements.btnLangEn.classList.remove('active', 'bg-primary-custom', 'text-white');
                elements.btnLangEn.classList.add('text-secondary-custom');
            }
        }

        if (elements.readmePreview) {
            if (typeof marked !== 'undefined') {
                elements.readmePreview.innerHTML = marked.parse(content);
            } else {
                elements.readmePreview.innerHTML = `<pre>${content}</pre>`;
            }
        }
    }

    if (elements.btnLangEn) elements.btnLangEn.addEventListener('click', (e) => { e.preventDefault(); renderMarkdown('en'); });
    if (elements.btnLangHu) elements.btnLangHu.addEventListener('click', (e) => { e.preventDefault(); renderMarkdown('hu'); });

    // --- Másolás ---
    if (elements.btnCopy) {
        elements.btnCopy.addEventListener('click', () => {
            navigator.clipboard.writeText(generatedContent[currentResultLang]);
            const icon = elements.btnCopy.querySelector('i');
            const textSpan = elements.btnCopy.querySelector('span');
            
            if (textSpan) {
                const originalText = textSpan.textContent;
                textSpan.textContent = window.langData ? window.langData.copied : "Másolva!";
                if (icon) icon.className = 'bi bi-check2-circle text-success-custom';
                
                setTimeout(() => {
                    if (icon) icon.className = 'bi bi-clipboard';
                    textSpan.textContent = originalText;
                }, 2000);
            }
        });
    }

    // --- Letöltés ---
    if (elements.btnDownload) {
        elements.btnDownload.addEventListener('click', () => {
            const blob = new Blob([generatedContent[currentResultLang]], { type: 'text/markdown;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = currentResultLang === 'hu' ? 'OLVASSEL.md' : 'README.md';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
    }

    // --- Újrakezdés ---
    if (elements.btnReset) {
        elements.btnReset.addEventListener('click', () => {
            if (elements.resultSection) elements.resultSection.classList.add('d-none');
            restoreInputView();
            loadedFiles = [];
            generatedContent = { en: '', hu: '' };
            if (elements.fileInput) elements.fileInput.value = '';
            if (elements.githubUrlInput) elements.githubUrlInput.value = '';
            switchTab('upload');
        });
    }

    function showLoading(show, text = 'Loading...') {
        if (!elements.loadingSection || !elements.loadingText) return;
        
        if (show) {
            elements.loadingText.textContent = text;
            elements.loadingSection.classList.remove('d-none');
            if (elements.inputSection) elements.inputSection.classList.add('d-none');
            if (elements.fileListSection) elements.fileListSection.classList.add('d-none');
        } else {
            elements.loadingSection.classList.add('d-none');
        }
    }
});

// Utolsó módosítás: 2026. február 06. 16:30:00