<?php
set_time_limit(0);
define( 'DOCUROOT', str_replace("/deals/Cron","",dirname( __FILE__ )));
include DOCUROOT.'/inc.comm.php';

class getAmazonCatalog{
	public $amazon;
	public $catalog;
	
	function __construct(){
		$this->catalog = load('deals_catalog');
		$this->amazon = load('deals_amazon');
		$this->amazon->init();
	}
	
	private $cataloglist = array();
	
	function init(){
		$conf = conf('deals.amazoncatalog');
		
		foreach($conf as $nodeid=>$val){
			$rs = $this->catalog->getOne('*', array('uniqueid'=>$nodeid,'source'=>'amazon'));
			//if( !empty($rs) && $nodeid != '2858778011' ) continue;
			
			$this->addcatalog($val['name'], $nodeid);//当前主分类
			$this->debug("{$val['name']}:");
			
			//获得主分类下的第一级子类
			$sublist = $this->browseNode($nodeid);
			if(!empty($sublist)){
				foreach($sublist as $subnode){
					$this->addcatalog($subnode['Name'], $subnode['BrowseNodeId'],$nodeid);
					$this->debug("====>{$subnode['Name']}:");
					
					//获得主分类下的第二级子类
					$finallist = $this->browseNode($subnode['BrowseNodeId']);
					if(!empty($finallist)){
						foreach($finallist as $finalnode){
							$this->addcatalog($finalnode['Name'], $finalnode['BrowseNodeId'],$subnode['BrowseNodeId']);
							$this->debug("========>{$finalnode['Name']}");
						}
					}
				}
			}
			
			//保存分类内容
			$this->savecatalog();
			
			$this->debug("\n===========================================");
			$this->debug("{$val['name']} is OK!\n\n");
		}
	}
	
	private function addcatalog($name,$nodeid,$parentid=0){
		$this->cataloglist[]=array(
				'name'=>$name,
				'cnname'=>google::translate($name),
				'uniqueid'=>$nodeid,
				'parentid'=>$parentid,
				'source'=>'amazon'
		);
	}
	
	//遍历返回节点的子分类
	function browseNode($nodeid){
		$config = array(
		 		'BrowseNodeId'=>$nodeid,
		 		'ResponseGroup'=>'BrowseNodeInfo',
		);
		
		$resultObj = $this->amazon->BrowseNodeLookup($config);
		if(!isset($resultObj->BrowseNodes->BrowseNode->Children->BrowseNode)) return array();
		
		$result = array();
		foreach($resultObj->BrowseNodes->BrowseNode->Children->BrowseNode as $val) $result[] = array('BrowseNodeId'=>$val->BrowseNodeId,'Name'=>$val->Name);
		
		usleep(1000000);;//根据amazon方面的限制:每秒允许请求一次，此处每次执行后间隔1秒
		return $result;
	}
	
	function savecatalog(){
		$this->catalog->resetConn();
		foreach($this->cataloglist as $data){
			$rs = $this->catalog->getOne('*', array('uniqueid'=>$data['uniqueid'],'source'=>'amazon'));
			if(!empty($rs)){
				$this->debug("{$data['cnname']} exists!");
				continue;
			}
			
			$this->catalog->Insert($data);
			$this->debug("Insesrt {$data['cnname']}...");
		}
		
		$this->cataloglist=array();
	}
	
	function debug($msg){
		echo "{$msg}\n";
		flush();
	}
}

$obj = new getAmazonCatalog();
$obj->init();