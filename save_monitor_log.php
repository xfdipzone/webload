<?php

$logfile = dirname(__FILE__).'/monitor.log';

$dbconfig = array(
			'host' => '192.168.1.100',
			'username' => 'username',
			'password' => 'password',
			'dbname' => 'mydb',
			'tabname' => 'monitor_log'
);

$obj = new SaveMonitorLog($dbconfig, 'myweb');
$obj->load($logfile);


// 讀取monitor log，記錄入db，查看db
class SaveMonitorLog{ // class start

	private $_apache_state = array('TIME_WAIT', 'CLOSE_WAIT', 'SYN_SENT', 'SYN_RECV', 'FIN_WAIT1', 'FIN_WAIT2', 'ESTABLISHED', 'LAST_ACK', 'CLOSING');
	private $_dbconfig = array();
	private $_site = null;


	/** init */
	public function __construct($dbconfig=array(), $site='web'){
		if(!isset($dbconfig['host']) || !isset($dbconfig['username']) || !isset($dbconfig['password']) || !isset($dbconfig['dbname']) || !isset($dbconfig['tabname'])){
			$this->debug('dbconfig error');
		}
		$this->_dbconfig = $dbconfig;
		$this->_site = $site;
		$this->connectdb();	
	}


	/** load data
	* @param  String $logfile log文件
	* @return boolean
	*/
	public function load($logfile){

		// 读取log数据
		if(file_exists($logfile)){
			$logdata = file_get_contents($logfile);
			// 清空monitor.log
			file_put_contents($logfile, '', true);
		}else{
			return false;
		}

		// 正则分析数据 [#start#]*[#end#]
		preg_match_all('/\[#start#\](.*?)\[#end#\].*?/si', $logdata, $data);

		if(isset($data[1]) && count($data[1])>0){
			$alldata = $data[1];
			foreach($alldata as $val){
				$indb = $this->parser($val);
				$newid = $this->addtodb($indb);
			}
		}

	}


	/** parser data
	* @param  Array $data
	* @return Array
	*/
	private function parser($data){
		$indb = array();
		$tmp = explode(chr(10), $data); // 按換行分隔

		$indb['site'] = $this->_site;
		$indb['addtime'] = $tmp[1];
		$indb['connects'] = array_pop(explode(':',$tmp[2]));
		$indb['cur_connects'] = array_pop(explode(':',$tmp[3]));

		for($i=5, $max=count($tmp)-2; $i<$max; $i++){
			list($key, $num) = explode(' ', $tmp[$i]);
			if(in_array($key, $this->_apache_state)){
				$indb[$key] = $num;
			}
		}

		return $indb;
	}


	/** connect db */
	private function connectdb(){
		$conn=@mysql_connect($this->_dbconfig['host'], $this->_dbconfig['username'], $this->_dbconfig['password'])  or die(mysql_error());
		mysql_select_db($this->_dbconfig['dbname'], $conn) or die(mysql_error());
	}


	/** add to db */
	private function addtodb($indb){
		$insertkey = '';
		$insertval = '';
		if($indb){
			foreach($indb as $key=>$val){
				$insertkey .= $insertkey? " ,".$key : $key;
				$insertval .= $insertval? " ,'".mysql_escape_string(trim($val))."'" : "'".mysql_escape_string(trim($val))."'";
			}
			$sqlstr = "insert into ".$this->_dbconfig['tabname']."($insertkey) values($insertval)";
			$query = @mysql_query($sqlstr) or die(mysql_error());
			$id = mysql_insert_id();
			return $id? $id : false;
		}
	}


	/** debug */
	private function debug($msg){
		exit($msg."\r\n");
	}


} // class end

?>