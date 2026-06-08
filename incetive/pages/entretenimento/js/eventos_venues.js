
       let activeFilters = {
    location: null,
    stars: []
};

// Elementos principais
const filtersText = document.querySelector('.filters-text');
const filtersTextBar = document.querySelector('.filters-text-bar');
const filtersBar = document.getElementById('filtersBar');
const filterPillsSection = document.getElementById('filterPillsSection');
const breadcrumbSection = document.querySelector('.breadcrumb-section');

// Função para abrir o painel de filtros
function openFiltersPanel() {
    filtersBar.style.display = 'flex';
    breadcrumbSection.style.display = 'none';
    filterPillsSection.style.display = 'none';

    // Troca visual: esconde o "Filters" normal e mostra o da barra
    filtersText.style.display = 'none';
    filtersTextBar.style.display = 'flex'; // ou 'inline-flex' dependendo do seu CSS
}

// Função para fechar o painel de filtros
function closeFiltersPanel() {
    filtersBar.style.display = 'none';

    // Decide o que mostrar: pills ou breadcrumb
    if (hasActiveFilters()) {
        filterPillsSection.style.display = 'flex';
        breadcrumbSection.style.display = 'none';
    } else {
        breadcrumbSection.style.display = 'block';
        filterPillsSection.style.display = 'none';
    }

    // Troca visual: volta o "Filters" normal e esconde o da barra
    filtersText.style.display = 'flex'; // ou 'inline-flex'
    filtersTextBar.style.display = 'none';
}

// Abrir painel ao clicar no Filters normal
filtersText.addEventListener('click', function () {
    openFiltersPanel();
});

// Fechar painel ao clicar no Filters da barra
filtersTextBar.addEventListener('click', function () {
    closeFiltersPanel();
});

// Aplicar filtros
document.getElementById('applyFilters').addEventListener('click', function () {
    // Pega o local selecionado
    const locationSelect = document.getElementById('locationSelect');
    activeFilters.location = locationSelect.value || null;

    // Pega as estrelas selecionadas
    activeFilters.stars = [];
    document.querySelectorAll('.star-checkbox input[type="checkbox"]:checked').forEach(cb => {
        activeFilters.stars.push(cb.value);
    });

    // Fecha o painel e atualiza visual
    closeFiltersPanel();

    // Atualiza e mostra os pills
    updateFilterPills();

    if (hasActiveFilters()) {
        filterPillsSection.style.display = 'flex';
    }

    console.log('Filtros aplicados:', activeFilters);
});

// Atualizar pills de filtro
function updateFilterPills() {
    const pillsContainer = document.getElementById('pillsContainer');
    pillsContainer.innerHTML = '';

    // Pill de localização
    if (activeFilters.location && activeFilters.location !== '') {
        const pill = createPill(activeFilters.location, 'location');
        pillsContainer.appendChild(pill);
    }

    // Pills de estrelas
    activeFilters.stars.forEach(star => {
        const pill = createPill(star + ' stars', 'star-' + star);
        pillsContainer.appendChild(pill);
    });
}

// Criar pill
function createPill(text, id) {
    const pill = document.createElement('button');
    pill.className = 'pill-btn';
    pill.textContent = text;
    pill.dataset.filterId = id;

    pill.addEventListener('click', function () {
        removePill(id);
        this.remove();
    });

    return pill;
}

// Remover filtro específico
function removePill(filterId) {
    if (filterId === 'location') {
        activeFilters.location = null;
        document.getElementById('locationSelect').value = '';
    } else if (filterId.startsWith('star-')) {
        const star = filterId.replace('star-', '');
        activeFilters.stars = activeFilters.stars.filter(s => s !== star);
        const checkbox = document.getElementById('star' + star);
        if (checkbox) checkbox.checked = false;
    }

    // Atualiza visual dos pills
    updateFilterPills();

    // Se não houver mais filtros → mostra breadcrumb
    if (!hasActiveFilters()) {
        filterPillsSection.style.display = 'none';
        breadcrumbSection.style.display = 'block';
    }
}

// Limpar todos os filtros
document.getElementById('clearAllFilters').addEventListener('click', function () {
    activeFilters = {
        location: null,
        stars: []
    };

    // Limpa os campos
    document.getElementById('locationSelect').value = '';
    document.querySelectorAll('.star-checkbox input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
    });

    // Fecha e atualiza visual
    closeFiltersPanel();
    updateFilterPills();
});

// Verifica se há filtros ativos
function hasActiveFilters() {
    return activeFilters.location || activeFilters.stars.length > 0;
}

// Funcionalidade change city (exemplo)
document.querySelector('.change-city')?.addEventListener('click', function () {
    alert('Funcionalidade de troca de cidade');
});

// Fechar ao clicar fora
document.addEventListener('click', function (event) {
    if (filtersBar.style.display === 'flex' &&
        !filtersBar.contains(event.target) &&
        !filtersText.contains(event.target) &&
        !filtersTextBar.contains(event.target)) {

        closeFiltersPanel();
    }
});
