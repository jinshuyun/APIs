<?php
class deals_amazon{
	public $country = 'com';
	
	public $accesskey= '###########';
	public $secretkey = '##############';
	public $AssociateTag = '#############';
	
	private $client = null;
	private $countrylist = array('de', 'com', 'co.uk', 'ca', 'fr', 'co.jp', 'it', 'cn', 'es', 'in', 'com.br');
	
	protected $webserviceWsdl = 'http://webservices.amazon.com/AWSECommerceService/AWSECommerceService.wsdl';
	protected $webserviceEndpoint = 'https://webservices.amazon.%%COUNTRY%%/onca/soap?Service=AWSECommerceService';
	
	public $debug = false;
	
	function init(){
		$conf = array('exceptions' => 1);
		if($this->debug) $conf['trace'] = 1;
		
		$this->client = new SoapClient(
				$this->webserviceWsdl,
				$conf
		);
	}
	
	/**
	 * see http://docs.aws.amazon.com/AWSECommerceService/latest/DG/ItemSearch.html
	 * http://docs.aws.amazon.com/AWSECommerceService/latest/DG/LocaleUS.html
	 * 
	 * params 说明：
	 * $params = array(
	  		'SearchIndex'=>'STRING',	//Sets the amazon category
	  		'Keywords'=>'STRING', 		//sets Keywords
	  		'ItemPage'=>'STRING', 		//Sets the resultpage to a specified value,Allows to browse resultsets which have more than one page
	  		
	  		//设定返回的数据内容
			'ResponseGroup'=>'Large,Reviews',	
										// Specifies the types of values to return. You can specify multiple response groups in one request by separating them with commas.
										// Type: String
										// Default: Small
										// Valid Values: 
										// Accessories | BrowseNodes | EditorialReview | ItemAttributes | ItemIds | 
										// Large | Medium | OfferFull | Offers | OfferSummary | Reviews | RelatedItems | 
										// SearchBins | Similarities | Small | Tracks | Variations | VariationSummary |
	   );
	 *
	 */
	public function ItemSearch($params){
		return $this->doSoapCall('ItemSearch', $params);
	}
	
	/**
	 * http://docs.aws.amazon.com/AWSECommerceService/latest/DG/SimilarityLookup.html
	 *
	 * @param array $params=>array(
			 'ItemId'=>'ASIN1,ASIN2,ASIN3', //参见 http://docs.aws.amazon.com/AWSECommerceService/latest/DG/LocaleUS.html
			 'ResponseGroup'=>'Small', //Valid Values: Accessories | BrowseNodes | EditorialReview | Images | Large | 
												 //ItemAttributes | ItemIds | Medium | Offers | OfferSummary | PromotionSummary | Reviews | SalesRank | Similarities | 
												 //Small | Tracks | Variations | VariationSummary
	 );
	 */
	public function SimilarityLookup($params){
		return $this->doSoapCall('SimilarityLookup', $params);
	}
	
	/**
	 * http://docs.aws.amazon.com/AWSECommerceService/latest/DG/ItemLookup.html
	 *
	 * @param array $params=>array(
			 'ItemId'=>"B00008OE6I", 
			 'ResponseGroup'=>'Small', 
				 //Valid Values: Accessories | BrowseNodes | EditorialReview | Images | 
							 //ItemAttributes | ItemIds | Large | Medium | OfferFull | Offers | PromotionSummary | OfferSummary| 
							 //RelatedItems | Reviews | SalesRank | Similarities | Small | Tracks | VariationImages | Variations (US only) | VariationSummary
	 );
	 */
	public function ItemLookup($params){
		return $this->doSoapCall('ItemLookup', $params);
	}
	
	/**
	 * http://docs.aws.amazon.com/AWSECommerceService/latest/DG/BrowseNodeLookup.html
	 * 
	 * @param array $params=>array(
	 		'BrowseNodeId'=>int, //参见 http://docs.aws.amazon.com/AWSECommerceService/latest/DG/LocaleUS.html
	 		'ResponseGroup'=>'BrowseNodeInfo', //Valid Values: MostGifted | NewReleases | MostWishedFor | TopSellers
	 );
	 */
	public function BrowseNodeLookup($params){
		return $this->doSoapCall('BrowseNodeLookup', $params);
	}
	
