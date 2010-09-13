<?php
class PermitComponent extends Object {

	var $components = array('Session');
	var $controller = null;
	var $session = null;
	var $executed = null;
	var $user = null;

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

	function initialize(&$controller, $config = array()) {
		$permit =& Permit::getInstance();
		if (!include(CONFIGS . DS . 'permit.php')) {
			trigger_error("File containing permissions not found.  It should be located at " . APP_PATH . DS . 'config' . DS . "permit.php", E_USER_ERROR);
		}

		$this->controller = $controller;

		$this->settings = array_merge($this->settings, $config);

		foreach ($permit->routes as $route) {
			if ($this->parse($route['route'])) {
				if ($this->execute($route)) {
					$this->redirect($route);
				}
				break;
			}
		}
	}

	function parse(&$route) {
		$count = count($route);
		if ($count == 0) return false;

		foreach ($route as $key => $value) {
			if (isset($this->controller->params[$key])) {
				$values = (is_array($value)) ?  $value : array($value);
				foreach ($values as $k => $v) {
					if (strtolower($this->controller->params[$key]) == strtolower($v)) {
						$count--;
					}
				}
			}
		}
		return ($count == 0);
	}

	function execute($route) {
		$this->executed = $route;

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
		if (($this->user = $this->Session->read("{$this->settings['path']}")) == false) {
			return true;
		}
		foreach ($route['rules']['auth'] as $field => $value) {
			if (!is_array($value)) $value = (array) $value;
			if (strpos($field,'.')!==false) $field = '/'.str_replace('.','/',$field);
			if ($field[0] == "/") {
				$values = (array) set::extract($field,$this->user);
				foreach ($value as $condition) {
					if (in_array($condition, $values)) $count--;
				}
			} else {
				foreach ($value as $condition) {
					if (isset($this->user[$field]) && $this->user[$field] == $condition) $count--;
				}
			}
		}
		if ($count != 0) return true;
	}

	function redirect($route) {
		if ($route['message'] != null) {
			$message = $route['message'];
			$element = $route['element'];
			$params = $route['params'];
			$this->Session->write("Message.{$route['key']}", compact('message', 'element', 'params'));
		}
		$this->controller->redirect($route['redirect']);
	}

}

class Permit extends Object{

	var $redirect = '/';
	var $clearances = array();

	function access($route, $rules = array(), $redirect = array()) {
		$self =& Permit::getInstance();
		if (empty($rules)) return $self->clearances;

		$redirect = array_merge(array('redirect' => $self->redirect,
									'message' => __('Access denied', true),
									'element' => 'default',
									'params' => array(),
									'key' => 'flash'),
									$redirect);

		$newRoute = array(
			'route' => $route,
			'rules' => $rules,
			'redirect' => $redirect['redirect'],
			'message' => $redirect['message'],
			'element' => $redirect['element'],
			'params' => $redirect['params'],
			'key' => $redirect['key'],
		);

		$self->clearances[] = $newRoute;

		return $self->clearances;
	}

/**
 * Gets a reference to the Permit object instance
 *
 * @return Permit Instance of the Permit.
 * @access public
 * @static
 */
	function &getInstance() {
		static $instance = array();

		if (!$instance) $instance[0] =& new Permit();
		return $instance[0];
	}
}
?>