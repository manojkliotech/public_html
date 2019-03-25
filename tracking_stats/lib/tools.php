<?
if (isset($tools_already_included) and $tools_already_included) return;
$tools_already_included = 1;

function getFile($fn, $type='r') {
	if ($fl=fopen($fn, $type)) {
		$res=fread($fl, filesize($fn));
		fclose($fl);
	} else $res='';
	return $res;
}

function getConfig($fn) {
        $config = "";

        if (!$fn)
                return $config;
        if (!is_file($fn))
                return $config;
        
        $fl = fopen($fn, "r");
        $fc = fread($fl, filesize($fn));
        fclose($fl);
        
        $fc = str_replace("\r", "", $fc);
        $strs = split("\n", $fc);
        
        while (list($id,$str)=each($strs)) {
                $str = str_replace(array("\r","\n"),array("",""),$str);
                if ($str && !preg_match("/^\#/", $str))
                        $params = split("=", $str);
                        $param = $params[0];
                        $params[0] = "";
                        $val = substr(join("=", $params),1);
                        $names = split("\.", $param);
                        if (!strlen($names[0])) continue;

                        $aval = split("::", $val);
                        if (sizeof($aval)>1)
                            $val = $aval;
                        
                        $pr = "\$config[\"".join("\"][\"", $names)."\"]=".(is_array($val)?"array(\"".join("\",\"",$val)."\")":"\"$val\"").";";
                        eval($pr);
        }
        
        return $config;
}

function getDict($filename='') {
	$res=array();
	if ($filename && is_file($filename)) {
		if ($f=fopen($filename, "r")) {
			$dict=fread($f, filesize($filename));
			fclose($f);
		}
		$dict=str_replace("\r", "", $dict);
		$dict=split("\n", $dict);
		while (list($id,$str)=each($dict)) {
			if ($str && !preg_match("/^\#/", $str)) {
				$pos=strpos($str, '=');
				if ($pos) {
					$name=substr($str, 0, $pos);
					$value=trim(substr($str, $pos+1));
					$res[$name]=$value;
				}
			}
		}
	}
	return $res;
}



function tmst($time) {
//      24-08-2005 18:52:06

        $dy = substr($time, 0, 2);
        $mn = substr($time, 3, 2);
        $yr = substr($time, 6, 4);
        $hr = substr($time, 11, 2);
        $mi = substr($time, 14, 2);
        $sc = substr($time, 17, 2);
        
        return mktime($hr, $mi, $sc, $mn, $dy, $yr);
}

function cleanup($str) {
        $limit = 50;
        $str = str_replace(">", "&gt;", $str);
        $str = str_replace("<", "&lt;", $str);
        $its = explode(" ",$str);
        for ($i=0;$i<sizeof($its);$i++)
                if (strlen($its[$i])>$limit)
                        $its[$i] = substr($its[$i], 0, $limit)."[...]";
        $str = join(" ", $its);
        return $str;
}

function get_offsets($count,$limit,$link,$offset) {
        global $rs, $subsite, $sid;

    $offsets = "";
    if ($count>$limit) {
                $items = ceil($count/$limit);
        
                $mn = $offset-3;
                $mx = $offset+3;
                if ($mn<0) { $mx -= $mn; $mn = 0; }
                if ($mx>=$items) { $mn-=($mx-$items+1); $mx = $items-1; }
                if ($mn<0) $mn = 0;
                
                for ($i=$mn; $i<=$mx; $i++) {
                    $offsets .= ($i!=$offset?"<a href='".$link."$i/' class='year'>":"<span class='active'>[");
                    $offsets .= ($i+1);
                    $offsets .= ($i!=$offset?"</a>":"]</span>")." | ";
                }
                if ($mx<$items-1)
                        $offsets .= "<a href='".$link.($mx+1)."/' class='year'>...</a> | ";
                if ($mn>0)
                        $offsets = "<a href='".$link.($mn-1)."/' class='year'>...</a> | ".$offsets;
                if ($offset>0 && $offset<$items-1)
                        $offsets = "<a  href='".$link.($offset-1)."/' class='year'>{rs:PREV}</a> | ".$offsets." <a href='".$link.($offset+1)."/' class='year'>{rs:NEXT}</a>";
                else if ($offset>0)
                        $offsets = "<a href='".$link.($offset-1)."/' class='year'>{rs:PREV}</a> | ".$offsets;
                else if (!$offset)
                        $offsets .= "<a href='".$link.($offset+1)."/' class='year'>{rs:NEXT}</a>";
//              $offsets .= (substr($offsets,-2)=="| "?"":" | ")."�����: ".$count;
    }
    return $offsets;
}

