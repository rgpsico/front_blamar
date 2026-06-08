<!-- ══════════════════════════════════════════
     HEADER — Language Switcher + Back Button
     Colar dentro do <head>: o bloco <style>
     Colar no <body>: o bloco <header>
     ══════════════════════════════════════════ -->


     <?php
//require_once '../../session_middleware.php';
//requireAuthenticatedSession('Acesso negado. Por favor, faça login.');
?>

<!-- ── STYLES: colar dentro do <head> ou no estilo.css ── -->
<style>
    /* ── Header page section ── */
    #header_page {
        background: #F9F6F6;
        overflow: hidden;
    }
    #header_page > .container {
        display: flex;
        align-items: center;
        padding: 1.5vw 0;
    }
    .page_title {
        display: flex;
        align-items: baseline;
        gap: 0.6vw;
    }
    #header_page h2 {
        color: #4C4B4B;
        font-size: 2vw;
        font-weight: 700;
        margin: 0;
        float: none;
    }
    #header_page h3 {
        color: #888;
        font-size: 0.85vw;
        font-weight: 400;
        margin: 0;
        float: none;
        padding: 0;
    }
    #header_page h3 strong {
        font-weight: 700;
        color: #4C4B4B;
    }
    .chose_city {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.85vw;
        color: #555;
        cursor: pointer;
        margin-left: auto;
    }
    .chose_city .material-icons {
        font-size: 1.1vw;
    }

    /* ── Filter bar ── */
    #header_page .container_max.ct01 {
        background: #4a8bbe;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    }
    #header_page .container_max.ct01 .container {
        display: flex;
        align-items: center;
        gap: 1.5vw;
        padding: 0.7vw 0;
    }
    .filter_box01 {
        flex-shrink: 0;
    }
    .filter_box02 {
        flex: 1;
    }
    .filters_hotel {
        display: flex;
        align-items: center;
        gap: 0.5vw;
    }
    .filters_hotel h3 {
        color: #fff;
        font-size: 0.8vw;
        font-weight: 500;
        margin: 0;
        float: none;
    }
    .filters_hotel .material-icons {
        color: #fff;
        font-size: 1vw;
    }
    .filters_hotel_inner {
        display: flex;
        align-items: center;
        gap: 1vw;
    }
    .select_hotel,
    .select_stars {
        border: 0;
        padding: 0.4vw 1vw;
        border-radius: 0.3vw;
        font-size: 0.75vw;
        color: #7e7e7e;
        font-style: italic;
        background: #fff;
        cursor: pointer;
    }
    .btn_apply_filter {
        background: transparent;
        border: 1.5px solid rgba(255,255,255,0.85);
        color: #fff;
        padding: 0.3vw 1.1vw;
        font-size: 0.75vw;
        border-radius: 0.3vw;
        cursor: pointer;
        white-space: nowrap;
        transition: background 0.2s, color 0.2s;
    }
    .btn_apply_filter:hover {
        background: #fff;
        color: #2c6e9e;
    }

    /* ── Breadcrumb bar ── */
    #header_page .container_max.ct02 {
        border-top: 1px solid #e8e8e8;
    }
    #header_page .container_max.ct02 .container {
        padding: 0.5vw 0;
    }
    .breadcrumb {
        display: flex;
        align-items: center;
        gap: 0.3vw;
        font-size: 0.75vw;
        color: #888;
        padding: 0;
        background: none;
    }
    .breadcrumb a {
        color: #888;
        text-decoration: none;
    }
    .breadcrumb a:hover {
        color: #4a8bbe;
        text-decoration: underline;
    }
    .breadcrumb .material-icons {
        font-size: 0.9vw;
        color: #bbb;
    }
</style>

