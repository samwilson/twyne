"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["map"],{

/***/ "./assets/js/map.js":
/*!**************************!*\
  !*** ./assets/js/map.js ***!
  \**************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.array.map.js */ "./node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_number_to_fixed_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.number.to-fixed.js */ "./node_modules/core-js/modules/es.number.to-fixed.js");
/* harmony import */ var core_js_modules_es_number_to_fixed_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_to_fixed_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.array.for-each.js */ "./node_modules/core-js/modules/es.array.for-each.js");
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var leaflet__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! leaflet */ "./node_modules/leaflet/dist/leaflet-src.js");
/* harmony import */ var leaflet__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(leaflet__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var leaflet_dist_leaflet_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! leaflet/dist/leaflet.css */ "./node_modules/leaflet/dist/leaflet.css");
/* harmony import */ var _css_map_less__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../css/map.less */ "./assets/css/map.less");








var map = leaflet__WEBPACK_IMPORTED_MODULE_5___default().map('map', {
  preferCanvas: true
}); // Load points

map.on('moveend', moveMap);
map.on('zoomend', moveMap);

function moveMap() {
  // eslint-disable-next-line no-undef
  var url = appBaseUrl + 'map/' + map.getBounds().getNorthEast().lat.toFixed(5) + '_' + map.getBounds().getNorthEast().lng.toFixed(5) + '_' + map.getBounds().getSouthWest().lat.toFixed(5) + '_' + map.getBounds().getSouthWest().lng.toFixed(5) + '.json';
  var dataRequest = new XMLHttpRequest();
  dataRequest.addEventListener('load', function () {
    var data = JSON.parse(this.responseText);
    data.forEach(function (e) {
      // eslint-disable-next-line no-undef
      var marker = leaflet__WEBPACK_IMPORTED_MODULE_5___default().circleMarker(new leaflet__WEBPACK_IMPORTED_MODULE_5__.LatLng(e.lat, e.lng), {
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
} // Marker.


var marker = null;
var defaultView = [-32.054178, 115.7475];
var mapData = document.getElementById('map').dataset;

if (mapData && mapData.latitude && mapData.longitude) {
  defaultView = new (leaflet__WEBPACK_IMPORTED_MODULE_5___default().LatLng)(mapData.latitude, mapData.longitude);
  makeMarker(defaultView);
}

map.setView(defaultView, 12); // Base map layers.

var configRequest = new XMLHttpRequest();
configRequest.addEventListener('load', function () {
  var config = JSON.parse(this.responseText); // Add empty layer-control to the map.

  var layers = leaflet__WEBPACK_IMPORTED_MODULE_5___default().control.layers().addTo(map); // Add the 'Edit' layer (only add to layer-control).

  if (config.edit_config !== undefined) {
    layers.addBaseLayer(leaflet__WEBPACK_IMPORTED_MODULE_5___default().tileLayer(config.edit_url, config.edit_config), config.edit_config.label === undefined ? 'edit' : config.edit_config.label);
  } // Add the 'View' layer (add to layer-control as well as the map).
  // Added last to make it the default layer.


  layers.addBaseLayer(leaflet__WEBPACK_IMPORTED_MODULE_5___default().tileLayer(config.view_url, config.view_config).addTo(map), config.view_config.label === undefined ? 'view' : config.view_config.label);
}); // eslint-disable-next-line no-undef

configRequest.open('GET', appBaseUrl + 'map-config.json');
configRequest.send();
leaflet__WEBPACK_IMPORTED_MODULE_5___default().geoJSON(geojsonFeature, {
  onEachFeature: onEachFeature
}).addTo(map); // Pointer interaction.

map.on('click', function (clickEvent) {
  if (!mapData.edit) {
    return;
  }

  if (!marker) {
    makeMarker(clickEvent.latlng);
  }

  moveMarker(clickEvent.latlng);
});

function makeMarker(latLng) {
  marker = new (leaflet__WEBPACK_IMPORTED_MODULE_5___default().Marker)(latLng, {
    draggable: mapData.edit,
    icon: leaflet__WEBPACK_IMPORTED_MODULE_5___default().icon({
      iconUrl: '/build/images/map-pin.png',
      iconRetinaUrl: '/build/images/map-pin-2x.png',
      iconSize: [20, 24],
      iconAnchor: [10, 24]
    })
  });
  map.addLayer(marker);
  marker.on('dragend', function (dragEvent) {
    moveMarker(dragEvent.target.getLatLng());
  });
}

function moveMarker(latLng) {
  marker.setLatLng(latLng);
  map.panTo(latLng); // Round the coordinates https://xkcd.com/2170/

  document.getElementById('latitude').value = latLng.lat.toFixed(5);
  document.getElementById('longitude').value = latLng.lng.toFixed(5);
}

/***/ }),

/***/ "./assets/css/map.less":
/*!*****************************!*\
  !*** ./assets/css/map.less ***!
  \*****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["vendors-node_modules_core-js_internals_array-method-has-species-support_js-node_modules_core--1227ea","vendors-node_modules_core-js_modules_es_array_map_js-node_modules_core-js_modules_es_number_t-b7200d"], () => (__webpack_exec__("./assets/js/map.js")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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Q0FBbUIsQ0FBbkIsQ0FBNUM7QUFDQXdCLEVBQUFBLFFBQVEsQ0FBQ0MsY0FBVCxDQUF3QixXQUF4QixFQUFxQ3VDLEtBQXJDLEdBQTZDZCxNQUFNLENBQUNqRCxHQUFQLENBQVdELE9BQVgsQ0FBbUIsQ0FBbkIsQ0FBN0M7QUFDSDs7Ozs7Ozs7Ozs7QUM3R0QiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9hc3NldHMvanMvbWFwLmpzIiwid2VicGFjazovLy8uL2Fzc2V0cy9jc3MvbWFwLmxlc3MiXSwic291cmNlc0NvbnRlbnQiOlsiaW1wb3J0IEwsIHsgTGF0TG5nIH0gZnJvbSAnbGVhZmxldCc7XG5pbXBvcnQgJ2xlYWZsZXQvZGlzdC9sZWFmbGV0LmNzcyc7XG5pbXBvcnQgJy4uL2Nzcy9tYXAubGVzcyc7XG5cbmNvbnN0IG1hcCA9IEwubWFwKCdtYXAnLCB7XG4gICAgcHJlZmVyQ2FudmFzOiB0cnVlXG59KTtcblxuLy8gTG9hZCBwb2ludHNcbm1hcC5vbignbW92ZWVuZCcsIG1vdmVNYXApO1xubWFwLm9uKCd6b29tZW5kJywgbW92ZU1hcCk7XG5mdW5jdGlvbiBtb3ZlTWFwICgpIHtcbiAgICAvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmUgbm8tdW5kZWZcbiAgICBjb25zdCB1cmwgPSBhcHBCYXNlVXJsICsgJ21hcC8nICtcbiAgICAgICAgbWFwLmdldEJvdW5kcygpLmdldE5vcnRoRWFzdCgpLmxhdC50b0ZpeGVkKDUpICtcbiAgICAgICAgJ18nICsgbWFwLmdldEJvdW5kcygpLmdldE5vcnRoRWFzdCgpLmxuZy50b0ZpeGVkKDUpICtcbiAgICAgICAgJ18nICsgbWFwLmdldEJvdW5kcygpLmdldFNvdXRoV2VzdCgpLmxhdC50b0ZpeGVkKDUpICtcbiAgICAgICAgJ18nICsgbWFwLmdldEJvdW5kcygpLmdldFNvdXRoV2VzdCgpLmxuZy50b0ZpeGVkKDUpICtcbiAgICAgICAgJy5qc29uJztcbiAgICBjb25zdCBkYXRhUmVxdWVzdCA9IG5ldyBYTUxIdHRwUmVxdWVzdCgpO1xuICAgIGRhdGFSZXF1ZXN0LmFkZEV2ZW50TGlzdGVuZXIoJ2xvYWQnLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgIGNvbnN0IGRhdGEgPSBKU09OLnBhcnNlKHRoaXMucmVzcG9uc2VUZXh0KTtcbiAgICAgICAgZGF0YS5mb3JFYWNoKGZ1bmN0aW9uIChlKSB7XG4gICAgICAgICAgICAvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmUgbm8tdW5kZWZcbiAgICAgICAgICAgIGNvbnN0IG1hcmtlciA9IEwuY2lyY2xlTWFya2VyKG5ldyBMYXRMbmcoZS5sYXQsIGUubG5nKSwge1xuICAgICAgICAgICAgICAgIHJhZGl1czogMixcbiAgICAgICAgICAgICAgICBmaWxsT3BhY2l0eTogMS4wLFxuICAgICAgICAgICAgICAgIHN0cm9rZTogZmFsc2UsXG4gICAgICAgICAgICAgICAgd2VpZ2h0OiAwLFxuICAgICAgICAgICAgICAgIGNvbG9yOiAnI2ZmMjIyMidcbiAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgbWFya2VyLmFkZFRvKG1hcCk7XG4gICAgICAgIH0pO1xuICAgIH0pO1xuICAgIGRhdGFSZXF1ZXN0Lm9wZW4oJ0dFVCcsIHVybCk7XG4gICAgZGF0YVJlcXVlc3Quc2VuZCgpO1xufVxuXG4vLyBNYXJrZXIuXG5sZXQgbWFya2VyID0gbnVsbDtcbmxldCBkZWZhdWx0VmlldyA9IFstMzIuMDU0MTc4LCAxMTUuNzQ3NV07XG5jb25zdCBtYXBEYXRhID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ21hcCcpLmRhdGFzZXQ7XG5pZiAobWFwRGF0YSAmJiBtYXBEYXRhLmxhdGl0dWRlICYmIG1hcERhdGEubG9uZ2l0dWRlKSB7XG4gICAgZGVmYXVsdFZpZXcgPSBuZXcgTC5MYXRMbmcobWFwRGF0YS5sYXRpdHVkZSwgbWFwRGF0YS5sb25naXR1ZGUpO1xuICAgIG1ha2VNYXJrZXIoZGVmYXVsdFZpZXcpO1xufVxubWFwLnNldFZpZXcoZGVmYXVsdFZpZXcsIDEyKTtcblxuLy8gQmFzZSBtYXAgbGF5ZXJzLlxuY29uc3QgY29uZmlnUmVxdWVzdCA9IG5ldyBYTUxIdHRwUmVxdWVzdCgpO1xuY29uZmlnUmVxdWVzdC5hZGRFdmVudExpc3RlbmVyKCdsb2FkJywgZnVuY3Rpb24gKCkge1xuICAgIGNvbnN0IGNvbmZpZyA9IEpTT04ucGFyc2UodGhpcy5yZXNwb25zZVRleHQpO1xuICAgIC8vIEFkZCBlbXB0eSBsYXllci1jb250cm9sIHRvIHRoZSBtYXAuXG4gICAgY29uc3QgbGF5ZXJzID0gTC5jb250cm9sLmxheWVycygpLmFkZFRvKG1hcCk7XG4gICAgLy8gQWRkIHRoZSAnRWRpdCcgbGF5ZXIgKG9ubHkgYWRkIHRvIGxheWVyLWNvbnRyb2wpLlxuICAgIGlmIChjb25maWcuZWRpdF9jb25maWcgIT09IHVuZGVmaW5lZCkge1xuICAgICAgICBsYXllcnMuYWRkQmFzZUxheWVyKFxuICAgICAgICAgICAgTC50aWxlTGF5ZXIoY29uZmlnLmVkaXRfdXJsLCBjb25maWcuZWRpdF9jb25maWcpLFxuICAgICAgICAgICAgY29uZmlnLmVkaXRfY29uZmlnLmxhYmVsID09PSB1bmRlZmluZWQgPyAnZWRpdCcgOiBjb25maWcuZWRpdF9jb25maWcubGFiZWxcbiAgICAgICAgKTtcbiAgICB9XG4gICAgLy8gQWRkIHRoZSAnVmlldycgbGF5ZXIgKGFkZCB0byBsYXllci1jb250cm9sIGFzIHdlbGwgYXMgdGhlIG1hcCkuXG4gICAgLy8gQWRkZWQgbGFzdCB0byBtYWtlIGl0IHRoZSBkZWZhdWx0IGxheWVyLlxuICAgIGxheWVycy5hZGRCYXNlTGF5ZXIoXG4gICAgICAgIEwudGlsZUxheWVyKGNvbmZpZy52aWV3X3VybCwgY29uZmlnLnZpZXdfY29uZmlnKS5hZGRUbyhtYXApLFxuICAgICAgICBjb25maWcudmlld19jb25maWcubGFiZWwgPT09IHVuZGVmaW5lZCA/ICd2aWV3JyA6IGNvbmZpZy52aWV3X2NvbmZpZy5sYWJlbFxuICAgICk7XG59KTtcbi8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZSBuby11bmRlZlxuY29uZmlnUmVxdWVzdC5vcGVuKCdHRVQnLCBhcHBCYXNlVXJsICsgJ21hcC1jb25maWcuanNvbicpO1xuY29uZmlnUmVxdWVzdC5zZW5kKCk7XG5cbkwuZ2VvSlNPTihnZW9qc29uRmVhdHVyZSwge1xuICAgIG9uRWFjaEZlYXR1cmU6IG9uRWFjaEZlYXR1cmVcbn0pLmFkZFRvKG1hcCk7XG5cbi8vIFBvaW50ZXIgaW50ZXJhY3Rpb24uXG5tYXAub24oJ2NsaWNrJywgY2xpY2tFdmVudCA9PiB7XG4gICAgaWYgKCFtYXBEYXRhLmVkaXQpIHtcbiAgICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICBpZiAoIW1hcmtlcikge1xuICAgICAgICBtYWtlTWFya2VyKGNsaWNrRXZlbnQubGF0bG5nKTtcbiAgICB9XG4gICAgbW92ZU1hcmtlcihjbGlja0V2ZW50LmxhdGxuZyk7XG59KTtcblxuZnVuY3Rpb24gbWFrZU1hcmtlciAobGF0TG5nKSB7XG4gICAgbWFya2VyID0gbmV3IEwuTWFya2VyKGxhdExuZywge1xuICAgICAgICBkcmFnZ2FibGU6IG1hcERhdGEuZWRpdCxcbiAgICAgICAgaWNvbjogTC5pY29uKHtcbiAgICAgICAgICAgIGljb25Vcmw6ICcvYnVpbGQvaW1hZ2VzL21hcC1waW4ucG5nJyxcbiAgICAgICAgICAgIGljb25SZXRpbmFVcmw6ICcvYnVpbGQvaW1hZ2VzL21hcC1waW4tMngucG5nJyxcbiAgICAgICAgICAgIGljb25TaXplOiBbMjAsIDI0XSxcbiAgICAgICAgICAgIGljb25BbmNob3I6IFsxMCwgMjRdXG4gICAgICAgIH0pXG4gICAgfSk7XG4gICAgbWFwLmFkZExheWVyKG1hcmtlcik7XG4gICAgbWFya2VyLm9uKCdkcmFnZW5kJywgZHJhZ0V2ZW50ID0+IHtcbiAgICAgICAgbW92ZU1hcmtlcihkcmFnRXZlbnQudGFyZ2V0LmdldExhdExuZygpKTtcbiAgICB9KTtcbn1cblxuZnVuY3Rpb24gbW92ZU1hcmtlciAobGF0TG5nKSB7XG4gICAgbWFya2VyLnNldExhdExuZyhsYXRMbmcpO1xuICAgIG1hcC5wYW5UbyhsYXRMbmcpO1xuICAgIC8vIFJvdW5kIHRoZSBjb29yZGluYXRlcyBodHRwczovL3hrY2QuY29tLzIxNzAvXG4gICAgZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2xhdGl0dWRlJykudmFsdWUgPSBsYXRMbmcubGF0LnRvRml4ZWQoNSk7XG4gICAgZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2xvbmdpdHVkZScpLnZhbHVlID0gbGF0TG5nLmxuZy50b0ZpeGVkKDUpO1xufVxuIiwiLy8gZXh0cmFjdGVkIGJ5IG1pbmktY3NzLWV4dHJhY3QtcGx1Z2luXG5leHBvcnQge307Il0sIm5hbWVzIjpbIkwiLCJMYXRMbmciLCJtYXAiLCJwcmVmZXJDYW52YXMiLCJvbiIsIm1vdmVNYXAiLCJ1cmwiLCJhcHBCYXNlVXJsIiwiZ2V0Qm91bmRzIiwiZ2V0Tm9ydGhFYXN0IiwibGF0IiwidG9GaXhlZCIsImxuZyIsImdldFNvdXRoV2VzdCIsImRhdGFSZXF1ZXN0IiwiWE1MSHR0cFJlcXVlc3QiLCJhZGRFdmVudExpc3RlbmVyIiwiZGF0YSIsIkpTT04iLCJwYXJzZSIsInJlc3BvbnNlVGV4dCIsImZvckVhY2giLCJlIiwibWFya2VyIiwiY2lyY2xlTWFya2VyIiwicmFkaXVzIiwiZmlsbE9wYWNpdHkiLCJzdHJva2UiLCJ3ZWlnaHQiLCJjb2xvciIsImFkZFRvIiwib3BlbiIsInNlbmQiLCJkZWZhdWx0VmlldyIsIm1hcERhdGEiLCJkb2N1bWVudCIsImdldEVsZW1lbnRCeUlkIiwiZGF0YXNldCIsImxhdGl0dWRlIiwibG9uZ2l0dWRlIiwibWFrZU1hcmtlciIsInNldFZpZXciLCJjb25maWdSZXF1ZXN0IiwiY29uZmlnIiwibGF5ZXJzIiwiY29udHJvbCIsImVkaXRfY29uZmlnIiwidW5kZWZpbmVkIiwiYWRkQmFzZUxheWVyIiwidGlsZUxheWVyIiwiZWRpdF91cmwiLCJsYWJlbCIsInZpZXdfdXJsIiwidmlld19jb25maWciLCJnZW9KU09OIiwiZ2VvanNvbkZlYXR1cmUiLCJvbkVhY2hGZWF0dXJlIiwiY2xpY2tFdmVudCIsImVkaXQiLCJsYXRsbmciLCJtb3ZlTWFya2VyIiwibGF0TG5nIiwiTWFya2VyIiwiZHJhZ2dhYmxlIiwiaWNvbiIsImljb25VcmwiLCJpY29uUmV0aW5hVXJsIiwiaWNvblNpemUiLCJpY29uQW5jaG9yIiwiYWRkTGF5ZXIiLCJkcmFnRXZlbnQiLCJ0YXJnZXQiLCJnZXRMYXRMbmciLCJzZXRMYXRMbmciLCJwYW5UbyIsInZhbHVlIl0sInNvdXJjZVJvb3QiOiIifQ==