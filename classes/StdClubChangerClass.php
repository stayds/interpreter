<?php


class StdClubChangerClass
{
   public static function changeClubandStd($request, $code){

       $url = "http://upload.betconverter.com/system/model/type_club.php";
//       $data = array_merge($request,["code"=>$code]);
       $data = json_encode(array_merge($request,["code"=>$code]));
//           return $data;
       try {

           $connection = curl_init($url);
           curl_setopt($connection, CURLOPT_RETURNTRANSFER,true);
           curl_setopt($connection,CURLOPT_POST, TRUE);
           curl_setopt($connection,CURLOPT_POSTFIELDS, $data);
           curl_exec($connection);

           if(curl_getinfo($connection)['http_code'] ==0)
               throw new Exception();

           if( curl_getinfo($connection)['http_code'] == 404)
               throw new Exception('url not found', 404);

           $response = curl_exec($connection);

           return  $response;
       } catch (\Exception $ex) {
           http_response_code(404);
           return array('status'=> 'error', 'message'=> curl_error($connection)!=null ? curl_error($connection) : $ex->getMessage() );
       } finally {
           curl_close($connection);
       }
   }
}