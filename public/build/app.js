(self["webpackChunk"] = self["webpackChunk"] || []).push([["app"],{

/***/ "./assets/js/app.js":
/*!**************************!*\
  !*** ./assets/js/app.js ***!
  \**************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _css_app_less__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../css/app.less */ "./assets/css/app.less");
/* harmony import */ var _timezone_converter__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./timezone-converter */ "./assets/js/timezone-converter.js");
/* harmony import */ var _timezone_converter__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_timezone_converter__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _tags__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./tags */ "./assets/js/tags.js");




/***/ }),

/***/ "./assets/js/tags.js":
/*!***************************!*\
  !*** ./assets/js/tags.js ***!
  \***************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.array.concat.js */ "./node_modules/core-js/modules/es.array.concat.js");
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_3__);





__webpack_require__(/*! select2/dist/js/select2.min */ "./node_modules/select2/dist/js/select2.min.js");

__webpack_require__(/*! select2/dist/css/select2.min.css */ "./node_modules/select2/dist/css/select2.min.css");

var wikidataResultTemplate = function wikidataResultTemplate(result) {
  console.log(result);

  if (result.loading) {
    return result.text;
  }

  return jquery__WEBPACK_IMPORTED_MODULE_3___default()("\n        <a href=\"https://www.wikidata.org/wiki/".concat(result.id, "\" target=\"_blank\">").concat(result.id, "</a>:\n        <strong>").concat(result.text, "</strong> &mdash;\n        <dfn>").concat(result.description, "</dfn>\n    "));
};

jquery__WEBPACK_IMPORTED_MODULE_3___default()('select#tags').select2({
  multiple: true,
  tags: true,
  ajax: {
    url: '/tags.json',
    dataType: 'json',
    delay: 250,
    data: function data(params) {
      return {
        q: params.term,
        page: params.page || 1
      };
    }
  },
  minimumInputLength: 1
});
jquery__WEBPACK_IMPORTED_MODULE_3___default()('select#depicts').select2({
  multiple: true,
  ajax: {
    url: '/wikidata.json',
    dataType: 'json',
    delay: 250
  },
  templateResult: wikidataResultTemplate,
  minimumInputLength: 1
});
jquery__WEBPACK_IMPORTED_MODULE_3___default()('select#wikidata').select2({
  ajax: {
    url: '/wikidata.json',
    dataType: 'json',
    delay: 250
  },
  templateResult: wikidataResultTemplate,
  minimumInputLength: 1
});

/***/ }),

/***/ "./assets/js/timezone-converter.js":
/*!*****************************************!*\
  !*** ./assets/js/timezone-converter.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

__webpack_require__(/*! core-js/modules/es.array.for-each.js */ "./node_modules/core-js/modules/es.array.for-each.js");

__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");

__webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");

__webpack_require__(/*! core-js/modules/es.date.to-string.js */ "./node_modules/core-js/modules/es.date.to-string.js");

(function () {
  document.body.querySelectorAll('time').forEach(function (timeEl) {
    // Show the UTC time as the tooltip.
    timeEl.title = 'UTC time: ' + timeEl.innerText; // Convert to local browser time for actual display.

    var date = new Date(Date.parse(timeEl.dateTime));
    var options = {
      timeZoneName: 'short',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      weekday: 'long',
      hour: 'numeric',
      minute: 'numeric'
    };
    timeEl.innerText = date.toLocaleString([], options) + '.';
  });
})();

/***/ }),

