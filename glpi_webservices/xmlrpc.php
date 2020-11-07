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
// Purpose of file: Manage list of config
// ----------------------------------------------------------------------
if (!function_exists("xmlrpc_encode")) {
   header("HTTP/1.0 500 Extension xmlrpc not loaded");
   die("Extension xmlrpc not loaded");
}

define('GLPI_ROOT', '../..');

function decodeFromUtf8Array(&$arg) {
   if (is_array($arg)) {
      foreach (array_keys($arg) as $key) {
         decodeFromUtf8Array($arg[$key]);
      }
   } else if (is_string($arg)) {
      $arg = decodeFromUtf8($arg);
   }
}
// define session_id before any other think
if (isset($_GET['session'])) {
   include_once ("inc/methodcommon.class.php");
   include_once ("inc/methodsession.class.php");
   $session = new PluginWebservicesMethodSession();
   $session->setSession($_GET['session']);
}

include (GLPI_ROOT . "/inc/includes.php");

Plugin::load('webservices', true);

doHook("webservices");
plugin_webservices_registerMethods();

error_reporting(E_ALL);

if ($_SERVER["CONTENT_TYPE"] != "text/xml") {
   header("HTTP/1.0 500 Bad content type");
   die("Bad content type");
}

if (!isset($GLOBALS["HTTP_RAW_POST_DATA"]) || empty($GLOBALS["HTTP_RAW_POST_DATA"])) {
   header("HTTP/1.0 500 No content");
}

$method = "";
$allparams = xmlrpc_decode_request($GLOBALS["HTTP_RAW_POST_DATA"],$method,'UTF-8');

if (empty($method) || !is_array($allparams)) {
   header("HTTP/1.0 500 Bad content");
}

$params = (isset($allparams[0]) && is_array($allparams[0]) ? $allparams[0] : array());
if (isset($params['iso8859'])) {
   $iso = true;
   unset($params['iso8859']);
} else {
   $iso = false;
}

$session = new PluginWebservicesMethodSession();
$resp = $session->execute($method,$params,WEBSERVICE_PROTOCOL_XMLRPC);

header("Content-type: text/xml");

if ($iso) {
   decodeFromUtf8Array($resp);
   echo xmlrpc_encode_request(NULL,$resp,array('encoding'=>'ISO-8859-1'));
} else {
   // request without method is a response ;)
   echo xmlrpc_encode_request(NULL,$resp,array('encoding'=>'UTF-8'));
}
?>