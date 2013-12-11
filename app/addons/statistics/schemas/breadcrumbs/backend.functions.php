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

/**
 * Gets statistics page link
 *
 * @param int $client_type log client tipe
 * @param string $return_url Return url
 * @return array Breadcrumb link data
 */
function fn_br_statistics_visitors_page_link($client_type, $return_url)
{
    $result = array(
        'title' => __(($client_type == 'B' ? 'robots' : 'visitors') . '_log'),
        'link' => $return_url
    );

    return $result;
}

/**
 * Gets statistics link
 *
 * @param string $section Statistics section
 * @param string $report Report type
 * @return array Breadcrumb link data
 */
function fn_br_statistics_visitors_link($section, $report)
{
    $result = array();

    if (!empty($section)) {
        $result = array(
            'title' => __($section),
            'link' => "statistics.reports?reports_group=$section&report=$report"
       );
    }

    return $result;
}
