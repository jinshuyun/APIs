<?php
require_once __DIR__."/facebook-sdk-v5/autoload.php";
class news_social_fb {
	public $pageid = "#################";
	public $appid = "##################";
	public $app_secret = "#####################";
	public $user_token;
	function __construct() {
		
		$fb = new Facebook\Facebook(array(
				'app_id' => $this->appid,
				'app_secret' => $this->app_secret,
				'default_graph_version' => 'v2.7',
		));
		
		$helper = $fb->getRedirectLoginHelper();
		$_SESSION['FBRLH_state']=$_GET['state'];
		try {
			$accessToken = $helper->getAccessToken('http://www.###########.com/####################');
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
			// When Graph returns an error
			echo 'Graph returned an error: ' . $e->getMessage();
			exit;
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
			// When validation fails or other local issues
			echo 'Facebook SDK returned an error: ' . $e->getMessage();
			exit;
		}
		
		if(isset($accessToken))
			$this->user_token = $accessToken->getValue();
		else {
			$_SESSION['FBRLH_state']=="";
			exit;
		}
			
		
	}
	
	function curl($url, $data=null, $method="GET", $token=null){
	
		$curl_request = curl_init();
	
		curl_setopt($curl_request, CURLOPT_URL, $url);
		curl_setopt($curl_request, CURLOPT_POST, FALSE);
		curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt( $curl_request, CURLOPT_CUSTOMREQUEST, $method );
		curl_setopt($curl_request, CURLOPT_VERBOSE, TRUE);
		if($token)
			curl_setopt($curl_request, CURLOPT_HTTPHEADER, array("Authorization: OAuth {$token}"));
		if($data) 
			curl_setopt( $curl_request, CURLOPT_POSTFIELDS, $data);
		
		$json = curl_exec($curl_request);
		curl_close($curl_request);
		
		return  json_decode($json);
	}
	
 	
	function getPageToken($token) {
		$url ='https://graph.facebook.com/'.$this->pageid.'?fields=access_token';
		$rs = $this->curl($url,"", "GET", $token);
		
		return $rs->access_token;
	}
	
	
	function publishWithPhoto($message, $thumbnails, $page_access_token) {
		$url="https://graph.facebook.com/v2.4/me/photos";
		$data = "url={$thumbnails}&caption={$message}";
		return $this->curl($url,$data, "POST", $page_access_token);
	}
	
	function publishWithMessage($title, $link, $thumbnails, $page_access_token)
	{
		$url="https://graph.facebook.com/".$this->pageid."/feed";
		$data = array(
					'access_token' => $page_access_token,
					'message' => $title,
					'link' =>$link
					);
		if($thumbnails)
			$data["picture"]=$thumbnails;
		return $this->curl($url,$data,"POST",null);
	}	
	
	function fb($title, $link, $thumbnails, $type) {
		$page_access_token = $this->getPageToken($this->user_token);
		
		if($page_access_token) {
			if($type=="ispic") {
				$message = $title." ".$link;
				return $this->publishWithPhoto($message, $thumbnails, $page_access_token);
			}
			else
				return $this->publishWithMessage($title, $link, $thumbnails, $page_access_token);
		}
		
	}
}