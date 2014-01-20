<?php
class ODKL{
	
	private $app_id;
	private $public_key;
	private $secret;
	
	public function __construct($app_id, $public_key, $secret){
		 $this->app_id=$app_id;
		 $this->public_key=$public_key;
 		 $this->secret=$secret;
	}
	
	public function app_id(){
		return $this->app_id;
	}
	
	public function public_key(){
		return $this->public_key;
	}
	
	public function calculateAuthKey($viewer_id){
		return md5($this->app_id.'_'.$viewer_id.'_'.$this->secret);
	}	 
	
	public function get_token($code, $redirect_uri){
	
		$curl = curl_init('http://api.odnoklassniki.ru/oauth/token.do');
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, 'code=' . $code . '&redirect_uri=' . urlencode($redirect_uri) . '&grant_type=authorization_code&client_id=' . $this->app_id . '&client_secret=' . $this->secret);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$s = curl_exec($curl);
		curl_close($curl);
		$auth = json_decode($s, true);
		$curl = curl_init('http://api.odnoklassniki.ru/fb.do?access_token=' . $auth['access_token'] . '&application_key=' . $this->public_key . '&method=users.getCurrentUser&sig=' . md5('application_key=' . $this->public_key . 'method=users.getCurrentUser' . md5($auth['access_token'] . $this->secret)));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$s = curl_exec($curl);
		curl_close($curl);
		
		return array('user'=>$s, 'access_token'=>$auth['access_token']);
	}
	 
	public function api($method, $params){
		$params['application_key'] = $this->public_key;
		$params['method'] = $method;
		$params['format'] = 'json';
		$params['sig']=$this->sign($params);
		
		$response=file_get_contents('http://api.odnoklassniki.ru/fb.do?'.http_build_query($params));
		if(!$response=json_decode($response)){
			throw new ODKLException('ODKL API error');
		}
		return $response;
	}	 
	
	public function promo_api($method, $params){
		$params['appId'] = $this->app_id;
		$params['format'] = 'json';
		$params['sig']=$this->sign($params);
		
		$response=file_get_contents('http://sp.odnoklassniki.ru/projects/common/'.$method.'?'.http_build_query($params));
		if(!$response=json_decode($response)){
			throw new ODKLException('ODKL API error');
		}
		return $response;
	}
	
	public function sign($params){
		$sign='';
		ksort($params);	
		foreach($params as $key=>$value){
			if('sig' == $key || 'resig' == $key){
				continue;
			}
			$sign.=$key.'='.$value;
		}
		
		$sign.=$this->secret;
		return md5($sign);
	}
	
}
