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
use Tygh\Session;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// Products search
if ($mode == 'search') {

    // Get all search params
    $search_params = $_REQUEST;
    unset($search_params['dispatch']);
    unset($search_params['page']);
    unset($search_params['result_ids']);
    unset($search_params['x']);
    unset($search_params['y']);
    $search_params['match'] = empty($search_params['match']) ? 'any' : $search_params['match']; // any, all, exact

    foreach ($search_params as $k => $v) {
        if (empty($v)) {
            unset($search_params[$k]);
            continue;
        }
        $search_params[$k] = $v;
    }

    ksort($search_params);
    $search_params = serialize($search_params);
    $md5_search_params = md5($search_params);
    $search = Registry::get('view')->getTemplateVars('search');
    $product_count = !empty($search['total_items']) ? $search['total_items'] : 0;

    // Save search params
    $sess_id = db_get_field("SELECT sess_id FROM ?:stat_sessions WHERE session = ?s AND expiry > ?i ORDER BY timestamp DESC LIMIT 1", Session::getId(), TIME);
    if (!empty($sess_id)) {
        $record_exist = db_get_field("SELECT sess_id FROM ?:stat_product_search WHERE sess_id = ?i AND md5 = ?s", $sess_id, $md5_search_params);
        if (!$record_exist) {
            $_data = array(
                'sess_id' => $sess_id,
                'search_string' => $search_params,
                'md5' => $md5_search_params,
                'quantity' => $product_count
            );
            fn_set_data_company_id($_data);

            db_query('INSERT INTO ?:stat_product_search ?e', $_data);
        }
    }
}
