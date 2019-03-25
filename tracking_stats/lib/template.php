<?

// version 2.1.3

// {VAR}
// {global:VAR} - useful in {rows}
// {rowcount:VAR} - number of {rows} named "VAR"
// {if:VAR}..{/if:VAR} : VAR can be VAR1&VAR2 VAR1|VAR2 !VAR rows:ROWNAME (no recursive ifs)
// {unless:VAR}..{/unless:VAR} : VAR can be VAR1&VAR2 VAR1|VAR2 !VAR rows:ROWNAME (no recursive unlesses)
// {row:VAR}..{/row:VAR} : VAR1&VAR2 VAR1|VAR2 !VAR inside (no recursive rows). Row name can be array with several rows names
// {rs:VAR} : define a dictionary file ('VAR=value' strings) and call dictionary("dict.file");
// {leave:VAR} : bubbling vars, call evaluate(1) to clear undefined vars
// {include:FILE} : path relative to template's location
// {section:VAR} : possible not:VAR (like {if:!VAR}) or VAR:default (if not turned off) or VAR:none-defined (if no sections defined in code)
// global $tmpdebug forces debug info instead of normal output

class Template {
	var $src;
	var $path;
	var $vals;
	var $dict;
	var $rows;
	var $sections;

	function Template($fn="") {
		if ($fn) {
			$this->src = $fn;
			$fn = explode("/", $fn);
			$this->path = join("/", array_slice($fn, 0, sizeof($fn)-1));
		} else
			$this->src = "";
		$this->vals = "";
	}
	
	function assign($array,$val="") {
		if (is_array($array)) {
			@reset($array);
			while (list($key,$value)=@each($array))
				$this->vals[$key] = $value;
		} else if ($val)
			$this->vals[$array] = $val;
	}
	
	function dictionary($name) {
		$this->dict = $name;
	}
	
	function addrow($name,$arr) {
		if (!is_array($arr)) return;
		if (is_array($name))
			for ($i=0; $i<sizeof($name); $i++)
				$this->rows[$name[$i]][] = $arr;
		else
			$this->rows[$name][] = $arr;
	}
	
	function section($name,$onoff=1) {
		$this->sections[$name] = $onoff;
	}
	
