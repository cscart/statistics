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

Registry::get('view')->assign('online_time', SESSION_ONLINE);
$company_condition = fn_get_ult_company_condition('?:stat_requests.company_id');
Registry::get('view')->assign('users_online', db_get_field("SELECT COUNT(distinct ?:stat_requests.sess_id) FROM ?:stat_requests LEFT JOIN ?:stat_sessions ON ?:stat_requests.sess_id = ?:stat_sessions.sess_id WHERE ?:stat_requests.timestamp >= ?i AND ?:stat_requests.timestamp <= ?i AND ?:stat_sessions.client_type = 'U' $company_condition", (TIME - SESSION_ONLINE), TIME)); // Count active connections in last 5 minutes
