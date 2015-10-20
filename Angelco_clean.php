<?php 

require_once("config.php");       
require_once("libs/db_mysql.php");

$SQL= new sqlQueries( PUBLIC_DATABASE_HOST, PUBLIC_DATABASE_DATABASE,PUBLIC_DATABASE_USER, PUBLIC_DATABASE_PASS););



 foreach( $SQL->query2assoc("SELECT `id`, `company_name`, `angel_startup` FROM scrap.`startups` where `angellist_url`='' and `angel_startup` !=''") as $r){

	//print_r($r);

	$temp=json_decode($r['angel_startup'],true);

	//print_r($temp);
	print($r['company_name']." - ".$temp['name']."\n");	
	
	if($r['company_name']==$temp['name']){
		$SQL->query("update scrap.`startups` set 
				`angellist_url` = '".$temp['angellist_url']."'
				 where id=".$r['id']);
		print("OK"."\n");
	} elseif(strtolower(trim($r['company_name']))==strtolower(trim($temp['name']))){
		$SQL->query("update scrap.`startups` set 
				`angellist_url` = '".$temp['angellist_url']."'
				 where id=".$r['id']);
		print("OK"."\n");
	} elseif(strlen($r['company_name'])>7 and preg_match("/".strtolower(trim($r['company_name']))."/",strtolower(trim($temp['name'])))){
		$SQL->query("update scrap.`startups` set 
				`angellist_url` = '".$temp['angellist_url']."'
				 where id=".$r['id']);
		print("OK"."\n");
	} elseif(! preg_match("/".strtolower(trim($r['company_name']))."/",strtolower(trim($temp['name'])))){
		$SQL->query("update scrap.`startups` set 
				`angel_startup` = '',
				`angel_startup_role` = ''
				 where id=".$r['id']);
		print("REMOVE"."\n");
	} else {
		$SQL->query("update scrap.`startups` set 
				`angel_startup` = '',
				`angel_startup_role` = ''
				 where id=".$r['id']);
		print("NOT OK"."\n");
	}



}


?>

