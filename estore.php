<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!function_exists("estore_list_apps")) {
  
  define("ESTORE_KEY","7QLjRm5sg8");
  define("ESTORE_CACHEID","LOGIKSTORE-".SiteID);
  define("ESTORE_URI","https://api.smartinfologiks.com/estore/");
  
  function estore_list_apps($recache=false) {
    $cacheData = _cache(ESTORE_CACHEID."-APPS");
    if($cacheData && !$recache) {
      $cacheData = json_decode($cacheData,true);
      
      //Cache once in 24hrs
      if(time()-$cacheData[1]<86400) {
        return $cacheData[0];
      }
    }
    set_time_limit(1000);

    $data = estore_fetch("apprepo");
    if($data['status']=="error") {
      return $data['msg'];
    } else {
      _cache(ESTORE_CACHEID."-APPS",json_encode([$data['data'],time()]));
      return $data['data'];
    }
  }
  
  function estore_list_packages($recache=false) {
    $cacheData = _cache(ESTORE_CACHEID."-REPO");
    if($cacheData && !$recache) {
      $cacheData = json_decode($cacheData,true);
      
      //Cache once in 24hrs
      if(time()-$cacheData[1]<86400) {
        return $cacheData[0];
      }
    }
    set_time_limit(1000);

    $data = estore_fetch("repo");
    if($data['status']=="error") {
      return $data['msg'];
    } else {
      _cache(ESTORE_CACHEID."-REPO",json_encode([$data['data'],time()]));
      return $data['data'];
    }
  }
  
  function estore_updates() {
    
  }
  
  function estore_fetch($url) {
    $url = ESTORE_URI.$url;
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "utf8",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//       CURLOPT_CUSTOMREQUEST => "GET",
//       CURLOPT_POSTFIELDS => "",
      CURLOPT_HTTPHEADER => array(
        "token: ".ESTORE_KEY
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      return ["status"=>"error","msg"=>$err];
    } else {
      return ["status"=>"success","data"=>json_decode($response,true)];
    }
  }
  
  
  function estore_filter($url,$params=[]) {
    $url = ESTORE_URI.$url;
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "utf8",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $params,
      CURLOPT_HTTPHEADER => array(
        "token: ".ESTORE_KEY
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      return ["status"=>"error","msg"=>$err];
    } else {
      return ["status"=>"success","data"=>json_decode($response,true)];
    }
  }
}
?>