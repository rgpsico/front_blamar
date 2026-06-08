<?php include __DIR__ . '/../includes/header.php'; ?>

<section id="header_page">
    <div class="container">
        <div class="page_title">
            <h2>Restaurants</h2>
            <h3 id="city_subtitle">Restaurants selection</h3>
        </div>
        <div class="chose_city" id="btnChangeCity">
            <span>Change the city</span>
            <i class="material-icons">expand_more</i>
        </div>
    </div>

    <!-- Barra de filtros azul -->
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

                    <!-- Location -->
                    <select class="select_hotel" id="selectLocation" name="city">
                        <option value="">select the location</option>
                    </select>

                    <!-- Capacity -->
                    <select class="select_hotel" id="selectCapacity" name="capacity">
                        <option value="">Capacity Filter</option>
                        <option value="50">Up to 50</option>
                        <option value="100">Up to 100</option>
                        <option value="200">Up to 200</option>
                        <option value="500">Up to 500</option>
                    </select>

                    <!-- View -->
                    <div class="toggle_filter_group">
                        <span class="toggle_label">View</span>
                        <label class="toggle_check_label">
                            <input type="radio" name="filter_view" class="chk_view" value="yes"> yes
                        </label>
                        <label class="toggle_check_label">
                            <input type="radio" name="filter_view" class="chk_view" value="no"> no
                        </label>
                    </div>

                    <!-- Private Area -->
                    <div class="toggle_filter_group">
                        <span class="toggle_label">Private Area</span>
                        <label class="toggle_check_label">
                            <input type="radio" name="filter_private" class="chk_private" value="yes"> yes
                        </label>
                        <label class="toggle_check_label">
                            <input type="radio" name="filter_private" class="chk_private" value="no"> no
                        </label>
                    </div>

                    <button class="btn_apply_filter" id="btnApply">Apply Filter</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Breadcrumb -->
    <div class="container_max ct02">
        <div class="container">
            <div class="breadcrumb">
                <a href="<?php echo htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8'); ?>">Incentive Area</a>
                <i class="material-icons">&#xe315;</i>
                <a href="restaurante_list.php">Restaurants</a>
                <i class="material-icons">&#xe315;</i>
                <a href="" id="breadcrumb_city">Rio de Janeiro</a>
            </div>
        </div>
    </div>
</section>

<section id="restaurante_result">
    <div class="container">
        <div class="loading_msg">Loading Restaurants...</div>
    </div>
</section>

