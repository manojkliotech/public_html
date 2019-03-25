<?
include_once "../lib/inc.php";
include("../common.php");
include("lib/function.sql.php");
include("lib/function.string.php");
include("lib/class.stat.php");
$url=(isset($id) ? to_str($id) : '');
$tbl=array(
	'n'	=> 'msg_newsletters',
	'a'	=> 'msg_autoresponder',
	'c'	=> 'msg_campaign_schedule',
	'sn'	=> 'stat_opened_newsletters',
);

function get_image() {
	header("Content-type: image/gif");
	$img=@imagecreate(1, 1);
	$background_color = imagecolorallocate($img, 255, 255, 255);
	imagegif($img);
	imagedestroy($img);
}

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

if ($url) {
	parse_str($url, $array);
	$stat=new stat();
	if ($array['type']=='n') {
		$sql="select newsletter_id as n_id, subscriber_id from {$tbl[$array['type']]}{$config["table_prefix"]} where id='{$array['msg_id']}'";
		list($data)=$db->select($sql);
		if (isset($data['n_id'])) {
			$stat->unique_by_key("NEWSLETTER[{$data['n_id']}].OPENED", $data['subscriber_id']);
			$stat->unique_by_key("NEWSLETTER[{$data['n_id']}].OPENED.ALL", $data['subscriber_id'], 0);
			//$stat->unique_by_key("NEWSLETTER[{$data['n_id']}].DELIVERED", $data['subscriber_id']);
			//$stat->unique_by_key("NEWSLETTER[{$data['n_id']}].DELIVERED.ALL", $data['subscriber_id'], 0);
			$stat->update("NEWSLETTER[{$data['n_id']}].OPENED.ALL", 0);
			$stat->update("NEWSLETTER[{$data['n_id']}].OPENED");
			//$stat->update("NEWSLETTER[{$data['n_id']}].DELIVERED.ALL", 0);
			//$stat->update("NEWSLETTER[{$data['n_id']}].DELIVERED");
			$log=date('d.m.Y [H:i:s]')."> Message opened\n";
			$sql="update {$tbl[$array['type']]}{$config["table_prefix"]} set (last_update, log) values ('".time()."', '{$log}')";
			$db->execute($sql);
		}
	} elseif (in_array($array['type'], array('a', 'c'))) {
        }
	if (isset($data['n_id'])) {
		$r=array(
			'date_created'		=> time(),
			'subscriber_id'		=> $data['subscriber_id'],
			'newsletter_id'		=> $data['n_id'],
			'ip'			=> $_SERVER['REMOTE_ADDR'],
			'browser'		=> get_browser2(),
			'os'			=> get_os(),
		);
		$fields='date_created, subscriber_id, newsletter_id, ip, browser, os';
		$sql=get_sql_insert($tbl['s'.$array['type']].$config["table_prefix"], $fields, $r);
		$db->execute($sql);
	}
}
$db->close();
get_image();
exit;
?>