<?php

/**
 * Bookmaker Interface defines the rules to be implemented in the bookmaker classes
 */
interface BookmakerInterface {


    /**
     * Method call to bookmaker API
     * @param string $code booked game code from the homebookmaker
     * @return array
     */
    public function callBookMaker($code);

    /**
     * Method call to parse the response from the bookmaker API
     * @param array $response variable that stores the response from homebookmaker call
     * @param string $homebookmaker variable that stores the home or intiating bookmaker name
     * @param string $awaybookmaker variable that stores the away or destination bookmaker name
     * @return array
     */
    public function responseParser($response, $homebookmaker, $awaybookmaker, $code);

}
