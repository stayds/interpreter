<?php
include_once './interfaces/BookmakerInterface.php';
include_once 'sportybet-files/sportybet-outcomes.php';
include_once './classes/StdClubChangerClass.php';

/**
 * Sportybet Class interprete Sportybet code to games and outcome  and return response in json format
 */
class Sportybet implements BookmakerInterface
{

    /**
     * @param $url
     */
    public $url;

    /**
     * Default constructor
     */
    public function __construct()
    {
    }

    /**
     * Method call to bookmaker API
     * @param $code booked game code from the homebookmaker
     * @return json
     */
    public function callBookMaker($code)
    {
        $curl = curl_init();
        $time = time();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://www.sportybet.com/api/ng/orders/share/{$code}?_t=$time",
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
    public function responseParser($response, $homebookmaker, $awaybookmaker, $code)
    {

        $data = json_decode($response);
        $data = $data->data->outcomes;
        // return $data;

        $cnt = count($data);
        for ($x = 0; $x < $cnt; $x++) {

            if(!isset($data[$x]->markets[0]->specifier)){
                $desc = $data[$x]->markets[0]->desc;
                $ovalue = "null";
            }else{
                $split = explode("=", $data[$x]->markets[0]->specifier);
                $desc = $data[$x]->markets[0]->desc."_".$split[1];
                $ovalue = $split[1];
            }

            $league = $data[$x]->sport->category->tournament->name;
            $otypeArr = $data[$x]->markets[0]->desc;
            if(strpos($otypeArr, '1X2') == false){
                /*if (strpos($otypeArr, ' ') !== false) {
                    $otype = $this->clean($otypeArr);
                }else{
                    $otype = $data[$x]->markets[0]->desc;
                }*/
                $otype = $data[$x]->markets[0]->desc;
            }else{
                $otype = $data[$x]->markets[0]->desc;
            }

            $homeclub = trim($data[$x]->homeTeamName);
            $awayclub = trim($data[$x]->awayTeamName);
            /**
             * the deleting
             */
//            $clubnames = $this->getClubNames($homebookmaker, $awaybookmaker, $homeclub, $awayclub, $code);

//            if(!isset($club_json['error'])){
//                $club_json = json_decode($clubnames, true);
//                $hometeam = trim($club_json['homeclub']);
//                $awayteam = trim($club_json['awayclub']);
//            }else{
            $hometeam = trim($data[$x]->homeTeamName);
            $awayteam = trim($data[$x]->awayTeamName);
//            }

            $country = $data[$x]->sport->category->name;
            $leagueName = $data[$x]->sport->category->tournament->name;
            $league = $country.". ".$leagueName;

            /*var_dump($data[$x]->markets[0]->outcomes[0]->id);
            exit;*/

            // var_dump($otype); exit;
            /**
             *delete this too
             */
//            $type = strtolower($this->getStdName($homebookmaker,$awaybookmaker,$otype, $code));
            $gamesplayed[$x] = array('home' => $hometeam, 'away' => $awayteam, 'type' =>
                $otype, 'outcome' => strtolower($GLOBALS["outcomes"][$desc][$data[$x]->markets[0]->outcomes[0]->id]), 'ovalue' => $ovalue, 'league' => $league, "bmbtype" => $data[$x]->markets[0]->desc, "league" => $league);

            /*$gamesplayed[$x] = array('home' => $data[$x]->homeTeamName, 'away' => $data[$x]->awayTeamName, 'type' =>
                $data[$x]->markets[0]->desc, 'outcome' => $this->formatOutcome($data[$x]->markets[0]->outcomes[0]->desc), 'ovalue' => $ovalue, 'league' => $league);*/
        }


        $output = [
            "$homebookmaker" => ["$awaybookmaker" => $gamesplayed
            ]
        ];

//        $outcome = json_encode($output);
        /**
         * added this
         */
        return StdClubChangerClass::changeClubandStd($output ,$code);

        //THIS IS WHERE GOES THROUGH
//        return $outcome;
    }

    public function formatOutcome($outcome)
    {
        if(strpos($outcome, 'Home') !== false){
            $result = str_replace("Home", "1", $outcome);
            preg_match('#\((.*?)\)#', $result, $match);
            if(isset($match[1])){
                $result = "1:".$match[1];
            }else{
                $result = $result;
            }
        }else if(strpos($outcome, 'Draw') !== false){
            $result = str_replace("Draw", "x", $outcome);
            preg_match('#\((.*?)\)#', $result, $match);
            if(isset($match[1])){
                $result = "x:".$match[1];
            }else{
                $result = $result;
            }
        }else if(strpos($outcome, 'Away') !== false){
            $result = str_replace("Away", "2", $outcome);
            preg_match('#\((.*?)\)#', $result, $match);
            if(isset($match[1])){
                $result = "2:".$match[1];
            }else{
                $result = $result;
            }
        }else{
            return $outcome;
        }

        return $result;

    }


    public function clean($string) {
        //$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

        return preg_replace('/[^A-Za-z\ \-]/', '', $string); // Removes special chars.
    }

    public function getStdName($homebookmaker, $awaybookermaker, $type, $code)
    {


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://upload.betconverter.com/system/model/bookmakers.php",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\"homemaker\" : \"$homebookmaker\",\"type\" : \"$type\",\"awaymaker\" : \"$awaybookermaker\", \"code\" : \"$code\"}",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $result = json_decode($response);
        if(isset($result->Error)){
            return $type;
        }else{
            return $result->Code->stdtype;
        }

    }

    public function getClubNames($homebookmaker, $awaybookmaker, $homeclub, $awayclub, $code)
    {


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://upload.betconverter.com/system/model/clubs.php",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "{\"homemaker\" : \"$homebookmaker\", \"awaymaker\" : \"$awaybookmaker\", \"homeclub\" : \"$homeclub\", \"awayclub\"  : \"$awayclub\", \"code\" : \"$code\"}",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;


    }

}