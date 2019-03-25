<?
/*
	MIME Generator 1.0.1

	Developed by .bh
*/

class MIMEContent {
	var $type='';
	var $subType='';
	var $boundary='';
	var $br="\n";
	var $headers=array();
	var $parts=array();

	function MIMEContent($type='text/plain', $attr=array()) {
		$this->checkType($type);
		if ($this->type=='multipart') {
			$this->setBoundary();
			$this->addHeader('Content-Type', $this->getType(), array('boundary' => $this->boundary));
			$this->addHeader('Content-Transfer-Encoding', '8bit');
		} elseif ($this->type=='text') {
			$attr['charset']=(isset($attr['charset']) ? $attr['charset'] : 'UTF-8');
			$this->addHeader('Content-Type', $this->getType(), array('charset' => $attr['charset']));
			$this->addHeader('Content-Transfer-Encoding', '8bit');
			//$this->addHeader('Content-Transfer-Encoding', '7bit');
		} else {
			$attr['name']=(isset($attr['name']) ? $attr['name'] : 'NoName');
			$this->addHeader('Content-Type', $this->getType(), array('name' => $attr['name']));
			$this->addHeader('Content-Transfer-Encoding', 'base64');
			$this->addHeader('Content-Disposition', 'attachment', array('filename' => $attr['name']));
		}
	}

	function checkType($type) {
		$array=explode('/', $type);
		$this->type=(isset($array[0]) ? $array[0] : 'text');
		$this->subType=(isset($array[1]) ? $array[1] : '');
	}

	function getType() {
		return ($this->type ? $this->type.($this->subType ? '/'.$this->subType : '') : '');
	}

	function initrand() {
		list($usec, $sec) = explode(' ', microtime());
		srand((float) $sec + ((float) $usec * 100000));
	}

	function setBoundary($len=70) {
		$this->initrand();
                $l='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
                $this->boundary='';
                for ($i=0; $i<$len; $i++) $this->boundary.=$l[rand()%strlen($l)];
	}

	function addHeader($name, $value, $attr=array()) {
		$this->headers[$name]=$value;
		if (is_array($attr)) {
			foreach ($attr as $attrName => $attrValue) {
				$this->headers[$name].=($this->headers[$name] ? '; ' : '')."{$attrName}=\"{$attrValue}\"";
			}
		}
	}

	function add($content) {
		if ($this->type=='multipart') $this->parts[]=trim($content);
		else $this->parts[0]=$content;
	}

	function getHeaders($ignore='') {
		$res='';
		$ignore=(is_array($ignore) ? $ignore : (isset($ignore) ? explode(', ', $ignore) : array()));
		foreach ($this->headers as $name => $value) if (!in_array($name, $ignore)) $res.="{$name}: {$value}".$this->br;
		return $res;
	}

	function get($headersEnabled=true) {
		$res='';
		if ($headersEnabled) {
			$res.=$this->getHeaders();
			$res.=$this->br;
		}
		if ($this->type=='multipart') {
			$res.='This is a multi-part message in MIME format.'.$this->br;
			$res.=$this->br;
			$res.='--'.$this->boundary.$this->br;
		}
		for ($i=0; $i<count($this->parts); $i++) {
			if ($i) $res.=$this->br.'--'.$this->boundary.$this->br;
			$res.=$this->parts[$i].$this->br;
		}
		if ($this->type=='multipart') $res.=$this->br.'--'.$this->boundary.'--'.$this->br;
		return $res;
	}
}

class MIME {
	var $content='';
	var $br="\n";
	var $headers=array();

	function MIME($from, $to, $subject='', $date='', $returnpath='') {
		$this->clear();
		$this->addHeader('From', $from);
		$this->addHeader('To', $to);
		$this->addHeader('Subject', $subject);
		$this->addHeader('Date', ($date ? $date : date('r')));
		if ($returnpath!=""){
			$this->addHeader('Return-Path', '"'.$returnpath.'" <'.$returnpath.'>');
			$this->addHeader('X-Return-Path', '"'.$returnpath.'" <'.$returnpath.'>');
			//$tosend = $this->mailOutNow("sarah@energenesis.com","sarah@energenesis.com","set return path","set:".$returnpath,"set:".$returnpath,0);
		} else {
			//$tosend = $this->mailOutNow("sarah@energenesis.com","sarah@energenesis.com","no return","no return","no return",0);
		}
		$this->addHeader('MIME-Version', '1.0');
	}

	function addHeader($name, $value, $attr=array()) {
		$this->headers[$name]=$value;
		if (is_array($attr)) {
			foreach ($attr as $attrName => $attrValue) {
				$this->headers[$name].=($this->headers[$name] ? '; ' : '')."{$attrName}=\"{$attrValue}\"";
			}
		}
	}

	function add($content) {
		$this->content=$content;
	}

	function getHeaders($ignore='') {
		$res='';
		$ignore=(is_array($ignore) ? $ignore : (isset($ignore) ? explode(', ', $ignore) : array()));
		foreach ($this->headers as $name => $value) if (!in_array($name, $ignore)) $res.="{$name}: {$value}".$this->br;
		return $res;
	}

	function get($headerEnabled=true) {
		$res='';
		if ($headerEnabled) $res.=$this->getHeaders();
		$res.=$this->content;
		return $res;
	}


	function clear() {
		$this->headers=array();
		$this->content='';
	}
/*
function mailOutNow($email_from,$email_to,$email_subject,$email_textmsg,$email_htmlmsg,$do_bcc=0){
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
}
*/
}
?>