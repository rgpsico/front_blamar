<?php include __DIR__ . '/../includes/header.php'; ?>

<section id="header_page">
    <div class="container">
        <div class="page_title">
            <h2>Activities</h2>
            <h3 id="city_subtitle">activities & experiences</h3>
        </div>
        <div class="chose_city">
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
                    <select class="select_hotel" id="selectLocation" name="city">
                        <option value="">select the location</option>
                    </select>

                    <!-- Filtros baseados nos campos da API -->
                    <div class="stars_filter_group">
                        <span class="stars_label">Type</span>
                        <label class="star_check_label">
                            <input type="checkbox" class="chk_type" value="classic"> Classic
                        </label>
                        <label class="star_check_label">
                            <input type="checkbox" class="chk_type" value="favourite"> Favourite
                        </label>
                        <label class="star_check_label">
                            <input type="checkbox" class="chk_type" value="out_of_box"> Out of the box
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
                <a href="activities_list.php">Activities</a>
                <i class="material-icons">&#xe315;</i>
                <a href="" id="breadcrumb_city">Rio de Janeiro</a>
            </div>
        </div>
    </div>
</section>

<section id="activities_result">
    <div class="container">
        <div class="loading_msg" style="text-align:center;padding:40px;">Loading activities...</div>
    </div>
</section>

