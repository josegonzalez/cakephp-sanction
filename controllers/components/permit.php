<?php
/**
 * Permit component class
 *
 * Manages user access to a given route
 *
 * @package       sanction
 * @subpackage    sanction.controller.components
 */
class PermitComponent extends Object {

/**
 * Other components utilized by PermitComponent
 *
 * @var array
 * @access public
 */
	var $components = array('Session');

/**
 * Parameter data from Controller::$params
 *
 * @var array
 * @access public
 */
	var $settings = array(
		'path' => 'Auth.User',
		'check' => 'id',
		'isTest' => false,
	);

/**
 * Array of routes connected with PermitComponent::access()
 *
 * @var array
 * @access public
 */
	var $routes = array();

/**
 * Array containing executed route
 *
 * @var array
 * @access public
 */
	var $executed = null;

/**
 * Maintains current logged in user.
 *
 * @var boolean
 * @access protected
 */
	var $_user = null;

/**
 * Current request parameters
 *
 * @var array
 */
	var $_requestParams = array();

/**
 * Initializes SanctionComponent for use in the controller
 *
 * @param object $controller A reference to the instantiating controller object
 * @return void
 * @access public
 */
	function initialize(&$controller, $config = array()) {
		if (!include(CONFIGS . 'permit.php')) {
			trigger_error("File containing permissions not found.  It should be located at " . APP_PATH . DS . 'config' . DS . "permit.php", E_USER_ERROR);
		}

		$this->settings = array_merge($this->settings, $config);
		if ($this->settings['isTest']) return;

		$Permit =& PermitComponent::getInstance();
		$this->routes = $Permit->routes;

		$this->_requestParams = $controller->params;
		foreach (array('controller', 'plugin') as $inflected) {
			if (isset($this->_requestParams[$inflected])) {
				$this->_requestParams[$inflected] = strtolower(Inflector::underscore($this->_requestParams[$inflected]));
			}
		}
	}


/**
 * Main execution method.  Handles redirecting of invalid users, and saving
 * of request url as Sanction.referer
 *
 * @param object $controller A reference to the instantiating controller object
 * @return boolean
 * @access public
 */
	function startup(&$controller) {
		if ($this->settings['isTest']) return;

		foreach ($this->routes as $route) {
			if ($this->_parse($route['route'])) {
				if ($this->_execute($route)) {
					if (isset($this->_requestParams['url']['url'])) {
						$url = $this->_requestParams['url']['url'];
					} else {
						$url = $this->_requestParams;
					}

					$url = Router::normalize($url);
					if (!empty($this->_requestParams['url']) && count($this->_requestParams['url']) >= 2) {
						$query = $this->_requestParams['url'];
						unset($query['url'], $query['ext']);
						$url .= Router::queryString($query, array());
					}
					$this->Session->write('Sanction.referer', $url);
					$this->redirect($controller, $route);
				}
				break;
			}
		}
	}

/**
 * Parses a given Permit route to see if it matches the current request
 *
 * @param object $controller A reference to the instantiating controller object
 * @param array $route A Permit Route
 * @return boolean true if current request matches Permit route, false otherwise
 * @access protected
 */
	function _parse($route) {
		$count = count($route);
		if ($count == 0) {
			return false;
		}

		foreach ($route as $key => $value) {
			if (isset($this->_requestParams[$key])) {
				$values = (array) $value;
				$check = (array) $this->_requestParams[$key];

				if (!count($values) && in_array(null, $check)) {
					$count--;
					continue;
				}

				if (in_array($key, array('controller', 'plugin'))) {
					foreach ($check as $k => $_check) {
						$check[$k] = Inflector::underscore(strtolower($_check));
					}
				} else {
					foreach ($check as $k => $_check) {
						$check[$k] = strtolower($_check);
					}
				}

				foreach ($values as $k => $v) {
					if (in_array(strtolower($v), $check)) {
						$count--;
					}
				}
			}
		}
		return ($count == 0);
	}

/**
 * Determines whether the given user is authorized to perform an action.  The result of
 * a failed request depends upon the options for the route 
 *
 * @param array $route A Permit Route
 * @return boolean True if redirect should be executed, false otherwise
 */
	function _execute($route) {
		$Permit =& PermitComponent::getInstance();
		$Permit->executed = $this->executed = $route;

		if (empty($route['rules'])) {
			return false;
		}

		if (isset($route['rules']['deny'])) {
			return $route['rules']['deny'] == true;
		}

		if (!isset($route['rules']['auth'])) {
			return false;
		}

		if (is_bool($route['rules']['auth'])) {
			$is_authed = $this->Session->read("{$this->settings['path']}.{$this->settings['check']}");

			if ($route['rules']['auth'] == true && !$is_authed) {
				return true;
			}
			if ($route['rules']['auth'] == false && $is_authed) {
				return true;
			}
			return false;
		}

		$count = count(set::flatten($route['rules']['auth']));
		if ($count == 0) {
			return false;
		}

		$this->_user = $this->Session->read("{$this->settings['path']}");
		if ($this->_user == false) {
			return true;
		}

		foreach ($route['rules']['auth'] as $field => $value) {
			if (!is_array($value)) {
				$value = (array) $value;
			}

			if (strpos($field, '.') !== false) {
				$field = '/'. str_replace('.', '/', $field);
			}

			if ($field[0] == "/") {
				$values = (array) Set::extract($field, $this->_user);
				foreach ($value as $condition) {
					if (in_array($condition, $values)) {
						$count--;
					}
				}
			} else {
				foreach ($value as $condition) {
					if (isset($this->_user[$field]) && $this->_user[$field] == $condition) {
						$count--;
					}
				}
			}
		}

		return $count != 0;
	}

/**
 * Performs a redirect based upon a given route
 *
 * @param object $controller A reference to the instantiating controller object
 * @param array $route A Permit Route
 * @return void
 */
	function redirect(&$controller, $route) {
		if ($route['message'] != null) {
			$message = $route['message'];
			$element = $route['element'];
			$params = $route['params'];
			$this->Session->write("Message.{$route['key']}", compact('message', 'element', 'params'));
		}

		$controller->redirect($route['redirect']);
	}

/**
 * Connects a route to a given ruleset
 *
 * @param array $route array describing a route
 * @param array $rules array of rules regarding the route
 * @param array $redirect Array containing the url to redirect to on route fail
 * @return array Array of connected routes
 */
	function access($route, $rules = array(), $redirect = array()) {
		$redirect = array_merge(array(
				'redirect' => '/',
				'message' => __('Access denied', true),
				'element' => 'default',
				'params' => array(),
				'key' => 'flash'
			),
			$redirect
		);

		//convert underscored names to camelCase as additional way of accessing a controller
		foreach (array('controller', 'plugin') as $inflected) {
			if (isset($route[$inflected])) {
				if (is_array($route[$inflected])) {
					foreach ($route[$inflected] as $i => $controllerName) {
						$route[$inflected][$i] = Inflector::underscore($controllerName);
					}
				} else {
					$route[$inflected] = Inflector::underscore($route[$inflected]);
				}
			}
		}

		foreach ($route as $k => $value) {
			if (is_array($value)) {
				foreach ($value as $i => $_value) {
					$route[$k][$i] = strtolower($_value);
				}
			} else {
				$route[$k] = strtolower($value);
			}
		}

		$newRoute = array(
			'route' => $route,
			'rules' => $rules,
			'redirect' => $redirect['redirect'],
			'message' => $redirect['message'],
			'element' => $redirect['element'],
			'params' => $redirect['params'],
			'key' => $redirect['key'],
		);

		$this->routes[] = $newRoute;
	}

/**
 * Returns the referring URL for this request.
 *
 * @param mixed $default Default URL to use if Session cannot be read
 * @return string Referring URL
 * @access public
 */
	function referer($referer = null) {
		if ($this->Session->check('Sanction.referer')) {
			$referer = $this->Session->read('Sanction.referer');
			$this->Session->delete('Sanction.referer');
		}

		if ($referer === null) {
			return false;
		}

		return Router::normalize($referer);
	}

/**
 * Gets a reference to the PermitComponent object instance
 *
 * @return PermitComponent Instance of the PermitComponent.
 * @access public
 * @static
 */
	function &getInstance() {
		static $instance = array();

		if (!$instance) {
			$instance[0] =& new PermitComponent();
		}
		return $instance[0];
	}

}

/**
 * Permit class
 *
 * Connects routes for a given request
 *
 * @package       sanction
 * @subpackage    sanction.controller.components
 */
class Permit extends Object {

/**
 * Connects a route to a given ruleset
 *
 * @param array $route array describing a route
 * @param array $rules array of rules regarding the route
 * @param array $redirect Array containing the url to redirect to on route fail
 * @return array Array of connected routes
 */
	function access($route, $rules = array(), $redirect = array()) {
		$Permit =& PermitComponent::getInstance();
		$Permit->access($route, $rules, $redirect);
	}

/**
 * Gets a reference to the Permit object instance
 *
 * @return object Instance of the Permit.
 * @access public
 * @static
 */
	function &getInstance() {
		static $instance = array();

		if (!$instance) {
			$instance[0] =& new Permit();
		}
		return $instance[0];
	}

}
