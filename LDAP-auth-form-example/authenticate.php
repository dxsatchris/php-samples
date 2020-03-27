<?php

function authenticate($user, $password) {
    include_once('config_gta_new.php');

    if (empty($user) || empty($password)) {
        return false;
    }

    // active directory server
    $ldap_host = $CONFIG['ldap_host'];
    // active directory DN (base location of ldap search)
    $ldap_dn = $CONFIG['ldap_dn'];
    // active directory user group name
    $ldap_user_group = $CONFIG['ldap_user_group'];
    // active directory manager group name
    $ldap_manager_group = $CONFIG['ldap_manager_group'];
    // domain, for purposes of constructing $user
    $ldap_usr_dom = $CONFIG['ldap_usr_dom'];
    // connect to active directory
    $ldap = ldap_connect($ldap_host);

    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    // verify user and password
    if ($bind = @ldap_bind($ldap, $user . $ldap_usr_dom, $password)) {
        $filter = "(sAMAccountName=" . $user . ")";
        $attr = array("memberof");
        $result = ldap_search($ldap, $ldap_dn, $filter, $attr) or exit("Unable to search LDAP server");
        $entries = ldap_get_entries($ldap, $result);

        if (count($entries)>1) {
            // check groups
            $access = 0;
            foreach ($entries[0]['memberof'] as $grps) {

                // is manager, break loop
                if (strpos($grps, $ldap_manager_group)) {
                    $access = 2;
                    break;
                }

                // is user
                if (strpos($grps, $ldap_user_group))
                    $access = 1;
            }

            if ($access != 0) {
                // establish session variables
                $_SESSION['user'] = $user;
                $_SESSION['access'] = $access;
                return true;
            } else {
                // user has no rights
                return false;
            }
        }

        ldap_unbind($ldap);
    } else {
        // invalid name or password
        return false;
    }
}
