<?
include_once "../lib/inc.php";
include("../common.php");
include("lib/function.sql.php");
include("lib/function.string.php");
include("lib/class.stat.php");
//echo "text";exit;
function get_browser2() {
	global $_SERVER;
	$u_agent=$_SERVER['HTTP_USER_AGENT'];
	if (eregi("(opera) ([0-9]{1,2}.[0-9]{1,3}){0,1}",$u_agent,$st_regs) || eregi("(opera/)([0-9]{1,2}.[0-9]{1,3}){0,1}",$u_agent,$st_regs)) {
		$st_brows = "Opera ";
		$st_ver = $st_regs[2];
	} elseif(eregi("(konqueror)/([0-9]{1,2}.[0-9]{1,3})",$u_agent,$st_regs)) {
		$st_brows = "KONQ";
		$st_ver = $st_regs[2];
		$st_sys = "Linux ";
	} elseif(eregi("(lynx)/([0-9]{1,2}.[0-9]{1,2}.[0-9]{1,2})",$u_agent,$st_regs) ) {
		$st_brows = "Lynx ";
		$st_ver = $st_regs[2];
	} elseif(eregi("(links) \(([0-9]{1,2}.[0-9]{1,3})",$u_agent,$st_regs)) {
		$st_brows = "Links ";
		$st_ver = $st_regs[2];
	} elseif(eregi("(omniweb/)([0-9]{1,2}.[0-9]{1,3})",$u_agent,$st_regs)) {
		$st_brows = "OmniWeb ";
		$st_ver = $st_regs[2];
	} elseif(eregi("(webtv/)([0-9]{1,2}.[0-9]{1,3})",$u_agent,$st_regs)) {
		$st_brows = "WebTV ";
		$st_ver = $st_regs[2];
	} elseif(eregi("(msie) ([0-9]{1,2}.[0-9]{1,3})",$u_agent,$st_regs)) {
		$st_brows = "Internet Explorer ";
		$st_ver = $st_regs[2];
	} elseif(eregi("(Firefox/)([0-9]{1,2}.[0-9]{1,2}.[0-9]{1,2})",$u_agent,$st_regs)) {
		$st_brows = "Mozzila Firefox ";
		$st_ver = $st_regs[2];
	} elseif(eregi("(Chrome/)([0-9]{1,2}.[0-9]{1,2})",$u_agent,$st_regs)) {
		$st_brows = "Chrome ";
		$st_ver = $st_regs[2];
	} elseif(eregi("(netscape6)/(6.[0-9]{1,3})",$u_agent,$st_regs)) {
		$st_brows = "Netscape Navigator ";
		$st_ver = $st_regs[2];
	} elseif(eregi("(rv:)([0-9]{1,2}.[0-9]{1,2})(\))( ){0,1}(Gecko/)",$u_agent,$st_regs)) {
		$st_brows = "Mozzila ";
		$st_ver = $st_regs[2];
	} elseif(eregi("(mozilla)/([0-9]{1,2}.[0-9]{1,3})",$u_agent,$st_regs)) {
		$st_brows = "Netscape Navigator ";
		$st_ver = $st_regs[2];
	} elseif(eregi("w3m",$u_agent)) {$st_brows = "w3m";
	} elseif(eregi("(scooter)-([0-9]{1,2}.[0-9]{1,3})",$u_agent,$st_regs)) {
		$st_brows = "Scooter";
		$st_ver = $st_regs[2];
	} elseif(eregi("(w3c_validator)/([0-9]{1,2}.[0-9]{1,3})",$u_agent,$st_regs)) {
		$st_brows = "W3C";
		$st_ver = $st_regs[2];
	} elseif(eregi("(googlebot)/([0-9]{1,2}.[0-9]{1,3})",$u_agent,$st_regs)) {
		$st_brows = "Google";
		$st_ver = $st_regs[2];
	} else {
		$st_brows = $i18n['welcome']['unknown'];
		$st_ver = "";
	}
	return $st_brows.$st_ver;
}

