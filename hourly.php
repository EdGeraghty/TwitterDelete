<?php
	require_once('TwitterAPIExchange.php');
	require_once('settings.php');

	$settings = [
		'oauth_access_token' => $_OAUTH_ACCESS_TOKEN,
		'oauth_access_token_secret' => $_OAUTH_ACCESS_TOKEN_SECRET,
		'consumer_key' => $_CONSUMER_KEY,
		'consumer_secret' => $_CONSUMER_SECRET
	];

	$getTwitter = new TwitterAPIExchange($settings);
	$postTwitter = new TwitterAPIExchange($settings);

	$maxId = null;
	$iter = 0;

	//iterate through until we find the newest out of date tweet
	while (!isset($maxId)) {
		$ids = getIds($iter);

		if (is_array($ids)) {
			$maxId = $ids[0];
		}
		else if ($iter === $ids) {
			$maxId = $iter;
		}
		else {
			$iter = $ids;
		}
	}

	//Then delete from there!
	deleteMessages($maxId);

	/*
	* Since we're limited in how many tweets we can query in one go (200)
	* and also in a fifteen-minute window (1500) we need to be a bit careful.
	* This function will return an array of all tweet ids older than
	* the given date, or the last id if they're all newer!
	*/
	function getIds($maxId) {
		global $_USER;
		global $_NUMDAYS;
		global $getTwitter;

		//Get tweets
		$url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
		$getField = "?screen_name={$_USER}";
		$getField .= '&trim_user=true';
		$getField .= '&count=200';
		$getField .= '&include_rts=true';
		if ((int)$maxId > 0) {
			$getField .= "&max_id={$maxId}";
		}
		$requestMethod = 'GET';

		$messages = json_decode($getTwitter->setGetfield($getField)
						->buildOauth($url, $requestMethod)
  						->performRequest(),
					true);

		$ids = [];

		foreach($messages as $msg) {
			if (strtotime($msg['created_at']) < (time()-(60*60*24*$_NUMDAYS))) {
				$ids[] = $msg['id_str'];
			}
		}

		if (count($ids) == 0 && count($messages) > 0) {
			return $messages[count($messages)-1]['id_str'];
		}
		else if (count($ids) == 0 && count($messages) == 0) {
			die("Done!\n");
		}
		else {
			return $ids;
		}
	}

	/*
	* Make sure we don't bounce off rate limiting (this would be A Bad Thing^TM).
	* We want to delete as many messages in one go as we can.
	*/
	function deleteMessages($maxId) {
		global $postTwitter;

		print "deleting from {$maxId}\n";

		$rateLimited = false;

		while (!$rateLimited) {
			$ids = getIds($maxId);

			if (is_array($ids)) {
				foreach ($ids as $id) {
				        //Delete, one by one
       					$url = "https://api.twitter.com/1.1/statuses/destroy/{$id}.json";
  		      			$postField = ['trim_user'=>'true'];
	        	        	$requestMethod = 'POST';

        			        $response = json_decode($postTwitter->buildOauth($url, $requestMethod)
										->setPostFields($postField)
               	        			        	                ->performRequest(),
                       	        		        	true);
					//Crude, but there appears to be no other way to do this...
					$rateLimited = array_key_exists('error', $response);
					print "{$id} deleted!\n";
				}
			}
			else {
				$rateLimited = true;
			}
		}
	}

?>
