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

function fn_statistics_init_secure_controllers(&$controllers)
{
    $controllers['statistics'] = 'passive';
}

function fn_statistics_get_banners_post(&$banners, &$params)
{
    if (AREA == 'C' && !fn_is_empty($banners) && !defined('AJAX_REQUEST')) {
        foreach ($banners as $k => $v) {
            if ($v['type'] == 'T' && !empty($v['description'])) {
                $i = $pos = 0;
                $matches = array();
                while (preg_match('/href=([\'|"])(.*?)([\'|"])/i', $banners[$k]['description'], $matches, PREG_OFFSET_CAPTURE, $pos)) {
                    $banners[$k]['description'] = substr_replace($banners[$k]['description'], fn_url("statistics.banners?banner_id=$v[banner_id]&amp;link=" . $i++ , 'C'), $matches[2][1], strlen($matches[2][0]));
                    $pos = $matches[2][1];
                }
            } elseif (!empty($v['url'])) {
                $banners[$k]['url'] = "statistics.banners?banner_id=$v[banner_id]";
            }

            $banner_stat = array (
                'banner_id' => $v['banner_id'],
                'type' => 'V',
                'timestamp' => TIME
            );
            fn_set_data_company_id($banner_stat);

            db_query('INSERT INTO ?:stat_banners_log ?e', $banner_stat);
        }
    } else {
        return false;
    }
}

function fn_statistics_delete_banners(&$banner_id)
{
    db_query("DELETE FROM ?:stat_banners_log WHERE banner_id = ?i", $banner_id);
}

function fn_statistics_search_by_objects(&$conditions, &$params)
{
    if (!empty($conditions['products'])) {
        $obj = $conditions['products'];
        $params['products_found'] = db_get_field("SELECT COUNT(DISTINCT($obj[table].$obj[key])) FROM ?:products as $obj[table] $obj[join] WHERE $obj[condition]");
    }
}

function fn_statistics_init_templater(&$view)
{
    if (AREA == 'C' && USER_AGENT == 'crawler' && !empty($_SERVER['HTTP_USER_AGENT']) && !defined('AJAX_REQUEST')) {
        $view->registerFilter('output', 'fn_statistics_track_robots');
    }
}

function fn_statistics_track_robots($tpl_output, &$view)
{
    if (strpos($tpl_output, '<title>') === false) {
        return $tpl_output;
    }

    $sess_id = db_get_field('SELECT sess_id FROM ?:stat_sessions WHERE uniq_code = ?i AND timestamp > ?i' . fn_get_ult_company_condition('?:stat_sessions.company_id'), fn_crc32($_SERVER['HTTP_USER_AGENT']), TIME - (24 * 60 * 60));

    if (empty($sess_id)) {
        $ip = fn_get_ip(true);
        $referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $parse_url = parse_url($referer);

        $stat_data = array(
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'host_ip' => $ip['host'],
            'proxy_ip' => $ip['proxy'],
            'client_language' => !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '',
            'ip_id' => fn_stat_ip_exist($ip),
            'client_type' => 'B',
            'robot' => CRAWLER,
            'referrer' => $referer,
            'timestamp' => TIME,
            'referrer_scheme' => empty($parse_url['scheme']) ? '' : $parse_url['scheme'],
            'referrer_host' => empty($parse_url['host']) ? '' : $parse_url['host'],
            'expiry' => 0,
            'uniq_code' => fn_crc32($_SERVER['HTTP_USER_AGENT'])
        );
        fn_set_data_company_id($stat_data);

        $request_type = STAT_LAST_REQUEST;
        $sess_id = db_query('INSERT INTO ?:stat_sessions ?e', $stat_data);
        $last_url = '';
    } else {
        $last_url = db_get_field("SELECT url FROM ?:stat_requests WHERE sess_id = ?i AND (request_type & ?i) = ?i", $sess_id, STAT_LAST_REQUEST, STAT_LAST_REQUEST);
        db_query("UPDATE ?:stat_requests SET request_type = request_type & ". STAT_ORDINARY_REQUEST ." WHERE sess_id = ?s", $sess_id);
        $request_type = STAT_END_REQUEST;
    }

    // Add to stat requests
    $this_url = fn_stat_prepare_url(REAL_URL);
    if ($last_url != $this_url) {
        $title = '';
        if (preg_match_all('/\<title\>(.*?)\<\/title\>/', $tpl_output, $m)) {
            $title = fn_html_escape($m[1][0], true);
        }

        $ve = array(
            'sess_id' => $sess_id,
            'timestamp' => TIME,
            'url' => $this_url,
            'title' => $title,
            'https' => defined('HTTPS') ? 'Y' : 'N',
            'loadtime' => microtime(true) - MICROTIME,
            'request_type' => $request_type
        );
        fn_set_data_company_id($ve);

        db_query("INSERT INTO ?:stat_requests ?e", $ve);
    }

    return $tpl_output;

}

//
// CHECK: Do IP exist?
//
function fn_stat_ip_exist($ip)
{
    if (!empty($ip['host']) && fn_is_inet_ip($ip['host'], true)) {
        $ip_num = $ip['host'];
    } elseif (!empty($ip['proxy']) && fn_is_inet_ip($ip['proxy'], true)) {
        $ip_num = $ip['proxy'];
    }
    $ip_id = isset($ip_num) ? db_get_field("SELECT ip_id FROM ?:stat_ips WHERE ip = ?i" . fn_get_ult_company_condition('?:stat_ips.company_id'), $ip_num) : false;
    if (empty($ip_id) && !empty($ip_num)) {
        $ip_id = fn_stat_save_ip(array('ip' => $ip_num));
    }

    return empty($ip_id) ? false : $ip_id;
}

