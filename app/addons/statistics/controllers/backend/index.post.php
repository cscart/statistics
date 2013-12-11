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

if (fn_check_view_permissions('statistics.reports', 'GET')) {
    $time_from = !empty($_REQUEST['time_from']) ? $_REQUEST['time_from'] : strtotime('-30 day');
    $time_to = !empty($_REQUEST['time_to']) ? $_REQUEST['time_to'] : strtotime('now');
    $time_difference = $time_to - $time_from;

    $visitors = fn_stat_get_visitors_count($time_from, $time_to);
    $prev_visitors = fn_stat_get_visitors_count($time_from - $time_difference, $time_to - $time_difference);

    if ($prev_visitors > 0) {
        $diff = ($visitors * 100) / $prev_visitors;
        $diff = number_format($diff, 2);
    } else {
        $diff = '&infin;';
    }

    $search_terms = fn_get_statistic_search_terms(STAT_DASHBOARD_LIMIT_TERMS);

    Registry::get('view')->assign('visitors', array(
        'total' => $visitors,
        'prev_total' => $prev_visitors,
        'diff' => $diff,
    ));
    Registry::get('view')->assign('search_terms', $search_terms);
}
