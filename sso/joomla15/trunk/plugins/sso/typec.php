<?php
jimport('joomla.plugin.plugin');

class plgSSOTypeC extends JPlugin {
	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @since 1.5
	 */
	function plgSSOTypeC(& $subject, $params) {
		parent :: __construct($subject, $params);
	}

	function getSSOPluginType() {
		return 'C';
	}

	function detectRemoteUser() {
		$remote_user = JRequest::getVar('remote_username','');
		if($remote_user) {
			return $remote_user;
		} else {
			return false;
		}
	}

	function getForm() {
		$component = JComponentHelper::getComponent('com_ssomanager', true);
		$result = '<form method="post" action="'. JURI::base() .'">'
			. 'Requested Username: '
			. '<input type="text" name="remote_username" value="" />'
			. '<input type="submit" value="Login" />';
		if($component->enabled) {
			$result .= '<input type="hidden" name="option" value="com_sso">'
			. '<input type="hidden" name="task" value="delegate">'
			. '<input type="hidden" name="plugin" value="typec">';
		}
		$result .= '</form>';
		return $result;
	}
} 
