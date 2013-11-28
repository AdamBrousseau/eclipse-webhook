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
          CURLOPT_HTTPHEADER => array(
            "Authorization: token ".GITHUB_TOKEN,
            "User-Agent: Eclipse-Github-Bot"
          ),
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
          //CURLOPT_HEADER => 1,
          CURLOPT_HTTPHEADER => array(
            "Authorization: token ".GITHUB_TOKEN,
            "User-Agent: Eclipse-Github-Bot",
            "Content-Length: 0"
          ),
          CURLOPT_HEADER => TRUE,
          CURLOPT_RETURNTRANSFER => TRUE, 
          CURLOPT_TIMEOUT => 4 
      ); 

      $ch = curl_init(); 
      curl_setopt_array($ch, ($options + $defaults)); 
      if(! $result = curl_exec($ch)) 
      {
          $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          //$githubCallsRemaining = curl_getinfo($ch, CURLINFO_HTTP_HEADER);
          if ($code < 400) {
            return "{\"http_code\": $code}";
          }
          trigger_error(curl_error($ch)); 
      }
      //getting headers, so we need offset to content
      $headerLength = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
      $header = substr($result, 0, $headerLength);
      $body = substr($result, $headerLength);
      
      //TODO: handle throttling
      curl_close($ch);
      return $body;
  }
  
  public function buildURL(array $components = NULL) {
    $path = implode('/', $components);
    return $this->endPoint .'/'. $path;
  } 
  
  /* http convenience functions
   */
  public function get($url) {
    $json = ($this->curl_get($url));
    return json_decode(stripslashes($json));
  }
  public function put($url) {
    $extra_headers = array(
      CURLOPT_CUSTOMREQUEST => 'PUT',
      CURLOPT_POSTFIELDS => ""
    );
    $json = ($this->curl_get($url, NULL, $extra_headers));
    return json_decode(stripslashes($json));
  }
  public function delete($url) {
    $extra_headers = array(CURLOPT_CUSTOMREQUEST => 'DELETE');
    $json = ($this->curl_get($url, NULL, $extra_headers));
    return json_decode(stripslashes($json));
  }
  public function post($url, $data) {
    $json = ($this->curl_post($url, json_encode($data)));
    return json_decode(stripslashes($json));
  }
  public function patch($url, $data) {
    $extra_headers = array(CURLOPT_CUSTOMREQUEST => 'PATCH');
    $json = ($this->curl_post($url, json_encode($data), $extra_headers));
    return json_decode(stripslashes($json));
  }
  
}

?>