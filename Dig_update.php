<?php 

require_once("config.php");       
require_once("libs/db_mysql.php");
 

$SQL= new sqlQueries( PUBLIC_DATABASE_HOST, PUBLIC_DATABASE_DATABASE,PUBLIC_DATABASE_USER, PUBLIC_DATABASE_PASS);



 foreach( $SQL->query2assoc("SELECT `id`, `website_url` FROM ".PUBLIC_DATABASE_DATABASE.".`startups`") as $r){

	print($r['website_url']."\n");
	//clean the urls	
	$url = str_replace("http://","",$r['website_url']);
	$url = str_replace("https://","",$url);
	$url = str_replace("http:/","",$url);
	$url = str_replace("https:/","",$url);
	$url = str_replace("www.","",$url);
	if(strpos($url, "/")>1){
		$url = substr($url, 0, strpos($url, "/"));
	}	
	//$url = substr($url, 0, strpos($url, "#"));
	//$url = substr($url, 0, strpos($url, "?"));

	print($url."\n");
	
	$result = dns_get_record($url);
	print_r($result);
	print("\n\n");
	
	//extract MX and SPF records 
	foreach($result as $line){
		if($line['type']=="MX"){
			print_r($line);
			$dns_mx.=$line['host']." - ".$line['target']."\n";
		}
		if($line['type']=="TXT" and preg_match("/spf1/",$line['txt'])){
			print_r($line);
			$dns_spf.=$line['host']." - ".$line['txt']."\n";
		}
	}	
	print($dns_mx.$dns_spf);


	$SQL->query("update ".PUBLIC_DATABASE_DATABASE.".`startups` set 
				`dns` = '".json_encode($result)."',
				`dns_mx` = '".$SQL->e($dns_mx)."',
				`dns_spf` = '".$SQL->e($dns_spf)."'
				 where id=".$r['id']);
	

}


?>

