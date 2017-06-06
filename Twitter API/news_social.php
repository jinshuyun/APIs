<?php
class news_social {
	
	public $service_upload = "https://upload.twitter.com/1.1/media/upload.json";
	public $service_status = "https://api.twitter.com/1.1/statuses/update.json";
	
	public $config = array (
						"consumer_key"=>"#################",
						"consumer_secret"=>"##################",
						"access_token"=>"##############-################",
						"access_token_secret"=>"#################"
					);
	
	
	function createOAuthSignature($url,$data,$method) {
	
		$oauth_hash = 'oauth_consumer_key='.$this->config['consumer_key'].'&';
		$oauth_hash .= 'oauth_nonce=' . time() . '&';
		$oauth_hash .= 'oauth_signature_method=HMAC-SHA1&';
		$oauth_hash .= 'oauth_timestamp=' . time() . '&';
		$oauth_hash .= 'oauth_token='.$this->config['access_token'].'&';
		$oauth_hash .= 'oauth_version=1.0';
		
		if($data) {
			if(array_key_exists("media_ids", $data)) {
				$oauth_hash=$this->basestring_textmedia($oauth_hash, $data);
			}
			else{
				$oauth_hash=$this->basestring_text($oauth_hash, $data);
			}
		}
		
		$base = $method;
		$base .= '&';
		$base .= rawurlencode($url);
		$base .= '&';
		$base .= rawurlencode($oauth_hash);
		

		$key = rawurlencode($this->config["consumer_secret"]);
		$key .= '&';
		$key .= rawurlencode($this->config["access_token_secret"]);
		
		$signature = base64_encode(hash_hmac("sha1", $base, $key, true));
		
		return $signature = rawurlencode($signature);
	}
	
	function basestring_text($oauth, $data) {
		return $oauth.'&status='.$data['status'];
	}
	
	function basestring_textmedia($oauth, $data) {
		return 'media_ids='.$data['media_ids']."&".$oauth.'&status='.$data['status'];
	}
	
	function createUrlHeader($url,$data,$method) {
		
		$signature = $this->createOAuthSignature($url,$data,$method);
		
		$oauth_header = 'oauth_consumer_key="'.$this->config['consumer_key'].'", ';
		$oauth_header .= 'oauth_nonce="' . time() . '", ';
		$oauth_header .= 'oauth_signature="' . $signature . '", ';
		$oauth_header .= 'oauth_signature_method="HMAC-SHA1", ';
		$oauth_header .= 'oauth_timestamp="' . time() . '", ';
		$oauth_header .= 'oauth_token="'.$this->config['access_token'].'", ';
		$oauth_header .= 'oauth_version="1.0"';
		$curl_header = array("Authorization: OAuth {$oauth_header}");
		
		return $curl_header;
	}
	
	
	function curl($url, $data, $method, $type){
		//文字+图片
		if($type=="text") {
			$curl_header = $this->createUrlHeader($url,$data,$method);
			//文字+图片
			if(array_key_exists("media_ids", $data)) {
				$post = 'media_ids='.$data['media_ids'].'&status='.$data['status'];
			}
			//纯文字
			else{
				$post = 'status='.$data['status'];
			}
		}
		//上传图片
		else {
			$curl_header = $this->createUrlHeader($url,"",$method);
			$curl_header[] = "Content-Type: multipart/form-data";
			
			$image = file_get_contents($data);
			$imagedata = base64_encode($image);
			//debug::d($imagedata);
			$post=array("media_data" => $imagedata);
		}
			
		
		$curl_request = curl_init();
		curl_setopt($curl_request, CURLOPT_HTTPHEADER, $curl_header);
		curl_setopt($curl_request, CURLOPT_HEADER, false);
		curl_setopt($curl_request, CURLOPT_URL, $url);
		curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt( $curl_request, CURLOPT_CUSTOMREQUEST, $method );
		if($data) curl_setopt( $curl_request, CURLOPT_POSTFIELDS, $post );
		
		
		$json = curl_exec($curl_request);
		curl_close($curl_request);
		
		return  json_decode($json);
		
	}
	
	
	function twitter($title, $link, $thumbnails) {
		$title = rawurlencode($title)." ".rawurldecode($link);
		if($thumbnails) {
			$url = $this->service_upload;
			$obj= $this->curl($url,$thumbnails,"POST","image");
			$media_id = $obj->media_id;
			$url = $this->service_status;
			$data = array(
					"media_ids"=>$media_id,
					"status"=>$title	
			);
			return $this->curl($url,$data,"POST","text");
		}
		else {
			$url = $this->service_status;
			$data = array(
					"status"=>$title
			);
			return $this->curl($url,$data,"POST","text");
		}
	}
	
	
}

