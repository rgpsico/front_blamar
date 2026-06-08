<?php include __DIR__ . '/../includes/header.php'; 

?>

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

                    <div class="stars_filter_group">
                        <span class="stars_label">Stars</span>
                        <label class="star_check_label">
                            <input type="checkbox" class="chk_stars" value="5"> 5
                        </label>
                        <label class="star_check_label">
                            <input type="checkbox" class="chk_stars" value="4"> 4
                        </label>
                        <label class="star_check_label">
                            <input type="checkbox" class="chk_stars" value="3"> 3
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
                <a href="hotel_list.php">Hotels</a>
                <i class="material-icons">&#xe315;</i>
                <a href="" id="breadcrumb_city">Rio de Janeiro</a>
            </div>
        </div>
    </div>
</section>

<section id="hotels_result">
    <div class="container">
        <div style="text-align:center;padding:40px;">Carregando hotéis...</div>
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

    /* filtros lado a lado */
    .filters_hotel_inner {
        display: flex;
        align-items: center;
        gap: 1.2vw;
    }

    /* Stars checkboxes */
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
        /* width: 14px;
        height: 14px; */
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
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    var API_URL     = '<?php echo htmlspecialchars(app_url('api/api_incentives.php'), ENT_QUOTES, 'UTF-8'); ?>';
    var DEFAULT_IMG = '<?php echo htmlspecialchars(asset_url('img/hotel_01.png'), ENT_QUOTES, 'UTF-8'); ?>';
    var allHotels   = [];

    function setLoading(msg) {
        $('#hotels_result .container').html('<div style="text-align:center;padding:40px;">' + msg + '</div>');
    }

    function fmt(val, fb) {
        return (val == null || val === '') ? (fb || '-') : String(val).trim();
    }

    function createCard(hotel) {
        var city  = fmt(hotel.city_name, 'City');
        var name  = fmt(hotel.inc_name, 'Hotel');
        var stars = hotel.star_rating ? hotel.star_rating : '-';
        var rooms = hotel.total_rooms ? hotel.total_rooms : '-';
        var thumb = fmt(hotel.thumnail || hotel.thumbnail, DEFAULT_IMG);
        var desc  = fmt(hotel.inc_description, '');
        var id    =  hotel.inc_id || '';

        return '<div class="box_hotels">'
            + '<img src="' + thumb + '" alt="' + name + '" onerror="this.src=\'' + DEFAULT_IMG + '\'">'
            + '<div class="box_hotels_conteudo">'
            + '<div class="line"></div>'
            + '<h3>' + city + '<br><span>Hotel</span></h3>'
            + '<h4>' + name + '</h4>'
            + '<p>Stars: ' + stars + '<br>No. Rooms: ' + rooms + '</p>'
            + (desc ? '<p class="hotels_description">' + desc + '</p>' : '')
            + '<a href="hotel_show_section.php?id=' + id + '"><button id="read_more">Read More</button></a>'
            + '</div></div>';
    }

    function renderHotels(list) {
        var $c = $('#hotels_result .container');
        if (!list || !list.length) {
            $c.html('<div style="text-align:center;padding:40px;">Nenhum hotel encontrado.</div>');
            return;
        }
        $c.empty();
        $.each(list, function(i, h) { $c.append(createCard(h)); });
    }

    function applyFilters() {
        var city    = $('#selectLocation').val();
        var checked = [];
        $('.chk_stars:checked').each(function() { checked.push(parseInt($(this).val(), 10)); });

        var filtered = allHotels;
        if (city) {
            filtered = $.grep(filtered, function(h) {
                return (h.city_name || '').toUpperCase().indexOf(city.toUpperCase()) >= 0;
            });
        }
        if (checked.length) {
            filtered = $.grep(filtered, function(h) {
                return checked.indexOf(Number(h.star_rating)) >= 0;
            });
        }
        renderHotels(filtered);
    }

    $(function() {
        setLoading('Carregando hotéis...');
        $.ajax({
            url: API_URL,
            method: 'GET',
            dataType: 'json',
            data: { request: 'listar_incentives_simples', per_page: 200 }
        })
        .done(function(resp) {
            if (!resp || !resp.success || !Array.isArray(resp.data)) {
                setLoading('Nenhum hotel retornado pela API.');
                return;
            }
            allHotels = resp.data;
            renderHotels(allHotels);

            // Subtítulo com cidade principal
            var cityCount = {};
            allHotels.forEach(function(h) {
                var c = (h.city_name || '').trim();
                if (c) cityCount[c] = (cityCount[c] || 0) + 1;
            });
            var mainCity = Object.keys(cityCount).sort(function(a, b) {
                return cityCount[b] - cityCount[a];
            })[0] || '';
            if (mainCity) {
                $('#city_subtitle').html('<strong>' + mainCity + '</strong>\'s hotels selection');
                $('#breadcrumb_city').text(mainCity);
            }

            // Popula select de cidades
            var cities = [];
            allHotels.forEach(function(h) {
                var c = (h.city_name || '').trim();
                if (c && cities.indexOf(c) < 0) cities.push(c);
            });
            cities.sort();
            var $sel = $('#selectLocation').empty().append('<option value="">select the location</option>');
            $.each(cities, function(i, c) {
                $sel.append('<option value="' + c + '">' + c + '</option>');
            });
        })
        .fail(function() { setLoading('Falha ao conectar com a API.'); });

        $('#btnApply').on('click', applyFilters);
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
