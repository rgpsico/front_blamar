// URL da nova API
const API_BASE_URL = 'https://webdeveloper.blumar.com.br/desenv/roger/conteudo/api/api_venues.php';
const DEFAULT_IMAGE = '../img/hotel_01.png';

// Seletores
const $locationSelect = $('#locationSelect');
const $venuesGrid = $('#venuesGrid');
const $applyFiltersBtn = $('#applyFilters');

// Array para armazenar todos os venues e facilitar o filtro no frontend
let allVenues = [];

function setLoading(message) {
    $venuesGrid.html(`
        <div class="hotel-card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
            <div class="hotel-content"><p>${message}</p></div>
        </div>
    `);
}

function escapeHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function createVenueCard(venue) {
    // Mapeando os dados da nova API
    const name = escapeHtml(venue.nome);
    const especialidade = escapeHtml(venue.especialidade || 'Venue');
    const city = venue.location && venue.location.city ? escapeHtml(venue.location.city) : '-';
    const address = venue.location && venue.location.address_line ? escapeHtml(venue.location.address_line) : '-';
    const priceRange = escapeHtml(venue.price_range || '-');
    
    // Capacidade
    const capMin = venue.capacity_min ? venue.capacity_min : '-';
    const capMax = venue.capacity_max ? venue.capacity_max : '-';
    const capacityText = (capMin !== '-' || capMax !== '-') ? `${capMin} to ${capMax}` : '-';

    // Imagem (A API já retorna ordenada, pegamos a primeira)
    let imgSrc = DEFAULT_IMAGE;
    if (venue.images && venue.images.length > 0) {
        imgSrc = venue.images[0].url;
    }

    const detailsUrl = `venues_show_section.php?id=${venue.venue_id}`;

    // Construindo o HTML respeitando as classes do seu CSS
    return `
        <div class="hotel-card">
            <img src="${imgSrc}" alt="${name}" class="hotel-image" onerror="this.src='${DEFAULT_IMAGE}'">
            <div class="hotel-content">
                <div class="vcard-topline"></div>
                <div class="vcard-city">${city}</div>
                <div class="vcard-type">Venue</div>
                <h2 class="hotel-name vcard-name">${name}</h2>
                <div class="vcard-meta">
                    <div>Location: ${address}</div>
                    <div>Price range: ${priceRange}</div>
                    <div>Capacity: ${capacityText}</div>
                </div>
                <p class="hotel-description">${especialidade}</p>
                <a class="read-more-btn" href="${detailsUrl}">Read More</a>
            </div>
        </div>
    `;
}

function renderVenues(venuesToRender) {
    if (!Array.isArray(venuesToRender) || venuesToRender.length === 0) {
        setLoading('Nenhum venue encontrado com estes filtros.');
        return;
    }

    $venuesGrid.empty();
    
    venuesToRender.forEach(venue => {
        $venuesGrid.append(createVenueCard(venue));
    });
}

function loadAllVenues() {
    setLoading('Carregando venues...');
    
    $.ajax({
        url: API_BASE_URL,
        method: 'GET',
        dataType: 'json',
        data: { 
            request: 'listar_venues',
            ativo: 'true', // Traz apenas os ativos
            limit: 500 
        }
    })
    .done((venues) => {
        if (!Array.isArray(venues) || venues.length === 0) {
            setLoading('Nenhum venue retornado pela API.');
            return;
        }

        allVenues = venues;
        renderVenues(allVenues);

        // Preencher o select de localidades (Cidades) dinamicamente
        const citiesSet = new Set();
        allVenues.forEach(v => {
            if (v.location && v.location.city) {
                const city = v.location.city.trim();
                if (city.length > 0) citiesSet.add(city);
            }
        });

        const sortedCities = [...citiesSet].sort();
        $locationSelect.empty().append('<option value="" selected>All Locations</option>');
        
        sortedCities.forEach(city => {
            $locationSelect.append(`<option value="${city}">${city}</option>`);
        });
    })
    .fail((jqXHR, textStatus, error) => {
        console.error('Erro na API:', jqXHR.status, textStatus, error);
        setLoading('Falha ao conectar com a API de venues.');
    });
}

function filterVenues() {
    const selectedCity = $locationSelect.val();
    let filtered = allVenues;

    // Filtro por cidade
    if (selectedCity && selectedCity !== '') {
        filtered = filtered.filter(v => 
            v.location && v.location.city && v.location.city.toUpperCase().includes(selectedCity.toUpperCase())
        );
    }

    // Nota: Removi o filtro de estrelas do JS porque a API de venues não possui estrelas.
    
    renderVenues(filtered);
}

// Inicialização
$(document).ready(function() {
    loadAllVenues();

    // Aplica o filtro quando o botão é clicado
    $applyFiltersBtn.on('click', filterVenues);
    
    // Se preferir que filtre automático ao mudar o select, descomente a linha abaixo:
    // $locationSelect.on('change', filterVenues);
});