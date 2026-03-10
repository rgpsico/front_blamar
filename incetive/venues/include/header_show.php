<?php
require_once __DIR__ . '/../../util/connection.php';

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$venue_name = '';
$city_name = '';

if ($id > 0) {
    $sql = "
        SELECT nome, city
        FROM conteudo_internet.venues
        WHERE cod_venues = $1
        LIMIT 1
    ";
    $res = pg_query_params($conn, $sql, [$id]);
    if ($res && pg_num_rows($res) > 0) {
        $row = pg_fetch_assoc($res);
        $venue_name = $row['nome'] ?? '';
        $city_name = $row['city'] ?? '';
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
    <link rel="stylesheet" href="../css/estilo.css">
    <link rel="stylesheet" href="../css/estilo_mobile.css">
    <style>
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
    <title><?php echo $venue_name !== '' ? h($venue_name) : 'Venue'; ?></title>
</head>
<body>

    <header>
        <div class="container">
            <div class="logo_topo">
                <img src="../img/logo_blumar.png" alt="">
            </div>
            <div class="menu_interno">
                <div class="language-switcher" aria-label="Language switcher">
                    <button type="button" id="languageToggle" class="language-toggle" title="Language">
                        <img id="currentLanguageFlag" src="../img/flags/us.png" alt="Language">
                        <span class="material-icons">expand_more</span>
                    </button>
                    <div id="languageMenu" class="language-menu" role="listbox" aria-label="Language options">
                        <button type="button" class="language-option" data-lang="en" title="English">
                            <img src="../img/flags/us.png" alt="English">
                        </button>
                        <button type="button" class="language-option" data-lang="pt" title="Portugues">
                            <img src="../img/flags/br.png" alt="Portugues">
                        </button>
                        <button type="button" class="language-option" data-lang="es" title="Espanol">
                            <img src="../img/flags/es.png" alt="Espanol">
                        </button>
                    </div>
                </div>
                <a href="venues.php">
                    <button type="button" style="cursor: pointer;">Back to main site</button>
                </a>
            </div>
        </div>
    </header>

    <section id="header_page">
        <div class="container">
            <div class="page_title">
                <h2>Venues</h2>
                <h3><?php echo isset($city_name) && $city_name !== '' ? h($city_name) : 'City'; ?></h3>
            </div>
            <div class="chose_city"></div>
        </div>
        <div class="container_max ct02">
            <div class="container">
                <div class="breadcrumb">
                    <a href="../index.html">Incentive Area</a><i class="material-icons">&#xe315;</i>
                    <a href="venues.php">Venues</a><i class="material-icons">&#xe315;</i>
                    <a href=""><?php echo isset($city_name) && $city_name !== '' ? h($city_name) : 'City'; ?></a><i class="material-icons">&#xe315;</i>
                    <a href=""><?php echo $venue_name !== '' ? h($venue_name) : 'Venue'; ?></a>
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
                pt: '../img/flags/br.png',
                en: '../img/flags/us.png',
                es: '../img/flags/es.png'
            };

            if (flag && flagMap[current]) {
                flag.src = flagMap[current];
            }
            options.forEach(function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-lang') === current);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.getElementById('languageToggle');
            var menu = document.getElementById('languageMenu');
            var options = document.querySelectorAll('.language-option[data-lang]');

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
        });
    </script>
    <script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
