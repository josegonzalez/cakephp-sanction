<?php

App::uses('SanctionAppController', 'Sanction.Controller');

class SanctionPermissionsController extends SanctionAppController {

	public function autocomplete() {
		$this->viewClass = 'JsonView';

		$type = $this->request->query('type');
		if (empty($type)) {
			$type = 'controller';
		}

		if (!in_array($type, array('plugin', 'controller', 'action'))) {
			return $this->set(array(
				'_serialize' => array('message', 'status'),
				'message' => sprintf('Invalid type \'%s\'', $type),
				'status' => 400,
			));
		}

		return $this->set(array(
			'status' => 200,
			Inflector::pluralize($type) => $this->_fetch($type),
		));
	}

	protected function _fetch($type) {
		$plugin = $this->request->query('plugin');
		$controller = $this->request->query('controller');

		if ($type === 'plugin') {
			return array(CakePlugin::loaded());
		} elseif ($type === 'controller') {
			if ($plugin) {
				// Scope request by plugin
			} else {
				// Get app controllers
			}
		} elseif ($type === 'action') {
			if ($plugin && $controller) {
				// Scope request by plugin and controller
			} elseif ($controller) {
				// Scope by app controller
			} else {
				return array();
			}
		}

		return array();
	}

}
