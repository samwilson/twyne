import L, { LatLng } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import '../css/map.less';

const map = L.map('map', {
    preferCanvas: true
});

// Load points
map.on('moveend', moveMap);
map.on('zoomend', moveMap);
function moveMap () {
    // eslint-disable-next-line no-undef
    const url = appBaseUrl + 'map/' +
        map.getBounds().getNorthEast().lat.toFixed(5) +
        '_' + map.getBounds().getNorthEast().lng.toFixed(5) +
        '_' + map.getBounds().getSouthWest().lat.toFixed(5) +
        '_' + map.getBounds().getSouthWest().lng.toFixed(5) +
        '.json';
    const dataRequest = new XMLHttpRequest();
    dataRequest.addEventListener('load', function () {
        const data = JSON.parse(this.responseText);
        data.forEach(function (e) {
            // eslint-disable-next-line no-undef
            const marker = L.circleMarker(new LatLng(e.lat, e.lng), {
                radius: 2,
                fillOpacity: 1.0,
                stroke: false,
                weight: 0,
                color: '#ff2222'
            });
            marker.addTo(map);
        });
    });
    dataRequest.open('GET', url);
    dataRequest.send();
}

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
    if (!mapData.edit) {
        return;
    }
    if (!marker) {
        makeMarker(clickEvent.latlng);
    }
    moveMarker(clickEvent.latlng);
});

function makeMarker (latLng) {
    marker = new L.Marker(latLng, {
        draggable: mapData.edit,
        icon: L.icon({
            iconUrl: '/build/images/map-pin.png',
            iconRetinaUrl: '/build/images/map-pin-2x.png',
            iconSize: [20, 24],
            iconAnchor: [10, 24]
        })
    });
    map.addLayer(marker);
    marker.on('dragend', dragEvent => {
        moveMarker(dragEvent.target.getLatLng());
    });
}

function moveMarker (latLng) {
    marker.setLatLng(latLng);
    map.panTo(latLng);
    // Round the coordinates https://xkcd.com/2170/
    document.getElementById('latitude').value = latLng.lat.toFixed(5);
    document.getElementById('longitude').value = latLng.lng.toFixed(5);
}
