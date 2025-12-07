document.addEventListener('DOMContentLoaded', function () {
    const cache = new Map();
    let tooltip = null;

    function createTooltip() {
        tooltip = document.createElement('div');
        tooltip.className = 'scryfall-tooltip';
        Object.assign(tooltip.style, {
            position: 'fixed',
            zIndex: 9999,
            background: 'rgba(18,18,18,0.95)',
            color: '#fff',
            padding: '8px',
            borderRadius: '6px',
            border: '1px solid #d4af37',
            boxShadow: '0 6px 18px rgba(0,0,0,0.6)',
            maxWidth: '320px',
            display: 'none',
            pointerEvents: 'none'
        });
        document.body.appendChild(tooltip);
    }

    function showTooltipAt(x, y, contentNode) {
        if (!tooltip) createTooltip();
        // clear
        tooltip.innerHTML = '';
        tooltip.appendChild(contentNode);
        tooltip.style.display = 'block';
        const pad = 12;
        let left = x + pad;
        let top = y + pad;
        const rect = tooltip.getBoundingClientRect();
        if (left + rect.width > window.innerWidth) {
            left = x - rect.width - pad;
        }
        if (top + rect.height > window.innerHeight) {
            top = y - rect.height - pad;
        }
        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
    }

    function hideTooltip() {
        if (tooltip) {
            tooltip.style.display = 'none';
        }
    }

    async function fetchCard(name) {
        if (!name) return null;
        if (cache.has(name)) return cache.get(name);
        const url = 'https://api.scryfall.com/cards/named?fuzzy=' + encodeURIComponent(name);
        try {
            const res = await fetch(url);
            if (!res.ok) {
                cache.set(name, null);
                return null;
            }
            const data = await res.json();
            // Determine image
            let img = null;
            if (data.image_uris && data.image_uris.normal) img = data.image_uris.normal;
            else if (data.card_faces && data.card_faces[0] && data.card_faces[0].image_uris && data.card_faces[0].image_uris.normal)
                img = data.card_faces[0].image_uris.normal;
            else if (data.image_uris && data.image_uris.large) img = data.image_uris.large;
            const out = { name: data.name || name, set: data.set_name || '', image: img, oracle: data.oracle_text || '', scryfall_uri: data.scryfall_uri ?? null };
            cache.set(name, out);
            return out;
        } catch (e) {
            cache.set(name, null);
            return null;
        }
    }

    function makeLoadingNode(text) {
        const wrap = document.createElement('div');
        wrap.style.minWidth = '160px';
        wrap.style.minHeight = '40px';
        wrap.style.display = 'flex';
        wrap.style.alignItems = 'center';
        wrap.style.justifyContent = 'center';
        wrap.textContent = text || 'Loading...';
        return wrap;
    }

    function makeCardNode(card) {
        const wrap = document.createElement('div');
        if (card.image) {
            const img = document.createElement('img');
            img.src = card.image;
            img.alt = card.name;
            img.style.maxWidth = '280px';
            img.style.display = 'block';
            img.style.marginBottom = '6px';
            wrap.appendChild(img);
        }
        const title = document.createElement('div');
        title.textContent = card.name + (card.set ? (' — ' + card.set) : '');
        title.style.fontWeight = '600';
        title.style.marginBottom = '4px';
        wrap.appendChild(title);
        if (card.oracle) {
            const oracle = document.createElement('div');
            oracle.textContent = card.oracle;
            oracle.style.fontSize = '12px';
            oracle.style.opacity = '0.9';
            wrap.appendChild(oracle);
        }
        return wrap;
    }

    // Attach listeners to the inner text element (.commander-link) so hover/click happen on the text only
    document.querySelectorAll('.commander-link[data-card-name]').forEach(function (el) {
        let currentName = el.getAttribute('data-card-name') || '';
        if (!currentName) return;
        let moveHandler = null;
        let hoverActive = false;
        el.addEventListener('mouseenter', function (ev) {
            hoverActive = true;
            if (!tooltip) createTooltip();
            showTooltipAt(ev.clientX, ev.clientY, makeLoadingNode('Searching Scryfall...'));
            // fetch
            fetchCard(currentName).then(function (card) {
                if (!hoverActive) return;
                if (!card) {
                    showTooltipAt(ev.clientX, ev.clientY, makeLoadingNode('Not found'));
                    return;
                }
                const node = makeCardNode(card);
                showTooltipAt(ev.clientX, ev.clientY, node);
            });
            moveHandler = function (me) {
                if (tooltip && tooltip.style.display === 'block') {
                    showTooltipAt(me.clientX, me.clientY, tooltip.firstChild || makeLoadingNode());
                }
            };
            document.addEventListener('mousemove', moveHandler);
        });
        el.addEventListener('mouseleave', function () {
            hoverActive = false;
            hideTooltip();
            if (moveHandler) {
                document.removeEventListener('mousemove', moveHandler);
                moveHandler = null;
            }
        });
        // On click, open Scryfall page for the card if available, otherwise perform a Scryfall search
        el.addEventListener('click', async function (ev) {
            ev.preventDefault();
            // If the click happened over the tooltip (which is visually above the cell), ignore it
            if (tooltip) {
                const r = tooltip.getBoundingClientRect();
                const cx = ev.clientX, cy = ev.clientY;
                if (cx >= r.left && cx <= r.right && cy >= r.top && cy <= r.bottom) {
                    // Click landed on tooltip overlay area — do nothing
                    return;
                }
            }
            const name = currentName;
            if (!name) return;
            const card = await fetchCard(name);
            if (card && card.scryfall_uri) {
                window.open(card.scryfall_uri, '_blank');
            } else {
                const searchUrl = 'https://scryfall.com/search?q=' + encodeURIComponent('"' + name + '"');
                window.open(searchUrl, '_blank');
            }
        });
    });

    // Also attach click handler to plain links with class commander-name-link to open Scryfall (they are regular anchors already)
    document.querySelectorAll('.commander-name-link').forEach(function (a) {
        a.addEventListener('click', function (ev) {
            // let normal navigation to detail page happen if user holds ctrl/shift or uses right click; but since these links point to internal detail view, we intercept only simple clicks
            if (ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) return;
            ev.preventDefault();
            const name = decodeURIComponent((new URL(a.href, window.location.origin)).searchParams.get('name') || '');
            if (!name) return; // fallback to normal
            // Attempt to open Scryfall directly for the card; if not found, open search
            (async function () {
                const card = await fetchCard(name);
                if (card && card.scryfall_uri) {
                    window.open(card.scryfall_uri, '_blank');
                } else {
                    const searchUrl = 'https://scryfall.com/search?q=' + encodeURIComponent('"' + name + '"');
                    window.open(searchUrl, '_blank');
                }
            })();
        });
    });
});







