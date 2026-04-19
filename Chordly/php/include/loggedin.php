<?php
if (!isset($_SESSION['userId'])) {
    header('Location: /CHORDLY/php/userpages/userLoginpage.php');
    exit;
}