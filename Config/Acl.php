<?php
	
	class AclConfig extends ConfigSettings {
		
		private static $_ranges = array(
			'v4' => array(
				array('141.24.40.0', '26'),
				array('141.24.41.0', '24'),
				array('141.24.42.0', '23'),
				array('141.24.44.0', '22'),
				array('141.24.48.0', '21'),
				array('10.28.0.0', '23'),
				array('127.0.0.1', '32')
			),
			'v6' => array(
				array('2001:638:904:ffc0::', '60'),
				array('2001:638:904:ffd2::', '63'),
				array('2001:638:904:ffd4::', '62'),
				array('2001:638:904:ffd8::', '61'),
				array('2001:638:904:ffe0::', '59')
			)
		);
		
		public function __construct() {
			
			// Roles
			$this->Acl->addRole('restricteduser');
			$this->Acl->addRole('user');
			$this->Acl->addRole('owner');
			
			$this->Acl->addRole('worker');
			
			$this->Acl->addRole('superuser', 'user');
			$this->Acl->addRole('admin', 'user');
			
			// Everybody
			$this->Acl->allow(null, array('user'), array('login'));
			
			if ($this->_IPIsInAllowedRange($_SERVER['REMOTE_ADDR'])) {
				$this->Acl->allow(null, array('projects'), array('index'));
				$this->Acl->allow(null, array('tickets'), array('feed', 'index', 'view'));
			} else {
				$this->Acl->allow('user', array('projects'), array('index'));
				$this->Acl->allow('user', array('tickets'), array('feed', 'index', 'view'));
			}
			
			// Script
			$this->Acl->allow('worker', array('XMLRPC_Handler'));
			$this->Acl->allow('worker', array('XMLRPC_ProjectHandler'));
			$this->Acl->allow('worker', array('export'), array('wiki', 'podcast'));
			
			// Restricted User
			$this->Acl->allow('restricteduser', array('user'), array('login_complete', 'logout', 'settings', 'changeback', 'act_as_substitute'));
			$this->Acl->allow('restricteduser', array('tickets'), array('feed', 'comment', 'cut', 'uncut', 'check', 'uncheck', 'handle', 'unhandle', 'log'));
			
			$this->Acl->allow('restricteduser', array('services'), array('workers'));
			
			// User
			$this->Acl->allow('user', array('user'), array('login_complete', 'logout', 'settings', 'changeback', 'act_as_substitute'));
			
            $this->Acl->allow('user', array('tickets'), array('dashboard', 'comment', 'create', 'cut', 'uncut', 'check', 'uncheck', 'fix', 'unfix', 'handle', 'unhandle', 'reset', 'log', 'edit'));
            $this->Acl->allow('owner', array('tickets'), array('delete_comment'));
            $this->Acl->allow('user', array('export'), array('index', 'wiki', 'podcast', 'feedback'));
			
            $this->Acl->allow('user', array('services'), array('workers'));
			
			// Superuser
			$this->Acl->allow('superuser', array('encodingprofiles'), array('index', 'create', 'edit'));
			
			// Admin
			$this->Acl->allow('admin');
			
			$this->Acl->deny('admin', array('user'), array('act_as_substitute'));
			
		}
		
		private function _IPIsInAllowedRange($ip) {
			// TODO: better list v4 subnets with v6 syntax?
			if (substr($ip, 0, 7) == '::ffff:') {
				$ip = substr($ip, 7);
			}
			
			if (filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)) {
				$protocol = 'v4';
			} elseif (filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6)) {
				$protocol = 'v6';
			} else {
				return false;
			}
			
			foreach (self::$_ranges[$protocol] as $range) {
				if ($this->_ip2pton($ip, $range[1]) == $this->_ip2pton($range[0], $range[1])) {
					return true;
				}
			}
			
			return false;
		}
		
		// idea from http://www.php.net/manual/de/function.inet-pton.php#93501
		// and http://www.dokuwiki.org/plugin:ipgroup
		private function _ip2pton($ip, $cidr) {
			$addr = inet_pton($ip);
			
			// Maximum netmask length = same as packed address
			$lenght = 8 * strlen($addr);
			
			if ($cidr > $lenght) {
				$cidr = $lenght;
			}
			
			// Create a hex expression of the subnet mask
			$mask  = str_repeat('f', $cidr >> 2);
			
			switch($cidr & 3) {
				case 3: $mask .= 'e'; break;
				case 2: $mask .= 'c'; break;
				case 1: $mask .= '8'; break;
			}
			
			$mask = str_pad($mask, $lenght >> 2, '0');

			// Packed representation of netmask
			$mask = pack('H*', $mask);

			// Return logical and of addr and mask
			return ($addr & $mask);
		}
		
	}
	
?>
