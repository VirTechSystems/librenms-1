<div id="leaflet-map-{{ $id }}" style="width: {{ $dimensions['x'] }}px; height: {{ $dimensions['y'] }}px;"></div>

<style>
    .criticalCluster {
        background-color: {{ $critical_circle_color }};
        text-align: center;
        width: 25px !important;
        height: 25px !important;
        font-size: 14px;
        color: {{ $critical_icon_color }};
        border-color: transparent;
    }
    .warningCluster {
        background-color: {{ $warning_circle_color }};
        text-align: center;
        width: 25px !important;
        height: 25px !important;
        font-size: 14px;
        color: {{ $warning_icon_color }};
        border-color: transparent;
    }
    .okCluster {
        background-color: {{ $ok_circle_color }};
        text-align: center;
        width: 25px !important;
        height: 25px !important;
        font-size: 14px;
        color: {{ $ok_icon_color }};
        border-color: transparent;
    }
    .maintenanceCluster {
        background-color: {{ $maintenance_circle_color }};
        text-align: center;
        width: 25px !important;
        height: 25px !important;
        font-size: 14px;
        color: {{ $maintenance_icon_color }};
        border-color: transparent;
    }
</style>
<script type="application/javascript">
    loadjs('js/leaflet.js', function() {
    loadjs('js/leaflet.markercluster.js', function () {
    loadjs('js/leaflet-openweathermap.js', function() {
    loadjs('js/leaflet.awesome-markers.min.js', function () {
        var map = L.map('leaflet-map-{{ $id }}', { zoomSnap: 0.1 } ).setView(['{{ $init_lat }}', '{{ $init_lng }}'], '{{ sprintf('%01.1f', $init_zoom) }}');
        L.tileLayer('//{{ $title_url }}/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        if ('{{ $owm_layer }}' == 'rain') {
            var layer = L.OWM.rain({showLegend: false, opacity: 0.5, appId: '{{ $owm_api }}'}).addTo(map);
        } else if ('{{ $owm_layer }}' == 'snow') {
            var layer = L.OWM.snow({showLegend: false, opacity: 0.5, appId: '{{ $owm_api }}'}).addTo(map);
        } else if ('{{ $owm_layer }}' == 'temperature') {
            var layer = L.OWM.temperature({showLegend: false, opacity: 0.5, appId: '{{ $owm_api }}'}).addTo(map);
        } else if ('{{ $owm_layer }}' == 'wind') {
            var layer = L.OWM.wind({showLegend: false, opacity: 0.5, appId: '{{ $owm_api }}'}).addTo(map);
        }
        
        @foreach($markers as $marker)
            var {{ $marker["type"] . $marker["state"] }}Marker = L.AwesomeMarkers.icon({
            icon: '{{ $marker["icon"] }}',
            markerColor: '{{ $marker["color"] }}', prefix: 'fa', iconColor: '{{ $marker["iconColor"] }}'
          });
        @endforeach
        
        var markers = L.markerClusterGroup({
            maxClusterRadius: '{{ $group_radius }}',
            iconCreateFunction: function (cluster) {
                var markers = cluster.getAllChildMarkers();
                var zIndex = 20000;
                var color = "okCluster";
                var newClass = " marker-cluster marker-cluster-small leaflet-zoom-animated leaflet-clickable";
                for (var i = 0; i < markers.length; i++) {
                    if (markers[i].options.icon.options.markerColor == "{{ $maintenance_circle_color }}" && color != "{{ $critical_circle_color }}" && color != "{{ $warning_circle_color }}") {
                        color = "maintenanceCluster";
                        zIndex = 5000;
                        break;
                    } else if (markers[i].options.icon.options.markerColor == "{{ $warning_circle_color }}" && color != "{{ $critical_circle_color }}") {
                        if (markers[i].options.icon != printerWarningMarker) {
                            color = "warningCluster";
                            zIndex = 25000;
                        }
                    } else if (markers[i].options.icon.options.markerColor == "{{ $critical_circle_color }}") {
                        if (markers[i].options.icon != printerCriticalMarker) {
                            color = "criticalCluster";
                            zIndex = 30000;
                            break;
                        }
                    }
                }
                return L.divIcon({ html: cluster.getChildCount(), className: color+newClass, iconSize: L.point(40, 40), zIndexOffset: zIndex });
            }
        });

        @foreach($devices as $device)
            @if($status != '0' or !$device->isUnderMaintenance())
                var title = '<a href="@deviceUrl($device)"><img src="{{ $device->icon }}" width="32" height="32" alt=""> {{ $device->displayName() }}</a>';
                var tooltip = '{{ $device->displayName() }}';
                var marker = L.marker(new L.LatLng('{{ $device->location->lat }}', '{{ $device->location->lng }}'), {title: tooltip, icon: {{ $device->markerIcon }}, zIndexOffset: {{ $device->zOffset }}});
                marker.bindPopup(title);
                markers.addLayer(marker);
            @endif
        @endforeach

        map.addLayer(markers);
        map.scrollWheelZoom.disable();
        $(document).ready(function() {
            $("#leaflet-map-{{ $id }}").on("click", function (event) {
                map.scrollWheelZoom.enable();
            }).on("mouseleave", function (event) {
                map.scrollWheelZoom.disable();
            });
        });

    });});});});
</script>
