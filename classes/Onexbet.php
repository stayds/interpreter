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
    public $connect;

    /**
     * Default constructor
     */
    public function __construct() {
        $this->connect = curl_init();
    }

    /**
     * Method call to bookmaker API
     * @param $code
     * booked game code from the homebookmaker
     */
    public function callBookMaker($code) {

        curl_setopt_array($this->connect,[
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
        $response = curl_exec($this->connect);
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

                //Call method the formats the outcome as required
                $outcomes = $this->outcome($item['GroupName'], $item['MarketName'], $item['Opp1'], $item['Opp2'], $item['Param']);

                //Calls method that querries the  Games types API
                $games = ($item['PeriodName'] != "") ? $item['GroupName'].". ".$item['PeriodName'] : $item['GroupName'];

                $gt = $this->gamestypes(strtolower(trim($games)),$homebookmaker,$awaybookmaker);

                //Calls method that queries the Club names API
                $cnames = $this->clubnames($homebookmaker,$awaybookmaker,$item['Opp1'],$item['Opp2']);

                $data[$homebookmaker][$awaybookmaker][] = [
                    'home' => (isset($cnames['error'])) ? $item['Opp1'] : $cnames['homeclub'],
                    'away' => (isset($cnames['error'])) ? $item['Opp2'] : $cnames['awayclub'],
                    'type' => $gt,
                    "bmbtype"=> $games,
                    'outcome' => strtolower($outcomes),
                    'odd' => $item['Coef'],
                    'ovalue' => ($item['Param'] != 0) ? $item['Param'] : null,
                ];
            }

            echo json_encode($data);
        }
    }

    private function outcome($gametype,$market,$home,$away,$param){

        if($gametype == "1x2" || $gametype =="1x2. 1 Half" || $gametype == "1x2. 2 Half"){
            $team = [$home=>'1','draw'=>'x',$away=>'2'];
            if ($market == $home || $market == $away){
                return $team[$market];
            }
            return $team['draw'];
        }
        elseif ($gametype == "Double Chance" || $gametype == "Double Chance. 2 Half" || $gametype=="Double Chance. 1 Half"){
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
        elseif ($gametype == "Total" || $gametype == "Asian Total" || $gametype =="Total. 1 Half" || $gametype =="Total. 2 Half"){
            $data = explode(" ", $market);
            return trim($data[1])."".$param;
        }
        elseif ($gametype == "Total 1" || $gametype =="Total 1. 1 Half" || $gametype =="Total 1. 2 Half" || $gametype == "Total 2"
            || $gametype =="Total 2. 1 Half" || $gametype == "Total 2. 2 Half" || $gametype == "Asian Team Total 1" || $gametype == "Asian Team Total 2"){
            $data = explode(" ", $market);
            $item = trim($data[3]);
            return $item."".$param;
        }
        elseif ($gametype == "Half/Half"){
            $team = [$home => "1","Drawn"=>'x',$away=>"2"];
            $data = explode("/", $market);
            $first =  explode(" ",$data[0])[0];
            $second =  explode(" ",$data[1])[0];
            return $team[$first].":".$team[$second];
        }
        elseif ($gametype == "Even/Odd" || $gametype == "Even/Odd. 2 Half" || $gametype == "Even/Odd. 1 Half" ){
            $data = ["Total Even - Yes"=>"even","Total Even - No"=>"odd" ];
            return $data[$market];
        }
        elseif ($gametype == "Both Teams To Score" || $gametype == "Both Teams To Score. 1 Half" || $gametype == "Red Card"
            || $gametype == "Own Goal" || $gametype == "Multi Goal" || $gametype == "Goal In Both Halves" || $gametype == "Penalty Awarded" ||
            $gametype == "Penalty Awarded And Sending Off" || $gametype == "Goal After Corner" || $gametype == "Draw In At Least One Half"
            || $gametype == "Team 2 To Win Either Half" || $gametype == "Team 1 To Win Either Half" || $gametype == "Both Halves To Be Won By Different Teams"
            || $gametype == "Score Draw" || $gametype == "A Player Scores Two Goals (Brace)" || $gametype == "Draw In Both Halves" || $gametype == "Total Goal Minutes"
            || $gametype == "Team 1 Win To Nil" || $gametype =="Team 2 Win To Nil" || $gametype =="Team 1 To Score A Goal In Both Halves"
            || $gametype =="Team 2 To Score A Goal In Both Halves"
        )
        {
            $data = explode("-", $market);
            $item = trim(strtolower($data[count($data)-1]));
            return $item;
        }
        elseif ($gametype == "Handicap" || $gametype == "Asian Handicap" || $gametype =="Handicap. 1 Half"
            || $gametype == "Handicap. 2 Half" || $gametype == "Asian Handicap. 1 Half" || $gametype == "Asian Handicap. 2 Half" ){

            $team = [$home=>'1','draw'=>'x',$away=>'2'];
            /*
             * this is used to get which team it is (away or home), by getting the
             * first occurrence of the team is the marketname string
            */
            $option = (strpos($market,$home)) ? $team[$home] : $team[$away];

            $param = ($param > 0) ? "+".$param : $param;
            return $option.":".$param;

        }
        elseif ($gametype == "Asian Goal"){
            $team = [$home=>'1',$away=>'2'];
            if(strpos($market,$home) || strpos($market,$away)) {
                return $team[$home];
            }
        }
        elseif ($gametype == "European Handicap"){
            $team = [$home=>'1','X'=>'x',$away=>'2'];
            $data = explode(" ", $market);
            $out = str_replace(")","",str_replace  ("(", "", $data[2]));
            $rec = trim(substr($market,strpos($market,")") + 1));
            return $team[$rec].":".$out;
        }
        elseif ($gametype == "Team 2, Multi Goal" || $gametype =="Team 2, Multi Goal"){
            $team = [$home=>'1',$away=>'2'];
            if(strpos($market,$home) || strpos($market,$away)) {
                return $team[$home];
            }
        }
        elseif ($gametype == "Team Wins"){
            $data = explode(" ", $market);
            $team = [$home=>'1',$away=>'2'];
            return $team[$data[0]];
        }
        elseif ($gametype == "Correct Score (17Way)" || $gametype == "Correct Score (17way). 1 Half" || $gametype =="Correct Score (17way). 2 Half"){
            $outcome = explode(' ',$market);
            return str_replace("-",":",$outcome[2]);
        }
        elseif ($gametype == "To Qualify"){
            $team = [$home=>'1','draw'=>'x',$away=>'2'];
            $outcome = explode('-',$market);
            return $team[trim($outcome[1])];
        }
        elseif ($gametype == "Goal In Half"){
            $outcome = ["First Goal in 1st Half"=>'1','First Goal in 2nd Half'=>'2',"No First Goal"=>'none'];
            return $outcome[$market];
        }
        elseif ($gametype == "Next Goal"){
            $team = [$home=>'1','Neither'=>'neither',$away=>'2'];
            $outcome = explode(' ', $market);
            return $team[$outcome[0]].":".$param;
        }
        elseif ($gametype == "HT-FT"){
            $combination = [
                "W $home W $home" => '1/1',"XW $home"=>'x/1',
                "W $away W $home"=>'2/1',"W $home X"=>'1/x',
                "XX"=>'x/x',"W $away X"=>'2/x',
                "W $home W $away"=>'1/2',"XW $away"=>'x/2',
                "W $away W $away"=>'2/2',"$home/$away X"=>'1/2/x',
                "$away/$home X"=>'2/1/x',
            ];
            $data = explode("HT-FT", $market);
            return $combination[trim($data[1])];
        }
        elseif ($gametype == "Goal Interval - No" || $gametype == "Goal Interval - Yes"){
            $team = [$home=>1,$away=>2];
            $data = explode(" ",$market);
            return $team[$data[0]].":".$data[4]."*".$data[6].":".$data[count($data)-1];
        }
        elseif ($gametype == 'Number In The Score' || $gametype == 'Goal Up To Minute'){
            $data = explode(' ', $market);
            if(isset($data[7]) && is_numeric($data[7])){
                return $data[1].":".$data[7].":".$data[count($data)-1];
            }
            return $data[1].":".$data[count($market)-1];
        }
        elseif ($gametype == "Last Goal"){
            $data = ["$home To Score Last Goal"=>1,"$away To Score Last Goal"=>2, "No One Will Score Last Goal"=>"none"];
            return $data[$market];
        }
        elseif ($gametype == "Team 2, Result + Total" || $gametype == "Team 1, Result + Total"){
            $data = explode('And', $market);

            $team = (strpos(trim($data[0]),$home) === 0 ) ? 1 : 2;
            $newdata = explode("-",$data[1]);
            $ou = (strpos(trim($newdata[0]), ">")) ? "Over" : "Under";
            return $team."/".$ou."".$param.":".trim($newdata[1]);
        }
        elseif ($gametype =="Team 2 Scores In Halves" || $gametype =="Team 1 Scores In Halves"){
            $outcome = [
                "$home - 1st Half > 2nd Half"=>"1h","$home - 1st Half = 2nd Half"=>"e",
                "$home - 1st Half < 2nd Half"=>"2h","$away - 1st Half > 2nd Half"=>"1h",
                "$away - 1st Half = 2nd Half"=>"e","$away - 1st Half < 2nd Half"=>"2h"
            ];
            return $outcome[$market];
        }
        elseif ($gametype == "Team 1 To Win Both Halves" || $gametype == "Team 2 To Win Both Halves"){
            $outcome = [
                "$home To Win Both Halves - Yes"=>"yes","$home To Win Both Halves - No"=>"no",
                "$away To Win Both Halves - Yes"=>"yes","$away To Win Both Halves - No"=>"no"
            ];
            return $outcome[$market];
        }
        elseif($gametype == "Draw + Total" || $gametype == "Draw + Total. 2 Half" || $gametype =="Draw + Total. 1 Half"){
            $data = explode(' ', $market);
            $ou = ($data[3] === ">") ? "Over" : "Under";
            return $ou."".$param.":".$data[6];
        }
        elseif($gametype == "Double Chance + Total" || $gametype == "Double Chance + Total. 1 Half" || $gametype == "Double Chance + Total. 2 Half"){
            $teams = [$home => 1, $away => 2, 'X'=>'x', "W $home"=> 1, "W $away"=>2];
            $data = explode('+', $market);
            $datr = explode('or', $data[0]);
            $end = count($datr);
            $outcome = '';
            $ou = (strpos($data[1], "Yes") > 0) ? "yes" : "no";

            foreach($datr as $key => $list){
                ++$key;
                if($key === $end){
                    $outcome .=$teams[trim($list)];
                    break;
                }
                $outcome .= $teams[trim($list)]."/";
            }

            return $outcome.":".$ou;
        }
        elseif($gametype == "Scores In Each Half"){
            $data = ["1st Half > 2nd Half"=>"1h","1st Half = 2nd Half"=>"e","1st Half < 2nd Half"=>'2h'];
            return $data[$market];
        }
        elseif ($gametype == "Exact Number" || $gametype == "Team 2 To Score N Goals" || $gametype == "Team 1 To Score N Goals"){
            return (is_float($param) ) ? floor($param)."+" : $param;
        }
        elseif($gametype == "3Way Total"){
            $data = explode(" ", $market);
            return $data[1].":".$data[2];
        }
        elseif($gametype == "Exact Total Goals. 2 Half"|| $gametype == "Exact Total Goals. 1 Half" || $gametype == "Exact Total Goals"){
            $sign = strpos($market,"Or More") ? "+" : "";
            return $param."".$sign;
        }
        elseif ($gametype == "1X2 + First Goal"){
            $outcome = ["Team $home To Score First And W $home"=>"1/1",
                        "Team $away To Score First And W $home"=>"2/1",
                        "Team $home To Score First And W $away"=>"1/2",
                        "Team $away To Score First And W $away"=>"2/2",
                        "Team $home To Score First And A Draw"=>"1/x",
                        "Team $away To Score First And A Draw"=>"2/x"];
            return $outcome[$market];
        }
        elseif($gametype == "Score During The Match"){
            $data = explode(" ", $market);
            return $data[1].":".$data[count($data)-1];
        }
        elseif($gametype == "Individual 3Way Total 1" || $gametype == "Individual 3Way Total 2"){
            $data = explode(" ", $market);
            return $data[count($data)-2].":".$data[count($data)-1];
        }
        elseif ($gametype == "Individual Total 1 Even/Odd" || $gametype == "Individual Total 2 Even/Odd"){
           $data = ["$home Total Even"=>"even", "$home Total Odd"=>"odd",
               "$away Total Even"=>"even", "$away Total Odd"=>"odd"
           ];
            return $data[$market];

        }
        elseif ($gametype == "HT-FT + Total"){
            $ou = (strpos(trim($market),'Over') >= 0) ? "Over" : "Under";
            $outcome = [
                "W $home W $home And Total $ou ($param) - Yes"=>"1:1/$ou$param:Yes",
                "W $home W $home And Total $ou ($param) - No"=>"1:1/$ou$param:No",
                "W $away W $away And Total $ou ($param) - Yes"=>"2:2/$ou$param:Yes",
                "W $away W $away And Total $ou ($param) - No"=>"2:2/$ou$param:No"
            ];
            return $outcome[$market];
        }
        elseif ($gametype == "To Keep Clean Sheet"){
           $data = ["$home To Keep Clean Sheet" => "yes", "$away To Keep Clean Sheet" => "yes"];

            return $data[$market];
        }
        elseif ($gametype == "Team Wins"){
            $team = [$home=>1,$away=>2];
            $data = explode(" ",$market);
            return $team[$data[0]];
        }
        elseif($gametype == "Draw And Total Corners Under/Over" || $gametype == "Total Each Team Will Score Under/Over"){
            $data = explode(" ",$market);
            return $data[4]."".$param.":".$data[count($data)-1];
        }
        elseif ($gametype == "At Least One Team Will Not Score + Total"){
            $data = explode(" ",$market);
            //return $data[9]."".$param.":".$data[count($data)-1];
            return $data[count($data)-1];
        }
        elseif ($gametype == "Team Goals"){
            $outcome = ["Only $home To Score"=>1,"Both Teams To Score"=>"both",
                "Only $away To Score"=>2,"No Goals"=>"no"];
            return $outcome[$market];
        }
        elseif ($gametype == "Team 1 To Score Penalty" || $gametype=="Team 1 To Score Penalty"){
            $outcome = ["Only $home To Score"=>1,"Both Teams To Score"=>"both",
                "Only $away To Score"=>2,"No Goals"=>"no"];
            return $outcome[$market];
        }
        else{
            return $market;
        }

    }

    private function gamestypes($games, $home, $away){

        $api = "http://upload.betconverter.com/system/model/bookmakers.php";

        curl_setopt_array($this->connect,[
            CURLOPT_URL => $api,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{"homemaker":"'.$home.'","type":"'.$games.'","awaymaker":"'.$away.'"}',
            CURLOPT_HTTPHEADER => array(
                "Accept:  application/json, text/plain, */*",
                "Accept-Language:  en-US,en;q=0.5",
                "REMOTE_ADDR: ",
                "X-Requested-With:  XMLHttpRequest",
                "Content-Type:  application/json;charset=utf-8",
            ),
        ]);

        $response = curl_exec($this->connect);

        $data = json_decode($response, true);
        if($data['Error']){
            return null;
        }
        else{
            return $data['Code']['stdtype'];
        }
    }

    private function clubnames($hbookmakers,$abookmakers,$home,$away){
        $url = "http://upload.betconverter.com/system/model/clubs.php";

        curl_setopt_array($this->connect,[
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => '{
                    "homemaker" : "'.$hbookmakers.'",
                    "homeclub" : "'.$home.'",
                    "awayclub" : "'.$away.'",
                    "awaymaker" : "'.$abookmakers.'"
                }',
        ]);
        $response = curl_exec($this->connect);
        return json_decode($response, true);

    }


}