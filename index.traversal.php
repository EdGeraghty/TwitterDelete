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

	$deleteFrom = date('Y-m-d', (time()-(60*60*24*$_NUMDAYS)));

	while (strtotime($deleteFrom) < (time()-(60*60*24*$_NUMDAYS))) {
		$url = "https://twitter.com/i/search/timeline?f=realtime&q=from:{$_USER}%20until:{$deleteFrom}&src=typd";
		$json = file_get_contents($url);
		$boom = explode('href=\"\/' . $_USER . '\/status\/', $json);
		for ($i = 0; $i < count($boom); ++$i) {
			$boom[$i] = explode('\"', $boom[$i])[0];
		}
		unset($boom[0]);

		deleteMessages($boom);

		$deleteFrom = date('Y-m-d', strtotime('-1 day', strtotime($deleteFrom)));
		print "{$deleteFrom}\n";
	}

	/*
	* Make sure we don't bounce off rate limiting (this would be A Bad Thing^TM).
	* We want to delete as many messages in one go as we can.
	*/
	function deleteMessages($ids) {
		global $postTwitter;

		$rateLimited = false;

		while (!$rateLimited) {
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
				return;
			}
			else {
				$rateLimited = true;
			}
		}
	}

?>
