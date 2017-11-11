<?php
	
	class View_PHP_Helper extends View_PHP {
		
		public static function isValidReferer($referer, $includeIndex = false) {
			if (empty($referer)) {
				return false;
			}
			
			if ($includeIndex and $referer == 'index') {
				return true;
			}
			
			return in_array($referer, array('recording', 'cutting', 'encoding', 'releasing'));
		}
		
	}

?>