<?php 

require_once("config.php");       
require_once("libs/db_mysql.php");
require_once("libs/Websummit.php"); 

$SQL= new sqlQueries( PUBLIC_DATABASE_HOST, PUBLIC_DATABASE_DATABASE,PUBLIC_DATABASE_USER, PUBLIC_DATABASE_PASS);

$ws = new Websummit();

for($i=1;$i<100;$i++){
	$params = array(
		"q" => "",
		"page" => $i,
		"auth_token" => $Websummit_tokenauth,
		"conference" => "ws15",
	  	/*"filters" => array( 
				"track"=> "",
				"country_code"=>"",
				"parent_industry"=>"",
				"amount_raised"=>""
				)*/
	);
	$result = $ws->sendRequest('', $params); 
	//print_r($ws->_response);
	$list=$ws->_response;
	foreach($list['startups'] as $company){
	 //print_r($company);
	 $SQL->insert('scrap.startups', $company); 
	}
}
?>

