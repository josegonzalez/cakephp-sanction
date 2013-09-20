<?php
class AccessComponent extends Component {

	public $settings = array();

	public $defaults = array(
		'admin_required' => array(),
		'auth_denied' => array(),
		'auth_required' => array(),
		'denied' => array(),
		'callback' => 'initialize'
	);

	public function initialize(&$controller, $settings) {
		$this->settings = array_merge($this->defaults, $settings);
		if ($this->settings['callback'] = 'initialize') {
			$this->_isAuthorized($controller);
		}
	}

	public function startup(&$controller) {
		if ($this->settings['callback'] = 'startup') {
			$this->_isAuthorized($controller);
		}
	}

	protected function _isAuthorized(&$controller) {
		$action = strtolower($controller->params['action']);

		$authRequiredActions = array_map('strtolower', $this->settings['auth_required']);
		$authRequired = ($authRequiredActions == array('*') || in_array($action, $authRequiredActions));
		if ($authRequired && Authsome::get('guest')) {
			$controller->Session->setFlash('Please login to access this resource');
			$controller->redirect(array('controller' => 'users', 'action' => 'login'));
		}

		$authDeniedActions = array_map('strtolower', $this->settings['auth_denied']);
		$authDenied = ($authDeniedActions == array('*') || in_array($action, $authDeniedActions));
		if ($authDenied && !Authsome::get('guest')) {
			$controller->Session->setFlash('You are already logged in');
			$controller->redirect(array('controller' => 'users', 'action' => 'dashboard'));
		}

		$adminRequiredActions = array_map('strtolower', $this->settings['admin_required']);
		$adminRequired = ($adminRequiredActions == array('*') || in_array($action, $adminRequiredActions));
		if ($adminRequired && (Authsome::get('group') != 'administrator')) {
			$controller->Session->setFlash('You must be an administrator to access this resource');
			$controller->redirect(array('controller' => 'users', 'action' => 'dashboard'));
		}

		$deniedActions = array_map('strtolower', $this->settings['denied']);
		$denied = ($deniedActions == array('*') || in_array($action, $deniedActions));
		if ($denied) {
			$controller->Session->setFlash('You do not have access to this resource');
			$controller->redirect(array('controller' => 'users', 'action' => 'index'));
		}
	}

}
