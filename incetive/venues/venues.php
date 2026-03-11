<?php
require_once __DIR__ . '/../session_middleware.php';
requireAuthenticatedSession();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BLUMAR - Venues</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/estilo_novo_hotelshow.css">
    <style>
        .filters-bar {
            background-color: #d4af37;
        }

        .filters-text-bar,
        .stars-label,
        .star-checkbox label {
            color: #ffffff;
        }

        .apply-filter-btn {
            border-color: rgba(255, 255, 255, 0.9);
            color: #ffffff;
        }

        .apply-filter-btn:hover {
            background-color: #ffffff;
            color: #7a5c00;
        }

        #venuesGrid {
            align-items: stretch;
        }

        #venuesGrid .hotel-card {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        #venuesGrid .hotel-content {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
        }

        #venuesGrid .hotel-description {
            flex-grow: 1;
        }

        #venuesGrid .read-more-btn {
            margin-top: auto;
            align-self: flex-start;
        }

        #venuesGrid .vcard-topline {
            width: 20px;
            height: 3px;
            background: #e69928;
            border-radius: 2px;
            margin: 10px 0 8px;
        }

        #venuesGrid .vcard-city {
            font-size: 16px;
            color: #666;
            line-height: 1.2;
        }

        #venuesGrid .vcard-type {
            font-size: 28px;
            color: #333;
            line-height: 1.15;
            margin-bottom: 10px;
        }

        #venuesGrid .vcard-name {
            margin: 0 0 10px;
        }

        #venuesGrid .vcard-meta {
            font-size: 13px;
            color: #6b6b6b;
            line-height: 1.5;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/include/header_venues.php'; ?>

    <div class="filters-bar" id="filtersBar" style="display: none;">
        <div class="filters-left">
            <div class="filters-text-bar">
                <span>Filters</span>
                <svg class="filters-icon" viewBox="0 0 24 24" fill="white">
                    <path d="M3 4h18v2H3V4zm3 7h12v2H6v-2zm4 7h4v2h-4v-2z"/>
                </svg>
            </div>
            <select class="location-select" id="locationSelect" required>
                <option value="" disabled selected>select the location</option>
                <option value="Copacabana">Copacabana</option>
                <option value="Ipanema">Ipanema</option>
                <option value="Leblon">Leblon</option>
                <option value="Barra">Barra da Tijuca</option>
            </select>
        </div>

        <div class="stars-filter">
            <span class="stars-label">Stars</span>
            <div class="star-checkbox">
                <input type="checkbox" id="star5" value="5">
                <label for="star5">5</label>
            </div>
            <div class="star-checkbox">
                <input type="checkbox" id="star4" value="4">
                <label for="star4">4</label>
            </div>
            <div class="star-checkbox">
                <input type="checkbox" id="star3" value="3">
                <label for="star3">3</label>
            </div>
        </div>

        <button class="apply-filter-btn" id="applyFilters">Apply Filter</button>
    </div>

    <div class="filter-pills-section" id="filterPillsSection" style="display: none;">
        <div class="filter-pills" id="filterPills">
            <span class="filter-status">Filters applied</span>
            <div id="pillsContainer"></div>
            <button class="pill-btn clear-btn" id="clearAllFilters">Clear Filters</button>
        </div>
    </div>

    <div class="hotels-grid" id="venuesGrid">
        <div class="hotel-card">
            <div class="hotel-content">
                <p>Carregando venues...</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../js/eventos_hotels.js"></script>
    <script src="../js/hotels.js"></script>
    <script>
        const VENUES_API_URL = 'https://webdeveloper.blumar.com.br/desenv/roger/conteudo/api/venues.php';
        const PLACEHOLDER_IMAGE = '../img/hotel_01.png';

        function escapeHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function pickVenueImage(venue) {
            if (Array.isArray(venue.imagens) && venue.imagens.length > 0) {
                const primary = venue.imagens.find(function (img) { return img.is_primary; }) || venue.imagens[0];
                return primary && primary.image_url ? primary.image_url : PLACEHOLDER_IMAGE;
            }
            return PLACEHOLDER_IMAGE;
        }

        function renderVenues(venues) {
            if (!Array.isArray(venues) || venues.length === 0) {
                $('#venuesGrid').html('<div class="hotel-card"><div class="hotel-content"><p>Nenhum venue encontrado.</p></div></div>');
                return;
            }

            const html = venues.map(function (v) {
                const name = escapeHtml(v.name || v.nome || '-');
                const city = escapeHtml(v.city_name || v.city || '-');
                const desc = escapeHtml(v.description || v.descritivo_en || v.descritivo_pt || v.short_description || v.especialidade || '');
                const image = escapeHtml(pickVenueImage(v));
                const location = escapeHtml(v.address_line || v.especialidade || '-');
                const priceRange = escapeHtml(v.price_range || '-');
                const capacityMin = v.capacity_min != null && v.capacity_min !== '' ? String(v.capacity_min) : '-';
                const capacityMax = v.capacity_max != null && v.capacity_max !== '' ? String(v.capacity_max) : '-';
                const capacity = escapeHtml(capacityMin + ' to ' + capacityMax);
                const detailsUrl = v.cod_venues ? 'venues_show_section.php?id=' + encodeURIComponent(v.cod_venues) : '#';

                return '' +
                    '<div class="hotel-card">' +
                    '    <img src="' + image + '" alt="' + name + '" class="hotel-image" onerror="this.onerror=null;this.src=\'' + PLACEHOLDER_IMAGE + '\'">' +
                    '    <div class="hotel-content">' +
                    '        <div class="vcard-topline"></div>' +
                    '        <div class="vcard-city">' + city + '</div>' +
                    '        <div class="vcard-type">Venue</div>' +
                    '        <h2 class="hotel-name vcard-name">' + name + '</h2>' +
                    '        <div class="vcard-meta">' +
                    '            <div>Location: ' + location + '</div>' +
                    '            <div>Price range: ' + priceRange + '</div>' +
                    '            <div>Capacity: ' + capacity + '</div>' +
                    '        </div>' +
                    '        <p class="hotel-description">' + desc + '</p>' +
                    '        <a class="read-more-btn" href="' + detailsUrl + '">Read More</a>' +
                    '    </div>' +
                    '</div>';
            }).join('');

            $('#venuesGrid').html(html);
        }

        function loadVenues() {
            $.ajax({
                url: VENUES_API_URL,
                method: 'GET',
                dataType: 'json',
                data: {
                    request: 'listar_venues',
                    filtro_nome: '',
                    filtro_ativo: 'all',
                    cidade: '',
                    limit: 200
                }
            }).done(function (data) {
                renderVenues(Array.isArray(data) ? data : []);
            }).fail(function () {
                $('#venuesGrid').html('<div class="hotel-card"><div class="hotel-content"><p>Erro ao carregar venues.</p></div></div>');
            });
        }

        $(document).ready(function () {
            loadVenues();
        });
    </script>
    <?php include 'footer_show.php'; ?>
</body>
</html>
