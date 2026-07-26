const config = globalThis.FEEDEVENT_CONNECTOR_CONFIG;
const statusElement = document.querySelector('#status');
const form = document.querySelector('#event-form');
const success = document.querySelector('#success');
const submitButton = document.querySelector('#submit-button');

function field(id) {
  return document.querySelector(`#${id}`);
}

function showError(message) {
  statusElement.hidden = false;
  statusElement.classList.add('error');
  statusElement.textContent = message;
}

function toLocalDateTime(value) {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  const local = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
  return local.toISOString().slice(0, 16);
}

function extractEventFromPage() {
  const clean = value => typeof value === 'string'
    ? value.replace(/\s+/g, ' ').trim()
    : '';
  const meta = (attribute, value) => clean(
    document.querySelector(`meta[${attribute}="${value}"]`)?.content,
  );
  const types = value => Array.isArray(value) ? value : [value];

  let structuredEvent = null;
  for (const script of document.querySelectorAll('script[type="application/ld+json"]')) {
    try {
      const parsed = JSON.parse(script.textContent);
      const entries = Array.isArray(parsed)
        ? parsed
        : (Array.isArray(parsed?.['@graph']) ? parsed['@graph'] : [parsed]);
      structuredEvent = entries.find(entry =>
        entry && types(entry['@type']).some(type => String(type).endsWith('Event')),
      );
      if (structuredEvent) break;
    } catch {
      // Ignore invalid JSON-LD supplied by the page.
    }
  }

  const location = structuredEvent?.location;
  const address = typeof location?.address === 'object'
    ? [
        location.address.streetAddress,
        location.address.postalCode,
        location.address.addressLocality,
      ].filter(Boolean).join(', ')
    : clean(location?.address);
  const organizer = typeof structuredEvent?.organizer === 'object'
    ? structuredEvent.organizer.name
    : structuredEvent?.organizer;
  const image = Array.isArray(structuredEvent?.image)
    ? structuredEvent.image[0]
    : (structuredEvent?.image?.url || structuredEvent?.image);

  return {
    title: clean(
      structuredEvent?.name
      || meta('property', 'og:title')
      || document.querySelector('h1')?.textContent
      || document.title,
    ).replace(/\s*[|·-]\s*Facebook\s*$/i, ''),
    description: clean(
      structuredEvent?.description
      || meta('property', 'og:description')
      || meta('name', 'description'),
    ),
    date_start: structuredEvent?.startDate
      || document.querySelector('time[datetime]')?.dateTime
      || document.querySelector('[itemprop="startDate"]')?.getAttribute('content')
      || '',
    date_end: structuredEvent?.endDate
      || document.querySelector('[itemprop="endDate"]')?.getAttribute('content')
      || '',
    location: clean(location?.name || document.querySelector('[itemprop="location"]')?.textContent),
    address: clean(address),
    organizer: clean(organizer),
    image_url: clean(image || meta('property', 'og:image')),
    source_url: window.location.href,
  };
}

async function initialize() {
  if (!config?.apiUrl || !config?.token) {
    showError('Configuration FeedEvent absente. Télécharge à nouveau l’extension depuis ton profil.');
    return;
  }

  try {
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    if (!tab?.id || !/^https?:\/\//i.test(tab.url || '')) {
      throw new Error('Ouvre une page web d’événement avant d’utiliser le connecteur.');
    }

    const [{ result }] = await chrome.scripting.executeScript({
      target: { tabId: tab.id },
      func: extractEventFromPage,
    });

    field('title').value = result.title || '';
    field('description').value = result.description || '';
    field('date-start').value = toLocalDateTime(result.date_start);
    field('date-end').value = toLocalDateTime(result.date_end);
    field('location').value = result.location || '';
    field('address').value = result.address || '';
    field('organizer').value = result.organizer || '';
    field('image-url').value = result.image_url || '';
    field('source-url').value = result.source_url || tab.url;

    statusElement.hidden = true;
    form.hidden = false;
    field(result.title ? 'date-start' : 'title').focus();
  } catch (error) {
    showError(error.message || 'La page ne peut pas être analysée.');
  }
}

form.addEventListener('submit', async event => {
  event.preventDefault();
  statusElement.hidden = true;
  submitButton.disabled = true;
  submitButton.textContent = 'Envoi en cours…';

  const payload = {
    title: field('title').value.trim(),
    description: field('description').value.trim() || null,
    date_start: new Date(field('date-start').value).toISOString(),
    date_end: field('date-end').value
      ? new Date(field('date-end').value).toISOString()
      : null,
    location: field('location').value.trim() || null,
    address: field('address').value.trim() || null,
    organizer: field('organizer').value.trim() || null,
    image_url: field('image-url').value.trim() || null,
    source_url: field('source-url').value,
  };

  try {
    const response = await fetch(config.apiUrl, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${config.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });
    const data = await response.json();

    if (!response.ok) {
      const validationMessage = Object.values(data.errors || {}).flat()[0];
      throw new Error(validationMessage || data.message || 'Import impossible.');
    }

    form.hidden = true;
    success.hidden = false;
    field('open-app').href = `${config.appUrl}/connector`;
  } catch (error) {
    showError(error.message || 'FeedEvent est inaccessible.');
  } finally {
    submitButton.disabled = false;
    submitButton.textContent = 'Envoyer pour validation';
  }
});

initialize();
