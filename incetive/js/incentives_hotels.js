// incentives_hotels.js
$(document).ready(function () {
    const INCENTIVES_API_URL = 'api_incentives.php';
    const CITIES_API_URL = 'galeria.php';
    const DEFAULT_IMAGE = 'img/hotel_01.png';
    const DEFAULT_CITY = 'Rio de Janeiro';

    const $list = $('.hotels-grid');
    const $citySelect = $('.select_hotel, #locationSelect');
    const $applyFiltersBtn = $('#applyFilters');
    const $clearFiltersBtn = $('#clearAllFilters');
    const $filterPillsSection = $('#filterPillsSection');
    const $pillsContainer = $('#pillsContainer');

    function setLoading(message) {
        $list.html(`<p style="text-align:center; padding: 40px;">${message}</p>`);
    }

    function formatText(value, fallback = '-') {
        if (value === null || value === undefined || value === '') return fallback;
        return String(value).trim();
    }

    function pickMediaUrl(media) {
        if (!Array.isArray(media) || media.length === 0) return DEFAULT_IMAGE;

        const banner = media.find((m) => m.media_type === 'banner' && m.media_url);
        if (banner && banner.media_url) return banner.media_url;

        const gallery = media.find((m) => m.media_type === 'gallery' && m.media_url);
        if (gallery && gallery.media_url) return gallery.media_url;

        const any = media.find((m) => m.media_url);
        return any && any.media_url ? any.media_url : DEFAULT_IMAGE;
    }

    function createIncentiveCard(incentive) {
        const city = formatText(incentive.city_name || DEFAULT_CITY, DEFAULT_CITY);
        const country = formatText(incentive.country_code, '---');
        const status = formatText(incentive.inc_status, '---');
        const hotelName = formatText(incentive.hotel_name_snapshot, '---');
        const rooms = incentive.convention && incentive.convention.total_rooms
            ? incentive.convention.total_rooms
            : '---';

        const name = formatText(incentive.inc_name, 'Incentive sem nome');
        let description = formatText(
            incentive.inc_description,
            'Breve descritivo do Incentivo, algo em torno de 3 linhas para que nao fique um texto muito extenso.'
        );

        const MAX_DESC_CHARS = 180;
        if (description.length > MAX_DESC_CHARS) {
            description = description.slice(0, MAX_DESC_CHARS).trimEnd() + '...';
        }

        const image = pickMediaUrl(incentive.media);
        const incentiveId = incentive.inc_id;

        const $card = $('<div>', { class: 'hotel-card' });

        const $img = $('<img>', {
            src: image,
            alt: name,
            class: 'hotel-image'
        });
        $img.on('error', function () {
            this.src = DEFAULT_IMAGE;
        });

        const $content = $('<div>', { class: 'hotel-content' });

        const $tag = $('<div>', { class: 'hotel-tag' }).text(status);
        const $location = $('<div>', { class: 'hotel-location' }).text(city);
        const $type = $('<div>', { class: 'hotel-type' }).text('Incentive');

        const $h2 = $('<h2>', { class: 'hotel-name' }).text(name);

        const $details = $('<div>', { class: 'hotel-details' }).html(`
            <div>Status: ${status}</div>
            <div>Hotel: ${hotelName}</div>
            <div>Country: ${country}</div>
        `);

        const $desc = $('<p>', { class: 'hotel-description' }).text(description);

        const detailsUrl = incentiveId
            ? `incentive_hotel_show.php?id=${encodeURIComponent(incentiveId)}`
            : 'incentive_hotel_show.php';
        const $button = $('<a>', { class: 'read-more-btn', href: detailsUrl }).text('Read More');

        $content.append($tag, $location, $type, $h2, $details, $desc, $button);
        $card.append($img, $content);

        return $card;
    }

    function renderIncentives(incentives) {
        if (!Array.isArray(incentives) || incentives.length === 0) {
            setLoading('Nenhum incentivo encontrado para esta localizacao.');
            return;
        }

        $list.empty();
        const $fragment = $(document.createDocumentFragment());

        incentives.forEach((incentive) => {
            $fragment.append(createIncentiveCard(incentive));
        });

        $list.append($fragment);
    }

    function loadCities() {
        return $.ajax({
            url: CITIES_API_URL,
            method: 'GET',
            dataType: 'json',
            data: { action: 'list_cities' }
        }).done((response) => {
            const cities = response && response.cities ? response.cities : [];

            $citySelect.empty();
            $citySelect.append('<option value="">select the location</option>');

            cities.forEach((city) => {
                const name = formatText(city.nome_en || city.nome || city.name, '');
                if (name) {
                    $citySelect.append(`<option value="${name}">${name}</option>`);
                }
            });
        }).fail(() => {
            $citySelect.empty();
            $citySelect.append('<option value="">Erro ao carregar cidades</option>');
        });
    }

    function loadIncentivesByCity(cityName) {
        if (!cityName) {
            setLoading('Selecione uma localizacao para ver os incentivos.');
            return;
        }

        setLoading('Carregando incentivos...');

        $.ajax({
            url: INCENTIVES_API_URL,
            method: 'GET',
            dataType: 'json',
            data: {
                request: 'listar_incentives',
                filtro_cidade: cityName,
                per_page: 200
            }
        }).done((response) => {
            const incentives = response && Array.isArray(response.data) ? response.data : [];
            incentives.forEach((incentive) => {
                incentive.city_name = incentive.city_name || cityName;
            });
            renderIncentives(incentives);
        }).fail(() => {
            setLoading('Nao foi possivel carregar os incentivos. Tente novamente mais tarde.');
        });
    }

    function createFilterPill(text, type) {
        const $pill = $('<div>', { class: 'filter-pill' });
        $pill.text(text);

        const $remove = $('<span>', { class: 'remove-pill' }).text('x');
        $remove.on('click', () => {
            $pill.remove();
            if (type === 'location') {
                $citySelect.val('');
            }
        });

        $pill.append($remove);
        return $pill;
    }

    function updateFilterPills() {
        $pillsContainer.empty();

        const selectedCity = $citySelect.val();
        if (selectedCity) {
            $pillsContainer.append(createFilterPill(`Location: ${selectedCity}`, 'location'));
        }

        if ($pillsContainer.children().length > 0) {
            $filterPillsSection.show();
        } else {
            $filterPillsSection.hide();
        }
    }

    $(function () {
        if (!$list.length) return;

        setLoading('Carregando...');

        loadCities().done(() => {
            let selected = false;

            $citySelect.find('option').each(function () {
                if (
                    $(this).val().toLowerCase().includes('rio de janeiro') ||
                    $(this).text().toLowerCase().includes('rio de janeiro')
                ) {
                    $citySelect.val($(this).val());
                    selected = true;
                    return false;
                }
            });

            if (!selected) {
                $citySelect.append(`<option value="${DEFAULT_CITY}" selected>${DEFAULT_CITY}</option>`);
                $citySelect.val(DEFAULT_CITY);
            }

            const defaultCityValue = $citySelect.val();
            if (defaultCityValue) {
                loadIncentivesByCity(defaultCityValue);
                updateFilterPills();
            } else {
                setLoading('Selecione uma localizacao para ver os incentivos.');
            }
        }).fail(() => {
            $citySelect.append(`<option value="${DEFAULT_CITY}" selected>${DEFAULT_CITY}</option>`);
            loadIncentivesByCity(DEFAULT_CITY);
            updateFilterPills();
        });

        $citySelect.on('change', function () {
            const cityName = $(this).val();
            loadIncentivesByCity(cityName);
            updateFilterPills();
        });

        $applyFiltersBtn.on('click', function () {
            const city = $citySelect.val();
            loadIncentivesByCity(city);
            updateFilterPills();
        });

        $clearFiltersBtn.on('click', function () {
            $citySelect.val('');
            $pillsContainer.empty();
            $filterPillsSection.hide();
            setLoading('Selecione uma localizacao para ver os incentivos.');
        });
    });
});
