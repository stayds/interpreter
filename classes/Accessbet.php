<?php
include_once './interfaces/BookmakerInterface.php';
/**
 * Accessbet Class interprete Accessbet code to games and outcome  and return response in json format
 */
class Accessbet implements BookmakerInterface {

    /**
     * @param $url
     */
    public $url;

    /**
     * Default constructor
     */
    public function __construct() {
    }

    /**
     * Method call to bookmaker API
     * @param $code booked game code from the homebookmaker
     */
    public function callBookMaker($code) {
        // TODO implement here
    }

    /**
     * Method call to parse the response from the bookmaker API
     * @param $response variable that stores the response from homebookmaker call
     * @param $homebookmaker variable that stores the home or intiating bookmaker name
     * @param $awaybookmaker variable that stores the away or destination bookmaker name
     */
    public function responseParser($response, $homebookmaker, $awaybookmaker) {
        // TODO implement here
    }

}