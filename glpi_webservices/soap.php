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
// Original Author of file: Walid Nouh
// Purpose of file: Plugin setup.
// ----------------------------------------------------------------------

if (!extension_loaded("soap")) {
   header("HTTP/1.0 500 Extension soap not loaded");
   die("Extension soap not loaded");
}

ini_set("soap.wsdl_cache_enabled", "0");

define('GLPI_ROOT', '../..');
include (GLPI_ROOT . "/inc/includes.php");

Plugin::load('webservices', true);

doHook("webservices");
plugin_webservices_registerMethods();

error_reporting(E_ALL);

try {
   $server = new SoapServer(null, array('uri' => ''));
   $server->setclass('PluginWebservicesSoap');

} catch (Exception $e) {
   echo $e;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
   $server->handle();
}

?>