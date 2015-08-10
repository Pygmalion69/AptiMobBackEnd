<?php

require("config.php");

$user = "test@test.nl";
$passwd = "test";

// Create a 256 bit (64 characters) long random salt
// Let's add 'something random' and the username
// to the salt as well for added security
$salt = hash('sha256', uniqid(mt_rand(), true) . 'something random' . strtolower($user));

// Prefix the password with the salt
$hash = $salt . $passwd;

// Hash the salted password a bunch of times
for ($i = 0; $i < 100000; $i++) {
    $hash = hash('sha256', $hash);
}

// Prefix the hash with the salt so we can find it back later
$hash = $salt . $hash;

$db = new PDO("mysql:dbname=$db_name;host=$db_host", $db_user, $db_password);

$db->exec("INSERT INTO $table_users (user, hash) VALUES ('$user', '$hash')");
?>
