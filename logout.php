<?php
require_once('access.php');
require_once('dao.php');
require_once('utils.php');
require_once('email.php');

$dao = new DAO($opts['hn'], $opts['db'], $opts['un'], $opts['pw']);

$dao->logout();

header('Location: .');
