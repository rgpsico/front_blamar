const SIDEBAR_API_DEFAULT = 'api_incentives.php';
const SIDEBAR_API_URL = window.INCENTIVES_API_URL || SIDEBAR_API_DEFAULT;

function pickBestNote(notes) {
  if (!Array.isArray(notes) || notes.length === 0) return null;
  const byLang = (lang) => notes.find((n) => n && n.note && String(n.language || '').toUpperCase() === lang);
  return byLang('EN') || byLang('PT') || notes.find((n) => n && n.note) || null;
}

function buildMapsUrls(contact) {
  if (!contact) return { linkUrl: null, embedUrl: null };

  const hasLatLng =
    contact.latitude !== null &&
    contact.latitude !== undefined &&
    contact.longitude !== null &&
    contact.longitude !== undefined &&
    `${contact.latitude}` !== '' &&
    `${contact.longitude}` !== '';

  const address = contact.address ? String(contact.address).trim() : '';

  if (contact.google_maps_url) {
    const raw = String(contact.google_maps_url).trim();
    const isShort = raw.includes('maps.app.goo.gl');
    const isGoogleMaps = raw.includes('google.com/maps');

    const linkUrl = raw;
    let embedUrl = null;

    if (isGoogleMaps) {
      embedUrl = raw.includes('output=embed') ? raw : `${raw}${raw.includes('?') ? '&' : '?'}output=embed`;
    } else if (isShort) {
      if (hasLatLng) {
        embedUrl = `https://www.google.com/maps?q=${encodeURIComponent(contact.latitude)},${encodeURIComponent(contact.longitude)}&z=14&output=embed`;
      } else if (address) {
        embedUrl = `https://www.google.com/maps?q=${encodeURIComponent(address)}&z=14&output=embed`;
      }
    }

    return { linkUrl, embedUrl };
  }

  if (hasLatLng) {
    const url = `https://www.google.com/maps?q=${encodeURIComponent(contact.latitude)},${encodeURIComponent(contact.longitude)}&z=14`;
    return { linkUrl: url, embedUrl: `${url}&output=embed` };
  }

  if (address) {
    const url = `https://www.google.com/maps?q=${encodeURIComponent(address)}&z=14`;
    return { linkUrl: url, embedUrl: `${url}&output=embed` };
  }

  return { linkUrl: null, embedUrl: null };
}

function updateSidebar(program, relations) {
  const starEl = document.getElementById('sidebarStarRating');
  if (starEl) {
    starEl.textContent = program && program.star_rating ? program.star_rating : '--';
  }

  const roomsEl = document.getElementById('sidebarTotalRooms');
  if (roomsEl) {
    roomsEl.textContent = program && program.total_rooms ? program.total_rooms : '--';
  }

  const noteEl = document.getElementById('sidebarPersonalNote');
  if (noteEl) {
    const note = pickBestNote(relations && relations.notes ? relations.notes : []);
    noteEl.textContent = note && note.note ? note.note : 'No notes available.';
  }

  const mapFrame = document.getElementById('sidebarMapFrame');
  const mapLink = document.getElementById('sidebarMapLink');
  const { linkUrl, embedUrl } = buildMapsUrls(relations && relations.hotel_contact ? relations.hotel_contact : null);
  if (mapFrame && embedUrl) {
    mapFrame.src = embedUrl;
  }
  if (mapLink) {
    mapLink.href = linkUrl || '#';
  }
}

function loadSidebarIncentive(id) {
  if (!id) return;
  const url = `${SIDEBAR_API_URL}?request=buscar_incentive&id=${encodeURIComponent(id)}`;
  fetch(url)
    .then((res) => res.json())
    .then((payload) => {
      if (!payload || payload.error || payload.success === false) return;
      const data = payload.data || payload;
      const program = data.program || data;
      const relations = data.relations || data;
      updateSidebar(program, relations);
    })
    .catch(() => {});
}

document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');
  if (id && !isNaN(Number(id))) {
    loadSidebarIncentive(id);
  }
});
