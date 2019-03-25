<?
if (isset($function_sql_already_included)) return;
$function_sql_already_included = 1;

function get_sql_insert($table, $fields, $val) {
	
	$fields=(is_array($fields) ? $fields : ($fields ? explode(", ", $fields) : array()));
	$val=(is_array($val) ? $val : ($val ? explode(", ", $val) : array()));
	$array=array();
	for ($i=0; $i<count($fields); $i++) $array[]="'".$val[$fields[$i]]."'";
	return (count($fields) ? "insert into $table (".implode(", ", $fields).") values (".implode(", ", $array).")" : "") ;
}

function get_sql_update($table, $fields, $val, $where) {
	
	$fields=(is_array($fields) ? $fields : ($fields ? explode(", ", $fields) : array()));
	$val=(is_array($val) ? $val : ($val ? explode(", ", $val) : array()));
	if (count($fields)) {
		$sql="update $table set ";
		
		for ($i=0; $i<count($fields); $i++) $sql.=($i ? ", " : "").$fields[$i]."='".$val[$fields[$i]]."'";
		
		return $sql.=" $where";
	}
	return "";
}

function get_row_from_table($name, $table, $id=0, $key="id", $val="title", $page="page") {
	global $db;
	$sql="select $key as id, $val as title from $table  order by title  ";
	return get_row_from_sql($name, $sql, $id, $page);
}

function get_row_from_table22($name, $table, $id=0, $key="id", $val="title", $company_id,$page="page") {

	global $db;
	$sql="select $key as id, $val as title from $table  where  is_delete ='0' and  owner_id IN (select id from users_mailing where company_id=".$company_id." or id=".$company_id.") order by title ";
	return get_row_from_sql($name, $sql, $id, $page);
}
function get_row_from_table33($name, $table, $id=0, $key="id", $val="title", $company_id,$page="page") {
	$table='mailing.newsletters_mailing';
	
	global $db;
	$sql="select $key as id, $val as title from $table  where owner_id IN (select id from mailing.users_mailing where company_id=".$company_id." or id=".$company_id.") order by title ";
	return get_row_from_sql($name, $sql, $id, $page);
}

function get_row_from_table23($name, $table, $id=0, $key="id", $val="title", $company_id,$page="page") {

	global $db;
	$sql="select $key as id, $val as title from $table  where owner_id IN (select id from users_mailing where company_id=".$company_id." or id=".$company_id.") order by title ";
	return get_row_from_sql($name, $sql, $id, $page);
}


function get_row_from_sql($name, $sql, $id=0, $p="page") {
	global $db, $$p;
	$id=(is_array($id) ? $id : array($id));
	$rs=$db->select($sql);
	if (count($rs)) $res=array($rs[0]["id"]);
	for ($i=0; $i<count($rs); $i++) {
		$$p->addrow($name, array(
			"first"		=> !$i,
			'n'		=> $i+1,
			"id"		=> $rs[$i]["id"],
			"title"		=> $rs[$i]["title"],
			"selected"	=> (in_array($rs[$i]["id"], $id) ? " selected" : ""),
		));
		$res=(in_array($rs[$i]["id"], $id) ? $id : $res);
	}
	return $res;
}

function is_val($table, $where) {
	global $db;
	$sql="select count(*) from $table $where";
	$rs=$db->select($sql);
	return $rs[0][0];
}

function get_val($table, $field, $where) {
	global $db;
	$sql="select $field from $table $where";
	$rs=$db->select($sql);
	return (isset($rs[0][0]) ? $rs[0][0] : "");
}

function get_rec($table="", $where="") {
	global $db;
	$sql="select * from $table $where";
	$rs=$db->select($sql);
	return (isset($rs[0]) ? $rs[0] : array());
}
?>