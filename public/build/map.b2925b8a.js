"use strict";(self.webpackChunk=self.webpackChunk||[]).push([[842],{28:(e,t,n)=>{n(1249),n(6977),n(9554),n(1539),n(4747);var a=n(5243),i=n.n(a),o=i().map("map",{preferCanvas:!0});function d(){var e=appBaseUrl+"map/"+o.getBounds().getNorthEast().lat.toFixed(5)+"_"+o.getBounds().getNorthEast().lng.toFixed(5)+"_"+o.getBounds().getSouthWest().lat.toFixed(5)+"_"+o.getBounds().getSouthWest().lng.toFixed(5)+".json",t=new XMLHttpRequest;t.addEventListener("load",(function(){JSON.parse(this.responseText).forEach((function(e){i().circleMarker(new a.LatLng(e.lat,e.lng),{radius:2,fillOpacity:1,stroke:!1,weight:0,color:"#ff2222"}).addTo(o)}))})),t.open("GET",e),t.send()}o.on("moveend",d),o.on("zoomend",d);var l=null,r=[-32.054178,115.7475],s=document.getElementById("map").dataset;s&&s.latitude&&s.longitude&&u(r=new(i().LatLng)(s.latitude,s.longitude)),o.setView(r,12);var g=new XMLHttpRequest;function u(e){l=new(i().Marker)(e,{draggable:s.edit,icon:i().icon({iconUrl:"/build/images/map-pin.png",iconRetinaUrl:"/build/images/map-pin-2x.png",iconSize:[20,24],iconAnchor:[10,24]})}),o.addLayer(l),l.on("dragend",(function(e){c(e.target.getLatLng())}))}function c(e){l.setLatLng(e),o.panTo(e),document.getElementById("latitude").value=e.lat.toFixed(5),document.getElementById("longitude").value=e.lng.toFixed(5)}g.addEventListener("load",(function(){var e=JSON.parse(this.responseText),t=i().control.layers().addTo(o);void 0!==e.edit_config&&t.addBaseLayer(i().tileLayer(e.edit_url,e.edit_config),void 0===e.edit_config.label?"edit":e.edit_config.label),t.addBaseLayer(i().tileLayer(e.view_url,e.view_config).addTo(o),void 0===e.view_config.label?"view":e.view_config.label)})),g.open("GET",appBaseUrl+"map-config.json"),g.send(),o.on("click",(function(e){s.edit&&(l||u(e.latlng),c(e.latlng))}))}},e=>{e.O(0,[315,805],(()=>{return t=28,e(e.s=t);var t}));e.O()}]);