<?php
class OAuthRequest {

	public $url;
	public $method;
	public $data;
	public $consumerToken;
	public $consumerSecret;
	public $accessToken;
	public $accessSecret;
	private $signatureBase;
	private $authArray;
	private $signature;
	public $result;
	private $rawDataString;

	public static function make($url,$method,$consumerToken,$consumerSecret,$accessToken,$accessSecret,$data=array()) {
		$request = new Static;
		$request->url = $url;
		$request->method = $method;
		$request->consumerToken = $consumerToken;
		$request->consumerSecret = $consumerSecret;
		$request->accessToken = $accessToken;
		$request->accessSecret = $accessSecret;
		$request->data = $data;

		return $request->buildAuthArray();
	}

	private function buildAuthArray() {
		$authArray = array(
			"oauth_version"=>"1.0",			
			"oauth_nonce"=>'fMC6Jo92LCq6SyVdMb7A2E0E0heyC41u',
			"oauth_timestamp"=>time()
		);
		$str = "";
		foreach($authArray as $key=>$val) {
			$str .= $key."=".$val."&";
		}
		if($this->consumerToken !== null) {
			$authArray['oauth_consumer_key'] = $this->consumerToken;
		}
		if($this->accessToken !== null) {
			$authArray['oauth_token'] = $this->accessToken;
		}
		$rawDataString = '';
		foreach($this->data as $key=>$val) {
			$authArray[$key] = $val;
			$rawDataString .= rawurlencode($key).'='.rawurlencode($val).'&';
		}
		$rawDataString = substr($rawDataString, 0,-1);
		$this->rawDataString = $rawDataString;
		$authArray["oauth_signature_method"] = "HMAC-SHA1";
		$this->authArray = $authArray;

		return $this->buildSignatureBase();
	}

	private function buildSignatureBase() {
		$authArray = $this->authArray;
		ksort($authArray);

		$paramString = "";
		foreach($authArray as $key=>$val) {
			$paramString .= rawurlencode($key)."=".rawurlencode($val)."&";
		}
		$paramString = substr($paramString,0,-1);
		$this->signatureBase = strtoupper($this->method)."&".rawurlencode(preg_replace("/\?.*$/","",$this->url))."&".rawurlencode($paramString);

		return $this->buildSignature();

	}
	private function buildSignature() {

		$signatureParts = array(
			rawurlencode($this->consumerSecret),
			($this->accessSecret) ? rawurlencode($this->accessSecret) : ''
		);
		$signatureKey = implode('&',$signatureParts);

		$this->signature = base64_encode(hash_hmac("SHA1", $this->signatureBase, $signatureKey,true));

		$this->authArray["oauth_signature"] = $this->signature;
		return $this;
	}

	public function headerToParamString() {
		$str = "";
		foreach($this->authArray as $key=>$val) {
			if(!preg_match('/^oauth/',$key)) continue;
			$str .= $key."=\"".rawurlencode($val)."\",";
		}
		$str = substr($str,0,-1);
		return $str;
	}

	public function postAuthArray() {
		$this->data = array_merge($this->data,$this->authArray);
		return $this;
	}

	public function appendAuthArrayAsGET() {
		$authArray = $this->authArray;
		$this->url .= '?';
		foreach($this->authArray as $key=>$val) {
			$this->url .= rawurlencode($key).'='.rawurlencode($val).'&';
		}
		$this->url = substr($this->url, 0,-1);
		return $this;
	}

	public function dataToUrlString() {
		$str = '';
		foreach($this->data as $key=>$val) {
			$str .= $key.'='.rawurlencode($val).'&';
		}
		$str = substr($str,0,-1);
		return $str;
	}

	public function sendRequest() {
		$header = $this->headerToParamString();
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL=>$this->url,
			CURLOPT_HTTPHEADER=>array(
				"Authorization: OAuth ".$header
			),
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_RETURNTRANSFER=>1,
			CURLOPT_CUSTOMREQUEST=>$this->method
		));
		if($this->method == "POST") {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $this->data);
		} elseif($this->method === 'PUT') {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $this->rawDataString);
		}
		$result = curl_exec($curl);
		$this->result = $result;
		return $result;
	}

	public static function getPublicKeys() {
		$headers = getallheaders();
		if(!array_key_exists('Authorization',$headers)) {
			return false;
		}
		$authHeaders = $headers['Authorization'];
		$authHeaders = explode(',',$authHeaders);
		$consumerKey = null;
		$token = null;
		foreach($authHeaders as $head) {
			list($key,$value) = explode('=',$head);
			preg_match("/\"(.*)\"/",$value,$matches);
			$value = $matches[1];			
			if($key == 'oauth_consumer_key') {
				$consumerKey = $value;
			} elseif ($key == 'oauth_token') {
				$token = $value;
			}
			if(!is_null($consumerKey) && !is_null($token)) {
				break;
			}
		}
		return array(
			'consumerKey'=>$consumerKey,
			'token'=>$token
		);
	}

	public static function auth($consumerKey,$consumerSecret,$token,$tokenSecret) {
		$headers = getallheaders();
		if(!array_key_exists('Authorization',$headers)) {
			return false;
		}
		$authHeaders = $headers['Authorization'];
		$authHeaders = preg_replace('/^OAuth /','',$authHeaders);
		$authHeaders = explode(',',$authHeaders);
		$authArray = array();
		foreach($authHeaders as $head) {
			list($key,$value) = explode('=',$head);
			preg_match("/\"(.*)\"/",$value,$matches);
			$value = $matches[1];
			if(substr($key, 0,6) == 'oauth_') {
				$authArray[$key] = $value;
			}
			
			if($key == 'oauth_consumer_key') {
				if($value !== $consumerKey) {
					return false;
				}
			}
			if($key == 'oauth_token') {
				if($value !== $token) {
					return false;
				}
			}
		}
		parse_str(file_get_contents('php://input'),$post_vars);
		$data = array_merge($post_vars,$_REQUEST);
		foreach($data as $key=>$val) {
			$authArray[$key] = $val;
		}
		$signature = $authArray['oauth_signature'];
		unset($authArray['oauth_signature']);
		ksort($authArray);

		$paramString = "";
		foreach($authArray as $key=>$val) {
			$paramString .= rawurlencode($key)."=".rawurlencode($val)."&";
		}
		$paramString = substr($paramString,0,-1);
		$method = $_SERVER['REQUEST_METHOD'];
		$signatureBase = $method."&".rawurlencode(preg_replace("/\?.*$/","",Url::current()))."&".rawurlencode($paramString);
		
		$signatureKey = rawurlencode($consumerSecret)."&".rawurlencode($tokenSecret);

		$mySignature = rawurlencode(base64_encode(hash_hmac("SHA1", $signatureBase, $signatureKey,true)));
		if($mySignature === $signature) {
			return true;
		}
		return false;
	}
}