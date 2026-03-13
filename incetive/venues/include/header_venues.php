<style>
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
        border: 1px solid #286175;
        background: #286175;
        border-radius: 4px;
        padding: 4px 6px;
        height: 30px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .language-caret {
        font-size: 12px;
        color: #0e0606;
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
        z-index: 1200;
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

    #goog-gt-tt,
    .goog-te-balloon-frame {
        display: none !important;
    }
</style>

<header class="header">
    <div class="logo">BLUMAR</div>
    <div class="header-right">
        <div class="language-switcher" aria-label="Language switcher">
            <button type="button" id="languageToggle" class="language-toggle" title="Language">
                <img id="currentLanguageFlag" src="../img/flags/us.png" alt="Language">
                <span class="language-caret">&#9662;</span>
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
        <button class="back-btn" style="cursor: pointer;">Back to Main Site</button>
    </div>
</header>

<section class="hotels-section">
    <div class="hotels-title">
        <h1>Venues</h1>
        <span class="hotels-subtitle">Rio de Janeiro's venues selection</span>
    </div>
    <div class="header-actions">
        <div class="change-city">Change the city</div>
        <div class="filters-text">
            <span>Filters</span>
            <svg class="filters-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M3 4h18v2H3V4zm3 7h12v2H6v-2zm4 7h4v2h-4v-2z"/>
            </svg>
        </div>
    </div>
</section>

<div class="breadcrumb-section">
    <div class="breadcrumb">
        <a href="#">Incentive Area</a> &#8250; <a href="#">Venues</a> &#8250; <strong>Rio de Janeiro</strong>
    </div>
</div>

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

   addEventListener('click', function (e) {
        if (e.target.closest('.back-btn')) {    
        window.location.href = '../index.php';
    }
    });

</script>
<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
