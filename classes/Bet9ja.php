<?php
include_once './interfaces/BookmakerInterface.php';
include_once './classes/bet9jaclass/Bet9jaHandler.php';
include_once './classes/StdClubChangerClass.php';

/**
 * Accessbet Class interprete Accessbet code to games and outcome  and return response in json format
 */
class Bet9ja implements BookmakerInterface {

    /**
     * @param $url
     */
    public $url;

    /**
     * Default constructor
     */
    public function __construct() {

    }

    private $code;

    /**
     * Method call to bookmaker API
     * @param $code
     * booked game code from the homebookmaker
     * @return false|string
     */
    public function callBookMaker($code) {
        $this->code = $code;
        return (new Bet9jaHandler($code) )->otherOperations();
    }


    /**
     * Method call to parse the response from the bookmaker API
     * @param $response variable that stores the response from homebookmaker call
     * @param $homebookmaker variable that stores the home or intiating bookmaker name
     * @param $awaybookmaker variable that stores the away or destination bookmaker name
     * @return false|string
     */
    public function responseParser($response, $homebookmaker, $awaybookmaker, $code) {
        // TODO implement here

        $response = json_decode($response,true);

        if( count($response) == 0){
            // This helps to handle cases of incorrect of expired Coupon code
            // This is coming directory from the api
            $response = ['status'=>"failed", "message"=>"Invalid/Expired Homebookmaker Code"];
            return json_encode($response, JSON_FORCE_OBJECT);
        }

        return $this->jsonateResponse($response, $homebookmaker, $awaybookmaker,$code);
//        $result = $this->jsonateResponse($response, $homebookmaker, $awaybookmaker,$code);
//        return json_encode($result);
    }

