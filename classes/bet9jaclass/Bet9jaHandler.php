<?php

include_once 'simple_html_dom.php';
include_once 'gamesfetcher.php';


class Bet9jaHandler{

    private $code;
    private $html;
    private $htmlContent;

    function __construct($code){
        $this->code = $code;
        $fetchGames = new gamesfetcher('betnaija');
        $this->htmlContent = $fetchGames->loadGame($this->code);
        $this->html = str_get_html($this->htmlContent);
    }

    public function otherOperations(){
// header('Content-Type: application/json');
// print_r($htmlContent); exit;
        $loopCount = count($this->html->find('div.CSubEv'));
        $codes=[];
        foreach ($this->html->find('div.CCodPub ') as $code)
            $codes[] = trim($code->innertext);

// game played
        for ($i = 1; $i <= $loopCount; $i++) {
            if ($i < 10) {
                $n = "0{$i}";
            } else {
                $n = $i;
            }
            $id = "h_w_PC_cCoupon_repCoupon_ctl{$n}_SE";
            $selected = $this->html->find("span#{$id}");
            $games[] = trim($selected[0]->innertext);
        }

        foreach ($this->html->find('div.DIQ') as $play) {
            $split = explode("|", $play->innertext);
            $teamwins[] = trim($split[0]);
        }

        foreach ($this->html->find('div.CEvento') as $play)
            $league[] = trim($play->innertext);

        foreach ($this->html->find('span.CqSegno') as $play)
            $gameoptions[] = trim($play->innertext);

        foreach ($this->html->find('div.valQuota_1') as $odd)
            $odds[] = trim($odd->innertext);

        /*$game_play = [
            'codes' => $codes,
            'games' => $games,
            'teamwins' => $teamwins,
            'gameoptions' => $gameoptions,
            'odds' => $odds
        ];*/

        $gamecount = count($codes);
        $gamesplayed=[];
        for ($x = 0; $x < $gamecount; $x++) {
            $gamesplayed[$x] = array(
                'league' => $league[$x], 'matchcode' => $codes[$x], 'match' => $games[$x], 'team' => $teamwins[$x],
                'type' => $gameoptions[$x],
                'odd' => $odds[$x]
            );
        }

        return json_encode($gamesplayed, JSON_PRETTY_PRINT);

    }
}



//header('Content-Type: application/json');

// var_dump($htmlContent);
// echo $game_play['codes'][3];
