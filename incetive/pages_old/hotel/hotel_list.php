<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../../css/estilo.css">
    <link rel="stylesheet" href="../../css/estilo_mobile.css">
    <title>Blumar Hotels - Rio de Janeiro</title>
</head>
<body>
    <?php include __DIR__ . '/include/header_list.php'; ?>

<section id="hotels_result">
        <div class="container">
            <!-- Os hotéis serão renderizados aqui dinamicamente via JavaScript -->
            <div style="text-align:center;padding:40px;">
                Carregando hotéis...
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        const API_URL = '../../api_incentives.php';
        const DEFAULT_IMAGE = '../../img/hotel_01.png';

        const $citySelect = $('.select_hotel');
        const $starsSelect = $('.select_stars');

        let allHotels = [];

        function setLoading(message) {
            $('#hotels_result .container').html(`<div style="text-align:center;padding:40px;">${message}</div>`);
        }

        function formatText(value, fallback = '-') {
            return (value == null || value === '') ? fallback : String(value).trim();
        }

        function createHotelCard(hotel) {
            console.log('Hotel data:', hotel); // Log para depuração
            const city = formatText(hotel.city_name, 'Cidade');
            const name = formatText(hotel.inc_name, 'Hotel');
            const stars = hotel.star_rating ? hotel.star_rating : '-';
            const rooms = hotel.total_rooms ? hotel.total_rooms : '-';
            const totalIncentives = hotel.total_incentives ? hotel.total_incentives : 0;
            const thumb = formatText(hotel.thumnail || hotel.thumbnail || '', DEFAULT_IMAGE);

            return `
                <div class="box_hotels">
                    <img src="${thumb}" alt="${name}" onerror="this.src='${DEFAULT_IMAGE}'">
                    <div class="box_hotels_conteudo">
                        <div class="line"></div>
                        <h3>${city}<br><span>Hotel</span></h3>
                        <h4>${name}</h4>
                        <p>Stars: ${stars}
                        No. Rooms: ${rooms}
                        Incentives: ${totalIncentives}
                        </p>
                        <p class="hotels_description">${formatText(hotel.description, 'Descrição não disponível.')}</p>
                        <a href="hotel_show_section.php?id=${hotel.hotel_ref_id || ''}">
                            <button id="read_more">Read More</button>
                        </a>
                    </div>
                </div>
            `;
        }

        function renderHotels(hotels) {
            const $container = $('#hotels_result .container');
            if (!Array.isArray(hotels) || hotels.length === 0) {
                $container.html('<div style="text-align:center;padding:40px;">Nenhum hotel encontrado.</div>');
                return;
            }

            $container.empty();
            hotels.forEach(hotel => $container.append(createHotelCard(hotel)));
        }

        function loadHotels() {
            setLoading('Carregando hotéis...');
            $.ajax({
                url: API_URL,
                method: 'GET',
                dataType: 'json',
                data: {
                    request: 'listar_incentives_simples',
                    per_page: 200
                }
            })
            .done((resp) => {
                if (!resp || !resp.success || !Array.isArray(resp.data)) {
                    setLoading('Nenhum hotel retornado pela API.');
                    return;
                }

                allHotels = resp.data;
                renderHotels(allHotels);

                // Popula select de cidades
                const citiesSet = new Set();
                const cityCount = {};
                allHotels.forEach(h => {
                    const city = (h.city_name || '').trim();
                    if (city.length > 2) {
                        citiesSet.add(city);
                        cityCount[city] = (cityCount[city] || 0) + 1;
                    }
                });
                const sortedCities = [...citiesSet].sort();
                $citySelect.empty().append('<option value="">select the location</option>');
                sortedCities.forEach(city => $citySelect.append(`<option value="${city}">${city}</option>`));

                // Atualiza subtítulo e breadcrumb com a cidade principal
                const mainCity = Object.keys(cityCount).sort((a, b) => cityCount[b] - cityCount[a])[0] || '';
                if (mainCity) {
                    $('#city_subtitle').html(`<strong>${mainCity}</strong>'s hotels selection`);
                    $('#breadcrumb_city').text(mainCity);
                }
            })
            .fail((jqXHR, textStatus, error) => {
                console.error('Erro na API:', jqXHR.status, textStatus, error);
                setLoading('Falha ao conectar com a API de hotéis.');
            });
        }

        function filterHotels() {
            const selectedCity = $citySelect.val();
            const selectedStars = $starsSelect.val();

            let filtered = allHotels;

            if (selectedCity) {
                filtered = filtered.filter(h =>
                    (h.city_name || '').toUpperCase().includes(selectedCity.toUpperCase())
                );
            }

            if (selectedStars) {
                const stars = parseInt(selectedStars, 10);
                filtered = filtered.filter(h => Number(h.star_rating) === stars);
            }

            renderHotels(filtered);
        }

        $(function() {
            loadHotels();
            $citySelect.on('change', filterHotels);
            $starsSelect.on('change', filterHotels);
            $('#btnApply').on('click', filterHotels);
        });
    </script>
</body>
</html>

