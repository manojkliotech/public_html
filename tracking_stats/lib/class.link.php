<?
Class Link {
	var $array;
	var $path;

	function Link($path='') {
		$this->array=array();
		$this->path=$path;
	}

	function add($var, $val=NULL) {
		global $$var;
		$v=str_replace("\"", "''", (isset($val) ? $val : $$var));
		if ($v or isset($val)) $this->array[$var]=$v;
	}

	function parse($link) {
		global $_SERVER, $SCRIPT_NAME;
		preg_match_all("/([^\.\/]+)\.([^\/]+)\//", $link, $array);
		for ($i=0; $i<count($array[0]); $i++) {
			$exp=$array[1][$i];
			$val=urldecode($array[2][$i]);
			$val=(get_magic_quotes_gpc() ? stripslashes($val) : $val);
			$val=str_replace("''", "\"", $val);
			global $$exp;
			$$exp=$val;
		}
	}

	function get($old_type=1, $var=NULL, $array=NULL) {
		$res="";
		$array=(is_array($array) ? $array : $this->array);
		reset($array);
		$key=key($array);
		while (isset($key)) {
			$name=($var ? $var."[".$key."]" : $key);
			if (is_array($array[$key])) $res.=(($old_type and $res) ? "&" : "").$this->get($old_type, $name, $array[$key]);
			else $res.=(($old_type and $res) ? "&" : "").$name.($old_type ? "=" : ".").urlencode($array[$key]).($old_type ? "" : "/");
			next($array);
			$key=key($array);
		}
		//print $this->path.(($old_type and $res) ? (strstr($this->path, '?') ? '&' : '?') : '').$res."<br>";
		return $this->path.(($old_type and $res) ? (strstr($this->path, '?') ? '&' : '?') : '').$res;
	}

	function show() {
		print "<pre>";
		print_r($this->array);
		print "</pre>";
	}
}
?>