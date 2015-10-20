<?php 

require_once("config.php");       
require_once("libs/db_mysql.php");

$SQL= new sqlQueries( PUBLIC_DATABASE_HOST, PUBLIC_DATABASE_DATABASE,PUBLIC_DATABASE_USER, PUBLIC_DATABASE_PASS);


function checkTwitter($username){
		require_once('libs/Twitter/tmhOAuth-master/tmhOAuth.php');
		require_once('libs/Twitter/tmhOAuth-master/tmhUtilities.php');
		$tmhOAuth = new tmhOAuth(array(
			  'consumer_key'    => TWITTER_CONSUMER_KEY,
			  'consumer_secret' => TWITTER_CONSUMER_SECRET,
			  'user_token'      => TWITTER_USER_TOKEN,
			  'user_secret'     => TWITTER_USER_SECRET
		));
 		$code = $tmhOAuth->request('GET', 'https://api.twitter.com/1.1/users/lookup.json',
				array('screen_name' => $username)
		);
		if($code == 200){
			return json_decode($tmhOAuth->response['response'],true); 
		} else {
			return false;
		}
	}

foreach( $SQL->query2assoc("SELECT `id`,`twitter_url` FROM ".PUBLIC_DATABASE_DATABASE.".`startups` where `twitter_url`!='' and twitter_followers_count=0 and twitter_url_2 is Null") as $r){
	//print_r($r);
	$twitter = str_replace("https://twitter.com/","",$r['twitter_url']);
	$twitter = str_replace("http://twitter.com/","",$twitter);
	$twitter = str_replace("www.twitter.com/","",$twitter);
	$twitter = str_replace("twitter.com/","",$twitter);
	$twitter = str_replace("http://","",$twitter);
	$twitter = str_replace("https://","",$twitter);
	$twitter = str_replace("http:/","",$twitter);
	$twitter = str_replace("https:/","",$twitter);
	$twitter = str_replace("@","",$twitter);
	print($twitter."\n");
	$res=checkTwitter($twitter);
	if($res and $res[0]){

	print("update ".PUBLIC_DATABASE_DATABASE.".`startups` set `twitter_url_2` = '".$SQL->e($res[0]['url'])."',
			`twitter_url` = '".$SQL->e($twitter)."',
			`twitter_followers_count` = '".$res[0]['followers_count']."',
			`twitter_friends_count` = '".$res[0]['friends_count']."',
			`twitter_description` = '".$SQL->e($res[0]['description'])."'
			 where id=".$r['id']);
	$SQL->query("update ".PUBLIC_DATABASE_DATABASE.".`startups` set `twitter_url_2` = '".$SQL->e($res[0]['url'])."',
			`twitter_url` = '".$SQL->e($twitter)."',
			`twitter_followers_count` = '".$res[0]['followers_count']."',
			`twitter_friends_count` = '".$res[0]['friends_count']."',
			`twitter_description` = '".$SQL->e($res[0]['description'])."'
			 where id=".$r['id']);
		print("DONE\n");
	} else {
		print("PASS\n");
	}

}


?>

