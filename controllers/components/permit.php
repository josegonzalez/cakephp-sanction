<?php
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
		'check' => 'id'
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
 * @access private
 */
	var $_user = null;

	function initialize(&$controller, $config = array()) {
		if (!include(CONFIGS . 'permit.php')) {
			trigger_error("File containing permissions not found.  It should be located at " . APP_PATH . DS . 'config' . DS . "permit.php", E_USER_ERROR);
		}

		$this->settings = array_merge($this->settings, $config);
		$Permit =& PermitComponent::getInstance();
		$this->routes = $Permit->routes;
	}

	function startup(&$controller) {
		foreach ($this->routes as $route) {
			if ($this->parse($controller, $route['route'])) {
				if ($this->execute($route)) {
					if (isset($controller->params['url']['url'])) {
						$url = $controller->params['url']['url'];
					}

					$url = Router::normalize($url);
					if (!empty($controller->params['url']) && count($controller->params['url']) >= 2) {
						$query = $controller->params['url'];
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

	function parse(&$controller, $route) {
		$count = count($route);
		if ($count == 0) return false;
		foreach ($route as $key => $value) {
			if (isset($controller->params[$key])) {
				$values = (array) $value;
				$check = (array) $controller->params[$key];
				foreach ($check as $k => $_check) {
					$check[$k] = strtolower($_check);
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

	function execute($route) {
		$Permit =& PermitComponent::getInstance();
		$Permit->executed = $this->executed = $route;

		if (empty($route['rules'])) return false;

		if (isset($route['rules']['deny'])) {
			if ($route['rules']['deny'] == true) return true;
			return false;
		}

		if (!isset($route['rules']['auth'])) return false;

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

		$count = count($route['rules']['auth']);
		if ($count == 0) return false;
		if (($this->_user = $this->Session->read("{$this->settings['path']}")) == false) {
			return true;
		}
		foreach ($route['rules']['auth'] as $field => $value) {
			if (!is_array($value)) $value = (array) $value;
			if (strpos($field,'.')!==false) $field = '/'.str_replace('.','/',$field);
			if ($field[0] == "/") {
				$values = (array) set::extract($field, $this->_user);
				foreach ($value as $condition) {
					if (in_array($condition, $values)) $count--;
				}
			} else {
				foreach ($value as $condition) {
					if (isset($this->_user[$field]) && $this->_user[$field] == $condition) $count--;
				}
			}
		}
		if ($count != 0) return true;
	}

	function redirect(&$controller, $route) {
		if ($route['message'] != null) {
			$message = $route['message'];
			$element = $route['element'];
			$params = $route['params'];
			$this->Session->write("Message.{$route['key']}", compact('message', 'element', 'params'));
		}

		$controller->redirect($route['redirect']);
	}

	function access($route, $rules = array(), $redirect = array()) {
		if (empty($rules)) return $this->routes;

		$redirect = array_merge(array(
				'redirect' => '/',
				'message' => __('Access denied', true),
				'element' => 'default',
				'params' => array(),
				'key' => 'flash'
			),
			$redirect
		);

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
		return $this->routes;
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

class Permit extends Object{

	function access($route, $rules = array(), $redirect = array()) {
		$Permit =& PermitComponent::getInstance();
		return $Permit->access($route, $rules, $redirect);
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