<style>
    /* =============================================
       HEADER / TÍTULO
    ============================================= */
    #header_page .container {
        display: flex;
        align-items: center;
    }
    #header_page .page_title h3 {
        font-weight: 400;
    }
    #header_page .page_title h3 strong {
        font-weight: 700;
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
    .chose_city .material-icons { font-size: 1.1vw; }

    /* =============================================
       FILTROS
    ============================================= */
    .filters_hotel_inner {
        display: flex;
        align-items: center;
        gap: 1.2vw;
        flex-wrap: wrap;
    }

    .select_hotel {
        padding: 0.3vw 0.8vw;
        font-size: 0.75vw;
        border-radius: 0.3vw;
        border: 1px solid rgba(255,255,255,0.6);
        background: rgba(255,255,255,0.15);
        color: #fff;
        cursor: pointer;
        min-width: 140px;
    }
    .select_hotel option {
        color: #333;
        background: #fff;
    }

    /* Radio groups (View / Private Area) */
    .toggle_filter_group {
        display: flex;
        align-items: center;
        gap: 0.5vw;
    }
    .toggle_label {
        font-size: 0.75vw;
        color: #fff;
        font-weight: 500;
        white-space: nowrap;
    }
    .toggle_check_label {
        display: flex;
        align-items: center;
        gap: 3px;
        font-size: 0.75vw;
        color: #fff;
        cursor: pointer;
        white-space: nowrap;
    }
    .toggle_check_label input[type="radio"] {
        cursor: pointer;
        accent-color: #fff;
    }

    /* Apply Filter */
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

    /* =============================================
       RESULTADO / CARDS
    ============================================= */
    #restaurante_result {
        padding: 30px 0 60px;
        background: #f7f5f0;
    }

    #restaurante_result .container {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 28px;
    }

    .loading_msg,
    .empty_msg {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 0;
        color: #888;
        font-size: 1rem;
    }

    /* Card */
    .box_restaurante {
        background: #fff;
        border-radius: 4px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        transition: box-shadow 0.2s;
    }
    .box_restaurante:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.13);
    }

    /* Imagem do card */
    .box_restaurante_img {
        width: 100%;
        height: 190px;
        object-fit: cover;
        display: block;
    }

    /* Conteúdo do card */
    .box_restaurante_conteudo {
        padding: 18px 20px 22px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    /* Linha laranja */
    .card_line {
        width: 36px;
        height: 3px;
        background: #e8a020;
        margin-bottom: 10px;
        border-radius: 2px;
    }

    /* City + tipo */
    .card_city {
        font-size: 0.78rem;
        color: #666;
        margin: 0 0 2px;
        line-height: 1.3;
    }
    .card_type {
        font-size: 0.78rem;
        color: #888;
        margin: 0 0 8px;
    }

    /* Nome do restaurante */
    .card_name {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1a1a1a;
        margin: 0 0 10px;
        line-height: 1.3;
    }

    /* Atributos (view, private area, capacity) */
    .card_attrs {
        font-size: 0.8rem;
        color: #555;
        line-height: 1.8;
        margin: 0 0 10px;
    }

    /* Descrição */
    .card_description {
        font-size: 0.8rem;
        color: #666;
        line-height: 1.5;
        margin: 0 0 18px;
        flex: 1;
        /* trunca em 3 linhas */
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Botão Read More */
    .btn_read_more {
        display: inline-block;
        background: #e8a020;
        color: #fff;
        border: none;
        padding: 8px 20px;
        font-size: 0.82rem;
        font-weight: 600;
        border-radius: 3px;
        cursor: pointer;
        text-decoration: none;
        align-self: flex-start;
        transition: background 0.2s;
    }
    .btn_read_more:hover {
        background: #c8881a;
        color: #fff;
    }

    /* Responsivo básico */
    @media (max-width: 900px) {
        #restaurante_result .container {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 580px) {
        #restaurante_result .container {
            grid-template-columns: 1fr;
        }
        .filters_hotel_inner {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        .select_hotel {
            font-size: 13px;
            min-width: 180px;
        }
    }
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    var API_URL     = '<?php echo htmlspecialchars(app_url('api/api_restaurante_incentive.php'), ENT_QUOTES, 'UTF-8'); ?>';
    var DEFAULT_IMG = '<?php echo htmlspecialchars(asset_url('img/hotel_01.png'), ENT_QUOTES, 'UTF-8'); ?>';
    var allRestaurantes = [];

    /* ── helpers ─────────────────────────────── */
    function fmt(val, fb) {
        return (val === null || val === undefined || val === '') ? (fb || '') : String(val).trim();
    }

    function boolAttr(val) {
        // 't', true, 1, 'true' → true
        return val === 't' || val === true || val === 1 || val === 'true';
    }

    function setLoading(msg) {
        $('#restaurante_result .container').html(
            '<div class="loading_msg">' + msg + '</div>'
        );
    }

    /* ── card ─────────────────────────────────── */
    function createCard(r) {
        var city     = fmt(r.city_name || r.city_code, 'City');
        var name     = fmt(r.name, 'Restaurant');
        var capacity = fmt(r.capacity, 'XXX');
        var thumb    = fmt(r.thumbnail || r.thumnail, DEFAULT_IMG);
        var desc     = fmt(r.short_description || r.description, '');
        var id       = r.id || '';
        var hasView  = boolAttr(r.has_view);
        var hasPriv  = boolAttr(r.has_private_area);

        var attrs = '';
        if (hasView)  attrs += 'With a view<br>';
        if (hasPriv)  attrs += 'Private Area<br>';
        attrs += 'Capacity: ' + capacity;

        return '<div class="box_restaurante">'
            + '<img class="box_restaurante_img" src="' + thumb + '" alt="' + name
            +     '" onerror="this.src=\'' + DEFAULT_IMG + '\'">'
            + '<div class="box_restaurante_conteudo">'
            +   '<div class="card_line"></div>'
            +   '<p class="card_city">' + city + '</p>'
            +   '<p class="card_type">Hotel</p>'
            +   '<h4 class="card_name">' + name + '</h4>'
            +   '<div class="card_attrs">' + attrs + '</div>'
            +   (desc ? '<p class="card_description">' + desc + '</p>' : '')
            +   '<a href="res_show_section.php?id=' + id + '" class="btn_read_more">Read More</a>'
            + '</div></div>';
    }

    /* ── render ───────────────────────────────── */
    function renderRestaurantes(list) {
        var $c = $('#restaurante_result .container');
        if (!list || !list.length) {
            $c.html('<div class="empty_msg">No restaurants found.</div>');
            return;
        }
        $c.empty();
        $.each(list, function(i, r) { $c.append(createCard(r)); });
    }

    /* ── filtros ──────────────────────────────── */
    function applyFilters() {
        var city        = $('#selectLocation').val();
        var maxCap      = parseInt($('#selectCapacity').val(), 10) || 0;
        var viewVal     = $('input[name="filter_view"]:checked').val();    // 'yes'|'no'|undefined
        var privateVal  = $('input[name="filter_private"]:checked').val(); // 'yes'|'no'|undefined

        var filtered = allRestaurantes;

        if (city) {
            filtered = $.grep(filtered, function(r) {
                var c = (r.city_name || r.city_code || '').toUpperCase();
                return c.indexOf(city.toUpperCase()) >= 0;
            });
        }

        if (maxCap) {
            filtered = $.grep(filtered, function(r) {
                var cap = parseInt(r.capacity, 10) || 0;
                return cap <= maxCap;
            });
        }

        if (viewVal === 'yes') {
            filtered = $.grep(filtered, function(r) { return boolAttr(r.has_view); });
        } else if (viewVal === 'no') {
            filtered = $.grep(filtered, function(r) { return !boolAttr(r.has_view); });
        }

        if (privateVal === 'yes') {
            filtered = $.grep(filtered, function(r) { return boolAttr(r.has_private_area); });
        } else if (privateVal === 'no') {
            filtered = $.grep(filtered, function(r) { return !boolAttr(r.has_private_area); });
        }

        renderRestaurantes(filtered);
    }

    /* ── init ─────────────────────────────────── */
    $(function() {
        setLoading('Loading restaurants...');

        $.ajax({
            url: API_URL,
            method: 'GET',
            dataType: 'json',
            data: { request: 'listar_restaurantes_paginate', per_page: 200 }
        })
        .done(function(resp) {
            if (!resp || !resp.success || !Array.isArray(resp.data)) {
                setLoading('No restaurants returned by API.');
                return;
            }

            allRestaurantes = resp.data;
            renderRestaurantes(allRestaurantes);

            /* Subtítulo / breadcrumb com cidade mais comum */
            var cityCount = {};
            allRestaurantes.forEach(function(r) {
                var c = (r.city_name || r.city_code || '').trim();
                if (c) cityCount[c] = (cityCount[c] || 0) + 1;
            });
            var mainCity = Object.keys(cityCount).sort(function(a, b) {
                return cityCount[b] - cityCount[a];
            })[0] || '';
            if (mainCity) {
                $('#city_subtitle').html('<strong>' + mainCity + '</strong>\'s restaurants selection');
                $('#breadcrumb_city').text(mainCity);
            }

            /* Popula select de cidades */
            var cities = [];
            allRestaurantes.forEach(function(r) {
                var c = (r.city_name || r.city_code || '').trim();
                if (c && cities.indexOf(c) < 0) cities.push(c);
            });
            cities.sort();
            var $sel = $('#selectLocation').empty()
                .append('<option value="">select the location</option>');
            $.each(cities, function(i, c) {
                $sel.append('<option value="' + c + '">' + c + '</option>');
            });
        })
        .fail(function() { setLoading('Failed to connect to the API.'); });

        $('#btnApply').on('click', applyFilters);
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>