<?php
include_once './interfaces/BookmakerInterface.php';
/**
 * 
 */
class Twentytwobet implements BookmakerInterface {

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
     */
    public function callBookMaker($code) {
        
        $curl = curl_init();

        curl_setopt_array($curl,[
            CURLOPT_URL => 'https://22bet.ng/LiveUtil/GetCoupon',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{"Guid":"'.$code.'","Lng":"en","partner":151}',
            CURLOPT_HTTPHEADER => array(
                "Accept:  application/json, text/plain, */*",
                "Accept-Language:  en-US,en;q=0.5",
                "REMOTE_ADDR: ",
                "X-Requested-With:  XMLHttpRequest",
                "Content-Type:  application/json;charset=utf-8",
                "Host: 22bet.ng",
                "Connection:  keep-alive",
                "Referer: https://22bet.ng/",
                "Content-Length: 41",
            ),
        ]);
         
        $response = curl_exec($curl);

        curl_close($curl);

        $data = json_decode($response, true);

        if (!is_array($data)) {
            return null;
        }

        $cnt = count($data['Value']['Events']);
        $baseData = $data['Value']['Events'];

        for ($x = 0; $x < $cnt; $x++) {
            $dataArr['code'] = $code;
            $dataArr['league'] = $baseData[$x]['Liga'];
            $dataArr['sport'] = $baseData[$x]['SportName'];
            $dataArr['home'] = $baseData[$x]['Opp1'];
            $dataArr['away'] = $baseData[$x]['Opp2'];
            $dataArr['odd'] = $baseData[$x]['Coef'];
            $dataArr['ovalue'] = $baseData[$x]['Param'];
            $dataArr['type'] = trim($baseData[$x]['GroupName'].' '.$baseData[$x]['GameType'].''.$baseData[$x]['PeriodName']);
            $dataArr['outcome'] = $baseData[$x]['MarketName'];
            // $dataArr['GroupId'] = $baseData[$x]['GroupId'];
            $outcome = $dataArr['outcome'];
            $outcome = str_replace($dataArr['type'], '',  $outcome);
            $outcome = str_replace($dataArr['home'], '1', $outcome);
            $outcome = str_replace($dataArr['away'], '2', $outcome);
            $outcome = str_replace('Draw', 'X', $outcome);

            $dataArr['sport'] = strtolower($dataArr['sport']);

            $dataArr['outcome'] = $outcome;
            
           if($dataArr['type'] =='Double Chance') {

                $dataArr['outcome'] = str_replace(' Or ', '',  $dataArr['outcome']);
            }
            if(($dataArr['type'] =='Correct Score (17Way)') || ($dataArr['type'] == 'Correct Score')) {

                $dataArr['outcome'] = str_replace('-', ':',  $dataArr['outcome']);
            }

            if(($dataArr['type'] =='Asian Total') || ($dataArr['type'] == 'Asian Total 1 Half')){

                $dataArr['outcome'] = str_replace('Total ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' (', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(')', '',  $dataArr['outcome']);
            }

            if(($dataArr['type'] =='Total 1 Half') || ($dataArr['type'] == 'Total 2 Half')){

                $dataArr['outcome'] = str_replace('Total ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' (', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(')', '',  $dataArr['outcome']);
            }

            if(($dataArr['type'] =='Asian Team Total 1 1 Half')|| ($dataArr['type'] == 'Asian Team Total 2 1 Half')) {

                $dataArr['outcome'] = str_replace('Team 1 Total ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('Team 2 Total ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' (', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(')', '',  $dataArr['outcome']);
            }

            if(($dataArr['type'] =='Total 1 1 Half') || ($dataArr['type'] == 'Total 2 1 Half')) {

                $dataArr['outcome'] = str_replace('Total 1', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('Total 2', '',  $dataArr['outcome']);
            }


            if(($dataArr['type'] =='Team 1 To Win Either Half') || ($dataArr['type'] == 'Team 2 To Win Either Half ')){

               $dataArr['outcome'] = str_replace('1 To Win At Least One Half - ', '',  $dataArr['outcome']);
               $dataArr['outcome'] = str_replace('2 To Win At Least One Half - ', '',  $dataArr['outcome']);
            }

            if(($dataArr['type'] =='Team 1 Win To Nil 1 Half')||($dataArr['type'] == 'Team 2 Win To Nil 1 Half')||($dataArr['type'] == 'Team 1 Win To Nil 2 Half') ||($dataArr['type'] == 'Team 2 Win To Nil 2 Half')){

               $dataArr['outcome'] = str_replace('1 To Win To Nil - ', '',  $dataArr['outcome']);
               $dataArr['outcome'] = str_replace('2 To Win To Nil - ', '',  $dataArr['outcome']);
            }


            if($dataArr['type'] =='First 5 Minutes First To Happen') {

               $dataArr['outcome'] = str_replace('Goal In First 5 Minutes - ', '',  $dataArr['outcome']);
            }

            if($dataArr['type'] =='Injury Time Goal') {

                $dataArr['outcome'] = str_replace('In At Least One Half - ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('In Second Half - ', '',  $dataArr['outcome']);
            }
            
            if(($dataArr['type'] =='A Player Scores Two Goals (Brace)') || ($dataArr['type'] == 'A Player Scores A Hat-Trick')){

                $dataArr['outcome'] = str_replace('A Player To Score Two Goals - ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('Hat-trick - ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('In Second Half - ', '',  $dataArr['outcome']);

            }

            if($dataArr['type'] =='Goal In Half') {

                $dataArr['outcome'] = str_replace('First Goal in 1st Half', '1',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('First Goal in 2nd Half', '2',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('No First Goal', 'none',  $dataArr['outcome']);
            }
            
            if($dataArr['type'] =='Goal In Both Halves') {

                $dataArr['outcome'] = str_replace('Goals Scored In Both Halves - ', '',  $dataArr['outcome']);
            }

            if(($dataArr['type'] =='Even/Odd') || ($dataArr['type'] == 'Even/Odd 1 Half') || ($dataArr['type'] == 'Even/Odd 2 Half')) {

                $dataArr['outcome'] = str_replace('Total Even - ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('Yes', 'even',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('No', 'odd',  $dataArr['outcome']);
            }

            if(($dataArr['type'] =='Individual Total 1 Even/Odd') || ($dataArr['type'] =='Individual Total 2 Even/Odd')){
                
                $dataArr['outcome'] = str_replace('1 Total ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('2 Total ', '',  $dataArr['outcome']);
            }


            if(($dataArr['type'] =='HT-FT')|| ($dataArr['type'] =='HT-FT + Total')) {
                $dataArr['outcome'] = str_replace(' ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('-', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('W', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('No', '',  $dataArr['outcome']);

                $dataArr['outcome'] = str_replace('And', ':',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('Total', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('(', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(')', '',  $dataArr['outcome']);

                $dataArr['outcome'] = str_replace('Yes', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('11', '1/1', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('12', '1/2', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('1X', '1/x', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('X2', 'x/2', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('XX', 'x/x', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('X1', 'x/1', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('21', '2/1', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('22', '2/2', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('2X', '2/x', $dataArr['outcome']);
            }

            if($dataArr['type'] =='Last Goal') {
                $dataArr['outcome'] = str_replace(' To Score ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('No One Will Score', 'none',  $dataArr['outcome']);
            }
            if($dataArr['type'] =='Draw In At Least One Half') {
                $dataArr['outcome'] = str_replace('- ', '',  $dataArr['outcome']);
            }
            if($dataArr['type'] =='Race To') {
                $dataArr['outcome'] = str_replace('(', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(') ', ':',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' - ', '',  $dataArr['outcome']);
            }

            if(($dataArr['type'] =='Penalty Awarded') || ($dataArr['type'] =='Will An Awarded Penalty Be Scored')){
                $dataArr['outcome'] = str_replace('Penalty Awarded And Scored', 'scored',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('Penalty Awarded And Not Scored', 'missed',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' - ', '',  $dataArr['outcome']);
            }

            if($dataArr['type'] =='Total And Both To Score') {
                $dataArr['outcome'] = str_replace('Both Teams To Score + Total', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' (', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(') ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('- ', ':',  $dataArr['outcome']);
            }

            if($dataArr['type'] =='Win To Nil') {
                $dataArr['outcome'] = str_replace('Any Team To  -', '',  $dataArr['outcome']);
            }
            
            if(($dataArr['type'] =='Team 1 Win To Nil') || ($dataArr['type'] =='Team 2 Win To Nil')) {
                $dataArr['outcome'] = str_replace('1 To Win To Nil -', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('2 To Win To Nil -', '',  $dataArr['outcome']);
            }

            if(($dataArr['type'] =='Team 1 To Score N Goals') || ($dataArr['type'] =='Team 2 To Score N Goals')) {
                $dataArr['outcome'] = str_replace('1 Will Score 1 - 2 Goals -', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('1 Will Score 1 - 2 Goals -', '',  $dataArr['outcome']);
            }
            
            if($dataArr['type'] =='Half/Half') {

                $dataArr['outcome'] = str_replace(' Win 1st Half', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' Win 2nd Half', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('n 1st Half', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('n 2nd Half', '',  $dataArr['outcome']);
            }

            if(($dataArr['type'] =='Team 2 To Score Goals In A Row')|| ($dataArr['type'] =='Team 1 To Score Goals In A Row')) {

                $dataArr['outcome'] = str_replace('2 To Score (2) Goals In A Row - ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('2 To Score (3) Goals In A Row - ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('1 To Score (2) Goals In A Row - ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('1 To Score (3) Goals In A Row - ', '',  $dataArr['outcome']);
            }

            if($dataArr['type'] =='Both Teams To Score') {
                $dataArr['type'] =  $baseData[$x]['MarketName'];
                $dataArr['type'] =  str_replace(' - Yes', '', $dataArr['type']);
                $dataArr['outcome'] = str_replace(' - ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('Each Team To Score (2) Or More', '',  $dataArr['outcome']);
            }

            if(($dataArr['type'] =='Both Teams To Score 2 Half')|| ($dataArr['type'] =='Both Teams To Score 1 Half')) {
                $dataArr['outcome'] =  str_replace('Both Teams To Score ', '', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('- ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('Each Team To Score (2) Or More', '',  $dataArr['outcome']);
            }

            if(($dataArr['type'] =='Asian Team Total 1') || ($dataArr['type'] =='Asian Team Total 2')){
                $dataArr['outcome'] = str_replace('Team 2 Total ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('Team 1 Total ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' (', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(')', '',  $dataArr['outcome']);
            }

            if($dataArr['type'] =='Correct Score - Group Bet') {
                $dataArr['outcome'] = str_replace(' Or ', '/',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' - Yes', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('-', ':',  $dataArr['outcome']);
            }

            if($dataArr['type'] =='Team 2 To Win Either Half') {

                $dataArr['outcome'] = str_replace('2 To Win At Least One Half - ', '',  $dataArr['outcome']);
            }

            if(($dataArr['type'] == 'Red Card') || ($dataArr['type'] =='To Qualify')|| ($dataArr['type'] =='Penalty Awarded And Sending Off')) {

                $dataArr['outcome'] = str_replace('- ', '',  $dataArr['outcome']);
            }

            if($dataArr['type'] =='Asian Team Total 2') {

                $dataArr['outcome'] = str_replace('Team 2 Total ', '',  $dataArr['outcome']);
            }

            if($dataArr['type'] =='First Half Score + Match Score') {

                $dataArr['outcome'] = str_replace('Score At Half-Time ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' And Match Score ', '/',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('-', ':',  $dataArr['outcome']);
            }

            if($dataArr['type'] =='Both Teams To Score + Double Chance') {

                $dataArr['outcome'] = str_replace('At Least One Team Not To Score And ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' - ', ':',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('12', '1/2', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('1X', '1/x', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('X2', 'x/2', $dataArr['outcome']);
            }

            if($dataArr['type'] =='Both Teams To Score Yes/No + Total')  {

                $dataArr['outcome'] = str_replace('Both Teams To Score - ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' Total ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('(', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(')', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' ', '',  $dataArr['outcome']);
            }

            if($dataArr['type'] =='Double Chance + Total') {

                $dataArr['outcome'] = str_replace('Team 2 Total ', '',  $dataArr['outcome']);
            }

            if(($dataArr['type'] =='Highest Scoring Half Total')|| ($dataArr['type'] == 'Total')) {

                $dataArr['outcome'] = str_replace(' (', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(')', '',  $dataArr['outcome']);
            }

            if(($dataArr['type'] =='Score Draw') || ($dataArr['type'] == 'Any Team To Come From Behind And Win')) {

                $dataArr['outcome'] = str_replace(' - ', '',  $dataArr['outcome']);
            }

            if(($dataArr['type'] =='Team 1 To Win Both Halves') || ($dataArr['type'] == 'Team 2 To Win Both Halves')) {

                $dataArr['outcome'] = str_replace('2 To Win Both Halves - ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('1 To Win Both Halves - ', '',  $dataArr['outcome']);
            }


            if($dataArr['type'] =='Highest Scoring Half Total') {

                $dataArr['outcome'] = str_replace(' (', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(')', '',  $dataArr['outcome']);
            }

            if($dataArr['type'] =='To Keep Clean Sheet') {

                $dataArr['outcome'] = str_replace('1', 'yes',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('2', 'no',  $dataArr['outcome']);
            }

            if($dataArr['type'] =='Next Goal') {

                $dataArr['outcome'] = str_replace(' To Score ', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('Neither Team', 'None',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' (', '/',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(')', '',  $dataArr['outcome']);
            }
            
            if($dataArr['type'] =='Win By') {
                $dataArr['outcome'] = str_replace(' To  (1) Goals - No', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' To  (1) Goals - Yes', '', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' To  1 - 2 Goals - No', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' To  1 - 2 Goals - Yes', '', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' To  (2) Goals - No', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' To  (2) Goals - Yes', '', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' To  (3) Goals - No', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' To  (3) Goals - Yes', '', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' To  2 - 3 Goals - No', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' To  2 - 3 Goals - Yes', '', $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' To  (3) Or More Goals - Yes', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' To  (3) Or More Goals - No', '', $dataArr['outcome']);
                $dataArr['outcome'] = $dataArr['outcome'].'by'.$dataArr['ovalue']; 
            }

            if(($dataArr['type'] =='Exact Number')|| ($dataArr['type'] == 'Exact Total Goals 1 Half')|| ($dataArr['type'] == 'Exact Total Goals 2 Half')|| ($dataArr['type'] == 'Exact Number 1 Half') || ($dataArr['type'] == 'Exact Number 2 Half')|| ($dataArr['type'] == 'Exact Total Goals')) {

                $dataArr['outcome'] = str_replace(' - Yes', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' - No', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('Total', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('From', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace('Goals', '',  $dataArr['outcome']); 
                $dataArr['outcome'] = str_replace(' Goals Exactly', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' Or More', '+',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' To ', '-',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(' (', '',  $dataArr['outcome']);
                $dataArr['outcome'] = str_replace(')', '',  $dataArr['outcome']);
            }

            if($dataArr['type'] == 'Handicap'){

                // $dataArr['type'] = $baseData[$x]['PeriodName'].' ' .$dataArr['outcome'];
                // $dataArr['type'] = str_replace(' 1 ', '',  $dataArr['type']);
                // $dataArr['type'] = str_replace(' 2 ', '',  $dataArr['type']);
                // $outcome = explode("(", $dataArr['outcome']);
                // $dataArr['type'] = str_replace($outcome[1], '', $dataArr['type']);
                // $dataArr['type'] = str_replace('(', '',  $dataArr['type']);
                // $dataArr['type'] = str_replace(' ', '',  $dataArr['type']);
            }

            if($dataArr['type'] == 'European Handicap'){

                $outcome = explode(")", $dataArr['outcome']);
                $dataArr['outcome'] = $outcome[1].$outcome[0];
                $dataArr['outcome'] = str_replace(' (', ':',  $dataArr['outcome']);
            }

            if($dataArr['type'] == 'Scores In Each Half' ) {
                $dataArr['outcome'] = $dataArr['outcome'];
                $dataArr['outcome'] =str_replace('1st Half = 2nd Half', 'e', $dataArr['outcome']);
                $dataArr['outcome'] =str_replace('1st Half > 2nd Half', '1h', $dataArr['outcome']);
                $dataArr['outcome'] =str_replace('1st Half < 2nd Half', '2h', $dataArr['outcome']);
            }
            
            if(($dataArr['type'] == 'Team 2 Scores In Halves') ||($dataArr['type'] == 'Team 1 Scores In Halves')) {
                $dataArr['outcome'] = $dataArr['outcome'];
                $dataArr['outcome'] =str_replace('1st Half = 2nd Half', 'e', $dataArr['outcome']);
                $dataArr['outcome'] =str_replace('1st Half > 2nd Half', '1h', $dataArr['outcome']);
                $dataArr['outcome'] =str_replace('1st Half < 2nd Half', '2h', $dataArr['outcome']);
                $dataArr['outcome'] =str_replace('1 - ', '', $dataArr['outcome']);
                $dataArr['outcome'] =str_replace('2 - ', '', $dataArr['outcome']);
            }

            if(($dataArr['type'] == 'Team 1 To Score A Goal In Both Halves') ||($dataArr['type'] == 'Team 2 To Score A Goal In Both Halves')) {
                $dataArr['outcome'] = $dataArr['outcome'];
                $dataArr['outcome'] =str_replace('1 To Score A Goal In Both Halves -', '', $dataArr['outcome']);
                $dataArr['outcome'] =str_replace('2 To Score A Goal In Both Halves -', '', $dataArr['outcome']);
            }

            $outcome = explode("(", $dataArr['outcome']);
            $tnameh = str_replace('"', '', json_encode($outcome[0]));
            $tnameh = str_replace('\\', '', $tnameh);

            if(strpos($tnameh, 'Individual') !== false){
                $tresult = str_replace(')', '', json_encode($outcome[1]));
                $tresult = str_replace('"', '', $tresult);
                $tresult = str_replace('\\', '', $tresult);
                $tnameh = str_replace('Individual ', '', $tnameh);
                $dataArr['outcome'] =  trim($tnameh) .$tresult;
            }

            if(strpos($tnameh, 'Handiu0441ap') !== false){
                $tresult = str_replace(')', '', json_encode($outcome[1]));
                $tresult = str_replace('"', '', $tresult);
                $tresult = str_replace('\\', '', $tresult);
                $tnameh = str_replace('Handiu0441ap ', '', $tnameh);
                $dataArr['outcome'] =  trim($tnameh).':'.$tresult;
            }

            if(strpos($tnameh, 'Under') !== false){
                $tresult = str_replace('(', '', $dataArr['outcome']);
                $tresult = str_replace(')', '', $tresult);
                $tresult = str_replace(' ', '', $tresult);
            }

             if(strpos($tnameh, 'Over') !== false){
                $tresult = str_replace('(', '', $dataArr['outcome']);
                $tresult = str_replace(')', '', $tresult);
                $tresult = str_replace(' ', '', $tresult);
            }

            $dataArr['outcome'] = str_replace(' Or ', ':', $dataArr['outcome']);
            $dataArr['outcome'] = str_replace('Correct Score ', '', $dataArr['outcome']);

            $dataArr['outcome'] = strtolower(trim($dataArr['outcome']));

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
            $data[$x]['type'] = $this->standardGameType($homebookmaker, $data[$x]['type'], $awaybookmaker, $code);
            $clubs = $this->standardClubNames($homebookmaker, $awaybookmaker, $data[$x]['home'], $data[$x]['away'],$code);
            $data[$x]['home'] = $clubs['homeclub'];
            $data[$x]['away'] = $clubs['awayclub'];

        }

        $result[$homebookmaker][$awaybookmaker] = $data;

       return json_encode($result);
    }

    public function standardGameType( $homemaker, $gameType, $awaymaker, $code){

         $url = "http://upload.betconverter.com/system/model/bookmakers.php";

        $data = json_encode(["homemaker"=>$homemaker,"type"=>$gameType, "code"=>$code, "awaymaker"=>$awaymaker]);

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

        $data = json_encode(["homemaker"=>$homemaker,"awaymaker"=>$awaymaker,"code"=>$code, "homeclub"=>$hometeam, "awayclub"=>$awayteam]);
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