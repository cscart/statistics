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

use Tygh\Session;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'collect') {

    if (empty($_REQUEST['ve'])) {
        exit;
    }

    $ve = $_REQUEST['ve'];

    if (empty($ve['url'])) {
        exit;
    }

    $ve['timestamp'] = TIME;
    $ve['url'] = fn_stat_prepare_url($ve['url']);
    $ve['https'] = defined('HTTPS') ? 'Y' : 'N';

    // Get current statistics session ID
    $sess_id = db_get_field("SELECT sess_id FROM ?:stat_sessions WHERE session = ?s AND expiry > ?i ORDER BY timestamp DESC LIMIT 1", Session::getId(), TIME);

    if (empty($sess_id)) {
        $sess_id = fn_stat_save_session_data($ve);
        $ve['request_type'] = STAT_LAST_REQUEST;

    } else {
        db_query("UPDATE ?:stat_sessions SET expiry = ?i WHERE sess_id = ?s", TIME + SESSION_ALIVE_TIME, $sess_id);
        $last_url = db_get_field("SELECT url FROM ?:stat_requests WHERE sess_id = ?i AND (request_type & ?i) = ?i", $sess_id, STAT_LAST_REQUEST, STAT_LAST_REQUEST);

        // Check if user refreshed the page.
        if ($last_url == $ve['url']) {
            exit;
        }

        db_query("UPDATE ?:stat_requests SET request_type = request_type & ". STAT_ORDINARY_REQUEST ." WHERE sess_id = ?s", $sess_id);
        $ve['request_type'] = STAT_END_REQUEST;
    }

    // Save request data
    $ve['sess_id'] = $sess_id;
    $ve['loadtime'] = empty($ve['time_begin']) ? 0 : MICROTIME - $ve['time_begin'];
    if ($ve['loadtime'] < 0) {
        $ve['loadtime'] = 0;
    }
    fn_set_data_company_id($ve);

    db_query("INSERT INTO ?:stat_requests ?e", $ve);
}
exit;

//
// The function extructs search words from url.
//
function fn_get_search_words($url = '')
{
    $search_engines = Registry::get('search_engines');
    $query_fields = array('q', 'p', 'query', 'qwederr', 'qs');

    if (empty ($url)) {
        return false;
    }

    $parse_url = parse_url($url);
    $parse_url['host'] = fn_strtolower(@$parse_url['host']);
    parse_str(@$parse_url['query'], $parse_query);

    $found_engine = false;
    $words = array();

    if (!empty($parse_url['host'])) {
        foreach ($search_engines as $engine_name => $engine) {
            if (is_array($engine['hosts'])) {
                $engine_hosts = $engine['hosts'];
            } else {
                $engine_hosts = array($engine['hosts']);
            }
            foreach ($engine_hosts as $host) {
                $host = fn_strtolower($host);

                $host_pos = strpos($parse_url['host'], $host);
                if ($host_pos !== false && !empty($engine['key_word'])) {
                    $host_pos = strpos($parse_url['host'] . $parse_url['path'], $engine['key_word']);
                }
                if ($host_pos !== false) { // host referrer match
                    $found_engine = $engine_name;
                    if (is_array($engine['qfield'])) {
                        $qfields = $engine['qfield'];
                    } else {
                        $qfields = array($engine['qfield']);
                    }
                    foreach ($qfields as $q) {
                        if (isset($parse_query[$q])) {
                            $words = urldecode($parse_query[$q]);
                            if (!empty($engine['conv_func'])) {
                                $words = call_user_func($engine['conv_func'], $words, $engine['charset']);
                            }
                            break;
                        }
                    }
                }
            }
        }

        if (!$found_engine) {
            $found_engine = $parse_url['host'];
            foreach ($query_fields as $field) {
                if (isset ($parse_query[$field])) {
                    $words = urldecode($parse_query[$field]);
                }
            }
        }
    }

    return array('engine' => $found_engine, 'phrase' => $words);
}

