<?
if (isset($INCLUDED["class.msg"])) return;
else $INCLUDED["class.msg"]=1;

class msg {
        var $log;
        var $table="";
        var $table_n="";
        var $limit=10;

        function add_log($text, $is_date=true, $is_enter=true, $is_print=false) {
                $text=($is_date ? date("d.m.y [H:i:s]> ") : "").$text.($is_enter ? "\n" : "");
                $this->log.=$text;
                if ($is_print) print nl2br($text);
                return $text;
        }

        function msg($table=NULL) {
		global $config;
                $this->log="";
		$this->table_n="newsletters".$config["table_prefix"];
                if (isset($table)) {
                        $this->table=$table;
                        return true;
                }
                else false;
        }

        function add($r, $is_print_log=false) {
                global $db;
                $data=array(
                        "date_created"  => time(),
                        "date_start"    => (isset($r["date_start"]) ? $r["date_start"] : time()),
                        "last_update"   => time(),
                        "subscriber_id" => (isset($r["subscriber_id"]) ? $r["subscriber_id"] : 0),
			"group_id"	=> (isset($r["group_id"]) ? $r["group_id"] : 0),
                        "task_id"       => (isset($r["task_id"]) ? $r["task_id"] : 0),
                        "status"        => 0,
                );
		$fields="date_created, date_start, last_update, subscriber_id, task_id, status, log".($data["group_id"] ? ", group_id" : "");
                if ($data["subscriber_id"] && $data["task_id"]) {
                        $data["log"]=$this->add_log("Add to list", true, true, $is_print_log);
                        $sql=get_sql_insert($this->table, $fields, $data);
                        $db->execute($sql);
                        return true;
                } else return false;
        }

        function get_next_task($table="campaign_schedule", $r) {
                global $db, $config;
		$sql="select * from $table where id='".$r["id"]."'";
                $rs=$db->select($sql);
                $day=(isset($rs[0]["id"]) ? $rs[0]["day"] : 0);
                $position=(isset($rs[0]["id"]) ? $rs[0]["position"] : -1);
                $subscriber_id=(isset($r["subscriber_id"]) ? $r["subscriber_id"] : 0);
		$group_id=(isset($r["group_id"]) ? $r["group_id"] : 0);
                $sql="select
                        cs.id,
                        cs.day
                from
                        campaign_schedule".$config["table_prefix"]." cs,
                        mailing_groups".$config["table_prefix"]." g,
                        subscribers".$config["table_prefix"]." s,
                        subscribers_group".$config["table_prefix"]." sg,
                        newsletters".$config["table_prefix"]." n
                where
                        cs.newsletter_id=n.id and
                        ((cs.day=$day and cs.position>$position) or cs.day>$day) and
                        cs.enabled=1 and
			s.id='$subscriber_id' and
			s.status=n.recipients and
			s.id=sg.subscribers_id and
			sg.group_id=g.id and
			g.id='$group_id' and
			g.enabled=1 and
			(	n.groups='$group_id' or
				n.groups like '$group_id,%' or
				n.groups like '%,$group_id,%' or
				n.groups like '%,$group_id'
			)
                order by cs.day, cs.position
                limit 1";
                $rs=$db->select($sql);
                if (isset($rs[0]["id"])) {
                        $day=$rs[0]["day"]-$day;
                        $res=array(
                                "date_start"    => ($day>0 ? mktime(0, 0, 0, date("m"), date("d")+$day, date("Y")) : time()),
                                "subscriber_id"	=> $subscriber_id,
				"group_id"	=> $group_id,
                                "task_id"       => $rs[0]["id"],
                        );
                } else $res=array();
                return $res;
        }

