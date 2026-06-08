let activeFilters = {
    location: null,
    stars: []
};

const filtersText = document.querySelector('.filters-text');
const filtersTextBar = document.querySelector('.filters-text-bar');
const filtersBar = document.getElementById('filtersBar');
const filterPillsSection = document.getElementById('filterPillsSection');
const breadcrumbSection = document.querySelector('.breadcrumb-section');
const applyFiltersBtn = document.getElementById('applyFilters');
const clearAllFiltersBtn = document.getElementById('clearAllFilters');
const locationSelect = document.getElementById('locationSelect');
const pillsContainer = document.getElementById('pillsContainer');

function hasActiveFilters() {
    return Boolean(activeFilters.location) || activeFilters.stars.length > 0;
}

function showElement(element, displayValue) {
    if (element) {
        element.style.display = displayValue;
    }
}

function openFiltersPanel() {
    showElement(filtersBar, 'flex');
    showElement(filterPillsSection, 'none');

    if (breadcrumbSection) {
        breadcrumbSection.style.display = 'none';
    }

    if (filtersText) {
        filtersText.style.display = 'none';
    }

    if (filtersTextBar) {
        filtersTextBar.style.display = 'flex';
    }
}

function closeFiltersPanel() {
    showElement(filtersBar, 'none');

    if (hasActiveFilters()) {
        showElement(filterPillsSection, 'flex');
        showElement(breadcrumbSection, 'none');
    } else {
        showElement(filterPillsSection, 'none');
        showElement(breadcrumbSection, 'block');
    }

    if (filtersText) {
        filtersText.style.display = 'flex';
    }

    if (filtersTextBar) {
        filtersTextBar.style.display = 'none';
    }
}

function createPill(text, id) {
    const pill = document.createElement('button');
    pill.className = 'pill-btn';
    pill.textContent = text;
    pill.dataset.filterId = id;

    pill.addEventListener('click', function () {
        removePill(id);
    });

    return pill;
}

function updateFilterPills() {
    if (!pillsContainer) {
        return;
    }

    pillsContainer.innerHTML = '';

    if (activeFilters.location) {
        pillsContainer.appendChild(createPill(activeFilters.location, 'location'));
    }

    activeFilters.stars.forEach(function (star) {
        pillsContainer.appendChild(createPill(star + ' stars', 'star-' + star));
    });
}

function removePill(filterId) {
    if (filterId === 'location') {
        activeFilters.location = null;
        if (locationSelect) {
            locationSelect.value = '';
        }
    } else if (filterId.indexOf('star-') === 0) {
        const star = filterId.replace('star-', '');
        activeFilters.stars = activeFilters.stars.filter(function (value) {
            return value !== star;
        });

        const checkbox = document.getElementById('star' + star);
        if (checkbox) {
            checkbox.checked = false;
        }
    }

    updateFilterPills();

    if (!hasActiveFilters()) {
        showElement(filterPillsSection, 'none');
        showElement(breadcrumbSection, 'block');
    }
}

if (filtersText) {
    filtersText.addEventListener('click', function () {
        openFiltersPanel();
    });
}

if (filtersTextBar) {
    filtersTextBar.addEventListener('click', function () {
        closeFiltersPanel();
    });
}

if (applyFiltersBtn) {
    applyFiltersBtn.addEventListener('click', function () {
        activeFilters.location = locationSelect && locationSelect.value ? locationSelect.value : null;

        activeFilters.stars = [];
        document.querySelectorAll('.star-checkbox input[type="checkbox"]:checked').forEach(function (checkbox) {
            activeFilters.stars.push(checkbox.value);
        });

        closeFiltersPanel();
        updateFilterPills();

        if (hasActiveFilters()) {
            showElement(filterPillsSection, 'flex');
        }
    });
}

if (clearAllFiltersBtn) {
    clearAllFiltersBtn.addEventListener('click', function () {
        activeFilters = {
            location: null,
            stars: []
        };

        if (locationSelect) {
            locationSelect.value = '';
        }

        document.querySelectorAll('.star-checkbox input[type="checkbox"]').forEach(function (checkbox) {
            checkbox.checked = false;
        });

        updateFilterPills();
        closeFiltersPanel();
    });
}

document.querySelector('.change-city')?.addEventListener('click', function () {
    alert('Funcionalidade de troca de cidade');
});

document.addEventListener('click', function (event) {
    if (!filtersBar || filtersBar.style.display !== 'flex') {
        return;
    }

    const clickedFiltersTrigger = filtersText && filtersText.contains(event.target);
    const clickedFiltersBar = filtersBar.contains(event.target);

    if (!clickedFiltersTrigger && !clickedFiltersBar) {
        closeFiltersPanel();
    }
});
