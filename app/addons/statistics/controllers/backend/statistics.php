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
use Tygh\Navigation\LastView;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_define('STATS_LIMIT', 20);
fn_define('STATS_DAY', 60 * 60 * 24);
fn_define('STATS_HOUR', 60 * 60);

require_once(Registry::get('config.dir.functions') . 'fn.sales_reports.php'); // FIXME: move amchars to separate file

$report = !empty($_REQUEST['selected_section']) ? $_REQUEST['selected_section'] : (!empty($_REQUEST['report']) ? $_REQUEST['report'] : '');
$chart_type = (!empty($_REQUEST['chart_type']) ? $_REQUEST['chart_type'] : 'table');
$reports_group = empty($_REQUEST['reports_group']) ? 'general' : $_REQUEST['reports_group'];
$view = Registry::get('view');

//
// Remove statistics data
//
if ($mode == 'delete') {
    if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
        $company_condition = db_quote("WHERE company_id =?i", Registry::get('runtime.company_id'));
        db_query("DELETE FROM ?:stat_ips $company_condition");
        db_query("DELETE FROM ?:stat_browsers $company_condition");
        db_query("DELETE FROM ?:stat_sessions $company_condition");
        db_query("DELETE FROM ?:stat_requests $company_condition");
        db_query("DELETE FROM ?:stat_search_phrases $company_condition");
        db_query("DELETE FROM ?:stat_product_search $company_condition");
        db_query("DELETE FROM ?:stat_search_engines $company_condition");
    } else {
        db_query("TRUNCATE TABLE ?:stat_ips");
        db_query("TRUNCATE TABLE ?:stat_browsers");
        db_query("TRUNCATE TABLE ?:stat_sessions");
        db_query("TRUNCATE TABLE ?:stat_requests");
        db_query("TRUNCATE TABLE ?:stat_search_phrases");
        db_query("TRUNCATE TABLE ?:stat_product_search");
        db_query("TRUNCATE TABLE ?:stat_search_engines");
    }

    return array(CONTROLLER_STATUS_OK, "statistics.reports");

