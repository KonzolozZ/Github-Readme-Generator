# README EN/HU
[![en](https://img.shields.io/badge/lang-en-red.svg)](https://github.com/KonzolozZ/Github-Readme-Generator/blob/main/README.md)
[![hu](https://img.shields.io/badge/lang-hu-green.svg)](https://github.com/KonzolozZ/Github-Readme-Generator/blob/main/README-HU.md)

# GitHub Olvasdel Generátor
[![Licenc: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Verzió](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://www.php.net/)
[![Legfőbb Nyelv](https://img.shields.io/github/languages/top/KonzolozZ/Github-Readme-Generator?color=blue)](https://github.com/KonzolozZ/Github-Readme-Generator)

Ez a projekt egy web alapú alkalmazás, melynek célja a professzionális és vonzó GitHub README fájlok létrehozásának egyszerűsítése. Az AI (Google Gemini) és a GitHub API erejét kihasználva segít a fejlesztőknek átfogó projekt dokumentációt generálni könnyedén, miközben a szolgáltatást Google reCAPTCHA védi.

✨ Funkciók
*   **AI-vezérelt tartalomgenerálás:** Google Gemini-t használ a leíró és strukturált README tartalom generálásához.
*   **GitHub integráció:** Közvetlenül a GitHub-ról lekéri a tároló részleteit, hogy alapul szolgáljon a README generáláshoz.
*   **Intuitív webes felület:** Felhasználóbarát webes alkalmazás a zökkenőmentes interakcióért.
*   **Biztonság reCAPTCHA-val:** Google reCAPTCHA segítségével védi a szolgáltatást a spam és visszaélések ellen.
*   **Átfogó szakaszok generálása:** Részletes és jól strukturált README szakaszokat hoz létre, amelyek különböző projektekhez alkalmasak.

📚 Technológia
*   **Backend:** PHP
*   **Frontend:** HTML, CSS, JavaScript
*   **AI szolgáltatás:** Google Gemini API
*   **Adatlekérés:** GitHub API
*   **Biztonság:** Google reCAPTCHA

🚀 Telepítés

A projekt helyi futtatásához kövesse az alábbi lépéseket:

1.  **Klónozza a tárolót:**
    ```bash
    git clone https://github.com/KonzolozZ/Github-Readme-Generator.git
    cd Github-Readme-Generator
    ```

2.  **Környezeti változók konfigurálása:**
    Hozzon létre egy `.env` fájlt a projekt gyökerében, és adja hozzá az API kulcsait és a projekt részleteit. Az `.env.example` fájl nem adott, ezért használja a feladatleírásban szereplő `.env` fájlban található kulcsokat.
    ```
    GEMINI_API_KEY=az_ön_gemini_api_kulcsa
    APP_NAME="Github Olvasdel Generátor"
    GOOGLE_PROJECT_NAME="projects/az_ön_google_projekt_száma"
    GOOGLE_PROJECT_NUMBER=az_ön_google_projekt_száma
    RECAPTCHA_SITE_KEY=az_ön_recaptcha_oldal_kulcsa
    RECAPTCHA_SECRET_KEY=az_ön_recaptcha_titkos_kulcsa
    ```
    *Cserélje ki a helyőrzőket a Google Cloud és reCAPTCHA szolgáltatásoktól kapott tényleges API kulcsaira és projekt részleteire.*

3.  **Webszerver beállítása:**
    Győződjön meg róla, hogy rendelkezik egy webszerverrel (pl. Apache, Nginx), amely konfigurálva van PHP alkalmazások futtatására. Irányítsa a dokumentum gyökerét a `Github-Readme-Generator` könyvtárba.
    Győződjön meg róla, hogy a PHP 8.1+ telepítve és megfelelően konfigurálva van a webszerverével.

4.  **Az alkalmazás elérése:**
    Nyissa meg a böngészőjét, és navigáljon arra az URL-re, ahol a webszerver az alkalmazást hosztolja (pl. `http://localhost/` vagy `http://az-ön-domainje.com/`).

▶️ Használat

Amint az alkalmazás fut, egyszerűen:

1.  **Nyissa meg az alkalmazást** a webböngészőjében.
2.  **Adja meg a GitHub tároló URL-jét**, amelyhez README-t szeretne generálni.
3.  **Kattintson a "README generálása" gombra** (vagy hasonló, az alkalmazás nevéből következtetve).
4.  Az alkalmazás AI-t fog használni a tároló információinak feldolgozására és egy jól strukturált README-t generál, amelyet áttekinthet és testreszabhat.

🤝 Hozzájárulás

A hozzájárulások szívesen látottak! Ha javaslatai vannak a fejlesztésekre vagy új funkciókra, kérjük, nyisson egy hibajegyet vagy küldjön be egy pull requestet.

📝 Licenc

Ez a projekt az MIT licenc alatt van licencelve - a részletekért tekintse meg a [LICENSE](https://github.com/KonzolozZ/Github-Readme-Generator/blob/main/LICENSE) fájlt.