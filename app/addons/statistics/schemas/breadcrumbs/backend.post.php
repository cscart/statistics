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

include_once(Registry::get('config.dir.addons') . 'statistics/schemas/breadcrumbs/backend.functions.php');

$schema['statistics.visitors'] = array (
    array(
        'function' => array('fn_br_statistics_visitors_link', '@section', '@report')
    ),
);
$schema['statistics.visitor_pages'] = array (
    array(
        'function' => array('fn_br_statistics_visitors_page_link', '@client_type', '@return_url')
    ),
);

return $schema;
