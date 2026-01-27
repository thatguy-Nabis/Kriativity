<?php
function printer($val){
    echo "<pre>";
    print_r($val);
    echo "</pre>";
    die();
}
function printer_json($val){
    header('Content-Type: application/json');
    echo json_encode($val);
    exit;
}

?>