//
// Show visitor pages
//
} elseif ($mode == 'visitor_pages') {

    list($visitors_log, $params) = fn_stat_get_visitors($_REQUEST, array(), db_quote("?:stat_sessions.sess_id = ?s", $_REQUEST['stat_sess_id']));
    list($requests, $params) = fn_stat_get_requests($_REQUEST, Registry::get('settings.Appearance.admin_elements_per_page'));

    $view->assign('visitors_log', $visitors_log);
    $view->assign('requests', $requests);
    $view->assign('search', $params);

//
// Show visitors log
//
} elseif ($mode == 'visitors') {

    $condition = '1';
    $join = array();
    $text_conditions = array();
    $clear_conditions = array();
    $object_code = empty($_REQUEST['object_code']) ? '' : $_REQUEST['object_code'];
    $report = empty($_REQUEST['report']) ? '' : $_REQUEST['report'];
    $params = $_REQUEST;
    if (empty($params['period'])) {
        $params['period'] = 'A';
    }
    list($params['time_from'], $params['time_to']) = fn_create_periods($params);

    if ($report == 'operating_systems') {
        $condition .= db_quote(" AND ?:stat_sessions.os = ?s", $object_code);
        $text_conditions['operating_system'] = $object_code;

    } elseif ($report == 'browsers') {
        $condition .= db_quote(" AND ?:stat_sessions.browser_id = ?s", $object_code);
        $text_conditions['browser'] = db_get_field("SELECT CONCAT(browser, ' ', version) FROM ?:stat_browsers WHERE browser_id = ?i", $object_code);
        if (empty($text_conditions['browser'])) {
            $text_conditions['browser'] = $object_code;
        }

    } elseif ($report == 'resolutions') {
        $xy = explode('x', $object_code);
        $x = empty($xy[0]) ? '0' : $xy[0];
        $y = empty($xy[1]) ? '0' : $xy[1];
        $condition .= db_quote(" AND ?:stat_sessions.screen_x = ?s AND ?:stat_sessions.screen_y = ?s", $x, $y);
        $text_conditions['screen_resolution'] = $x . 'x' . $y;

    } elseif ($report == 'countries') {

        $condition .= db_quote(" AND ?:stat_ips.country_code = ?s AND ?:stat_sessions.ip_id != '0'", $object_code);
        $text_conditions['country'] = db_get_field("SELECT country FROM ?:country_descriptions WHERE code = ?s AND lang_code = ?s", $object_code, CART_LANGUAGE);
        if (empty($text_conditions['country'])) {
            $text_conditions['country'] = $object_code;
        }

    } elseif ($report == 'languages') {
        $condition .= db_quote(" AND ?:stat_sessions.client_language = ?s", $object_code);
        $text_conditions['language'] = db_get_field("SELECT language FROM ?:stat_languages WHERE lang_code = ?s", $object_code);
        if (empty($text_conditions['language'])) {
            $text_conditions['language'] = $object_code;
        }

    } elseif ($report == 'ip_addresses') {
        $condition .= db_quote(" AND ?:stat_sessions.host_ip = ?i", sprintf("%u", ip2long($object_code)));
        if (!empty($_REQUEST['proxy_ip'])) {
            $condition .= db_quote(" AND ?:stat_sessions.proxy_ip = ?i", sprintf("%u", ip2long($_REQUEST['proxy_ip'])));
        }

        $text_conditions['ip'] = $object_code;
        if (!empty($_REQUEST['proxy_ip'])) {
            $text_conditions['proxy'] = $_REQUEST['proxy_ip'];
            $clear_conditions['proxy'] = 'proxy_ip';
        }

    } elseif ($report == 'all_referrers') {
        $condition .= db_quote(" AND ?:stat_sessions.referrer = ?s", $object_code);
        $text_conditions['referrer'] = $object_code;

    } elseif ($report == 'by_domain') {
        $object_code = preg_replace("/^http(?:s|):\/\//", '', $object_code);
        $object_code = rtrim($object_code, '/');
        $condition .= db_quote(" AND ?:stat_sessions.referrer_host = ?s", $object_code);
        $text_conditions['referrer_domain'] = $object_code;

    } elseif ($report == 'by_search_engine') {
        $condition .= db_quote(" AND ?:stat_sessions.engine_id = ?i", $object_code);
        $text_conditions['search_engine'] = db_get_field("SELECT engine FROM ?:stat_search_engines WHERE engine_id = ?i", $object_code);

    } elseif ($report == 'search_words') {
        $condition .= db_quote(" AND ?:stat_sessions.phrase_id = ?i", $object_code);
        $text_conditions['search_phrase'] = db_get_field("SELECT phrase FROM ?:stat_search_phrases WHERE phrase_id = ?i", $object_code);
        if (empty($text_conditions['search_phrase'])) {
            $text_conditions['search_phrase'] = $object_code;
        }

    } elseif ($report == 'came_to') {
        $condition .= db_quote(" AND ?:stat_requests.url = ?s AND (?:stat_requests.request_type & ?p) = ?p AND ?:stat_sessions.engine_id = ?i", $object_code, STAT_ORDINARY_REQUEST, STAT_FIRST_REQUEST, (empty($_REQUEST['engine_id']) ? 0 : $_REQUEST['engine_id']));
        $join['?:stat_requests'] = "?:stat_sessions.sess_id = ?:stat_requests.sess_id";
        $text_conditions['entry_point'] = $object_code;
        if (!empty($_REQUEST['engine_id'])) {
            $text_conditions['search_engine'] = db_get_field("SELECT engine FROM ?:stat_search_engines WHERE engine_id = ?i", $_REQUEST['engine_id']);
            $clear_conditions['search_engine'] = 'engine_id';
        }

    } elseif ($report == 'came_from') {
        $condition .= db_quote(" AND ?:stat_sessions.phrase_id = ?i AND ?:stat_sessions.engine_id = ?i", $object_code, (empty($_REQUEST['engine_id']) ? 0 : $_REQUEST['engine_id']));
        $text_conditions['search_phrase'] = db_get_field("SELECT phrase FROM ?:stat_search_phrases WHERE phrase_id = ?i", $object_code);
        if (empty($text_conditions['search_phrase'])) {
            $text_conditions['search_phrase'] = $object_code;
        }
        if (!empty($_REQUEST['engine_id'])) {
            $text_conditions['search_engine'] = db_get_field("SELECT engine FROM ?:stat_search_engines WHERE engine_id = ?i", $_REQUEST['engine_id']);
            $clear_conditions['search_engine'] = 'engine_id';
        }

    } elseif ($report == 'pages_by_visits') {
        $join['?:stat_requests'] = "?:stat_sessions.sess_id = ?:stat_requests.sess_id";
        $condition .= db_quote(" AND ?:stat_requests.url = ?s", $object_code);
        $text_conditions['visited_page'] = $object_code;

    } elseif ($report == 'titles_by_visits') {
        $join['?:stat_requests'] = "?:stat_sessions.sess_id = ?:stat_requests.sess_id";
        $condition .= db_quote(" AND ?:stat_requests.title = ?s", $object_code);
        $text_conditions['visited_page'] = $object_code;

    } elseif ($report == 'entry_points') {
        $join['?:stat_requests'] = "?:stat_sessions.sess_id = ?:stat_requests.sess_id";
        $condition .= db_quote(" AND ?:stat_requests.url = ?s AND (?:stat_requests.request_type & ?p) = ?p", $object_code, STAT_ORDINARY_REQUEST, STAT_FIRST_REQUEST);
        $text_conditions['entry_point'] = $object_code;

    } elseif ($report == 'exit_points') {
        $join['?:stat_requests'] = "?:stat_sessions.sess_id = ?:stat_requests.sess_id";
        $condition .= db_quote(" AND ?:stat_requests.url = ?s AND (?:stat_requests.request_type & ?p) = ?p", $object_code, STAT_LAST_REQUEST, STAT_LAST_REQUEST);
        $text_conditions['exit_point'] = $object_code;

    } elseif ($report == 'site_attendance') {
        $condition .= db_quote(" AND FROM_UNIXTIME(?:stat_sessions.timestamp, '%H') = ?s", $object_code);
        $text_conditions['time_interval'] = "$object_code:00-". sprintf("%2s", $object_code + 1) .':00';

    } elseif ($report == 'page_load_speed') {
        $text_conditions['page_load_speed'] = html_entity_decode($object_code, ENT_COMPAT, 'UTF-8');

        if (strpos($object_code, '<') !== false) {
            preg_match("/[\d]+/", $object_code, $obj_codes);
            $object_code = empty($obj_codes) ? 0 : $obj_codes[0];
            $condition .= db_quote(" AND ?:stat_requests.loadtime > 0 AND ?:stat_requests.loadtime < ?s", $object_code . '000000');

        } elseif (strpos($object_code, '>') !== false) {
            preg_match("/[\d]+/", $object_code, $obj_codes);
            $object_code = empty($obj_codes) ? 0 : $obj_codes[0];
            $condition .= db_quote(" AND ?:stat_requests.loadtime >= ?s", ($object_code * 60000000));

        } else {
            preg_match_all("/[\d]+/", $object_code, $obj_codes);
            if (empty($obj_codes[0])) {
                $start = 0;
                $end = 0;
            } else {
                $obj_codes = reset($obj_codes);
                $start = empty($obj_codes[0]) ? 0 : $obj_codes[0];
                $end = empty($obj_codes[1]) ? 0 : $obj_codes[1];
            }
            $condition .= db_quote(" AND ?:stat_requests.loadtime >= ?s AND ?:stat_requests.loadtime < ?s", $start . '000000', $end . '000000');
        }
        $join['?:stat_requests'] = "?:stat_sessions.sess_id = ?:stat_requests.sess_id";

    } elseif ($report == 'search') {
        $join['?:stat_product_search'] = "?:stat_sessions.sess_id = ?:stat_product_search.sess_id";
        $condition .= db_quote(" AND ?:stat_product_search.md5 = ?s", $object_code);
        $search_params = db_get_field("SELECT search_string FROM ?:stat_product_search WHERE md5 = ?s LIMIT 1", $object_code);
        $search_params = empty($search_params) ? array() : unserialize($search_params);
        $text_conditions = fn_stat_product_search_data($search_params, $text_conditions);

    } elseif ($report == 'online') { // SPECIAL TYPE (check if online)
        $condition .= db_quote(" AND ?:stat_requests.timestamp >= ?i AND ?:stat_requests.timestamp <= ?i", (TIME - SESSION_ONLINE), TIME);
        $join['?:stat_requests'] = "?:stat_sessions.sess_id = ?:stat_requests.sess_id";
        $text_conditions['users_online'] = round(SESSION_ONLINE / 60) . ' ' . __('short_minute');

    } elseif ($report == 'by_ip') { // SPECIAL TYPE (by ip address)
        $condition .= db_quote(" AND ?:stat_sessions.host_ip = ?i", $_REQUEST['ip']);
        $text_conditions['ip'] = long2ip($_REQUEST['ip']);

    } elseif ($report == 'general') { // SPECIAL TYPE (for general statistics)
        if ($params['period'] == STAT_PERIOD_HOUR) {
            $__time_to = $params['time_from'] + STATS_HOUR;
        } elseif ($params['period'] == STAT_PERIOD_DAY) {
            $_date = getdate($params['time_from']);
            $__time_to = mktime(23, 59, 59, $_date['mon'], $_date['mday'], $_date['year']);
        } else {
            $__time_to = $params['time_to'];
        }
        $condition .= db_quote(" AND ?:stat_requests.timestamp >= ?i AND ?:stat_requests.timestamp <= ?i", $params['time_from'], $__time_to);
        $join['?:stat_requests'] = "?:stat_sessions.sess_id = ?:stat_requests.sess_id";
    }

    list($visitors_log, $params) = fn_stat_get_visitors($_REQUEST, $join, $condition);

    if (!empty($_REQUEST['section'])) {
        $active_section = $_REQUEST['section'];
    }

    $data = array (
        'report' => $report,
        'visitors_log' => $visitors_log
    );

    $view->assign('statistics_data', $data);
    $view->assign('text_conditions', $text_conditions);
    $view->assign('clear_conditions', $clear_conditions);

//
// Reports
//
} elseif ($mode == 'reports') {
    // System reports
    if ($reports_group == 'system') {

        // Get filter information
        list($where, $join, $params) = fn_stat_filter_data($_REQUEST);
        $where .= " AND client_type = 'U'";

        $filter_condition = fn_bild_sql_join($join);
        $data = array();

        if ($report == 'browsers') {
            $data['data'] = db_get_array("SELECT sb.browser_id, CONCAT(sb.browser, ' ', sb.version) AS label, COUNT(*) as count FROM ?:stat_sessions LEFT JOIN ?:stat_browsers as sb ON ?:stat_sessions.browser_id = sb.browser_id $filter_condition WHERE $where GROUP BY ?:stat_sessions.browser_id ORDER BY count DESC LIMIT ?i", $params['limit']);

        } elseif ($report == 'resolutions') {
            $data['data'] = db_get_array("SELECT CONCAT(screen_x, 'x', screen_y) AS label, COUNT(*) as count FROM ?:stat_sessions $filter_condition WHERE $where GROUP BY screen_x, screen_y ORDER BY count DESC LIMIT ?i", $params['limit']);

        } else { // operating_systems
            $report = 'operating_systems';
            $data['data'] = db_get_array("SELECT os AS label, COUNT(*) as count FROM ?:stat_sessions $filter_condition WHERE $where GROUP BY os ORDER BY count DESC LIMIT ?i", $params['limit']);
        }

        Registry::set('navigation.tabs', array (
            'operating_systems' => array (
                'title' => __('operating_systems'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=operating_systems'), AREA, 'rel'),
                'ajax' => true
            ),
            'browsers' => array (
                'title' => __('browsers'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=browsers'), AREA, 'rel'),
                'ajax' => true
            ),
            'resolutions' => array (
                'title' => __('resolutions'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=resolutions'), AREA, 'rel'),
                'ajax' => true
            ),
        ));

        $data['report'] = $report;

        if ($chart_type != 'table') {
            $view->assign('chart_data', fn_stat_map_data($chart_type, $data['data'], array('label' => 'title', 'count' => 'value')));
        }
        $view->assign('statistics_data', fn_stat_format_data($data));

    // Geo reports
    } elseif ($reports_group == 'geography') {

        // Get filter information
        list($where, $join, $params) = fn_stat_filter_data($_REQUEST);
        $where .= " AND client_type = 'U'";

        $filter_condition = fn_bild_sql_join($join);

        $data = array();

        if ($report == 'cities') {
            // Not implemented yet

        } elseif ($report == 'languages') {
            $data['data'] = db_get_array("SELECT client_language, IF(client_language, CONCAT(sl.language, ' (', client_language, ')'), sl.language) AS label, COUNT(*) as count FROM ?:stat_sessions LEFT JOIN ?:stat_languages as sl ON ?:stat_sessions.client_language = sl.lang_code $filter_condition WHERE $where GROUP BY client_language ORDER BY count DESC LIMIT ?i", $params['limit']);

        } elseif ($report == 'ip_addresses') {
            $data['data'] = db_get_array("SELECT INET_NTOA(host_ip) as host_ip, INET_NTOA(proxy_ip) as proxy_ip, COUNT(*) as count FROM ?:stat_sessions $filter_condition WHERE $where GROUP BY host_ip, proxy_ip ORDER BY count DESC LIMIT ?i", $params['limit']);

        } else { // countries

            $report = 'countries';
            $data['data'] = db_get_array("SELECT si.country_code, cd.country AS label, COUNT(*) as count FROM ?:stat_sessions LEFT JOIN ?:stat_ips as si ON ?:stat_sessions.ip_id = si.ip_id LEFT JOIN ?:country_descriptions as cd ON si.country_code = cd.code AND cd.lang_code = ?s ?p WHERE ?p AND ?:stat_sessions.ip_id != 0 GROUP BY si.country_code ORDER BY count DESC LIMIT ?i", CART_LANGUAGE, $filter_condition, $where, $params['limit']);
        }

        Registry::set('navigation.tabs', array (
            'languages' => array (
                'title' => __('languages'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=languages'), AREA, 'rel'),
                'ajax' => true
            ),
            'ip_addresses' => array (
                'title' => __('ip_addresses'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=ip_addresses'), AREA, 'rel'),
                'ajax' => true
            ),
            'countries' => array (
                'title' => __('countries'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=countries'), AREA, 'rel'),
                'ajax' => true
            ),
        ));

        $data['report'] = $report;

        if ($chart_type != 'table') {
            $view->assign('chart_data', fn_stat_map_data($chart_type, $data['data'], array('label' => 'title', 'count' => 'value')));
        }
        $view->assign('statistics_data', fn_stat_format_data($data));

    } elseif ($reports_group == 'referrers') {

        // Get filter information
        list($where, $join, $params) = fn_stat_filter_data($_REQUEST);
        $where .= " AND client_type = 'U' AND referrer != ''";

        $filter_condition = fn_bild_sql_join($join);

        $data = array();

        if ($report == 'by_domain') {
            $data['data'] = db_get_array("SELECT CONCAT(referrer_scheme, '://', referrer_host, '/') AS label, COUNT(*) AS count FROM ?:stat_sessions $filter_condition WHERE $where GROUP BY referrer_scheme, referrer_host ORDER BY count DESC LIMIT ?i", $params['limit']);

        } elseif ($report == 'by_search_engine') {

            $join['?:stat_search_engines'] = "?:stat_search_engines.engine_id = ?:stat_sessions.engine_id";
            $filter_condition = fn_bild_sql_join($join);

            $data['data'] = db_get_array("SELECT ?:stat_sessions.engine_id, engine AS label, COUNT(*) AS count FROM ?:stat_sessions $filter_condition WHERE $where AND ?:stat_sessions.engine_id != 0 GROUP BY ?:stat_sessions.engine_id, engine ORDER BY count DESC LIMIT ?i", $params['limit']);

        } elseif ($report == 'search_words') {
            $data['data'] = db_get_array("SELECT ?:stat_sessions.phrase_id, sph.phrase AS label, COUNT(*) AS count FROM ?:stat_sessions LEFT JOIN ?:stat_search_phrases as sph ON sph.phrase_id = ?:stat_sessions.phrase_id $filter_condition WHERE $where AND ?:stat_sessions.phrase_id != 0 GROUP BY ?:stat_sessions.phrase_id, sph.phrase ORDER BY count DESC LIMIT ?i", $params['limit']);

        } elseif ($report == 'came_to') {

            $_where_time = fn_get_sql_where_time('sr', $params['time_from'], $params['time_to']);
            $where .= ' AND (sr.request_type & '. STAT_ORDINARY_REQUEST .') = '. STAT_FIRST_REQUEST . (empty($_where_time) ? '' : " AND $_where_time ") . " AND ?:stat_sessions.engine_id != '0'";

            $join['?:stat_search_engines'] = "?:stat_search_engines.engine_id = ?:stat_sessions.engine_id";
            $filter_condition = fn_bild_sql_join($join);

            $data['data'] = db_get_array("SELECT ?:stat_sessions.engine_id, sr.url AS label, COUNT(*) AS count FROM ?:stat_sessions LEFT JOIN ?:stat_requests as sr ON sr.sess_id = ?:stat_sessions.sess_id $filter_condition WHERE $where GROUP BY ?:stat_sessions.engine_id, sr.url ORDER BY count DESC LIMIT ?i", $params['limit']);

        } elseif ($report == 'came_from') {

            $join['?:stat_search_engines'] = "?:stat_search_engines.engine_id = ?:stat_sessions.engine_id";
            $filter_condition = fn_bild_sql_join($join);

            $data['data'] = db_get_array("SELECT ?:stat_sessions.engine_id, ?:stat_sessions.phrase_id, CONCAT('[', engine, '] ', sph.phrase) AS label, COUNT(*) AS count FROM ?:stat_sessions LEFT JOIN ?:stat_search_phrases as sph ON sph.phrase_id = ?:stat_sessions.phrase_id $filter_condition WHERE $where AND ?:stat_sessions.phrase_id != 0 AND ?:stat_sessions.engine_id != 0 GROUP BY ?:stat_sessions.engine_id, engine, ?:stat_sessions.phrase_id, sph.phrase ORDER BY count DESC LIMIT ?i", $params['limit']);

        } else { // all referrers

            $report = 'all_referrers';
            $data['data'] = db_get_array("SELECT referrer AS label, sph.phrase, COUNT(*) AS count FROM ?:stat_sessions LEFT JOIN ?:stat_search_phrases as sph ON sph.phrase_id = ?:stat_sessions.phrase_id $filter_condition WHERE $where GROUP BY referrer, sph.phrase ORDER BY count DESC LIMIT ?i", $params['limit']);
        }

        Registry::set('navigation.tabs', array (
            'by_domain' => array (
                'title' => __('by_domain'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=by_domain'), AREA, 'rel'),
                'ajax' => true
            ),
            'by_search_engine' => array (
                'title' => __('by_search_engine'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=by_search_engine'), AREA, 'rel'),
                'ajax' => true
            ),
            'search_words' => array (
                'title' => __('search_words'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=search_words'), AREA, 'rel'),
                'ajax' => true
            ),
            'came_from' => array (
                'title' => __('came_from'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=came_from'), AREA, 'rel'),
                'ajax' => true
            ),
            'came_to' => array (
                'title' => __('came_to'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=came_to'), AREA, 'rel'),
                'ajax' => true
            ),
            'all_referrers' => array (
                'title' => __('all'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=all_referrers'), AREA, 'rel'),
                'ajax' => true
            ),
        ));

        $data['report'] = $report;
        if ($chart_type != 'table') {
            $view->assign('chart_data', fn_stat_map_data($chart_type, $data['data'], array('label' => 'title', 'count' => 'value')));
        }
        $view->assign('statistics_data', fn_stat_format_data($data));
        $view->assign('search_engines', Registry::get('search_engines'));

    } elseif ($reports_group == 'pages') {

        // Get filter information
        list($where, $join, $params) = fn_stat_filter_data($_REQUEST);
        $where .= " AND client_type = 'U'";

        $filter_condition = fn_bild_sql_join($join);

        $data = array();
        if ($report == 'titles_by_visits') {
            if (isset($join['?:stat_requests'])) {
                unset($join['?:stat_requests']);
            }

            $_where_time = fn_get_sql_where_time('?:stat_requests', $params['time_from'], $params['time_to']);
            if (!empty($_where_time)) {
                $where .= " AND $_where_time";
            }

            $join['?:stat_sessions'] = "?:stat_requests.sess_id = ?:stat_sessions.sess_id";
            $filter_condition = fn_bild_sql_join($join);

            $data['data'] = db_get_array("SELECT title AS label, COUNT(*) AS count FROM ?:stat_requests $filter_condition WHERE $where GROUP BY title ORDER BY count DESC LIMIT ?i", $params['limit']);

        } elseif ($report == 'entry_points') {
            $data['data'] = db_get_array("SELECT sr.url AS label, sr.title, COUNT(*) AS count FROM ?:stat_sessions LEFT JOIN ?:stat_requests as sr ON sr.sess_id = ?:stat_sessions.sess_id AND sr.timestamp = ?:stat_sessions.timestamp $filter_condition WHERE $where AND sr.url != '' GROUP BY sr.url ORDER BY count DESC LIMIT ?i", $params['limit']);

        } elseif ($report == 'exit_points') {
            $join['?:stat_requests'] = "?:stat_requests.sess_id = ?:stat_sessions.sess_id";
            $filter_condition = fn_bild_sql_join($join);
            $_where_time = fn_get_sql_where_time('?:stat_requests', $params['time_from'], $params['time_to']);
            if (!empty($_where_time)) {
                $where .= " AND $_where_time";
            }

            $data['data'] = db_get_array("SELECT url AS label, title, COUNT(*) AS count FROM ?:stat_sessions $filter_condition WHERE $where AND url != '' AND (?:stat_requests.request_type & ". STAT_LAST_REQUEST .") = ". STAT_LAST_REQUEST . " GROUP BY url ORDER BY count DESC LIMIT ?i", $params['limit']);

        } else {

            $report = 'pages_by_visits';

            if (isset($join['?:stat_requests'])) {
                unset($join['?:stat_requests']);
            }
            $_where_time = fn_get_sql_where_time('?:stat_requests', $params['time_from'], $params['time_to']);
            if (!empty($_where_time)) {
                $where .= " AND $_where_time";
            }
            $join['?:stat_sessions'] = "?:stat_requests.sess_id = ?:stat_sessions.sess_id";

            $filter_condition = fn_bild_sql_join($join);
            $data['data'] = db_get_array("SELECT url AS label, SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT title ORDER BY ?:stat_requests.timestamp DESC SEPARATOR '[##]'), '[##]', 1) AS title, COUNT(*) AS count FROM ?:stat_requests $filter_condition WHERE $where GROUP BY url ORDER BY count DESC LIMIT ?i", $params['limit']);
        }

        Registry::set('navigation.tabs', array (
            'titles_by_visits' => array (
                'title' => __('titles_by_visits'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=titles_by_visits'), AREA, 'rel'),
                'ajax' => true
            ),
            'entry_points' => array (
                'title' => __('entry_points'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=entry_points'), AREA, 'rel'),
                'ajax' => true
            ),
            'exit_points' => array (
                'title' => __('exit_points'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=exit_points'), AREA, 'rel'),
                'ajax' => true
            ),
            'pages_by_visits' => array (
                'title' => __('pages_by_visits'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=pages_by_visits'), AREA, 'rel'),
                'ajax' => true
            ),
        ));

        $data['report'] = $report;
        if ($chart_type != 'table') {
            $view->assign('chart_data', fn_stat_map_data($chart_type, $data['data'], array('label' => 'title', 'count' => 'value')));
        }
        $view->assign('statistics_data', fn_stat_format_data($data));

    } elseif ($reports_group == 'audience') {

        // Get filter information
        list($where, $join, $params) = fn_stat_filter_data($_REQUEST);
        $where .= " AND client_type = 'U'";

        $filter_condition = fn_bild_sql_join($join);

        $data = array();
        if ($report == 'site_attendance') {

            $data['data'] = db_get_array("SELECT FROM_UNIXTIME(?:stat_sessions.timestamp, '%H') as hour, CONCAT(FROM_UNIXTIME(?:stat_sessions.timestamp, '%H'), ':00 ', ?s) AS label, COUNT(*) AS count FROM ?:stat_sessions ?p WHERE ?p GROUP BY hour ORDER BY hour", __('hours'), $filter_condition, $where);

        } elseif ($report == 'visit_time') {
            $join['?:stat_sessions'] = "sr.sess_id=?:stat_sessions.sess_id";
            $filter_condition = fn_bild_sql_join($join);
            $data = array();
            $range = array(1, 2, 5, 15, 30, 60, 120, 121);
            $r_max = end($range);
            $r_min = reset($range);
            $rng_srt = -1;
            foreach ($range as $rng) {
                // calculate client count in range ($rng_srt - $rng)
                $cnt = fn_calc_aggregate_functions("SELECT sr.sess_id FROM ?:stat_requests as sr $filter_condition WHERE $where GROUP BY sr.sess_id HAVING (MAX(sr.timestamp) - MIN(sr.timestamp)) > " . ($rng_srt * 60) . ' ' . ($rng == $r_max ? '' : "AND (MAX(sr.timestamp) - MIN(sr.timestamp)) <= ". ($rng * 60)), STAT_ROW_LIMIT);

                if ($cnt > 0) {
                    $name = $rng == $r_min ? "<= $rng" : (($rng == $r_max ? '> '. $rng_srt/60 : ($rng_srt < 60 ? $rng_srt : $rng_srt/60) .' - '. ($rng < 60 ? $rng : $rng/60)));
                    $data['data'][$rng] = array('label' => "$name " . __($rng < 60 ? 'short_minute' : 'short_hour'), 'count' => $cnt);
                }

                $rng_srt = $rng;
            }
            $data['average_duration'] = fn_this_day_begin() + round(fn_calc_aggregate_functions("SELECT MAX(sr.timestamp) - MIN(sr.timestamp) FROM ?:stat_requests as sr $filter_condition WHERE $where GROUP BY sr.sess_id", STAT_ROW_LIMIT, 'AVG'));

        } elseif ($report == 'repeat_new_visits') {
            $stat_settings = Registry::get('addons.statistics');
            $fields = ($stat_settings['unique_clients_by'] == 'cookie') ? 'uniq_code' : 'host_ip, proxy_ip';
            $data['data'] = array();

            $filter_condition = fn_bild_sql_join($join);
            $old = db_get_fields("SELECT $fields, COUNT(*) AS count FROM ?:stat_sessions $filter_condition WHERE $where GROUP BY $fields HAVING count > 1");
            $new = db_get_fields("SELECT $fields, COUNT(*) AS count FROM ?:stat_sessions $filter_condition WHERE $where GROUP BY $fields HAVING count = 1");

            if (!empty($old) || !empty($new)) {
                $data['data'] = array(
                    'O' => array('label' => __('old_visitors'), 'count' => count($old)),
                    'N' => array('label' => __('new_visitors'), 'count' => count($new)),
                );
            }

        } elseif ($report == 'page_load_speed') {
            if (isset($join['?:stat_requests'])) {
                unset($join['?:stat_requests']);
            }

            $load_speed_details = empty($_REQUEST['load_speed_details']) ? '' : $_REQUEST['load_speed_details'];

            $filter_condition = fn_bild_sql_join($join);
            $order_filed = 'loadtime';
            $order_direction = 'ASC';

            $_where_time = fn_get_sql_where_time('?:stat_requests', $params['time_from'], $params['time_to']);
            if (!empty($_where_time)) {
                $where .= " AND $_where_time";
            }
            $loadtime_condition = "loadtime > 0 AND loadtime < 1000000";
            $count = db_get_field("SELECT COUNT(*) AS count FROM ?:stat_requests LEFT JOIN ?:stat_sessions ON ?:stat_requests.sess_id = ?:stat_sessions.sess_id $filter_condition WHERE $where AND $loadtime_condition");
            $data['data']['lt'] = array('label' => '< 1 '. __('short_second'), 'count' => $count);
            if ($load_speed_details == 'lt') {
                $data['data']['lt']['pages'] = fn_get_stat_requests_over_period(0, 1000000, $order_filed, $order_direction);
            }

            $ranges = array (
                array(1, 4, 1),
                array(5, 11, 5),
                array(15, 46, 15),
            );

            foreach ($ranges as $range) {
                list($from, $to, $step) = $range;

                for ($i = $from; $i < $to ; $i = $i + $step) {
                    $loadtime_condition = "(loadtime >= '" . ($i * 1000000) . "' AND loadtime < '" . (($i + $step) * 1000000) . "')";
                    $count = db_get_field("SELECT COUNT(*) AS count FROM ?:stat_requests LEFT JOIN ?:stat_sessions ON ?:stat_requests.sess_id = ?:stat_sessions.sess_id $filter_condition WHERE $where AND $loadtime_condition");
                    $data['data'][$i] = array('label' => $i .' - ' . ($i + $step) . ' ' . __('short_second'), 'count' => $count);
                    if ($load_speed_details == $i) {
                        $data['data'][$i]['pages'] = fn_get_stat_requests_over_period($i * 1000000, ($i + $step) * 1000000, $order_filed, $order_direction);
                    }
                }
            }

            $loadtime_condition = "loadtime >= 60000000";
            $count = db_get_field("SELECT COUNT(*) AS count FROM ?:stat_requests LEFT JOIN ?:stat_sessions ON ?:stat_requests.sess_id = ?:stat_sessions.sess_id $filter_condition WHERE $where AND $loadtime_condition");
            $data['data']['gt'] = array('label' => '>= 1 '. __('short_minute'), 'count' => $count);
            if ($load_speed_details == 'gt') {
                $data['data']['gt']['pages'] = fn_get_stat_requests_over_period(60000000, '', $order_filed, $order_direction);
            }

            $max = 0;
            foreach ($data['data'] as $k => $v) {
                if (empty($v['count'])) {
                    unset($data['data'][$k]);
                    continue;
                }
                $max += $v['count'];
                $data['data'][$k]['sum_count'] = $max;
            }

            foreach ($data['data'] as $k => $v) {
                $data['data'][$k]['sum_percent'] = round($v['sum_count'] / $max * 10000) / 100;
            }
        } else {

            $report = 'total_pages_viewed_per_visitor';

            $_join = $join;
            $_join['?:stat_sessions'] = "?:stat_requests.sess_id = ?:stat_sessions.sess_id";
            unset($_join['?:stat_requests']);

            $filter_condition = fn_bild_sql_join($_join);

            $range = array(1, 2, 3, 4, 5, 6, 11, 21, 31, 41, 51, 101);
            $r_max = end($range);
            $r_min = reset($range);
            foreach ($range as $rng) {
                if ($rng == $r_min) {
                    $rng_srt = $rng;
                    continue;
                }

                // calculate client count in range ($rng_srt - $rng)
                $cnt = fn_calc_aggregate_functions("SELECT ?:stat_requests.sess_id FROM ?:stat_requests $filter_condition WHERE $where GROUP BY sess_id HAVING COUNT(*) >= $rng_srt ". ($rng == $r_max ? '' : "AND COUNT(*) < $rng"), STAT_ROW_LIMIT);

                if ($cnt > 0) {
                    $name = $rng == $r_max ? '> ' . ($r_max - 1) : (($rng - $rng_srt) > 1 ? "$rng_srt - " . ($rng - 1) : $rng_srt);
                    $data['data'][$name] = array('label' => "$name " . __($rng_srt == 1 ? 'page' : 'pages'), 'count' => $cnt);
                }

                $rng_srt = $rng;
            }

            $count = db_get_field("SELECT COUNT(*) FROM ?:stat_requests $filter_condition WHERE $where");
            $filter_condition = fn_bild_sql_join($join);
            $max = db_get_field("SELECT COUNT(*) FROM ?:stat_sessions $filter_condition WHERE $where");
            if ($max > 0) {
                $data['average_depth'] = round($count / $max * 100) / 100;
            }
        }

        Registry::set('navigation.tabs', array (
            'site_attendance' => array (
                'title' => __('site_attendance'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=site_attendance'), AREA, 'rel'),
                'ajax' => true
            ),
            'visit_time' => array (
                'title' => __('visit_time'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=visit_time'), AREA, 'rel'),
                'ajax' => true
            ),
            'repeat_new_visits' => array (
                'title' => __('repeat_new_visits'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=repeat_new_visits'), AREA, 'rel'),
                'ajax' => true
            ),
            'page_load_speed' => array (
                'title' => __('page_load_speed'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=page_load_speed'), AREA, 'rel'),
                'ajax' => true
            ),
            'total_pages_viewed_per_visitor' => array (
                'title' => __('total_pages_viewed_per_visitor'),
                'href' => fn_url(fn_link_attach(Registry::get('config.current_url'), 'report=total_pages_viewed_per_visitor'), AREA, 'rel'),
                'ajax' => true
            ),
        ));

        $data['report'] = $report;
        if ($chart_type != 'table') {
            $view->assign('chart_data', fn_stat_map_data($chart_type, $data['data'], array('label' => 'title', 'count' => 'value')));
        }
        $view->assign('statistics_data', fn_stat_format_data($data));

    } elseif ($reports_group == 'products') {

        // Get filter information
        list($where, $join, $params) = fn_stat_filter_data($_REQUEST);
        $fields = "?:stat_product_search.search_string AS label, ?:stat_product_search.md5,"
            . " DATE_FORMAT(FROM_UNIXTIME(?:stat_sessions.timestamp), '%Y-%m-%d') AS date,"
            . " AVG(?:stat_product_search.quantity) AS quantity, COUNT(*) as count";

        if (fn_allowed_for('ULTIMATE')) {
            $join['?:companies'] = "?:stat_sessions.company_id = ?:companies.company_id";
            $fields .= ', ?:companies.storefront';
        }

        $where .= " AND client_type = 'U'";
        $join['?:stat_sessions'] = "?:stat_product_search.sess_id=?:stat_sessions.sess_id";

        $filter_condition = fn_bild_sql_join($join);

        $data = array();
        if (empty($report)) {
            $report = 'search';
        }

        if ($report == 'search') {
            $data['data'] = db_get_array(
                    "SELECT $fields"
                    . " FROM ?:stat_product_search $filter_condition"
                    . " WHERE $where"
                    . " GROUP BY ?:stat_product_search.md5,"
                    . " DATE_FORMAT(FROM_UNIXTIME(?:stat_sessions.timestamp), '%Y-%m-%d')"
                    . " ORDER BY date DESC, count DESC LIMIT ?i", $params['limit']
                );

            if (!empty($data['data'])) {
                foreach ($data['data'] as $k => $v) {
                    $data['data'][$k]['label'] = unserialize($v['label']);
                    $data['data'][$k]['url'] = empty($data['data'][$k]['label']) ? '' : http_build_query($data['data'][$k]['label']);

                    $url = fn_url("products.search?" . $data['data'][$k]['url'], 'C', 'rel');
                    if (!empty($v['storefront'])) {
                        $data['data'][$k]['storefront_url'] = (defined('HTTPS') ? 'https://' : 'http://') . $v['storefront'] . '/' . $url;
                    } else {
                        $data['data'][$k]['storefront_url'] = $url;
                    }
                }
            }

            list($product_features) = fn_get_product_features();
            $view->assign('product_features', $product_features);
        }

        $data['report'] = $report;
        $view->assign('statistics_data', $data);

    } elseif ($reports_group == 'general') {

        $items_per_page = !empty($_REQUEST['limit']) ? $_REQUEST['limit'] : 0;
        list($data, $search, $period) = fn_stat_get_general_reports($_REQUEST, $items_per_page);

        if ($chart_type != 'table') {
            $view->assign('chart_data', fn_stat_map_data($chart_type, $data['data'], array('total', 'visitors', 'hosts', 'robots'), array(__('total'), __('visits'), __('hosts'), __('robots')), true));
        }

        $view->assign('statistic_period', $period);
        $view->assign('statistics_data', $data);
        $view->assign('search', $search);
    }
}

if (!empty($chart_type)) {
    $view->assign('chart_type', $chart_type);
}

if (!empty($params)) {
    $view->assign('search', $params);
}

// [Page sections]
$sections = array();

foreach (array('general', 'system', 'geography', 'referrers', 'pages', 'audience', 'products') as $s) {
    $sections[$s] = array (
        'title' => __($s),
        'href' => "statistics.reports?reports_group=$s",
        'ajax' => true
    );
}
Registry::set('navigation.dynamic.sections', $sections);
Registry::set('navigation.dynamic.active_section', empty($active_section) ? $reports_group : $active_section);

$view->assign('reports_group', $reports_group);

//
// [FUNCTIONS]
//

//
// Build sql condition for time range.
//
function fn_get_sql_where_time($table_name, $time_from, $time_to, $field = 'timestamp')
{
    return (db_quote("$table_name.$field >= ?i AND $table_name.$field < ?i", $time_from, empty($time_to) ? TIME : $time_to));
}

//
// Convert join array to SQL string
//
function fn_bild_sql_join($joins)
{
    if (empty($joins)) {
        return '';
    }

    // Sort joins to avoid cross-joining errors in mysql 5.1+
    $sorted_joins = array();
    $joined_tables = array();

    $joined_tables = array_keys($joins);
    $required = array();

    foreach ($joins as $table => $condition) {
        $required[$table] = array();
        foreach ($joined_tables as $check_table) {
            if ($check_table == $table) {
                continue;
            }

            if (strpos($condition, $check_table) !== false) {
                $required[$table][] = $check_table;
            }
        }
    }
    foreach ($joins as $table => $condition) {
        if (empty($required[$table])) {
            $sorted_joins[$table] = $condition;
            unset($joins[$table]);
        }
    }

    $included_tables = array_keys($sorted_joins);
    while (count($joins) > 0) {
        foreach ($joins as $table => $condition) {
            $not_inluded_tables = array_diff($required[$table], $included_tables);
            if (empty($not_included_tables)) {
                $included_tables[] = $table;
                $sorted_joins[$table] = $condition;
                unset($joins[$table]);
            }
        }
    }

    $r_join = '';
    foreach ($sorted_joins as $table => $condition) {
        $r_join .= "LEFT JOIN $table ON ($condition) ";
    }

    return $r_join;
}
//
// The function creates SQL conditions from filter conditions.
//
function fn_stat_filter_data($params)
{
    $params = LastView::instance()->update('statistics', $params);
    $join = array();
    $where = '';

    if (!empty($params)) {
        $sql_where = array();
        $pattern = "/AND|OR/";

        foreach ($params as $param => $value) {
            if (empty($value)) {
                continue;
            }

            $conditions = preg_split($pattern, $value);
            if (empty($conditions)) {
                $conditions = array($value);
            }
            preg_match_all($pattern, $value, $conjunctions);
            $_condition = array();
            foreach ($conditions as $k => $v) {
                $_condition[] = array(
                    'condition' => trim($v),
                    'conjunction' => ((empty($k) || empty($conjunctions[0][$k - 1]) ? '' : trim($conjunctions[0][$k - 1]))),
                );
            }
            $__where = '';

            foreach ($_condition as $c) {
                if ($param == 'ip_address') {
                    $_long_ip = sprintf('%u', ip2long($c['condition']));
                    $_where = db_quote("?:stat_sessions.proxy_ip = ?i OR ?:stat_sessions.host_ip = ?i", $_long_ip, $_long_ip);

                } elseif ($param == 'language') {
                        $_where = db_quote("(?:stat_sessions.client_language LIKE ?l OR ?:stat_languages.language LIKE ?l)", "$c[condition]%", "$c[condition]%");
                        $join['?:stat_languages'] = "?:stat_sessions.client_language = ?:stat_languages.lang_code";

                } elseif ($param == 'search_phrase') {
                        $_where = db_quote("?:stat_search_phrases.phrase = ?s", $c['condition']);
                        $join['?:stat_search_phrases'] = "?:stat_sessions.phrase_id = ?:stat_search_phrases.phrase_id";

                } elseif ($param == 'url') {
                        $_where = db_quote("?:stat_requests.url LIKE ?l", "%$c[condition]%");
                        $join['?:stat_requests'] = "?:stat_sessions.sess_id = ?:stat_requests.sess_id";

                } elseif ($param == 'page_title') {
                        $_where = db_quote("?:stat_requests.title LIKE ?l", "%$c[condition]%");
                        $join['?:stat_requests'] = "?:stat_sessions.sess_id = ?:stat_requests.sess_id";

                } elseif ($param == 'user_agent') {
                        $_where = db_quote("?:stat_sessions.user_agent LIKE ?l", "%$c[condition]%");

                } elseif ($param == 'browser_name') {
                        $_where = db_quote("?:stat_browsers.browser LIKE ?l", "%$c[condition]%");
                        $join['?:stat_browsers'] = "?:stat_sessions.browser_id = ?:stat_browsers.browser_id";

                } elseif ($param == 'browser_version') {
                        $_where = db_quote("?:stat_browsers.version = ?s", $c['condition']);
                        $join['?:stat_browsers'] = "?:stat_sessions.browser_id = ?:stat_browsers.browser_id";

                } elseif ($param == 'operating_system') {
                        $_where = db_quote("UPPER(?:stat_sessions.os) LIKE UPPER(?s)", "%$c[condition]%");

                } elseif ($param == 'country') {
                        $_where = db_quote("(?:stat_ips.country_code = ?s OR ?:country_descriptions.country LIKE ?l)", $c['condition'], $c['condition']);
                        $join['?:stat_ips'] = "?:stat_sessions.ip_id = ?:stat_ips.ip_id";
                        $join['?:country_descriptions'] = db_quote("?:stat_ips.country_code = ?:country_descriptions.code AND ?:country_descriptions.lang_code = ?s", CART_LANGUAGE);

                } elseif ($param == 'referrer_url') {
                        $_where = db_quote("?:stat_sessions.referrer LIKE ?l", "%$c[condition]%");

                } else {
                        $_where = '';
                }

                if (!empty($_where)) {
                    $__where .= "$c[conjunction] $_where ";
                }
            }

            if (!empty($__where)) {
                $sql_where[] = $__where;
            }
        }

        if (!empty($sql_where)) {
            $where .= (empty($where) ? '' : ' AND ') . (!empty($params['exclude_condition']) ? 'NOT' : '') . '('. implode(') AND ' . (!empty($params['exclude_condition']) ? 'NOT ' : ' ') . '(', $sql_where) . ')';
        } else {
            $where .= "1";
        }
    }

    if (empty($params['period'])) {
        $params['period'] = 'A';
    }

    list($params['time_from'], $params['time_to']) = fn_create_periods($params);

    if (!empty($params['time_from']) || !empty($params['time_to'])) {
        $where = (empty($where) ? '' : $where . ' AND ') . fn_get_sql_where_time('?:stat_sessions', $params['time_from'], $params['time_to']);
        if (!empty($join['?:stat_requests'])) {
            $where .= ' AND ' . fn_get_sql_where_time('?:stat_requests', $params['time_from'], $params['time_to']);
        }
    }

    if ($company_condition = fn_get_ult_company_condition('?:stat_sessions.company_id')) {
        $where = empty($where) ? '1' : $where;
        $where .= $company_condition;
    }

    if (empty($params['limit'])) {
        $params['limit'] = STATS_LIMIT;
    }

    return array($where, $join, $params);
}

//
// The function returns request urls over a period of time.
//
function fn_get_stat_requests_over_period($begin, $end, $order_field, $order_direction)
{
    list($where, $join, $params) = fn_stat_filter_data($_REQUEST); // FIXME duplicate
    $where .= " AND client_type = 'U'";

    if (isset($join['?:stat_requests'])) {
        unset($join['?:stat_requests']);
    }

    $fields = array('req_id', '?:stat_requests.url', 'https', '?:stat_requests.timestamp', 'loadtime');

    if (fn_allowed_for('ULTIMATE')) {
        $join['?:companies'] = "?:stat_sessions.company_id = ?:companies.company_id";
        $fields[] = '?:companies.storefront';
    }
    $sql_join = fn_bild_sql_join($join);

    if (empty($order_field) || !in_array($order_field, $fields)) {
        $order_field = 'loadtime';
    }
    if (empty($order_direction) || !in_array($order_direction, array('ASC', 'DESC'))) {
        $order_direction = 'ASC';
    }
    $_where_time = fn_get_sql_where_time('?:stat_requests', $params['time_from'], $params['time_to']);
    if (!empty($_where_time)) {
        $where .= " AND $_where_time";
    }
    $loadtime_condition = empty($begin) ? " (loadtime > '0') " : db_quote(" (loadtime >= ?i) ", $begin);
    $loadtime_condition .= empty($end) ? '' : db_quote(" AND (loadtime < ?i) ", $end);
    $requests = db_get_hash_array("SELECT ". implode(', ', $fields) ." FROM ?:stat_requests LEFT JOIN ?:stat_sessions ON ?:stat_requests.sess_id = ?:stat_sessions.sess_id $sql_join WHERE $where AND $loadtime_condition ORDER BY $order_field $order_direction", 'req_id');
    if (!empty($requests)) {
        foreach ($requests as $req_id => $req) {
            if (!empty($req['url'])) {
                $requests[$req_id]['url'] = fn_stat_get_visitor_url($req, $req);
            }
        }
    }

    return $requests;
}

//
// calculate aggregate functions
//
// $func = 'COUNT' | 'SUM' | 'AVG'
//
function fn_calc_aggregate_functions($sql, $limit, $func = 'COUNT')
{
    $limit = intval($limit);
    if (empty($sql) || empty($limit)) {
        return false;
    }

    $func = empty($func) ? 'COUNT' : strtoupper($func);

    $limit_start = 0;
    $result = 0;
    $cnt = 0;
    $sum = 0;

    do {
        $_data = db_get_fields("$sql LIMIT $limit_start, $limit");
        $limit_start += $limit;
        if (empty($_data)) {
            continue;
        }

        switch ($func) {
            case 'SUM':
                $result += array_sum($_data);
                break;

            case 'AVG':
                $sum += array_sum($_data);
                $cnt += count($_data);
                break;

            default:
                $result += count($_data);
                break;
        }

    } while (!empty($_data));

    if ($func == 'AVG') {
        $result = empty($cnt) ? 0 : $sum/$cnt;
    }

    return $result;
}

function fn_stat_format_data($data)
{

    if (!empty($data['data'])) {
        // Calculate percentage
        $total = 0;

        foreach ($data['data'] as $v) {
            $total += $v['count'];
        }

        // Calculate percent value for every item
        foreach ($data['data'] as $k => $v) {
            $data['data'][$k]['percent'] = !empty($total) ? round($v['count'] * 100 / $total, 2) : 0;
        }
    }

    return $data;
}

function fn_stat_map_data($chart, $data, $map, $titles = array(), $aggregate = false)
{
    $result = array();

    if ($aggregate == false) {
        foreach ($data as $k => $v) {
            foreach ($map as $field => $alias) {
                $result[$k][$alias] = ($v[$field] == '') ? __('undefined') : $v[$field];
            }
        }
    } else {
        foreach ($data as $k => $v) {
            foreach ($map as $_k => $field) {
                $result[$field]['values'][$k]['title'] = $k;
                $result[$field]['values'][$k]['value'] = $v[$field];
                $result[$field]['title'] = $titles[$_k];
            }
        }
    }

    /*if (count($result) > $max_items) {
        $other = array_splice($result, $max_items);

        $total = 0;
        foreach ($other as $k => $v) {
            $total += $v['value'];
        }

        $result[] = arra
    }*/

    return fn_amcharts_data($chart, $result);
}

//
// The function returns visitors log.
//
function fn_stat_get_visitors($params, $extra_join, $condition)
{
    list($where, $join, $params) = fn_stat_filter_data($params);

    $sortings = array (
        'date' => "?:stat_sessions.timestamp",
        'ip' => "?:stat_sessions.host_ip",
        'proxy' => "?:stat_sessions.proxy_ip",
        'robot' => "?:stat_sessions.robot",
        'os' => "?:stat_sessions.os",
        'browser' => array("?:stat_browsers.browser", "?:stat_browsers.version"),
        'screen' => array("?:stat_sessions.screen_x", "?:stat_sessions.screen_y", "?:stat_sessions.color"),
        'language' => "?:stat_languages.language",
        'country' => "?:country_descriptions.country",
    );

    $sorting = db_sort($params, $sortings, 'date', 'desc');

    if (empty($params['page'])) {
        $params['page'] = 1;
    }

    if (empty($params['client_type'])) {
        $params['client_type'] = 'U';
    }

    if (empty($params['limit'])) {
        $params['limit'] = Registry::get('settings.Appearance.admin_elements_per_page');
    }

    $where .= db_quote(" AND client_type = ?s" . (!empty($condition) ? " AND $condition" : ''), $params['client_type']);

    if (!empty($extra_join)) {
        $join = fn_array_merge($join, $extra_join);
    }

    $join['?:stat_browsers'] = "?:stat_sessions.browser_id = ?:stat_browsers.browser_id";
    $join['?:stat_ips'] = "?:stat_sessions.ip_id = ?:stat_ips.ip_id";
    $join['?:stat_search_phrases'] = "?:stat_sessions.phrase_id = ?:stat_search_phrases.phrase_id";
    $join['?:stat_languages'] = "?:stat_sessions.client_language = ?:stat_languages.lang_code";
    $join['?:country_descriptions'] = db_quote("?:stat_ips.country_code = ?:country_descriptions.code AND ?:country_descriptions.lang_code = ?s", CART_LANGUAGE);

    $company_fields = '';
    if (fn_allowed_for('ULTIMATE')) {
        $join['?:companies'] = "?:stat_sessions.company_id = ?:companies.company_id";
        $company_fields = ', ?:companies.storefront';
    }

    $filter_condition = fn_bild_sql_join($join);

    if ($company_condition = fn_get_ult_company_condition('?:stat_sessions.company_id')) {
        $where .= $company_condition;
    }

    $total = db_get_field("SELECT COUNT(distinct ?:stat_sessions.sess_id) FROM ?:stat_sessions $filter_condition WHERE $where");
    $limit = db_quote(' LIMIT ?i', $params['limit']);
    $data = db_get_hash_array(
            "SELECT ?:stat_sessions.*, ?:stat_browsers.browser, ?:stat_browsers.version as browser_version,"
            . " ?:stat_ips.*, phrase, ?:stat_languages.language, ?:country_descriptions.country $company_fields"
            . " FROM ?:stat_sessions $filter_condition"
            . " WHERE $where"
            . " GROUP BY ?:stat_sessions.sess_id "
            . (empty($sorting) ? '' : $sorting) ." $limit",
            'sess_id'
        );

    if (!empty($data)) {
        $sess_ids = array_keys($data);
        $entity_data = db_get_hash_array("SELECT sess_id, url, title, https FROM ?:stat_requests WHERE sess_id IN (?n) AND (request_type & " . STAT_ORDINARY_REQUEST . ") = " . STAT_FIRST_REQUEST . " GROUP BY sess_id ORDER BY timestamp ASC", 'sess_id', $sess_ids);

        $current_data = db_get_hash_array("SELECT sess_id, url, title, https FROM ?:stat_requests WHERE sess_id IN (?n) AND (request_type & " . STAT_LAST_REQUEST . ") = " . STAT_LAST_REQUEST . " AND ?:stat_requests.timestamp >= ?i GROUP BY sess_id ORDER BY timestamp ASC", 'sess_id', $sess_ids, (TIME - SESSION_ONLINE));
        $requests_count = db_get_hash_array("SELECT sess_id, COUNT(*) AS count FROM ?:stat_requests WHERE sess_id IN (?n) GROUP BY sess_id ORDER BY timestamp ASC", 'sess_id', $sess_ids);

        foreach ($data as $sess_id => $v) {
            if (isset($entity_data[$sess_id])) {
                $data[$sess_id]['url'] = fn_stat_get_visitor_url($v, $entity_data[$sess_id]);
                $data[$sess_id]['title'] = $entity_data[$sess_id]['title'];
            }

            if (isset($current_data[$sess_id])) {
                $data[$sess_id]['current_url'] = fn_stat_get_visitor_url($v, $current_data[$sess_id]);
                $data[$sess_id]['current_title'] = $current_data[$sess_id]['title'];
            }

            if (isset($requests_count[$sess_id])) {
                $data[$sess_id]['requests_count'] = $requests_count[$sess_id]['count'];
            }

            $data[$sess_id]['host_ip'] = (empty($data[$sess_id]['host_ip']) || $data[$sess_id]['host_ip'] <= 0) ? '-' : long2ip($data[$sess_id]['host_ip']);
            $data[$sess_id]['proxy_ip'] = empty($data[$sess_id]['proxy_ip']) ? '-' : long2ip($data[$sess_id]['proxy_ip']);
        }
    }

    return array($data, $params);
}

function fn_stat_get_visitor_url($data, $url_data)
{
    if (fn_allowed_for('ULTIMATE') && !empty($data['storefront'])) {
        return (($url_data['https'] == 'Y') ? 'https://' : 'http://') . $data['storefront'] . $url_data['url'];
    }

    return (($url_data['https'] == 'Y') ? Registry::get('config.https_location') : Registry::get('config.http_location')) . $url_data['url'];
}

//
// Generate information about products search parameters
//
function fn_stat_product_search_data($search_params, $text_conditions = array())
{
    $text_conditions['find_results_with'] = (empty($search_params['q']) ? '- '. __('empty') .' -' : $search_params['q']). ' ['. ($search_params['match'] == 'exact' ? __('exact_phrase') : ($search_params['match'] == 'all' ? __('all_words') : __('any_words'))) .']';

    $_data = array();
    if (!empty($search_params['pname'])) {
        $_data[] = __('product_name');
    }
    if (!empty($search_params['pshort'])) {
        $_data[] = __('short_description');
    }
    if (!empty($search_params['pfull'])) {
        $_data[] = __('full_description');
    }
    if (!empty($search_params['pkeywords'])) {
        $_data[] = __('keywords');
    }
    if (!empty($_data)) {
        $text_conditions['search_in'] = implode(', ', $_data);
    }

    if (!empty($search_params['feature'])) {
        list($product_features) = fn_get_product_features();
        $_data = array();
        foreach ($search_params['feature'] as $feature_id) {
            $_data[] = $product_features[$feature_id]['description'];
        }
        if (!empty($_data)) {
            $text_conditions['search_by_product_features'] = implode(', ', $_data);
        }
    }

    if (!empty($search_params['cid'])) {
        $text_conditions['search_in_category'] = fn_get_category_name($search_params['cid'], CART_LANGUAGE) . (empty($search_params['subcats']) ? '' : ' ['. __('search_in_subcategories') .']');
    }

    if (!empty($search_params['pcode'])) {
        $text_conditions['search_by_sku'] = $search_params['pcode'];
    }

    if (!empty($search_params['price_from']) || !empty($search_params['price_to'])) {
        $text_conditions['search_by_price'] = fn_format_price($search_params['price_from']) .' - '. fn_format_price($search_params['price_to']);
    }

    if (!empty($search_params['weight_from']) || !empty($search_params['weight_to'])) {
        $text_conditions['search_by_weight'] = (empty($search_params['weight_from']) ? '0' : $search_params['weight_from']) .' - '. (empty($search_params['weight_to']) ? '0' : $search_params['weight_to']) . " ({Registry::get('settings.General.weight_symbol')})";
    }

    fn_set_hook('stat_product_search_data', $search_params, $text_conditions);

    return $text_conditions;
}

function fn_stat_build_query($params, $override)
{
    $params = fn_array_merge($params, $override);
    unset($params['selected_section']);

    return http_build_query($params);
}

function fn_stat_get_requests($params, $items_per_page = 0)
{
    // Set default values to input params
    $default_params = array (
        'page' => 1,
        'items_per_page' => $items_per_page
    );

    $params = array_merge($default_params, $params);

    $company_condition = fn_get_ult_company_condition('?:stat_requests.company_id');

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:stat_requests WHERE sess_id = ?i $company_condition ORDER BY req_id", $params['stat_sess_id']);
        $limit = db_paginate($params['page'], $params['items_per_page']);
    }

    $requests = db_get_array("SELECT req_id, url, title, timestamp, https FROM ?:stat_requests WHERE sess_id = ?i $company_condition ORDER BY req_id DESC $limit", $params['stat_sess_id']);

    $company_data = array();
    if (fn_allowed_for('ULTIMATE')) {
        $company_data['storefront'] = db_get_field (
                "SELECT storefront"
                . " FROM ?:stat_sessions, ?:companies"
                . " WHERE ?:stat_sessions.company_id = ?:companies.company_id"
                . " AND ?:stat_sessions.sess_id = ?i",
                $_REQUEST['stat_sess_id']
        );
    }

    foreach ($requests as $k => $v) {
        $requests[$k]['storefront_url'] = fn_stat_get_visitor_url($company_data, $v);
    }

    return array($requests, $params);
}

function fn_stat_get_general_reports($params, $items_per_page = 0)
{
    // Set default values to input params
    $default_params = array (
        'page' => 1,
        'items_per_page' => $items_per_page
    );

    $params = array_merge($default_params, $params);

    // Get filter information
    list($where, $join, $params) = fn_stat_filter_data($params);

    $filter_condition = fn_bild_sql_join($join);

    $group_date = 'timestamp';
    $_period = (in_array($params['period'], array('D', 'LD', 'HH'))) ? STAT_PERIOD_HOUR : STAT_PERIOD_DAY;
    if ($_period == STAT_PERIOD_HOUR) {
        $group_date = "DATE_FORMAT(FROM_UNIXTIME(?:stat_sessions.timestamp), '%Y-%m-%d %H:00')";
    } elseif ($_period == STAT_PERIOD_MONTH) {
        $group_date = "DATE_FORMAT(FROM_UNIXTIME(?:stat_sessions.timestamp), '%Y-%m-01')";
    } else {
        $_period = STAT_PERIOD_DAY;
        $group_date = "DATE_FORMAT(FROM_UNIXTIME(?:stat_sessions.timestamp), '%Y-%m-%d')"; // Day
    }

    if ($company_condition = fn_get_ult_company_condition('?:stat_sessions.company_id')) {
        $where = empty($where) ? '1' : $where;
        $where .= $company_condition;
    }

    if (!empty($where)) {
        $where = "WHERE $where";
    }

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(DISTINCT $group_date) FROM ?:stat_sessions $filter_condition $where");
        $limit = db_paginate($params['page'], $params['items_per_page']);
    }

    $data = array();
    $_data = db_get_hash_array("SELECT $group_date as date, COUNT(*) as total FROM ?:stat_sessions $filter_condition $where GROUP BY date ORDER BY date DESC $limit", 'date');
    $_where = (empty($where) ? 'WHERE ' : $where . ' AND ') . "client_type = 'U'";
    $__data = db_get_hash_array("SELECT $group_date as date, COUNT(*) as visitors, COUNT(DISTINCT host_ip) as hosts FROM ?:stat_sessions $filter_condition $_where GROUP BY date ORDER BY date DESC $limit", 'date');

    foreach ($_data as $_k => $_v) {
        $_data[$_k]['visitors'] = empty($__data[$_k]['visitors']) ? '0' : $__data[$_k]['visitors'];
        $_data[$_k]['hosts'] = empty($__data[$_k]['hosts']) ? '0' : $__data[$_k]['hosts'];
        $_data[$_k]['robots'] = !empty($_data[$_k]['total']) ? ($_data[$_k]['total'] - $_data[$_k]['visitors']) : 0;
        $_data[$_k]['time_from'] = strtotime($_k);
    }

    $data['data'] = $_data;

    return array($data, $params, $_period);
}
