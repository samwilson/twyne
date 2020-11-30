import L from 'leaflet';

import 'leaflet/dist/leaflet.css';
import '../css/map.less';

/* This code is needed to properly load the images in the Leaflet CSS */
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: require('leaflet/dist/images/marker-icon-2x.png').default,
    iconUrl: require('leaflet/dist/images/marker-icon.png').default,
    shadowUrl: require('leaflet/dist/images/marker-shadow.png').default
});

const map = L.map('map');

// Marker.
let marker = null;
let defaultView = [-32.054178, 115.7475];
const mapData = document.getElementById('map').dataset;
if (mapData && mapData.latitude && mapData.longitude) {
    defaultView = new L.LatLng(mapData.latitude, mapData.longitude);
    makeMarker(defaultView);
}
map.setView(defaultView, 12);

// Base map.
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap contributors</a>'
}).addTo(map);

// Pointer interaction.
map.on('click', clickEvent => {
    if (!marker) {
        makeMarker(clickEvent.latlng);
    }
    moveMarker(clickEvent.latlng);
});
function makeMarker (latLng) {
    marker = new L.Marker(latLng, { draggable: true });
    map.addLayer(marker);
    marker.on('dragend', dragEvent => {
        moveMarker(dragEvent.target.getLatLng());
    });
}
function moveMarker (latLng) {
    marker.setLatLng(latLng, { draggable: true });
    map.panTo(latLng);
    // Round the coordinates https://xkcd.com/2170/
    document.getElementById('latitude').value = latLng.lat.toFixed(5);
    document.getElementById('longitude').value = latLng.lng.toFixed(5);
}
