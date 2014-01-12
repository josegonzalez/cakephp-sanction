<?php
App::uses('SanctionAppModel', 'Sanction.Model');
App::uses('Permit', 'Sanction.Controller/Component');
App::uses('CakeLog', 'Log');

class SanctionPermission extends SanctionAppModel {

 /**
	* Array of rules upon which we will control access
	* Array of rules by which the User's session must be defined by
	*  - If you restrict to a single model and don't use associated data, you can enter just the fieldname to match on in the Auth array
	*  - If you use associated models, you need to specify a Set::extract() path as the fieldname
	* Array of extra parameters, such as where to redirect, the flash message, etc.
	*/
	public function savePermission($route, $rules = array(), $redirect = array()) {
		return $this->save(array(
			'route' => json_encode($route),
			'rules' => json_encode($rules),
			'redirect' => json_encode($redirect),
		));
	}

	public function retrievePermissions() {
		$keys = array('route', 'rules', 'redirect');
		$_permissions = $this->find('all');
		$permissions = array();
		foreach ($_permissions as $i => $permission) {
			foreach ($keys as $key) {
				$permissions[$i][$key] = json_decode(
					$_permissions[$i][$this->alias][$key],
					true
				);
			}
		}
		return $permissions;
	}

	public function retrieveCachedPermissions() {
		$config = $this->_getCacheConfig();

		$permissions = Cache::read('sanction.permissions', $config);
		if (!$permissions) {
			$stream = $this->_getLogConfig();
			CakeLog::notice('Loading permissions from database', array($stream));
			$permissions = $this->retrievePermissions();
			Cache::write('sanction.permissions', $permissions, $config);
		}

		return $permissions;
	}

	public function connectPermissions() {
		$permissions = $this->retrieveCachedPermissions();
		foreach ($permissions as $permission) {
			Permit::access(
				$permission['route'],
				$permission['rules'],
				$permission['redirect']
			);
		}
	}

	protected function _getLogConfig() {
		$config = 'default';
		if (in_array('sanction', CakeLog::configured())) {
			$config = 'sanction';
		}
		return $config;
	}

	protected function _getCacheConfig() {
		$config = 'default';
		if (in_array('sanction', Cache::configured())) {
			$config = 'sanction';
		}
		return $config;
	}

}
