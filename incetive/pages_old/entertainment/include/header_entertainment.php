
<style>
    /* ── Language Switcher (entertainment) ── */
    .language-switcher {
        position: relative;
    }

    .lang-btn {
        display: flex;
        align-items: center;
        gap: 7px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 6px;
        padding: 0 12px;
        height: 36px;
        cursor: pointer;
        transition: background 0.2s;
        font-family: 'Montserrat', sans-serif;
    }
    .lang-btn:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    .lang-btn img {
        width: 22px;
        height: auto;
        aspect-ratio: 4/3;
        object-fit: cover;
        border-radius: 2px;
        display: block;
    }
    .lang-btn .lang-label {
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.5px;
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
        font-family: 'Montserrat', sans-serif;
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
        font-weight: 500;
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
    .back-btn {
        background-color: rgba(255, 255, 255, 0.15);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.25);
        padding: 0 20px;
        height: 36px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        font-family: 'Montserrat', sans-serif;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: background-color 0.2s;
        letter-spacing: 0.2px;
    }
    .back-btn:hover {
        background-color: rgba(255, 255, 255, 0.25);
    }

    /* ── Google Translate: esconder elementos nativos ── */
    #google_translate_element { display: none; }
    .goog-te-banner-frame.skiptranslate,
    iframe.goog-te-banner-frame,
    iframe.goog-te-menu-frame,
    iframe.skiptranslate { display: none !important; visibility: hidden !important; }
    body > .skiptranslate { display: none !important; }
    body { top: 0 !important; }
    #goog-gt-tt, .goog-te-balloon-frame { display: none !important; }
</style>

<header class="header">
    <div class="logo">BLUMAR</div>
    <div class="header-right">

        <!-- Language Switcher -->
        <div class="language-switcher" id="langSwitcher">
            <button type="button" class="lang-btn" id="languageToggle">
                <img id="currentLanguageFlag" src="../../img/flags/us.png" alt="EN">
                <span class="lang-label" id="currentLangLabel">EN</span>
                <svg class="lang-arrow" width="11" height="11" viewBox="0 0 11 11" fill="none">
                    <path d="M2 3.5l3.5 3.5 3.5-3.5" stroke="rgba(255,255,255,0.75)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <div id="languageMenu" class="lang-menu" role="listbox">
                <button type="button" class="lang-option" data-lang="en" data-label="EN" data-flag="../../img/flags/us.png">
                    <img src="../../img/flags/us.png" alt="English">
                    <span class="lang-name">English</span>
                    <svg class="lang-check" width="14" height="14" viewBox="0 0 14 14" fill="none">
                        <path d="M2 7l3.5 3.5L12 4" stroke="#e8a655" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="lang-sep"></div>
                <button type="button" class="lang-option" data-lang="pt" data-label="PT" data-flag="../../img/flags/br.png">
                    <img src="../../img/flags/br.png" alt="Português">
                    <span class="lang-name">Português</span>
                    <svg class="lang-check" width="14" height="14" viewBox="0 0 14 14" fill="none">
                        <path d="M2 7l3.5 3.5L12 4" stroke="#e8a655" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="lang-sep"></div>
                <button type="button" class="lang-option" data-lang="es" data-label="ES" data-flag="../../img/flags/es.png">
                    <img src="../../img/flags/es.png" alt="Español">
                    <span class="lang-name">Español</span>
                    <svg class="lang-check" width="14" height="14" viewBox="0 0 14 14" fill="none">
                        <path d="M2 7l3.5 3.5L12 4" stroke="#e8a655" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Back to Main Site -->
        <button class="back-btn" id="backBtn">
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                <path d="M8.5 2L4 6.5l4.5 4.5" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Back to Main Site
        </button>

    </div>
</header>

<section class="hotels-section">
    <div class="hotels-title">
        <h1>Entertainment</h1>
        <span class="hotels-subtitle">Rio de Janeiro's entertainment selection</span>
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
        <a href="#">Incentive Area</a> &#8250; <a href="#">Entertainment</a> &#8250; <strong>Rio de Janeiro</strong>
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

        document.getElementById('backBtn').addEventListener('click', function () {
            window.location.href = '../../index.php';
        });

        updateLanguageUi();
        setTimeout(updateLanguageUi, 1200);
        hideGoogleIframes();
        setInterval(hideGoogleIframes, 800);
    });
</script>
<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