     private function jsonateResponse($response, $homebookmaker, $awaybookmaker, $code){

        $output_awaybookmaker = $awaybookmaker ? $awaybookmaker : 'awayBookmaker'; // To make sure awaybookmaker does not cause error in array
        $output_homebookmaker = $homebookmaker ? $homebookmaker : 'Bet9ja'; //// This is the default homebookmaker for this Bet9ja class

        $new_format = ['home'=> null ,'away'=>null ,'type'=>null ,'bmbtype'=>null, 'outcome'=>null ,'ovalue'=>null ,'odd'=>null,'league'=>null];
        $result = array($output_homebookmaker =>[$output_awaybookmaker =>[]] );

        $group_one = array('Tot Goals','Total Goals HT','Total Goals 2HT','DNB','DNB HT','DNB 2HT',
            '1X2','Half Time','1X2 - 2HT','1X2 - HT','1X2 HT','ANB','HNB','1X2 Corner','1st Corner');
        $group_two = array('Odd/Even','Odd/Even Home','Odd/Even Away','Odd/Even 1HT','Odd/Even 2HT','Double Chance',
            'Goals Home','Goals Away','Goal Type','Double Chance HT','Double Chance 2HT',
            'Away Win To Nil','Home Win To Nil','Corner 10 Min','Red card','Odd / Even Card','DC HT/FT',
            'GG/NG 1st & 2nd Half',
            'Home To Score','Away To Score','Clean Sheet Home','Clean Sheet Away',"At Least a Half X","Odd/Even Corner"); // works for "3+" "odd" e.t.c
        $both_team_array = array('GG/NG','GG/NG HT','GG/NG 2+','GG/NG 2HT');
        $penalty = ["Penalty Yes/No","PenaltyScored/Missed","Penality Scored","Penality Missed","ExactCards"];
        $corner_group_one = ['1X2 Corner 2HT','1X2 Corner HT'];
        $fl_card = ['FirstCard','LastCard'];
        $first_last_goal = ['First Goal','Last Goal'];
//        return $response;
        foreach($response as $key=>$matchInfo){
            $new_format["type"] = trim($matchInfo['type']);
            $new_format["bmbtype"] = trim($matchInfo['type']);

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

            $very_tricky_case =false;
            if(preg_match("/(\s*(\w+)\s*(Goalscorer))|(number of goals)|(G. Pl. Min)/i", $new_format["type"])) {
                $very_tricky_case = true;
                //for Goal Scorer and its variants, Number of Goals, G. PL. Min
                $teams = explode(" - ", $matchInfo['league']); // display teams in league entry instead of match entry
            }else{
                $teams = explode(" - ", $matchInfo['match']);
            }



            $new_format["home"] = trim($teams[0]);
            $new_format["away"] = trim($teams[1]);
            $new_format["odd"]=$matchInfo['odd'];
            $new_format["league"]=$matchInfo['league'];

            if(preg_match("/(Score)\s*\d+\s*(min)/i", $new_format["type"])) {
                /**
                 * @outcome Yes or No
                 * @bettype "Score 5min" and its variants
                 */
                $new_format["outcome"] = $matchInfo["team"];
                $new_format["type"] = strtolower( $new_format["type"] );

            }elseif($very_tricky_case) { //for Goal Scorer and its variants
                $filter= explode(' ',$matchInfo['team']);
                $new_format['ovalue'] = str_replace(['(',')',' '],['',':'],$matchInfo['match']);
                if(preg_match("/\w*\s*(Goalscorer)/", $new_format["type"])){
                    $aim = trim($filter[0]);
                    $fm = ":".$new_format['ovalue'];
                    $new_format["outcome"] = $aim.$fm;
                }
                elseif($new_format["type"] == "Number of Goals" ){
                    $new_format["outcome"] = $filter[0].":".$new_format['ovalue'];
                }elseif($new_format["type"]=="G. Pl. Min"){
                    $new_format["outcome"] = $filter[0].":".$new_format['ovalue'];
                }
            }elseif( preg_match("/^(1X2)\s*(-)\s*\d+\s*(min)/i", $new_format["type"]) ){
                /**
                 * @outcome Yes or No
                 * @bettype for "1X2 _XMINS" and its variants
                 */
                $filter= explode('-',$matchInfo['team']);
                // $new_format['ovalue'] =  str_ireplace(['min'],'',trim($filter[1]));
                $new_format["outcome"] = trim($filter[0]);//.":".$new_format['ovalue'];
                $new_format["type"] = strtolower( $new_format["type"] );

            }elseif( preg_match("/^(Home|Away)\s*(Score)\s*(HT|2HT)/i", $new_format["type"]) ){
                /**
                 * @outcome Yes or No
                 * @bettype for "Home Score HT" and "Away Score HT" and "Home Score 2HT" and "Away Score 2HT"

                 */
                $filter= explode('-',$matchInfo['team']);
                $new_format['ovalue'] =  str_ireplace(['min'],'',trim($filter[1]));
                $new_format["outcome"] = trim($filter[0]);//.":".$new_format['ovalue'];
                $new_format["type"] = strtolower( $new_format["type"] );

            }elseif( preg_match("/^(3\s*Combo\s*\d\.\d)$/i", $new_format["type"]) ){
                /**
                 * @outcome 1:Yes:over1.5, x:yes:under2.5,2:No:over1.5
                 * @bettype for "3 Combo 1.5" and its variants, represented as "3 Combo" on excel sheet
                 *    * "team": "X/GG/Un",
                 */
//                return $matchInfo;
                $filter = explode(' ',$matchInfo['type']);
                $new_format["type"] = "3 Combo";
                $new_format["outcome"] = trim( str_replace(['/','Ov','OV','NG','GG','Un'],[':','over','over','No','Yes','under'],$matchInfo['team']) ).$filter[2];
            }
            elseif( $new_format["type"] == "1X2 & GG/NG" ){ //
                /**
                 * @outcome 1:Yes, x:yes,2:yes, 2:No,1:No, x:no
                 * @bettype "1X2 & GG/NG"
                 */
                $filter= explode(' & ',$matchInfo['team']);
                if($filter[1] == "GG"){
                    $new_format["outcome"] = $filter[0].':'."Yes";
                }else{
                    $new_format["outcome"] = $filter[0].':'."No";
                }

            }
            elseif( preg_match("/^((H|F)T\s*(&)\s*O\s*\/\s*U\s*\d\.\d)$/i", $new_format["type"]) ){
                /**
                 * @outcome 1:over1.5,1:under2.5,1:over2.5
                 * @bettype "HT&O/U 1.5" and its variants represented on the sheet as
                 */

                $filter = explode('&',$matchInfo['team']);
                $new_format['ovalue']  = array_reverse(explode(' ',$matchInfo['type']) )[0];
                $new_format['outcome'] =explode(' ',$filter[0])[0].':'.(explode(' ',$filter[1])[1]).$new_format['ovalue'];
                $new_format['type'] = trim(str_ireplace($new_format['ovalue'],'',$new_format['type']) );
            } elseif( preg_match("/^(HT\/FT\s*(&)\s*O\s*\/\s*U\s*\d\.\d\s*(H|F)T)$/i", $new_format["type"]) ){
                /**
                 * @outcome 1/1:over1.5,x/1:under2.5,2/1:over2.5
                 * @bettype "HT/FT & O/U HT"
                 */
                $new_format['ovalue'] =  explode(' ',$matchInfo['type'])[3];
                $new_format['type'] = trim(str_ireplace($new_format['ovalue'],'',$new_format['type']) );
                $new_format['outcome'] =trim(str_ireplace(['HT','+','ov','un',' '],['',':','over','under',''], $matchInfo['team']) ).$new_format['ovalue'];
            }
            elseif( preg_match("/^(HT\/FT\s*(&)\s*O\s*\/\s*U\s*\d\.\d)$/i", $new_format["type"]) ){
                /**
                 * @outcome 1/1:over1.5,x/1:under2.5,2/1:over3.5
                 * @bettype for "HT/FT & O/U 1.5" and variants represented as "HT/FT & O/U" on excel
                 */
                $new_format['ovalue'] =  explode(' ',$matchInfo['type'])[3];
                $new_format['type'] = trim(str_ireplace($new_format['ovalue'],'',$new_format['type']) );
                $new_format['outcome'] =str_ireplace(['HT','+','ov','un','&',' '],['',':','over','under',':',''], $matchInfo['team']).$new_format['ovalue'];
            }
            elseif( preg_match("/^(1X2)\s*(&)\s*(((O\s*\/\s*U)\s*\d.\d))$/i", $new_format["type"]) ){
                /**
                 * @outcome 1:over1.5,x:under2.5,2:over3.5
                 * @bettype for "1X2 & O/U 1.5" and variants represented as "1X2 & O/U" on excel
                 */

                $filter= explode(' ',$matchInfo['type']);
                $new_format['ovalue'] =  array_reverse($filter)[0];
                $new_format['type'] =  "1X2 & O/U";
                $new_format["outcome"] = str_ireplace([' ','&'],['',':'],$matchInfo['team']).$new_format['ovalue'];
            }elseif( preg_match("/^((DC))\s*(&)\s*(((O\s*\/\s*U)\s*\d.\d))$/i", $new_format["type"]) ){
                /**
                 * @outcome 1x:over2.5, x2:over3.5,12:over1.5,1x:under3.5, x2:under3.5,12:under3.5
                 * @bettype for "DC&O/U 1.5" and variants represented as "DC&O/U" on excel
                 */
//                      return $matchInfo;
                $filter= explode(' ',$matchInfo['type']);
                $new_format['ovalue'] =  array_reverse($filter)[0];
                $new_format["outcome"] = str_ireplace([' ','&'],['',':'],$matchInfo['team']).$new_format['ovalue'];
                $new_format["type"] = "DC&O/U";
            }
            elseif($matchInfo["type"]=="DC & GG/NG" || $matchInfo["type"]=="DC & GG/GG" ){
                /**
                 * @outcome 1x:yes, x2:Yes,12:Yes,1x:No, x2:No,12:No
                 * @bettype for "DC & GG/NG"
                 */
                $filter= explode(' ',$matchInfo['team']);
                $new_format['ovalue'] =  array_reverse($filter)[0];
                if($new_format['ovalue'] =="GG"){
                    $new_format["outcome"] = trim($filter[0]).":Yes";
                }else{
                    $new_format["outcome"] = trim($filter[0]).":No";
                }
            }elseif( preg_match("/^(Home\/Away)\s*((O\/U)\s*\d.\d\s*HT)$/i", $new_format["type"])) {
                /**
                 *   Home/Away O/U 1.5 HT
                 * @outcome over1.5,over2.5,under3.5,under1.5
                 * @bettype for "HA1HOU 1.5" and its variants represented as "Home HT OV/UN & Away HT OV/UN" on sheet
                 * this and the next elseif are thesame thing
                 * there is an inconsistency in the bookmarker when transfering the data
                 */
//                return $matchInfo;
                // H/A 1H Ov/Un 0.5
                $filter= explode(' ',strtolower($matchInfo["team"]) );

                if (in_array('home',$filter)) {
                    if(in_array('ov', $filter) ){
                        $new_format["outcome"] = "over".$filter[1];
                    }else{
                        $new_format["outcome"] = "under".$filter[1];
                    }
                    $new_format['type'] = "Home HT OV/UN";
                }else{
                    if(in_array('un', $filter) ){
                        $new_format["outcome"] = "under".$filter[1];
                    }else{
                        $new_format["outcome"] = "under".$filter[1];
                    }
                    $new_format['type'] = "Away HT OV/UN";
                }

            }
            elseif( preg_match("/^(H\/A)\s*(1|2)H\s*((Ov\/Un)\s*\d.\d)$/i", $new_format["type"] ) ){
                /**
                 * @outcome over1.5,over2.5,under3.5,under1.5
                 * @bettype for "H/A 2H Ov/Un 1.5" and its variants represented as "Home 2H OV/UN & Away 2H OV/UN" on sheet
                 */
//                return $matchInfo;
                $filter= explode(' ',strtolower($matchInfo["team"]) );
                $new_format['ovalue'] =  array_reverse($filter)[0];
                if ( $filter[0] =='h') {
                    if( in_array('over',$filter) ){
                        $new_format["outcome"] = "over".$new_format['ovalue'];
                    }else{
                        $new_format["outcome"] = "under".$new_format['ovalue'];
                    }
                    $new_format['type'] = "Home 2H OV/UN";
                } else{
                    if(in_array('under',$filter) ){
                        $new_format["outcome"] = "over".$new_format['ovalue'];
                    }else{
                        $new_format["outcome"] = "under".$new_format['ovalue'];
                    }
                    $new_format['type'] = "Away 2H OV/UN";
                }

            }
            elseif( preg_match("/^(H\/A)\s*(O\s*\/\s*U)\s*(\d.\d)$/i", $new_format["type"] ) ){
                /**
                 * @outcome over1.5,over2.5,under3.5,under1.5
                 * @bettype for "H/A U/O 1.5" and its variants represented as "Home Team Over/Under and Away Team Over/Under" on sheet
                 * revisiting started here
                 */
//                return $matchInfo;
                $filter= explode(' ',strtolower($matchInfo["team"]) );
                $new_format['ovalue'] =  array_reverse(explode(' ',$matchInfo['type']) )[0];
                $new_format["outcome"] = $filter[0].$new_format['ovalue'];
                if($filter[1] =="home"){
                    $new_format["type"]= "Home Team Over/Under";
                }else{
                    $new_format["type"]="Away Team Over/Under";
                }


            }elseif( preg_match("/^(O\s*\/\s*U)\s*\d(.)\d/i", $new_format["type"]) ){
                /**
                 * @outcome over1.5,under2.5, etc
                 * @bettype "O/U 1.5" AND ITS VARIANTS
                 */

                $filter= explode(' ',str_ireplace('O / U','O/U',$matchInfo['type']) );
                $new_format['ovalue'] = trim($filter[1]);
//                return $matchInfo['team'];
//                if(){ // e.g O/U 1.5 HT
                if(isset($filter[2])){ // e.g O/U 1.5 HT
                    /**
                     * @bettype for "O/U 1.5 HT" and its variants represented as "O/U HT AND O/U 2HT" on sheet
                     */
                    $matchInfo['team'] = explode(' ',$matchInfo['team']);
                    $new_format["outcome"] = $matchInfo['team'][0].$new_format['ovalue'];
                    $new_format["type"] = "o/u ".$filter[2];
                }else{
                    /**
                     * @bettype for "O/U 1.5" and its variants represented as "O/U" on sheet
                     */
                    $new_format["outcome"] = $matchInfo['team'].$new_format['ovalue'];
                    $new_format["type"] = trim("O/U");
                }
            }elseif( preg_match("/^((O\s*\/\s*U)\s*Cards\s*\d(.)\d)$/i", $new_format["type"]) ){
//        "team": "Ca Under 3.5",
//        "type": "O/U Cards 3.5",
                /**
                 * @outcome over1.5,over2.5,under3.5,under1.5
                 * @bettype for "O/U Cards 1.5" and its variants represented as "O/U Cards" on sheet
                 * revisiting started here
                 */
                $filter = str_ireplace(['Ca ',' '],'',$matchInfo['team']);
                $new_format["outcome"] = $filter;
                $new_format["type"] = "O/U Cards";
            }elseif( preg_match("/^((Home)|(Away)\s*Cards\s*(O\s*\/\s*U)\s*\d(.)\d)$/i", $new_format["type"]) ){
                //  Home Cards O/U 0.5

                $new_format["outcome"] = str_ireplace(['Home','Cards',' '],[''], $matchInfo['team']);
            }elseif( preg_match("/^(AwayCardsOv\/Un\d+)$/i", $new_format["type"]) ){
                //  AwayCardsOv/Un05
                $new_format["outcome"] =str_ireplace("AwayCards",'',$matchInfo['team']);
            }elseif( preg_match("/^(HT\s*O\/U\s*Cards\s*\d.\d)$/i", $new_format["type"]) ){
                //  HT O/U Cards 0.5
                /**
                 * @outcome over1.5,over2.5,under3.5,under1.5
                 * @bettype for "HT O/U Cards 0.5" and its variants represented as "HT O/U Cards" on sheet
                 * revisiting started here
                 */
                $new_format["type"]="HT O/U Cards";
                $new_format["outcome"] = str_ireplace(['Ca',' '],'',$matchInfo['team']);//str_ireplace("AwayCards",'',$matchInfo['team']);
            }elseif( preg_match("/^(2HT\s*O\/U\s*((CA)|Cards)\s*\d.\d)$/i", $new_format["type"]) ){
                //  HT O/U Cards 0.5
                /**
                 * @outcome over1.5,over2.5,under3.5,under1.5
                 * @bettype for "2HT O/U CA 2.5" and its variants represented as "2HT O/U CA" on sheet
                 * revisiting started here
                 */
                $new_format["type"]="2HT O/U CA";
                $new_format["outcome"] = str_ireplace(['Card','2HT',' '],'',$matchInfo['team']);//str_ireplace("AwayCards",'',$matchInfo['team']);
            }elseif( preg_match("/^(H\/A)\s*(Team)\s*(O\s*\/\s*U)\s*\d(.)\d/i", $new_format["type"]) ){
                // H/A O/U 0.5, H/A Team O/U 3.5
                $filter= array_reverse(explode(' ',$matchInfo['type']) );
                $new_format['ovalue'] = trim($filter[0]);
                $team = explode(' ',$matchInfo['team']);
                $new_format["outcome"] = $team[0][0].':'.$team[1][0].$new_format['ovalue'];
            }elseif(preg_match("/((C.Score)\s*Multi\d*)$|(HT\/FT C. Score)/i", $new_format["type"]) ){ // C.Score Multi
                $filter= explode('-',$matchInfo['team']);
                $new_format['ovalue'] = substr_count($matchInfo['team'],'-');
                $new_format["outcome"] = str_replace([' ','-'],['',':'],$matchInfo['team']);
                if(substr_count('Multi',$new_format["type"]) > 0){
                    $new_format["type"]="c.score multi";
                }
            }elseif(preg_match("/^(2HT C. Score)$/i", $new_format["type"]) ){ // C.Score Multi
                $filter= explode(' ',$matchInfo['team']);
                $new_format['ovalue'] = substr_count($matchInfo['team'],'-');
//                $outcome = str_replace(" / ",'^',str_replace('-',':',$matchInfo['team']));
                $new_format["outcome"] = str_replace([' ','-'],['',':'],$filter[0]);
                //    $new_format["outcome"] = str_replace([' ','-'],['',':'],$matchInfo['team']);
            }elseif(preg_match("/^(Corner\s*\d+.\d+O\s*\/\s*U\s*(1|2)HT)$/i", $matchInfo["type"]) ){
                //   "team": "Corner Ov 4,5 1HT",
                //        "type": "Corner 4.5O/U 1HT",
                // Corner 2.5O/U 2HT
//                return $matchInfo;
                /**
                 * @outcome under1.5, under2.5, over1.5, over3.5
                 * @bettype "Corner 2.5O/U 1HT" and variants represented as Corner O/U 1H & Corner O/U 2H  on the sheet
                 */

                $remove_OU_from_type = str_ireplace('O/U','',$matchInfo["type"]);
                $new_format["ovalue"] = explode(' ',$remove_OU_from_type)[1];
                $get_over_or_under = strtolower(explode(' ',$matchInfo["team"])[1]);
                if(trim($get_over_or_under)[0] =='o'){
                    $new_format["outcome"] = "over".$new_format["ovalue"] ;
                }else{
                    $new_format["outcome"] = "under".$new_format["ovalue"] ;
                }
                if(explode(' ',$matchInfo["type"])[2] =="1HT"){
                    $new_format["type"] = "Corner O/U 1HT" ;
                }else{
                    $new_format["type"] = "Corner O/U 2HT" ;
                }

            }elseif(preg_match("/(Highest Scoring Half)|(Half Most Card)/i", $new_format["type"]) ){ // for Highest Scoring Half
                $new_format["outcome"] =  $matchInfo['team'] =='Equal' ? strtolower($matchInfo['team'][0]) : $matchInfo['team'][0].'h';
            }elseif(preg_match("/^(Home O\s*\/\s*U\s*Corner\s*\d\.\d)$/i", $new_format["type"]) ){
                /***
                 * @outcome under1.5, under2.5, over1.5, over3.5
                 * @bettype "Home O/U Corner 2.5" and variants represented as Home Corner O/U on the sheet
                 * @bettype "Away O/U Corner 2.5" and variants represented as Away Corner O/U on the sheet for future bookmarker upgrade
                 */
                $filter = explode(" ",$matchInfo["team"])[1];
                $variant = array_reverse(explode(" ",$matchInfo["type"]))[0];
                $new_format["outcome"] = trim($filter).$variant;
                $new_format["ovalue"] = $variant;
                $new_format["type"]  = "Home O/U Corner";
            }elseif(preg_match("/^(Away O\s*\/\s*U\s*Corner\s*\d\.\d)$/i", $new_format["type"]) ){
                /***
                 * @outcome under1.5, under2.5, over1.5, over3.5
                 * @bettype "Home O/U Corner 2.5" and variants represented as Home Corner O/U on the sheet
                 * @bettype "Away O/U Corner 2.5" and variants represented as Away Corner O/U on the sheet for future bookmarker upgrade
                 */
                $filter = explode(" ",$matchInfo["team"])[1];
                $variant = array_reverse(explode(" ",$matchInfo["type"]))[0];
                $new_format["outcome"] = trim($filter).$variant;
                $new_format["ovalue"] = $variant;
                $new_format["type"]  = "Away O/U Corner";
            }elseif(preg_match("/^(Away Corner O\s*\/\s*U \d+.\d+)$/i", $new_format["type"]) ){
                /***
                 * @outcome under1.5, under2.5, over1.5, over3.5
                 * @bettype "Away Corner O/U 3.5" and variants represented as Away Corner O/U on the sheet
                 */
                $filter = str_ireplace("Away Corner",'',$matchInfo["team"]);
                $new_format["ovalue"]  = array_reverse(explode(" ",$matchInfo["type"]))[0];
                $new_format["type"]  = "Away Corner O/U";
                $new_format["outcome"] = str_ireplace([" ",','],['','.'],$filter);
            }elseif(preg_match("/^(Half 1st Goal Home)|^(Half 1st Goal Away)$/i", $new_format["type"]) ){ // Half 1st Goal Home, Half 1st Goal Away
                /**
                 * @outcome 2, none, 1
                 * @bettype for "Half 1st Goal Home" and "Half 1st Goal Away"
                 */
                $m_outcome = explode(' ',$matchInfo['team'])[0];
                $new_format["outcome"] = str_ireplace(['no','first','second'],['none','1','2'],trim($m_outcome));
            }elseif(preg_match("/^((Team|Home|Away) (Sc.)*(3|2) in a Row)$/i", $new_format["type"]) ){ //Team Sc.2, Sc.3 in a Row,Away 2 in a Row
                $new_format["outcome"] =  explode('-',$matchInfo['team'])[0];
                $new_format["type"] = strtolower( $new_format["type"]);
            }elseif(  preg_match("/^((2HT)|(HT))\s*(DC)\s*(&)\s*(O\s*\/\s*U)$/i", $new_format["type"])){
                /**
                 * @outcome 1X:over1.5, 1X:under1.5, 12:over1.5, X2:under1.5
                 * @bettype "HT DC&OU 1.5" and variants represented as HT DC&O/U
                 */

                $filter = str_ireplace(['HT',' '],[''],$matchInfo['team']);
                $new_format["outcome"] = str_ireplace(['&ov','&un'],[':over',':under'],$filter);
                $new_format["type"] = strtolower( $new_format["type"]);
            }elseif(  preg_match("/^((2HT)|(HT))\s*(DC)\s*(&)\s*(GG\/NG)$/i", $new_format["type"])){
                $filter = str_ireplace(['HT',' '],[''],$matchInfo['team']);
                $pos = str_ireplace(['GG','NN','&'],['Yes','No',':'],$filter);
                $align = explode(':',$pos);
                $new_format["outcome"] = $align[0][0].'/'.$align[0][1].':'.str_ireplace(['GG','NG'],['Yes','No'],$align[1]);//str_ireplace(['GG','NN'],['Yes','No'],$filter);
                $new_format["type"] = strtolower( $new_format["type"]);
            }elseif( preg_match("/^((2HT)\s*(1X2)\s*(&)\s*(GG\/NG))$/i", $new_format["type"])){
                /**
                 * @outcome 1:Yes,2:yes,:x:yes,1:no,2:no,:x
                 * @bettype for "2HT 1X2 & GG/NG"
                 */

                $filter = explode( ' ',$matchInfo['team']);
                $new_format["outcome"] = $filter[0].':'.str_ireplace(['GG','NG'],['Yes','No'],$filter[3]);
            }
            elseif( preg_match("/^((1X2)\s*(HT)\s*(&)GG\/NG)$/i", $new_format["type"])  ){
                /**
                 * @outcome 1:Yes,2:yes,:x:yes,1:no,2:no,:x:no
                 * @bettype for "1X2 HT &GG/NG"
                 */
                //1X2 HT &GG/NG
                //"type": "1x2 ht &gg/ng",
                $filter = str_ireplace(['HT',' '],[''],$matchInfo['team']);
                $filter = explode('&',$filter);
                if($filter[1]=="GG"){
                    $pos = "Yes";
                }else{
                    $pos = "No";
                }
                $new_format["outcome"] = $filter[0].':'.$pos;
            }elseif(preg_match("/^((2HT)\s*(1X2)\s*(&)\s*(O\s*\/\s*U)\s*\d.\d)$/i", $new_format["type"])){
//                return $matchInfo;
                /**
                 * @outcome 2:over1.5,1:over1.5,x:over1.5,2:under1.5,1:under1.5,x:under1.5
                 * @bettype for "2HT 1X2 & O/U 1.5" and variants represented as "2HT 1X2 & O/U" on the sheet
                 */
                $filter = str_ireplace(['2HT',' '],[''],$matchInfo['team']);
                $filter = str_ireplace(['15','1,5'],['1.5'],$filter);
                $new_format["outcome"] = str_ireplace(['&ov','&un'],[':over',':under'],$filter);
                $new_format["type"] = "2HT 1X2 & O/U";
            }elseif(preg_match("/^((DC)\s*(2HT)\s*(&)\s*(O\s*\/\s*U)\s*\d.\d\s*(2HT))$/i", $new_format["type"])){

                $new_format["type"] = "2HT DC&O/U";
                $filter = str_ireplace(['2HT',' '],[''],$matchInfo['team']);
                $new_format["outcome"] = str_ireplace(['&un','&ov'],[':under',':over'] ,$filter);
            }elseif(in_array($new_format["type"],$group_one)){
                $new_format["outcome"] = trim($matchInfo['team'])[0];
                switch($new_format["type"]){
                    case "Half Time": $new_format["type"] = "1x2 - ht";
                        break;
                }
            }elseif(in_array($new_format["type"],$first_last_goal)){
                $new_format["outcome"] = trim($matchInfo['team']);
                if($matchInfo["team"]=="No Goal"){
                    $new_format["outcome"] = "none";
                }
            }
            elseif(in_array($new_format["type"],$both_team_array)){
                switch($matchInfo["team"]){
                    case "GG": case "GG 2HT": case "GG HT":
                    $new_format["outcome"] = "Yes";
                    break;
                    case "NG":  case "NG 2HT": case "NG HT":
                    $new_format["outcome"] = "No";
                    break;
                }
            }elseif(in_array($new_format["type"],$group_two)){
                if($matchInfo['team'] = explode(' ', $matchInfo['team'] )[0] ){
                    $new_format["outcome"] = trim($matchInfo['team']);
                }else{
                    $new_format["outcome"] = trim($matchInfo['team']);
                }
                switch($new_format["type"]){
                    case "Home To Score" :  case "Away To Score" :
                    $new_format["type"] = strtolower($new_format["type"]);
                    break;
                }
                switch($new_format["type"]){
                    case "GG/NG 2+": $new_format["type"] = trim("GG/NG");;
                        break;
                    case "GG/NG 1st & 2nd Half": $new_format["outcome"] = str_ireplace(['gg','ng','&'],['Yes','No',':'],$matchInfo['team']);
                        break;
                    case "GG/NG HT": $new_format["type"] =  "gg/ng ht";
                        break;
                    case "GG/NG 2HT": $new_format["type"] =  "gg/ng 2ht";
                        break;

                }
            }elseif(in_array($new_format["type"],$fl_card)){
                //LastCard, FirstCard,
                /**
                 * @outcome 1,x,2
                 * @bettype for "FirstCard" and "LastCard"
                 */
                $new_format["outcome"] = trim(str_ireplace($matchInfo['type'],'',$matchInfo['team']) );
            }elseif($new_format["type"] =='Correct Score'){
                $new_format["outcome"] = trim(str_ireplace('-',':',$matchInfo['team']) );
            }elseif(preg_match("/^(((GG\/NG))\s*&\s*O\s*\/\s*U\s*\d(,|\.)\d)$/",$new_format["type"])){

                /**
                 * @outcome Yes:over1.5,No:over2.5,Yes:over3.5,No:under4.5,Yes:under1.5,NO:under1.5
                 * @bettype for "GG/NG & O/U 2,5" and variants represented as "GG/NG & O/U" on the sheet
                 */
                $new_format['ovalue'] = str_ireplace(',','.',array_reverse(explode(' ',$matchInfo['type']))[0] );
                $new_format["outcome"] = trim(str_ireplace([' & ','gg','ng'],[':','Yes','No'],$matchInfo['team']).$new_format['ovalue'] );
                $new_format["type"] ="GG/NG & O/U";
            }elseif($new_format["type"] =='1X2 Cards'){
                //1X2 Cards
                $new_format["outcome"] = trim(str_ireplace('1X2 Cards','',$matchInfo['team']) );
            }elseif($new_format["type"] =='Half Most Corner') {
                /**
                 * @outcome 1,e,2
                 * @bettype for "Half Most Corner"
                 */
                $new_format["outcome"] = trim(str_ireplace(['Half Most Corner -',' X'], ['','e'], $matchInfo['team']));
            }elseif($new_format["type"] =='FirstHalfCards1X2'){
                //FirstHalfCards1X2
                $new_format["outcome"] = trim(str_ireplace('FirstHalfCards','',$matchInfo['team']) );
            }elseif($new_format["type"] =='Cor HT/FT'){ //Cor HT/FT
                //Corner 1/2
                /**
                 * @outcome 1/1,1/x/,1/2, x/1,x/x,x/2,2/1,2/x,2/2
                 * @bettype for "Cor HT/FT"
                 */
                $new_format["outcome"] = trim(str_ireplace('Corner ','',$matchInfo['team']) );
            }elseif( preg_match("/^Corner Handicap \((-)|(\d)\)/",$new_format["type"]) ){ // Corner Handicap (1)
//                Corner Handicap (-1)
//                "team": "Corner HND 1H (1)",
                //   Corner Handicap (-1)
                /**
                 * @outcome 1h(1),xh(2),2h(-1),xh(-2),1h(-3)
                 * @bettype for "Corner Handicap (-2)" and its variants represented as "Corner Handicap" on the sheet
                 */


                $filter = explode(" ",$matchInfo["team"]);
                $new_format["outcome"] =  trim($filter[2]).$filter[3];
                $new_format["ovalue"] =$filter[3];
                $new_format["type"] = trim(str_ireplace($filter[3], '',$new_format["type"]) );
            }elseif($new_format["type"] =='Total1H/2H'){
                /**
                 * @outcome  1+/1+:y,1+/1+:n,1+/2+:n,2+/2+:n,1-/1-:y,3-/1-:n
                 * @bettype Total1H/2H
                 */
                $new_format["outcome"] = trim(str_ireplace([' ','&','y','n'],['','/',':y',':n'],$matchInfo['team']) );
            }elseif($new_format["type"] =='HT C.Score' ||  $new_format["type"] =='2HT C.Score'){
                $new_format["outcome"] = trim(str_ireplace('-',':',explode(' ',$matchInfo['team'])[0]) );
            }elseif($new_format["type"] =='Correct Score HT'){
                /**
                 * @outcome 0:0,0:1, etc
                 * @bettype for "Correct Score HT" and its variants represented as "Correct Score HT" on sheet
                 * You have to predict the correct score of the first half time of the match or the correct score of the second half time of the match,
                 * without considering the goals scored during the first half time.
                 */
                $new_format["outcome"] = trim(str_ireplace('-',':',explode(' ',$matchInfo['team'])[0]) );
            }elseif($new_format["type"] =='HSH Home' || $new_format["type"] =='HSH Away'){
                $new_format["outcome"] = explode(' ',$matchInfo['team'])[0];
                if($new_format["outcome"] =="Equal"){
                    $new_format["outcome"] ='e';
                }else{
                    $new_format["outcome"] .='h';
                }
            }elseif($new_format["type"] =='HT/FT'){
                $new_format["outcome"] = $matchInfo['team'];
            }elseif($new_format["type"] =='Number Corner'){
                /**
                 * @outcome <7,8,9,10, ....., 20+
                 * @bettype for "Number Corner"
                 */
                $new_format["outcome"] = trim(str_replace(['Corner',' '],'',$matchInfo['team']) );
            }elseif($new_format["type"] =='Win Margin'){
                if($matchInfo['team'] =="Draw"){
                    $new_format["outcome"] = 'x';
                }else{
                    $filter = str_ireplace(['by','home','away','Goals',' Goal',' '],['by:','1','2',''],$matchInfo['team']);
                    $new_format["outcome"] = $filter;//$filter[0].':'.trim( );
                }
            }elseif(preg_match("/((H|A)\s*Score\s*Both\s*Halves)|(Score 2HT)|(Wins (Both|Either) Halves)/", $new_format["type"]) ){
                // to win nil, Home Score 2HT,H Wins Both Halves,Wins Either Halves, H Score Both Halves, A Score Both Halves
                $new_format["outcome"] = trim(explode('-',$matchInfo['team'])[0]);
            }elseif(strtolower($new_format["type"]) == "home win to nil ht" || strtolower($new_format["type"]) == "away win to nil ht"
                || strtolower($new_format["type"]) == "home win to nil 2ht" || strtolower($new_format["type"]) == "away win to nil 2ht"){
                /**
                 * @outcome Yes or No
                 * @bettype for "home win to nil ht" and its variants
                 * revisiting started here
                 */
                $new_format["outcome"] = trim(explode('-',$matchInfo['team'])[0]);
            }
            elseif(  $new_format["type"] ==='Handicap' || $new_format["type"] ==='Handicap HT'|| $new_format["type"] ==='Handicap 2HT'){
                $type_parts = explode(")", str_replace( "(","", $matchInfo['team'] ) );
                $new_format["ovalue"] = trim($type_parts[0]);
                $new_format["outcome"] = trim($type_parts[1])[0].":".$new_format["ovalue"]; // 2 H become 2
            }elseif($new_format["type"] =='MultiGoal' || preg_match('/(Multi Goal)$/',$new_format["type"] )){
                $new_format["outcome"]  = trim(str_replace( "Goals","", $matchInfo['team'] ) );
            }elseif(preg_match('/(Multi Gol (1|2)H)/',$new_format["type"] )){
//                $new_format["outcome"]  =$matchInfo['team'];
                $new_format["outcome"]  = trim(str_replace( ["Multi Gol 1H","Multi Gol 2H"],[''], $matchInfo['team'] ) );
            }elseif(preg_match('/(Tot. MultiGoal (Home|Away))/',$new_format["type"] )){
                //Home 2-3
                $filter = explode(" ",$matchInfo["team"]);
                $new_format["outcome"]  = trim($filter[1] );
            }elseif($new_format["type"] =="Chance Mix" || $new_format["type"] =="Chance Mix +"){ //Chance Mix , Chance Mix +
                if($matchInfo['type'] =="Chance Mix"){
                    /***
                     * @outcome X:Yes,x:no,1:yes,1:no,2:yes,2:yes
                     * @bettype Chance Mix
                     */

                    $filter = explode(' or ',$matchInfo['team']);
                    $ls = str_ireplace(['GG','NG'],['Yes','No'],$filter[1]);
                    $new_format["outcome"]  = $filter[0].':'.$ls;
                }else{
                    /***multiscore
                     * @outcome 1:HT/No:HT,1:HT/1:FT,Yes:HT/Yes:2HT
                     * @bettype Chance Mix +
                     */
                    // $filter = str_ireplace(]'or'],['/'],$matchInfo['team']);
                    $filter = str_ireplace(['Gol','GG','NG',' or '],['','Yes','No','/'],$matchInfo['team']);
                    $new_format["outcome"]  = str_ireplace([' '],[':'],$filter);
                }

            }elseif( preg_match("/^(Chance Mix)\s*\d.\d/i",$new_format["type"]) ){
                /***
                 * @outcome 1:under1.5,x:under1.5,2:under1.5,1:over1.5,x:over1.5,2:over1.5
                 * @bettype "Chance Mix 1.5" and variants represented as Chance Mix O/U on the sheet
                 */

                $filter = explode(' or ',$matchInfo['team']);
                $variant = explode(' ',$matchInfo['type'])[2];
                if($filter[1] =="ov"){
                    $new_format["outcome"]  = $filter[0].':over'.$variant;
                }else{
                    $new_format["outcome"]  = $filter[0].':under'.$variant;
                }
                $new_format["type"]  = "Chance Mix O/U";
            }elseif( preg_match("/^(3 Chance Mix)\s*\d.\d/i",$new_format["type"]) ){
                /***
                 * @outcome 1:Yes:under1.5, 2:No:under1.5, 1:No:over1.5, 2:Yes:over1.5
                 * @bettype "3 Chance Mix 2,5" and variants represented as 3 Chance Mix O/U on the sheet
                 */
//                return $matchInfo;
                $variant = explode(' ',str_ireplace(',','.',$matchInfo['type']))[3];
                $filter = str_ireplace(['GG','NG','ov','un',' or '],['Yes','No','over'.$variant,'under'.$variant,':'],$matchInfo['team']);
                $new_format["outcome"]  = $filter;
                $new_format["type"]  = "3 Chance Mix";
                $new_format["ovalue"]  = $variant;

            }elseif( in_array($new_format["type"],$penalty) ){ //PenaltyScored/Missed. Penality Scored
                if( $filter= explode(' ',$matchInfo['team'] )){
                    $new_format["outcome"]  = trim(array_reverse($filter)[0]);
                }else{
                    $new_format["outcome"]  = trim($matchInfo['team'] );
                }
            }elseif( preg_match("/((DC|1X2) & MultiGoal \d-\d)/",$new_format["type"]) ){ //DC & MultiGoal 1-2
                // 1X2 & MultiGoal 1-2
                //  1X2 & MultiGoal 1-3
                /**
                 * @outcome 1:1-2,x:1-2,2:1-2 FOR  1X2 & MultiGoal & 1x:1-2,12:1-2,x2:1-2
                 * @bettype for "1X2 & MultiGoal 1-2" and its variants represented as "1X2 & MultiGoal" on sheet
                 * @bettype for "DC & MultiGoal 1-2" and its variants represented as "DC & MultiGoal" on sheet
                 */
                $filter = str_ireplace(['Goal'],[''],$matchInfo['team'] );
                $filter = explode('&',$filter);
                $new_format["outcome"]  = trim($filter[0]).':'.trim($filter[1]);
            }elseif( preg_match("/^((1X2)\s*HT\s*\/\s*DC FT)$/i",$new_format["type"]) ){
                /**
                 * @outcome 1X/1, 1X/X, 1X/2, 12/1, 12/X, 12/1, X2/1, X2/X, X2/2
                 * @bettype for "1X2 HT / DC FT"
                 */
                $new_format["outcome"]  =$matchInfo['team'] ;
            }
            elseif( preg_match("/^((DC)\s*HT\s*\/\s*1X2 FT)$/i",$new_format["type"]) ){
                /**
                 * @outcome 1X/1, 1X/X, 1X/2, 12/1, 12/X, 12/1, X2/1, X2/X, X2/2
                 * @bettype for "DC HT / 1X2 FT"
                 */
                $new_format["outcome"]  =$matchInfo['team'] ;
            }elseif($new_format["type"] =='First Goal & 1X2'){// "First Goal & 1X2",
                if($matchInfo['team']=="2-1st Goal & 2"){
                    $new_format['outcome'] = "agoal:2";
                }elseif($matchInfo['team']=="1-1st Goal & 1"){
                    $new_format['outcome'] = "hgoal:1";
                }elseif($matchInfo['team']==" 1-1st Goal & X"){
                    $new_format['outcome'] = "agoal:x";
                }elseif($matchInfo['team']=="1-1st Goal & 2"){
                    $new_format['outcome'] = "hgoal:2";
                }elseif($matchInfo['team']=="2-1st Goal & X"){
                    $new_format['outcome'] = "agoal:x";
                }elseif($matchInfo['team']=="2-1st Goal & 1"){
                    $new_format['outcome'] = "agoal:1";
                }
                elseif($matchInfo['team']=='None Goal (0-0)'){
                    $new_format['outcome'] = "ngoal";
                }
            }elseif($new_format["type"] =="Half 1st Goal"){
                $m_outcome = explode(' ',$matchInfo['team'])[0];
                $new_format["outcome"] = str_ireplace(['no','first','second'],['none','1','2'],trim($m_outcome));
            }elseif( in_array($new_format["type"], $corner_group_one) ){ // 1X2 Corner HT /////////////////////////////////////////////
                $filter = explode('-',$matchInfo['team']);
                $new_format['outcome'] = trim(array_reverse($filter)[0]);
            }elseif( preg_match("/^(O\/U\s+Corner\s+\d+\.\d)$/", $matchInfo['type']) ){
                /**
                 * @outcome over5.5, under3.5, under6.5, 0ver9.5
                 * @bettype for "O/U Corner 5.5" and its variants represented as "1X2 & MultiGoal" on sheet
                 */
                $filter = explode(' ',$matchInfo['team']);
                $new_format['ovalue'] = array_reverse(explode(' ',$matchInfo['type']))[0];
                if(count($filter) <3){
                    $new_format['outcome'] = $filter[0].$new_format['ovalue'];
                }else{
                    $new_format['outcome'] = $filter[1].$new_format['ovalue'];
                }
                $new_format['type'] = trim(str_ireplace( [$new_format['ovalue']] ,'',$matchInfo['type']) );
            }elseif($new_format["type"] =="Minute 1st Goal" || $new_format["type"] =="Minute First Goal"){
                /**
                 * @outcome  0-15,16-30,31-45+,46-60,61-75, 76-90+, No
                 * @bettype for "Minute First Goal"
                 */
                $filter = explode(' ',$matchInfo['team'])[0];
                $new_format['outcome'] = $filter;
            }elseif($new_format["type"] =="Minute Last Goal"){
                /**
                 * @outcome  0-15,16-30,31-45+,46-60,61-75, 76-90+, No
                 * @bettype for "Minute Last Goal"
                 */
                $filter = explode(' ',$matchInfo['team'])[0];
                $new_format['outcome'] = $filter;
            }elseif($new_format["type"] =="Team To Score"){
                if($matchInfo['team'] =='GG'){
                    $new_format["outcome"]  = 'bothteams';
                }elseif($matchInfo['team'] =="Only Away"){
                    $new_format["outcome"]  = 'onlyaway';
                }elseif($matchInfo['team'] =='No Goal'){
                    $new_format["outcome"]  = 'none';
                }else{
                    $new_format["outcome"]  = 'onlyhome';
                }
            }elseif($new_format["type"] =="NumberCardsHome" || $new_format["type"] =="NumberCardsAway"){
                $new_format['outcome'] = str_ireplace(['NumberCardsHome','NumberCardsAway'],'',$matchInfo['team']);
            }
            else{
                // $file = fopen('./log.txt','a');
                // fwrite($file, date(DATE_RFC822)." Market type  not implemented  - {$matchInfo['type']}".PHP_EOL);
                // fclose($file);

                echo "{$matchInfo['type']} not found in if/else block";
            }

            $result[$output_homebookmaker][$output_awaybookmaker ][] = $new_format;
        }// end of response loop
        return StdClubChangerClass::changeClubandStd($result,$code);
    }

}