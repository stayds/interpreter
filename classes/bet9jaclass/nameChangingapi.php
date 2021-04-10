<?php


class gamesfetcher
{
    public $betcompanyName;

    public function __construct($betcompanyName)
    {
        $this->betcompanyName = $betcompanyName;
    }

    function loadGame($code)
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
            CURLOPT_CUSTOMREQUEST => "POST",
//            CURLOPT_HTTPHEADER => array(
//                "User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:77.0) Gecko/20100101 Firefox/77.0",
//                "Accept:  */*",
//                "Accept-Language:  en-US,en;q=0.5",
//                "Accept-Encoding:  gzip, deflate, br",
//                "X-Requested-With:  XMLHttpRequest",
//                "X-MicrosoftAjax:  Delta=true",
//                "Cache-Control:  no-cache",
//                "Content-Type:  application/x-www-form-urlencoded; charset=utf-8",
//                // "Content-Length:  $contentLen",
//                "Connection:  keep-alive",
//                "Pragma:  no-cache",
//            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}
