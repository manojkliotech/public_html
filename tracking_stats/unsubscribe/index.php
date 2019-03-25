<?
include_once "../lib/inc.php";
include("../common.php");
include("lib/function.string.php");
include("lib/function.sql.php");
include("lib/class.stat.php");
include "lib/class.msg.php";
include("lib/class.mime.php");
include("lib/class.smtp.php");
$table="subscribers".$config["table_prefix"];
$table_n="newsletters".$config["table_prefix"];
$table_g="mailing_groups".$config["table_prefix"];
$table_sg="subscribers_group".$config["table_prefix"];
$table_confirm="msg_confirm_unsubscribe".$config["table_prefix"];
$table_stat="stat".$config["table_prefix"];
$table_log='stat_unsubscribes'.$config["table_prefix"];

$mail_format_list=array(
        0 => "{rs:MAIL_FORMAT_0}",
        1 => "{rs:MAIL_FORMAT_1}",
);


$message_list=array(
        1 => $db->get_val("pages".$config["table_prefix"], "description", "where name='unsubscribe_confirm_page_text'"),
        2 => $db->get_val("pages".$config["table_prefix"], "description", "where name='unsubscribe_successful'"),
);

function send_confirm_message($inf) {
        global $PATH, $HTTP_HOST, $table_g, $db, $config, $settings;
        $inf['group_id']=(is_array($inf['group_id']) ? $inf['group_id'] : array($inf['group_id']));
        $sql="select title from {$table_g} where id in (".implode(", ", $inf['group_id']).") order by position";
        $rs=$db->execute($sql);
        $groups="";
	while ($row=$db->get_row($rs)) $groups.=($groups ? "\n" : "")."- ".$row["title"];
        $message=$db->get_val("pages".$config["table_prefix"], "description", "where name='unsubscribe_confirm'");
        $f=array(
                "%name%",
                "%link%",
                "%url%",
                "%groups%",
        );
        $v=array(
                $inf['name'],
                'http://'.$HTTP_HOST.$PATH.'unsubscribe/?id='.$inf['subscriber_id'].'&active_key='.$inf['active_key'],
                'http://'.$HTTP_HOST.$PATH,
                $groups,
        );
        $array=array(
                "login"         => '',
                "password"      => '',
                "host"          => '',
                "port"          => '',
                "from_name"     => $settings["mail_from_name"],
                "from"          => $settings["mail_from"],
                "reply_to"      => "",
                "to_name"       => $inf["name"],
                "to"            => $inf["mail"],
                "subject"       => $db->get_val("pages".$config["table_prefix"], "subject", "where name='unsubscribe_confirm'"),
                "message"       => str_replace($f, $v, $message),
                "charset"       => $settings["encoding"],
                "content_type"  => "text/plain",
        );
        $smtp=new SMTP($array);
        return $smtp->send_mail();
}

function put_confirm($inf) {
        global $db, $table_confirm;
        if (!$inf['subscriber_id'] or !$inf['group_id']) return false;
        if ($res=$db->get_val($table_confirm, 'active_key', "where subscriber_id='{$inf[subscriber_id]}' and group_id='{$inf[group_id]}'")) return $res;
        $fields="date_created, subscriber_id, group_id, active_key";
        $r=array(
                'date_created'  => time(),
                'subscriber_id' => $inf['subscriber_id'],
                'group_id'      => $inf['group_id'],
                'active_key'    => gencookie(20),
        );
        $sql=get_sql_insert($table_confirm, $fields, $r);
        $db->execute($sql);
	set_stat($inf, array('UNSUBSCRIBES', 'UNSUBSCRIBES.PENDING'));
	$inf['active_key']=$r['active_key'];
	save_log($inf);
        return $r['active_key'];
}

function unsubscribe_group($inf) {
        global $db, $table_sg;
        $sql="delete from $table_sg where subscribers_id='{$inf[subscriber_id]}'";
        $db->execute($sql);
		$sql="insert into $table_sg (group_id, subscribers_id) values (1, {$inf[subscriber_id]})";
        $db->execute($sql);
}

function get_newsletters_id($group_id) {
	global $db, $table_n;
	$res=array();
	$sql="select id from $table_n where groups='{$group_id}' or groups like '{$group_id},%' or groups like '%,{$group_id},%' or groups like '%,{$group_id}' group by id";
	$rs=$db->execute($sql);
	while ($row=$db->get_row($rs)) $res[]=$row['id'];
	return $res;
}

function set_stat($inf, $code, $date=NULL) {
	global $db, $table_n, $stat;
	$code=(is_array($code) ? $code : (isset($code) ? array($code) : array()));
	foreach ($inf['n_id'] as $k => $id) {
		foreach ($code as $key => $val) {
			$stat->update("NEWSLETTER[{$id}].{$val}.ALL", 0);
			$stat->update("NEWSLETTER[{$id}].{$val}", $date);
		}
	}
}

function remove_pending($inf, $date) {
	global $db, $table_stat, $table_n, $stat;
	$date=mktime(0, 0, 0, date('m', $date), date('d', $date), date('Y', $date));
	foreach ($inf['n_id'] as $k => $id) {
		$sql="update $table_stat set count=count-1 where
			count>0 and
			(	(date_created='0' and code='NEWSLETTER[{$id}].UNSUBSCRIBES.PENDING.ALL') or
				(date_created='{$date}' and code='NEWSLETTER[{$id}].UNSUBSCRIBES.PENDING'))";
		$db->execute($sql);
	}
}

