document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-map-picker]').forEach(function (el) {
        const mapId         = el.dataset.mapId;
        const locationInput = document.getElementById(mapId + '-map-location');
        const coordsEl      = document.getElementById(mapId + '-coords');
        const clearBtn      = document.getElementById(mapId + '-clear');
        const mapEl         = document.getElementById(mapId);

        if (!locationInput || !coordsEl || !mapEl) {
            console.error('RubinEvents MapPicker: missing elements for map', mapId);
            return;
        }

        const centerLat = parseFloat(el.dataset.centerLat);
        const centerLon = parseFloat(el.dataset.centerLon);
        const zoom      = parseInt(el.dataset.zoom);
        const hasMarker = el.dataset.hasMarker === 'true';
        const markerLat = parseFloat(el.dataset.markerLat);
        const markerLon = parseFloat(el.dataset.markerLon);

        const map = L.map(mapId).setView([centerLat, centerLon], zoom);
        let marker = null;

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        if (hasMarker) {
            marker = L.marker([markerLat, markerLon]).addTo(map);
        }

        map.on('click', function (e) {
            const lat = e.latlng.lat.toFixed(6);
            const lon = e.latlng.lng.toFixed(6);

            // store as "lat,lon" in map_location field
            locationInput.value = lat + ',' + lon;
            coordsEl.textContent = 'Lat: ' + lat + ', Lon: ' + lon;

            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng).addTo(map);
            }
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                locationInput.value = '';
                coordsEl.textContent = el.dataset.emptyText;

                if (marker) {
                    map.removeLayer(marker);
                    marker = null;
                }
            });
        }
    });
});