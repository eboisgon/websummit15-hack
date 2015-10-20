<?php 

require_once("config.php");       
require_once("libs/db_mysql.php");
 

$SQL= new sqlQueries( PUBLIC_DATABASE_HOST, PUBLIC_DATABASE_DATABASE,PUBLIC_DATABASE_USER, PUBLIC_DATABASE_PASS);

 foreach( $SQL->query2assoc("SELECT `id`, `angellist_url`, `company_name` as `angellist_url` FROM scrap.`startups` where `angel_startup` =''") as $r){

	//print_r($r);
	
	
	//clean the Angellist url 
	if($r['angellist_url']){
		$angel_search = str_replace("https://angel.co/","",$r['angellist_url']);
		$angel_search = str_replace("www.angel.co/","",$angel_search);
		$angel_search = str_replace("angel.co/","",$angel_search);
		$angel_search = str_replace("http://","",$angel_search);
		$angel_search = str_replace("https://","",$angel_search);
		$angel_search = str_replace("http:/","",$angel_search);
		$angel_search = str_replace("https:/","",$angel_search);
		$angel_search = str_replace("/","",$angel_search);
		$angel_search = str_replace("-1","",$angel_search);
		$angel_search = str_replace("-2","",$angel_search);
		print($angel_search."\n");
		print("SEARCH FOR : $angel_search \n");
	} else {
		//we don't have a URL but can stuill search with the company_name
		$angel_search = str_replace(" ","%20",$r['company_name']);
		print("SEARCH FOR COMPANY : $angel_search \n");
	}	
	$couc=file_get_contents('https://api.angel.co/1/search?type=Startup&query='.$angel_search.'&access_token='.$Angellist_tokenauth);
	$info_search=json_decode($couc,true);
	print("SEARCH RESULT : \n");		
	print_r($info_search);
	$info="";
	$info_role="";
	if($info_search){
		//Get Company info
		$info=file_get_contents('https://api.angel.co/1/startups/'.$info_search[0]['id'].'?access_token='.$Angellist_tokenauth);
		$info_startup=json_decode($info,true);
		print_r($info_startup);
		
		//Get Company roles
		$info_role=file_get_contents('https://api.angel.co/1/startups/'.$info_search[0]['id'].'/roles?access_token='.$Angellist_tokenauth);
		$info_startup_role=json_decode($info_role,true);
		print_r($info_startup_role);
		
		if($info_startup){
			$SQL->query("update scrap.`startups` set 
				`angel_startup` = '".$SQL->e(json_encode($info_startup))."',
				`angel_startup_role` = '".$SQL->e(json_encode($info_startup_role))."'
				 where id=".$r['id']);
			print("DONE\n");
		} else {
			print("PASS\n");
		}
	}




}


?>

