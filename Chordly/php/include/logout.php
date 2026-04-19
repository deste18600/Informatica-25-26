<?php
session_start();
$_SESSION = array();
session_destroy();
header('Location: /CHORDLY/php/userpages/welcomePage.php');
exit;