        function send_message($data) {
                global $PATH, $HTTP_HOST, $config, $db, $newsletter;
                $subscriber=get_rec("subscribers".$config["table_prefix"], "where id='".$data["subscriber_id"]."'");
                $sql="select n.*".($data["type"]=="autoresponder" ? ", t.is_newsletter" : "")." from ".$this->table_n." n, ".substr($this->table, 4)." t where t.id='".$data["task_id"]."' and t.newsletter_id=n.id";
                $rs=$db->select($sql);
                $newsletter=$rs[0];
		$smtp=get_account("smtp_accounts", $newsletter["smtp"]);
		$pop3=get_account("pop3_accounts", $newsletter["pop3"]);
		$pop3['mail']=(isset($pop3['login']) ? preg_replace("/@.*/", '', $pop3['login']).'@'.$pop3['host'] : '');
		$settings=load_settings($newsletter["owner_id"]);
                $message=($subscriber["receive_format"] ? $newsletter["text_content"] : $newsletter["html_header"].$newsletter["html_css"].$newsletter["html_content"]);
                $groups=explode(",", $newsletter["groups"]);
                $f=array(
                        "%subject%",
                        "%name%",
                        "%email%",
                        "%format%",
                        "%encoding%",
                        "%unsubscribe%",
                        "%properties%",
                        "%date%",
                );
                $v=array(
                        $newsletter["subject"],
                        $subscriber["name"],
                        $subscriber["mail"],
                        ($subscriber["receive_format"] ? "Plain text" : "HTML"),
                        $subscriber["encoding"],
			"http://".$HTTP_HOST.str_replace("admin/", "", $BASE_PATH)."unsubscribe/?id=".$subscriber["id"]."&group_id=".(isset($data["group_id"]) ? $data["group_id"] : $groups[0]),
                        "",
                        date($settings["date_format"]),
                );
		$sql="select cf.id, if(cf.enabled=1, scf.value, '') as value from custom_fields".$config["table_prefix"]." cf
		LEFT JOIN scf".$config["table_prefix"]." as scf ON
			scf.custom_field_id=cf.id and
			scf.subscriber_id='".$data["subscriber_id"]."'
		where cf.owner_id='".$subscriber["owner_id"]."'";
		$rs=$db->select($sql);
		for ($i=0; $i<count($rs); $i++) {
			$f[]="%custom_field_".$rs[$i]["id"]."%";
			$v[]=$rs[$i]["value"];
		}
                $message=change_links2(str_replace($f, $v, $message), $subscriber["receive_format"], $data["type"][0]."-".$data["task_id"], $subscriber['id']);
		$message=ChangeImageURL($message);
                $array=array(
                        "login"         => $smtp["login"],
                        "password"      => $smtp["password"],
                        "host"          => $smtp["host"],
                        "port"          => $smtp["port"],
                        "from_name"     => $newsletter["from_name"],
			"report"        => ($pop3['mail'] ? $pop3['mail'] : ''),
                        "from"          => ($newsletter["from_mail"] ? $newsletter["from_mail"] : $settings["mail_from"]),
			"reply_to"      => $newsletter["reply_mail"],
                        "to_name"       => $subscriber["name"],
                        "to"            => $subscriber["mail"],
                        "subject"       => $newsletter["subject"],
                        "message"       => $message,
                        "charset"       => $subscriber["encoding"],
                        "content_type"  => ($subscriber["receive_format"] ? "text/plain" : "text/html"),
                );
		$SMTP=new SMTP($array);
		$newsletter['files']=($newsletter['files'] ? explode('|', $newsletter['files']) : array());
		for ($i=0; $i<count($newsletter['files']); $i++) {
			$SMTP->addAttach((strpos($PATH, 'admin/') ? '../' : '').'files/'.$newsletter['files'][$i]);
		}
		//$res=(($settings["mail_mode"] ? $SMTP->send() : $SMTP->send_mail()) ? $SMTP->message_id : "");
		$res=(($smtp["login"] ? $SMTP->send() : $SMTP->send_mail()) ? $SMTP->message_id : "");
		if ($res) {
			$stat=new stat();
			$stat->update("NEWSLETTER[".$newsletter["id"]."].SEND");
			$stat->update("NEWSLETTER[".$newsletter["id"]."].SEND.ALL", 0);
			$stat->update("NEWSLETTER[".$newsletter["id"]."].".($data["type"]=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".SEND.ALL", 0);
			$stat->update("NEWSLETTER[".$newsletter["id"]."].".($data["type"]=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".SEND");
			$stat->update(($data["type"]=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".SEND.ALL", 0);
			$stat->update(($data["type"]=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".SEND");
			if ($newsletter["is_newsletter"]) {
				$stat->update("NEWSLETTER[".$newsletter["id"]."].NEWSLETTER.SEND.ALL", 0);
				$stat->update("NEWSLETTER[".$newsletter["id"]."].NEWSLETTER.SEND");
				$stat->update("NEWSLETTER.SEND.ALL", 0);
				$stat->update("NEWSLETTER.SEND");
				$stat->update("USER[".$newsletter["owner_id"]."].NEWSLETTER[".$newsletter["id"]."].NEWSLETTER.SEND.ALL", 0);
				$stat->update("USER[".$newsletter["owner_id"]."].NEWSLETTER[".$newsletter["id"]."].NEWSLETTER.SEND");
				$stat->update("USER[".$newsletter["owner_id"]."].NEWSLETTER.SEND.ALL", 0);
				$stat->update("USER[".$newsletter["owner_id"]."].NEWSLETTER.SEND", 0);
			}
			$stat->update("USER[".$newsletter["owner_id"]."].NEWSLETTER[".$newsletter["id"]."].SEND");
			$stat->update("USER[".$newsletter["owner_id"]."].NEWSLETTER[".$newsletter["id"]."].SEND.ALL", 0);
			$stat->update("USER[".$newsletter["owner_id"]."].NEWSLETTER[".$newsletter["id"]."].".($data["type"]=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".SEND.ALL", 0);
			$stat->update("USER[".$newsletter["owner_id"]."].NEWSLETTER[".$newsletter["id"]."].".($data["type"]=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".SEND");
			$stat->update("USER[".$newsletter["owner_id"]."].".($data["type"]=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".SEND.ALL", 0);
			$stat->update("USER[".$newsletter["owner_id"]."].".($data["type"]=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".SEND");
			$sql="update {$this->table_n} set last_sent='".time()."' where id='".$newsletter['id']."'";
			$db->execute($sql);
		}
		return $res;
        }

        function status_0($type="autoresponder") {
                global $db, $config, $newsletter;
                $sql="select * from ".$this->table." where status=0 and date_start<=".time()." order by last_update limit ".$this->limit;
                $rs=$db->select($sql);
                for ($i=0; $i<count($rs); $i++) {
                        $fields="last_update, log, status, message_id";
                        $data=$rs[$i];
                        $data["type"]=$type;
                        $res=$this->send_message($data);
                        $data["last_update"]=time();
                        $data["log"].=$this->add_log(($res ? "Message sent" : "Error: message not sent"), true, true);
                        $data["status"]=($res ? 1 : 0);
			$data["message_id"]=$res;
                        $sql=get_sql_update($this->table, $fields, $data, "where id='".$data["id"]."'");
                        $db->execute($sql);
                        if ($res) {
                                if ($type=="campaign_schedule") {
                                        $this->add_log("Search new task: ", true, false);
                                        $data=array(
                                                "id"            => $rs[$i]["task_id"],
                                                "group_id"	=> $rs[$i]["group_id"],
						"subscriber_id"	=> $rs[$i]["subscriber_id"],
                                        );
                                        $data=$this->get_next_task("campaign_schedule".$config["table_prefix"], $data);
                                        if ($data!=array()) {
                                                $this->add_log("ok", false, true);
                                                $this->add($data, false);
                                        } else $this->add_log("Not found", false, true);
                                }
/*
                                $stat=new stat();
				$stat->update("NEWSLETTER[$newsletter_id].SEND");
				$stat->update("NEWSLETTER[$newsletter_id].SEND.ALL", 0);
				$stat->update("NEWSLETTER[$newsletter_id].".($type=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".SEND.ALL", 0);
				$stat->update("NEWSLETTER[$newsletter_id].".($type=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".SEND");
                                $stat->update(($type=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".SEND.ALL", 0);
                                $stat->update(($type=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".SEND");
*/
                        }
                }
        }

	function get_message_info($st) {
		$res=array();
		$res["X-Failed-Recipients"]=ereg("\r\nX-Failed-Recipients:", $st);
		$res["Auto-Submitted"]=(ereg("\r\nAuto-Submitted:([^\r]*)\r\n", $st, $array) ? $array[1] : "");
		$res["Delivery-date"]=(ereg("\r\nDelivery-date:([^\r]*)\r\n", $st, $array) ? $array[1] : "");
		preg_match("/The following address\(es\) failed:\r\n\r\n([^\r]*)\r\n([^\r]*)\r\n/m", $st, $array);
		$res["mail"]=(isset($array[1]) ? trim($array[1]) : "");
		$res["error"]=(isset($array[2]) ? trim($array[2]) : "");
		$res["message_id"]='';
		if ($pos=strrpos($st, 'Received: from')) {
			$s=substr($st, $pos);
			preg_match("/id ([^\r]*)/", $s, $array);
			if (isset($array[1])) $res["message_id"]=trim($array[1]);
		}
		return $res;
	}

        function check_delivery($POP3) {
                global $config, $db, $settings;
                $pop=new POP3();
		if ($pop->connect($POP3["login"], $POP3["password"], $POP3["host"], $POP3["port"])) {
			$messages=$pop->check_mail();
			$pop->close();
		}
                $stat=new stat("stat");
		$type_list=array("autoresponder", "campaign_schedule", "newsletters");
		reset($type_list);
		$key=0;
		while ($type=$type_list[$key]) {
			$key++;
			$this->table="msg_".$type.$config["table_prefix"];
			for ($i=0; $i<count($messages); $i++) {
				$array[$i]=$this->get_message_info($messages[$i]);
				if (isset($array[$i]["X-Failed-Recipients"]) or $array[$i]["Auto-Submitted"]=="auto-generated") {
					$t=($array[$i]["Delivery-date"] ? strtotime($array[$i]["Delivery-date"]) : time());
					if ($type=='newsletters') {
						$sql="select m.*
						from
							".$this->table." m,
						where
							m.status=1 and
							m.message_id='".$array[$i]["message_id"]."'";
					} else {
						$sql="select m.*, t.newsletter_id, t.owner_id".($type=="autoresponder" ? ", t.is_newsletter" : "")."
						from
							".$this->table." m,
							".substr($this->table, 4)." t
						where
							m.status=1 and
							m.task_id=t.id and
							m.message_id='".$array[$i]["message_id"]."'";
					}
					$rs=$db->select($sql);
					if (isset($rs[0][0])) {
						$data=$rs[0];
						$data["log"].=$this->add_log("Mail delivery failed: ".$array[$i]["error"], true, true);
						$data["status"]=-1;
						$fields="log, status";
						$sql=get_sql_update($this->table, $fields, $data, "where id='".$data["id"]."'");
						$db->execute($sql);
						if ($type!='newsletters') {
							$stat->update("NEWSLETTER[".$data["newsletter_id"]."].".($type=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".UNDELIVERED.ALL", 0);
	        	                        	$stat->update("NEWSLETTER[".$data["newsletter_id"]."].".($type=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".UNDELIVERED", $t);
						}
		                                $stat->update("NEWSLETTER[".$data["newsletter_id"]."].UNDELIVERED.ALL", 0);
						$stat->update("NEWSLETTER[".$data["newsletter_id"]."].UNDELIVERED", $t);
						if ($type!='newsletters') {
							$stat->update("USER[".$data["owner_id"]."].NEWSLETTER[".$data["newsletter_id"]."].".($type=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".UNDELIVERED.ALL", 0);
							$stat->update("USER[".$data["owner_id"]."].NEWSLETTER[".$data["newsletter_id"]."].".($type=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".UNDELIVERED", $t);
							$stat->update("USER[".$data["owner_id"]."].NEWSLETTER[".$data["newsletter_id"]."].UNDELIVERED.ALL", 0);
							$stat->update("USER[".$data["owner_id"]."].NEWSLETTER[".$data["newsletter_id"]."].UNDELIVERED", $t);
							$stat->update("USER[".$data["owner_id"]."].".($type=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".UNDELIVERED.ALL", 0);
							$stat->update("USER[".$data["owner_id"]."].".($type=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".UNDELIVERED", $t);
						}
						if ($type=='newsletters') {
							$stat->update("NEWSLETTER[".$data["newsletter_id"]."].NEWSLETTER.UNDELIVERED.ALL", 0);
							$stat->update("NEWSLETTER[".$data["newsletter_id"]."].NEWSLETTER.UNDELIVERED");
							$stat->update("NEWSLETTER.UNDELIVERED.ALL", 0);
							$stat->update("NEWSLETTER.UNDELIVERED");
							//$stat->update("USER[".$data["owner_id"]."].NEWSLETTER[".$data["newsletter_id"]."].NEWSLETTER.UNDELIVERED.ALL", 0);
							//$stat->update("USER[".$data["owner_id"]."].NEWSLETTER[".$data["newsletter_id"]."].NEWSLETTER.UNDELIVERED");
							//$stat->update("USER[".$data["owner_id"]."].NEWSLETTER.UNDELIVERED.ALL", 0);
							//$stat->update("USER[".$data["owner_id"]."].NEWSLETTER.UNDELIVERED", 0);
						}
						$sql="select bounce_count, status from subscribers".$config["table_prefix"]." where id='".$data["subscriber_id"]."'";
						$rs=$db->select($sql);
						if (isset($rs[0])) {
							$r=$rs[0];
							if ($r["bounce_count"]>=$POP3["bounce_count"]) {
								if ($POP3["bounce_type"]==1) {
									$r["status"]=0;
									$stat->update("NEWSLETTER[".$data["newsletter_id"]."].INACTIVE.ALL", 0);
									$stat->update("NEWSLETTER[".$data["newsletter_id"]."].INACTIVE");
								} elseif ($POP3["bounce_type"]==2) {
									$r=0;
									$stat->update("NEWSLETTER[".$data["newsletter_id"]."].DELETE.ALL", 0);
									$stat->update("NEWSLETTER[".$data["newsletter_id"]."].DELETE");
								} elseif ($POP3["bounce_type"]==3) {
									$r["status"]=3;
									$stat->update("NEWSLETTER[".$data["newsletter_id"]."].BLACKLIST.ALL", 0);
									$stat->update("NEWSLETTER[".$data["newsletter_id"]."].BLACKLIST");
								}
							} else $r["bounce_count"]++;
							if (isset($r["status"])) $sql=get_sql_update("subscribers".$config["table_prefix"], "bounce_count, status", $r, "where id='".$data["subscriber_id"]."'");
							else $sql="delete from subscribers".$config["table_prefix"]." where id='".$data["subscriber_id"]."'";
							$db->execute($sql);
						}
					}
					if ($type!='newsletters') {
	                                	$stat->update(($type=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".UNDELIVERED.ALL", 0);
	                                	$stat->update(($type=="campaign_schedule" ? "CAMPAIGN" : "AUTORESPONDER").".UNDELIVERED", $t);
					}
                        	}
                	}
		}
                $stat->close();
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