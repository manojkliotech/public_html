<?
if (isset($function_string_already_included)) return;
$function_string_already_included = 1;

function check_string($str, $strip=false) {
        if (!get_magic_quotes_gpc()) $str=addslashes($str);
        if ($strip) $str = strip_tags($str);
        return $str;
}

function check_array($array, $ignor=NULL) {
        $ignor=(is_array($ignor) ? $ignor : (isset($ignor) ? explode(", ", $ignor) : array()));
        if (is_array($array)) {
                reset($array);
                $key=key($array);
                while (isset($key)) {
                        $array[$key]=(is_array($array[$key]) ? check_array($array[$key], $ignor) : check_string($array[$key], !in_array($key, $ignor)));
                        next($array);
                        $key=key($array);
                }
        } else $array=check_string($array, true);
        return $array;
}

function html_string($str, $strip=false, $ignor=NULL) {
        $ignor=(is_array($ignor) ? $ignor : (isset($ignor) ? explode(", ", $ignor) : array()));
        if (is_array($str)) {
                reset($str);
                $key=key($str);
                while (isset($key)) {
                        if (!in_array($key, $ignor)) {
                                $str[$key]=(is_array($str[$key]) ? html_string($str[$key], $strip, $ignor) : htmlspecialchars(($strip ? stripslashes($str[$key]) : $str[$key]), ENT_QUOTES));
                        }
                        next($str);
                        $key=key($str);
                }
        } else $str=htmlspecialchars(($strip ? stripslashes($str) : $str), ENT_QUOTES);
        return $str;
}

function shortStr($str, $len=20, $pre='...') {
	if ($len) {
		if (strlen($str)>$len) $str=substr($str, 0, $len).$pre;
	}
	return $str;
}

function shorter($str, $characters, $words, $sentences) {
        if ($characters) {
                if (strlen($str)<=$characters) return $str;
                print $str = substr($str,0,$characters);
                while (substr($str,strlen($str)-1,1)!=" " && strlen($str))
                        print $str = substr($str,0,strlen($str)-1);
                return $str."...";
        } else if ($words) {
                $w = explode(" ", $str);
                if (sizeof($w)<=$words) 
                        return $str;
                return join(" ", array_slice($w,0,$words))."...";
        } else if ($sentences) {
                $s = preg_split("/[\.|\!|\?]+/", $str);
                if (sizeof($s)<=$sentences)
                        return $str;
                return join(". ", array_slice($s, 0, $sentences))."...";
        } else return $str;
}

function check_email($email) {
        return preg_match('/^[\w_\-\.]+\@[\w_\-]+\.[\w_\-\.]+$/',$email);
}

function check_url($url, $del_param=0) {
        $url=ereg_replace("[[:alpha:]]+://", "", strip_tags($url));
        if ($del_param) $url=ereg_replace("([^\?]*)\?.*", "\\1", $url);
        return ($url ? "http://$url" : "");
}

function ru_to_en($str) {
        return str_replace(
                array(" ","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�","�"),
                array(" ","a","b","v","g","d","e","e","zh","z","i","j","k","l","m","n","o","p","r","s","t","u","f","h","ts","ch","sh","sch","e","yu","ya","","","y","i","yi","A","B","V","G","D","E","E","ZH","Z","I","J","K","L","M","N","O","P","R","S","T","U","F","H","TS","CH","SH","SCH","E","YU","YA","","","Y","I","YI"),
                $str
        );      
}

function gen_password($len=7) {
        initrand();
        $ch = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        $res = "";
        for ($i=0; $i<$len; $i++)
                $res .= $ch[rand()%strlen($ch)];
        return $res;
}

