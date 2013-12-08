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

	// Link functions

	// Function to create a short link
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
			$output['short_url'] = $result->{'data'}->{'short_url'};
			$output['long_url'] = $result->{'data'}->{'long_url'};
			$output['new_hash'] = $result->{'data'}->{'new_hash'};
			$output['global_hash'] = $result->{'data'}->{'global_hash'};
		}
		else $output['error'] = $raw_result;
		else $output['error'] = $raw_result;

		return $output;
	}

	// Function to expand existing short link
	//
	//	Params:
	//		$shortUrl
	//		$hash
	//	At least one of them must be set
	public function expand($shortUrl, $hash)
	{
		$shortUrl = urlencode($shortUrl);

		if ($shortUrl != "" || $hash != "")
		{
			$url = API_URL . "/expand?access_token=" . $this->getAccessToken();
			if ($shortUrl != "")
				$url .= "&shortUrl=" . $shortUrl;
			if ($hash != "")
				$url .= "&hash=" . $hash;

			$raw_result = $this->get_curl($url);
	                $result = json_decode($raw_result);

			$output = array();
		        if (is_object($result) && $result->{'status_code'} == 200)
				foreach ($result->{'data'}->{'expand'} as $obj)
        		        {
					$output['hash'] = $obj->{'user_hash'};
	                	        $output['short_url'] = $obj->{'short_url'};
	                        	$output['long_url'] = $obj->{'long_url'};
		                        $output['global_hash'] = $obj->{'global_hash'};
				}
			else $output['error'] = $raw_result;

			return $output;
		}
		return array("error" => "You need to specify at least one of shortUrl or hash params");
	}


	//Function to lookup a shortlink corresponding to a given longurl
	//
	//	Params:
	//		$longUrl
	public function lookup($longUrl)
	{
		$longUrl = urlencode($longUrl);

		if ($longUrl != "")
		{
			$url = API_URL . "/link/lookup?access_token=" . $this->getAccessToken() . "&url=" . $longUrl;

			$raw_result = $this->get_curl($url);
	                $result = json_decode($raw_result);

        	        $output = array();
			if (is_object($result) && $result->{'status_code'} == 200)
				foreach ($result->{'data'}->{'link_lookup'} as $obj)
				{
					$output['short_url'] = $obj->{'aggregate_link'};
					$output['long_url'] = $obj->{'url'};
				}
			else $output['error'] = $raw_result;

			return $output;
		}

		return array("error" => "You need to provide a longUrl");
	}


	//Function to change metadata for a short link
	//
	//	Params:
	//		$shortUrl
	//		$title - optional
	//		$note - optional
	//		$private - boolean - optional
	//		$user_ts - timestamp - optional
	//		$archived - boolean - optional
	public function edit($shortUrl, $title = "", $note = "", $private = "", $user_ts = "", $archived = "")
	{
		if ($shortUrl != "")
		{
			$edit = "";
			$shortUrl = urlencode($shortUrl);

			$url = API_URL . "/user/link_edit?access_token=" . $this->getAccessToken() . "&link=" . $shortUrl;

			if ($title != "")
			{
				$url .= "&title=" . urlencode($title);
				$edit .= "title,";
			}
			if ($note != "")
			{
				$url .= "&note=" . urlencode($note);
				$edit .= "note,";
			}
			if ($private == "true" || $private == "false")
			{
				$url .= "&private=" . $private;
				$edit .= "private,";
			}
			if ($user_ts != "")
			{
				$url .= "&user_ts=" . urlencode($user_ts);
				$edit .= "user_ts,";
			}
			if ($archived == "true" || $archived == "false")
			{
				$url .= "&archived=" . $archived;
				$edit .= "archived,";
			}

			$edit = trim($edit, ",");
			$url .= "&edit=" . $edit;
			echo $url . "\n";
			$raw_result = $this->get_curl($url);
	                $result = json_decode($raw_result);
        	        if (is_object($result) && $result->{'status_code'} == 200)
	                {
        	                $output = TRUE;
                	}
	                else $output = $raw_result;
			return $output;
		}
		return FALSE;
	}

	public function getTotalClicks($link)
	{
		$link = urlencode($link);

		$url = API_URL . "/link/clicks?access_token=" . $this->getAccessToken() . "&link=" . $link; 

		$raw_result = $this->get_curl($url);
		$result = json_decode($raw_result);
		if (is_object($result) && $result->{'status_code'} == 200)
		{
			$output = $result->{'data'}->{'link_clicks'};
		}
		else $output = $raw_result;

		return $output;
	}


}

?>
