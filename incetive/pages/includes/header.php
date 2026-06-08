<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '../../../session_middleware.php';
requireAuthenticatedSession('https://www.blumar.com.br/');
?>
<style>
    #read_more {
        cursor: pointer;
    }

    .menu_interno {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .language-switcher {
        position: relative;
    }

    .language-switcher img {
        width: 24px;
        height: 18px;
        object-fit: cover;
        border-radius: 3px;
        border: 1px solid #d0d0d0;
    }

    .language-toggle {
        border: 1px solid #d0d0d0;
        background: #fff;
        border-radius: 4px;
        padding: 4px 6px;
        height: 30px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .language-toggle .material-icons {
        font-size: 16px;
        color: #666;
    }

    .language-menu {
        position: absolute;
        top: 34px;
        left: 0;
        display: none;
        background: #fff;
        border: 1px solid #d0d0d0;
        border-radius: 6px;
        padding: 6px;
        z-index: 20;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
    }

    .language-menu.is-open {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .language-option {
        border: 1px solid transparent;
        background: transparent;
        padding: 2px;
        border-radius: 4px;
        cursor: pointer;
    }

    .language-option:hover,
    .language-option.is-active {
        border-color: #e69928;
    }

    #google_translate_element {
        display: none;
    }

    .goog-te-banner-frame.skiptranslate {
        display: none !important;
    }

    iframe.goog-te-banner-frame,
    iframe.goog-te-menu-frame,
    iframe.skiptranslate {
        display: none !important;
        visibility: hidden !important;
    }

    body > .skiptranslate {
        display: none !important;
    }

    body {
        top: 0 !important;
    }

    .goog-tooltip,
    .goog-tooltip:hover,
    .goog-text-highlight {
        background-color: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    #goog-gt-tt,
    .goog-te-balloon-frame {
        display: none !important;
    }
</style>
<?php require_once  'url_helpers.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('css/estilo.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('css/estilo_mobile.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <style>
        #hotels_result .container::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
    <title>Blumar Hotels - Rio de Janeiro</title>
</head>
<body>
<header>
    <div class="container">
        <div class="logo_topo">
            <img src="<?php echo htmlspecialchars(asset_url('img/logo_blumar.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="Blumar Logo">
        </div>
        <div class="menu_interno">
            <div class="language-switcher" aria-label="Language switcher">
                <button type="button" id="languageToggle" class="language-toggle" title="Language">
                    <img id="currentLanguageFlag" src="<?php echo htmlspecialchars(asset_url('img/flags/us.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="Language">
                    <span class="material-icons">expand_more</span>
                </button>
                <div id="languageMenu" class="language-menu" role="listbox" aria-label="Language options">
                    <button type="button" class="language-option" data-lang="en" title="English">
                        <img src="<?php echo htmlspecialchars(asset_url('img/flags/us.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="English">
                    </button>
                    <button type="button" class="language-option" data-lang="pt" title="Portugues">
                        <img src="<?php echo htmlspecialchars(asset_url('img/flags/br.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="Portugues">
                    </button>
                    <button type="button" class="language-option" data-lang="es" title="Espanol">
                        <img src="<?php echo htmlspecialchars(asset_url('img/flags/es.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="Espanol">
                    </button>
                </div>
            </div>
            <button type="button" id="backToMainSite">Back to main site</button>
        </div>
    </div>
</header>


<div id="google_translate_element"></div>
<script>
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'en',
            includedLanguages: 'pt,en,es',
            autoDisplay: false
        }, 'google_translate_element');
    }

    function getCurrentLang() {
        var match = document.cookie.match(/(?:^|; )googtrans=([^;]+)/);
        if (!match || !match[1]) return 'en';
        var parts = decodeURIComponent(match[1]).split('/');
        return parts.length >= 3 && parts[2] ? parts[2] : 'en';
    }

    function setLanguage(lang) {
        var cookieValue = '/en/' + lang;
        document.cookie = 'googtrans=' + cookieValue + '; path=/';
        document.cookie = 'googtrans=' + cookieValue + '; path=/; domain=' + window.location.hostname;
        updateLanguageUi();

        var combo = document.querySelector('.goog-te-combo');
        if (combo) {
            combo.value = lang;
            combo.dispatchEvent(new Event('change'));
        } else {
            window.location.reload();
        }
    }

    function updateLanguageUi() {
        var current = getCurrentLang();
        var flag = document.getElementById('currentLanguageFlag');
        var options = document.querySelectorAll('.language-option[data-lang]');
        var flagMap = {
            pt: '<?php echo htmlspecialchars(asset_url('img/flags/br.png'), ENT_QUOTES, 'UTF-8'); ?>',
            en: '<?php echo htmlspecialchars(asset_url('img/flags/us.png'), ENT_QUOTES, 'UTF-8'); ?>',
            es: '<?php echo htmlspecialchars(asset_url('img/flags/es.png'), ENT_QUOTES, 'UTF-8'); ?>'
        };

        if (flag && flagMap[current]) {
            flag.src = flagMap[current];
        }

        options.forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-lang') === current);
        });
    }

    function hideGoogleIframes() {
        var selectors = ['.goog-te-banner-frame', '.goog-te-menu-frame', 'iframe.skiptranslate'];
        selectors.forEach(function (selector) {
            document.querySelectorAll(selector).forEach(function (el) {
                el.style.display = 'none';
                el.style.visibility = 'hidden';
            });
        });

        if (document.body) {
            document.body.style.top = '0px';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var backBtn = document.getElementById('backToMainSite');
        var toggle = document.getElementById('languageToggle');
        var menu = document.getElementById('languageMenu');
        var options = document.querySelectorAll('.language-option[data-lang]');

        if (backBtn) {
            backBtn.style.cursor = 'pointer';
            backBtn.addEventListener('click', function () {
                window.location.href = '<?php echo htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8'); ?>';
            });
        }

        if (toggle && menu) {
            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                menu.classList.toggle('is-open');
            });
        }

        options.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (menu) menu.classList.remove('is-open');
                setLanguage(btn.getAttribute('data-lang'));
            });
        });

        document.addEventListener('click', function () {
            if (menu) menu.classList.remove('is-open');
        });

        updateLanguageUi();
        setTimeout(updateLanguageUi, 1200);
        hideGoogleIframes();
        setInterval(hideGoogleIframes, 800);
    });
</script>
<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

