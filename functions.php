<?php

use \Hcode\Model\User;
use \Hcode\Model\Address;
use \Hcode\Model\Cart;

function formatPrice($vlprice){
    if(!$vlprice > 0) $vlprice = 0;

    return number_format($vlprice, 2, ",", ".");
    
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



function checkFields(array $fields, $zipcode){
    foreach ($fields as $key => $value) {
        if(!isset($_POST[$key]) || $_POST[$key] === ''){
            Address::setMsgError($value);
            if($zipcode) header("Location: /checkout?zipcode=$zipcode");
            else header("Location: /checkout");
            exit;
        }
    }
}

function getCartNrQtd(){
    $cart = Cart::getFromSession();
    $totals = $cart->getProductsTotals();

    return $totals['nrqtd'];
}


function getCartVlSubTotal(){
    $cart = Cart::getFromSession();
    $totals = $cart->getProductsTotals();

    return formatPrice($totals['vlprice']);
}

?>