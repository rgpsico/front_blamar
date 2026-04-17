<?php include __DIR__ . '/../includes/header.php'; ?>

<section id="header_page">
    <div class="container">
        <div class="page_title">
            <h2>Restaurants</h2>
            <h3 id="city_subtitle">restaurants selection</h3>
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
                    <select class="select_hotel" id="selectCapacity" name="capacity">
                        <option value="">Capacity Filter</option>
                        <option value="50">Up to 50</option>
                        <option value="100">Up to 100</option>
                        <option value="200">Up to 200</option>
                        <option value="999">200+</option>
                    </select>
                    <div class="filter_toggle_group">
                        <span class="filter_toggle_label">View</span>
                        <label class="filter_toggle_item"><input type="radio" name="filter_view" value="yes"> yes</label>
                        <label class="filter_toggle_item"><input type="radio" name="filter_view" value="no"> no</label>
                    </div>
                    <div class="filter_toggle_group">
                        <span class="filter_toggle_label">Private Area</span>
                        <label class="filter_toggle_item"><input type="radio" name="filter_private" value="yes"> yes</label>
                        <label class="filter_toggle_item"><input type="radio" name="filter_private" value="no"> no</label>
                    </div>
                    <button class="btn_apply_filter" id="btnApply">Apply Filter</button>
                </div>
            </div>
        </div>
    </div>

    <div class="container_max ct02">
        <div class="container">
            <div class="breadcrumb">
                <a href="<?php echo htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8'); ?>">Incentive Area</a>
                <i class="material-icons">&#xe315;</i>
                <a href="res_list.php">Restaurants</a>
                <i class="material-icons">&#xe315;</i>
                <a href="" id="breadcrumb_city">Restaurants</a>
            </div>
        </div>
    </div>
</section>

<section id="restaurants_result">
    <div class="container">
        <div style="text-align:center;padding:40px;">Loading restaurants...</div>
    </div>
</section>

