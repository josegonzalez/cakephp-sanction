<?php
class PermitPanel extends DebugPanel {
	var $plugin = 'sanction';
	var $elementName = 'permit_panel';
	var $title = 'Permit';

	function beforeRender(&$controller) {
		$Permit =& PermitComponent::getInstance();
		if (empty($Permit->_user)) {
			$Permit->_user = $Permit->Session->read("{$Permit->settings['path']}");
		}
		return array(
			'user' => $Permit->_user,
			'routes' => $Permit->routes,
			'executed' => $Permit->executed
		);
	}
}
?>