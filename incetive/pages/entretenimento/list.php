<?php include __DIR__ . '/../includes/header.php'; ?>

<section id="header_page">
    <div class="container">
        <div class="page_title">
            <h2>Entertainment</h2>
            <h3 id="city_subtitle">entertainments selection</h3>
        </div>
        <div class="chose_city">
            <span>Change the city</span>
            <i class="material-icons">expand_more</i>
        </div>
    </div>

    <!-- Barra de filtros laranja -->
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

                    <!-- Filtros baseados nos campos da API -->
                    <div class="stars_filter_group">
                        <label class="star_check_label">
                            <input type="checkbox" class="chk_type" value="show"> show
                        </label>
                        <label class="star_check_label">
                            <input type="checkbox" class="chk_type" value="music"> music
                        </label>
                        <label class="star_check_label">
                            <input type="checkbox" class="chk_type" value="others"> others
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
                <a href="entertainment_list.php">Entertainment</a>
                <i class="material-icons">&#xe315;</i>
                <a href="" id="breadcrumb_city">Rio de Janeiro</a>
            </div>
        </div>
    </div>
</section>

<section id="entertainment_result">
    <div class="container">
        <div class="loading_msg" style="text-align:center;padding:40px;">Loading entertainment...</div>
    </div>
</section>

