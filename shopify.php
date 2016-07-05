<?php

	namespace phpish\shopify;
	use phpish\http;


	function install_url($shop, $api_key)
	{
		return "http://$shop/admin/api/auth?api_key=$api_key";
	}
	
	function is_valid_request($query_params, $shared_secret) { 
		if(!is_array($query_params)) return false; 
		if(array_key_exists('shop',$query_params) && array_key_exists('timestamp',$query_params) && array_key_exists('hmac',$query_params)) { 
			$hmac = $query_params['hmac']; 
			unset($query_params['signature']); 
			unset($query_params['hmac']); 
			ksort($query_params); 
			return $hmac == hash_hmac('sha256', http_build_query($query_params), $shared_secret); 
		} 
		return false; 
	}
	
	function authorization_url($shop, $api_key, $scopes=array(), $redirect_uri='')
	{
		$scopes = empty($scopes) ? '' : '&scope='.implode(',', $scopes);
		$redirect_uri = empty($redirect_uri) ? '' : '&redirect_uri='.urlencode($redirect_uri);
		return "https://$shop/admin/oauth/authorize?client_id=$api_key$scopes$redirect_uri";
	}


	function access_token($shop, $api_key, $shared_secret, $code)
	{
		try
		{
			$response = http\request("POST https://$shop/admin/oauth/access_token", array(), array('client_id'=>$api_key, 'client_secret'=>$shared_secret, 'code'=>$code));
		}
		catch (http\CurlException $e) { throw new CurlException($e->getMessage(), $e->getCode(), $e->getRequest()); }
		catch (http\ResponseException $e) { throw new ApiException($e->getMessage(), $e->getCode(), $e->getRequest(), $e->getResponse()); }

		return $response['access_token'];
	}


	function client($shop, $api_key, $oauth_token, $private_app=false)
	{
		$base_uri = $private_app ? _private_app_base_url($shop, $api_key, $oauth_token) : "https://$shop/";

		return function ($method_uri, $query='', $payload='', &$response_headers=array(), $request_headers=array(), $curl_opts=array()) use ($base_uri, $oauth_token, $private_app)
		{
			if (!$private_app) $request_headers['X-Shopify-Access-Token'] = $oauth_token;
			$request_headers['content-type'] = 'application/json; charset=utf-8';
			$http_client = http\client($base_uri, $request_headers);

			try
			{
				$response = $http_client($method_uri, $query, $payload, $response_headers, $request_headers, $curl_opts);
			}
			catch (http\CurlException $e) { throw new CurlException($e->getMessage(), $e->getCode(), $e->getRequest()); }
			catch (http\ResponseException $e) { throw new ApiException($e->getMessage(), $e->getCode(), $e->getRequest(), $e->getResponse()); }
			if (isset($response['errors']))
			{
				list($method, $uri) = explode(' ', $method_uri, 2);
				$uri = rtrim($base_uri).'/'.ltrim($uri, '/');
				$headers = $request_headers;
				$request = compact('method', 'uri', 'query', 'headers', 'payload');
				$response = array('headers'=>$response_headers, 'body'=>$response);
				throw new ApiException($response_headers['http_status_message'].": $uri", $response_headers['http_status_code'], $request, $response);
			}

			return (is_array($response) and !empty($response)) ? array_shift($response) : $response;

		};
	}

		function _private_app_base_url($shop, $api_key, $password)
		{
			return "https://$api_key:$password@$shop/";
		}


	function calls_made($response_headers)
	{
		return _shop_api_call_limit_param(0, $response_headers);
	}


	function call_limit($response_headers)
	{
		return _shop_api_call_limit_param(1, $response_headers);
	}


	function calls_left($response_headers)
	{
		return call_limit($response_headers) - calls_made($response_headers);
	}


		function _shop_api_call_limit_param($index, $response_headers)
		{
			$params = explode('/', $response_headers['http_x_shopify_shop_api_call_limit']);
			return (int) $params[$index];
		}


	class Exception extends http\Exception { }
	class CurlException extends Exception { }
	class ApiException extends Exception
	{
		function __construct($message, $code, $request, $response=array(), Exception $previous=null)
		{
			$response_body_json = isset($response['body']) ? $response['body'] : '';
			$response = json_decode($response_body_json, true);
			$response_error = isset($response['errors']) ? ' '.var_export($response['errors'], true) : '';
			$this->message = $message.$response_error;
			parent::__construct($this->message, $code, $request, $response, $previous);
		}
	}

?>
