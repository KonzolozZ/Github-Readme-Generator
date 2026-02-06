# README EN/HU
[![en](https://img.shields.io/badge/lang-en-red.svg)](https://github.com/KonzolozZ/Github-Readme-Generator/blob/main/README.md)
[![hu](https://img.shields.io/badge/lang-hu-green.svg)](https://github.com/KonzolozZ/Github-Readme-Generator/blob/main/README-HU.md)

# GitHub Readme Generator
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://www.php.net/)
[![Top Language](https://img.shields.io/github/languages/top/KonzolozZ/Github-Readme-Generator?color=blue)](https://github.com/KonzolozZ/Github-Readme-Generator)

This project is a web-based application designed to streamline the creation of professional and engaging GitHub README files. Leveraging the power of AI (Google Gemini) and the GitHub API, it helps developers generate comprehensive project documentation with ease, while protecting the service with Google reCAPTCHA.

✨ Features
*   **AI-Powered Content Generation:** Utilizes Google Gemini to generate descriptive and structured README content.
*   **GitHub Integration:** Fetches repository details directly from GitHub to inform the README generation.
*   **Intuitive Web Interface:** A user-friendly web application for seamless interaction.
*   **Security with reCAPTCHA:** Protects the service from spam and abuse using Google reCAPTCHA.
*   **Generates Comprehensive Sections:** Creates detailed and well-structured README sections suitable for various projects.

📚 Tech Stack
*   **Backend:** PHP
*   **Frontend:** HTML, CSS, JavaScript
*   **AI Service:** Google Gemini API
*   **Data Fetching:** GitHub API
*   **Security:** Google reCAPTCHA

🚀 Installation

To get this project up and running locally, follow these steps:

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/KonzolozZ/Github-Readme-Generator.git
    cd Github-Readme-Generator
    ```

2.  **Configure environment variables:**
    Create a `.env` file in the project root and add your API keys and project details. A `.env.example` is not provided, so use the keys from the problem statement's `.env` file.
    ```
    GEMINI_API_KEY=your_gemini_api_key
    APP_NAME="Github Readme Generator"
    GOOGLE_PROJECT_NAME="projects/your_google_project_number"
    GOOGLE_PROJECT_NUMBER=your_google_project_number
    RECAPTCHA_SITE_KEY=your_recaptcha_site_key
    RECAPTCHA_SECRET_KEY=your_recaptcha_secret_key
    ```
    *Replace placeholders with your actual API keys and project details obtained from Google Cloud and reCAPTCHA services.*

3.  **Set up a web server:**
    Ensure you have a web server (e.g., Apache, Nginx) configured to serve PHP applications. Point its document root to the `Github-Readme-Generator` directory.
    Ensure PHP 8.1+ is installed and configured correctly with your web server.

4.  **Access the application:**
    Open your web browser and navigate to the URL where your web server is hosting the application (e.g., `http://localhost/` or `http://your-domain.com/`).

▶️ Usage

Once the application is running, simply:

1.  **Open the application** in your web browser.
2.  **Input the GitHub repository URL** for which you want to generate a README.
3.  **Click the "Generate README" button** (or similar, inferring from the app name).
4.  The application will use AI to process the repository information and generate a well-structured README for you to review and customize.

🤝 Contributing

Contributions are welcome! If you have suggestions for improvements or new features, please open an issue or submit a pull request.

📝 License

This project is licensed under the MIT License - see the [LICENSE](https://github.com/KonzolozZ/Github-Readme-Generator/blob/main/LICENSE) file for details.