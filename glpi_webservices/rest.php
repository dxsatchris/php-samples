<?php
/*
 * @version $Id: rest.php 300 2011-10-07 09:08:36Z walid $
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
// Original Author of file: Walid Nouh
// Purpose of file: REST communication
// ----------------------------------------------------------------------
if (!function_exists("json_encode")) {
   header("HTTP/1.0 500 Extension json not loaded");
   die("Extension json not loaded");
}

define('GLPI_ROOT', '../..');

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

$method = (isset($_GET['method'])?$_GET['method']:'');
$session = new PluginWebservicesMethodSession();
$resp    = $session->execute($method, $_GET, WEBSERVICE_PROTOCOL_REST);

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
echo json_encode($resp);
?>