<style>
    /* ── Sobrescreve a cor da barra de filtro para laranja (Entertainment) ── */
    .container_max.ct01 {
        background: #e6a817 !important;
    }
    .filters_hotel h3 {
        color: #fff !important;
    }
    .filters_hotel .material-icons {
        color: #fff !important;
    }
    .select_hotel {
        border-color: rgba(255,255,255,0.7) !important;
        color: #fff !important;
        background-color: transparent !important;
    }
    .select_hotel option {
        color: #333;
        background: #fff;
    }

    #header_page .container {
        display: flex;
        align-items: center;
    }
    #header_page .page_title h3 { font-weight: 400; }
    #header_page .page_title h3 strong { font-weight: 700; }

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

    .filters_hotel_inner {
        display: flex;
        align-items: center;
        gap: 1.2vw;
    }

    .stars_filter_group {
        display: flex;
        align-items: center;
        gap: 0.6vw;
    }
    .star_check_label {
        display: flex;
        align-items: center;
        gap: 3px;
        font-size: 0.75vw;
        color: #fff;
        cursor: pointer;
        white-space: nowrap;
    }
    .star_check_label input[type="checkbox"] {
        cursor: pointer;
        accent-color: #fff;
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
        color: #e6a817;
    }

    /* Grid de cards */
    #entertainment_result .container {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        padding: 30px 0;
    }
    #entertainment_result .loading_msg {
        grid-column: 1 / -1;
    }

    /* Card */
    .box_entertainment {
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
        display: flex;
        flex-direction: column;
    }
    .box_entertainment img {
        width: 100%;
        height: 220px;
        object-fit: cover;
    }
    .box_entertainment_conteudo {
        padding: 15px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .box_entertainment_conteudo .line {
        height: 3px;
        background: #e6a817;
        width: 40px;
        margin-bottom: 10px;
    }
    .box_entertainment_conteudo .ent_city {
        font-size: 0.78em;
        color: #777;
        margin: 0 0 2px 0;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .box_entertainment_conteudo .ent_label {
        font-size: 0.78em;
        color: #555;
        margin: 0 0 6px 0;
    }
    .box_entertainment_conteudo h4 {
        font-size: 1.05em;
        margin: 0 0 6px 0;
        color: #222;
        font-weight: 600;
    }
    .box_entertainment_conteudo .ent_subtype {
        font-size: 0.8em;
        color: #888;
        margin: 0 0 8px 0;
        font-style: italic;
    }

    .entertainment_description {
        font-size: 0.85em;
        color: #555;
        line-height: 1.5;
        flex: 1;
        margin-bottom: 14px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .btn_read_more {
        display: inline-block;
        background: #e6a817;
        color: #fff;
        border: none;
        padding: 8px 18px;
        border-radius: 4px;
        font-size: 0.82em;
        cursor: pointer;
        text-decoration: none;
        font-weight: 500;
        transition: background 0.2s;
        align-self: flex-start;
    }
    .btn_read_more:hover { background: #c8900f; }

    @media (max-width: 900px) {
        #entertainment_result .container {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 600px) {
        #entertainment_result .container {
            grid-column: 1fr;
        }
    }
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    var API_URL      = '<?php echo htmlspecialchars(app_url('api/api_entertainment_incentive.php'), ENT_QUOTES, 'UTF-8'); ?>';
    var DEFAULT_IMG  = '<?php echo htmlspecialchars(asset_url('img/entertainment_default.png'), ENT_QUOTES, 'UTF-8'); ?>';
    var allItems = [];

    function setLoading(msg) {
        $('#entertainment_result .container').html(
            '<div class="loading_msg" style="text-align:center;padding:40px;grid-column:1/-1;">' + msg + '</div>'
        );
    }

    function fmt(val, fb) {
        return (val == null || val === '') ? (fb || '') : String(val).trim();
    }

    function createCard(a) {
        var city    = fmt(a.cidade_nome, 'City');
        var name    = fmt(a.nome, 'Entertainment');
        var thumb   = a.foto_capa_url || DEFAULT_IMG;
        var desc    = fmt(a.descricao_curta, '');
        var id      = a.id || '';
        var subtype = fmt(a.subtipo, ''); // ex: "Music", "Show", etc.

        return ''
            + '<div class="box_entertainment">'
            +   '<img src="' + thumb + '" alt="' + name + '" onerror="this.src=\'' + DEFAULT_IMG + '\'">'
            +   '<div class="box_entertainment_conteudo">'
            +     '<div class="line"></div>'
            +     '<p class="ent_city">' + city + '</p>'
            +     '<p class="ent_label">Entertainment</p>'
            +     '<h4>' + name + '</h4>'
            +     (subtype ? '<p class="ent_subtype">' + subtype + '</p>' : '')
            +     (desc ? '<p class="entertainment_description">' + desc + '</p>' : '')
            +     '<a class="btn_read_more" href="show_entertainment.php?id=' + id + '">Read More</a>'
            +   '</div>'
            + '</div>';
    }

    function renderItems(list) {
        var $c = $('#entertainment_result .container');
        if (!list || !list.length) {
            $c.html('<div class="loading_msg" style="text-align:center;padding:40px;grid-column:1/-1;">No entertainment found.</div>');
            return;
        }
        $c.empty();
        $.each(list, function(i, item) {
            $c.append(createCard(item));
        });
    }

    function applyFilters() {
        var city    = $('#selectLocation').val();
        var checked = [];
        $('.chk_type:checked').each(function() {
            checked.push($(this).val());
        });

        var filtered = allItems;

        if (city) {
            filtered = $.grep(filtered, function(a) {
                return (a.cidade_nome || '').toUpperCase() === city.toUpperCase();
            });
        }

        if (checked.length) {
            filtered = $.grep(filtered, function(a) {
                var sub = (a.subtipo || '').toLowerCase();
                return checked.some(function(chk) {
                    if (chk === 'show')   return sub === 'show';
                    if (chk === 'music')  return sub === 'music';
                    if (chk === 'others') return sub !== 'show' && sub !== 'music';
                    return false;
                });
            });
        }

        renderItems(filtered);
    }

    $(function() {
        setLoading('Loading entertainment...');

        $.ajax({
            url: API_URL,
            method: 'GET',
            dataType: 'json',
            data: { request: 'listar_entertainment' }
        })
        .done(function(resp) {
            if (!resp || !Array.isArray(resp)) {
                setLoading('No entertainment returned by the API.');
                return;
            }

            allItems = resp;
            renderItems(allItems);

            // Cidade principal no subtítulo e breadcrumb
            var cityCount = {};
            allItems.forEach(function(a) {
                var c = (a.cidade_nome || '').trim();
                if (c) cityCount[c] = (cityCount[c] || 0) + 1;
            });
            var mainCity = Object.keys(cityCount).sort(function(a, b) {
                return cityCount[b] - cityCount[a];
            })[0] || '';

            if (mainCity) {
                $('#city_subtitle').html('<strong>' + mainCity + '</strong>\'s entertainments selection');
                $('#breadcrumb_city').text(mainCity);
            }

            // Popula select de cidades
            var cities = [];
            allItems.forEach(function(a) {
                var c = (a.cidade_nome || '').trim();
                if (c && cities.indexOf(c) < 0) cities.push(c);
            });
            cities.sort();

            var $sel = $('#selectLocation').empty().append('<option value="">select the location</option>');
            $.each(cities, function(i, c) {
                $sel.append('<option value="' + c + '">' + c + '</option>');
            });
        })
        .fail(function(xhr) {
            setLoading('Failed to connect to the API. (' + xhr.status + ')');
        });

        $('#btnApply').on('click', applyFilters);
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>