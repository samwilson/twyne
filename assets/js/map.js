import L from 'leaflet';
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
    const geojsonUrl = appBaseUrl + 'map/' +
        map.getBounds().getNorthEast().lat.toFixed(5) +
        '_' + map.getBounds().getNorthEast().lng.toFixed(5) +
        '_' + map.getBounds().getSouthWest().lat.toFixed(5) +
        '_' + map.getBounds().getSouthWest().lng.toFixed(5) +
        '.json';
    const dataRequest = new XMLHttpRequest();
    dataRequest.addEventListener('load', function () {
        const data = JSON.parse(this.responseText);
        const geojsonMarkerOptions = {
            fillOpacity: 1.0,
            stroke: false,
            weight: 0,
            color: '#ff0000',
            radius: 4
        };
        L.geoJSON(data, {
            style: function (feature) {
                if (feature.properties && feature.properties.type && feature.properties.type === 'trackpoint') {
                    return { color: '#0000ff', radius: 1, fillOpacity: 0.4 };
                }
            },
            pointToLayer: function (feature, latlng) {
                return L.circleMarker(latlng, geojsonMarkerOptions);
            },
            onEachFeature: function (feature, layer) {
                if (feature.properties && feature.properties.popupContent) {
                    layer.bindPopup(feature.properties.popupContent);
                }
            }
        }).addTo(map);
    });
    dataRequest.open('GET', geojsonUrl);
    dataRequest.send();
}

// Marker.
let marker = null;
let defaultView = [0, 0];
const mapData = document.getElementById('map').dataset;
if (mapData && mapData.latitude && mapData.longitude) {
    defaultView = new L.LatLng(mapData.latitude, mapData.longitude);
    makeMarker(defaultView);
}
map.setView(defaultView, 12);

// Base map layers.
const configRequest = new XMLHttpRequest();
configRequest.addEventListener('load', function () {
    const config = JSON.parse(this.responseText);
    // Add empty layer-control to the map.
    const layers = L.control.layers().addTo(map);
    // Add the 'Edit' layer (only add to layer-control).
    if (config.edit_config !== undefined) {
        layers.addBaseLayer(
            L.tileLayer(config.edit_url, config.edit_config),
            config.edit_config.label === undefined ? 'edit' : config.edit_config.label
        );
    }
    // Add the 'View' layer (add to layer-control as well as the map).
    // Added last to make it the default layer.
    layers.addBaseLayer(
        L.tileLayer(config.view_url, config.view_config).addTo(map),
        config.view_config.label === undefined ? 'view' : config.view_config.label
    );
});
// eslint-disable-next-line no-undef
configRequest.open('GET', appBaseUrl + 'map-config.json');
configRequest.send();

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

const dateInput = document.querySelector('input[name="date"]');
let estimatedMarker = null;
if (dateInput) {
    dateInput.addEventListener('change', (event) => {
        setEstimatedLocation(event.target.value);
    });
    setEstimatedLocation(dateInput.value);
} else if (!mapData || !mapData.latitude || !mapData.longitude) {
    setEstimatedLocation('');
}

function setEstimatedLocation (date) {
    // eslint-disable-next-line no-undef
    const geojsonUrl = appBaseUrl + 'map/estimates.json?date=' + date;
    const dataRequest = new XMLHttpRequest();
    dataRequest.addEventListener('load', function () {
        const data = JSON.parse(this.responseText);
        const estimatedLatLng = [data.lat, data.lng];
        if (estimatedMarker === null) {
            estimatedMarker = new L.Marker(estimatedLatLng, {
                icon: getIcon('send', [23, 23], [23, 0])
            });
            map.addLayer(estimatedMarker);
        } else {
            estimatedMarker.setLatLng(estimatedLatLng);
        }
        map.setView(estimatedLatLng, 12);
    });
    dataRequest.open('GET', geojsonUrl);
    dataRequest.send();
}

function getIcon (name, size, anchor) {
    return L.icon({
        iconUrl: '/build/images/' + name + '.png',
        iconRetinaUrl: '/build/images/' + name + '-2x.png',
        iconSize: size,
        iconAnchor: anchor
    });
}

function makeMarker (latLng) {
    marker = new L.Marker(latLng, {
        draggable: mapData.edit,
        icon: getIcon('map-pin', [20, 24], [10, 24])
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
