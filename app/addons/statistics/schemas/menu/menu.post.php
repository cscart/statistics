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

$schema['top']['addons']['items']['statistics'] = array(
    'href' => 'statistics.reports',
    'position' => 300,
    'subitems' => array(
        'general' => array(
            'href' => 'statistics.reports?reports_group=general',
            'position' => 100
        ),
        'system' => array(
            'href' => 'statistics.reports?reports_group=system',
            'position' => 200
        ),
        'geography' => array(
            'href' => 'statistics.reports?reports_group=geography',
            'position' => 300
        ),
        'referrers' => array(
            'href' => 'statistics.reports?reports_group=referrers',
            'position' => 400
        ),
        'pages' => array(
            'href' => 'statistics.reports?reports_group=pages',
            'position' => 500
        ),
        'audience' => array(
            'href' => 'statistics.reports?reports_group=audience',
            'position' => 600
        ),
        'products' => array(
            'href' => 'statistics.reports?reports_group=products',
            'position' => 700
        ),
    )
);
return $schema;