//
// Save session data.
//
function fn_stat_save_session_data(&$stat_data)
{
    $stat_data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $ip = fn_get_ip(true);
    $stat_data['host_ip'] = $ip['host'];
    $stat_data['proxy_ip'] = $ip['proxy'];
    $stat_data['client_language'] = empty($stat_data['client_language']) ? (empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? '' : $_SERVER['HTTP_ACCEPT_LANGUAGE']) : $stat_data['client_language'];
    $stat_data['session'] = Session::getId();

    $stat_data['host_ip'] = $ip['host'];
    $stat_data['proxy_ip'] = $ip['proxy'];
    $stat_data['ip_id'] = fn_stat_ip_exist($ip);

    if (!empty($stat_data['browser'])) {
        $stat_data['browser_version'] = !empty($stat_data['browser_version']) ? $stat_data['browser_version'] : '';
        $browser_id = db_get_field("SELECT browser_id FROM ?:stat_browsers WHERE browser = ?s AND version = ?s", $stat_data['browser'], $stat_data['browser_version']);
        if (empty($browser_id)) {
            $browser_id = db_query('INSERT INTO ?:stat_browsers ?e', array('browser' => $stat_data['browser'], 'version' => $stat_data['browser_version']));
        }
        $stat_data['browser_id'] = $browser_id;
    }

    $parse_url = parse_url(@$stat_data['referrer']);
    $stat_data['referrer_scheme'] = empty($parse_url['scheme']) ? '' : $parse_url['scheme'];
    $stat_data['referrer_host'] = empty($parse_url['host']) ? '' : $parse_url['host'];

    $search_data = fn_get_search_words(@$stat_data['referrer']);
    if (!empty($search_data['engine'])) {
        //$stat_data['engine'] = $search_data['engine'];
        $engine_id = db_get_field("SELECT engine_id FROM ?:stat_search_engines WHERE engine = ?s", $search_data['engine']);
        if (empty($engine_id)) {
            $engine_id = db_query('INSERT INTO ?:stat_search_engines ?e', array('engine' => $search_data['engine']));
        }
        $stat_data['engine_id'] = empty($engine_id) ? 0 : $engine_id;
    }

    if (!empty($search_data['phrase'])) {
        $phrase_id = db_get_field("SELECT phrase_id FROM ?:stat_search_phrases WHERE phrase = ?s", $search_data['phrase']);
        if (empty($phrase_id)) {
            $phrase_id = db_query('INSERT INTO ?:stat_search_phrases ?e', array('phrase' => $search_data['phrase']));
        }
        $stat_data['phrase_id'] = empty($phrase_id) ? 0 : $phrase_id;
    }

    if (!empty($stat_data['client_language'])) {
        $is_lang = db_get_field("SELECT lang_code FROM ?:stat_languages WHERE lang_code = ?s", $stat_data['client_language']);
        // If there is not long language code in DB then save short language code
        if (empty($is_lang)) {
            $stat_data['client_language'] = substr($stat_data['client_language'], 0, 2);
        }
    }

    $stat_data['expiry'] = TIME + SESSION_ALIVE_TIME;

    fn_set_data_company_id($stat_data);
    $sess_id = db_query('INSERT INTO ?:stat_sessions ?e', $stat_data);

    // Set the cookie 'stat_uniq_code' to identify unique clients.
    $stat_uniq_code = fn_get_cookie('stat_uniq_code');
    if (!empty($sess_id) && (empty($stat_uniq_code) || $stat_uniq_code >= $sess_id)) {
        $stat_uniq_code = $sess_id;
    }
    fn_set_cookie('stat_uniq_code', $stat_uniq_code, 365 * 24 * 3600);
    if (!empty($sess_id)) {
        db_query('UPDATE ?:stat_sessions SET ?u WHERE sess_id = ?i', array('uniq_code' => $stat_uniq_code), $sess_id);
    }

    return $sess_id;
}

function fn_str_htmlentities($s, $charset)
{
    $_charset = fn_strtolower($charset);
    if ($_charset == 'unicode') {
        $s = fn_unicode_to_utf8($s);
        $charset = 'UTF-8';
    }
    if ($_charset == 'gb2312' || $_charset == '936' || function_exists('mb_convert_encoding') == false) {
        $s = htmlentities($s, ENT_QUOTES, $charset);
    } else {
        $s = mb_convert_encoding($s, 'HTML-ENTITIES', $charset);
    }

    return $s;
}
