<?php
require_once __DIR__ . '/../../session_middleware.php';
require_once __DIR__ . '/../../util/connection.php';
require_once __DIR__ . '/../includes/url_helpers.php';
// requireAuthenticatedSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BLUMAR - Venues</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- css/estilo_novo_hotelshow.css -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('css/estilo_novo_hotelshow.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('css/estilo.css'), ENT_QUOTES, 'UTF-8'); ?>">

    <style>
        /* ---- Cor dourada para venues ---- */
        .filters-bar { background-color: #d4af37; }
        .filters-text-bar,
        .stars-label,
        .star-checkbox label { color: #ffffff; }
        .apply-filter-btn {
            border-color: rgba(255,255,255,0.9);
            color: #ffffff;
        }
        .apply-filter-btn:hover {
            background-color: #ffffff;
            color: #7a5c00;
        }

        /* ---- Barra sempre visível (sem toggle) ---- */
        .filters-bar {
            display: flex !important;
        }

        /* ---- Segundo select de capacity ---- */
        .capacity-select {
            background-color: #ffffff;
            border: 1px solid #d7e6ec;
            padding: 7px 34px 7px 14px;
            border-radius: 8px;
            font-size: 13px;
            color: #5e6b73;
            min-width: 180px;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L6 6L11 1' stroke='%2390A4AE' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
        }
        .capacity-select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255,255,255,0.35);
        }

        /* ---- Alinha os dois selects na mesma linha ---- */
        .filters-left {
            gap: 12px;
        }

        /* ---- Cards iguais ao padrão ---- */
        #venuesGrid { align-items: stretch; }
        #venuesGrid .hotel-card  { display: flex; flex-direction: column; height: 100%; }
        #venuesGrid .hotel-content { display: flex; flex-direction: column; flex: 1 1 auto; }
        #venuesGrid .hotel-description { flex-grow: 1; }
        #venuesGrid .read-more-btn { margin-top: auto; align-self: flex-start; }

        /* linha laranja e tipografia do card */
        #venuesGrid .vcard-topline {
            width: 20px; height: 3px;
            background: #e69928; border-radius: 2px;
            margin: 10px 0 8px;
        }
        #venuesGrid .vcard-city  { font-size: 14px; color: #666; line-height: 1.2; }
        #venuesGrid .vcard-type  { font-size: 22px; color: #333; line-height: 1.15; margin-bottom: 6px; }
        #venuesGrid .vcard-name  { margin: 0 0 10px; }
        #venuesGrid .vcard-meta  { font-size: 13px; color: #6b6b6b; line-height: 1.5; margin-bottom: 10px; }
    </style>
