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
