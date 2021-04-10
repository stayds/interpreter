<?php

$Request = json_decode(@file_get_contents('php://input'));
$homeBookmaker = $Request->homebookmaker;
$awayBookmaker = $Request->awaybookmaker;
$code = $Request->code;


include_once "./classes/$homeBookmaker.php";
include_once "./classes/Interpreter.php";

if(class_exists($homeBookmaker)){
    $homeBokmaker = new $homeBookmaker();
    $interpreteCode = new Interpreter($code, $homeBookmaker,$awayBookmaker);

    header('Content-Type: application/json');
    echo $interpreteCode->interPrete($homeBokmaker);
}else{
    echo "bookmaker does not exist";
}
