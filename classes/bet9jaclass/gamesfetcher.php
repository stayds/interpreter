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
        $codlen = strlen($code);
        $contentLen = $codlen + 6170;
        $ip = "" . mt_rand(0, 255) . "." . mt_rand(0, 255) . "." . mt_rand(0, 255) . "." . mt_rand(0, 255);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://web.bet9ja.com/Sport/Default.aspx",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "h%24w%24SM=h%24w%24PC%24cCoupon%24atlasCoupon%7Ch%24w%24PC%24cCoupon%24lnkLoadPrenotazione&__PREVIOUSPAGE=uocENRZD2yWUBVGQhGhaqlYkdNLVux1d56NH-LFCjXEeO052nCfeqasG0poKngNd4H7IVy3GTvC3Po2g70ZZEcCm2ys1&h%24w%24cLogin%24ctrlLogin%24Username=atfmoney&h%24w%24cLogin%24ctrlLogin%24Password=MustN0tB3Kn0wn&h%24w%24PC%24oddsSearch%24txtSearch=Search&h%24w%24PC%24cCoupon%24txtPrenotatore=$code&h%24w%24PC%24cCoupon%24hidRiserva=0&h%24w%24PC%24cCoupon%24hidAttesa=0&h%24w%24PC%24cCoupon%24hidCouponAsincrono=0&h%24w%24PC%24cCoupon%24hidTipoCoupon=1&h%24w%24PC%24cCoupon%24hidStatoCoupon=0&h%24w%24PC%24cCoupon%24hidBonusNumScommesse=1&h%24w%24PC%24cCoupon%24hidQuotaTotaleDIMax=&h%24w%24PC%24cCoupon%24hidQuotaTotaleDIMin=&h%24w%24PC%24cCoupon%24hidQuotaTotale=3.25&h%24w%24PC%24cCoupon%24hidIDQuote=&h%24w%24PC%24cCoupon%24hidModificatoQuote=1&h%24w%24PC%24cCoupon%24hidBonusQuotaMinimaAttivo=0&h%24w%24PC%24cCoupon%24hidBonusRaggruppamentoMinimo=0&h%24w%24PC%24cCoupon%24hidNumItemCoupon=0&h%24w%24PC%24cCoupon%24hidIDCoupon=&h%24w%24PC%24cCoupon%24hidIDBookmakerCoupon=&h%24w%24PC%24cCoupon%24hidIDUtentePiazzamento=&h%24w%24PC%24cCoupon%24hidPrintAsincronoDisabled=0&h%24w%24PC%24cCoupon%24txtIDQuota=7060394622&h%24w%24PC%24cCoupon%24txtQB=&h%24w%24PC%24cCoupon%24txtAddImporto=&h%24w%24PC%24cCoupon%24txtIDCouponPrecompilato=&h%24w%24PC%24cCoupon%24txtImportoCouponPrecompilato=&h%24w%24PC%24cCoupon%24txtIDCouponReload=&h%24w%24PC%24ScoRis%24hidAttesaAutorizzazioneGovernativa=false&h%24w%24PC%24ctl09%24txtCodiceCoupon=&h%24w%24PC%24ctl13%24txtVincita=&h%24w%24PC%24ctl13%24txtGiocata=&__EVENTTARGET=h%24w%24PC%24cCoupon%24lnkLoadPrenotazione&__EVENTARGUMENT=&__VIEWSTATE=&__VIEWSTATEGENERATOR=15C4A0A3&__VIEWSTATEENCRYPTED=&__ASYNCPOST=true&",
            CURLOPT_HTTPHEADER => array(
                "User-Agent:  Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:77.0) Gecko/20100101 Firefox/77.0",
                "Accept:  */*",
                "Accept-Language:  en-US,en;q=0.5",
                "Accept-Encoding:  gzip, deflate, br",
                "X-Requested-With:  XMLHttpRequest",
                "X-MicrosoftAjax:  Delta=true",
                "Cache-Control:  no-cache",
                "Content-Type:  application/x-www-form-urlencoded; charset=utf-8",
                // "Content-Length:  $contentLen",
                "REMOTE_ADDR: $ip",
                "HTTP_X_FORWARDED_FOR: $ip",
                "Origin:  https://web.bet9ja.com",
                "Connection:  keep-alive",
                "Referer:  https://web.bet9ja.com/Sport/Default.aspx",
                "Cookie:  ISBetsWebAdmin_CurrentCulture=2; _gcl_au=1.1.936064758.1591171950; __auc=bc73ca98172793d27a3eef04d79; _fbp=fb.1.1591171952302.997369822; _ga=GA1.2.1883134405.1591171954; ISBets_CurrentOddsFormat=1; ISBets_CurrentGMT=42; landingRedirection=true; __asc=5831303d1729697877beb498311; _gid=GA1.2.1973225877.1591664415; ASP.NET_SessionId=ou0jakrl5cwliachpy4pl4uv; mb9j_nodesession=2080442122.20480.0000; ISBets_CurrentCulture=2; ISBetsWebAdmin_CurrentCulture=2",
                "Pragma:  no-cache",
                "TE:  Trailers"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}
