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

//
// [CONSTANTS]
//
define('STAT_PERIOD_DAY', 1001);
define('STAT_PERIOD_MONTH', 1002);
define('STAT_PERIOD_HOUR', 1003);

define('STAT_HOUR', 3600);

// Request type
define('STAT_FIRST_REQUEST', 3000);
define('STAT_ORDINARY_REQUEST', 3001);
define('STAT_LAST_REQUEST', 3002);
define('STAT_END_REQUEST', 3003);

// It is limit to load data from DB, when we calculate quantity or row
define('STAT_ROW_LIMIT', 100000); // see using function fn_calc_aggregate_functions

define('STAT_DASHBOARD_LIMIT_TERMS', 5);

Registry::set('search_engines', array(
    'Yandex' => array(
        'hosts' => 'yandex',
        'qfield' => 'text',
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.yandex.ru/'),
    'Rambler' => array(
        'hosts' => 'rambler',
        'qfield' => 'words',
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.rambler.ru/'),
    'Google' => array(
        'hosts' => 'www.google',
        'qfield' => 'q',
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.google.com/'),
    'Aport' => array(
        'hosts' => 'aport',
        'qfield' => 'r',
        'charset'=> 'cp1251',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.aport.ru/'),
    'Mail' => array(
        'hosts' => 'mail',
        'key_word' => 'search',
        'qfield' => 'q',
        'charset'=> 'cp1251',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.mail.ru/'),
    'MSN' => array(
        'hosts' => array('search.msn', 'msn.com'),
        'key_word' => 'results',
        'qfield' => 'q',
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.msn.com/'),
    'Live' => array(
        'hosts' => array('search.live', 'live.com'),
        'key_word' => 'results',
        'qfield' => 'q',
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.live.com/'),
    'Yahoo' => array(
        'hosts' => array('yahoo.com', 'search.yahoo'),
        'qfield' => 'p',
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.yahoo.com/'),
    'AllTheWeb' => array(
        'hosts' => 'www.alltheweb',
        'qfield' => 'q',
        'url' => 'http://www.alltheweb.com/'),
    'Looksmart' => array(
        'hosts' => array('looksmart.com', 'search.looksmart'),
        'qfield' => 'qt',
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.looksmart.com/'),
    'Ask' => array(
        'hosts' => array('askjeeves.com', 'ask.com'),
        'qfield' => 'q',
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.ask.com/'),
    'AOL' => array(
        'hosts' => 'aol.com',
        'qfield' => array('query', 'encquery'),
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.aol.com/'),
    'Lycos' => array(
        'hosts' => 'lycos.com',
        'qfield' => 'query',
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.lycos.com/'),
    'AltaVista' => array(
        'hosts' => 'altavista',
        'qfield' => 'q',
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.altavista.com/'),
    'Search' => array(
        'hosts' => 'search.com',
        'qfield' => 'q',
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.search.com/'),
    'Netscape' => array(
        'hosts' => 'netscape.com',
        'qfield' => array('s', 'query'),
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.netscape.com/'),
    'CNN' => array(
        'hosts' => 'cnn.com',
        'qfield' => 'query',
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.cnn.com/SEARCH/'),
    'About' => array(
        'hosts' => 'about.com',
        'qfield' => 'terms',
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.about.com/fullsearch.htm'),
    'Mamma' => array(
        'hosts' => 'mamma.com',
        'qfield' => 'query',
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.mamma.com/'),
    'Gigablast' => array(
        'hosts' => 'gigablast.com',
        'qfield' => 'q',
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.gigablast.com/'),
    'Voila' => array(
        'hosts' => 'voila.fr',
        'qfield' => array('kw', 'rdata'),
        'charset'=> 'unicode',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.voila.fr/'),
    'Virgilio' => array(
        'hosts' => 'alice.it',
        'qfield' => array('qs'),
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.virgilio.it/'),
    'Baidu' => array(
        'hosts' => 'baidu.com',
        'qfield' => array('wd'),
        'charset'=> 'EUC-JP',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.baidu.com/'),
    'Seznam' => array(
        'hosts' => 'seznam.cz',
        'qfield' => array('w'),
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.seznam.cz/'),
    'Najdi' => array(
        'hosts' => 'najdi.si',
        'qfield' => array('q'),
        'charset'=> 'UTF-8',
        'conv_func' => 'fn_str_htmlentities',
        'url' => 'http://www.najdi.si/'),
));