function get_calendar($days, $append) {
        global $dt;
        
        if (!is_array($days) || !sizeof($days))
                $days = array();
        if ($dt) {
                $dy = substr($dt,6,4)*1;
                $mn = substr($dt,4,2)*1;
                $yr = substr($dt,0,4)*1;
        } else {
                $dy = date("d");
                $mn = date("m");
                $yr = date("Y");
        }
        $cal = "<tr bgcolor=#bccee6>\n";
        for ($i=1;$i<wday(mktime(5,5,5,$mn,1,$yr));$i++)
                $cal .= "<td>&nbsp;</td>\n";
        for ($i=1;$i<=date("t",mktime(5,5,5,$mn,1,$yr));$i++) {
                $k = $i;
                if (wday(mktime(5,5,5,$mn,$i,$yr))==7) $k = "<b>$k</b>";
                if ($dy==$i)
                        $cal .= "<td align=right bgcolor=ffa300>$k</td>\n";
                else if (in_array($i,$days))
                        $cal .= "<td align=right><a class=calendara href=?dt=".date("Ymd",mktime(1,1,1,$mn,$i,$yr)).">$k</a></td>\n";
                else
                        $cal .= "<td align=right class=calendar>$k</td>\n";
                if (wday(mktime(5,5,5,$mn,$i,$yr))==7) {
                        $cal .= "</tr>\n";
                        if ($i<date("t",mktime(5,5,5,$mn,1,$yr)))
                                $cal .= "<tr bgcolor=#bccee6>\n";
                }
        }
        for ($i=wday(mktime(5,5,5,$mn,date("t",mktime(5,5,5,$mn,1,$yr)),$yr));$i<7;$i++)
                $cal .= "<td>&nbsp;</td>\n";
        $cal .= "</tr>";

        for ($i=1;$i<=12;$i++)
                $months .= "<option value=".($i<10?"0":"").$i." ".($i==$mn?"selected":"").">{rs:MONTH".$i."}";
        for ($i=date("Y",time()-3*365*24*3600);$i<=date("Y",time()+3*365*24*3600);$i++)
                $years .= "<option value=$i ".($i==$yr?"selected":"").">$i";

        $cl = new Template("templates/calendar.htm");
        $cl->assign(array(
                DAYS    => $cal,
                PDT             => date("Ymd", mktime(5,5,5,$mn-1,date("t",mktime(5,5,5,$mn-1,1,$yr)),$yr)),
                NDT             => date("Ymd", mktime(5,5,5,$mn+1,1,$yr)),
                PYR             => date("Y", mktime(5,5,5,$mn-1,1,$yr)),
                PMN             => date("n", mktime(5,5,5,$mn-1,1,$yr)),
                NYR             => date("Y", mktime(5,5,5,$mn+1,1,$yr)),
                NMN             => date("n", mktime(5,5,5,$mn+1,1,$yr)),
                EXTRA   => $extra,
                MONTH   => "{rs:1MONTH".(1*$mn)."} $yr",
        ));

        return $cl->evaluate();
}

function our_date($tmst,$needtime=0) {
        global $rs, $lang;
        
        $dt = date("j.m.Y",$tmst);
        if ($needtime)
                $dt .= " / ".date("H:i",$tmst);
        return $dt;
}

function wday($tm) {
        $w = date("w",$tm);
        if ($w==0) return 7;
        else return $w;
}