function save_log($inf, $status=0) {
	global $db, $table_log, $REMOTE_ADDR, $_SERVER;
	$r=array(
		'subscriber_id'	=> $inf['subscriber_id'],
		'newsletter_id'	=> 0,
		'group_id'	=> $inf['group_id'],
		'date_created'	=> time(),
		'ip'		=> $_SERVER['REMOTE_ADDR'],
		'browser'	=> get_browser2(),
		'os'		=> get_os(),
		'status'	=> $status,
		'active_key'	=> $inf['active_key'],
	);
	$fields='subscriber_id, newsletter_id, group_id, date_created, ip, browser, os, status, active_key';
	foreach ($inf['n_id'] as $key => $id) {
		$r['newsletter_id']=$id;
		$sql=get_sql_insert($table_log, $fields, $r);
		$db->execute($sql);
	}
}

function change_status_log($active_key='') {
	global $db, $table_log;
	$sql="update {$table_log} set status='1' where active_key='{$active_key}'";
	$db->execute($sql);
	return $res;
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

$message=new Message();
$error=new Message("error");
$stat=new stat();
if(isset($_GET['id'])){
	$id=$_GET['id'];
}
if(isset($_GET['group_id'])){
	$group_id=$_GET['group_id'];
}

if(isset($_GET['id'])){
	$info=array(
		'subscriber_id'	=> intval($id),
		'group_id'	=> (isset($group_id) ? intval($group_id) : 0),
	);
}
if (isset($_r)) $message->add($message_list[$_r]);
if (isset($active_key) and isset($id)) {
        $sql="select s.status, s.owner_id, c.* from $table s, $table_confirm c
        where   s.id='$id' and
                c.subscriber_id=s.id and
                c.active_key='$active_key'";
        list($rs)=$db->select($sql);
        if (isset($rs['active_key'])) {
		$info['group_id']=	$rs['group_id'];
		$info['n_id']=		get_newsletters_id($info['group_id']);
		$info['active_key']=	$active_key;
                $info['owner_id']=	$rs['owner_id'];
                $settings=load_settings($info['owner_id']);
		remove_pending($info, $rs['date_created']);
		set_stat($info, array('UNSUBSCRIBES.CONFIRMED'), $rs['date_created']);
		change_status_log($info['active_key']);
                unsubscribe_group($info);
                $link->add("_r", 2);
        }
        $sql="delete from $table_confirm where subscriber_id='$id' and active_key='$active_key'";
        $db->execute($sql);
        header("Location: ".$link->get());
        $db->close();
        exit;
} elseif (isset($id)) {
	
        /*
		$sql="select s.* from $table s, $table_sg sg
	where
		s.id='{$info[subscriber_id]}' and 
		sg.subscribers_id=s.id and
		sg.group_id='{$info[group_id]}'";
		*/
		
		$sql="select s.* from $table s, $table_sg sg
	where
		s.id='{$info[subscriber_id]}' and 
		sg.subscribers_id=s.id";
		
        list($rs)=$db->select($sql);
        if (isset($rs["id"])) {
                $info['owner_id']=	$rs['owner_id'];
                $info['name']=		$rs['name'];
                $info['mail']=		$rs['mail'];
		$info['n_id']=		get_newsletters_id($info['group_id']);
                $settings=load_settings($info['owner_id']);
                if ($settings["unsubscribe_confirm"]) {
                        if ($info['active_key']=put_confirm($info)) {                       	set_stat($info, array('UNSUBSCRIBES', 'UNSUBSCRIBES.PENDING'));
                        		unsubscribe_group($info);
                        		save_log($info, 1);
                                /*send_confirm_message($info);*/
                                $link->add("_r", 1);
                        } else $link->add("_r", 2);
                } else {
			set_stat(array('UNSUBSCRIBES', 'UNSUBSCRIBES.CONFIRMED'));
			save_log($info, 1);
                        unsubscribe_group($info);
                        $link->add("_r", 2);
                }
                header("Location: ".$link->get());
                $db->close();
                exit;
        } else $error->add("{rs:ERROR_USER_NOT_FOUND}");
} elseif (!isset($_r)) $error->add("{rs:ERROR_USER_NOT_FOUND}");

/*$page = new Template("templates/unsubscribe/index.tpl");
$page->dictionary("common/dict.ru");
$page->assign(array(
        "PATH"                  => $PATH,
        "header_title"          => "{rs:TITLE_UNSUBSCRIBE}",
        "ERROR"                 => ($error->count ? $error->get() : ""),
        "MESSAGE"               => ($message->count ? $message->get() : ""),
        "LINK"                  => $link->get(),
));*/
//print str_replace("%form%", $page->evaluate(), $db->get_val("pages".$config["table_prefix"], "description", "where name='subscribe_page'"));
$db->close();
?>
<html>
<head>
<style>
.alert-success {
color: #468847;
background-color: #dff0d8;
border-color: #d6e9c6;
}
.alert {
padding: 15px;
margin-bottom: 20px;
border: 1px solid transparent;
border-radius: 4px;
}
*, *:before, *:after {
-webkit-box-sizing: border-box;
-moz-box-sizing: border-box;
box-sizing: border-box;
}
button.close {
padding: 0;
cursor: pointer;
background: transparent;
border: 0;
-webkit-appearance: none;
}
.close {
float: right;
font-size: 21px;
font-weight: bold;
line-height: 1;
color: #000;
text-shadow: 0 1px 0 #fff;
opacity: .2;
filter: alpha(opacity=20);
}

body {
font-family: 'open_sansregular';
font-size:16px;
}
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
<script>
$(document).ready(function(){
$(".load_img").hide();

$("#closebtn").click(function(){
$('.msg_panel').hide();
});

});

</script>
</head>

<body>
<div class="msg_panel message">
<div class="alert alert-success">
<button class="close" id="closebtn" type="button" data-dismiss="alert" aria-hidden="true">x</button>
Unsubscription successfull ..
</div>
</div>
</body>
</html>