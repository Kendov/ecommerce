<?php

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

?>