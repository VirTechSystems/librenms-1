<?php

require 'includes/html/graphs/common.inc.php';

$scale_min = 0;
$nototal = 1;
$unit_text = 'Memory';
$unitlen = 15;
$bigdescrlen = 20;
$smalldescrlen = 15;
$colours = 'mixed';

$rrd_filename = rrd_name($device['hostname'], ['app', 'redis', $app['app_id'], 'memory']);

$array = [
    'active' => 'Active',
    'allocated'   => 'Allocated',
    'resident'   => 'Resident',
    'frag_bytes'   => 'Frag Bytes',
    'rss_bytes'   => 'RSS Bytes',
];

$rrd_list = [];
if (rrdtool_check_rrd_exists($rrd_filename)) {
    $i = 0;
    foreach ($array as $ds => $descr) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr'] = $descr;
        $rrd_list[$i]['ds'] = $ds;
        $i++;
    }
} else {
    echo "file missing: $rrd_filename";
}

require 'includes/html/graphs/generic_multi_line_exact_numbers.inc.php';