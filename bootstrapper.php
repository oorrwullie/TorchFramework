<?php
include_once("factory.php");
include_once("config.php");

class bootstrapper {
	private $_path;
	private $_params;

	function __construct($path, $params) {
		$this->_path = $path;
		$this->_params = $params;
	}

	function handle_request($memcache, $key) {
		GLOBAL $config;

		$pathParts = array_values(array_filter(split('/', $this->_path)));
		$type = (isset($pathParts[CONTROLLER_NAME_LOCATION])) ? $pathParts[CONTROLLER_NAME_LOCATION] : null;

		if ($type === 'user' && $_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['type'] === 'activate') {
		} else if (!($type === 'user' && $_SERVER['REQUEST_METHOD'] === 'POST')
			and !(in_array($type, $config['no_auth']))){

			$validate_message = bootstrapper::post_validate_request($memcache, $key);
			unset($memcache); // No idea why we have to do this.

			if (!empty($validate_message)) {
				header("HTTP/1.0 401");
				die();
			}
		}

		$memcache = new Memcache;
		foreach ($config['memcache_servers'] as $server) {
			$memcache->addServer($server,$config['memcache_port']);
		}

		$controller = controller_factory::get_controller($memcache, $key, $type);

		if (isset($controller))
			return $controller->handle_request($this->_params); // Should return an array
	}

	static function validate_request($path) {
        $message = '';
		if (!isset($path)) $message .= 'Path is empty. ';
		$pathParts = array_values(array_filter(split('/', $path)));

		return $message;
	}

	static function post_validate_request($memcache, $key) {
		$user = $memcache->get($key."_user");

		if (!$user) return 'Please Log In';
	}
}
?>
