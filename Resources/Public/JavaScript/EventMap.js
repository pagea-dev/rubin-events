document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-rubin-event-map]').forEach(function (el) {
        if (typeof L === 'undefined') {
            console.error('RubinEvents EventMap: Leaflet is not loaded');
            return;
        }

        const lat = parseFloat(el.dataset.lat);
        const lon = parseFloat(el.dataset.lon);

        if (Number.isNaN(lat) || Number.isNaN(lon)) {
            console.error('RubinEvents EventMap: missing or invalid coordinates');
            return;
        }

        const zoom = parseInt(el.dataset.zoom, 10) || 14;

        // Scroll wheel stays with the page, the map zooms once it has focus
        const map = L.map(el, { scrollWheelZoom: false }).setView([lat, lon], zoom);
        map.on('focus', function () {
            map.scrollWheelZoom.enable();
        });
        map.on('blur', function () {
            map.scrollWheelZoom.disable();
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        const marker = L.marker([lat, lon]).addTo(map);

        const label = el.dataset.label || '';
        if (label) {
            // textContent so event titles can never inject markup into the popup
            const popup = document.createElement('span');
            popup.textContent = label;
            marker.bindPopup(popup);
        }
    });
});
