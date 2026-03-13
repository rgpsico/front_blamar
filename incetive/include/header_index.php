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

<header>
    <div class="container">
        <div class="logo_topo">
            <img src="img/logo_blumar.png" alt="">
        </div>
        <div class="menu_interno">
            <div class="language-switcher" aria-label="Language switcher">
                <button type="button" id="languageToggle" class="language-toggle" title="Language">
                    <img id="currentLanguageFlag" src="img/flags/us.png" alt="Language">
                    <span class="material-icons">expand_more</span>
                </button>
                <div id="languageMenu" class="language-menu" role="listbox" aria-label="Language options">
                    <button type="button" class="language-option" data-lang="en" title="English">
                        <img src="img/flags/us.png" alt="English">
                    </button>
                    <button type="button" class="language-option" data-lang="pt" title="Portugues">
                        <img src="img/flags/br.png" alt="Portugues">
                    </button>
                    <button type="button" class="language-option" data-lang="es" title="Espanol">
                        <img src="img/flags/es.png" alt="Espanol">
                    </button>
                </div>
            </div>
            <a href="">
                <button>Back to main site</button>
            </a>
        </div>
    </div>
</header>
