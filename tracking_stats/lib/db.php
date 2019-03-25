<?
class DB {
	var $dbh;
	var $error_code;

	function DB($t=false) {
		global $config;
		$this->error_code=0;
		$this->dbh = mysql_connect($config["db"]["server"], $config["db"]["username"], $config["db"]["password"]);
		if (!$this->dbh) {
			$this->error_code=-1;
			if (!$t) {
				print "Can't connect to DB";
				exit;
			}
		}
		if (!mysql_select_db($config["db"]["database"])) $this->error_code=-2;
	}

	function execute($sql) {
		global $debug;

		if ($debug) {
			$start_time = microtime();
			list($a,$b) = split(" ", $start_time);
			$start_time = $a+$b;
		}

		$sth = mysql_query($sql);
		$err = mysql_error();
		if ($err && $debug)  print "<b>SQL error</b>: ".$err."<br>";
		if ($debug) {
			$fin_time = microtime();
			list($a,$b) = split(" ", $fin_time);
			$fin_time = $a+$b;
			
			echo "\n$sql [".($fin_time-$start_time)." sec]<br>\n";
	
			if ($fin_time > $start_time)
				$sql_time += $fin_time-$start_time;

		}

		if (!$sth) {
			return false;
		};
		return $sth;
	}

	function select($sql) {
		$result = array();
		
		$sth = $this->execute($sql);
		if (!$sth) return $result;
		while ($row=mysql_fetch_array($sth))
			$result[] = $row;
		
		return $result;
	}

	function getid($table,$field,$next=0) {
		$rs = $this->select("select max($field) as mid from $table");
		return ($rs[0]["mid"] ? ($next?$rs[0]["mid"]+1:$rs[0]["mid"]) : ($next?1:0));
	}
	
	function hash($table, $fields="*", $mode=0) {
		return $this->hash_from_sql("select $fields from $table", $mode);
	}

	function hash_from_sql($sql, $mode=0) {
		$res = array();
		$sth = mysql_query($sql);
		if (!$sth) return $res;
		$key = mysql_field_name($sth,0);
		$val = mysql_field_name($sth,1);
		while ($r=mysql_fetch_array($sth)) {
			if ($mode==0)
				$res[$r[$key]] = $r[$val];
			else if ($mode==1)
				$res[$r[$val]] = $r[$key];
			else if ($mode==2)
				$res[] = array($r[$key], $r[$val]);
		}
		return $res;
	}

	function count($sql, $count="0") {
		if ($sth = mysql_query($sql)) {
			if ($count) {
				$row=mysql_fetch_array($sth);
				return (isset($row[0]) ? $row[0] : 0);
			} else return mysql_num_rows($sth);
		} else return 0;
	}

        function execute_all($sqls) {
                for ($i=0; $i<count($sqls); $i++) $this->execute($sqls[$i]);
        }

	function get_fields($table) {
		$sql="show fields from $table";
		$result = array();
		$sth = $this->execute($sql);
		if (!$sth) return $result;
		while ($row=mysql_fetch_array($sth))
			$result[]= $row["Field"];
			
		return $result;
	}
	function get_fields_type($table) {
		$sql="show fields from $table";
		$result = array();
		$sth = $this->execute($sql);
		if (!$sth) return $result;
		while ($row=mysql_fetch_array($sth))
			$result[$row["Field"]]= $row["Type"];
			
		return $result;
	}

	function get_row($sth) {
		return mysql_fetch_array($sth);
	}

	function get_array($table, $id="id", $title="title", $where="") {
		$sql="select ".($id ? "$id as id, " : "")."$title as title from $table $where";
		//echo $sql;
		$res=array();
		$sth=$this->execute($sql);
		if ($sth) {
			while ($row=mysql_fetch_array($sth)) {
				if (isset($row["id"])) $res[$row["id"]]=$row["title"];
				else $res[]=$row["title"];
			}
		}
		return $res;
	}
	
	function get_val($table, $field, $where) {
		global $db;
		$sql="select $field from $table $where";
		$sth=$this->execute($sql);
		$row=mysql_fetch_array($sth);
		return (isset($row[$field]) ? $row[$field] : "");
	}

	function tstart() { }
	function tcommit() { }
	function trollback() { }
	
	function close() {
		mysql_close($this->dbh);
	}
}

?>