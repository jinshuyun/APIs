<?php
use Aws\Polly\PollyClient;
use Aws\Credentials\CredentialProvider;

require DOCUROOT.'/tts/Lib/aws/Aws/functions.php';
require DOCUROOT. '/tts/Lib/aws/GuzzleHttp/functions.php';
require DOCUROOT. '/tts/Lib/aws/GuzzleHttp/Psr7/functions.php';
require DOCUROOT. '/tts/Lib/aws/GuzzleHttp/Promise/functions.php';
require DOCUROOT. '/tts/Lib/aws/JmesPath/JmesPath.php';

class tts_polly {
	public $client;
	public $body;
	function __construct() {
		$this->client = new PollyClient([
				'region'  => 'us-west-2',
				'version' => 'latest',
				'credentials' => [
						'key'    => POLLY_CLIENT_KEY,
						'secret' => POLLY_CLIENT_SECRET
				],
				'debug'=>true
		]);
		
	}

	
	function createSynthesizeSpeechUrl($config) {
		return $this->client->createSynthesizeSpeechPreSignedUrl($config);
		
	}
	
	function saveStream($response) {
		$handle1 = fopen($response, "r");
		$content = stream_get_contents($handle1);
		
		$filename = uniqid();
		$handle2 = fopen(DOCUROOT."/data/polly/".$filename.".mp3", "w");
		fwrite($handle2, $content);
		
		fclose($handle1);
		fclose($handle2);
		
	}
 }