	private function doSoapCall($funcname,$params){
		$this->client->__setSoapHeaders($this->buildSoapHeader($funcname));
		$params = $this->buildRequestParams($funcname,$params);
		return $this->client->__soapCall($funcname, array($params));
	}
	
	private function _getCountry(){
		$country = in_array($this->country,$this->countrylist)?$this->country:'com';
		return $country;
	}
	
	private function buildRequestParams($funcname,$param){
		$associateTag = array('AssociateTag' => $this->AssociateTag);

		return array_merge(
				$associateTag,
				array(
						'Request' => array_merge(
								array(
										'Operation' => $funcname
								),
								$param
						)
				)
		);
	}
	
	/**
	 * Provides some necessary soap headers
	 *
	 * @param $funcname 函数名称
	 *
	 * @return array Each element is a concrete SoapHeader object
	 */
	private function buildSoapHeader( $funcname )
	{
		$timeStamp = $this->getTimeStamp();
		$signature = $this->buildSignature($funcname . $timeStamp);
	
		return array(
				new SoapHeader(
						'http://security.amazonaws.com/doc/2007-01-01/',
						'AWSAccessKeyId',
						$this->accesskey
				),
				new SoapHeader(
						'http://security.amazonaws.com/doc/2007-01-01/',
						'Timestamp',
						$timeStamp
				),
				new SoapHeader(
						'http://security.amazonaws.com/doc/2007-01-01/',
						'Signature',
						$signature
				)
		);
	}
	
	/**
	 * see http://docs.aws.amazon.com/AWSECommerceService/latest/DG/NotUsingWSSecurity.html
	 * Calculates the signature for the request
	 *
	 * @param string $request
	 *
	 * @return string
	 */
	private function buildSignature($request)
	{
		return base64_encode(hash_hmac("sha256", $request, $this->secretkey, true));
	}
	
	/**
	 * Provides the current timestamp according to the requirements of amazon
	 *
	 * @return string
	 */
	private function getTimeStamp()
	{
		return gmdate("Y-m-d\TH:i:s\Z");
	}
	
	
	function importdeal(){
		$result = array();
		
		$conf = conf('deals.source');
		$conf = $conf['amazon'];
		$content = $_POST['code'];
		$htmlarr = strings::findMeAll( $content, $conf['deal']['item']['start'], $conf['deal']['item']['end'] );
		if( empty($htmlarr) ) return $result;
		
		$datetime = times::getTime();
		foreach($htmlarr as $item){
			$tmp=array();
			$tmp['type'] = 'deal';
			$tmp['categorise'] = 'all';
			$tmp['listprice'] = '';
			$tmp['star'] = '';
			$tmp['endtime'] = 0;
			$tmp['uid']=intval($_SESSION['UserID']);
			
			$tmp['link'] = strings::findMe($item, $conf['deal']['tags']['link'][0], $conf['deal']['tags']['link'][1]);
			$tmp['link'] = substr($tmp['link'],0,strpos($tmp['link'],"/ref")+1);
			$tmp['link'] = trim($tmp['link']);
			$uuid = md5($tmp['link']);
			
			if(isset($result[$uuid])) continue; //确保内容的唯一性
			if(strstr($tmp['link'],'/s/browse/')) continue;//排除无效的地址
			
			$tmp['title']= strings::findMe($item, $conf['deal']['tags']['title'][0], $conf['deal']['tags']['title'][1]);
			$tmp['image'] = strings::findMe($item, $conf['deal']['tags']['images'][0], $conf['deal']['tags']['images'][1]);
			$tmp['text'] = $tmp['title'];
			$tmp['price']= strings::findMe($item, $conf['deal']['tags']['price'][0], $conf['deal']['tags']['price'][1]);
			
			//查找原始价格
			if(strstr($item,$conf['deal']['tags']['listprice'][0]))
				$tmp['listprice']= strings::findMe($item, $conf['deal']['tags']['listprice'][0], $conf['deal']['tags']['listprice'][1]);
			
			if(strstr($item,$conf['deal']['tags']['star'][0]))
				$tmp['star'] = strings::findMe($item, $conf['deal']['tags']['star'][0], $conf['deal']['tags']['star'][1]);
			
			//清理
			foreach($tmp as $k=>$v)$tmp[$k]=trim($v);
			
			//计算结束时间
			if(strstr($item,$conf['deal']['tags']['endtime'][0])){
				$endtimestr = strings::findMe($item, $conf['deal']['tags']['endtime'][0], $conf['deal']['tags']['endtime'][1]);
				$endtimearr = explode(":",$endtimestr);
				if(count($endtimearr)==3){
					$tmp['endtime']=$datetime+$endtimearr[0]*3600+$endtimearr[1]*60+$endtimearr[2];
				}
				if(count($endtimearr)==2){
					$tmp['endtime']=$datetime+$endtimearr[0]*60+$endtimearr[1];
				}
				if(count($endtimearr)==1){
					$tmp['endtime']=$datetime+$endtimearr[0];
				}
				
				$tmp['endtime'] = intval($tmp['endtime']);
			}
			
			//当前更新时间
			$tmp['updatetime'] = $datetime;
			$tmp['dateline'] = $datetime;
			
			if(empty($tmp['title'])||empty($tmp['price'])||empty($tmp['link'])||empty($tmp['image'])) continue;
			$result[$uuid]=$tmp;
		}
		
		return $result;
	}
	
