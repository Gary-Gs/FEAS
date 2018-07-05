<?php
ini_set('max_execution_time', 600);
class CSRFProtection
{
	/**
	 * The namespace for the session variable and form inputs
	 * @var string
	 */
	private $namespace;
	
	/**
	 * Initializes the session variable name, starts the session if not already so,
	 * and initializes the token
	 * 
	 * @param string $namespace
	 */
	public function __construct($namespace = 'csrf__')
	{
		$this->namespace = $namespace;
		
		//session_start();

		$this->setCSRFToken();
		
		
	}
	
	/**
	 * Return the token from session
	 * 
	 * @return string
	 */
	public function getCSRFToken()
	{
		return $this->readTokenFromSession();
	}
	
	/**
	 * Verifies if form token matches the stored token
	 * 
	 * @param string $userToken
	 * @return boolean
	 */
	public function isTokenValid($userToken)
	{
		if ($userToken!= null ) {
 		   return (hash_equals($this->readTokenFromSession(), $userToken));
		}
		else {
			return false;
		}
	}
	
	/**
	 * Echoes the HTML input field with the token, and namespace as the
	 * name of the field
	 */
	public function echoInputField()
	{
		$token = $this->getCSRFToken();
		
		echo "<input id = \"CSRF_token\" type=\"hidden\" name=\"{$this->namespace}\" value=\"{$token}\" />";
	}
	
	/**
	 * Verifies whether the form token is valid, else dies with error
	 */
	public function cfmRequest()
	{
		if (isset ($_POST[$this->namespace])){
			if (!$this->isTokenValid($_POST[$this->namespace]))
			{
				//die("CSRF validation failed.");
				//echo "<p class='warn'> CSRF validation failed.</p>";
				return "failed CSRF";
			}
		}
		/*else {
			return "failed CSRF";
		}*/
		
		
			
	}
	
	/**
	 * Generates a new token value and stores it in session, or else
	 * does nothing if one already exists in session
	 */
	private function setCSRFToken()
	{
		$storeToken = "";
		if (isset($_SESSION[$this->namespace]))
		{
		
			$storeToken= $_SESSION[$this->namespace];
		}
		
		if ($storeToken === "" || is_null ($storeToken))
		{
			if (PHP_VERSION <7) {
				$csrfToken = bin2hex(openssl_random_pseudo_bytes(64));
			}
			else {
				$csrfToken = bin2hex(random_bytes(64));
			}
			
			
			$this->writeTokenToSession($csrfToken);
		}
		
	}
	
	/**
	 * Reads token from session
	 * @return string
	 */
	private function readTokenFromSession()
	{
		$storeToken = "";
		if (isset($_SESSION[$this->namespace]))
		{
		
			$storeToken= $_SESSION[$this->namespace];
		}
		return $storeToken;		
	}
	
	/**
	 * Writes token to session
	 */
	private function writeTokenToSession($uToken)
	{
		$_SESSION[$this->namespace] = $uToken;
	}
}