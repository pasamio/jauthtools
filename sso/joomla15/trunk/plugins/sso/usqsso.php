<?php
jimport('joomla.plugin.plugin');
jimport('usq.usqsso.usqsso');

class plgSSOUSQSSO extends JPlugin {
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
	function plgSSOUSQSSO(& $subject, $params) {
		parent :: __construct($subject, $params);
	}

	function getSSOPluginType() {
		return 'C';
	}

	function detectRemoteUser() {
		$wsdl = $this->params->get('usq_sso_server'); 
		$forcehttps = false;

$sso = new USQSingleSignOn($wsdl, $forcehttps);
$ssoresult = $sso->authenticate(true);

if ($ssoresult === true) {
	$app = JFactory::getApplication();
	$app->redirect($sso->redirectURL);
} elseif ($ssoresult === false) {
	JError::raiseError(500, "Something went wrong calling the SSO service!");
} else {
	
	if(is_array($ssoresult) && !empty($ssoresult['UserID']))
	{
		$session =& JFactory::getSession();
		$details = new stdClass;
		$details->username = strtolower($ssoresult['UserID']);
		$details->email = $ssoresult['Email'];
		$details->name = $ssoresult['FullName'];
		$details->groups = $ssoresult['Groups'];
		$session->set('UserSourceDetails',$details);
		return strtolower($ssoresult['UserID']);
	}
	return false;
}
		
		
	}

	function getForm() {
		$component = JComponentHelper::getComponent('com_sso', true);
		$base = urldecode(JAuthSSOAuthentication::getBaseURL($this->params->get('prefer_component',true),'usqsso'));
		$result = '<a href="'. $base.'"><img width="180" height="36" alt="UConnect" src="http://www.usq.edu.au/~/media/USQ/HomePage Collages/Buttons/09-469-UConnect-USQ-Home-v2B.ashx?w=180&amp;h=36&amp;as=1" border="0"></a>';
		return $result;
	}
} 
