<?
Class Message {
	var $tpl;
	var $count;

	function Message($type="message") {
		global $PATH;
		$this->tpl=new Template("templates/show_message.tpl");
		$this->tpl->dictionary("common/dict.ru");
		$this->tpl->assign(array(
			"type"	=> ($type=="message" ? $type : "error"),
			"title"	=> ($type=="message" ? "{rs:UPDATE}" : "{rs:ERROR}"),
			"PATH"	=> $PATH,
		));
		$this->count=0;
		
	}

	function add($message) {
		$this->tpl->addrow("MSG_LINE", array("message" => $message));
		$this->count++;
	}

	function get() {
		return $this->tpl->evaluate();
	}
}
?>