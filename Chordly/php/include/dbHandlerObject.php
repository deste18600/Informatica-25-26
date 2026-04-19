<?php
// Create needed objects
require_once 'dbHandler.php';
$dbh = new DBHandler();

// Check if database connection established successfully
if ($dbh->getInstance() === null) {
    die("No database connection");
}
