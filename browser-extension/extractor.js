globalThis.FeedEventExtractPage = async function () {
  const clean = value => typeof value === 'string'
    ? value.replace(/\s+/g, ' ').trim()
    : '';
  const meta = (attribute, value) => clean(
    document.querySelector(`meta[${attribute}="${value}"]`)?.content,
  );
  const types = value => Array.isArray(value) ? value : [value];
  const isFacebook = /(^|\.)facebook\.com$/i.test(window.location.hostname);
  const genericFacebookTitles = new Set([
    'événements',
    'events',
    'facebook',
  ]);

  function facebookDocumentTitle() {
    const title = clean(document.title)
      .replace(/^\(\d+\)\s*/, '')
      .replace(/\s*[|·-]\s*Facebook\s*$/i, '');

    return genericFacebookTitles.has(title.toLocaleLowerCase('fr')) ? '' : title;
  }

  function visible(element) {
    if (!element) return false;
    const style = getComputedStyle(element);
    const rect = element.getBoundingClientRect();
    return style.display !== 'none'
      && style.visibility !== 'hidden'
      && Number(style.opacity) > 0
      && rect.width > 0
      && rect.height > 0;
  }

  function facebookTitle(root) {
    const excluded = new Set([
      ...genericFacebookTitles,
      'événements',
      'à propos',
      'discussion',
      'détails',
      'merci pour votre réponse',
    ]);

    const candidates = [...root.querySelectorAll('h1, h2, [role="heading"], span, div')]
      .filter(element => visible(element))
      .map(element => {
        const text = clean(element.textContent);
        const style = getComputedStyle(element);
        const rect = element.getBoundingClientRect();
        const size = Number.parseFloat(style.fontSize) || 0;
        const weight = Number.parseInt(style.fontWeight, 10) || (style.fontWeight === 'bold' ? 700 : 400);
        const isLeaf = element.children.length === 0;

        return {
          text,
          size,
          weight,
          rect,
          score: (size * 10) + (weight / 10) + (isLeaf ? 20 : 0) - (rect.top > 900 ? 80 : 0),
        };
      })
      .filter(candidate =>
        candidate.text.length >= 8
        && candidate.text.length <= 180
        && candidate.size >= 20
        && candidate.weight >= 600
        && !excluded.has(candidate.text.toLocaleLowerCase('fr')),
      )
      .sort((a, b) => b.score - a.score);

    return facebookDocumentTitle() || candidates[0]?.text || '';
  }

  function frenchDate(text) {
    const months = {
      janvier: 0,
      février: 1,
      fevrier: 1,
      mars: 2,
      avril: 3,
      mai: 4,
      juin: 5,
      juillet: 6,
      août: 7,
      aout: 7,
      septembre: 8,
      octobre: 9,
      novembre: 10,
      décembre: 11,
      decembre: 11,
    };
    const match = text.match(
      /(?:lundi|mardi|mercredi|jeudi|vendredi|samedi|dimanche)?\s*(\d{1,2})\s+(janvier|f[ée]vrier|mars|avril|mai|juin|juillet|ao[uû]t|septembre|octobre|novembre|d[ée]cembre)\s+(\d{4})(?:\s+(?:à|a|de)\s+(\d{1,2})(?::|h)(\d{2})?)?/i,
    );

    if (!match) return '';

    const month = months[match[2].toLocaleLowerCase('fr').normalize('NFC')];
    if (month === undefined) return '';

    const date = new Date(
      Number(match[3]),
      month,
      Number(match[1]),
      Number(match[4] || 0),
      Number(match[5] || 0),
    );

    return Number.isNaN(date.getTime()) ? '' : date.toISOString();
  }

  function facebookDetails(root, title) {
    const lines = root.innerText
      .split('\n')
      .map(clean)
      .filter(Boolean);
    const titleIndex = lines.findIndex(line => line === title);
    const organizerLineIndex = lines.findIndex(line => /^Év[èé]nement de\s+/i.test(line));
    const publicLineIndex = lines.findIndex(line =>
      /^(Public|Privé)(?:\s*[·•-].*)?$/i.test(line),
    );

    let location = '';
    if (titleIndex >= 0) {
      const next = lines.slice(titleIndex + 1, titleIndex + 4).find(line =>
        line.length <= 180
        && !/^(À propos|Discussion|Détails|Samedi|Dimanche|Lundi|Mardi|Mercredi|Jeudi|Vendredi)/i.test(line),
      );
      location = next || '';
    }

    if (!location && organizerLineIndex >= 0) {
      const next = lines.slice(organizerLineIndex + 1, organizerLineIndex + 4)
        .find(line => !/^(Public|Privé)\s*[·•-]/i.test(line));
      location = next || '';
    }

    let description = '';
    if (publicLineIndex >= 0) {
      description = lines
        .slice(publicLineIndex + 1, publicLineIndex + 7)
        .filter(line =>
          !/^(?:[·•-]\s*)?(Tout le monde|Merci pour|Lancer|Inviter|Ajouter|Toulon$)/i.test(line),
        )
        .join('\n')
        .replace(/\s*En voir plus.*$/i, '')
        .trim();
    }

    return {
      location,
      organizer: organizerLineIndex >= 0
        ? lines[organizerLineIndex].replace(/^Év[èé]nement de\s+/i, '')
        : '',
      description,
      date_start: frenchDate(
        titleIndex >= 0
          ? lines.slice(Math.max(0, titleIndex - 4), titleIndex + 3).join(' ')
          : '',
      ) || frenchDate(lines.join(' ')),
    };
  }

  function facebookCoordinates() {
    const mapElements = document.querySelectorAll(
      '[style*="static_map.php"], [style*="marker_list"]',
    );

    for (const element of mapElements) {
      const source = element.style.backgroundImage || element.getAttribute('style') || '';
      const urlMatch = source.match(/url\(["']?([^"')]+)["']?\)/i);
      if (!urlMatch) continue;

      try {
        const mapUrl = new URL(urlMatch[1].replaceAll('&amp;', '&'));
        const marker = mapUrl.searchParams.get('marker_list[0]');
        const [latitude, longitude] = (marker || '').split(',').map(Number);

        if (
          Number.isFinite(latitude)
          && latitude >= -90
          && latitude <= 90
          && Number.isFinite(longitude)
          && longitude >= -180
          && longitude <= 180
        ) {
          return { latitude, longitude };
        }
      } catch {
        // Ignore malformed map URLs and keep looking.
      }
    }

    return {};
  }

  function facebookImage() {
    return clean(
      document.querySelector('img[data-imgperflogname="profileCoverPhoto"]')?.currentSrc
      || document.querySelector('img[data-imgperflogname="profileCoverPhoto"]')?.src,
    );
  }

  function sourceUrl() {
    if (!isFacebook) return window.location.href;

    const eventId = window.location.pathname.match(/\/events\/(\d+)/)?.[1];
    return eventId
      ? `${window.location.origin}/events/${eventId}/`
      : `${window.location.origin}${window.location.pathname}`;
  }

  if (isFacebook) {
    for (let attempt = 0; attempt < 20; attempt += 1) {
      const title = facebookDocumentTitle();
      const bodyText = clean(document.body?.innerText);
      const hasRenderedTitle = title && bodyText.includes(title);
      const hasRenderedDate = frenchDate(bodyText);

      if (hasRenderedTitle && hasRenderedDate) break;
      await new Promise(resolve => setTimeout(resolve, 250));
    }
  }

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

  const root = document.querySelector('[role="main"], main') || document.body;
  const title = isFacebook
    ? facebookTitle(root)
    : clean(
        structuredEvent?.name
        || meta('property', 'og:title')
        || document.querySelector('h1')?.textContent
        || document.title,
      ).replace(/\s*[|·-]\s*Facebook\s*$/i, '');
  const facebook = isFacebook ? facebookDetails(root, title) : {};
  const location = structuredEvent?.location;
  const structuredGeo = location?.geo || {};
  const facebookGeo = isFacebook ? facebookCoordinates() : {};
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
    title,
    description: clean(
      structuredEvent?.description
      || facebook.description
      || meta('property', 'og:description')
      || meta('name', 'description'),
    ),
    date_start: structuredEvent?.startDate
      || document.querySelector('time[datetime]')?.dateTime
      || document.querySelector('[itemprop="startDate"]')?.getAttribute('content')
      || facebook.date_start
      || '',
    date_end: structuredEvent?.endDate
      || document.querySelector('[itemprop="endDate"]')?.getAttribute('content')
      || '',
    location: clean(
      location?.name
      || facebook.location
      || document.querySelector('[itemprop="location"]')?.textContent,
    ),
    address: clean(address),
    organizer: clean(organizer || facebook.organizer),
    latitude: structuredGeo.latitude ?? facebookGeo.latitude ?? null,
    longitude: structuredGeo.longitude ?? facebookGeo.longitude ?? null,
    image_url: clean(
      image
      || (isFacebook ? facebookImage() : '')
      || meta('property', 'og:image'),
    ),
    source_url: sourceUrl(),
  };
};
