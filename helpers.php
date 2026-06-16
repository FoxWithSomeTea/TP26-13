<?php
// Vygeneruje náhodné heslo (8-12 znaků, a-z, A-Z, 0-9)
function generatePassword() {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $len = rand(8, 12);
    $password = "";
    for ($i = 0; $i < $len; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}


