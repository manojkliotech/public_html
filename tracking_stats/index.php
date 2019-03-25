<?php
echo $_SERVER["HTTP_HOST"];
mysql_connect('mzs.cirrqkkhmj8y.us-west-2.rds.amazonaws.com', 'statsadm17', 'aFyjaR5dTXmeQZk2') or die('Could not connect the database : Username or password incorrect');
mysql_select_db('kmmzsdb') or die ('No database found');
echo 'Database Connected successfully';
?>
