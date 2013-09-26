<?php
/**
* Base class for provider implementations
* - provides curl interface. 
*/

class RestClient
{
  private $endpoint;
  function __construct($endPoint)
  {
    $this->endPoint = $endPoint;
  }

  /** 
   * Send a POST requst using cURL 
   * @param string $url to request 
   * @param array $post values to send 
   * @param array $options for cURL 
   * @return string 
   */ 
  protected function curl_post($url, $post = NULL, array $options = array()) { 
      $defaults = array( 
          CURLOPT_POST => 1, 
          CURLOPT_HEADER => 0, 
          CURLOPT_HTTPHEADER => array("Authorization: token ".$_SERVER['TOKEN']),
          CURLOPT_URL => $url, 
          CURLOPT_FRESH_CONNECT => 1, 
          CURLOPT_RETURNTRANSFER => 1, 
          CURLOPT_FORBID_REUSE => 1, 
          CURLOPT_TIMEOUT => 4, 
          CURLOPT_POSTFIELDS => $post 
      ); 

      $ch = curl_init(); 
      curl_setopt_array($ch, ($options + $defaults)); 
      if( ! $result = curl_exec($ch)) 
      { 
          trigger_error(curl_error($ch)); 
      } 
      curl_close($ch); 
      return $result; 
  } 

  /** 
   * Send a GET requst using cURL 
   * @param string $url to request 
   * @param array $get values to send 
   * @param array $options for cURL 
   * @return string 
   */ 
  protected function curl_get($url, array $get = NULL, array $options = array()) {
      $defaults = array( 
          CURLOPT_URL => $url,//. (strpos($url, '?') === FALSE ? '?' : ''). http_build_query($get), 
          CURLOPT_HEADER => 0,
          CURLOPT_HTTPHEADER => array("Authorization: token ".$_SERVER['TOKEN']),
          CURLOPT_RETURNTRANSFER => TRUE, 
          CURLOPT_TIMEOUT => 4 
      ); 
    
      $ch = curl_init(); 
      curl_setopt_array($ch, ($options + $defaults)); 
      if( ! $result = curl_exec($ch)) 
      { 
          trigger_error(curl_error($ch)); 
      } 
      curl_close($ch); 
      return $result; 
  }
  
  protected function buildURL(array $components = NULL) {
    $path = implode('/', components);
    return $this->endPoint .'/'. $path;
  } 
}

?>