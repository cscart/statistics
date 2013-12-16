<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return;
}

Registry::set('navigation.dynamic.sections.banners', array(
    'title' => __('banners'),
    'href' => "statistics.banners",
    'ajax' => true
));

if ($mode == 'banners') {

    list($stat, $params) = fn_get_banners_statistics($_REQUEST);

    Registry::get('view')->assign('banners_statistics', $stat['log']);
    Registry::get('view')->assign('banners', $stat['banners']);
    Registry::get('view')->assign('search', $params);
    Registry::set('navigation.dynamic.active_section', 'banners');

} elseif ($mode == 'banner_stats') {

    list($stat, $period) = fn_get_banners_detailed_stats($_REQUEST);

    Registry::get('view')->assign('stat', $stat);
    Registry::get('view')->assign('period', $period);

} if ($mode == 'delete') {
    db_query("TRUNCATE TABLE ?:stat_banners_log");
}

function fn_get_banners_detailed_stats($params)
{
    $condition = '';

    $params['page'] = empty($params['page']) ? 1 : $params['page'];

    list($params['time_from'], $params['time_to']) = fn_create_periods($params);

    $constraints = db_get_row("SELECT MIN(timestamp) as min, MAX(timestamp) as max FROM ?:stat_banners_log WHERE banner_id = ?i", $params['banner_id']);

    if (!empty($params['time_from'])) {
        $condition .= db_quote(" AND timestamp >= ?i", $params['time_from']);
    } else {
        $params['time_from'] = $constraints['min'];
    }

    if (!empty($params['time_to'])) {
        $condition .= db_quote(" AND timestamp <= ?i", $params['time_to']);
    } else {
        $params['time_to'] = $constraints['max'];
    }

    if ($params['time_to'] - $params['time_from'] > 60 * 60 * 24 * 365) { // split by year
        $field = "YEAR(FROM_UNIXTIME(timestamp))";
        $period = 'year';
    } elseif ($params['time_to'] - $params['time_from'] > 60 * 60 * 24 * 30) { // split by month
        $field = "CONCAT(YEAR(FROM_UNIXTIME(timestamp)),'/',MONTH(FROM_UNIXTIME(timestamp)))";
        $period = 'month';
    } elseif ($params['time_to'] - $params['time_from'] > 60 * 60 * 24) { // split by day
        $field = "CONCAT(YEAR(FROM_UNIXTIME(timestamp)),'/',MONTH(FROM_UNIXTIME(timestamp)),'/',DAY(FROM_UNIXTIME(timestamp)))";
        $period = 'day';
    } else { // split per hour
        $field = "CONCAT(YEAR(FROM_UNIXTIME(timestamp)),'/',MONTH(FROM_UNIXTIME(timestamp)),'/',DAY(FROM_UNIXTIME(timestamp)),' ',HOUR(FROM_UNIXTIME(timestamp)),':00')";
        $period = 'hour';
    }

    $log = db_get_hash_multi_array("SELECT type, COUNT(type) as number, banner_id, unix_timestamp($field) as date FROM ?:stat_banners_log WHERE banner_id = ?i ?p GROUP BY type, date ORDER BY date DESC", array('date', 'type'), $params['banner_id'], $condition);

    foreach ($log as $k => $v) {
        if (!empty($v['C']['number']) && !empty($v['V']['number'])) {
            $log[$k]['conversion'] = sprintf('%.2f', $v['C']['number'] / $v['V']['number'] * 100);
            if (floatval($log[$k]['conversion']) == intval($log[$k]['conversion'])) {
                $log[$k]['conversion'] = intval($log[$k]['conversion']);
            }
        }
    }

    return array ($log, $period);
}

function fn_get_banners_statistics($params)
{
    $condition = '';
    if (!empty($params['period']) && $params['period'] != 'A') {
        list($params['time_from'], $params['time_to']) = fn_create_periods($params);
    } else {
        $params['period'] = 'A';
        $params['time_from'] = '';
        $params['time_to'] = '';
    }

    if (!empty($params['time_from'])) {
        $condition .= db_quote(" AND timestamp >= ?i", $params['time_from']);
    }

    if (!empty($params['time_to'])) {
        $condition .= db_quote(" AND timestamp <= ?i", $params['time_to']);
    }

    $log = db_get_hash_multi_array("SELECT type, COUNT(type) as number, banner_id FROM ?:stat_banners_log WHERE 1 ?p GROUP BY banner_id, type ORDER BY timestamp DESC", array('banner_id', 'type'), $condition);

    foreach ($log as $b_id => $v) {
        if (!empty($v['C']['number']) && !empty($v['V']['number'])) {
            $log[$b_id]['conversion'] = sprintf('%.2f', $v['C']['number'] / $v['V']['number'] * 100);
            if (floatval($log[$b_id]['conversion']) == intval($log[$b_id]['conversion'])) {
                $log[$b_id]['conversion'] = intval($log[$b_id]['conversion']);
            }
        }
    }

    $banner_ids = array_keys($log);

    if (!empty($banner_ids)) {
        $_params = array(
            'item_ids' => implode(',', $banner_ids)
        );
        list($banners) = fn_get_banners($_params);
    } else {
        $banners = array();
    }

    $data = array(
        'log' => $log,
        'banners' => $banners
    );

    return array ($data, $params, count($banner_ids));
}
