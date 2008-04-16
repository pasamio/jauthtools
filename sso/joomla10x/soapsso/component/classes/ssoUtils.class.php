<?php
/**
* @version 	$Id: ssoUtils.class.php,v V1.1 1128 bytes, 2007-06-07 12:43:34 cero Exp $
* @package 	SSO
* @author	Tomo Cerovsek <Tomo.Cerovsek.fgg.uni-lj.si> 
* @author	Damjan Murn <Damjan.Murn.uni-lj.si>
* @copyright 	(C) 2007 SSO Team, UL FGG
* @license 	http://www.gnu.org/copyleft/gpl.html GNU/GPL
* SSO was initiated during the EU CONNIE project
*/

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

/**
 * SSO Utilities Class
 * @package SSO
 */
class ssoUtils {
    public function cutString($string, $maxLength) {
        if (strlen($string) > $maxLength) {
            return substr($string, 0, $maxLength-3) . '...';
        } else {
            return $string;
        }
    }

	function generateHash($str) {
		$checksum = abs(crc32($str) % 14776336);
		$hash = '';
		for ($i=0; $i<4; $i++) {
			$n = $checksum % 62;
			$checksum = (int)($checksum/62);
			if ($n < 26) {
				$c = chr(ord('A') + $n);
			} else if ($n < 52) {
				$c = chr(ord('a') + $n - 26);
			} else {
				$c = $n - 52;
			}
			$hash .= $c;
		}
		return $hash;
	}
}

?>