<?php
include_once './interfaces/BookmakerInterface.php';
/**
 * Onexbet Class interprete Onexbet code to games and outcome  and return response in json format
 */
class Onexbet implements BookmakerInterface {

    /**
     * @param $url
     */
    public $url = "https://1xbet.ng/LiveUtil/GetCoupon";

    /**
     * Default constructor
     */
    public function __construct() {
    }

    /**
     * Method call to bookmaker API
     * @param $code
     * booked game code from the homebookmaker
     */
    public function callBookMaker($code) {

        $curl = curl_init();

        curl_setopt_array($curl,[
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{"Guid":"'.$code.'","Lng":"en","partner":159}',
            CURLOPT_HTTPHEADER => array(
                "Accept:  application/json, text/plain, */*",
                "Accept-Language:  en-US,en;q=0.5",
                "REMOTE_ADDR: ",
                "X-Requested-With:  XMLHttpRequest",
                "Content-Type:  application/json;charset=utf-8",
                "Origin:  https://1xbet.ng",
                "Connection:  keep-alive",
                "referrer: https://1xbet.ng/en",
                "TE:  Trailers"
            ),
        ]);
        $response = curl_exec($curl);
        $data =  json_decode($response, true);
        return (isset($data['Value']['Events'])) ? $data['Value']['Events'] : null;

    }

    /**
     * Method call to parse the response from the bookmaker API
     * @param $response
     * variable that stores the response from homebookmaker call
     * @param $homebookmaker
     * variable that stores the home or intiating bookmaker name
     * @param $awaybookmaker
     * variable that stores the away or destination bookmaker name
     */
    public function responseParser($response, $homebookmaker, $awaybookmaker) {

        $data = [];

        if (!is_null($response)) {
            foreach ($response as $key => $item) {

                $data[$homebookmaker][$awaybookmaker][] = [
                    'sportname' => $item['SportName'],
                    'sportid' => $item['SportId'],
                    'gameid' => $item['GameId'],
                    'league' => $item['Liga'],
                    'home' => $item['Opp1'],
                    'away' => $item['Opp2'],
                    'gametype' => $this->gametype($item['GroupName']),
                    'outcome' => $this->outcome($item['GroupName'], $item['MarketName'], $item['Opp1'], $item['Opp2']),
                    'odd' => $item['Coef'],
                    'ovalue' => ($item['Param'] != 0) ? $item['Param'] : null,
                    'identifier' => '',
                ];
            }

            echo json_encode($data);
        }

        echo "Data not found";

    }

    public function outcome($gametype,$market,$home,$away){

        if($gametype == "1x2"){
            $team = [$home=>'1','draw'=>'x',$away=>'2'];
            if ($market == $home || $market == $away){
                return $team[$market];
            }
            return $team['draw'];
        }
        elseif ($gametype == "Double Chance"){
            $team = [$home=>'1','X'=>'x',$away=>'2'];
            $outcome = '';
            $data = explode('Or', $market);
            foreach ($data as $list){
                $list = trim($list);
                if($team[$list]){
                    $outcome .=$team[$list];
                }
            }
            return $outcome;
        }
        elseif ($gametype == "Total" || $gametype == "Asian Total"){
            $outcome = ['Over'=>'over','Under'=>'under'];
            $data = explode(" ", $market);
            $item = trim($data[1]);
            return $outcome[$item];
        }
        elseif ($gametype == "Both Teams To Score" || $gametype == "Even/Odd" || $gametype == "Red Card"
            || $gametype == "Own Goal" || $gametype == "Penalty Awarded And Sending Off" || $gametype == "Goal After Corner")
        {
            $data = explode("-", $market);
            $item = trim(strtolower($data[count($data)-1]));
            return $item;
        }
        elseif ($gametype == "Handicap" || $gametype == "Asian Handicap" ){
            $team = [$home=>'1','draw'=>'x',$away=>'2'];
            if(strpos($market,$home) || strpos($market,$away)) {
                return $team[$home];
            }
            return $team['away'];
        }
        elseif ($gametype == "Multi Goal"){
            $team = [$home=>'1',$away=>'2'];
            if(strpos($market,$home) || strpos($market,$away)) {
                return $team[$home];
            }
        }
        elseif ($gametype == "European Handicap"){
            $team = [$home=>'1','X'=>'x',$away=>'2'];
            $data = explode(" ", $market);
            return $team[$data[3]];
        }
        elseif ($gametype == "Correct Score (17Way)"){
            $outcome = explode(' ',$market);
            return $outcome[2];
        }
        elseif ($gametype == "Goal In Half"){
            $outcome = ["First Goal in 1st Half"=>'1','First Goal in 2nd Half'=>'2',"No First Goal"=>'no'];
            return $outcome[$market];
        }
//        elseif ($gametype == "HF-FT"){
//            $team = [$home=>'1',$away=>'2'];
//            $combination = ["W1W1" => '11',"X1W1"=>'x1','W1X1'];
//            $data = explode(" ", $market);
//            return $team[$data[3]];
//        }
//        else{
//
//        }

    }

    public function gametype($gametype){

        $types = [
            "1x2"=>"1x2",
            "Handicap"=>"handicap",
            "Asian Handicap"=>"asian handicap",
            "Multi Goal"=>"Multi Goal",
            "European Handicap"=>"European Handicap",
            "Double Chance"=>"dc",
            "Both Teams To Score"=>"bts",
            "Draw in Both halves"=>"Draw in Both halves",
            "Even/Odd"=>"even/odd",
            "Own Goal"=>"Own Goal",
            "Red Card"=>"Red Card",
            "Goal After Corner"=>"Goal After Corner",
            "Goal In Half"=>"Goal In Half",
            "HT-FT"=>"hf/ft",
            "Penalty Awarded And Sending Off"=>"penalty/sending off",
            "Number In The Score"=>"number in score",
            "Total"=>"OU",
            "Asian Total"=>"Asian OU",
            "Correct Score (17Way)"=>"cs",
        ];

        return $types[$gametype];
    }

}