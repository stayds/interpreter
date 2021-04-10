
<?php
include_once './interfaces/BookmakerInterface.php';
/**
 * 
 */
class Betking implements BookmakerInterface {

    /**
     * @param $url
     */
    public $url = "https://sportsapi.betagy.services/api/BetCoupons/Booked/";

    /**
     * Default constructor
     */
    public function __construct() {
    }

    /**
     * Method call to bookmaker API
     * @param $code $booked game code from the homebookmaker
     * @return false|string|null
     */
    public function callBookMaker($code) {
        
        $url = $this->url . $code ."/en";
        $ch = curl_init();
        $getUrl = $url;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $getUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 80);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
                array('Accept:application/json'));
         
        $response = curl_exec($ch);

        curl_close($ch);

        $data = json_decode($response, true);

        if (!is_array($data['BookedCoupon']['Odds'])) {
            return null;
        }

        $cnt = count($data['BookedCoupon']['Odds']);
        $baseData = $data['BookedCoupon']['Odds'];

        for ($x = 0; $x < $cnt; $x++) {
            $dataArr['code'] = $code;
            $dataArr['league'] = $baseData[$x]['TournamentName'];
            $dataArr['sport'] = ($baseData[$x]['SportName']);
            $teams = explode(" - ", $baseData[$x]['MatchName']);
            $dataArr['home'] = trim($teams[0]);
            $dataArr['away'] = trim($teams[1]);
            $dataArr['odd'] = $baseData[$x]['OddValue'];
            $dataArr['ovalue'] = $baseData[$x]['SpecialValue'];
            $dataArr['type'] = trim($baseData[$x]['MarketName']);
            $dataArr['outcome'] = $baseData[$x]['SelectionName'];

            $dataArr['sport'] = str_replace('Soccer', 'football', $baseData[$x]['SportName']);
            $dataArr['sport'] = strtolower($dataArr['sport']);
            
            $dnb = json_encode($dataArr['outcome']);

            $bty = json_encode($dataArr['type']);


            if(strpos($dnb, 'DNB') !== false){
                $dataArr['outcome'] = str_replace('DNB', '', $dataArr['outcome']);
            }

            if(strpos($bty, 'FT Correct Score') !== false){
                $dataArr['outcome'] = str_replace(' ', '/', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('-', ':', $dataArr['outcome']);
            }
            
            if(strpos($bty, 'Half - 1X2 & Under') !== false){
                $dataArr['outcome'] = str_replace('+', ':', $dataArr['outcome']);
                // $dataArr['outcome'] = str_replace('-', ':', $dataArr['outcome']);
            }

            // if(strpos($bty, 'Half - 1X2 & Under') !== false){
            //     $dataArr['outcome'] = str_replace('+', ':', $dataArr['outcome']);
            //     // $dataArr['outcome'] = str_replace('-', ':', $dataArr['outcome']);
            // }

            if(strpos($bty, 'HT DC & GG') !== false){
                $dataArr['outcome'] = str_replace('+', ':', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('12', '1/2', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('1X', '1/x', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('X2', 'x/2', $dataArr['outcome']);
            }

            if(strpos($bty, 'Highest Scoring Half Home Team') !== false){
                $dataArr['outcome'] = str_replace('2', '2h', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('1', '1h', $dataArr['outcome']);
            }

            if(strpos($bty, 'Highest Scoring Half Away Team') !== false){
                $dataArr['outcome'] = str_replace('2', '2h', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('1', '1h', $dataArr['outcome']);
            }

            if($bty == "\"Winning Margins\""){
                $dataArr['outcome'] = str_replace('HT', '1by:', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('AT', '2by:', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('> 2', '2+', $dataArr['outcome']);
            }

            $outcome = explode("(", $baseData[$x]['MarketName']);
            $tnameh = str_replace('"', '', json_encode($outcome[0]));
            $tnameh = str_replace('\\', '', $tnameh);

            $outcomeM = explode("-", $baseData[$x]['MarketName']);
            $tnamem = str_replace('"', '', json_encode($outcomeM[0]));

            
            if(trim($tnameh) == "Handicap"){
                $tresult = str_replace(')', '', json_encode($outcome[1]));
                $tresult = str_replace('"', '', $tresult);
                $dataArr['type'] = trim($tnameh);
                $dataArr['outcome'] = $dataArr['outcome'] .' : '. $tresult;
                $dataArr['outcome'] = str_replace(' ', '',  trim($dataArr['outcome']));
            }

            else if(trim($tnameh) == "Asian Handicap"){
                $tresult = str_replace(')', '', json_encode($outcome[1])); 
                $tresult = str_replace('"', '', $tresult);
                $dataArr['type'] = trim($tnameh);
                $dataArr['outcome'] = $dataArr['outcome'];
                $dataArr['outcome'] = str_replace('1H:', '',  trim($dataArr['outcome']));
                $dataArr['outcome'] = str_replace('2H:', '',  trim($dataArr['outcome']));

            }else if(strpos($tnameh, "Handicap") !== False){
                $tresult = str_replace(')', '', json_encode($outcome[1]));
                $tresult = str_replace('"', '', $tresult);
                $dataArr['type'] = trim($tnameh);
                $dataArr['outcome'] = $dataArr['outcome'] .' : '. $tresult;
                $dataArr['outcome'] = str_replace(' ', '',  trim($dataArr['outcome']));
            }

            else if(strpos($tnameh, "Asian Handicap") !== False){
                $tresult = str_replace(')', '', json_encode($outcome[1])); 
                $tresult = str_replace('"', '', $tresult);
                $dataArr['type'] = trim($tnameh);
                $dataArr['outcome'] = $dataArr['outcome'];
                $dataArr['outcome'] = str_replace('H', '',  trim($dataArr['outcome']));
            }

            if(strpos($tnameh, "Combo") !== False){
                $tresult = str_replace(')', '', json_encode($outcome[1])); 
                $tresult = str_replace('"', '', $tresult);
                $dataArr['outcome'] = $dataArr['outcome'] . $tresult;
                $dataArr['outcome'] = trim($dataArr['outcome']);
            }

            if(strpos($tnameh, 'O/U') !== false){
                $tresult = str_replace(')', '', json_encode($outcome[1]));
                $tresult = str_replace('"', '', $tresult);
                $dataArr['type'] = trim($tnameh);
                $dataArr['outcome'] = $dataArr['outcome'] . $tresult;
                $dataArr['outcome'] = str_replace(' or ', ':', $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('+', ':',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('&', ':',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace(' + ', ':',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace(' & ', ':',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('GG', 'yes',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('NG', 'no',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('Over', 'Ov',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('Under', 'Un', $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('Ov.', 'Ov',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('Un.', 'Un', $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('Ov', 'over',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('Un', 'under', $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace(' ', '',  trim($dataArr['outcome']));
                $dataArr['outcome'] = strtolower($dataArr['outcome']);
            }


            if(strpos($tnameh, 'Under/Over') !== false){
                $tresult = str_replace(')', '', json_encode($outcome[1]));
                $tresult = str_replace('"', '', $tresult);
                $dataArr['type'] = trim($tnameh);
                $dataArr['outcome'] = $dataArr['outcome'] . $tresult;
                $dataArr['outcome'] = str_replace(' or ', ':', $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('+', ':',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('&', ':',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace(' + ', ':',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace(' & ', ':',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('GG', 'yes',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('NG', 'no',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('Over', 'O',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('Under', 'U', $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('O', 'over',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('U', 'under', $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace(' ', '',  trim($dataArr['outcome']));
                $dataArr['outcome'] = strtolower($dataArr['outcome']);
            }


            if(strpos($tnameh, 'Winning Margins') !== false){

                $dataArr['outcome'] = str_replace(' ', '', $dataArr['outcome']);
            }

            if(strpos($tnameh, 'Corners HT/FT&ET') !== false){

                $dataArr['outcome'] = str_replace('/', ':', $dataArr['outcome']);
            }
            if(strpos($tnameh, 'Half Most Corners') !== false){

                $dataArr['outcome'] = str_replace(' & ET', '', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('X', 'e', $dataArr['outcome']);
            }
            if(strpos($tnameh, 'Match Bookings HT') !== false){

                $dataArr['outcome'] = str_replace('Booking ', '', $dataArr['outcome']);
            }
            if(strpos($tnameh, 'Corner - U/O FT & ET') !== false){

                $dataArr['outcome'] = $dataArr['outcome'].$dataArr['ovalue'];
            }

            if(strpos($tnameh, 'DC HT/FT') !== false){

                $dataArr['outcome'] = str_replace('/', ':', $dataArr['outcome']);
            }
            if(strpos($tnameh, '1X2 HT& DC FT') !== false){

                $dataArr['outcome'] = str_replace(' ', '', $dataArr['outcome']);
            }

            if(strpos($tnameh, 'DC HT & 1X2 FT') !== false){

                $dataArr['outcome'] = str_replace(' ', '', $dataArr['outcome']);
            }


            if(strpos($tnameh, 'DC & Multigoal') !== false){

                $dataArr['outcome'] = str_replace(' ', '', $dataArr['outcome']);
            }

            if(strpos($tnameh, 'Chance Mix +') !== false){

                $dataArr['outcome'] = str_replace(' ', '', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('or', ':', $dataArr['outcome']);
            }

            if(strpos($tnameh, '1X2 & Multigoal') !== false){

                $dataArr['outcome'] = str_replace(' ', '', $dataArr['outcome']);
            }

            if(strpos($tnameh, 'HT DC & GG/NG') !== false){

                $dataArr['outcome'] = str_replace(' ', '', $dataArr['outcome']);
            }

            if(strpos($tnameh, '1X2 & Both Teams To Score') !== false){

                $dataArr['outcome'] = str_replace(' ', '', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('+', ':', $dataArr['outcome']);
            }

            if(strpos($tnameh, 'Total MultiGoal') !== false){

                $dataArr['outcome'] = $dataArr['outcome'];
            }

            else if(strpos($tnameh, 'Total Goals') !== false){
                $tresult = str_replace('Total Goals (Exact) ', '', $dataArr['type']);
                $tresult = str_replace('Total s (Exact)', '', $dataArr['type']);
                $tresult = str_replace('1h Half:Total s', '', $dataArr['type']);
                $tresult = str_replace('1st Half - Total Goals ', '', $dataArr['type']);
                $tresult = str_replace('2nd Half - Total Goals ', '', $dataArr['type']);
                $tresult = str_replace('(', '', $tresult);
                $tresult = str_replace(')', '', $tresult);

                $type = explode("(", $dataArr['type']);
                $dataArr['type'] = $type[0].'(Exact)';
                $dataArr['outcome'] = $dataArr['ovalue'];
            
            }

            else if(strpos($tnameh, 'Total') !== false){
                $tresult = str_replace(')', '', json_encode($outcome[1]));
                $tresult = str_replace('"', '', $tresult);
                $dataArr['type'] = trim($tnameh);
                $dataArr['outcome'] = $dataArr['outcome'] .''. $tresult;  
                $dataArr['outcome'] = str_replace(' or ', ':',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('+', ':',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('&', ':',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace(' + ', ':',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace(' & ', ':',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('Ov.:', 'over',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('Un.:', 'under',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace(' ', '',  trim($dataArr['outcome']));
                $dataArr['outcome'] = strtolower($dataArr['outcome']);
            }

            if(trim($tnameh) == "Over/Under"){
                $tresult = str_replace(')', '', json_encode($outcome[1]));
                $tresult = str_replace('"', '', $tresult);
                $dataArr['type'] = trim($tnameh);
                $dataArr['outcome'] = $dataArr['outcome'] .':'. $tresult;
                $dataArr['outcome'] = str_replace('Ov.:', 'over',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('Un.:', 'under', $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace(' ', '',  trim($dataArr['outcome']));
            }
            else if(strpos($tnameh, 'Over/Under') !== false){
                $tresult = str_replace(')', '', json_encode($outcome[1]));
                $tresult = str_replace('"', '', $tresult);
                $dataArr['type'] = trim($tnameh);
                $dataArr['outcome'] = $dataArr['outcome'] . $tresult;
                $dataArr['outcome'] = str_replace(' or ', ':', $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('+', ':',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('&', ':',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace(' + ', ':',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace(' & ', ':',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('Over', 'O',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('Under', 'U', $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('O', 'over',  $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace('U', 'under', $dataArr['outcome'] );
                $dataArr['outcome'] = str_replace(' ', '',  trim($dataArr['outcome']));
                $dataArr['outcome'] = strtolower($dataArr['outcome']);
            }
            
            if(trim($tnamem) == "Multi C.Score"){
                $tresult = ($outcome);
                $tresult = str_replace('Multi C.Score - ', '', $tresult);
                $dataArr['ovalue'] = $tresult[0];
                $dataArr['type'] = trim($tnamem);
                $dataArr['outcome'] = str_replace(' ', '',  trim($dataArr['outcome']));
            }


            $dataArr['outcome'] = str_replace(' or ', ':',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace('&', ':',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace(' - ', ':',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace(' + ', ':',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace('Home ', '',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace('Away ', '',  $dataArr['outcome'] );

            $dataArr['outcome'] = str_replace('GG/', 'yes:',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace('NG/', 'no:',  $dataArr['outcome'] );

            $dataArr['outcome'] = str_replace('goals', '',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace('Goal', '',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace('GG', 'yes',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace('NG', 'no',  $dataArr['outcome'] );
            
            $dataArr['outcome'] = str_replace('Home', '1',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace('Away', '2',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace('Draw', 'x',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace('Score', 'yes',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace('No score', 'no',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace(' 2HT', '',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace('HT', '',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace('FT', '',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace('1st', '1h',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace('2nd', '2h',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace('Equal', 'e',  $dataArr['outcome'] );
            $dataArr['outcome'] = str_replace('ov.', 'Over',  trim($dataArr['outcome']));
            $dataArr['outcome'] = str_replace('Ov.', 'Over',  trim($dataArr['outcome']));
            $dataArr['outcome'] = str_replace('Un.', 'Under',  trim($dataArr['outcome']));
            $dataArr['outcome'] = str_replace('un.', 'Under',  trim($dataArr['outcome']));
            $dataArr['outcome'] = strtolower($dataArr['outcome']);
            // $dataArr['outcome'] = $dnb;

            $result[] = $dataArr;
        }

        $output = (isset($result)) ? $result : null;

        $output = json_encode($output);

        return $output;
    }

    /**
     * Method call to parse the response from the bookmaker API
     * @param $response variable that stores the response from homebookmaker call
     * @param $homebookmaker variable that stores the home or intiating bookmaker name
     * @param $awaybookmaker variable that stores the away or destination bookmaker name
     * @return false|string
     */
    public function responseParser($response, $homebookmaker, $awaybookmaker, $code) {
        
        $data = json_decode($response, true);

         if (!is_array($data)) {
            
            $result[$homebookmaker][$awaybookmaker] = $data;

            return json_encode($result);
        }

         $cnt = count($data);

        for ($x = 0; $x < $cnt; $x++) {
            $data[$x]['bmbtype'] = $data[$x]['type'];
            $data[$x]['type'] = $this->standardGameType($homebookmaker, $data[$x]['type'], $awaybookmaker , $code);
            $clubs = $this->standardClubNames($homebookmaker, $awaybookmaker, $data[$x]['home'], $data[$x]['away'], $code);
            $data[$x]['home'] = $clubs['homeclub'];
            $data[$x]['away'] = $clubs['awayclub'];

        }

        $result[$homebookmaker][$awaybookmaker] = $data;

       return json_encode($result);
    }

    public function standardGameType( $homemaker, $gameType, $awaymaker, $code){

         $url = "http://upload.betconverter.com/system/model/bookmakers.php";

        $data = json_encode(["homemaker"=>$homemaker, "code"=>$code,"type"=>$gameType, "awaymaker"=>$awaymaker]);

            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
            curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl,CURLOPT_POST, true);
            curl_setopt($curl,CURLOPT_POSTFIELDS, $data);
            curl_exec($curl);

            $response = curl_exec($curl);

            curl_close($curl);      

        $data =  json_decode($response, true);

         if (!is_array($data)) {
            return null;
        }
        if(is_array(current($data))){

            return ($data['Code']['stdtype']);
        }
        else{
            return null;
        }
    }

    public function standardClubNames( $homemaker, $awaymaker, $hometeam, $awayteam, $code){

        $url = "http://upload.betconverter.com/system/model/clubs.php";

        $data = json_encode(["homemaker"=>$homemaker,"awaymaker"=>$awaymaker, "code"=>$code, "homeclub"=>$hometeam, "awayclub"=>$awayteam]);
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
            curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl,CURLOPT_POST, true);
            curl_setopt($curl,CURLOPT_POSTFIELDS, $data);
            curl_exec($curl);

            $response = curl_exec($curl);

            curl_close($curl);      

        $data =  json_decode($response, true);

         if (!is_array($data)) {
            
             $data = ["homeclub"=>$hometeam, "awayclub"=>$awayteam];
            
            return $data;
        }
        if(count($data)>1){

            return $data;
        }
        else{
            
            $data = ["homeclub"=>$hometeam, "awayclub"=>$awayteam];
            
            return $data;
        }
        
    }
}