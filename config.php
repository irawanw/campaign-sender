<?php

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);
set_time_limit(0);

define('API_URL', 'http://crm.bal-idf.com/index.php/api/');
define('API_KEY', 'D9dqvZ5O1iCV1ecAEvGydnb68Fzoe1Ey7WMlgU3W');
//define('API_URL', 'http://svr.bonenvoi.com/trie-version-25/index.php/api/');
//define('API_KEY', '9c09de2c-ecfe-4b3e-9a21-30ccd4e2d84b');
define('SIMULTANEOUS', 1);
define('TIMEFRAME', 14*24*60*60); //(check email for 7 days before)
define('SERVER_IP', file_get_contents("http://ipecho.net/plain"));

//global array for saving the bounce category count
global $bounce_type;			
$bounce_type['blocked'] = 0;
$bounce_type['autoreply'] = 0;
$bounce_type['soft'] = 0;
$bounce_type['hard'] = 0;
$bounce_type['temporary'] = 0;
$bounce_type['generic'] = 0;
$bounce_type['unknown'] = 0;

//global array for saving the bounce reason count
global $bounce_reason;
$bounce_reason['antispam'] = 0;
$bounce_reason['autoreply'] = 0;
$bounce_reason['concurrent'] = 0;
$bounce_reason['content_reject'] = 0;
$bounce_reason['command_reject'] = 0;
$bounce_reason['internal_error'] = 0;
$bounce_reason['defer'] = 0;
$bounce_reason['delayed'] = 0;
$bounce_reason['dns_loop'] = 0;
$bounce_reason['dns_unknown'] = 0;
$bounce_reason['full'] = 0;
$bounce_reason['inactive'] = 0;
$bounce_reason['latin_only'] = 0;
$bounce_reason['other'] = 0;
$bounce_reason['oversize'] = 0;
$bounce_reason['outofoffice'] = 0;
$bounce_reason['unknown'] = 0;
$bounce_reason['unrecognized'] = 0;
$bounce_reason['user_reject'] = 0;
$bounce_reason['warning'] = 0;

global $bounce_detail;
$bounce_detail = '';

global $unsubscribe_detail;
$unsubscribe_detail = '';