/***/ "./assets/css/app.less":
/*!*****************************!*\
  !*** ./assets/css/app.less ***!
  \*****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["vendors-node_modules_core-js_internals_array-method-has-species-support_js-node_modules_core--1227ea","vendors-node_modules_core-js_modules_es_array_concat_js-node_modules_core-js_modules_es_date_-eb7d60"], () => (__webpack_exec__("./assets/js/app.js")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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LE1BQU0sRUFBRTtBQVBJLEtBQWhCO0FBU0FkLElBQUFBLE1BQU0sQ0FBQ0UsU0FBUCxHQUFtQkMsSUFBSSxDQUFDWSxjQUFMLENBQW9CLEVBQXBCLEVBQXdCUixPQUF4QixJQUFtQyxHQUF0RDtBQUNILEdBZkQ7QUFnQkgsQ0FqQkEsR0FBRDs7Ozs7Ozs7Ozs7O0FDQUEiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9hc3NldHMvanMvYXBwLmpzIiwid2VicGFjazovLy8uL2Fzc2V0cy9qcy90YWdzLmpzIiwid2VicGFjazovLy8uL2Fzc2V0cy9qcy90aW1lem9uZS1jb252ZXJ0ZXIuanMiLCJ3ZWJwYWNrOi8vLy4vYXNzZXRzL2Nzcy9hcHAubGVzcz9kNDU5Il0sInNvdXJjZXNDb250ZW50IjpbImltcG9ydCAnLi4vY3NzL2FwcC5sZXNzJztcbmltcG9ydCAnLi90aW1lem9uZS1jb252ZXJ0ZXInO1xuaW1wb3J0ICcuL3RhZ3MnO1xuIiwiaW1wb3J0ICQgZnJvbSAnanF1ZXJ5JztcbnJlcXVpcmUoJ3NlbGVjdDIvZGlzdC9qcy9zZWxlY3QyLm1pbicpO1xucmVxdWlyZSgnc2VsZWN0Mi9kaXN0L2Nzcy9zZWxlY3QyLm1pbi5jc3MnKTtcblxuY29uc3Qgd2lraWRhdGFSZXN1bHRUZW1wbGF0ZSA9IGZ1bmN0aW9uIChyZXN1bHQpIHtcbiAgICBjb25zb2xlLmxvZyhyZXN1bHQpO1xuICAgIGlmIChyZXN1bHQubG9hZGluZykge1xuICAgICAgICByZXR1cm4gcmVzdWx0LnRleHQ7XG4gICAgfVxuICAgIHJldHVybiAkKGBcbiAgICAgICAgPGEgaHJlZj1cImh0dHBzOi8vd3d3Lndpa2lkYXRhLm9yZy93aWtpLyR7cmVzdWx0LmlkfVwiIHRhcmdldD1cIl9ibGFua1wiPiR7cmVzdWx0LmlkfTwvYT46XG4gICAgICAgIDxzdHJvbmc+JHtyZXN1bHQudGV4dH08L3N0cm9uZz4gJm1kYXNoO1xuICAgICAgICA8ZGZuPiR7cmVzdWx0LmRlc2NyaXB0aW9ufTwvZGZuPlxuICAgIGApO1xufTtcblxuJCgnc2VsZWN0I3RhZ3MnKS5zZWxlY3QyKHtcbiAgICBtdWx0aXBsZTogdHJ1ZSxcbiAgICB0YWdzOiB0cnVlLFxuICAgIGFqYXg6IHtcbiAgICAgICAgdXJsOiAnL3RhZ3MuanNvbicsXG4gICAgICAgIGRhdGFUeXBlOiAnanNvbicsXG4gICAgICAgIGRlbGF5OiAyNTAsXG4gICAgICAgIGRhdGE6IGZ1bmN0aW9uIChwYXJhbXMpIHtcbiAgICAgICAgICAgIHJldHVybiB7IHE6IHBhcmFtcy50ZXJtLCBwYWdlOiBwYXJhbXMucGFnZSB8fCAxIH07XG4gICAgICAgIH1cbiAgICB9LFxuICAgIG1pbmltdW1JbnB1dExlbmd0aDogMVxufSk7XG5cbiQoJ3NlbGVjdCNkZXBpY3RzJykuc2VsZWN0Mih7XG4gICAgbXVsdGlwbGU6IHRydWUsXG4gICAgYWpheDoge1xuICAgICAgICB1cmw6ICcvd2lraWRhdGEuanNvbicsXG4gICAgICAgIGRhdGFUeXBlOiAnanNvbicsXG4gICAgICAgIGRlbGF5OiAyNTBcbiAgICB9LFxuICAgIHRlbXBsYXRlUmVzdWx0OiB3aWtpZGF0YVJlc3VsdFRlbXBsYXRlLFxuICAgIG1pbmltdW1JbnB1dExlbmd0aDogMVxufSk7XG5cbiQoJ3NlbGVjdCN3aWtpZGF0YScpLnNlbGVjdDIoe1xuICAgIGFqYXg6IHtcbiAgICAgICAgdXJsOiAnL3dpa2lkYXRhLmpzb24nLFxuICAgICAgICBkYXRhVHlwZTogJ2pzb24nLFxuICAgICAgICBkZWxheTogMjUwXG4gICAgfSxcbiAgICB0ZW1wbGF0ZVJlc3VsdDogd2lraWRhdGFSZXN1bHRUZW1wbGF0ZSxcbiAgICBtaW5pbXVtSW5wdXRMZW5ndGg6IDFcbn0pO1xuIiwiKGZ1bmN0aW9uICgpIHtcbiAgICBkb2N1bWVudC5ib2R5LnF1ZXJ5U2VsZWN0b3JBbGwoJ3RpbWUnKS5mb3JFYWNoKGZ1bmN0aW9uICh0aW1lRWwpIHtcbiAgICAgICAgLy8gU2hvdyB0aGUgVVRDIHRpbWUgYXMgdGhlIHRvb2x0aXAuXG4gICAgICAgIHRpbWVFbC50aXRsZSA9ICdVVEMgdGltZTogJyArIHRpbWVFbC5pbm5lclRleHQ7XG4gICAgICAgIC8vIENvbnZlcnQgdG8gbG9jYWwgYnJvd3NlciB0aW1lIGZvciBhY3R1YWwgZGlzcGxheS5cbiAgICAgICAgY29uc3QgZGF0ZSA9IG5ldyBEYXRlKERhdGUucGFyc2UodGltZUVsLmRhdGVUaW1lKSk7XG4gICAgICAgIGNvbnN0IG9wdGlvbnMgPSB7XG4gICAgICAgICAgICB0aW1lWm9uZU5hbWU6ICdzaG9ydCcsXG4gICAgICAgICAgICB5ZWFyOiAnbnVtZXJpYycsXG4gICAgICAgICAgICBtb250aDogJ2xvbmcnLFxuICAgICAgICAgICAgZGF5OiAnbnVtZXJpYycsXG4gICAgICAgICAgICB3ZWVrZGF5OiAnbG9uZycsXG4gICAgICAgICAgICBob3VyOiAnbnVtZXJpYycsXG4gICAgICAgICAgICBtaW51dGU6ICdudW1lcmljJ1xuICAgICAgICB9O1xuICAgICAgICB0aW1lRWwuaW5uZXJUZXh0ID0gZGF0ZS50b0xvY2FsZVN0cmluZyhbXSwgb3B0aW9ucykgKyAnLic7XG4gICAgfSk7XG59KCkpO1xuIiwiLy8gZXh0cmFjdGVkIGJ5IG1pbmktY3NzLWV4dHJhY3QtcGx1Z2luXG5leHBvcnQge307Il0sIm5hbWVzIjpbIiQiLCJyZXF1aXJlIiwid2lraWRhdGFSZXN1bHRUZW1wbGF0ZSIsInJlc3VsdCIsImNvbnNvbGUiLCJsb2ciLCJsb2FkaW5nIiwidGV4dCIsImlkIiwiZGVzY3JpcHRpb24iLCJzZWxlY3QyIiwibXVsdGlwbGUiLCJ0YWdzIiwiYWpheCIsInVybCIsImRhdGFUeXBlIiwiZGVsYXkiLCJkYXRhIiwicGFyYW1zIiwicSIsInRlcm0iLCJwYWdlIiwibWluaW11bUlucHV0TGVuZ3RoIiwidGVtcGxhdGVSZXN1bHQiLCJkb2N1bWVudCIsImJvZHkiLCJxdWVyeVNlbGVjdG9yQWxsIiwiZm9yRWFjaCIsInRpbWVFbCIsInRpdGxlIiwiaW5uZXJUZXh0IiwiZGF0ZSIsIkRhdGUiLCJwYXJzZSIsImRhdGVUaW1lIiwib3B0aW9ucyIsInRpbWVab25lTmFtZSIsInllYXIiLCJtb250aCIsImRheSIsIndlZWtkYXkiLCJob3VyIiwibWludXRlIiwidG9Mb2NhbGVTdHJpbmciXSwic291cmNlUm9vdCI6IiJ9