function resize_image($src, $dest, $size) {
        if (!is_file($src)) return;

        list($wd,$hg,$sh,$it)=getimagesize($src);
        if ($wd<=$size&&$hg<=$size) {
                copy($src, $dest);
                return;
        }
        if ($wd>$hg) {
                $wd1 = $size;
                $hg1 = $hg/($wd/$size);
        } else {
                $hg1 = $size;
                $wd1 = $wd/($hg/$size);
        }

        $i1 = imagecreatefromjpeg($src);
        if (function_exists("imagecreatetruecolor")) {
                $i2 = imagecreatetruecolor($wd1,$hg1);
                imagecopyresampled($i2, $i1, 0, 0, 0, 0, $wd1, $hg1, $wd, $hg);
        } else {
                $i2 = imagecreate($wd1,$hg1);
                imagecopyresized($i2, $i1, 0, 0, 0, 0, $wd1, $hg1, $wd, $hg);
        }
        imagejpeg($i2,$dest,100);
}

function get_monday_tmst($dt) {
        if (!$dt) return 0;
        $wd = date("w",tmst($dt));
        if ($wd==0) $wd = 7;
        $ndt = tmst($dt)-($wd-1)*24*3600;
        return mktime(0,0,0,date("n",$ndt),date("j",$ndt),date("Y",$ndt));
}

function track_time($task,$output=0) {
        static $tasks;
        if ($output) {                  // output result time
                $st = explode(" ",$tasks[$task]);
                $st = $st[0]+$st[1];
                $fn = explode(" ",microtime());
                $fn = $fn[0]+$fn[1];
                print "<p><b>$task</b>: ".($fn-$st)." seconds\n";
        } else
                $tasks[$task] = microtime();
}

function initrand() {
	list($usec, $sec) = explode(' ', microtime());
	srand((float) $sec + ((float) $usec * 100000));
}

function begins($str,$strs) {
        return (substr($str,0,strlen($strs))==$strs?1:0);
}

function forumquotes($str) {
        while ($i = strpos(" $str", "[quote]")) {
                for ($j=$i+7; $j<strlen($str); $j++) {
                        if (substr($str,$j,8)=="[/quote]") {
                                $str = trim(substr($str, 0, $i-1)) . "<div class=quote>".trim(substr($str, $i+7, $j-$i-7))."</div>" . trim(substr($str, $j+8));
                                break;
                        }
                }
                if (substr($str, $i-1, 7)=="[quote]")
                        $str = substr($str, 0, $i-1).substr($str, $i+6);
        }
        return $str;
}

function gencookie($len=15) {
        list($usec, $sec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));
    
        $l = "QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm1234567890";
        for ($i=0; $i<$len; $i++)
                $res .= substr($l, mt_rand()%strlen($l), 1);
        
        return $res;
}

function check_date($date, $format="d.m.Y") {
        if ($date) {
		preg_match_all("/([dmYHis])/", $format, $var);
		$p=array("d", "m", "Y", "H", "i", "s");
		$r=array("(\d{2})", "(\d{2})", "(\d{2,4})", "(\d{2})", "(\d{2})", "(\d{2})");
		$format=str_replace($p, $r, $format);
		preg_match("/$format/", $date, $array);
		for ($ii=0; $ii<count($var[1]); $ii++) $$var[1][$ii]=$array[$ii+1];
		$d=(isset($d) ? $d : 0);
		$m=(isset($m) ? $m : 0);
		$Y=(isset($Y) ? $Y : 0);
		$H=(isset($H) ? $H : 0);
		$i=(isset($i) ? $i : 0);
		$s=(isset($s) ? $s : 0);

		if (!checkdate($m, $d, $Y) or $H>23 or $i>59 or $s>59) return 0;
                $date=mktime($H, $i, $s, $m, $d, $Y);
        }
        return $date;
}

function get_row_from_array($name, $array, $id, $p="page") {
	global $$p;
	$first=true;
	if (!is_array($array)) return false;
	$id=(is_array($id) ? $id : (isset($id) ? array($id) : array()));
        reset($array);
        $key=key($array);
        while (isset($key)) {
                $$p->addrow($name, array(
                        "id"    	=> $key,
                        "title" 	=> $array[$key],
                        "selected"      => (in_array($key, $id) ? " selected" : ""),
			"first"		=> $first,
                ));
                next($array);
		$first=false;
                $key=key($array);
	}
}

