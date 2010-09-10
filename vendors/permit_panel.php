<?php
class PermitPanel extends DebugPanel {
	var $plugin = 'sanction';
	var $elementName = 'permit_panel';
	var $title = 'Permit';

	function beforeRender(&$controller) {
		$permit_component =& PermitComponent::getInstance();
		$permit =& Permit::getInstance();
		if (empty($permit_component->user)) {
			$permit_component = $permit_component->initializeSessionComponent($permit_component);
			$permit_component->user = $permit_component->session->read("{$permit_component->settings['path']}");
		}
		return array(
			'user' => $permit_component->user,
			'clearances' => $permit->clearances,
			'executed' => $permit_component->executed
		);
	}
}
?>