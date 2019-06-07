<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['allow_concurrent_session'] = false;

$config['attempt_expired'] = "+30 days";
$config['session_remember'] = "+30 days";
$config['session_forgot'] = "+1 day";
$config['session_renew'] = "+10 minutes";

$config['cookie_name'] = "cibp_session";
$config['cookie_domain'] = "localhost";
$config['cookie_path'] = "/";
$config['cookie_secure'] = false;
$config['cookie_httponly'] = true;

$config['max_login_attempt'] = 3;
