<?php

/**
 * Bookmaker Interface defines the rules to be implemented in the bookmaker classes
 */
interface BookmakerInterface {


    /**
     * Method call to bookmaker API
     * @param $code booked game code from the homebookmaker
     */
    public function callBookMaker(string $code):array;

    /**
     * Method call to parse the response from the bookmaker API
     * @param $response variable that stores the response from homebookmaker call
     * @param $homebookmaker variable that stores the home or intiating bookmaker name
     * @param $awaybookmaker variable that stores the away or destination bookmaker name
     */
    public function responseParser(array $response, string $homebookmaker, string $awaybookmaker): array;

}
