import L from 'leaflet';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

window.activityTravelMap = function activityTravelMap(config) {
    return {
        map: null,
        init() {
            if (!config.points?.length) {
                return;
            }

            this.$nextTick(() => {
                this.mount();
            });
        },
        mount() {
            const el = document.getElementById(config.mapId);
            if (!el || this.map) {
                return;
            }

            this.map = L.map(el, {
                worldCopyJump: true,
                scrollWheelZoom: false,
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(this.map);

            const bounds = [];

            (config.routes || []).forEach((route) => {
                L.polyline(
                    [
                        [route.from.lat, route.from.lng],
                        [route.to.lat, route.to.lng],
                    ],
                    {
                        color: route.color,
                        weight: 3,
                        opacity: 0.85,
                    },
                ).addTo(this.map);
            });

            config.points.forEach((point) => {
                const latLng = [point.lat, point.lng];
                bounds.push(latLng);

                const popup = [
                    `<strong>${escapeHtml(point.name)}</strong>`,
                    point.location ? escapeHtml(point.location) : null,
                    point.start && point.end ? `${escapeHtml(point.start)} → ${escapeHtml(point.end)}` : null,
                    point.url ? `<a href="${escapeHtml(point.url)}">Edit activity</a>` : null,
                ]
                    .filter(Boolean)
                    .join('<br>');

                L.marker(latLng).addTo(this.map).bindPopup(popup);
            });

            if (bounds.length === 1) {
                this.map.setView(bounds[0], 5);
            } else if (bounds.length > 1) {
                this.map.fitBounds(bounds, { padding: [40, 40], maxZoom: 8 });
            }

            // Lazy-loaded widgets often mount before the container has a final size.
            setTimeout(() => this.map?.invalidateSize(), 50);
            setTimeout(() => this.map?.invalidateSize(), 250);
        },
    };
};

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
