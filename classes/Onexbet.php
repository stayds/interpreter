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
                $outcomes = $this->outcome($item['GroupName'], $item['MarketName'], $item['Opp1'], $item['Opp2'], $item['Param']);
                $data[$homebookmaker][$awaybookmaker][] = [
                    'home' => $item['Opp1'],
                    'away' => $item['Opp2'],
                    'gametype' => $this->gametype($item['GroupName']),
                    'outcome' => strtolower($outcomes),
                    'odd' => $item['Coef'],
                    'ovalue' => ($item['Param'] != 0) ? $item['Param'] : null,
                ];
            }

            echo json_encode($data);
        }


    }

    public function outcome($gametype,$market,$home,$away,$param){

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
            $outcome = ['Over'=>'over','Under'=>'under'];
            $data = explode(" ", $market);
            $item = trim($data[1]);
            return $outcome[$item].":".$param;
        }
        elseif ($gametype == "Total 1" || $gametype =="Total 1. 1 Half" || $gametype =="Total 1. 2 Half" || $gametype == "Total 2"
            || $gametype =="Total 2. 1 Half" || $gametype == "Total 2. 2 Half" || $gametype == "Asian Team Total 1" || $gametype == "Asian Team Total 2"){
            $data = explode(" ", $market);
            $item = trim($data[3]);
            return $item.":".$param;
        }
        elseif ($gametype == "Half/Half"){
            $team = [$home => "1","Drawn"=>'x',$away=>"2"];
            $data = explode("/", $market);
            $first =  explode(" ",$data[0])[0];
            $second =  explode(" ",$data[1])[0];
            return $team[$first].":".$team[$second];
        }
        elseif ($gametype == "Both Teams To Score" || $gametype == "Both Teams To Score. 1 Half" || $gametype == "Even/Odd" || $gametype == "Even/Odd. 2 Half" || $gametype == "Even/Odd. 1 Half" || $gametype == "Red Card"
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

            //get the absolute number since a negative or positive is given
            $value = abs($param);

            if($param > 0){
               return $outcome = ceil($value).":0:".$option;
            }
            return $outcome = "0:".ceil($value).":".$option;

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
            return $out.":".$team[$data[3]];
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
            return $outcome[2];
        }
        elseif ($gametype == "To Qualify"){
            $team = [$home=>'1','draw'=>'x',$away=>'2'];
            $outcome = explode('-',$market);
            return $team[trim($outcome[1])];
        }
        elseif ($gametype == "Goal In Half"){
            $outcome = ["First Goal in 1st Half"=>'1','First Goal in 2nd Half'=>'2',"No First Goal"=>'no'];
            return "h:".$outcome[$market];
        }
        elseif ($gametype == "Next Goal"){
            $team = [$home=>'1','Neither'=>'neither',$away=>'2'];
            $outcome = explode(' ', $market);
            return $team[$outcome[0]].":".$param;
        }
        elseif ($gametype == "HT-FT"){
            $combination = [
                "W $home W $home" => '1:1',"XW $home"=>'x:1',
                "W $away W $home"=>'2:1',"W $home X"=>'1:x',
                "XX"=>'x:x',"W $away X"=>'2:x',
                "W $home W $away"=>'1:2',"XW $away"=>'x:2',
                "W $away W $away"=>'2:2',"$home/$away X"=>'1/2:x',
                "$away/$home X"=>'2/1:x',
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
            return (strpos(trim($market),$home) == 0) ? 1 : 2;
        }
        elseif ($gametype == "Team 2, Result + Total" || $gametype == "Team 1, Result + Total"){
            $data = explode('And', $market);
            $team = (strpos(trim($data[0]),$home) == 0) ? 1 : 2;
            $newdata = explode("-",$data[1]);
            $ou = (strpos(trim($newdata[0]), ">")) ? "Over" : "Under";
            return $team.":".$ou."".$param."^".trim($newdata[1]);
        }
        elseif ($gametype =="Team 2 Scores In Halves" || $gametype =="Team 1 Scores In Halves"){
            $outcome = [
                "$home - 1st Half > 2nd Half"=>"1","$home - 1st Half = 2nd Half"=>"equal",
                "$home - 1st Half < 2nd Half"=>"2","$away - 1st Half > 2nd Half"=>"1",
                "$away - 1st Half = 2nd Half"=>"equal","$away - 1st Half < 2nd Half"=>"2"
            ];
            return $outcome[$market];
        }
        elseif($gametype == "Draw + Total" || $gametype == "Draw + Total. 2 Half" || $gametype =="Draw + Total. 1 Half"){
            $data = explode(' ', $market);
            $ou = ($data[3] === ">") ? "Over" : "Under";
            return $ou.":".$param."^".$data[6];
        }
        elseif($gametype == "Double Chance + Total" || $gametype == "Double Chance + Total. 1 Half" || $gametype == "Double Chance + Total. 2 Half"){
            $data = explode(' ', $market);
            $ou = ($data[3] === ">") ? "Over" : "Under";
            return $ou.":".$param."^".$data[6];
        }
        elseif($gametype == "Scores In Each Half"){
            $data = ["1st Half > 2nd Half"=>"1>2","1st Half = 2nd Half"=>"1=2","1st Half < 2nd Half"=>'1<2'];
            return $data[$market];
        }
        elseif($gametype == "3Way Total"){
            $data = explode(" ", $market);
            return $data[1].":".$data[2];
        }
        elseif($gametype == "Exact Total Goals. 2 Half"|| $gametype == "Exact Total Goals. 1 Half" || $gametype == "Exact Total Goals"){
            return $param;
        }
        elseif ($gametype == "1X2 + First Goal"){
            $outcome = ["Team $home To Score First And W $home"=>"1^1",
                        "Team $away To Score First And W $home"=>"2^1",
                        "Team $home To Score First And W $away"=>"1^2",
                        "Team $away To Score First And W $away"=>"2^2",
                        "Team $home To Score First And A Draw"=>"1^X",
                        "Team $away To Score First And A Draw"=>"2^X"];
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
            $team = [$home=>1,$away=>2];
            $data = explode("Total",$market);
            return $team[$data[0]].":".$data[1];

        }
        elseif ($gametype == "HT-FT + Total"){
            echo "hello";
            $outcome = [
                "W $home W $home And Total Over (2.5) - Yes"=>"1:1^Over2.5:Yes",
                "W $home W $home And Total Over (2.5) - No"=>"1:1^Over2.5:No",
                "W $away W $away And Total Over (2.5) - Yes"=>"2:2^Over2.5:Yes",
                "W $away W $away And Total Over (2.5) - No"=>"2:2^Over2.5:No"
            ];
            return $outcome[$market];
        }
        elseif ($gametype == "To Keep Clean Sheet"){
           // $outcome = [$home => 1, $away => 2, 'neither' => 'none'];
            $data = explode(' ', $market);
            return $data[1].":".$data[count($data)-1];
        }
        elseif ($gametype == "Team Wins"){
            $team = [$home=>1,$away=>2];
            $data = explode(" ",$market);
            return $team[$data[0]];
        }
        elseif($gametype == "Draw And Total Corners Under/Over" || $gametype == "Total Each Team Will Score Under/Over"){
            $data = explode(" ",$market);
            return $data[4].":".$param."^".$data[count($data)-1];
        }
        elseif ($gametype == "At Least One Team Will Not Score + Total"){
            $data = explode(" ",$market);
            return $data[9].":".$param."^".$data[count($data)-1];
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

    public function gametype($gametype){

        $types = [
            "1x2"=>"m_1x2",
            "1x2. 2 Half"=>"m1x2_2ht",
            "1x2. 1 Half"=>"m1x2_ht",
            "Next Goal"=>"Next Goal",
            "Handicap"=>"handicap",
            "Team 1 To Win Both Halves"=>"h_win_both_half",
            "Team 2 To Win Both Halves"=>"a_win_both_half",
            "Asian Handicap"=>"asain_hcap",
            "Asian Handicap. 1 Half"=>"ahcap_1half",
            "Asian Handicap. 2 Half"=>"ahcap_2half",
            "Double Chance. 1 Half"=>"dc_first_half",
            "Multi Goal"=>"multi_goal",
            "Both Teams To Score. 1 Half"=>"btts_1half",
            "Correct Score (17Way)"=>"c_score",
            "Correct Score (17way). 1 Half"=>"c_score_half",
            "Correct Score (17way). 2 Half"=>"c_score_half",
            "Double Chance"=>"d_chance",
            "Exact Total Goals. 2 Half"=>"exact_goal_2ht",
            "Exact Total Goals. 1 Half"=>"exact_goal_ht",
            "Even/Odd"=>"odd_even",
            "Even/Odd. 1 Half" =>"odd_even_ht",
            "Even/Odd. 2 Half" =>"even_odd_2half",
            "HT-FT"=>"hf_time",
            "Total"=>"over_under",
            "Total 1"=>"total_1",
            "Total 1. 1 Half"=>"ou_home_ht",
            "Total 1. 2 Half"=>"ov_home_2ht",
            "Total 2. 1 Half"=>"ov_away_ht",
            "Total 2. 2 Half"=>"ov_away_2ht",
            "Total 2"=>"total_2",
            "Team 2 To Win Either Half" => "a_wins_eith",
            "Team 1 To Win Either Half" => "h_wins_eith",
            "Scores In Each Half"=> "h_score_half",
            "Individual Total 2 Even/Odd"=> "away_oddeven",
            "Individual Total 1 Even/Odd"=> "home_oddeven",
            "To Keep Clean Sheet"=> "h_clean_sheet",
            "Team 1 Win To Nil"=>"h_win_nil",
            "Team 2 Win To Nil"=>"a_win_nil",
            "At Least One Team Will Not Score + Total"=>"gn_goal",
            "Team 1 To Score A Goal In Both Halves"=>"h_score_both",
            "Team 2 To Score A Goal In Both Halves"=>"a_score_both",
            "Team 1 Scores In Halves"=>"h_highest_score",
            "Team 2 Scores In Halves"=>"a_highest_score",
            "Handicap. 1 Half"=>"handicap_half",
            "Handicap. 2 Half"=>"handicap_2ht",
            "Total. 1 Half"=> "un_over_half",
            "Total. 2 Half"=> "un_over_2half",
            "Double Chance. 2 Half"=>"dc_2half",
            "Draw + Total. 2 Half"=>"drawnobet_2haft",
            "Draw + Total. 1 Half"=>"dn_bet_half",
            "Double Chance + Total. 1 Half"=>"dc_both_score_ht",
            "Double Chance + Total. 2 Half"=>"dc_both_score_2ht",
            "HT-FT + Total"=>"htft_c_score",
            "Goal In Half"=>"first_goal",
            "Team 1 To Score N Goals"=>"h_score_home",
            "Last Goal"=>"last_goal",
            "Both Teams To Score"=>"both_team_score",
            "A Player Scores Two Goals (Brace)"=>"A Player Scores Two Goals (Brace)",
            "European Handicap"=>"European Handicap",
            "Fouls"=>"Fouls",
            "Draw And Total Corners Under/Over"=>"Draw And Total Corners Under/Over",
            "Draw In At Least One Half"=>"Draw In At Least One Half",
            "Draw in Both halves"=>"Draw in Both halves",
            "Own Goal"=>"Own Goal",
            "Red Card"=>"Red Card",
            "Goal After Corner"=>"Goal After Corner",
            "Total Goal Minutes"=>"Total Goal Minutes",
            "Half/Half"=>"Half/Half",
            "Penalty Awarded And Sending Off"=>"Penalty Awarded And Sending Off",
            "Team 1 To Score Penalty"=>"Team 1 To Score Penalty",
            "Team 2 To Score Penalty"=>"Team 1 To Score Penalty",
            "Asian Total"=>"Asian O/U",
            "Asian Team Total 1"=>"Asian Team Total 1",
            "Asian Team Total 2"=>"Asian Away Team O/U",
            "How Goal Will Be Scored"=>"How Goal Will Be Scored",
            "Number In The Score"=>"Number In The Score",
            "To Qualify" => "To Qualify",
            "Team 2, Result + Total" => "Away Win + O/U",
            "Team 1, Result + Total" => "Home Win + O/U",
            "Draw + Total" => "Draw + O/U",
            "Goal Up To Minute"=>"Goal Up To Minute",
            "Goal Interval - No"=>"Goal Interval",
            "Goal Interval - Yes"=>"Goal Interval",
            "Goal In Both Halves"=> "Goal In Both Halves",
            "Penalty Awarded"=> "Penalty Awarded",
            "3Way Total"=> "3Way O/U",
            "Individual 3Way Total 1"=> "Home 3Way O/U",
            "Individual 3Way Total 2"=> "Away 3Way O/U",
            "Team Wins"=> "Team Wins",
            "Team Goals"=> "Team Goals",
            "Last Goal Time"=> "Last Goal Time",
            "Score Draw"=> "Score Draw",
            "Score During The Match"=> "Score During The Match",
            "Double Chance + Total"=> "Double Chance + O/U",
            "Exact Number" =>"",
            "Both Halves To Be Won By Different Teams"=> "Both Halves To Be Won By Different Teams",
        ];

        return ($types[$gametype]) ? $types[$gametype] : $gametype;
    }

}