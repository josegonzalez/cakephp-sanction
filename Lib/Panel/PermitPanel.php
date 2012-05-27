<?php
class PermitPanel extends DebugPanel {
	var $plugin = 'sanction';
	var $elementName = 'permit_panel';
	var $title = 'Permit';

	function beforeRender(Controller &$controller) {
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