<style>
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

    .chose_city .material-icons {
        font-size: 1.1vw;
    }

    .filters_hotel_inner {
        display: flex;
        align-items: center;
        gap: 1.2vw;
        flex-wrap: wrap;
    }

    .filter_toggle_group {
        display: flex;
        align-items: center;
        gap: 0.4vw;
        color: #fff;
        font-size: 0.75vw;
        white-space: nowrap;
    }

    .filter_toggle_label {
        font-weight: 500;
        margin-right: 0.2vw;
    }

    .filter_toggle_item {
        display: flex;
        align-items: center;
        gap: 0.2vw;
        cursor: pointer;
        font-weight: 400;
    }

    .filter_toggle_item input[type="radio"] {
        accent-color: #e89127;
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

    #restaurants_result {
        padding-top: 2vw;
    }

    #restaurants_result .container {
        display: flex;
        flex-wrap: wrap;
        gap: 0;
    }

    .box_restaurants {
        float: left;
        width: 30%;
        background: #fff;
    }

    .box_restaurants:nth-child(3n+2) {
        margin: 0 5%;
    }

    .box_restaurants img {
        width: 100%;
        height: 15vw;
        object-fit: cover;
        border-radius: 0.5vw;
    }

    .box_restaurants_conteudo {
        padding: 1vw;
    }

    .box_restaurants_conteudo .line {
        width: 10%;
        height: 0.2vw;
        background: #e89127;
        margin-bottom: 0.5vw;
    }

    .box_restaurants_conteudo h3 {
        margin: 0.7vw 0 0 0;
        font-size: 0.9vw;
        font-weight: 400;
        color: #555;
        line-height: 1.4;
    }

    .box_restaurants_conteudo h3 span {
        display: block;
        font-size: 1vw;
        color: #555;
    }

    .box_restaurants_conteudo h4 {
        color: #4C4B4B;
        margin: 0.3vw 0 0.5vw 0;
        font-size: 1.1vw;
    }

    .box_restaurants_conteudo p {
        font-size: 0.85vw;
        line-height: 1.4vw;
        color: #555;
        margin: 0.2vw 0;
    }

    .restaurants_description {
        font-size: 0.85vw;
        color: #555;
        line-height: 1.4vw;
        margin-top: 0.5vw;
        display: -webkit-box;
        -webkit-line-clamp: 4;
        line-clamp: 4;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .box_restaurants button {
        border: 0;
        background: #e89127;
        padding: 0.6vw 1.6vw;
        color: #fff;
        border-radius: 0.4vw;
        margin-top: 0.8vw;
        cursor: pointer;
        font-size: 0.75vw;
    }
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    var API_URL = '<?php echo htmlspecialchars(app_url('api/api_restaurante_incentive.php'), ENT_QUOTES, 'UTF-8'); ?>';
    var DEFAULT_IMG = '<?php echo htmlspecialchars(asset_url('img/restaurant_default.png'), ENT_QUOTES, 'UTF-8'); ?>';
    var allRestaurants = [];
    var cityMap = {};

    function setLoading(msg) {
        $('#restaurants_result .container').html('<div style="text-align:center;padding:40px;">' + msg + '</div>');
    }

    function fmt(val, fb) {
        return (val == null || val === '') ? (fb || '-') : String(val).trim();
    }

    function cityLabel(code) {
        return cityMap[String(code)] || code || 'City';
    }

    function resolveRestaurantImage(restaurant) {
        if (Array.isArray(restaurant.images) && restaurant.images.length) {
            var cover = restaurant.images.find(function(image) {
                return image && (image.is_cover === true || image.is_cover === 't');
            });

            if (cover && cover.image_url) {
                return cover.image_url;
            }

            if (restaurant.images[0] && restaurant.images[0].image_url) {
                return restaurant.images[0].image_url;
            }
        }

        return DEFAULT_IMG;
    }

    function createCard(restaurant) {
        var city = cityLabel(restaurant.city_code);
        var name = fmt(restaurant.name, 'Restaurant');
        var desc = fmt(restaurant.short_description, '');
        var capacity = restaurant.capacity ? 'Capacity: ' + restaurant.capacity : '';
        var privateArea = restaurant.has_private_area === true || restaurant.has_private_area === 't';
        var hasView = restaurant.has_view === true || restaurant.has_view === 't';
        var features = [];
        var thumb = resolveRestaurantImage(restaurant);
        var id = restaurant.id || '';

        if (privateArea) features.push('Private area');
        if (hasView) features.push('View');

        var featuresHtml = '';
        if (hasView)     featuresHtml += '<p>With a view</p>';
        if (privateArea) featuresHtml += '<p>Private Area</p>';
        if (capacity)    featuresHtml += '<p>' + capacity + '</p>';

        return '<div class="box_restaurants">'
            + '<img src="' + thumb + '" alt="' + name + '" onerror="this.src=\'' + DEFAULT_IMG + '\'">'
            + '<div class="box_restaurants_conteudo">'
            + '<div class="line"></div>'
            + '<h3>' + city + '<br><span>Restaurant</span></h3>'
            + '<h4>' + name + '</h4>'
            + featuresHtml
            + (desc ? '<p class="restaurants_description">' + desc + '</p>' : '')
            + '<a href="res_show_section.php?id=' + id + '"><button>Read More</button></a>'
            + '</div></div>';
    }

    function renderRestaurants(list) {
        var $c = $('#restaurants_result .container');
        if (!list || !list.length) {
            $c.html('<div style="text-align:center;padding:40px;">No restaurant found.</div>');
            return;
        }

        $c.empty();
        $.each(list, function(i, restaurant) {
            $c.append(createCard(restaurant));
        });
    }

    function applyFilters() {
        var city     = $('#selectLocation').val();
        var capacity = $('#selectCapacity').val();
        var view     = $('input[name="filter_view"]:checked').val();
        var priv     = $('input[name="filter_private"]:checked').val();
        var filtered = allRestaurants;

        if (city) {
            filtered = $.grep(filtered, function(r) {
                return String(r.city_code || '') === String(city);
            });
        }

        if (capacity) {
            var cap = parseInt(capacity, 10);
            if (cap === 999) {
                filtered = $.grep(filtered, function(r) {
                    return parseInt(r.capacity || 0, 10) > 200;
                });
            } else {
                filtered = $.grep(filtered, function(r) {
                    return parseInt(r.capacity || 0, 10) <= cap;
                });
            }
        }

        if (view === 'yes') {
            filtered = $.grep(filtered, function(r) {
                return r.has_view === true || r.has_view === 't';
            });
        } else if (view === 'no') {
            filtered = $.grep(filtered, function(r) {
                return r.has_view !== true && r.has_view !== 't';
            });
        }

        if (priv === 'yes') {
            filtered = $.grep(filtered, function(r) {
                return r.has_private_area === true || r.has_private_area === 't';
            });
        } else if (priv === 'no') {
            filtered = $.grep(filtered, function(r) {
                return r.has_private_area !== true && r.has_private_area !== 't';
            });
        }

        renderRestaurants(filtered);
    }

    function loadCities() {
        return $.ajax({
            url: API_URL,
            method: 'GET',
            dataType: 'json',
            data: {
                request: 'listar_cidades_tpo'
            }
        }).done(function(resp) {
            var rows = resp && Array.isArray(resp.data) ? resp.data : [];
            var $sel = $('#selectLocation').empty().append('<option value="">select the location</option>');

            rows.forEach(function(city) {
                var code = String(city.tpocidcod || '');
                var label = city.nome_en || city.nome_pt || code;
                if (!code) return;
                cityMap[code] = label;
                $sel.append('<option value="' + code + '">' + label + '</option>');
            });
        });
    }

    $(function() {
        setLoading('Loading restaurants...');

        $.when(
            loadCities(),
            $.ajax({
                url: API_URL,
                method: 'GET',
                dataType: 'json',
                data: {
                    request: 'listar_restaurantes_paginate',
                    per_page: 200,
                    page: 1
                }
            })
        )
        .done(function(cityResp, restaurantsResp) {
            var resp = restaurantsResp[0];

            if (!resp || !Array.isArray(resp.data)) {
                setLoading('No restaurants returned by the API.');
                return;
            }

            allRestaurants = resp.data;
            renderRestaurants(allRestaurants);

            var cityCount = {};
            allRestaurants.forEach(function(restaurant) {
                var city = cityLabel(restaurant.city_code);
                if (city && city !== 'City') {
                    cityCount[city] = (cityCount[city] || 0) + 1;
                }
            });

            var mainCity = Object.keys(cityCount).sort(function(a, b) {
                return cityCount[b] - cityCount[a];
            })[0] || '';

            if (mainCity) {
                $('#city_subtitle').html('<strong>' + mainCity + '</strong>\'s restaurants selection');
                $('#breadcrumb_city').text(mainCity);
            }
        })
        .fail(function() {
            setLoading('Failed to connect to the API.');
        });

        $('#btnApply').on('click', applyFilters);
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