function convert_links($text="") {
        if (!$text) return "";
        $text=str_replace("\r", "", $text);
        $array=explode("\n", $text);
        $res=array();
        for ($i=0; $i<count($array); $i++) {
                preg_match("/\[([^\]]*)\]/", $array[$i], $r);
                $url=($r[1] ? trim($r[1]) : "");
                preg_match("/^([^\[]*)/", $array[$i], $r);
                $title=($r[1] ? trim($r[1]) : $url);
                if ($url) $res[]="<a href=\"$url\" target=\"_blank\">$title</a>";
                elseif (trim($array[$i])) $res[]=trim($array[$i]);
        }
        return implode($res, "; ");
}

function int_to($n=0, $lang=NULL, $max=0) {
        $lang=(!isset($lang) ? "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz" : $lang);
        $c=strlen($lang);
        if (!$c) return $n;
        $res="";
        while ($n) {
                $res=$lang[$n%$c].$res;
                $n=floor($n/$c);
        }
        $res=str_pad($res, $max, "0", STR_PAD_LEFT);  
        return $res;
}

function to_int($n, $lang=NULL) {
        $lang=(!isset($lang) ? "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz" : $lang);
        $c=strlen($lang);
        if (!$c) return $n;
        $res=0;
        $b=strlen($n);
        for ($i=0; $i<$b; $i++) $res+=strpos($lang, $n[$i])*pow($c, $b-1-$i);
        return $res;
}

function str_to($st, $lang=NULL) {
        $res="";
        for ($i=0; $i<strlen($st); $i++) $res.=int_to(ord($st[$i]), $lang, 2);
        return $res;
}

function to_str($code, $lang=NULL, $max=2) {
        $res="";
        for ($i=0; $i<strlen($code)/$max; $i++) {
                $st=substr($code, $i*$max, $max);
                $res.=chr(to_int($st, $lang));
        }
        return $res;
}

function replace_link($array, $task_id=NULL, $s_id=NULL) {
        global $HTTP_HOST, $PATH;
        $PATH='/';
        $link="http://".$HTTP_HOST.str_replace("/admin", "", $PATH)."url/?l=".str_to("t_id=$task_id;s_id=$s_id;".$array[(count($array)==2 ? 1 : 2)]);
        return (count($array)==2 ? $link : $array[1].$link.$array[3]);
}

function change_links($st, $type=1) {
        $mask=($type ? "/([a-z]{3,5}:\/\/[^\s\"\'<>]*)/i" : "/(<a[^>]+href=[\'\"]{1})([^\'\"]*)([\'\"]{1}[^>]*>)/i");
        $st=preg_replace_callback($mask, "replace_link", $st);
        return $st;
}

function change_links2($st, $type=1, $task_id=0, $s_id=0) {
        $mask=($type ? "/([a-z]{3,5}:\/\/[^\s\"\'<>]*)/i" : "/(<a[^>]+href=[\'\"]{1})([^\'\"]*)([\'\"]{1}[^>]*>)/i");
	preg_match_all($mask, $st, $array);
        $st=preg_replace_callback($mask, create_function('$array', 'return replace_link($array, "'.$task_id.'", '.intval($s_id).');'), $st);
        return $st;
}

function validation($val, $type='') {
        if ($type=='c') {
                return ((!preg_match("/[\d]/", $val) and preg_match("/^[\w]*$/", $val)) ? $val : "");
        } elseif ($type=='n') {
                return (preg_match("/^[\d]*$/", $val) ? $val : "");
        } elseif ($type=='nc') {
                return (preg_match("/^[\w]*$/", $val) ? $val : "");
        } elseif ($type=='mail') {
                return (check_email($val) ? $val : "");
        } elseif ($type=='url') {
                return check_url($val);
        } else return $val;
}

function ChangeImageURL($st) {
	global $_SERVER, $PATH;
        $PATH='/';
	//$PATH='/myzend/';
	$r=array(
		'/(<img [^>]*src=")[\.\/]+(files\/[^"]+)("[^>]*>)/i',
		'/(<a [^>]*href=")[\.\/]+([^"]+)("[^>]*>)/i',
	);
	return preg_replace($r, '$1http://'.$_SERVER['HTTP_HOST'].str_replace("/admin", "", $PATH).'$2$3', $st);
}
?>