//
// Save IP data.
//
function fn_stat_save_ip($ip_data)
{
    if (!empty($ip_data['ip'])) {
        $ip_data['country_code'] = fn_get_country_by_ip($ip_data['ip']);
        fn_set_data_company_id($ip_data);

        return db_query('INSERT INTO ?:stat_ips ?e', $ip_data);
    }

    return false;
}

function fn_stat_prepare_url($url)
{
    $url = fn_stat_cut_www($url);
    $location = fn_stat_cut_www(Registry::get('config.http_location'));
    $s_location = fn_stat_cut_www(Registry::get('config.https_location'));

    // Remove url prefix
    if (strpos($url, $location) !== false) {
        $url = str_replace($location, '', $url);

    } elseif (strpos($url, $s_location) !== false) {
        $url = str_replace($s_location, '', $url);
    }

    return $url;
}

function fn_stat_cut_www($url)
{
    return str_replace('://www.', '://', $url);
}

function fn_stat_get_visitors_count($time_from, $time_to)
{
    if (fn_allowed_for('ULTIMATE')) {
        $company_condition = fn_get_company_condition('?:stat_sessions.company_id');
    } else {
        $company_condition = '';
    }
    $visitors = db_get_field('SELECT COUNT(*) FROM ?:stat_sessions WHERE `timestamp` BETWEEN ?i AND ?i AND client_type = ?s ?p', $time_from, $time_to, 'U', $company_condition);

    return $visitors;
}

/**
 * Get list search terms
 *
 * @param integer $limit List limit
 * @return array search terms list
 */
function fn_get_statistic_search_terms($limit = 0)
{
    $limit_query = "";
    if (!empty($limit)) {
        $limit_query = db_quote("LIMIT ?i", $limit);
    }

    $field_company = "";
    $company_condition = "";

    if (fn_allowed_for('ULTIMATE')) {
        $field_company = "?:stat_sessions.company_id, ";
        $company_condition = fn_get_company_condition('?:stat_sessions.company_id');
    }

    $search_terms = db_get_array(
                            "SELECT "
                                . "?:stat_product_search.search_string, "
                                . "ROUND(AVG(?:stat_product_search.quantity), 0) as quantity, "
                                . "?p "
                                . "COUNT(*) as count "
                            . "FROM ?:stat_product_search "
                            . "INNER JOIN ?:stat_sessions "
                                . "ON ?:stat_sessions.sess_id = ?:stat_product_search.sess_id "
                            . "WHERE 1 "
                                . "?p "
                            . "GROUP BY ?p ?:stat_product_search.md5 "
                            . "ORDER BY count DESC "
                            . "?p ",
                            $field_company, $company_condition, $field_company, $limit_query);

    foreach ($search_terms as $key => $term) {
        $search_terms[$key]['search_string'] = !empty($term['search_string']) ? unserialize($term['search_string']) : array();
        if (fn_allowed_for('ULTIMATE')) {
            $search_terms[$key]['search_string']['company_id'] = $term['company_id'];
        }
    }

    return $search_terms;
}

/**
 * Hook for add statistics charts to dashboard
 *
 * @param integer $time_from Start time range
 * @param integer $time_to End time range
 * @param integer $graphs Charts data
 * @param integer $graph_tabs Tabs
 */
function fn_statistics_dashboard_get_graphs_data(&$time_from, &$time_to, &$graphs, &$graph_tabs, &$is_day)
{
    if (!fn_check_view_permissions('statistics.reports', 'GET')) {
        return false;
    }

    $company_condition = '';
    if (fn_allowed_for('ULTIMATE')) {
        $company_condition = fn_get_company_condition('?:stat_requests.company_id');
    }

    for ($i = $time_from; $i <= $time_to; $i = $i + ($is_day ? 60*60 : SECONDS_IN_DAY)) {
        $date = !$is_day ? date("Y, (n-1), j", $i) : date("H", $i);
        if (empty($graphs['dashboard_statistics_visits_chart'][$date])) {
            $graphs['dashboard_statistics_visits_chart'][$date] = array(
                'cur' => 0,
                'prev' => 0,
            );
        }
    }

    $visits = db_get_fields("SELECT timestamp FROM ?:stat_requests WHERE timestamp BETWEEN ?i AND ?i ?p GROUP BY sess_id ", $time_from, $time_to, $company_condition);
    foreach ($visits as $visit) {
        $date = !$is_day ? date("Y, (n-1), j", $visit) : date("H", $visit);
        $graphs['dashboard_statistics_visits_chart'][$date]['cur']++;
    }

    $visits_prev = db_get_fields("SELECT timestamp FROM ?:stat_requests WHERE timestamp BETWEEN ?i AND ?i ?p GROUP BY sess_id ", $time_from - ($time_to - $time_from), $time_from, $company_condition);
    foreach ($visits_prev as $visit) {
        $date = !$is_day ? date("Y, (n-1), j", $visit + ($time_to - $time_from)) : date("H", $visit + ($time_to - $time_from));
        $graphs['dashboard_statistics_visits_chart'][$date]['prev']++;
    }

    $graph_tabs['visits_chart'] = array(
        'title' => __('visits'),
        'js' => true
    );
}