	function evaluate($clear=0) {
		global $tmpdebug;
	
		$fc = "";
		$error = 0;
		$dct = array();

		if ($this->src)
		
			if (is_file($this->src)) {
				if ($fl = fopen($this->src,"r")) {
					$fc = fread($fl,filesize($this->src));
					fclose($fl);
				} else {
					$fc="Template error: can't open file ".$this->src;
					$error = 1;
				}
			} else {
				$fc = "Template error: file ".$this->src." not found";
				$error = 1;
			}
		else {
			$fc = "Template error: file not defined";
			$error = 1;
		}
		
		if ($tmpdebug) {
			$log = "<table border=0 cellspacing=5 cellpadding=0>\n<tr>\n<td colspan=4><b>".$this->src."</b></td>\n</tr>\n";
			while (list($a,$b)=@each($this->sections))
				$log .= "<tr>\n<td width=20>&nbsp;</td>\n<td colspan=3>section <b>$a</b> ".($b?"on":"off")."</td>\n</tr>\n";
			while (list($a,$b)=@each($this->vals))
				$log .= "<tr>\n<td width=20>&nbsp;</td>\n<td colspan=3>&#149 $a = $b</td>\n</tr>\n";
			while (list($a,$b)=@each($this->rows)) {
				for ($i=0; $i<sizeof($b); $i++) {
					$log .= "<tr>\n<td width=20>&nbsp;</td>\n<td colspan=3>row <b>$a</b> ".($i+1)."</td>\n</tr>\n";
					while (list($c,$d)=@each($b[$i]))
						$log .= "<tr>\n<td width=20>&nbsp;</td>\n<td width=20>&nbsp;</td>\n<td colspan=2>&#149; $c = $d</td>\n</tr>\n";
				}
			}
			$log .= "</table>\n";
			return $log;
		}

		while (strpos(" ".$fc, "{section:")>0) {
			$items = explode("{section:", $fc);
			$str = $items[0];
			for ($i=1;$i<sizeof($items);$i++) {
				$its = explode("}", $items[$i]);
				@list($rname,$default) = explode(":", $its[0]);
				$rcont = join("}", array_slice($its, 1));
				list($rcont,$fc1) = explode("{/section}", $rcont, 2);
				if (($rname=="not"&&!$this->sections[$default]) || $this->sections[$rname] || $default=="default"&&!isset($this->sections[$rname]) || $default=="none-defined"&&!sizeof($this->sections))
					$str .= $rcont;
				else
					if (substr($fc1,0,1)=="\n") $fc1 = substr($fc1,1);
				$str .= $fc1;
			}
		
			$fc = $str;
		}
		
		if ($this->dict && is_file($this->dict)) {
			$fl = fopen($this->dict, "r");
			$dfc = fread($fl, filesize($this->dict));
			fclose($fl);
	
			$dfc = str_replace("\r", "", $dfc);
			$strs = split("\n", $dfc);
	
			while (list($id,$str)=each($strs)) {
				if ($str && !preg_match("/^\#/", $str)) {
					$params = split("=", $str);
					$param = $params[0];
					$params[0] = "";
					$val = substr(join("=", $params),1);
					$dct[$param] = trim($val);
				}
			}
		}
		
		while (strpos(" ".$fc,"{include:")>0) {
			$items = explode("{include:",$fc);
			$str = $items[0];
			for ($i=1; $i<sizeof($items); $i++) {
				$its = explode("}", $items[$i]);
				if (is_file($this->path."/".$its[0]))
					$str .= join("\n", file($this->path."/".$its[0]));
				else
					$str .= "Template error: can't include ".$this->path."/".$its[0]." in ".$this->src.", file not found";
				$str .= join("}", array_slice($its, 1));
			}
			$fc = $str;
		}

		if (strpos(" ".$fc,"{row:")>0) {
			$items = explode("{row:", $fc);
			$str = $items[0];
			for ($i=1;$i<sizeof($items);$i++) {
				$its = explode("}", $items[$i]);
				$rname = $its[0];
				$rcont = join("}", array_slice($its, 1));
				$rcont = str_replace("\r","", $rcont);
				if (substr($rcont,0,1)=="\n") $rcont = substr($rcont,1);
				list($rcont,$fc1) = explode("{/row}", $rcont, 2);
				for ($j=0;$j<sizeof($this->rows[$rname]);$j++) {
					if (!is_array($this->rows[$rname][$j])) continue;
					$row = $rcont;
	
					if (strpos(" ".$row,"{delimiter}")>0) {
						$_items = explode("{delimiter}", $row);
						$_str = $_items[0];
						for ($_i=1;$_i<sizeof($_items);$_i++) {
							list($delim, $rest) = explode("{/delimiter}",$_items[$_i]);
							if ($j<sizeof($this->rows[$rname])-1)
								$_str .= $delim;
							$_str .= $rest;
						}
						$row = $_str;
					}
					
					if (strpos(" ".$row,"{if:")>0) {
						$_items = explode("{if:", $row);
						$_str = $_items[0];
						for ($_i=1;$_i<sizeof($_items);$_i++) {
							$_its = explode("}", $_items[$_i]);
							$_rname = $_its[0];
							$_rcont = join("}", array_slice($_its, 1));
							list($_rcont,$_fc1) = explode("{/if}", $_rcont, 2);
							if (strpos(" ".$_rname, "|")) {
								$_rnames = explode("|", $_rname);
								for ($__i=0; $__i<sizeof($_rnames); $__i++)
									if ($this->rows[$rname][$j][$_rnames[$__i]]) {
										$_str .= $_rcont;
										break;
									}
							} else if (strpos(" ".$_rname, "&")) {
								$_rnames = explode("&", $_rname);
								$ok = 1;
								for ($__i=0; $__i<sizeof($_rnames); $__i++)
									if (!$this->rows[$rname][$j][$_rnames[$__i]])
										$ok = 0;
								if ($ok)
									$_str .= $_rcont;
							} else if (substr($_rname,0,1)=="!") {
								/*if (!$this->rows[$rname][$j][substr($_rname,1)])
									$_str .= $_rcont;*/
							} else if (substr($_rname,0,7)=="global:") {
								if ($this->vals[substr($_rname,7)])
									$_str .= $_rcont;
							} else if ($this->rows[$rname][$j][$_rname])
								$_str .= $_rcont;
							else 
								if (substr($_fc1,0,1)=="\n") $_fc1 = substr($_fc1,1);
							$_str .= $_fc1;
						}
						$row = $_str;
					}
	
					if (strpos(" ".$row,"{unless:")>0) {
						$_items = explode("{unless:", $row);
						$_str = $_items[0];
						for ($_i=1;$_i<sizeof($_items);$_i++) {
							$_its = explode("}", $_items[$_i]);
							$_rname = $_its[0];
							$_rcont = join("}", array_slice($_its, 1));
							list($_rcont,$_fc1) = explode("{/unless}", $_rcont, 2);
							if (substr($_rcont,0,1)=="\n") $_rcont = substr($_rcont,1);
							if (substr($_fc1,0,1)=="\n") $_fc1 = substr($_fc1,1);
							if (strpos(" ".$_rname, "&")) {
								$_rnames = explode("&", $_rname);
								for ($__i=0; $__i<sizeof($_rnames); $__i++)
									if (!$this->rows[$rname][$j][$_rnames[$__i]]) {
										$_str .= $_rcont;
										break;
									}
							} else if (strpos(" ".$_rname, "|")) {
								$_rnames = explode("|", $_rname);
								$ok = 1;
								for ($__i=0; $__i<sizeof($_rnames); $__i++)
									if ($this->rows[$rname][$j][$_rnames[$__i]])
										$ok = 0;
								if ($ok)
									$_str .= $_rcont;
							} else if (substr($_rname,0,1)=="!") {
								if ($this->rows[$rname][$j][substr($_rname,1)])
									$_str .= $_rcont;
							} else if (substr($_rname,0,7)=="global:") {
								if (!$this->vals[substr($_rname,7)])
									$_str .= $_rcont;
							} else if (!$this->rows[$rname][$j][$_rname])
								$_str .= $_rcont;
							else 
								if (substr($_fc1,0,1)=="\n") $_fc1 = substr($_fc1,1);
							$_str .= $_fc1;
						}
						$row = $_str;
					}

					@reset($this->rows[$rname][$j]);
					while (list($k,$v)=@each($this->rows[$rname][$j])) {
						$row = str_replace('{'.$k.'}', $v, $row);
					}
					$row = preg_replace("/\{[\w\d]+\}/", "", $row);
					$str .= $row;
				}
				$fc1 = str_replace("\r","", $fc1);
				if (substr($fc1,0,1)=="\n") $fc1 = substr($fc1,1);
				$str .= trim($fc1);
			}
		
			$fc = $str;
		}
		
		if (strpos(" ".$fc, "{unless:")>0) {
			$items = explode("{unless:", $fc);
			$str = $items[0];
			for ($i=1;$i<sizeof($items);$i++) {
				$its = explode("}", $items[$i]);
				$rname = $its[0];
				$rcont = join("}", array_slice($its, 1));
				list($rcont,$fc1) = explode("{/unless}", $rcont, 2);
				if (strpos(" ".$rname, "&")) {
					$_rnames = explode("&", $rname);
					for ($_i=0; $_i<sizeof($_rnames); $_i++)
						if (!$this->vals[$_rnames[$_i]]) {
							$str .= $rcont;
							break;
						}
				} else if (strpos(" ".$rname, "|")) {
					$_rnames = explode("|", $rname);
					$ok = 1;
					for ($_i=0; $_i<sizeof($_rnames); $_i++)
						if ($this->vals[$_rnames[$_i]])
							$ok = 0;
					if ($ok)
						$str .= $rcont;
				} else if (substr($rname,0,1)=="!") {
					if ($this->vals[substr($rname,1)])
						$str .= $rcont;
				} else if (substr($rname,0,5)=="rows:") {
					if (0==sizeof($this->rows[substr($rname,5)]))
						$str .= $rcont;
				} else if (!$this->vals[$rname])
					$str .= $rcont;
				else 
					if (substr($fc1,0,1)=="\n") $fc1 = substr($fc1,1);
				$str .= $fc1;
			}
			$fc = $str;
			
		}
		
		if (strpos(" ".$fc, "{if:")>0) {
			$items = explode("{if:", $fc);
			$str = $items[0];
			for ($i=1;$i<sizeof($items);$i++) {
				$its = explode("}", $items[$i]);
				$rname = $its[0];
				$rcont = join("}", array_slice($its, 1));
				list($rcont,$fc1) = explode("{/if}", $rcont,2);
				if (strpos(" ".$rname, "|")) {
					$_rnames = explode("|", $rname);
					for ($_i=0; $_i<sizeof($_rnames); $_i++)
						if ($this->vals[$_rnames[$_i]]) {
							$str .= $rcont;
							break;
						}
				} else if (strpos(" ".$rname, "&")) {
					$_rnames = explode("&", $rname);
					$ok = 1;
					for ($_i=0; $_i<sizeof($_rnames); $_i++)
						if (!$this->vals[$_rnames[$_i]])
							$ok = 0;
					if ($ok)
						$str .= $rcont;
				} else if (substr($rname,0,1)=="!") {
					if (!$this->vals[substr($rname,1)])
						$str .= $rcont;
				} else if (substr($rname,0,5)=="rows:") {
					if (sizeof($this->rows[substr($rname,5)]))
						$str .= $rcont;
				} else if ($this->vals[$rname])
					$str .= $rcont;
				else 
					if (substr($fc1,0,1)=="\n") $fc1 = substr($fc1,1);
				$str .= $fc1;
			}
		
			$fc = $str;
		}

		if (is_array($this->vals) && !$error) {
			reset($this->vals);
			while (list($key,$value)=each($this->vals)) {
				$fc = str_replace("{".$key."}", $value, $fc);
				$fc = str_replace("{global:".$key."}", $value, $fc);
				$fc = str_replace("{leave:".$key."}", $value, $fc);
			}
		}
		
		if (strpos(" ".$fc, "{rowcount:"))
			if (is_array($this->rows)) {
				@reset($this->rows);
				while (list($a,$b)=@each($this->rows))
					$fc = str_replace("{rowcount:$a}", sizeof($b), $fc);
			}
		
		if (is_array($dct)) {
			while (list($k,$v)=each($dct)) {
				$fc = str_replace('{rs:'.$k.'}', $v, $fc);
			}
		}
		
		if (!$error) {
			if (!$clear) $fc = str_replace("{leave:","[#5leave6%@",$fc);
			$fc = preg_replace("/{[\.\:\w\d]*?}/", "", $fc);
			if (!$clear) $fc = str_replace("[#5leave6%@","{",$fc);
		}

		return preg_replace("/\n+/", "\n", str_replace("\r","",$fc));
	}
	
	function reset() {
		$this->vals = array();
		$this->rows = array();
	}
}

?>