<?php
	require_once('TwitterAPIExchange.php');
	require_once('settings.php');

	$settings = [
		'oauth_access_token' => $_OAUTH_ACCESS_TOKEN,
		'oauth_access_token_secret' => $_OAUTH_ACCESS_TOKEN_SECRET,
		'consumer_key' => $_CONSUMER_KEY,
		'consumer_secret' => $_CONSUMER_SECRET
	];

	$postTwitter = new TwitterApiExchange($settings);

	$ids = file('tweets.csv', FILE_IGNORE_NEW_LINES);

	deleteMessages($ids);

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