	function importcoupon(){
		$result = array();
		
		$conf = conf('deals.source');
		$conf = $conf['amazon'];
		$content = $_POST['code'];
		$htmlarr = strings::findMeAll( $content, $conf['coupon']['item']['start'], $conf['coupon']['item']['end'] );
		
		if( empty($htmlarr) ) return $result;
		
		$datetime = times::getTime();
		foreach($htmlarr as $item){
			$tmp=array();
			$tmp['type'] = 'coupon';
			$tmp['categorise'] = 'all';
			$tmp['listprice'] = '';
			$tmp['star'] = '';
			$tmp['endtime'] = 0;
			$tmp['uid']=intval($_SESSION['UserID']);
				
			$tmp['link'] = strings::findMe($item, $conf['coupon']['tags']['link'][0], $conf['coupon']['tags']['link'][1]);
			$tmp['link'] = substr($tmp['link'],0,strpos($tmp['link'],"/ref")+1);
			$tmp['link'] = trim($tmp['link']);
			$uuid = md5($tmp['link']);
				
			if(isset($result[$uuid])) continue; //确保内容的唯一性
			if(strstr($tmp['link'],'/s/browse/')) continue;//排除无效的地址
				
			$tmp['title']= strings::findMe($item, $conf['coupon']['tags']['title'][0], $conf['coupon']['tags']['title'][1]);
			$tmp['image'] = strings::findMe($item, $conf['coupon']['tags']['images'][0], $conf['coupon']['tags']['images'][1]);
			$tmp['text'] = $tmp['title'];
			$tmp['price']= strings::findMe($item, $conf['coupon']['tags']['price'][0], $conf['coupon']['tags']['price'][1]);
				
			//清理
			foreach($tmp as $k=>$v)$tmp[$k]=trim($v);
				
			//当前更新时间
			$tmp['updatetime'] = $datetime;
			$tmp['dateline'] = $datetime;
				
			if(empty($tmp['title'])||empty($tmp['price'])||empty($tmp['link'])||empty($tmp['image'])) continue;
			$result[$uuid]=$tmp;
		}

		return $result;
	}
	
	function __destruct(){
		if($this->debug){
			echo "====== REQUEST HEADERS =====" . PHP_EOL;
			debug::d($this->client->__getLastRequestHeaders());
	
			echo "========= REQUEST ==========" . PHP_EOL;
			$content = $this->client->__getLastRequest();
			debug::d($content);
			
			echo "========= RESPONSEHEADERS ==========" . PHP_EOL;
			debug::d($this->client->__getLastResponseHeaders());
			
		}
	}
}