function add_to_link($exp, $val) {
        global $link, $offset_link, $$exp;
        $val=(isset($val) ? $val : $$exp);
        $link.=urlencode($val)."/";
        if ($exp!="offset") $offset_link.=urlencode($val)."/";
}

function add_to_tree($title, $url="", $first=0) {
        global $page;
        $page->addrow("TREE", array(
                first   => $first,
                title   => $title,
                url     => $url,
        ));
}

function get_new_offsets($count,$limit,$link,$offset) {
        global $rs, $subsite, $sid;
	$link.=($link ? '&' : '?');
	$offsets = "";
	if ($count>$limit) {
                $items = ceil($count/$limit);
        
                $mn = $offset-2;
                $mx = $offset+2;
                if ($mn<0) { $mx -= $mn; $mn = 0; }
                if ($mx>=$items) { $mn-=($mx-$items+1); $mx = $items-1; }
                if ($mn<0) $mn = 0;
                
                for ($i=$mn; $i<=$mx; $i++) {
                    $offsets.= ($i!=$offset ? "<a href=\"{$link}offset={$i}\" class=\"item\">" : '<div class="active">');
                    $offsets.= ($i+1);
                    $offsets.= ($i!=$offset ? '</a>' : '</div>');
                }
                if ($mx<$items-1)
                        $offsets.= "<a href=\"{$link}offset=".($mx+1)."\" class=\"item\">...</a>";
                if ($mn>0)
                        $offsets = "<a href=\"{$link}offset=".($mn-1)."\" class=\"item\">...</a>".$offsets;
                if ($offset>0 && $offset<$items-1)
                        $offsets = "<a  href=\"{$link}offset=".($offset-1)."\" class=\"item\">{rs:PREV}</a>{$offsets}<a href=\"{$link}offset=".($offset+1)."\" class=\"item\">{rs:NEXT}</a>";
                else if ($offset>0)
                        $offsets = "<a href=\"{$link}offset=".($offset-1)."\" class=\"item\">{rs:PREV}</a>".$offsets;
                else if (!$offset)
                        $offsets.= "<a href=\"{$link}offset=".($offset+1)."\" class=\"item\">{rs:NEXT}</a>";
	}
	return ($offsets ? "<div class=\"offset_container\">{$offsets}</div><div class=\"br\"></div>" : '');
}

function get_account($table="", $id=0) {
	global $db, $config;
	$table.=$config["table_prefix"];
	$sql="select * from $table where id='$id'";
	$rs=$db->select($sql);
	if (isset($rs[0]["id"])) {
		$res=array(
			"login"		=> $rs[0]["login"],
			"password"	=> $rs[0]["password"],
			"host"		=> $rs[0]["host"],
			"port"		=> $rs[0]["port"],
		);
		if (isset($rs[0]["mail"])) $res["mail"]=$rs[0]["mail"];
		if (isset($rs[0]["email"])) $res["email"]=$rs[0]["email"];
		if (isset($rs[0]["bounce_type"])) $res["bounce_type"]=$rs[0]["bounce_type"];
		if (isset($rs[0]["bounce_count"])) $res["bounce_count"]=$rs[0]["bounce_count"];
	} else {
		$res=array(
			"login"		=> '',
			"password"	=> '',
			"host"		=> '',
			"port"		=> '',
		);
	}
	return $res;
}