<style>
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
    .stars_label {
        font-size: 0.75vw;
        color: #fff;
        font-weight: 500;
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
        color: #2c6e9e;
    }

    /* Grid de cards */
    #activities_result .container {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        padding: 30px 0;
    }
    #activities_result .loading_msg {
        grid-column: 1 / -1;
    }

    /* Card */
    .box_activities {
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
        display: flex;
        flex-direction: column;
    }
    .box_activities img {
        width: 100%;
        height: 220px;
        object-fit: cover;
    }
    .box_activities_conteudo {
        padding: 15px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .box_activities_conteudo .line {
        height: 3px;
        background: #e6a817;
        width: 40px;
        margin-bottom: 10px;
    }
    .box_activities_conteudo .act_city {
        font-size: 0.78em;
        color: #777;
        margin: 0 0 2px 0;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .box_activities_conteudo .act_label {
        font-size: 0.78em;
        color: #555;
        margin: 0 0 6px 0;
    }
    .box_activities_conteudo h4 {
        font-size: 1.05em;
        margin: 0 0 6px 0;
        color: #222;
        font-weight: 600;
    }
    .box_activities_conteudo .act_capacity {
        font-size: 0.8em;
        color: #666;
        margin: 0 0 8px 0;
    }
    .act_tags {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
        margin-bottom: 8px;
    }
    .act_tag {
        font-size: 0.68em;
        padding: 2px 8px;
        border-radius: 20px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .act_tag.classic    { background: #e8f0fb; color: #2c6e9e; }
    .act_tag.favourite  { background: #fff8e1; color: #b8860b; }
    .act_tag.out_of_box { background: #fce4ec; color: #c2185b; }

    .act_favourite_star {
        color: #e6a817;
        font-size: 1.1em;
        margin-left: auto;
    }

    .activities_description {
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
        #activities_result .container {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 600px) {
        #activities_result .container {
            grid-template-columns: 1fr;
        }
    }
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    var API_URL      = '<?php echo htmlspecialchars(app_url('api/api_atividades_incentive.php'), ENT_QUOTES, 'UTF-8'); ?>';
    var DEFAULT_IMG  = '<?php echo htmlspecialchars(asset_url('img/activity_default.png'), ENT_QUOTES, 'UTF-8'); ?>';
    var allActivities = [];

    function setLoading(msg) {
        $('#activities_result .container').html(
            '<div class="loading_msg" style="text-align:center;padding:40px;grid-column:1/-1;">' + msg + '</div>'
        );
    }

    function fmt(val, fb) {
        return (val == null || val === '') ? (fb || '') : String(val).trim();
    }

    function createCard(a) {
        var city     = fmt(a.cidade_nome, 'City');
        var name     = fmt(a.nome, 'Activity');
        var thumb    = a.foto_capa_url || DEFAULT_IMG;
        var desc     = fmt(a.descricao_curta, '');
        var id       = a.id || '';
        var capMin   = a.capacidade_min;
        var capMax   = a.capacidade_max;
        var pr       = fmt(a.price_range_label, '');

        // Tags
        var tags = '';
        if (a.is_classic)    tags += '<span class="act_tag classic">Classic</span>';
        if (a.is_favourite)  tags += '<span class="act_tag favourite">&#9733; Favourite</span>';
        if (a.is_out_of_box) tags += '<span class="act_tag out_of_box">Out of the box</span>';

        // Capacidade
        var cap = (capMin && capMax) ? 'Capacity: ' + capMin + ' to ' + capMax : '';
        if (cap && pr) cap += ' &nbsp;|&nbsp; ' + pr;

        return ''
            + '<div class="box_activities">'
            +   '<img src="' + thumb + '" alt="' + name + '" onerror="this.src=\'' + DEFAULT_IMG + '\'">'
            +   '<div class="box_activities_conteudo">'
            +     '<div class="line"></div>'
            +     '<p class="act_city">' + city + '</p>'
            +     '<p class="act_label">Activities</p>'
            +     '<h4>' + name + '</h4>'
            +     (cap ? '<p class="act_capacity">' + cap + '</p>' : '')
            +     (tags ? '<div class="act_tags">' + tags + '</div>' : '')
            +     (desc ? '<p class="activities_description">' + desc + '</p>' : '')
            +     '<a class="btn_read_more" href="show_section.php?id=' + id + '">Read More</a>'
            +   '</div>'
            + '</div>';
    }

    function renderActivities(list) {
        var $c = $('#activities_result .container');
        if (!list || !list.length) {
            $c.html('<div class="loading_msg" style="text-align:center;padding:40px;grid-column:1/-1;">No activities found.</div>');
            return;
        }
        $c.empty();
        $.each(list, function(i, act) {
            $c.append(createCard(act));
        });
    }

    function applyFilters() {
        var city    = $('#selectLocation').val();
        var checked = [];
        $('.chk_type:checked').each(function() {
            checked.push($(this).val());
        });

        var filtered = allActivities;

        if (city) {
            filtered = $.grep(filtered, function(a) {
                return (a.cidade_nome || '').toUpperCase() === city.toUpperCase();
            });
        }

        if (checked.length) {
            filtered = $.grep(filtered, function(a) {
                return checked.some(function(chk) {
                    if (chk === 'classic')    return a.is_classic    === true;
                    if (chk === 'favourite')  return a.is_favourite  === true;
                    if (chk === 'out_of_box') return a.is_out_of_box === true;
                    return false;
                });
            });
        }

        renderActivities(filtered);
    }

    $(function() {
        setLoading('Loading activities...');

        $.ajax({
            url: API_URL,
            method: 'GET',
            dataType: 'json',
            data: { request: 'listar_activities' }
        })
        .done(function(resp) {
            // API retorna array direto
            if (!resp || !Array.isArray(resp)) {
                setLoading('No activities returned by the API.');
                return;
            }

            allActivities = resp;
            renderActivities(allActivities);

            // Cidade principal no subtítulo e breadcrumb
            var cityCount = {};
            allActivities.forEach(function(a) {
                var c = (a.cidade_nome || '').trim();
                if (c) cityCount[c] = (cityCount[c] || 0) + 1;
            });
            var mainCity = Object.keys(cityCount).sort(function(a, b) {
                return cityCount[b] - cityCount[a];
            })[0] || '';

            if (mainCity) {
                $('#city_subtitle').html('<strong>' + mainCity + '</strong>\'s activities & experiences');
                $('#breadcrumb_city').text(mainCity);
            }

            // Popula select de cidades
            var cities = [];
            allActivities.forEach(function(a) {
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