<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../session_middleware.php';
requireAuthenticatedSession();

require_once __DIR__ . '/../../../util/connection.php';

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!isset($hotel_name) || $hotel_name === '') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id > 0) {
        $sql = "SELECT inc_name FROM incentive.inc_program WHERE inc_id = $1 LIMIT 1";
        $res = pg_query_params($conn, $sql, [$id]);
        if ($res && pg_num_rows($res) > 0) {
            $row = pg_fetch_assoc($res);
            $hotel_name = $row['inc_name'] ?? '';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="../../css/estilo.css">
    <link rel="stylesheet" href="../../css/estilo_mobile.css">
    <style>
        /* ── Menu interno ── */
        .menu_interno {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: auto;
        }

        /* ── Language Switcher ── */
        .language-switcher {
            position: relative;
        }

        .lang-btn {
            display: flex;
            align-items: center;
            gap: 7px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 6px;
            padding: 0 10px;
            height: 2.2vw;
            min-height: 30px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .lang-btn:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        .lang-btn img {
            width: 1.3vw;
            min-width: 18px;
            height: auto;
            aspect-ratio: 4/3;
            object-fit: cover;
            border-radius: 2px;
            display: block;
        }
        .lang-btn .lang-label {
            color: #fff;
            font-size: 0.75vw;
            font-weight: 500;
            letter-spacing: 0.4px;
        }
        .lang-btn .lang-arrow {
            transition: transform 0.2s;
            display: block;
        }
        .lang-btn.open .lang-arrow {
            transform: rotate(180deg);
        }

        /* ── Dropdown ── */
        .lang-menu {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.1);
            min-width: 150px;
            z-index: 1200;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        .lang-menu.open {
            display: block;
        }
        .lang-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 14px;
            cursor: pointer;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
            transition: background 0.15s;
        }
        .lang-option:hover {
            background: #f4f7fa;
        }
        .lang-option.is-active {
            background: #eef4f9;
        }
        .lang-option img {
            width: 22px;
            height: auto;
            aspect-ratio: 4/3;
            object-fit: cover;
            border-radius: 2px;
            display: block;
        }
        .lang-option .lang-name {
            font-size: 13px;
            color: #2c2c2c;
            font-weight: 400;
            flex: 1;
        }
        .lang-option .lang-check {
            visibility: hidden;
            flex-shrink: 0;
        }
        .lang-option.is-active .lang-check {
            visibility: visible;
        }
        .lang-sep {
            height: 1px;
            background: #eee;
        }

        /* ── Back button ── */
        .btn_back_site {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #78AAC3;
            border: none;
            border-radius: 6px;
            padding: 0 1vw;
            height: 2.2vw;
            min-height: 30px;
            color: #fff;
            font-size: 0.75vw;
            font-weight: 500;
            cursor: pointer;
            letter-spacing: 0.3px;
            transition: background 0.2s;
            white-space: nowrap;
            font-family: "Roboto", sans-serif;
            text-decoration: none;
        }
        .btn_back_site:hover {
            background: #6399b2;
        }

        /* ── Google Translate: esconder elementos nativos ── */
        #google_translate_element { display: none; }
        .goog-te-banner-frame.skiptranslate,
        iframe.goog-te-banner-frame,
        iframe.goog-te-menu-frame,
        iframe.skiptranslate { display: none !important; visibility: hidden !important; }
        body > .skiptranslate { display: none !important; }
        body { top: 0 !important; }
        .goog-tooltip, .goog-tooltip:hover, .goog-text-highlight {
            background-color: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }
        #goog-gt-tt, .goog-te-balloon-frame { display: none !important; }
    </style>
    <title><?php echo isset($hotel_name) && $hotel_name !== '' ? h($hotel_name) : 'Hotel'; ?></title>
</head>
<body>

    <header>
        <div class="container">
            <div class="logo_topo">
                <img src="../../img/logo_blumar.png" alt="">
            </div>
            <div class="menu_interno">

                <!-- Language Switcher -->
                <div class="language-switcher" id="langSwitcher">
                    <button type="button" class="lang-btn" id="languageToggle">
                        <img id="currentLanguageFlag" src="../../img/flags/us.png" alt="EN">
                        <span class="lang-label" id="currentLangLabel">EN</span>
                        <svg class="lang-arrow" width="11" height="11" viewBox="0 0 11 11" fill="none">
                            <path d="M2 3.5l3.5 3.5 3.5-3.5" stroke="rgba(255,255,255,0.7)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div id="languageMenu" class="lang-menu" role="listbox">
                        <button type="button" class="lang-option" data-lang="en" data-label="EN" data-flag="../../img/flags/us.png">
                            <img src="../../img/flags/us.png" alt="English">
                            <span class="lang-name">English</span>
                            <svg class="lang-check" width="14" height="14" viewBox="0 0 14 14" fill="none">
                                <path d="M2 7l3.5 3.5L12 4" stroke="#E89127" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div class="lang-sep"></div>
                        <button type="button" class="lang-option" data-lang="pt" data-label="PT" data-flag="../../img/flags/br.png">
                            <img src="../../img/flags/br.png" alt="Português">
                            <span class="lang-name">Português</span>
                            <svg class="lang-check" width="14" height="14" viewBox="0 0 14 14" fill="none">
                                <path d="M2 7l3.5 3.5L12 4" stroke="#E89127" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div class="lang-sep"></div>
                        <button type="button" class="lang-option" data-lang="es" data-label="ES" data-flag="../../img/flags/es.png">
                            <img src="../../img/flags/es.png" alt="Español">
                            <span class="lang-name">Español</span>
                            <svg class="lang-check" width="14" height="14" viewBox="0 0 14 14" fill="none">
                                <path d="M2 7l3.5 3.5L12 4" stroke="#E89127" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Back to hotel list -->
                <a href="hotel_list.php" class="btn_back_site">
                    <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                        <path d="M8.5 2L4 6.5l4.5 4.5" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Back to Hotels
                </a>

            </div>
        </div>
    </header>

    <section id="header_page">
        <div class="container">
            <div class="page_title">
                <h2>Hotels</h2>
                <h3><?php echo isset($city_name) && $city_name !== '' ? h($city_name) : 'City'; ?></h3>
            </div>
            <div class="chose_city"></div>
        </div>
        <div class="container_max ct02">
            <div class="container">
                <div class="breadcrumb">
                    <a href="../../index.php">Incentive Area</a><i class="material-icons">&#xe315;</i>
                    <a href="hotel_list.php">Hotel</a><i class="material-icons">&#xe315;</i>
                    <a href=""><?php echo isset($city_name) && $city_name !== '' ? h($city_name) : 'City'; ?></a><i class="material-icons">&#xe315;</i>
                    <a href=""><?php echo isset($hotel_name) && $hotel_name !== '' ? h($hotel_name) : 'Hotel'; ?></a>
                </div>
            </div>
        </div>
    </section>

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

        var flagMap  = { en: '../../img/flags/us.png', pt: '../../img/flags/br.png', es: '../../img/flags/es.png' };
        var labelMap = { en: 'EN', pt: 'PT', es: 'ES' };

        function updateLanguageUi() {
            var current = getCurrentLang();
            var flagEl  = document.getElementById('currentLanguageFlag');
            var labelEl = document.getElementById('currentLangLabel');
            if (flagEl  && flagMap[current])  flagEl.src = flagMap[current];
            if (labelEl && labelMap[current]) labelEl.textContent = labelMap[current];
            document.querySelectorAll('.lang-option[data-lang]').forEach(function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-lang') === current);
            });
        }

        function setLanguage(lang) {
            var val = '/en/' + lang;
            document.cookie = 'googtrans=' + val + '; path=/';
            document.cookie = 'googtrans=' + val + '; path=/; domain=' + window.location.hostname;
            updateLanguageUi();
            var combo = document.querySelector('.goog-te-combo');
            if (combo) {
                combo.value = lang;
                combo.dispatchEvent(new Event('change'));
            } else {
                window.location.reload();
            }
        }

        function hideGoogleIframes() {
            ['iframe.goog-te-banner-frame', 'iframe.skiptranslate', '.goog-te-menu-frame'].forEach(function (sel) {
                document.querySelectorAll(sel).forEach(function (el) {
                    el.style.display    = 'none';
                    el.style.visibility = 'hidden';
                });
            });
            if (document.body) document.body.style.top = '0px';
        }

        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.getElementById('languageToggle');
            var menu   = document.getElementById('languageMenu');

            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                toggle.classList.toggle('open');
                menu.classList.toggle('open');
            });

            document.querySelectorAll('.lang-option[data-lang]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    toggle.classList.remove('open');
                    menu.classList.remove('open');
                    setLanguage(btn.getAttribute('data-lang'));
                });
            });

            document.addEventListener('click', function () {
                toggle.classList.remove('open');
                menu.classList.remove('open');
            });

            updateLanguageUi();
            setTimeout(updateLanguageUi, 1200);
            hideGoogleIframes();
            setInterval(hideGoogleIframes, 800);
        });
    </script>
    <script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>