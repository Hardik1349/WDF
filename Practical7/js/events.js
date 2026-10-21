const eventsList = document.getElementById('eventsList');
const eventSearch = document.getElementById('eventSearch');
const categoryFilter = document.getElementById('categoryFilter');
const sortEvents = document.getElementById('sortEvents');
const resultCount = document.getElementById('resultCount');
const pagination = document.getElementById('pagination');
const resetEvents = document.getElementById('resetEvents');

let allEvents = [];
let currentPage = 1;
const eventsPerPage = 6;

// Fetch the event records from an external JSON file.
async function loadEvents() {
    try {
        const response = await fetch('data/events.json');
        if (!response.ok) {
            throw new Error('The events JSON file could not be loaded.');
        }
        allEvents = await response.json();
        populateCategories();
        renderEvents();
    } catch (error) {
        eventsList.innerHTML = '<p class="events-message error-message">Unable to load events. Please run this website using a local server and check that data/events.json exists.</p>';
        resultCount.textContent = 'Events could not be loaded.';
        console.error('Fetch error:', error);
    }
}

function populateCategories() {
    const categories = [...new Set(allEvents.map(event => event.category))].sort();
    categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category;
        option.textContent = category;
        categoryFilter.appendChild(option);
    });
}

function getFilteredEvents() {
    const searchText = eventSearch.value.trim().toLowerCase();
    const selectedCategory = categoryFilter.value;
    const sortValue = sortEvents.value;

    const filtered = allEvents.filter(event => {
        const searchableText = `${event.title} ${event.category} ${event.venue} ${event.description} ${event.organizer}`.toLowerCase();
        const matchesSearch = searchableText.includes(searchText);
        const matchesCategory = selectedCategory === 'all' || event.category === selectedCategory;
        return matchesSearch && matchesCategory;
    });

    filtered.sort((a, b) => {
        if (sortValue === 'date-asc') return a.date.localeCompare(b.date);
        if (sortValue === 'date-desc') return b.date.localeCompare(a.date);
        if (sortValue === 'title-asc') return a.title.localeCompare(b.title);
        if (sortValue === 'title-desc') return b.title.localeCompare(a.title);
        return 0;
    });
    return filtered;
}

function formatDate(dateString) {
    return new Date(`${dateString}T00:00:00`).toLocaleDateString('en-IN', {
        day: 'numeric', month: 'short', year: 'numeric'
    });
}

function renderEvents() {
    const filteredEvents = getFilteredEvents();
    const totalPages = Math.ceil(filteredEvents.length / eventsPerPage);
    if (currentPage > totalPages) currentPage = Math.max(totalPages, 1);
    const startIndex = (currentPage - 1) * eventsPerPage;
    const pageEvents = filteredEvents.slice(startIndex, startIndex + eventsPerPage);

    resultCount.textContent = `Showing ${filteredEvents.length ? startIndex + 1 : 0}–${Math.min(startIndex + eventsPerPage, filteredEvents.length)} of ${filteredEvents.length} event(s)`;

    if (pageEvents.length === 0) {
        eventsList.innerHTML = '<p class="events-message">No events found. Try changing your search or filters.</p>';
    } else {
        eventsList.innerHTML = pageEvents.map(event => `
            <article class="dynamic-event-card">
                <div class="dynamic-event-top"><span class="event-category">${escapeHTML(event.category)}</span><span class="event-date">📅 ${formatDate(event.date)}</span></div>
                <h2>${escapeHTML(event.title)}</h2>
                <p class="dynamic-event-description">${escapeHTML(event.description)}</p>
                <div class="dynamic-event-details"><p>🕒 <strong>${escapeHTML(event.time)}</strong></p><p>📍 ${escapeHTML(event.venue)}</p><p>👥 ${escapeHTML(event.organizer)}</p></div>
            </article>
        `).join('');
    }
    renderPagination(totalPages);
}

// Escape JSON text before inserting it into HTML.
function escapeHTML(value) {
    return String(value).replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[character]);
}

function renderPagination(totalPages) {
    pagination.innerHTML = '';
    if (totalPages <= 1) return;

    const previousButton = createPageButton('← Previous', currentPage - 1, currentPage === 1);
    pagination.appendChild(previousButton);

    for (let page = 1; page <= totalPages; page++) {
        const button = createPageButton(String(page), page, false);
        if (page === currentPage) {
            button.classList.add('active-page');
            button.setAttribute('aria-current', 'page');
        }
        pagination.appendChild(button);
    }

    const nextButton = createPageButton('Next →', currentPage + 1, currentPage === totalPages);
    pagination.appendChild(nextButton);
}

function createPageButton(label, page, disabled) {
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = label;
    button.disabled = disabled;
    button.addEventListener('click', () => {
        currentPage = page;
        renderEvents();
        document.querySelector('.events-tools').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    return button;
}

function resetToFirstPage() {
    currentPage = 1;
    renderEvents();
}

eventSearch.addEventListener('input', resetToFirstPage);
categoryFilter.addEventListener('change', resetToFirstPage);
sortEvents.addEventListener('change', resetToFirstPage);
resetEvents.addEventListener('click', () => {
    eventSearch.value = '';
    categoryFilter.value = 'all';
    sortEvents.value = 'date-asc';
    currentPage = 1;
    renderEvents();
});

loadEvents();