function get_os() {
	global $_SERVER;
	$u_agent = $_SERVER['HTTP_USER_AGENT'];
	if (eregi("linux",$u_agent)) $st_sys = "Linux";
	elseif(eregi("Win 9x 4.90",$u_agent)) $st_sys = "MS Windows Me";
	elseif(eregi("win32",$u_agent)) $st_sys = "Win 32";
	elseif(eregi("windows 2000",$u_agent)) $st_sys = "MS Windows 2000";
	elseif((eregi("(win)([0-9]{2})",$u_agent,$st_regs)) || (eregi("(windows) ([0-9]{2})",$u_agent,$st_regs))) $st_sys = "MS Windows ".$st_regs[2];
	elseif(eregi("(windows nt)( ){0,1}(5.0)",$u_agent,$st_regs)) $st_sys = "MS Windows 2000";
	elseif(eregi("(windows nt)( ){0,1}(5.1)",$u_agent,$st_regs)) $st_sys = "MS Windows XP";
	elseif(eregi("(winnt)([0-9]{1,2}.[0-9]{1,2}){0,1}",$u_agent,$st_regs)) $st_sys = "MS Windows NT".$st_regs[2];
	elseif(eregi("(windows nt)( ){0,1}([0-9]{1,2}.[0-9]{1,2}){0,1}",$u_agent,$st_regs)) $st_sys = "MS Windows NT".$st_regs[3];
	elseif(eregi("PPC",$u_agent) || eregi("Mac_PowerPC",$u_agent)) $st_sys = "Mac PPC";
	elseif(eregi("mac",$u_agent)) $st_sys = "Mac";
	elseif(eregi("(sunos) ([0-9]{1,2}.[0-9]{1,2}){0,1}",$u_agent,$st_regs)) {
		$st_sys = "SunOS";
		$st_sysver = $st_regs[2];
	} elseif(eregi("(beos) r([0-9]{1,2}.[0-9]{1,2}){0,1}",$u_agent,$st_regs)) {
		$st_sys = "BeOS";
		$st_sysver = $st_regs[2];
	} elseif(eregi("freebsd",$u_agent)) $st_sys = "FreeBSD";
	elseif(eregi("openbsd",$u_agent)) $st_sys = "OpenBSD";
	elseif(eregi("irix",$u_agent)) $st_sys = "IRIX";
	elseif(eregi("os/2",$u_agent)) $st_sys = "OS/2";
	elseif(eregi("plan9",$u_agent)) $st_sys = "Plan9";
	elseif(eregi("unix",$u_agent) || eregi("hp-ux",$u_agent) ) $st_sys = "Unix";
	elseif(eregi("osf",$u_agent)) $st_sys = "OSF";
	elseif(eregi("X11",$u_agent) && !isset($st_sys)) $st_sys = "Unix";
	else $st_sys=$i18n['welcome']['unknown'];
	return $st_sys;
}

//$url=to_str(preg_replace("/^.*\/url\/([^\/]*)\/?$/", "$1", $_SERVER['REQUEST_URI']));
$url=(isset($l) ? to_str($l) : '');
if (!$url) {
	$db->close();
	header('Location: '.$PATH);
	exit;
}
preg_match("/t_id=([^;]*);s_id=([^;]*);(.*)/", $url, $array);
$t_id=(isset($array[1]) ? $array[1] : 0);
list($type, $t_id)=explode("-", $t_id);
$s_id=intval(isset($array[2]) ? $array[2] : 0);
$url=(isset($array[3]) ? $array[3] : 'http://'.$_SERVER['HTTP_HOST'].$url);
if ($url[strlen($url)-1]=='/') $url=substr($url, 0, strlen($url)-1);
$stat=new stat();
if ($type=='n') {
        //$stat->add("NEWSLETTER[{$t_id}].LINK[".$url."]");
        $n_id=$t_id;
} else {
        $sql="select newsletter_id, owner_id from ".($type=="a" ? "autoresponder" : "campaign_schedule").$config["table_prefix"]." where id='$t_id'";
        $rs=$db->select($sql);
        if (isset($rs[0][0])) {
                $n_id=$rs[0]["newsletter_id"];
                //$stat->add("NEWSLETTER[".$rs[0]["newsletter_id"]."].LINK[".$url."]");
                //$stat->add("USER[".$rs[0]["owner_id"]."].NEWSLETTER[".$rs[0]["newsletter_id"]."].LINK[".$url."]");
                //$stat->add("USER[".$rs[0]["owner_id"]."].LINK[".$url."]");
        } else $n_id=0;
}
if ($n_id) {
	$stat->update("NEWSLETTER[{$n_id}].CLICK.ALL", 0);
	$stat->update("NEWSLETTER[{$n_id}].CLICK");
	$stat->unique_by_key("NEWSLETTER[{$n_id}].CLICK.ALL", $s_id, 0);
	$stat->unique_by_key("NEWSLETTER[{$n_id}].CLICK", $s_id);
	$stat->update("NEWSLETTER[{$n_id}].LINK[{$url}].ALL", 0);
}
//$stat->add("LINK[".$url."]");
$stat->close();
$data=array(
        'newsletter_id' => $n_id,
        'subscriber_id' => $s_id,
        'date_created'  => time(),
        'url'           => $url,
        'ip'            => $_SERVER['REMOTE_ADDR'],
	'browser'	=> get_browser2(),
	'os'		=> get_os(),
);
$fields='newsletter_id, subscriber_id, date_created, url, ip, browser, os';
$sql=get_sql_insert('stat_links'.$config["table_prefix"], $fields, $data);
$db->execute($sql);
$db->close();
header("Location: $url");
exit;
?>