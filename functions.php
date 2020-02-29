<?php

use \Hcode\Model\User;

function formatPrice($vlprice){
    if(is_float((float)$vlprice))
        return number_format($vlprice, 2, ",", ".");
    else
        return 0.00;
}

function debug_to_console($data) {
    $output = $data;
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
}

function checkLogin($inadmin = true){
    return User::checkLogin($inadmin);
}

function getUserName(){
    $user = User::getFromSession();
    return ($user->getdesperson()) ? utf8_decode($user->getdesperson()) : $user->getdeslogin();
}

?>