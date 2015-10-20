# Small hack to use List of attendees and statups at WebSummit 2015

This is short description of the process: 

1. Download data from Websummit website
=======================================

2 sources : 
	- list of attendees: https://websummit.net/attendees/featured-attendees  (behind there is a API : https://api.cilabs.net/v1/conferences/ws15/info/attendees?page=1). The content is really limited and would require lots of reconciliation of data to be sure of the data recovered (no URL, no twitter... just name of company and name of attendee)
	- list of startups (gold mine): https://my.websummit.net/ws15/startup-search/#/ (need to have a attendee login to access). There is no API but an easy to hack json feed (neeeding a user token) 

For our need we took this second list. It contains lots of links and informations already.

2. Clean the information
========================

The information about startups was entered manualy so we can totaly rely on the quality of the information. Some twitter links for example are : http://www.twitter.com/xxxxxx, @xxxxxx , xxxxxx .... 

So we first clean all this to keep only the real twitter username.

Same process for the Angel.co urls

3. Twitter information
======================

We can get some nice info about the number of followers on twitter. 
Using the the twitter API with a simple :

```
$code = $tmhOAuth->request('GET', 'https://api.twitter.com/1.1/users/lookup.json',
	array('screen_name' => $username)
);
if($code == 200){
	return json_decode($tmhOAuth->response['response'],true); 
} else {
	return false;
}
``` 

You can get some interesting informations like these : 

```
Array
(
    [id] => 2921958671
    [id_str] => 2921958671
    [name] => Neamtime
    [screen_name] => neamtime
    [location] => 
    [description] => 
    [url] => 
    [entities] => Array
        (
            [description] => Array
                (
                    [urls] => Array
                        (
                        )

                )

        )

    [protected] => 
    [followers_count] => 0
    [friends_count] => 0
    [listed_count] => 0
    [created_at] => Sun Dec 14 20:49:40 +0000 2014
    [favourites_count] => 0
    [utc_offset] => 7200
    [time_zone] => Ljubljana
    [geo_enabled] => 
    [verified] => 
    [statuses_count] => 0
    [lang] => en
    [contributors_enabled] => 
    [is_translator] => 
    [is_translation_enabled] => 
    [profile_background_color] => C0DEED
    [profile_background_image_url] => http://abs.twimg.com/images/themes/theme1/bg.png
    [profile_background_image_url_https] => https://abs.twimg.com/images/themes/theme1/bg.png
    [profile_background_tile] => 
    [profile_image_url] => http://pbs.twimg.com/profile_images/647550564499329024/Fi7hdfOd_normal.png
    [profile_image_url_https] => https://pbs.twimg.com/profile_images/647550564499329024/Fi7hdfOd_normal.png
    [profile_link_color] => 0084B4
    [profile_sidebar_border_color] => C0DEED
    [profile_sidebar_fill_color] => DDEEF6
    [profile_text_color] => 333333
    [profile_use_background_image] => 1
    [has_extended_profile] => 
    [default_profile] => 1
    [default_profile_image] => 
    [following] => 
    [follow_request_sent] => 
    [notifications] => 
)
```


NB : this Twitter API call is limited to 150 calls per hours. So it can take a bit of time before you manage to cover all your datas 

4. Angel List 
=============

Angel List provides a nice API to get information. We chose 2 API resources /startups and /startups/$id/roles. (https://angel.co/api/spec/startups)

You need to register to get credential to use this and the number of call seems limited for a certain period.

You can't directly find the startup from their name so we had first to go through the search resource (https://angel.co/api/spec/search ) to find the id of the startup we wanted information about.

We did 2 pass :
	- using the clean angel.co url 
	- using the name of the company (some companies had instances in the angel.co DB but didn't put the link to their Angel.co page.

5. DNS information 
==================

As an ESP, we are interested to look at what those company are using as ESP. 

We run a DNS information request to extract MX and SPF records.
