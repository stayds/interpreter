<?php
include_once './interfaces/BookmakerInterface.php';
include_once './classes/StdClubChangerClass.php';
/**
 * Onexbet Class interprete Onexbet code to games and outcome  and return response in json format
 */
class Onexbet implements BookmakerInterface {

    /**
     * @param $url
     */
    public $url = "https://1xbet.ng/LiveUtil/GetCoupon";
    public $connect;
    public $code;

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
     * @return |null
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

    public function responseParser($response, $homebookmaker, $awaybookmaker, $code) {

        $data = [];

        if (!is_null($response)) {

            foreach ($response as $key => $item) {

                //get betting type
                $g = $this->getTypes($item['GroupId'],$item['PeriodName'],$item['GameType'],$item['GroupName']);
                $groupname = $this->groupname($g,$item['Param'],$item['Opp2'],$item['MarketName']);

                //Calls method that formats the outcome
                $sport = strtolower($item['SportName']);
                if($sport == "football"){
                    $outcome = $this->football($groupname, $item['MarketName'], $item['Opp1'], $item['Opp2'], $item['Param'], $item['GameVid']);
                }
                elseif ($sport == "tennis"){
                    $outcome = $this->tennis($groupname, $item['MarketName'], $item['Opp1'], $item['Opp2'], $item['Param'], $item['GameVid']);
                }
                elseif ($sport == "basketball"){
                    $outcome = $this->basketball($groupname, $item['MarketName'], $item['Opp1'], $item['Opp2'], $item['Param'], $item['GameVid']);
                }

                $data[$homebookmaker][$awaybookmaker][] = [
                    'sport' =>strtolower($item['SportName']),
                    'league'=>$item['Liga'],
                    'home' => $item['Opp1'],
                    'away' => $item['Opp2'],
                    'type'=> $groupname,
                    'bmbtype'=> $groupname,
                    'outcome' => strtolower($outcome),
                    'odd' => $item['Coef'],
                    'ovalue' => ($item['Param'] != 0) ? $item['Param'] : null,
                ];
            }
            //Calls Static Class which queries API to get clubname and bettype that has been stored
            return StdClubChangerClass::changeClubandStd($data,$code);
        }
    }

