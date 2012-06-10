<?php
class PermitPanel extends DebugPanel {

	public $plugin = 'sanction';

	public function beforeRender(Controller $controller) {
		if (empty(Permit::$user)) {
			Permit::$user = $controller->Toolbar->Session->read(Permit::$settings['path']);
		}
		return array(
			'user' => Permit::$user,
			'routes' => Permit::$routes,
			'executed' => Permit::$executed
		);
	}

}