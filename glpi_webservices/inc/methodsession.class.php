<?php

/*
 * @version $Id: HEADER 14685 2011-06-11 06:40:30Z remi $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file: Methods for WebServices plugin
//    Methods which manage the user session
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginWebservicesMethodSession extends PluginWebservicesMethodCommon {

   /**
    * This method try to identicate a user
    *
    * @param $params array of options
    * => login_name : mandatory user name
    * => login_password : mandatory user password
    * => other : optionnal values for post action
    *
    * @return an response ready to be encode
    * => id of the user
    * => name of the user
    * => realname of the user
    * => firstname of the user
    * => session : ID of the session for future call
    */
   static function methodLogin($params,$protocol) {

      if (isset ($params['help'])) {
         return array( 'login_name'     => 'string,mandatory',
                       'login_password' => 'string,mandatory',
                       'help'           => 'bool,optional');
      }

      if (!isset ($params['login_name'])
          || empty ($params['login_name'])
          || !isset ($params['login_password'])
          || empty ($params['login_password'])) {

         return self::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER);
      }

      foreach ($params as $name => $value) {
         switch ($name) {
            case 'login_name' :
            case 'login_password' :
               break;

            default :
               // Store to Session, for post login action (retrieve_more_data_from_ldap, p.e.)
               $_SESSION[$name] = $value;
         }
      }

      $identificat = new Auth();

      if ($identificat->Login($params['login_name'],$params['login_password'],true)) {
         return (array ('id'        => getLoginUserID(),
                        'name'      => $_SESSION['glpiname'],
                        'realname'  => $_SESSION['glpirealname'],
                        'firstname' => $_SESSION['glpifirstname'],
                        'session'   => session_id()));
      }
      return self::Error($protocol,WEBSERVICES_ERROR_LOGINFAILED,'',html_clean($identificat->getErr()));
   }


   /**
    * This method try to identicate a user
    *
    * @param $params array of options ignored
    * @return an response ready to be encode
    * => fields of glpi_users
    */
   static function methodGetMyInfo($params,$protocol) {

      if (isset ($params['help'])) {
         return array ('help'    => 'bool,optional',
                       'id2name' => 'bool,optional');
      }

      if (!getLoginUserID()) {
         return self::Error($protocol,WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      $user = new User();
      if ($user->getFromDB(getLoginUserID())) {
         $resp = $user->fields;
         if (isset ($params['id2name'])) {
            $resp['locations_name']
               = html_clean(Dropdown::getDropdownName('glpi_locations', $resp['locations_id']));
            $resp['usertitles_name']
               = html_clean(Dropdown::getDropdownName('glpi_usertitles', $resp['usertitles_id']));
            $resp['usercategories_name']
               = html_clean(Dropdown::getDropdownName('glpi_usercategories', $resp['usercategories_id']));
            $resp['default_requesttypes_name']
               = html_clean(Dropdown::getDropdownName('glpi_requesttypes', $resp['default_requesttypes_id']));
         }
         return ($resp);
      } else {
         return self::Error($protocol,WEBSERVICES_ERROR_NOTFOUND);
      }
   }


   /**
    * This method try to identicate a user
    *
    * @param $params array of options ignored
    * @return an response ready to be encode
    * => Nothing
    */
   static function methodLogout($params, $protocol) {

      if (isset ($params['help'])) {
         return array ('help' => 'bool,optional');
      }

      $msg = "Bye ";
      if (getLoginUserID()) {
         $msg .= (empty ($_SESSION['glpifirstname']) ? $_SESSION['glpiname'] : $_SESSION['glpifirstname']);
      }

      $id = new Auth();
      $id->destroySession();

      return array ('message' => $msg);
   }


   /**
    * This method try to identicate a user
    *
    * @param $params array of options ignored
    * @return an response ready to be encode
    * => fields of glpi_users
    */
   static function methodListMyProfiles($params,$protocol) {

      if (isset ($params['help'])) {
         return array ('help' => 'bool,optional');
      }

      if (!getLoginUserID()) {
         return self::Error($protocol,WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      $resp = array ();
      foreach ($_SESSION['glpiprofiles'] as $id => $prof) {
         $resp[] = array ('id'   => $id,
                          'name' => $prof['name'],
                          'current' => ($id == $_SESSION['glpiactiveprofile']['id'] ? 1 : 0));
      }
      return $resp;
   }


   /**
    * This method return the entities list allowed
    * for a authenticated users
    *
    * @param $params array of option : ignored
    *
    * @return an response ready to be encode (ID + completename)
    */
   static function methodListMyEntities($params,$protocol) {
      global $DB, $LANG;

      if (isset ($params['help'])) {
         return array ('help' => 'bool,optional');
      }

      if (!getLoginUserID()) {
         return self::Error($protocol,WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      $resp = array ();

      foreach ($_SESSION['glpiactiveprofile']['entities'] as $ent) {
         if ($ent['is_recursive']) {
            $search = getSonsOf("glpi_entities",$ent['id']);
         } else {
            $search = $ent['id'];
         }
         if ($ent['id'] == 0) {
            $resp[0] = array ('id'           => 0,
                              'name'         => $LANG['entity'][2],
                              'entities_id'  => 0,
                              'completename' => $LANG['entity'][2],
                              'comment'      => '',
                              'level'        => 0,
                              'is_recursive' => $ent['is_recursive'],
                              'current'      => (in_array(0, $_SESSION['glpiactiveentities']) ? 1 : 0));
         }
         foreach ($DB->request('glpi_entities', array ('id' => $search)) as $data) {
            $resp[$data['id']] = array('id'           => $data['id'],
                                       'name'         => $data['name'],
                                       'entities_id'  => $data['entities_id'],
                                       'completename' => $data['completename'],
                                       'comment'      => $data['comment'],
                                       'level'        => $data['level'],
                                       'is_recursive' => $ent['is_recursive'],
                                       'current'      => (in_array($data['id'],
                                                            $_SESSION['glpiactiveentities']) ? 1 : 0));
         }
      }
      return $resp;
   }


   /**
    * Change the current profile of a authenticated user
    *
    * @param $params array of options
    *  - profile : ID of the new profile
    * @return an response ready to be encode
    *  - ID
    *  - name of the new profile
    */
   static function methodSetMyProfile($params,$protocol) {

      if (isset ($params['help'])) {
         return array ('profile' => 'integer,mandatory',
                       'help'    => 'bool,optional');
      }

      if (!getLoginUserID()) {
         return self::Error($protocol,WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }
      if (!isset ($params['profile'])) {
         return self::Error($protocol,WEBSERVICES_ERROR_MISSINGPARAMETER, '', 'profile');
      }

      // TODO search for profile name if not an ID.
      $id = $params['profile'];

      if (isset ($_SESSION['glpiprofiles'][$id])
          && count($_SESSION['glpiprofiles'][$id]['entities'])) {

         changeProfile($id);
         $resp = array ('id'   => $_SESSION['glpiactiveprofile']['id'],
                        'name' => $_SESSION['glpiactiveprofile']['name']);
      } else {
         return self::Error($protocol,WEBSERVICES_ERROR_BADPARAMETER, '', "profile=$id");
      }
      return $resp;
   }


   /**
    * Change the current entity(ies) of a authenticated user
    *
    * @param $params array of options
    *  - entity : ID of the new entity or "all"
    *  - recursive : 1 to see children
    * @return like plugin_webservices_method_listEntities
    */
   static function methodSetMyEntity($params, $protocol) {

      if (isset ($params['help'])) {
         return array ('entity'    => 'integer,mandatory',
                       'recursive' => 'bool,optional',
                       'help'      => 'bool,optional');
      }

      if (!getLoginUserID()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      if (!isset ($params['entity'])) {
         return self::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER, '', 'entity');
      }

      if (changeActiveEntities($params['entity'],
                               (isset ($params['recursive']) && $params['recursive']))) {
         return self::methodListEntities($_SESSION['glpiactiveentities'], $params);
      }

      return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', "entity=" . $params['entity']);
   }


   /**
    * Recovery session
    * @param session the session ID
    * @destroy try to destroy current session, in order to recover the stored one
    * TODO : use it for xmlrpc.php
    */
   static function setSession($session) {

      $current = session_id();
      $session = trim($session);

      if (file_exists(GLPI_ROOT . "/config/config_path.php")) {
         include_once (GLPI_ROOT . "/config/config_path.php");
      }
      if (!defined("GLPI_SESSION_DIR")) {
         define("GLPI_SESSION_DIR", GLPI_ROOT . "/files/_sessions");
      }

      if ($session!=$current && !empty($current)) {
         session_destroy();
      }
      if ($session!=$current && !empty($session)) {
         if (ini_get("session.save_handler")=="files") {
            session_save_path(GLPI_SESSION_DIR);
         }
         session_id($session);
         session_start();
         
         // Define current time for sync of action timing
         $_SESSION["glpi_currenttime"] = date("Y-m-d H:i:s");
      }
   }


   /**
    * Standard method execution : checks if client can execute method + manage session
    *
    * @param $method string method name
    * @param $params array the method parameters
    * @return array the method response
    */
   function execute($method,$params,$protocol) {
      global $LANG, $DB, $WEBSERVICES_METHOD, $TIMER_DEBUG;

      // Don't display error in result
      set_error_handler('userErrorHandlerNormal');
      ini_set('display_errors', 'Off');

      $iptxt = (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"]
                                                        : $_SERVER["REMOTE_ADDR"]);
      $ipnum = ip2long($iptxt);


      if (isset($_SESSION["MESSAGE_AFTER_REDIRECT"])) {
         // Avoid to keep "info" message between call
         $_SESSION["MESSAGE_AFTER_REDIRECT"]='';
      }

      $plug = new Plugin();
      if ($plug->isActivated('webservices')) {
         if (isset ($params['session'])) {
            self::setSession($params['session']);
         }

         // Build query for security check
         $sql = "SELECT *
                 FROM `glpi_plugin_webservices_clients`
                 WHERE '" . addslashes($method) . "' REGEXP pattern
                       AND `is_active` = '1'
                       AND (`ip_start` IS NULL
                            OR (`ip_start` <= '$ipnum' AND `ip_end` >= '$ipnum'))";

         if (isset ($params["username"])) {
            $username = addslashes($params["username"]);
            $password = md5(isset ($params["password"]) ? $params["password"] : '');

            $sql .= " AND (`username` IS NULL
                           OR (`username` = '$username' AND `password` = '$password'))";

            unset ($params["username"]);
            unset ($params["password"]);
         } else {
            $username = 'anonymous';
            $sql .= " AND `username` IS NULL ";
         }
         $deflate = $debug = $log = false;
         $entities = array ();
         if (getLoginUserID() && isset ($_SESSION['glpiactiveentities'])) {
            $username = $_SESSION['glpiname']; // for log (no t for SQL request)
         }

         foreach ($DB->request($sql) as $data) {
            // Check matching rules

            // Store entities for not authenticated user
            if (!getLoginUserID()) {
               if ($data['is_recursive']) {
                  foreach (getSonsOf("glpi_entities",$data['entities_id']) as $entity) {
                     $entities[$entity] = $entity;
                  }
               } else {
                  $entities[$data['entities_id']] = $data['entities_id'];
               }
            }

            // Where to log
            if ($data["do_log"] == 2) {
               // Log to file
               $log = LOGFILENAME;
            } else if ($data["do_log"] == 1) {
               // Log to History
               $log = $data["id"];
            }
            $debug = $data['debug'];
            $deflate = $data['deflate'];
         }
         $callname='';
         // Always log when connection denied
         if (!getLoginUserID() && !count($entities)) {
            $resp = self::Error($protocol,1, $LANG['login'][5]);

            // log to file (not macthing config to use history)
            logInFile(LOGFILENAME, $LANG['login'][5] . " ($username, $iptxt, $method, $protocol)\n");
         } else { // Allowed
            if (!getLoginUserID()) {
               // TODO : probably more data should be initialized here
               $_SESSION['glpiactiveentities'] = $entities;
            }
            // Log if configured
            if (is_numeric($log)) {
               $changes[0] = 0;
               $changes[1] = "";
               $changes[2] = $LANG['log'][55] . " ($username, $iptxt, $method, $protocol)";
               Log::history($log, 'PluginWebservicesClient', $changes, 0, HISTORY_LOG_SIMPLE_MESSAGE);
            } else if ($log && !$debug) {
               logInFile($log, $LANG['log'][55] . " ($username, $iptxt, $method)\n");
            }

            $defserver = ini_get('zlib.output_compression');

            if ($deflate && !$defserver) {
               // Globally off, try to enable for this client
               // This only work on PHP > 5.3.0
               ini_set('zlib.output_compression', 'On');
            }
            if (!$deflate && $defserver) {
               // Globally on, disable for this client
               ini_set('zlib.output_compression', 'Off');
            }

            if (!isset ($WEBSERVICES_METHOD[$method])) {
               $resp = self::Error($protocol,2, "Unknown method ($method)");
               logInFile(LOGFILENAME, "Unknown method ($method)\n");
            } else if (is_callable($call=$WEBSERVICES_METHOD[$method], false, $callname)){
               $resp = call_user_func($WEBSERVICES_METHOD[$method], $params, $protocol);
               logInFile(LOGFILENAME,
                         "Execute method:$method, function:$callname ($protocol), ".
                         "duration:".$TIMER_DEBUG->getTime().", size:".strlen(serialize($resp))."\n");
            } else {
               $resp = self::Error($protocol, 3, "Unknown internal function for $method",
                                                $protocol);
               logInFile(LOGFILENAME, "Unknown internal function for $method\n");
            }
         } // Allowed
         if ($debug) {
            logInFile(LOGFILENAME, $LANG['log'][55] . ": $username, $iptxt\nProtocol: $protocol, ".
                      "Method: $method, Function: $callname\nParams: ".
                      (count($params) ? print_r($params, true) : "none\n") .
                      "Compression: Server:$defserver/" . ini_get('zlib.output_compression') .
                      ", Config:$deflate, Agent:" .
                      (isset ($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '?') .
                      "\nDuration: " .$TIMER_DEBUG->getTime()."s\nResponse size: ".strlen(serialize($resp)).
                      "\nResponse content: " .print_r($resp, true));
         }
      } else {
         $resp = self::Error($protocol,4, "Server not ready",$protocol);
      } // Activated

      return $resp;
   }
}
?>