    private function football($gametype,$market,$home,$away,$param){
            
        //outcomes for Football
        if($gametype == '1x2' || $gametype =="1x2. 1 Half" || $gametype == "1x2. 2 Half" || $gametype == "1x2. Corners" || $gametype == "1x2. 2 Half Corners" || $gametype == "1x2. 1 Half Corners"){
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
            $outcome = ($outcome == "2x" || $outcome == "21")? strrev($outcome) : $outcome;
            return $outcome;
        }
        elseif ($gametype == "Total" || $gametype == "Asian Total" || $gametype =="Total. 1 Half" || $gametype =="Total. 2 Half"){
            $data = explode(" ", $market);
            return trim($data[1])."".$param;
        }
        elseif ($gametype == "Total 1" || $gametype =="Total 1. 1 Half" || $gametype =="Total 1. 2 Half" || $gametype == "Total 2"
            || $gametype =="Total 2. 1 Half" || $gametype == "Total 2. 2 Half" || $gametype == "Total 1. Corners" || $gametype == "Total 2. Corners" || $gametype == "Asian Team Total 1" || $gametype == "Asian Team Total 2"){
            $data = explode(" ", $market);
            $item = trim($data[3]);
            return strtolower($item)."".$param;
        }
        elseif ($gametype == "Total. 1 Half Corners" || $gametype == "Total. 2 Half Corners" || $gametype == "Total. Corners"){
            $data = explode(" ", $market);
            $item = trim($data[1]);
            return strtolower($item)."".$param;
        }
        elseif ($gametype == "Total And Both To Score"){
            $data = explode("-", $market);
            $datax = (strpos("Under",$data[0]) !== false) ? "under".$param : "over".$param;
            return trim($data[1]).":".$datax;
        }
        elseif ($gametype == "Half/Half"){
            $team = [$home => "1","Drawn"=>'x',$away=>"2"];
            $data = explode("/", $market);
            $first =  explode(" ",$data[0])[0];
            $second =  explode(" ",$data[1])[0];
            return $team[$first].":".$team[$second];
        }
        elseif ($gametype == "Even/Odd" || $gametype == "Even/Odd. Corners" || $gametype == "Even/Odd. 2 Half" || $gametype == "Even/Odd. 1 Half" ){
            $data = ["Total Even - Yes"=>"even","Total Even - No"=>"odd" ];
            return $data[$market];
        }
        elseif($gametype == "Multi Goal" || $gametype == "Multi Goal. 1 Half" || $gametype == "Multi Goal. 2 Half"){
            $data = str_replace("Multi Goal ","",$market);
            return trim($data);
        }
        elseif ($gametype == "Both Teams To Score" || $gametype == "Both Teams To Score. 1 Half" || $gametype == "Both Teams To Score. 2 Half" || $gametype == "Both Teams To Score 2+"  || $gametype == "Red Card"
            || $gametype == "Own Goal" ||  $gametype == "Goal In Both Halves" || $gametype == "Penalty Awarded" ||
            $gametype == "Penalty Awarded And Sending Off" || $gametype == "Goal After Corner" || $gametype == "Draw In At Least One Half"
            || $gametype == "Team 2 To Win Either Half" || $gametype == "Team 1 To Win Either Half" || $gametype == "Both Halves To Be Won By Different Teams"
            || $gametype == "Score Draw" || $gametype == "A Player Scores Two Goals (Brace)" || $gametype == "Draw In Both Halves" || $gametype == "Total Goal Minutes"
            || $gametype == "Team 1 Win To Nil" || $gametype =="Team 2 Win To Nil" || $gametype =="Team 1 To Score A Goal In Both Halves"
            || $gametype =="Team 2 To Score A Goal In Both Halves" || $gametype == "Team 2 Win To Nil. 1 Half" || $gametype == "Team 2 Win To Nil. 2 Half" || $gametype == "Team 1 Win To Nil. 1 Half"
            || $gametype == "Team 1 Win To Nil. 2 Half" || $gametype == "First 5 Minutes. First To Happen" || $gametype == "Draw In At Least One Half"
        )
        {
            $data = explode("-", $market);
            return trim(strtolower($data[count($data)-1]));
            
        }
        elseif ($gametype == "Win By") {
            $team = [$home => "1","Draw"=>'x',$away=>"2"];

            if(strpos($market,$home) !== false){
                $data = (is_float($param)) ? floor($param)."+": $param;
                $data = $team[$home]."by:".$data;
            }
            elseif (strpos($market,$away) !== false){
                $data = (is_float($param)) ? floor($param)."+": $param;
                $data = $team[$away]."by:".$data;
            }
            else{
                $data = "x";
            }
            return $data;

        }

        elseif ($gametype == "Both Teams To Score + Double Chance" || $gametype == "Both Teams To Score + Double Chance. 1 Half" || $gametype == "Both Teams To Score + Double Chance. 2 Half"){
            $data = [
                "At Least One Team Not To Score And 1X - Yes"=>"1/x:yes",
                "At Least One Team Not To Score And 1X - No"=>"1/x:no",
                "At Least One Team Not To Score And 2X - Yes"=>"1/x:yes",
                "At Least One Team Not To Score And 2X - No"=>"1/x:no",
            ];
            return $data[$market];
        }
        elseif ($gametype == "Handicap" || $gametype == "Asian Handicap" || $gametype =="Handicap. 1 Half"
            || $gametype == "Handicap. 2 Half" || $gametype == "Asian Handicap. 1 Half" || $gametype == "Asian Handicap. 2 Half" ){

            $team = [$home=>'1','draw'=>'x',$away=>'2'];
            /*
             * this is used to get which team it is (away or home), by getting the
             * first occurrence of the team is the marketname string
            */
            $option = (strpos($market,$home)) ? $team[$home] : $team[$away];
            //$param = (is_int($param)) ? sprintf("%.1f", $param) : $param;
            $param = ($param > 0) ? "+".$param : $param;
            return $option.":".$param;
        }
        elseif ($gametype == "Handicap. Corners"){
            $team = [$home=>'1h','draw'=>'xh',$away=>'2h'];
            /*
             * this is used to get which team it is (away or home), by getting the
             * first occurrence of the team is the marketname string
            */
            $option = (strpos($market,$home)) ? $team[$home] : $team[$away];
            //$param = (is_int($param)) ? sprintf("%.1f", $param) : $param;
            $param = ($param > 0) ? "+".$param : $param;
            return $option."(".$param.")";
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
        elseif ($gametype == "Team 2, Multi Goal" || $gametype =="Team 1, Multi Goal"){
            $data = explode(" ",$market);
            return $data[count($data)-1];
        }
        elseif ($gametype == "Team Wins"){
            $data = explode(" ", $market);
            $team = [$home=>'1',$away=>'2'];
            return $team[$data[0]];
        }
        elseif ($gametype == "Correct Score (17Way)" || $gametype == "Correct Score (17way). 1 Half" || $gametype =="Correct Score (17way). 2 Half"|| $gametype == "Correct Score"
        || $gametype == "Correct Score. 1 Half" || $gametype == "Correct Score. 2 Half"){
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
            $outcome = ["Team $home To Score First And W $home"=>"hgoal:1",
                        "Team $away To Score First And W $home"=>"agoal:1",
                        "Team $home To Score First And W $away"=>"hgoal:2",
                        "Team $away To Score First And W $away"=>"agoal:2",
                        "Team $home To Score First And A Draw"=>"hgoal:x",
                        "Team $away To Score First And A Draw"=>"agoal:x"];
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
                "W $home W $home And Total $ou ($param) - Yes"=>"1/1:$ou$param",
                "W $home W $home And Total $ou ($param) - No"=>"1/1:$ou$param",
                "W $away W $away And Total $ou ($param) - Yes"=>"2/2:$ou$param",
                "W $away W $away And Total $ou ($param) - No"=>"2/2:$ou$param"
            ];
            return $outcome[$market];
        }
        elseif ($gametype == "To Keep Clean Sheet"){
           $data = ["$home To Keep Clean Sheet" => "yes"];
            return $data[$market];
        }
        elseif($gametype == "Away To Keep Clean Sheet"){
            $data = ["$away To Keep Clean Sheet" => "yes"];
            return $data[$market];
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
            $outcome = ["Only $home To Score"=>"onlyhome","Both Teams To Score"=>"bothteams",
                "Only $away To Score"=>"onlyaway","No Goals"=>"none"];
            return $outcome[$market];
        }
        elseif ($gametype == "Team 1 To Score Penalty" || $gametype=="Team 1 To Score Penalty"){
            $outcome = ["Only $home To Score"=>1,"Both Teams To Score"=>"both",
                "Only $away To Score"=>2,"No Goals"=>"no"];
            return $outcome[$market];
        }
        elseif ($gametype == "Correct Score - Group Bet"){
            $data = str_replace("Correct Score ","",str_replace("- Yes","" ,$market));
            $datax = str_replace("-",":",str_replace(" Or ","/", $data));
            return trim($datax);
        }
        elseif ($gametype == "Total. Cards") {
            $data = explode(" ", $market);
            return trim($data[1])."".$param;
        }
        elseif ($gametype == "HT-FT. Corners") {
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
        elseif ($gametype == "Team 1 To Score Goals In A Row" || $gametype == "Team 2 To Score Goals In A Row" || $gametype == "Team 1 To Score 3 Goals In A Row" || $gametype == "Team 2 To Score 3 Goals In A Row") {
           $data = explode("-", $market);
           return trim($data[count($data)-1]);
        }
        elseif ($gametype == "Result In Minute. Corners") {
          // code...
        }
        elseif ($gametype == "Will An Awarded Penalty Be Scored") {
          $data = ["Penalty Awarded And Scored"=>"scored","Penalty Awarded And Not Scored"=>"missed"];
          return $data[$market];
        }

        else{
            return $market;
        }

    }

    private function tennis($gametype,$market,$home,$away,$param){

        if ($gametype == "Total" || $gametype =="Total. 1 Set" || $gametype =="Total. 2 Set"){
            $data = explode(" ", $market);
            return trim($data[1])."".$param;
        }
        elseif ($gametype == "Total 1" || $gametype =="Total 1. 1 Set" || $gametype =="Total 1. 2 Set" || $gametype == "Total 2"
            || $gametype =="Total 2. 1 Set" || $gametype == "Total 2. 2 Set"){
            $data = explode(" ", $market);
            $item = trim($data[3]);
            return strtolower($item)."".$param;
        }
        elseif ($gametype == "Handicap" || $gametype =="Handicap. 1 Set" || $gametype == "Handicap. 2 Set"){

            $team = [$home=>'1','draw'=>'x',$away=>'2'];
            
            $option = (strpos($market,$home)) ? $team[$home] : $team[$away];
            //$param = (is_int($param)) ? sprintf("%.1f", $param) : $param;
            $param = ($param > 0) ? "+".$param : $param;
            return $option.":".$param;
        }
        elseif ($gametype == "Even/Odd" || $gametype == "Even/Odd. 2 Set" || $gametype == "Even/Odd. 1 Set" ){
            $data = ["Total Even - Yes"=>"even","Total Even - No"=>"odd" ];
            return $data[$market];
        }
        elseif ($gametype == "Correct Score" || $gametype == "Correct Score. 1 Set" || $gametype == "Correct Score. 2 Set"){
            $outcome = explode(' ',$market);
            return str_replace("-",":",$outcome[2]);
        }
        elseif ($gametype == "Sets Handicap") {
            $team = [$home=>'1','draw'=>'x',$away=>'2'];
            
            $option = (strpos($market,$home)) ? $team[$home] : $team[$away];
            //$param = (is_int($param)) ? sprintf("%.1f", $param) : $param;
            $param = ($param > 0) ? "+".$param : $param;
            return $option.":".$param;
        }
        elseif ($gametype =="Set / Match"){
            $combination = [
                "Set / Match W $home W $home" => '1/1',
                "Set / Match W $home W $away"=>'1/2',
                "Set / Match W $away W $home"=>'2/1',
                "Set / Match W $away W $away"=>'2/2',
            ];
            return $combination[$market];
        }
    }

    private function basketball($gametype,$market,$home,$away,$param,$gamevid){

        if($gametype == "1X2 In Regular Time" || $gametype =="1x2. 1 Half" || $gametype == "1x2. 2 Half" || $gametype =="1x2. 1 Quarter" || $gametype =="1x2. 2 Quarter" || $gametype =="1x2. 3 Quarter" || $gametype =="1x2. 4 Quarter" ){
            $team = ["W $home In Regular Time"=>'1','X In Regular Time'=>'x',"W $away In Regular Time"=>'2'];
            return $team[$market];
        }

        elseif ($gametype == "Handicap" || $gametype =="Handicap. 1 Half" || $gametype == "Handicap. 2 Half"){
            $team = [$home=>'1',$away=>'2'];
            $option = (strpos($market,$home)) ? $team[$home] : $team[$away];
            $param = ($param > 0) ? "+".$param : $param;
            return $option.":".$param;
        }
        elseif ($gametype == "Total" || $gametype =="Total. 1 Half" || $gametype =="Total. 2 Half" || $gametype == "Total. 1 Quarter" || $gametype == "Total. 2 Quarter" || $gametype == "Total. 3 Quarter" || $gametype == "Total. 4 Quarter"){
            $data = explode(" ", $market);
            return trim($data[1])."".$param;
        }
        elseif ($gametype == "Regular Time Double Chance" || $gametype == "Double Chance. 2 Half" || $gametype=="Double Chance. 1 Half" || $gametype == "Double Chance. 1 Quarter" || $gametype == "Double Chance. 2 Quarter" || $gametype == "Double Chance. 3 Quarter" || $gametype == "Double Chance. 4 Quarter") {
            $team = ["W $home Or X In Regular Time"=>'1x',"W $home Or W $away In Regular Time"=>'12',"W $away Or X In Regular Time"=>'x2'];
            return $team[$market];
        }
        elseif ($gametype == "Even/Odd" || $gametype == "Even/Odd. 2 Half" || $gametype == "Even/Odd. 1 Half") {
            $data = ["Total Even - Yes"=>"even","Total Even - No"=>"odd" ];
            return $data[$market];
        }
        elseif ($gametype == "Team Wins. Including Overtime") {
            $data = ["$home  Wins"=>"1","$away  Wins"=>"2" ];
            return $data[$market];
        }
        elseif ($gametype == "Win By") {

            $team = [$home => "W1",$away=>"W2"];

            if(strpos($market,$team[$home]) !== false){
                $data = (is_float($param)) ? floor($param)."+": $param;
                $data = "1"."by:".$data;
            }
            elseif (strpos($market,$team[$away]) !== false){
                $data = (is_float($param)) ? floor($param)."+": $param;
                $data = "2"."by:".$data;
            }
            else{
                $data = "x";
            }
            return $data;

        }

        elseif ($gametype == "Total 1" || $gametype =="Total 1. 1 Half" || $gametype =="Total 1. 2 Half" || $gametype == "Total 2" || $gametype =="Total 2. 1 Half" || $gametype == "Total 2. 2 Half"
            || $gametype == "Total 2. 1 Quarter" || $gametype == "Total 2. 2 Quarter" || $gametype == "Total 2. 3 Quarter" || $gametype == "Total 2. 4 Quarter"
            || $gametype == "Total 1. 1 Quarter" || $gametype == "Total 1. 2 Quarter" || $gametype == "Total 1. 3 Quarter" || $gametype == "Total 1. 4 Quarter"){
            $data = explode(" ", $market);
            $item = trim($data[3]);
            return strtolower($item)."".$param;
        }
        elseif ($gametype == "Will There Be Overtime? - Yes/No") {
            $data = explode("-", $market);
            return  trim(strtolower($data[count($data)-1]));
        }
        elseif ($gametype == "First Half / Full-time") {
            $combination = [
                "Half-time / Full-time W $home W $home" => '1/1',
                "Half-time / Full-time XW $home"=>'x/1',
                "Half-time / Full-time W $away W $home"=>'2/1',
                "Half-time / Full-time W $home X"=>'1/x',
                "Half-time / Full-time XX"=>'x/x',
                "Half-time / Full-time W $away X"=>'2/x',
                "Half-time / Full-time W $home W $away"=>'1/2',
                "Half-time / Full-time XW $away"=>'x/2',
                "Half-time / Full-time W $away W $away"=>'2/2',
                "Half-time / Full-time $home/$away X"=>'1/2/x',
                "Half-time / Full-time $away/$home X"=>'2/1/x',
            ];
            return $combination[$market];
        }
    }

    private function getTypes($groupid, $period,$gametype,$groupname){
        /**
         * The method binds each betting type with its corresponding id,
         * so the Id can be used in place of the groupname(from the API),
         * in getting the right betting type
        */
        $types = [
            "1" => "1x2",
            "2" => "Handicap",
            "8" => "Double Chance",
            "11" => "HT-FT",
            "14" => "Even/Odd",
            "15" => "Total 1",
            "62" => "Total 2",
            "19" => "Both Teams To Score",
            "17" => "Total",
            "18" => "Scores In Each Half",
            "32" => "Goal In Both Halves",
            "43" => "Team 1 To Win Either Half",
            "44" => "Team 2 To Win Either Half",
            "9322"=>"Both Teams To Score + Double Chance",
            "100" => "To Qualify",
            "91" => "Individual Total 1 Even/Odd",
            "92" => "Individual Total 2 Even/Odd",
            "99" => "Asian Total",
            "2854" => "Asian Handicap",
            "27" => "European Handicap",
            "8863" => "Correct Score (17Way)",
            "136" => "Correct Score",
            "154" => "Last Goal",
            "2418" => "To Keep Clean Sheet",
            "2422" => "Team 1 Scores In Halves",
            "2424" => "Team 2 Scores In Halves",
            "2876" => "Team 1 To Score N Goals",
            "2878" => "Team 2 To Score N Goals",
            "2866" => "Team 1 Win To Nil",
            "2867" => "Team 2 Win To Nil",
            "7142" => "1X2 + First Goal",
            "2667" => "Draw + Total",
            "9939" => "Exact Number",
            "10047" => "Team 2 To Win Both Halves",
            "10046" => "Team 1 To Win Both Halves",
            "8427" => "Asian Team Total 1",
            "8429" => "Asian Team Total 2",
            "2668"=>"Double Chance + Total",
            "2440"=>"Team Goals",
            "1130"=>"Exact Total Goals",
            "8801"=>"Team 1, Multi Goal",
            "8803"=>"Team 2, Multi Goal",
            "3265"=>"Correct Score - Group Bet",
            "864"=>"Win By",
            "7609"=>"Win By....",
            "7961"=>"Multi Goal",
            "50"=>"Penalty Awarded",
            "2880"=>"Team 1 To Score Goals In A Row",
            "2882"=>"Team 2 To Score Goals In A Row",
            "2382"=>"First 5 Minutes. First To Happen",
            "2444"=>"Total And Both To Score",
            "829"=>"HT-FT + Total",
            "10064"=>"Red Card",
            "303"=>"Result In Minute. Corners",
            "10037"=>"Team 1 To Score A Goal In Both Halves",
            "10038"=>"Team 2 To Score A Goal In Both Halves",
            "75"=>"Goal In Half",
            "10065"=>"Will An Awarded Penalty Be Scored",
            "49"=>"Draw In At Least One Half",
            "2766"=>"1X2 In Regular Time",
            "2768" => "Regular Time Double Chance",
            "38" => "Team Wins",
            "101" => "Team Wins. Including Overtime",
            "90" => "Will There Be Overtime? - Yes/No",
            "2244" => "First Half / Full-time",
            "109" => "Sets Handicap",
            "2744"=>"Set / Match",
            "176"=>"Highest Scoring Period"

        ];

        if(isset($types[$groupid]) && $period != "" && $gametype ==""){
            return $types[$groupid].". ".$period;
        }
        elseif(isset($types[$groupid]) && $period != "" && $gametype !=""){
            return $types[$groupid].". ".$period." ".$gametype;
        }
        elseif (isset($types[$groupid]) && $gametype != "" && $period == ""){
            return $groupname.". ".$gametype;
        }
        elseif(isset($types[$groupid]) && $gametype=="Win By...."){
            return "Win By";
        }
        else{
            return $types[$groupid];
        }


    }

    private function groupname($name,$param,$away, $market){

        /**
         * This get/adjust the betting type
        */

        if($name == "Team 1 To Score Goals In A Row" & $param == 2){
            return "Team 1 To Score Goals In A Row";
        }
        elseif($name == "Team 2 To Score Goals In A Row" & $param == 2){
            return "Team 2 To Score Goals In A Row";
        }
        elseif($name == "Team 1 To Score Goals In A Row" & $param == 3){
            return "Team 1 To Score 3 Goals In A Row";
        }
        elseif($name == "Team 2 To Score Goals In A Row" & $param == 3){
            return "Team 2 To Score 3 Goals In A Row";
        }
        elseif ($name == "Both Teams To Score" & $param == 2 ){
            return "Both Teams To Score 2+";
        }
        elseif ($name == "To Keep Clean Sheet" & strpos($market,$away) !== false){
            return "Away To Keep Clean Sheet";
        }
        else{
            return $name;
        }
    }
}
