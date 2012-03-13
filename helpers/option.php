<?php 

class PL_Option_Helper {
	
	function api_key() {
	    $api_key = PL_Options::get('placester_api_key');
		if (strlen($api_key) <= 0) {
			return false;
		}
	    return $api_key;	    
	}

	function set_api_key($new_api_key) {
		if (get_option('placester_api_key') == $new_api_key) {
			return array('result' => false,'message' => 'You\'re already using that Placester API Key.');
		}
		$response = PL_User::whoami(array('api_key' => $new_api_key));
		if ($response && isset($response['user'])) {
			$option_result = PL_Options::set('placester_api_key', $new_api_key);
			if ($option_result) {
				// Nuke the cache if they change their API Key
				PL_HTTP::clear_cache();
				return array('result' => true,'message' => 'You\'ve successfully changed your Placester API Key. This page will reload in momentarily.');
			} else {
				return array('result' => false,'message' => 'There was an error. Are you sure that\'s a valid Placester API key?');
			}
		} 
		return array('result' => false,'message' => 'That \'s not a valid Placester API Key.');
	}

	function post_slug() {
	    $url_slug = get_option('placester_url_slug');
	    if (strlen($url_slug) <= 0) {
	    	$url_slug = 'listing';
	    }
	    return $url_slug;
	}

//end of class
}