<style>
    /* ── Menu interno: alinha switcher + botão ── */
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
        min-font-size: 11px;
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
        z-index: 100;
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
    .lang-option.active {
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
    .lang-option.active .lang-check {
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
        text-decoration: none;
        white-space: nowrap;
    }
    .btn_back_site:hover {
        background: #6399b2;
    }
    .btn_back_site svg {
        flex-shrink: 0;
    }
</style>

<!-- ── HEADER: substituir o <header> atual ── -->
<header>
    <div class="container">
        <div class="logo_topo">
            <img src="../../img/logo_blumar.png" alt="Blumar">
        </div>
        <div class="menu_interno">

            <!-- Language Switcher -->
            <div class="language-switcher" id="langSwitcher">
                <button type="button" class="lang-btn" id="langBtn">
                    <img id="currentFlag" src="../../img/flags/us.png" alt="EN">
                    <span class="lang-label" id="currentLabel">EN</span>
                    <svg class="lang-arrow" width="11" height="11" viewBox="0 0 11 11" fill="none">
                        <path d="M2 3.5l3.5 3.5 3.5-3.5" stroke="rgba(255,255,255,0.7)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="lang-menu" id="langMenu">
                    <button type="button" class="lang-option active" data-lang="en" data-label="EN" data-flag="../../img/flags/us.png">
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

            <!-- Back to main site -->
            <a href="../../index.php" class="btn_back_site">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                    <path d="M8.5 2L4 6.5l4.5 4.5" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Back to main site
            </a>

        </div>
    </div>
</header>

<!-- ── HEADER PAGE: título, filtros e breadcrumb ── -->
<section id="header_page">
    <div class="container">
        <div class="page_title">
            <h2>Hotels</h2>
            <h3 id="city_subtitle">hotels selection</h3>
        </div>
        <div class="chose_city">
            <span>Change the city</span>
            <i class="material-icons">expand_more</i>
        </div>
    </div>

    <div class="container_max ct01">
        <div class="container">
            <div class="filter_box01">
                <div class="filters_hotel">
                    <h3>Filters</h3>
                    <i class="material-icons">&#xe152;</i>
                </div>
            </div>
            <div class="filter_box02">
                <div class="filters_hotel filters_hotel_inner">
                    <select class="select_hotel" id="selectLocation" name="city">
                        <option value="">select the location</option>
                    </select>
                    <select class="select_stars" id="selectStars" name="stars">
                        <option value="">Stars Filter</option>
                        <option value="3">3 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="5">5 Stars</option>
                    </select>
                    <button class="btn_apply_filter" id="btnApply">Apply Filter</button>
                </div>
            </div>
        </div>
    </div>

    <div class="container_max ct02">
        <div class="container">
            <div class="breadcrumb">
                <a href="../../index.php">Incentive Area</a>
                <i class="material-icons">&#xe315;</i>
                <a href="hotel_list.php">Hotels</a>
                <i class="material-icons">&#xe315;</i>
                <a href="" id="breadcrumb_city">Hotels</a>
            </div>
        </div>
    </div>
</section>

<!-- ── SCRIPT: colar antes do </body> ── -->
<script>
(function () {
    var btn    = document.getElementById('langBtn');
    var menu   = document.getElementById('langMenu');
    var flag   = document.getElementById('currentFlag');
    var label  = document.getElementById('currentLabel');

    if (!btn) return;

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        btn.classList.toggle('open');
        menu.classList.toggle('open');
    });

    menu.querySelectorAll('.lang-option').forEach(function (opt) {
        opt.addEventListener('click', function () {
            /* atualiza botão */
            flag.src           = opt.dataset.flag;
            flag.alt           = opt.dataset.label;
            label.textContent  = opt.dataset.label;

            /* atualiza active */
            menu.querySelectorAll('.lang-option').forEach(function (o) {
                o.classList.remove('active');
            });
            opt.classList.add('active');

            /* fecha */
            btn.classList.remove('open');
            menu.classList.remove('open');

            /* ── Google Translate (se estiver em uso) ── */
            var sel = document.querySelector('.goog-te-combo');
            if (sel) {
                var map = { en: 'en', pt: 'pt', es: 'es' };
                sel.value = map[opt.dataset.lang] || 'en';
                sel.dispatchEvent(new Event('change'));
            }
        });
    });

    /* fecha ao clicar fora */
    document.addEventListener('click', function () {
        btn.classList.remove('open');
        menu.classList.remove('open');
    });
})();
</script>
