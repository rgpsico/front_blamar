// hotels.js (versão completa com Rio de Janeiro como default)
$(document).ready(function () {
    const API_BASE_URL = 'galeria.php';
    const HOTELS_API_URL = 'https://webdeveloper.blumar.com.br/desenv/roger/conteudo/api/hotels.php';
    const DEFAULT_IMAGE = 'img/hotel_01.png';
    const DEFAULT_CITY = 'Rio de Janeiro';  // Cidade que será selecionada por padrão
console.log('AQUI ESTÁ HOTELS.JS');
    // Seletores jQuery
    const $list = $('.hotels-grid');
    const $citySelect = $('.select_hotel, #locationSelect'); // suporta ambos os seletores que você usou
    const $starsSelect = $('.select_stars');
    const $applyFiltersBtn = $('#applyFilters');
    const $clearFiltersBtn = $('#clearAllFilters');
    const $filterPillsSection = $('#filterPillsSection');
    const $pillsContainer = $('#pillsContainer');

    // Função auxiliar para exibir mensagens de loading/erro
    function setLoading(message) {
        $list.html(`<p style="text-align:center; padding: 40px;">${message}</p>`);
    }

    // Formata valores que podem vir nulos/vazios
    function formatText(value, fallback = '-') {
        if (value === null || value === undefined || value === '') return fallback;
        return String(value).trim();
    }

    // Cria o card do hotel (adaptado para o layout do seu HTML)
    function createHotelCard(hotel) {
        const city = formatText(hotel.cidade || hotel.cidade_nome || hotel.nome_cidade || DEFAULT_CITY, 'Rio de Janeiro');
        const category = formatText(hotel.categoria || hotel.classificacao || 'Hotel');
        const stars = formatText(hotel.estrelas || hotel.classificacao || hotel.stars, '—');
        const rooms = formatText(hotel.quartos || hotel.numero_quartos || hotel.qtd_quartos, 'XXXXXX');
        const capacity = formatText(hotel.capacidade || hotel.capacidade_maxima, '—');

        const name = formatText(hotel.nome || hotel.nome_for || hotel.nome_produto, 'Hotel sem nome');
        let description = formatText(
            hotel.descricao_ingles || hotel.descricao || hotel.descricao_curta || hotel.descricao_espanhol || hotel.nome_produto,
            'Breve descritivo do Hotel, algo em torno de 3 linhas para que não fique um texto muito extenso.'
        );
        const MAX_DESC_CHARS = 180;
        if (description.length > MAX_DESC_CHARS) {
            description = description.slice(0, MAX_DESC_CHARS).trimEnd() + '...';
        }
        const image = hotel.imagem_fachada || hotel.imagem_piscina || hotel.foto_fachada || hotel.url_imagem || hotel.imagem || DEFAULT_IMAGE;
        const hotelId = hotel.frncod || hotel.id || hotel.codigo;

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

        const $tag = $('<div>', { class: 'hotel-tag' }).text('—');
        const $location = $('<div>', { class: 'hotel-location' }).text(city);
        const $type = $('<div>', { class: 'hotel-type' }).text(category);

        const $h2 = $('<h2>', { class: 'hotel-name' }).text(name);

        const $details = $('<div>', { class: 'hotel-details' }).html(`
            <div>Stars: ${stars}</div>
            <div>No. Rooms: ${rooms}</div>
            <div>Capacity: ${capacity}</div>
        `);

        const $desc = $('<p>', { class: 'hotel-description' }).text(description);

        const detailsUrl = hotelId ? `show.php?frcod=${encodeURIComponent(hotelId)}` : 'show.php';
        const $button = $('<a>', { class: 'read-more-btn', href: detailsUrl }).text('Read More');

        $content.append($tag, $location, $type, $h2, $details, $desc, $button);
        $card.append($img, $content);

        return $card;
    }

    // Renderiza a lista de hotéis
    function renderHotels(hotels) {
        if (!Array.isArray(hotels) || hotels.length === 0) {
            setLoading('Nenhum hotel encontrado para esta localização.');
            return;
        }

        $list.empty();
        const $fragment = $(document.createDocumentFragment());

        hotels.forEach((hotel) => {
            $fragment.append(createHotelCard(hotel));
        });

        $list.append($fragment);
    }

    // Carrega as cidades disponíveis
    function loadCities() {
        return $.ajax({
            url: API_BASE_URL,
            method: 'GET',
            dataType: 'json',
            data: { action: 'list_cities' }
        }).done((response) => {
            console.log('Cidades carregadas:', response);
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

    // Carrega hotéis por cidade
    function loadHotelsByCity(cityName) {
        if (!cityName) {
            setLoading('Selecione uma localização para ver os hotéis.');
            return;
        }

        setLoading('Carregando hotéis...');

        $.ajax({
            url: HOTELS_API_URL,
            method: 'GET',
            dataType: 'json',
            data: { request: 'listar_hoteis', cidade: cityName, limit: 200 }
        }).done((response) => {
            const hotels = Array.isArray(response) ? response : [];
            hotels.forEach((hotel) => {
                hotel.cidade = hotel.cidade || cityName;
            });
            renderHotels(hotels);
        }).fail(() => {
            setLoading('Não foi possível carregar os hotéis. Tente novamente mais tarde.');
        });
    }

    // Cria um "pill" de filtro aplicado
    function createFilterPill(text, type) {
        const $pill = $('<div>', { class: 'filter-pill' });
        $pill.text(text);
        
        const $remove = $('<span>', { class: 'remove-pill' }).text('×');
        $remove.on('click', () => {
            $pill.remove();
            // Aqui você pode limpar o filtro correspondente
            if (type === 'location') {
                $citySelect.val('');
            }
            // Se quiser recarregar sem filtros → loadHotelsByCity('')
        });

        $pill.append($remove);
        return $pill;
    }

    // Atualiza visual dos filtros aplicados
    function updateFilterPills() {
        $pillsContainer.empty();
        
        const selectedCity = $citySelect.val();
        if (selectedCity) {
            $pillsContainer.append(createFilterPill(`Location: ${selectedCity}`, 'location'));
        }

        // Se futuramente adicionar estrelas, pode incluir aqui também

        if ($pillsContainer.children().length > 0) {
            $filterPillsSection.show();
        } else {
            $filterPillsSection.hide();
        }
    }

    // Inicialização
    $(function () {
        if (!$list.length) return;

        setLoading('Carregando...');

        // 1. Carrega as cidades
        loadCities().done(() => {
            // 2. Tenta selecionar Rio de Janeiro automaticamente
            let selected = false;

            $citySelect.find('option').each(function() {
                if ($(this).val().toLowerCase().includes('rio de janeiro') || 
                    $(this).text().toLowerCase().includes('rio de janeiro')) {
                    $citySelect.val($(this).val());
                    selected = true;
                    return false; // sai do loop
                }
            });

            // Se não encontrou na lista da API, força a adição
            if (!selected) {
                $citySelect.append(`<option value="${DEFAULT_CITY}" selected>${DEFAULT_CITY}</option>`);
                $citySelect.val(DEFAULT_CITY);
            }

            // 3. Carrega os hotéis da cidade padrão
            const defaultCityValue = $citySelect.val();
            if (defaultCityValue) {
                loadHotelsByCity(defaultCityValue);
                updateFilterPills();
            } else {
                setLoading('Selecione uma localização para ver os hotéis.');
            }
        }).fail(() => {
            // Fallback caso a API de cidades falhe
            $citySelect.append(`<option value="${DEFAULT_CITY}" selected>${DEFAULT_CITY}</option>`);
            loadHotelsByCity(DEFAULT_CITY);
            updateFilterPills();
        });

        // Evento de mudança na cidade
        $citySelect.on('change', function () {
            const cityName = $(this).val();
            loadHotelsByCity(cityName);
            updateFilterPills();
        });

        // Botão Apply Filters (caso queira usar os checkboxes de estrelas no futuro)
        $applyFiltersBtn.on('click', function () {
            const city = $citySelect.val();
            // Aqui você pode coletar as estrelas selecionadas também
            // Exemplo: const stars = $('#star5:checked, #star4:checked, #star3:checked').map((i,e)=>$(e).val()).get();
            
            loadHotelsByCity(city);
            updateFilterPills();
        });

        // Limpar todos os filtros
        $clearFiltersBtn.on('click', function () {
            $citySelect.val('');
            // Desmarcar checkboxes de estrelas se existirem
            // $('#star5, #star4, #star3').prop('checked', false);
            
            $pillsContainer.empty();
            $filterPillsSection.hide();
            
            setLoading('Selecione uma localização para ver os hotéis.');
        });

        // Estrelas (ainda não implementado na API, mas preparado)
        $starsSelect.on('change', function () {
            console.log('Filtro de estrelas selecionado (ainda não implementado):', $(this).val());
            // Quando a API suportar, você pode chamar uma função de filtro aqui
        });
    });
});
