<?
$DOCUMENT_ROOT=$_SERVER["DOCUMENT_ROOT"]."/tracking_stats";
$domain=$_SERVER['SERVER_NAME'];
$config=array(
        "db"            => array (
                                "server"        => "mzs.cirrqkkhmj8y.us-west-2.rds.amazonaws.com",
                                "username"      => "statsadm17",
                                "password"      => "aFyjaR5dTXmeQZk2",
                                "port"          => "",
                                "database"      => "kmmzsdb"),
                                "table_prefix"  => "_mailing",
                                "path"          => "/",
                                "host"          => "$domain");

$BASE_PATH='http://$domain/';
$TIME_ZONE = 'America/Los_Angeles';

function filter($data) {
        $data = trim(htmlentities(strip_tags($data)));

        if (get_magic_quotes_gpc())
                $data = stripslashes($data);

        $data = mysql_real_escape_string($data);

        return $data;
}
?>