function load_settings($owner_id=0) {
	global $db, $ui, $config;
	$table="settings".$config["table_prefix"];
	$table_u="users".$config["table_prefix"];
	$user_id=$ui["id"];
	$sql="select * from $table where owner_id=$user_id";
	$rs=$db->select($sql);
	$date_format_list=array(
		0 => "m.d.Y",
		1 => "d.m.Y",
		2 => "Y.m.d",
	);
	if (isset($rs[0][0])) {
		if ($rs[0]["mail_max_hour"]=="" || $rs[0]["mail_max_hour"]=="0"){
			// get the admin max mail per hour
			$sql="select * from $table where owner_id = (select id from $table_u where name='Super Administrator')";
			$rs_admin=$db->select($sql);
			$rs[0]["mail_max_hour"] = $rs_admin[0]["mail_max_hour"];
			$rs[0]["mail_from"] = $rs_admin[0]["mail_from"];
		}
		$res=array(
			"mail_mode"		=> $rs[0]["mail_mode"],
			"mail_from_name"	=> $rs[0]["mail_from_name"],
			"mail_from"		=> $rs[0]["mail_from"],
			"encoding"		=> $rs[0]["encoding"],
			"mail_count"		=> $rs[0]["mail_count"],
			"rec_count"		=> $rs[0]["rec_count"],
			"date_format"		=> $date_format_list[$rs[0]["date_format"]],
			"is_quick_steps"	=> $rs[0]["is_quick_steps"],
			"email_activation"	=> $rs[0]["email_activation"],
			"unsubscribe_confirm"	=> $rs[0]["unsubscribe_confirm"],
			"company_name"		=> $rs[0]["company_name"],
			"company_address"		=> $rs[0]["company_address"],
			"mail_max_hour"			=> $rs[0]["mail_max_hour"],
			"tech_email"			=> $rs[0]["tech_email"],
		);
		if ($res["mail_mode"]) {
			$res["autoresponder"]["smtp"]=get_account("smtp_accounts", $rs[0]["autoresponder_smtp"]);
			$res["autoresponder"]["pop3"]=get_account("pop3_accounts", $rs[0]["autoresponder_pop3"]);
			$res["campaign_schedule"]["smtp"]=get_account("smtp_accounts", $rs[0]["campaign_schedule_smtp"]);
			$res["campaign_schedule"]["pop3"]=get_account("pop3_accounts", $rs[0]["campaign_schedule_pop3"]);
		}
	} else {
		//if ($rs[0]["mail_max_hour"]=="" || $rs[0]["mail_max_hour"]=="0"){
			// get the admin max mail per hour
			$sql="select * from $table where owner_id = 1";
			$rs=$db->select($sql);
			$rs[0]["mail_max_hour"] = $rs_admin[0]["mail_max_hour"];
		//}
			if ($rs[0]["mail_max_hour"]=="" || $rs[0]["mail_max_hour"]=="0"){
				// get the admin max mail per hour
				$sql="select * from $table where owner_id = 1";
				$rs_admin=$db->select($sql);
				$rs[0]["mail_max_hour"] = $rs_admin[0]["mail_max_hour"];
			}
		$res=array(
			"mail_mode"		=> $rs[0]["mail_mode"],
			"mail_from_name"	=> $rs[0]["mail_from_name"],
			"mail_from"		=> $rs[0]["mail_from"],
			"encoding"		=> $rs[0]["encoding"],
			"mail_count"		=> $rs[0]["mail_count"],
			"rec_count"		=> $rs[0]["rec_count"],
			"date_format"		=> $date_format_list[$rs[0]["date_format"]],
			"is_quick_steps"	=> $rs[0]["is_quick_steps"],
			"email_activation"	=> $rs[0]["email_activation"],
			"unsubscribe_confirm"	=> $rs[0]["unsubscribe_confirm"],
			"company_name"		=> $rs[0]["company_name"],
			"company_address"		=> $rs[0]["company_address"],
			"mail_max_hour"			=> $rs[0]["mail_max_hour"],
			"tech_email"			=> $rs[0]["tech_email"],
		);
	}
	//print $res["rec_count"];
	return $res;
}

function get_all_vars() {
	$array=array('_GET', '_POST', '_SESSION', '_COOKIE');
	foreach($array as $key => $var) {
		global $$var;
		foreach ($$var as $k => $v) {
			global $$k;
			$$k=$v;
		}
	}
}

function get_fields_from_array($name, $array, $id=0) {
	global $page;
	foreach ($array as $key=>$val) {
		$page->addrow($name, array(
			'id'		=> $key,
			'title'		=> $val,
			'active'	=> ($key==$id ? 1 : 0),
			'icon'		=> $key.'_'.($key==$id ? 'on' : 'off').'.gif'
		));
	}

}

function ip_to_int($ip) {
	if ($ip) {
		list($a, $b, $c, $d)=explode('.', $ip);
		return $a*256*256*256 + $b*256*256 + $c*256 + $d;
	}
	return 0;
}
?>