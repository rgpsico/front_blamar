<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/estilo.css?v=1.0.1">
    <link rel="stylesheet" href="css/estilo_mobile.css">
        <link rel="stylesheet" href="css/index.css">
    <title>Blumar | Incentives</title>
</head>
<body>
    <?php include __DIR__ . '/include/header_index.php'; ?>

    <section id="incentive_area">
        <div class="container">

        <div class="title_incentive">
            <h1>Special INCENTIVE Client Area</h1>
            <p>Welcome!<br><br>
We have created a special area that will allow you to navigate through all the information our team has put together to help you better sell Brazil. At just a few clicks you will have access to many products that were designed carefully to better help you sell our destination: tariff rates, special programs, Inspection reports, online training , detailed maps and lots of product update just to mention a few.
<br><br>

                <strong>We invite you to explore and enjoy!</strong>
            </p>
        </div>

        <div class="container_menu_grid">
            <div class="menu_grid">
                <div class="menu_grid_container">
                    <a href="pages/hotel/hotel_list.php">
                    <div class="box_menu_grid bmg01">
                        <h3>Hotels</h3>
                        <div class="line"></div>
                        <p>Um breve texto introdutorio sobre</p>
                    </div>
                    </a>
                    <a href="activities.html">
                    <div class="box_menu_grid bmg02">
                        <h3>Activities</h3>
                        <div class="line"></div>
                        <p>Um breve texto introdutorio sobre</p>
                    </div>
                    </a>
                    <div class="box_menu_grid bmg03">
                        
                    </div>
                    <a href="pages/venues/venues.php">
                    <div class="box_menu_grid bmg04">
                        <h3>Venues</h3>
                        <div class="line"></div>
                        <p>Um breve texto introdutorio sobre</p>
                    </div>
                    </a>
                </div>
                <div class="menu_grid_container">
                    <div class="box_menu_grid bmg05">
                        
                    </div>
                    <a href="pages/entertainment/entertainment_list.php">
                    <div class="box_menu_grid bmg06">
                        <h3>Entertainment</h3>
                        <div class="line"></div>
                        <p>Um breve texto introdutorio sobre</p>
                    </div>
                    </a>
                    <a href="restaurants.html">
                    <div class="box_menu_grid bmg07">
                        <h3>Restaurants</h3>
                        <div class="line"></div>
                        <p>Um breve texto introdutorio sobre</p>
                    </div>
                    </a>
                    <div class="box_menu_grid bmg08">
                        <h3>Gifts</h3>
                        <div class="line"></div>
                        <p>Um breve texto introdutorio sobre</p>
                    </div>
                </div>
                <div class="menu_grid_container">
                    <div class="box_menu_grid bmg09">
                        <h3>Add-ons</h3>
                        <div class="line"></div>
                        <p>Um breve texto introdutorio sobre</p>
                    </div>
                    <div class="box_menu_grid bmg10"></div>
                    <div class="box_menu_grid bmg11">
                        <h3>Library</h3>
                        <div class="line"></div>
                        <p>Um breve texto introdutorio sobre</p>
                    </div>
                    <div class="box_menu_grid bmg12">
                        <h3>Team
Blumar</h3>
                        <div class="line"></div>
                        <p>Um breve texto introdutorio sobre</p>
                    </div>
                </div>
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

        function updateLanguageUi() {
            var current = getCurrentLang();
            var flag = document.getElementById('currentLanguageFlag');
            var options = document.querySelectorAll('.language-option[data-lang]');
            var flagMap = {
                pt: 'img/flags/br.png',
                en: 'img/flags/us.png',
                es: 'img/flags/es.png'
            };

            if (flag && flagMap[current]) {
                flag.src = flagMap[current];
            }

            options.forEach(function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-lang') === current);
            });
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
            hideGoogleIframes();
            setInterval(hideGoogleIframes, 800);
        });
    </script>
    <script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>
</html>
