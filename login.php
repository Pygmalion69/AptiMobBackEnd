<?php

require("config.php");

//if ($_POST[lang] == "nl") 
//{
require_once("language_nl.php");
//}

define("SUCCESSFUL_LOGIN", 0);
define("REGISTER_NEW", 1);

$db = new PDO("mysql:dbname=$db_name;host=$db_host", $db_user, $db_password);

function validate_credentials($user, $passwd) {

    global $db;
    global $table_users;

    $r = $db->query("SELECT username, hash FROM `$table_users` WHERE username = '$user'");
    if (isset($r))
        $userRow = $r->fetch();
    else
        return false;

    // The first 64 characters of the hash is the salt
    $salt = substr($userRow['hash'], 0, 64);

    $hash = $salt . $passwd;

    // Hash the password as we did before
    for ($i = 0; $i < 100000; $i++) {
        $hash = hash('sha256', $hash);
    }

    $hash = $salt . $hash;

    if ($hash == $userRow['hash']) {
        // Ok!
        return true;
    } else {
        return false;
    }
}

function user_exists($user) {

    global $db;
    global $table_users;

    $r = $db->query("SELECT user FROM `$table_users` WHERE user = '$user'");
    if (isset($r))
        return true;
    else
        return false;
}

$logfile = "log.txt";
$fh = fopen($logfile, 'a') or die("can't open file");
$date = new DateTime();
if (!validate_credentials($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="AptiMob"');
    header('HTTP/1.0 401 Unauthorized');
    echo AUTHORIZATION_NEEDED;
    fwrite($fh, $date->format('Y-m-d H:i:s') . " Login failed for user ${_SERVER['PHP_AUTH_USER']}\n");
    exit;
} else {
    fwrite($fh, $date->format('Y-m-d H:i:s') . " Login succeeded for user ${_SERVER['PHP_AUTH_USER']}\n");
}
fclose($fh);
?>