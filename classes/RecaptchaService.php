<?php
/**
 * Recaptcha Service Osztály
 * Fájl helye: /classes/RecaptchaService.php
 * Funkció: Google reCAPTCHA token validálása szerver oldalon.
 */

declare(strict_types=1);

class RecaptchaService
{
    private string $secretKey;
    private string $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';

    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * Token ellenőrzése
     * @param string|null $token A klienstől kapott token
     * @return bool Igaz, ha valid, különben hamis
     */
    public function verify(?string $token): bool
    {
        if (empty($token)) {
            writeLog("Recaptcha hiba: Üres token.");
            return false;
        }

        if (empty($this->secretKey)) {
            writeLog("Recaptcha hiba: Hiányzó secret key a configban.");
            // Ha nincs beállítva kulcs, fejlesztési módban átengedhetjük, 
            // de biztonsági okokból inkább false.
            return false;
        }

        $postData = http_build_query([
            'secret' => $this->secretKey,
            'response' => $token
        ]);

        $ch = curl_init($this->verifyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        // SSL ellenőrzés kikapcsolása csak végső esetben (fejlesztéshez)
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            writeLog("Recaptcha Curl hiba: " . curl_error($ch));
            curl_close($ch);
            return false;
        }
        curl_close($ch);

        if ($httpCode !== 200) {
            writeLog("Recaptcha API HTTP hiba: $httpCode");
            return false;
        }

        $data = json_decode($response, true);
        
        if (isset($data['success']) && $data['success'] === true) {
            return true;
        } else {
            writeLog("Recaptcha validálás sikertelen: " . json_encode($data));
            return false;
        }
    }
}

// Utolsó módosítás: 2026. február 06. 17:16:00