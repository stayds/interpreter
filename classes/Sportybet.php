<?php
include_once './interfaces/BookmakerInterface.php';
/**
 * Sportybet Class interprete Sportybet code to games and outcome  and return response in json format
 */
class Sportybet implements BookmakerInterface {

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
     * @return json
     */
    public function callBookMaker($code) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://www.sportybet.com/api/ng/orders/share/{$code}?_t=1584963320556",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:74.0) Gecko/20100101 Firefox/74.0",
                "Accept: */*,",
                "Accept-Language: en-US,en;q=0.5,",
                "Content-Type: application/x-www-form-urlencoded; charset=UTF-8,",
                "OperId: 2,",
                "platform: web,",
                "ClientId: web"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    /**
     * Method call to parse the response from the bookmaker API
     * @param $response json that stores the response from homebookmaker call
     * @param $homebookmaker string that stores the home or intiating bookmaker name
     * @param $awaybookmaker string that stores the away or destination bookmaker name
     * @return json
     */
    public function responseParser($response, $homebookmaker, $awaybookmaker) {
        // TODO implement here
        return $response;
    }

}