</head>
<body>

    <?php include __DIR__ . '/include/header_venues.php'; ?>

    <!-- Título / "Change the city" -->


    <!-- Breadcrumb -->
  

    <!-- Barra de filtros dourada — dois selects simples, sempre visível -->
    <div class="filters-bar" id="filtersBar">
        <div class="filters-left">
            <div class="filters-text-bar">
                <span>Filters</span>
                <svg class="filters-icon" viewBox="0 0 24 24" fill="white" width="14" height="14">
                    <path d="M3 4h18v2H3V4zm3 7h12v2H6v-2zm4 7h4v2h-4v-2z"/>
                </svg>
            </div>

            <select class="location-select" id="locationSelect">
                <option value="" selected>select the location</option>
            </select>

            <select class="capacity-select" id="capacitySelect">
                <option value="" selected>Capacity Filter</option>
                <option value="0-50">Up to 50</option>
                <option value="50-100">50 to 100</option>
                <option value="100-200">100 to 200</option>
                <option value="200-500">200 to 500</option>
                <option value="500+">500+</option>
            </select>
        </div>

        <button class="apply-filter-btn" id="applyFilters">Apply Filter</button>
    </div>

    <!-- Grid de venues -->
    <div class="hotels-grid" id="venuesGrid">
        <div class="hotel-card">
            <div class="hotel-content">
                <p>Carregando venues...</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        var API_URL     = '<?php echo htmlspecialchars(app_url('api/api_venues.php'), ENT_QUOTES, 'UTF-8'); ?>';
        var DEFAULT_IMG = '<?php echo htmlspecialchars(asset_url('img/hotel_01.png'), ENT_QUOTES, 'UTF-8'); ?>';
        var allVenues   = [];

        function esc(str) {
            return String(str || '')
                .replace(/&/g,'&amp;').replace(/</g,'&lt;')
                .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        function setLoading(msg) {
            $('#venuesGrid').html(
                '<div class="hotel-card" style="grid-column:1/-1;text-align:center;padding:40px;">'
                + '<div class="hotel-content"><p>' + msg + '</p></div></div>'
            );
        }

        function createCard(v) {
            var name    = esc(v.nome || v.name || 'Venue');
            var city    = v.location && v.location.city ? esc(v.location.city) : '-';
            var addr    = v.location && v.location.address_line ? esc(v.location.address_line) : '-';
            var price   = esc(v.price_range || '-');
            var capMin  = v.capacity_min != null ? v.capacity_min : '-';
            var capMax  = v.capacity_max != null ? v.capacity_max : '-';
            var capText = (capMin !== '-' || capMax !== '-') ? capMin + ' to ' + capMax : '-';
            var desc    = esc(v.especialidade || v.description || '');
            var imgSrc  = (v.images && v.images.length > 0) ? v.images[0].url : DEFAULT_IMG;
            var url     = 'venues_show_section.php?id=' + (v.venue_id || '');

            return '<div class="hotel-card">'
                + '<img src="' + imgSrc + '" alt="' + name + '" class="hotel-image" onerror="this.src=\'' + DEFAULT_IMG + '\'">'
                + '<div class="hotel-content">'
                + '<div class="vcard-topline"></div>'
                + '<div class="vcard-city">' + city + '</div>'
                + '<div class="vcard-type">Venue</div>'
                + '<h2 class="hotel-name vcard-name">' + name + '</h2>'
                + '<div class="vcard-meta">'
                + 'Location: ' + addr + '<br>'
                + 'Price range: ' + price + '<br>'
                + 'Capacity: ' + capText
                + '</div>'
                + (desc ? '<p class="hotel-description">' + desc + '</p>' : '')
                + '<a class="read-more-btn" href="' + url + '">Read More</a>'
                + '</div></div>';
        }

        function renderVenues(list) {
            if (!list || !list.length) {
                setLoading('Nenhum venue encontrado com estes filtros.');
                return;
            }
            $('#venuesGrid').empty();
            $.each(list, function(i, v) { $('#venuesGrid').append(createCard(v)); });
        }

        function applyFilters() {
            var city = $('#locationSelect').val();
            var cap  = $('#capacitySelect').val();
            var filtered = allVenues;

            if (city) {
                filtered = $.grep(filtered, function(v) {
                    return v.location && (v.location.city || '').toUpperCase().indexOf(city.toUpperCase()) >= 0;
                });
            }

            if (cap) {
                filtered = $.grep(filtered, function(v) {
                    var max = v.capacity_max != null ? Number(v.capacity_max) : null;
                    if (cap === '0-50')    return max !== null && max <= 50;
                    if (cap === '50-100')  return max !== null && max > 50  && max <= 100;
                    if (cap === '100-200') return max !== null && max > 100 && max <= 200;
                    if (cap === '200-500') return max !== null && max > 200 && max <= 500;
                    if (cap === '500+')    return max !== null && max > 500;
                    return true;
                });
            }

            renderVenues(filtered);
        }

        $(function() {
            setLoading('Carregando venues...');
            $.ajax({
                url: API_URL,
                method: 'GET',
                dataType: 'json',
                data: { request: 'listar_venues', ativo: 'true', limit: 500 }
            })
            .done(function(venues) {
                if (!Array.isArray(venues) || !venues.length) {
                    setLoading('Nenhum venue retornado pela API.');
                    return;
                }
                allVenues = venues;
                renderVenues(allVenues);

                // Subtítulo com cidade principal
                var cityCount = {};
                allVenues.forEach(function(v) {
                    var c = v.location && v.location.city ? v.location.city.trim() : '';
                    if (c) cityCount[c] = (cityCount[c] || 0) + 1;
                });
                var mainCity = Object.keys(cityCount).sort(function(a, b) {
                    return cityCount[b] - cityCount[a];
                })[0] || '';
                if (mainCity) {
                    $('#city_subtitle').text(mainCity + "'s venues selection");
                    $('#breadcrumb_city').text(mainCity);
                }

                // Popula select de cidades
                var cities = [];
                allVenues.forEach(function(v) {
                    var c = v.location && v.location.city ? v.location.city.trim() : '';
                    if (c && cities.indexOf(c) < 0) cities.push(c);
                });
                cities.sort();
                var $sel = $('#locationSelect').empty().append('<option value="" selected>select the location</option>');
                $.each(cities, function(i, c) {
                    $sel.append('<option value="' + c + '">' + c + '</option>');
                });
            })
            .fail(function() { setLoading('Falha ao conectar com a API.'); });

            $('#applyFilters').on('click', applyFilters);
        });
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
