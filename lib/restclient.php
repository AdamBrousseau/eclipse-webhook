<?php
/*******************************************************************************
* Copyright (c) 2013-2016 Eclipse Foundation and others.
* All rights reserved. This program and the accompanying materials
* are made available under the terms of the Eclipse Public License v1.0
* which accompanies this distribution, and is available at
* http://www.eclipse.org/legal/epl-v10.html
*
* Contributors:
*    Zak James (zak.james@gmail.com) - Initial implementation
*    Denis Roy (Eclipse Foundation) - misc enhancements
*******************************************************************************/


/**
* Base class for provider implementations
* - provides curl interface. 
*/
include('logger.php');

class RestClient
{
  private $endpoint;
  protected $logger;
  protected $response_headers = "";

  function __construct($endPoint)
  {
    $this->endPoint = $endPoint;
    $this->logger = new Logger();
  }

  private function throttle($header) {
    $matches = array();
    $re = "/X-RateLimit-(.*)\: (\d+)/";

    preg_match_all($re, $header, $matches, PREG_SET_ORDER);
    $remaining = 0; $limit = 0; $reset = 0;
    foreach ($matches as $line) {
      switch ($line[1]) {
        case 'Limit':
          $limit = (int)$line[2];
          break;
        case 'Remaining':
          $remaining = (int)$line[2];
          break;
        case 'Reset':
          $reset = (int)$line[2];
          break;
        default:
        echo "[Warning] unknown rate limit header: $line\n";
          break;
      }
    }
    //check if tokens are available. Sometimes github has reset time set to 0
    //so handle that too.
    if ($remaining < 1 && $reset > 0) {
      $sleepTime = (int)($reset - time());
      echo "[Info] rate limit exceeded. Sleeping for $sleepTime s\n";
      sleep($sleepTime);
    }
  }

  /**
   * Send a POST requst using cURL
   * @param string $url to request
   * @param array $post values to send
   * @param array $options for cURL
   * @return string 
   */
  protected function curl_post($url, $post = NULL, array $options = array()) { 
      //mangle URLS to use bump proxy.
      $url = preg_replace("/api\.github\.com/","proxy.eclipse.org:9998",$url);
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
          CURLOPT_TIMEOUT => 60,
          CURLOPT_POSTFIELDS => $post
      );

      $ch = curl_init();
      curl_setopt_array($ch, ($options + $defaults));
      if( ! $result = curl_exec($ch)) 
      {
          $this->logger->error(curl_error($ch));
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
      //mangle URLS to use bump proxy.
      $url = preg_replace("/api\.github\.com/","proxy.eclipse.org:9998",$url);
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
          CURLOPT_TIMEOUT => 60
      ); 

      $ch = curl_init();
      curl_setopt_array($ch, ($options + $defaults));
      $result = curl_exec($ch);
      $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      if(! $result) {
          //$githubCallsRemaining = curl_getinfo($ch, CURLINFO_HTTP_HEADER);
          if ($code < 400) {
            return "{\"http_code\": $code}";
          }
          $this->logger->error(curl_error($ch));
          trigger_error(curl_error($ch)); 
      }
      //getting headers, so we need offset to content
      $headerLength = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
      $header = substr($result, 0, $headerLength);
      $body = substr($result, $headerLength);
      //handle throttling
      $this->throttle($header);
      $this->response_headers = $header;

      if(strlen($body) <= 0) {
        $body = "{\"http_code\": $code}";
      }
      curl_close($ch);

      return $body;
  }

 /** 
   * Send a put requst using cURL
   * @param string $url to request
   * @param array $get values to send
   * @param array $options for cURL
   * @return string
   */
  protected function curl_put($url, $data = NULL, array $options = array()) {
      //mangle URLS to use bump proxy.
      $url = preg_replace("/api\.github\.com/","proxy.eclipse.org:9998",$url);
      $defaults = array(
          CURLOPT_URL => $url,//. (strpos($url, '?') === FALSE ? '?' : ''). http_build_query($get), 
          //CURLOPT_HEADER => 1,
          CURLOPT_HTTPHEADER => array(
            "Authorization: token ".GITHUB_TOKEN,
            "User-Agent: Eclipse-Github-Bot",
          ),
          CURLOPT_HEADER => TRUE,
          CURLOPT_RETURNTRANSFER => TRUE,
          CURLOPT_CUSTOMREQUEST => "PUT",
          CURLOPT_POSTFIELDS => '',
      );
      if ( $data !== NULL ) {
          $data_json = json_encode($data);
          $defaults[CURLOPT_POSTFIELDS] = $data_json;
          array_push($defaults[CURLOPT_HTTPHEADER],"Content-Type: application/json");
          array_push($defaults[CURLOPT_HTTPHEADER],"Content-Length: " . strlen($data_json));
      } else {
          array_push($defaults[CURLOPT_HTTPHEADER],"Content-Length: 0");
      }
      $ch = curl_init();
      curl_setopt_array($ch, ($options + $defaults));
      if( ! $result = curl_exec($ch))
      {
          $this->logger->error(curl_error($ch));
          trigger_error(curl_error($ch));
          trigger_error("Post url" . $url);
      }
      curl_close($ch);
      return $result;
  }

  public function buildURL(array $components = NULL) {
    $path = implode('/', $components);
    return $this->endPoint .'/'. $path;
  } 

  /* http convenience functions
   */
  public function get($url) {
    $json = ($this->curl_get($url));
    return json_decode(urldecode($json));
  }

  public function put($url, $data=NULL) {
    $extra_headers = array(
      CURLOPT_CUSTOMREQUEST => 'PUT',
      CURLOPT_POSTFIELDS => ""
    );
    if ( $data !== NULL ) {
      $json = ($this->curl_put($url,$data));
    } else {
      $json = ($this->curl_get($url, NULL, $extra_headers));
    }
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

  /**
   * Get all response headers from the last GET, or one specific header value
   * @param string optional header to look for
   * @return string The entire set, or just the desired value
   * @author droy
   * @since 2016-01-14
   */
  public function getResponseHeaders($header=NULL) {
    $rValue = $this->response_headers;
    if(!is_null($header)) {
      if(preg_match("/$header: (.*)/", $this->response_headers, $matches)) {
        $rValue = preg_replace('/\r/', "", $matches[1]);
      }
    }
    return $rValue;
  }

}

?>
