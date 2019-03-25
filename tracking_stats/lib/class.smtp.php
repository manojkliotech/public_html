<?
Class SMTP {
        var $f;
        var $error_code=0;
        var $error="";
        var $res;
        var $debug=false;
        var $parts=array();
        var $mime;
        var $boundary="";
        var $info;
        var $message_id="";
	var $br="\r\n";

/*function mailOut($email_from,$email_to,$email_subject,$email_textmsg,$email_htmlmsg,$do_bcc=0){
	$boundary = md5(uniqid(time())); 
	$mailheaders  = 'From: ' . $email_from . "\n"; 
	$mailheaders .= 'To: ' . $email_to . "\n";
	if ($do_bcc=="1"){
		//$mailheaders .= 'Bcc: ' . "sarah@energenesis.com\n";
		//$mailheaders .= 'Bcc: ' . $do_bcc . "\n";
	}
	$mailheaders .= 'Return-Path: ' . $email_from . "\n"; 
	$mailheaders .= 'MIME-Version: 1.0' ."\n"; 
	$mailheaders .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . "\n\n";
	//$mailheaders .= $body_simple . "\n"; 
	$mailheaders .= '--' . $boundary . "\n"; 
	$mailheaders .= 'Content-Type: text/plain; charset=ISO-8859-1' ."\n"; 
	$mailheaders .= 'Content-Transfer-Encoding: 8bit'. "\n\n";
	$mailheaders .= $email_textmsg . "\n";
	$mailheaders .= '--' . $boundary . "\n";
	$mailheaders .= 'Content-Type: text/HTML; charset=ISO-8859-1' ."\n";
	$mailheaders .= 'Content-Transfer-Encoding: 8bit'. "\n\n";
	$mailheaders .= "<html><body>".$email_htmlmsg . "</body></html>\n"; 
	$mailheaders .= '--' . $boundary . "--\n"; 
	$mailNow = @mail("", $email_subject,"", $mailheaders, "-f$email_from");
	if ($mailNow){
		// do nothing, mail was sent...
	} else {
		if ($_SERVER["REMOTE_ADDR"]=="64.86.90.234"){
			print "problem sending mail?";
			exit;
		}
	}
}*/

        function SMTP($array) {
		$_SERVER;
                $this->info=(is_array($array) ? $array : array());
                $this->info["login"]=(isset($this->info["login"]) ? $this->info["login"] : "");
                $this->info["password"]=(isset($this->info["password"]) ? $this->info["password"] : "");
                $this->info["host"]=($this->info["host"] ? $this->info["host"] : "localhost");
                $this->info["port"]=(isset($this->info["port"]) ? $this->info["port"] : 25);

				$this->info["from"]=(isset($this->info["from"]) ? $this->info["from"] : '');
				$this->info["report"]=(isset($this->info["report"]) ? $this->info["report"] : '');
                $this->info["to"]=(isset($this->info["to"]) ? $this->info["to"] : "");
                $this->info["charset"]=(isset($this->info["charset"]) ? $this->info["charset"] : "UTF-8");
                $this->info["content_type"]=(isset($this->info["content_type"]) ? $this->info["content_type"] : "text/plain");
                $this->info["priority"]=(isset($this->info["priority"]) ? $this->info["priority"] : 3);
                $this->info["message"]=(isset($this->info["message"]) ? $this->info["message"] : "");
				$this->info["message"]=preg_replace(array("/(href=)[\"']{1}\/([^\"\']+)[\"\']{1}/", "/(src=)[\"']{1}\/([^\"\']+)[\"\']{1}/i"), "$1\"http://".$_SERVER['HTTP_HOST']."/$2\"", $this->info["message"]);
				$this->info["message"]=str_replace("\n", "\r\n", str_replace("\r\n", "\n", $this->info["message"]));
				$this->convert();
        }

	function convert() {
		$array=array('from_name', 'to_name', 'subject', 'reply_to_name', 'message');
		foreach ($array as $key => $name) {
			if (isset($this->info[$name])) $this->info[$name]=(function_exists("iconv") ? iconv("UTF-8", $this->info["charset"], $this->info[$name]) : $this->info[$name]);
		}
	}

/*
        function connect($is_auth=true) {
		if ($this->debug) print "Start connection to {$this->info[host]}:{$this->info[port]}\n";
                $this->f=fsockopen($this->info["host"], $this->info["port"], $errno, $errstr, 30);
                if ($this->f) {
					if ($this->debug) print "Connected\n";
                        $this->get_answer();
                        if (!$this->error_code) {
							if ($is_auth) {
                                	if ($this->send_command("EHLO localhost")) {
                                        	if ($this->send_command("AUTH LOGIN")) {
                                                	if ($this->send_command(base64_encode($this->info["login"]))) {
                                                        	if ($this->send_command(base64_encode($this->info["password"]))){
																// command above runs what we need...
															} else {
																// password failed
																if ($this->debug) print "Bad Authentication\n";
																$this->error_code=-2;
															}
                                                	} else {
														if ($this->debug) print "Bad Authentication\n";
														$this->error_code=-2;
													}
                                        	}
                                	}
							} else $this->send_command("HELO localhost");
                        }
                } else {
					if ($this->debug) print "Can't connect\n";
					$this->error_code=-1;
				}
                return (!$this->error_code);
        }
*/
        function connect($is_auth=true) {
		if ($this->debug) print "Start connection to {$this->info[host]}:{$this->info[port]}\n";
                $this->f=fsockopen($this->info["host"], $this->info["port"], $errno, $errstr, 30);
				$this->error_code=0;
				if ($errno!="" || $errstr!=""){
					if ($this->debug) print "there was an error\n";
					$this->error_code=-3;
					//if ($this->info["host"]!="mail.mydentalwebsitegurus.net"){
						//$tosend = $this->mailOut("sarah@energenesis.com","sarah@energenesis.com","mail conn problem(1):::: ".$this->info["host"],"errno: ".$errno." errstr: ".$errstr." host: ".$this->info["host"]." to: ".$this->info["return_path"],"errno: ".$errno." errstr: ".$errstr." host: ".$this->info["host"]." to: ".$this->info["return_path"],0);
					//}
				} else {
				   if ($this->f) {
						if ($this->debug) print "Connected\n";
	                        $this->get_answer();
	                        if (!$this->error_code) {
								if ($is_auth) {
	                                	if ($this->send_command("EHLO localhost")) {
	                                        	if ($this->send_command("AUTH LOGIN")) {
	                                                	if ($this->send_command(base64_encode($this->info["login"]))) {
	                                                        	if ($this->send_command(base64_encode($this->info["password"]))){
																	// command above runs what we need...
																} else {
																	// password failed
																	if ($this->debug) print "Bad Authentication\n";
																	$this->error_code=-2;
																}
	                                                	} else {
															if ($this->debug) print "Bad Authentication\n";
															$this->error_code=-2;
														}
	                                        	}
	                                	}
								} else $this->send_command("HELO localhost");
	                        }
	                } else {
						if ($this->debug) print "Can't connect\n";
						$this->error_code=-1;
					}
				}
				unset($errno);
				unset($errstr);
                return (!$this->error_code);
        }

        function get_answer() {
                $this->error_code=0;
                if ($this->f) {
                        $line=fgets($this->f, 1024);
                        if ($this->debug) print $line;
                        $this->error=substr($line, 4);
                        while (substr($line, 3, 1)!=" ") {
                                $line=fgets($this->f, 1024);
                                if ($this->debug) print $line;
                                $this->error.=substr($line, 4);
                        }
                        $x=substr($line, 0, 1);
                        if (!in_array($x, array(1, 2, 3))) $this->error_code=substr($line, 0, 3);
                } else $this->error_code=-1;
                return !$this->error_code;
        }

        function send_command($name, $param="") {
                if ($this->f) {
                        $c=$name.($param ? " $param" : "")."\r\n";
                        if ($this->debug) print $c;
                        fputs($this->f, $c);
                        $this->get_answer();
                } else $this->error_code=-1;
                return !$this->error_code;
        }

        function addAttach($filename, $contentType="application/octet-stream") {
		$data=file_get_contents($filename);
		if ($data) {
			preg_match('/[^\/]+$/', $filename, $array);
			$content=new MIMEContent($contentType, array('name' => $array[0]));
			$content->add(chunk_split(base64_encode($data)));
               		$this->parts[]=$content->get();
		}
        }

        function get_message($smtp=true) {
		$content=new MIMEContent($this->info["content_type"], array('cherset' => $this->info["charset"]));
		$content->add($this->info["message"]);
		$text=$content->get();

		$content=new MIMEContent('multipart/mixed');
		$content->add($text);
		for ($i=0; $i<count($this->parts); $i++) $content->add($this->parts[$i]);

		$from='"'.(isset($this->info["from_name"]) ? $this->info["from_name"] : $this->info["from"]).'" <'.$this->info["from"].'>';
		$to='"'.(isset($this->info["to_name"]) ? $this->info["to_name"] : $this->info["to"]).'" <'.$this->info["to"].'>';
                $subject=$this->info["subject"];
		$replyTo='"'.(isset($this->info["reply_to_name"]) ? $this->info["reply_to_name"] : $this->info["reply_to"])."\" <".$this->info["reply_to"].">";

		$mime=new MIME($from, $to, $subject, '', $this->info["return_path"]);
		if (isset($this->info["reply_to"]) and $this->info["reply_to"]) $mime->addHeader('Reply-To', $replyTo);

		$mime->add($content->get(false));
		$res=array(
			'headers'	=> $mime->getHeaders((!$smtp ? 'To, Subject' : '')).$content->getHeaders(),
			'content'	=> $mime->get(false)
		);
		//$tosend = $this->mailOut("sarah@energenesis.com","sarah@energenesis.com","mime header info",$mime->getHeaders((!$smtp ? 'To, Subject' : '')),"",0);
		return $res;
        }

/*
        function send($is_auth=true) {
                $this->close();
                if ($this->connect($is_auth)) {
                        //if ($this->send_command("MAIL FROM:", "<".($this->info["report"] ? $this->info["report"] : $this->info["from"]).">")) {
						if ($this->send_command("MAIL FROM:", "<".($this->info["return_path"] ? $this->info["return_path"] : $this->info["from"]).">")) {
                                if ($this->send_command("RCPT TO:", $this->info["to"])) {
                                        if ($this->send_command("DATA")) {
						$message=$this->get_message();
//$tosend = $this->mailOut("sarah@energenesis.com","sarah@energenesis.com","the headers to use (send to smtp)","set the headers to:<br>".$message['headers'],"set the headers to:\n".$message['headers'],0);
                                                $this->send_command($message['headers'].$this->br.$message['content'].$this->br.$this->br.'.'.$this->br);
                                                if (!$this->error_code) {
                                                        preg_match("/=?([\w\d-_]+)$/", trim($this->error), $array);
                                                        $this->message_id=(isset($array[1]) ? $array[1] : "");
                                                }
                                        }
                                }
                        }
                        $res=!$this->error_code;
                        $this->close();
                } else {
					if ($this->debug) print "... error code: ".$this->error_code."\n";
					if ($this->error_code=="-3"){
						$this->error_code = "0";
						$this->message_id="noconnect";
					}
					if ($this->error_code=="-2"){
						$this->error_code = "0";
						$this->message_id="badauth";
						if ($this->debug) print "... set badauth info\n";
					}
					$res=!$this->error_code;
				}
				//} else $res=!$this->error_code;
                return $res;
        }
*/
        function send($is_auth=true) {
                $this->close();
					$this->error_code = "0";
					$start_connection_now = $this->connect($is_auth);
					//if ($this->debug) print "... error code: ".$this->error_code."\n";
					if ($this->error_code=="-3"){
						if ($this->debug) print "... no connect info\n";
						$this->error_code = "0";
						$this->message_id="noconnect";
						$res = "1";
						//$tosend = $this->mailOut("sarah@energenesis.com","sarah@energenesis.com","mail conn problem (2):::: ".$this->info["host"]," host: ".$this->info["host"]." to: ".$this->info["return_path"]," host: ".$this->info["host"]." to: ".$this->info["return_path"],0);
					} elseif ($this->error_code=="-2"){
						$this->error_code = "0";
						$this->message_id="badauth";
						$res = "1";
						if ($this->debug) print "... set badauth info\n";
					} else {
						if ($start_connection_now) {
		                        //if ($this->send_command("MAIL FROM:", "<".($this->info["report"] ? $this->info["report"] : $this->info["from"]).">")) {
								if ($this->send_command("MAIL FROM:", "<".($this->info["return_path"] ? $this->info["return_path"] : $this->info["from"]).">")) {
		                                if ($this->send_command("RCPT TO:", $this->info["to"])) {
		                                        if ($this->send_command("DATA")) {
								$message=$this->get_message();
		//$tosend = $this->mailOut("sarah@energenesis.com","sarah@energenesis.com","the headers to use (send to smtp)","set the headers to:<br>".$message['headers'],"set the headers to:\n".$message['headers'],0);
		                                                $this->send_command($message['headers'].$this->br.$message['content'].$this->br.$this->br.'.'.$this->br);
		                                                if (!$this->error_code) {
		                                                        preg_match("/=?([\w\d-_]+)$/", trim($this->error), $array);
		                                                        $this->message_id=(isset($array[1]) ? $array[1] : "");
		                                                }
		                                        }
		                                }
		                        }
		                        $res=!$this->error_code;
		                        $this->close();
		                } else {
							$res=!$this->error_code;
						}
					}
				//} else $res=!$this->error_code;
                return $res;
        }

        function send_mail() {
		$message=$this->get_message(false);
                $res=mail(
			$this->info["to"],
			$this->info["subject"],
			str_replace("\r", '', $message['content']),
			$message['headers']
		);
                $this->message_id=($res ? "NONE" : "");
                return $res;
		
        }

        function close() {
                if (isset($this->f)) {
                        $this->send_command("QUIT");
                        fclose($this->f);
                }
        }

}

if (!class_exists('MIME')) die("Can't find MIME class");
?>