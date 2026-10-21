'use strict';

const apiUrl = 'api/events.php';
const page = document.querySelector('.api-wrap');
const csrfToken = page.dataset.csrf;
const form = document.getElementById('eventForm');
const eventIdInput = document.getElementById('eventId');
const titleInput = document.getElementById('title');
const dateInput = document.getElementById('eventDate');
const venueInput = document.getElementById('venue');
const categoryInput = document.getElementById('category');
const descriptionInput = document.getElementById('description');
const saveButton = document.getElementById('saveButton');
const cancelButton = document.getElementById('cancelButton');
const refreshButton = document.getElementById('refreshButton');
const eventsBody = document.getElementById('eventsBody');
const eventCount = document.getElementById('eventCount');
const messageBox = document.getElementById('apiMessage');
let cachedEvents = [];

function showMessage(message, type = 'ok') {
  messageBox.textContent = message;
  messageBox.className = `api-message ${type}`;
}

async function requestApi(method, data = null) {
  const options = { method, headers: { 'Accept': 'application/json' } };
  if (data !== null) {
    options.headers['Content-Type'] = 'application/json';
    options.headers['X-CSRF-Token'] = csrfToken;
    options.body = JSON.stringify(data);
  }
  const response = await fetch(apiUrl, options);
  const result = await response.json();
  if (!response.ok || !result.success) throw new Error(result.message || 'The request failed.');
  return result;
}

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  })[char]);
}

function formatDate(value) {
  const date = new Date(String(value).replace(' ', 'T'));
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
}

function renderEvents(events) {
  cachedEvents = events;
  eventCount.textContent = `${events.length} event${events.length === 1 ? '' : 's'} found`;
  if (events.length === 0) {
    eventsBody.innerHTML = '<tr><td colspan="6" class="empty">No events found. Use the form above to add one.</td></tr>';
    return;
  }
  eventsBody.innerHTML = events.map(event => `
    <tr>
      <td>${escapeHtml(event.event_id)}</td>
      <td><strong>${escapeHtml(event.title)}</strong><br><span class="muted">${escapeHtml(event.description || '')}</span></td>
      <td>${escapeHtml(formatDate(event.event_date))}</td>
      <td>${escapeHtml(event.venue)}</td>
      <td>${escapeHtml(event.category)}</td>
      <td><button type="button" class="api-btn small" data-action="edit" data-id="${escapeHtml(event.event_id)}">Edit</button>
      <button type="button" class="api-btn danger small" data-action="delete" data-id="${escapeHtml(event.event_id)}">Delete</button></td>
    </tr>`).join('');
}

async function loadEvents() {
  eventsBody.innerHTML = '<tr><td colspan="6" class="empty">Loading events...</td></tr>';
  try {
    const result = await requestApi('GET');
    renderEvents(result.events || []);
  } catch (error) {
    eventCount.textContent = 'Could not load events';
    eventsBody.innerHTML = '<tr><td colspan="6" class="empty">Events could not be loaded.</td></tr>';
    showMessage(error.message, 'err');
  }
}

function resetForm() {
  form.reset();
  eventIdInput.value = '';
  saveButton.textContent = 'Add Event';
  cancelButton.hidden = true;
}

form.addEventListener('submit', async event => {
  event.preventDefault();
  const id = eventIdInput.value;
  const data = {
    title: titleInput.value.trim(),
    event_date: dateInput.value,
    venue: venueInput.value.trim(),
    category: categoryInput.value.trim(),
    description: descriptionInput.value.trim()
  };
  try {
    saveButton.disabled = true;
    const result = id
      ? await requestApi('PUT', { ...data, event_id: Number(id) })
      : await requestApi('POST', data);
    showMessage(result.message || 'Saved successfully.');
    resetForm();
    await loadEvents();
  } catch (error) {
    showMessage(error.message, 'err');
  } finally {
    saveButton.disabled = false;
  }
});

eventsBody.addEventListener('click', async event => {
  const button = event.target.closest('button[data-action]');
  if (!button) return;
  const id = Number(button.dataset.id);
  const selected = cachedEvents.find(item => Number(item.event_id) === id);
  if (!selected) return;

  if (button.dataset.action === 'edit') {
    eventIdInput.value = selected.event_id;
    titleInput.value = selected.title || '';
    dateInput.value = String(selected.event_date || '').replace(' ', 'T').slice(0, 16);
    venueInput.value = selected.venue || '';
    categoryInput.value = selected.category || '';
    descriptionInput.value = selected.description || '';
    saveButton.textContent = 'Update Event';
    cancelButton.hidden = false;
    titleInput.focus();
    window.scrollTo({ top: 0, behavior: 'smooth' });
    showMessage(`Editing event #${id}. Make changes and choose Update Event.`);
    return;
  }

  if (button.dataset.action === 'delete') {
    if (!window.confirm(`Delete event "${selected.title}"? This cannot be undone.`)) return;
    try {
      button.disabled = true;
      const result = await requestApi('DELETE', { event_id: id });
      if (eventIdInput.value === String(id)) resetForm();
      showMessage(result.message || 'Event deleted.');
      await loadEvents();
    } catch (error) {
      showMessage(error.message, 'err');
      button.disabled = false;
    }
  }
});

cancelButton.addEventListener('click', resetForm);
refreshButton.addEventListener('click', loadEvents);
loadEvents();
