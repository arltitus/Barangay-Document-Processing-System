<?php
define('BDPS_SYSTEM', true);
require_once 'includes/init.php';

Auth::getInstance()->logout();
header('Location: index.php');
