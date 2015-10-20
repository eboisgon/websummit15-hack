<?php 

require_once("config.php");       
require_once("libs/db_mysql.php");

$SQL= new sqlQueries( PUBLIC_DATABASE_HOST, PUBLIC_DATABASE_DATABASE,PUBLIC_DATABASE_USER, PUBLIC_DATABASE_PASS);

for($i=1;$i<200;$i++){
  //print($i."\n");
  //this url is public :) 
  $info=file_get_contents("https://api.cilabs.net/v1/conferences/ws15/info/attendees?page=".$i);
  $list=json_decode($info,true);
  foreach($list['attendees'] as $info){
	//create company 
  	$query = "INSERT IGNORE INTO `scrap`.`companies` ( `name`, `country`) VALUES ('".addslashes($info['company'])."', '".addslashes($info['country'])."')";
	//print($query."\n"); 	
	$R = $SQL->query($query);  
	
	//find company
       	$query = "select `id_companies` from `scrap`.`companies` where `name` = '".addslashes($info['company'])."' and  `country` ='".addslashes($info['country'])."' ";   
	//print($query."\n"); 
        $R = $SQL->query2cell($query);
	//print($R."\n");
	
	//insert attendee  
	$query = "INSERT IGNORE INTO `scrap`.`persons` ( `name`, `position`, `description`, `id_ext`, `id_company`, `country`, `avatar`) 
			VALUES ('".addslashes($info['name'])."', '".addslashes($info['career'])."', '".addslashes($info['bio'])."', ".$info['id'].", ".$R.", '".addslashes($info['country'])."', '".addslashes($info['avatar_url'])."')";
	//print($query."\n"); 	
	$R = $SQL->query($query);  	
   }
 
}

?>

