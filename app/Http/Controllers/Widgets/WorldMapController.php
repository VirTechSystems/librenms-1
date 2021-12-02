<?php
/**
 * WorldMapController.php
 *
 * -Description-
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2018 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace App\Http\Controllers\Widgets;

use App\Models\Device;
use Illuminate\Http\Request;
use LibreNMS\Config;

class WorldMapController extends WidgetController
{
    protected $title = 'World Map';

    public function __construct()
    {
        $this->defaults = [
            'title' => null,
            'title_url' => Config::get('leaflet.tile_url', '{s}.tile.openstreetmap.org'),
            'init_lat' => Config::get('leaflet.default_lat', 51.4800),
            'init_lng' => Config::get('leaflet.default_lng', 0),
            'init_zoom' => Config::get('leaflet.default_zoom', 2),
            'group_radius' => Config::get('leaflet.group_radius', 80),
            'status' => '0,1',
            'device_group' => null,
            'owm_layer' => Config::get('leaflet.owm_default_layer', 'none'),
        ];
    }

    public function getView(Request $request)
    {
        $settings = $this->getSettings();
        $status = explode(',', $settings['status']);

        $settings['dimensions'] = $request->get('dimensions');
        $settings['critical_circle_color'] = Config::get('leaflet.critical_circle_color', 'red');
        $settings['warning_circle_color'] = Config::get('leaflet.warning_circle_color', 'orange');
        $settings['ok_circle_color'] = Config::get('leaflet.ok_circle_color', 'green');
        $settings['maintenance_circle_color'] = Config::get('leaflet.maintenance_circle_color', 'gray');
        $settings['critical_icon_color'] = Config::get('leaflet.critical_icon_color', 'white');
        $settings['warning_icon_color'] = Config::get('leaflet.warning_icon_color', 'white');
        $settings['ok_icon_color'] = Config::get('leaflet.ok_icon_color', 'white');
        $settings['maintenance_icon_color'] = Config::get('leaflet.maintenance_icon_color', 'white');

        $settings['owm_api'] = Config::get('leaflet.owm_api', null);
        
        $types = array('server', 'appliance', 'firewall', 'network', 'storage', 'wireless', 
            'printer', 'power', 'environment');
        $default_icons = array('server' => 'desktop', 'appliance' => 'desktop', 'firewall' => 'sitemap',
            'network' => 'server', 'storage' => 'database', 'wireless' => 'wifi', 'printer' => 'print',
            'power' => 'plug', 'environment' => 'thermometer');

        $markers = [];
        foreach($types as $type) {
            $marker_critical = array('icon' => 
                Config::get('leaflet.'.$type.'_icon', $default_icons[$type]),
                'color' => $settings['critical_circle_color'],
                'iconColor' => $settings['critical_icon_color'],
                'type' => $type,
                'state' => 'Critical'
            );
            $marker_warning = array('icon' => 
                Config::get('leaflet.'.$type.'_icon', $default_icons[$type]),
                'color' => $settings['warning_circle_color'],
                'iconColor' => $settings['warning_icon_color'],
                'type' => $type,
                'state' => 'Warning'
            );
            $marker_ok = array('icon' => 
                Config::get('leaflet.'.$type.'_icon', $default_icons[$type]),
                'color' => $settings['ok_circle_color'],
                'iconColor' => $settings['ok_icon_color'],
                'type' => $type,
                'state' => 'Ok'
            );
            $marker_maintenance = array('icon' => 
                Config::get('leaflet.'.$type.'_icon', $default_icons[$type]),
                'color' => $settings['maintenance_circle_color'],
                'iconColor' => $settings['maintenance_icon_color'],
                'type' => $type,
                'state' => 'Maintenance'
            );

            array_push($markers, $marker_critical);
            array_push($markers, $marker_warning);
            array_push($markers, $marker_ok);
            array_push($markers, $marker_maintenance);
        }

        $settings['markers'] = $markers;

        $devices = Device::hasAccess($request->user())
            ->with('location')
            ->isActive()
            ->whereIn('status', $status)
            ->when($settings['device_group'], function ($query) use ($settings) {
                $query->inDeviceGroup($settings['device_group']);
            })
            ->get()
            ->filter(function ($device) use ($status) {
                /** @var Device $device */
                if (! ($device->location_id && $device->location && $device->location->coordinatesValid())) {
                    return false;
                }

                // add extra data
                /** @phpstan-ignore-next-line */
                $device->markerIcon = 'serverOkMarker';
                /** @phpstan-ignore-next-line */
                $device->zOffset = 0;
                $uptime_warn = Config::get('uptime_warning', 84600);

                if ($device->isUnderMaintenance()) {
                    $device->markerIcon = $device->type . 'MaintenanceMarker';
                    $device->zOffset = 5000;
                } else if ($device->status == 0) {
                    $device->markerIcon = $device->type . 'CriticalMarker';
                    $device->zOffset = 10000;
                } else {
                    if (($device->uptime < $uptime_warn) && ($device->uptime != 0)) {
                        $device->markerIcon = $device->type . 'WarningMarker';
                        $device->zOffset = 5000;
                    } else {
                        $device->markerIcon = $device->type . 'OkMarker';
                    }
                }

                return true;
            });

        $settings['devices'] = $devices;

        return view('widgets.worldmap', $settings);
    }

    public function getSettingsView(Request $request)
    {
        return view('widgets.settings.worldmap', $this->getSettings(true));
    }
}
