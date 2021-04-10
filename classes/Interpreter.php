<?php

/**
 * Interpreter class handles call to the bookmaker API, convert bet code to games and outcomes
 */
class Interpreter {

    /**
     * booked game code from the homebookmaker
     */
    public $code;

    /**
     * variable that stores the home or intiating bookmaker name
     */
    public $homebookmaker;

    /**
     * variable that stores the away or destination bookmaker name
     */
    public $awaybookmaker;

    /**
     * variable that stores the response from homebookmaker call
     */
    public $response;



    /**
     * Default constructor
     */
    public function __construct($code, $homeBookmaker,$awayBookmaker)
    {
        $this->code = $code;
        $this->homebookmaker = $homeBookmaker;
        $this->awaybookmaker = $awayBookmaker;
    }

    /**
     * The method recieves an instance of a bookmaker interface.
     * Call the bookmaker API and
     * parse the response
     * @param $BookmakerInterface homebookmaker
     * @return array
     */
    public function interPrete(BookmakerInterface $homebookmaker) {
        // TODO implement here
        $this->response = $homebookmaker->callBookMaker($this->code);
        return $homebookmaker->responseParser($this->response, $this->homebookmaker, $this->awaybookmaker, $this->code);
    }

}