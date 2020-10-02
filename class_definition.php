<?php

interface bookmakerInterface
{
    public function callBookMaker($code, $url);
    public function responseParser($response, $homebookmaker, $awaybookmaker);
}


class Betking implements bookmakerInterface
{
    public function callBookMaker($code, $url)
    {
        return "Use given code to call bookmaker API";
    }

    public function responseParser($response, $homebookmaker, $awaybookmaker)
    {
        return "Betking json generic definition";
    }
}

class Onexbet implements bookmakerInterface
{
    public function callBookMaker($code, $url)
    {
        return "Use given code to call bookmaker API";
    }

    public function responseParser($response, $homebookmaker, $awaybookmaker)
    {
        return "Onexbet json generic definition";
    }
}

class Sportybet implements bookmakerInterface
{
    public function callBookMaker($code, $url)
    {
        return "Use given code to call bookmaker API";
    }

    public function responseParser($response, $homebookmaker, $awaybookmaker)
    {
        return "Sportybet json generic definition";
    }
}

class Interpreter
{
    public $code;
    public $homebookmaker;
    public $awaybookmaker;
    public $url;
    public $response;

    public function __construct($code, $homeBookmaker,$awayBookmaker, $url)
    {
        $this->code = $code;
        $this->homebookmaker = $homeBookmaker;
        $this->awaybookmaker = $awayBookmaker;
        $this->url = $url;
    }
    public function interprete(bookmakerInterface $homeBookmaker)
    {
        $this->response = $homeBookmaker->callBookMaker($this->code, $this->url);
        return $homeBookmaker->responseParser($this->response, $this->homebookmaker, $this->awaybookmaker);
    }
}

$code = "adadfa";
$homeBookmaker = "Onexbet";
$awayBookmaker = "sportybet";
$url = "";

if(class_exists($homeBookmaker)){
    $homeBokmaker = new $homeBookmaker();
    $interpreteCode = new Interpreter($code, $homeBookmaker,$awayBookmaker, $url);

    echo $interpreteCode->interprete($homeBokmaker);
}else{
    echo "bookmaker does not exist";
}
