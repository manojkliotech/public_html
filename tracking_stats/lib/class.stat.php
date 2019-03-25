<?
if (isset($INCLUDED["class.stat"])) return;
else $INCLUDED["class.stat"]=1;

class stat {
	var $log;
	var $table;
	var $table_d;

	function add_log($text, $is_date=true, $is_enter=true, $is_print=false) {
		$text=($is_date ? date("d.m.y [H:i:s]> ") : "").$text.($is_enter ? "\n" : "");
		$this->log.=$text;
		if ($is_print) print nl2br($text);
	}

	function stat() {
		global $config;
		$this->log="";
		$this->table="stat".$config["table_prefix"];
		$this->table_d="stat_daily".$config["table_prefix"];
	}

	function add($code, $t=NULL) {
		global $db, $REMOTE_ADDR, $HTTP_X_FORWARDED_FOR;
		$ip=$REMOTE_ADDR.((isset($HTTP_X_FORWARDED_FOR) and $HTTP_X_FORWARDED_FOR) ? "/".$HTTP_X_FORWARDED_FOR : "");
		if (!isset($t)) $t=time();
		$t=($t ? mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t)) : 0);
		$this->update($code.".ALL", $t);
		if (!is_val($this->table_d, "where ip='$ip' and date_created='$t' and code='$code'")) {
			$fields="date_created, code, ip";
			$data=array(
				"date_created"	=> $t,
				"code"		=> $code,
				"ip"		=> $ip,
			);
			$sql=get_sql_insert($this->table_d, $fields, $data);
			$db->execute($sql);
			$this->update($code.".UNIQUE", $t);
		}		
	}

	function unique_by_key($code, $key, $t=NULL) {
		global $db;
		if (!isset($t)) $t=time();
		$t=($t ? mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t)) : 0);
		if (!is_val($this->table_d, "where ip='$key' and date_created='$t' and code='$code'")) {
			$fields="date_created, code, ip";
			$data=array(
				"date_created"	=> $t,
				"code"		=> $code,
				"ip"		=> $key,
			);
			$sql=get_sql_insert($this->table_d, $fields, $data);
			$db->execute($sql);
			$this->update($code.".UNIQUE", $t);
		}		
	}

	function update_sql($code, $t) {
	        return (is_val($this->table, "where code='$code' and date_created='$t'") ? 
	                "update ".$this->table." set count=count+1 where code='$code' and date_created='$t'" : 
	                "insert into ".$this->table." (date_created, code, count) values ('$t', '$code', '1')"
	        );
	}

	function update($code, $t=NULL) {
		global $db;
		if (!isset($t)) $t=time();
		$t=($t ? mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t)) : 0);
		$sql=$this->update_sql($code, $t);
	        $db->execute($sql);
		$this->add_log("Add to $code");
	}

	function remove_sql($code, $t) {
	        return (is_val($this->table, "where code='$code' and date_created='$t'") ? 
	                "update ".$this->table." set count=count-1 where code='$code' and date_created='$t'" : 
	                "insert into ".$this->table." (date_created, code, count) values ('$t', '$code', '1')"
	        );
	}

	function subtract($code, $t=NULL) {
		global $db;
		if (!isset($t)) $t=time();
		$t=($t ? mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t)) : 0);
		$sql=$this->remove_sql($code, $t);
	        $db->execute($sql);
		$this->add_log("Subtract 1 to $code");
	}

	function update_all($codes, $add_all=false, $t=NULL) {
		if (!is_array($codes)) return false;
		for ($i=0; $i<count($codes); $i++) {
			$this->update($codes[$i], $t);
			if ($add_all) $this->update("ALL_".$codes[$i], 0);
		}
	}

	function clear_day($t0) {
		global $db;
		$t0=mktime(0, 0, 0, date("m", $t0), date("d", $t0), date("Y", $t0));
		$t1=mktime(0, 0, 0, date("m", $t0), date("d", $t0)+1, date("Y", $t0));
		$sql="delete from ".$this->table." where date_created>=$t0 and date_created<$t1";
		$db->execute($sql);
		$this->add_log("Statistic from ".date("d.m.y", $t0)." deleted");
	}

	function close($dir="") {
		$filename=$dir."files/stat/stat.log";
		if ($f=fopen($filename, "a")) {
			fwrite($f, $this->log);
			fclose($f);
		} else {
			die("Can't save in file \"$filename\"");
		}
	}
}
?>