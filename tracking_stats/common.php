<?
session_start();
$PATH=$config["path"];
//$PATH=$BASE_PATH."mailing/";
$HTTP_HOST=$_SERVER['HTTP_HOST'];
chdir($DOCUMENT_ROOT.$PATH);
include "lib/db.php";
include "lib/tools.php";
include "lib/template.php";
include "lib/class.link.php";
include "lib/class.message.php";

get_all_vars();
$db = new DB();

//include "admin/timezone.php";

$array=pathinfo($_SERVER['SCRIPT_NAME']);
$link=new Link();
//$link->parse($_SERVER['REQUEST_URI']);


//$page->dictionary("common/dict.ru");
//$page->assign(array(
//        "PATH"  => $PATH
//));

/*function check_level($module="") {
         global $ui, $db, $config;
        $res=array();
        if ($ui["system_status"]==1) $res["admin"]=1;
        else {
                $sql="select a.title from users".$config["table_prefix"]." u, user_access".$config["table_prefix"]." ua, access".$config["table_prefix"]." a, modules".$config["table_prefix"]." m where
                        u.login='".$ui["login"]."' and
                        u.password='".$ui["password"]."' and
                        u.status='1' and
                        u.id=ua.user_id and
                        a.id=ua.access_id and
                        a.module_id=m.id and
                        m.title='$module'";
                $rs=$db->select($sql);
                for ($i=0; $i<count($rs); $i++) $res[$rs[$i]["title"]]=1;
        }
        return $res;
}*/

//return $page;
?>