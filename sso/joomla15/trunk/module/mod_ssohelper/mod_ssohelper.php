<?php
/**
 * SSO Helper
 * 
 * Logs the user in automatically 
 * 
 * PHP4/5
 *  
 * Created on Oct 8, 2008
 * 
 * @package JAuthTools
 * @author Sam Moffatt <sam.moffatt@toowoombarc.qld.gov.au>
 * @author Toowoomba Regional Council Information Management Branch
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2008 Toowoomba Regional Council/Sam Moffatt 
 * @version SVN: $Id$
 * @see http://joomlacode.org/gf/project/jauthtools   JoomlaCode Project: Joomla! Authentication Tools    
 */
 
jimport('jauthtools.sso');
jimport('jauthtools.usersource');

$sso = new JAuthSSOAuthentication();
$sso->doSSOAuth($params->getValue('autocreate',false));