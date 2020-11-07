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
// Purpose of file: Test the XML-RPC plugin from Command Line
// ----------------------------------------------------------------------

if (!extension_loaded("soap")) {
   die("Extension soap not loaded\n");
}

chdir(dirname($_SERVER["SCRIPT_FILENAME"]));
chdir("../../..");
$url = "/" . basename(getcwd()) . "/plugins/webservices/soap.php";

$args = array ();
if ($_SERVER['argc'] > 1) {
   for ($i = 1 ; $i < count($_SERVER['argv']) ; $i++) {
      $it = explode("=", $argv[$i], 2);
      $it[0] = preg_replace('/^--/', '', $it[0]);
      $args[$it[0]] = (isset ($it[1]) ? $it[1] : true);
   }
}

if (isset ($args['help']) && !isset ($args['method'])) {
   echo "\nusage : " . $_SERVER["SCRIPT_FILENAME"] . " [ options] \n\n";

   echo "\thelp     : display this screen\n";
   echo "\thost     : server name or IP, default : localhost\n";
   echo "\turl      : SOAP plugin URL, default : $url\n";
   echo "\tusername : User name for security check (optionnal)\n";
   echo "\tpassword : User password (optionnal)\n";
   echo "\tmethod   : SOAP method to call, default : glpi.test\n";
   echo "\tdeflate  : allow server to compress response (if supported)\n";

   die("\nOther options are used for SOAP call.\n\n");
}

if (isset ($args['url'])) {
   $url = $args['url'];
   unset ($args['url']);
}

if (isset ($args['host'])) {
   $host = $args['host'];
   unset ($args['host']);
} else {
   $host = 'localhost';
}

if (isset ($args['method'])) {
   $method = $args['method'];
} else {
   $args['method'] = $method = 'glpi.test';
}

echo "+ Calling '$method' on http://$host/$url\n";

try {
   // Nouvelle instance de la classe soapClient
   $client = new SoapClient(null, array('uri'      => 'http://' . $host . '/' . $url,
                                        'location' => 'http://' . $host . '/' . $url));

   //Call the genericExecute method
   $result = $client->__soapCall('genericExecute', array(new SoapParam($args, 'params')));
   print_r($result);

} catch (SoapFault $fault) {
   echo $fault;
}

?>