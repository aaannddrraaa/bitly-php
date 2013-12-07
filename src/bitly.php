<?php

// Define client id
define('CLIENT_ID', 'YOUR_CLIENT_ID');

// Define client secret
define('CLIENT_SECRET', 'YOUR_CLIENT_SECRET');

// Define authentication api url
define('AUTH_URL', 'https://api-ssl.bitly.com/oauth/access_token');

// Define general api url
define('API_URL', 'https://api-ssl.bitly.com/v3');

// Define account username
define('USERNAME', 'YOUR_BITLY_USERNAME');

// Define account password
define('PASSWORD', 'YOUR_BITLY_PASSWORD');


class Bitly {

	private $access_token = null;

	function get_curl($uri) 
	{
 		$output = "";
  		try {
		$ch = curl_init($uri);
    		curl_setopt($ch, CURLOPT_HEADER, 0);
    		curl_setopt($ch, CURLOPT_TIMEOUT, 4);
    		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    		$output = curl_exec($ch);
  		} catch (Exception $e) {
  		}
  		return $output;
	}

	function post_curl($uri, $fields, $header)
	{
  		$output = "";
  		$fields_string = "";
  		foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
  		rtrim($fields_string,'&');
  		try {
    			$ch = curl_init($uri);
    			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    			curl_setopt($ch, CURLOPT_POST,count($fields));
    			curl_setopt($ch, CURLOPT_POSTFIELDS,$fields_string);
    			curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    			$output = curl_exec($ch);
  		} catch (Exception $e) {
			echo "Error";
  		}
  		return $output;
	}

	public function getAccessToken()
	{
		if ($this->access_token == null)
		{
			$headr = array();
			$headr[] = 'Content-type: application/x-www-form-urlencoded';
			$headr[] = 'Authorization: Basic ' . base64_encode(CLIENT_ID . ":" . CLIENT_SECRET);

			$fields	= array();
			$fields['grant_type'] = 'password';
			$fields['username'] = USERNAME;
			$fields['password'] = PASSWORD;

			$result = json_decode($this->post_curl(AUTH_URL, $fields, $headr));
			if (isset($result->{'access_token'}))
			{
				$this->access_token = $result->{'access_token'};
			}
			else return false;
		}
		return $this->access_token;
	}

	public function shorten($longUrl, $domain = "")
	{
		$longUrl = urlencode($longUrl);
		$domain = urlencode($domain);

		$url = API_URL . "/shorten?access_token=" . $this->getAccessToken() . "&longUrl=" . $longUrl;
		if($domain != "")
			$url .= "&domain=" . $domain;

		$output = array();
		$raw_result = $this->get_curl($url);
		$result = json_decode($raw_result);
		if (is_object($result))
		if ($result->{'status_code'} == 200)
		{
			$output['hash'] = $result->{'data'}->{'hash'};
			$output['url'] = $result->{'data'}->{'url'};
			$output['long_url'] = $result->{'data'}->{'long_url'};
			$output['new_hash'] = $result->{'data'}->{'new_hash'};
			$output['global_hash'] = $result->{'data'}->{'global_hash'};
		}
		else $output['error'] = $raw_result;
		else $output['error'] = $raw_result;

		return $output;
	}

	public function getTotalClicks($link)
	{
		$link = urlencode($link);

		$url = API_URL . "/link/clicks?access_token=" . $this->getAccessToken() . "&link=" . $link; 

		$raw_result = $this->get_curl($url);
		$result = json_decode($raw_result);
		if (is_object($result))
		if ($result->{'status_code'} == 200)
		{
			$output = $result->{'data'}->{'link_clicks'};
		}
		else $output = var_dump($raw_result);
		else $output = var_dump($raw_result);

		return $output;
	}
}

?>
