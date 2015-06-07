<?php
$ldaphost = '192.168.0.0';
$ldapconn = ldap_connect($ldaphost) or die("Could not connect to {$ldaphost}");
$ldappass = 'password';  // associated password
$ldaprdn  = 'cn=admin,dc=lan,dc=home';

$user			= 'testuser';
$password = 'testing';

ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

if($ldapconn) {
	$ldapbind = ldap_bind($ldapconn, $ldaprdn, $ldappass); 

	if ($ldapbind) {
		echo "LDAP bind successful...".PHP_EOL;
		$dn 				= "dc=lan,dc=home";
		//$filter		= "(|(cn=developers*))";
		$filter			= "(uid=$user)";
		//$filter			= "uid=t*";
		$ldapsearch	= ldap_search($ldapconn, $dn, $filter);
		 
		if(ldap_count_entries($ldapconn, $ldapsearch) > 0 ) { 
			$ldapentry 		= ldap_first_entry($ldapconn,$ldapsearch);
			do // Loop through them until we find the one we want 
			{ 
					if($ldapentry === false) break; 
					$user_dn	= ldap_get_dn($ldapconn,$ldapentry);
					echo "user dn $user_dn \n";
					
					$ldapbind	= @ldap_bind($ldapconn, $user_dn , $password);
					if($ldapbind) {
						echo "Found User $user with Password $password\n";
						$ldapattrs = ldap_get_attributes($ldapconn,$ldapentry);
						print_r($ldapattrs);
						//break;
					}
			} while ( $ldapentry = ldap_next_entry($ldapconn,$ldapentry) );
			ldap_free_result($ldapsearch);
		}
	} else {
		echo "LDAP bind failed...".PHP_EOL;
	}
}

ldap_close($ldapconn);



?>
