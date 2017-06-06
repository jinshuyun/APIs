<?php
if(!isset($_SESSION))
	session_start();
require_once __DIR__."/facebook-sdk-v5/autoload.php";

class news_fb_login {
	public $pageid = "###############";
	public $appid = "#####################";
	public $app_secret = "##############################";
	
	function __construct() {

		if(!$this->checkUserID($_GET['userid'])) go("/");
	}
	
	function index() {
		$fb = new Facebook\Facebook(array(
				'app_id' => $this->appid, // Replace {app-id} with your app id
				'app_secret' => $this->app_secret,
				'default_graph_version' => 'v2.7',
		));
		
		$helper = $fb->getRedirectLoginHelper();
		// 		echo $_SESSION['FBRLH_state'];
		
		$permissions = array('manage_pages', 'publish_pages'); // Optional permissions
		
		$subid=empty($_GET['subid'])?"":$_GET['subid'];
		$postid=empty($_GET['postid'])?"":$_GET['postid'];
		
		$_SESSION['social']	= array(
				"subid"=>$subid,
				"postid"=>$postid
		);
// 		debug::d($_SESSION);
		$loginUrl = $helper->getLoginUrl('http://beta.wenxuecity.com/bbs/index.php?act=fbcallback&subid=cooking&postid=1485938', $permissions);
		
		echo '<a href="' . htmlspecialchars($loginUrl) . '">Log in with Facebook!</a>';
	}

	
	private function checkUserID($userid) {
		$obj = load("members_tools");
		$userid = $obj->decrypt_password($userid);
		
		$members = load("members_user");
		$list = $members->getAll("*", array("usergroupid"=>6, "userid"=>$userid));
		if($list)
			return true;
		return false; 
	}
}