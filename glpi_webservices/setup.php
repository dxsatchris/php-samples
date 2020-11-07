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
// Purpose of file: Plugin setup.
// ----------------------------------------------------------------------

// Init the hooks of the plugins -Needed
function plugin_init_webservices() {
   global $PLUGIN_HOOKS, $LANG, $CFG_GLPI, $WEBSERVICE_LINKED_OBJECTS;

   Plugin::registerClass('PluginWebservicesClient');

   if (haveRight("config", "r")) {
      $PLUGIN_HOOKS['menu_entry']['webservices']               = 'front/client.php';
      $PLUGIN_HOOKS['submenu_entry']['webservices']['search']  = 'front/client.php';
      $PLUGIN_HOOKS['submenu_entry']['webservices']['add']     = 'front/client.form.php?new=1';
   }
   $PLUGIN_HOOKS['webservices']['webservices'] = 'plugin_webservices_registerMethods';

   //Store objects that can be retrieved when querying another object
   $WEBSERVICE_LINKED_OBJECTS =array (
                    'with_infocom'      => array('help'           => 'bool, optional',
                                                 'itemtype'       => 'Infocom',
                                                 'allowed_types'  => $CFG_GLPI['infocom_types'],
                                                 'class'          => 'PluginWebservicesMethodInventaire'),
                    'with_networkport'  => array('help'           => 'bool, optional',
                                                 'itemtype'       => 'NetworkPort',
                                                 'allowed_types'  => plugin_webservices_getNetworkPortItemtypes(),
                                                 'class'          => 'PluginWebservicesMethodInventaire'),
                    'with_phone'        => array('help'           => 'bool, optional (Computer only)',
                                                 'itemtype'       => 'Phone',
                                                 'allowed_types'  => array('Computer'),
                                                 'class'          => 'PluginWebservicesMethodInventaire'),
                    'with_printer'      => array('help'           => 'bool', 'optional (Computer only)',
                                                 'itemtype'       => 'Printer',
                                                 'allowed_types'  => array('Computer'),
                                                 'class'          => 'PluginWebservicesMethodInventaire'),
                    'with_monitor'      => array('help'           => 'bool', 'optional (Computer only)',
                                                 'itemtype'       => 'Monitor',
                                                 'allowed_types'  => array('Computer'),
                                                 'class'          => 'PluginWebservicesMethodInventaire'),
                    'with_peripheral'   => array('help'           => 'bool', 'optional (Computer only)',
                                                 'itemtype'       => 'Peripheral',
                                                 'allowed_types'  => array('Computer'),
                                                 'class'          => 'PluginWebservicesMethodInventaire'),
                    'with_document'     => array('help'           => 'bool', 'optional',
                                                 'itemtype'       => 'Document',
                                                 'allowed_types'  => plugin_webservices_getDocumentItemtypes(),
                                                 'class'          => 'PluginWebservicesMethodInventaire'),
                    'with_ticket'       => array('help'           => 'bool', 'optional',
                                                 'itemtype'       => 'Ticket',
                                                 'allowed_types'  => plugin_webservices_getTicketItemtypes(),
                                                 'class'          => 'PluginWebservicesMethodHelpdesk'),
                    'with_tickettask'   => array('help'           => 'bool', 'optional (Ticket only)',
                                                 'itemtype'       => 'TicketTask',
                                                 'allowed_types'  => array('Ticket'),
                                                 'class'          => 'PluginWebservicesMethodHelpdesk'),
                    'with_ticketfollowup'=> array('help'          => 'bool', 'optional (Ticket only)',
                                                 'itemtype'       => 'TicketFollowup',
                                                 'allowed_types'  => array('Ticket'),
                                                 'class'          => 'PluginWebservicesMethodHelpdesk'),
                    'with_ticketvalidation'=> array('help'        => 'bool', 'optional (Ticket only)',
                                                 'itemtype'       => 'TicketValidation',
                                                 'allowed_types'  => array('Ticket'),
                                                 'class'          => 'PluginWebservicesMethodHelpdesk'),
                    'with_reservation'  => array('help'           => 'bool',
                                                 'itemtype'       => 'Reservation',
                                                 'allowed_types'  => $CFG_GLPI['reservation_types'],
                                                 'class'          => 'PluginWebservicesMethodInventaire'),
                    'with_software'     => array('help'           => 'bool',
                                                 'itemtype'       => 'Software',
                                                 'allowed_types'  => array('Computer'),
                                                 'class'          => 'PluginWebservicesMethodInventaire'),
                    'with_softwareversion' => array('help'        => 'bool',
                                                 'itemtype'       => 'SoftwareVersion',
                                                 'allowed_types'  => array('Software'),
                                                 'class'          => 'PluginWebservicesMethodInventaire'),
                    'with_softwarelicense' => array('help'        => 'bool',
                                                 'itemtype'       => 'SoftwareLicense',
                                                 'allowed_types'  => array('Software'),
                                                 'class'          => 'PluginWebservicesMethodInventaire'),
                    'with_contract'     => array('help'           => 'bool',
                                                 'itemtype'       => 'Contract',
                                                 'allowed_types'  => $CFG_GLPI['contract_types'],
                                                 'class'          => 'PluginWebservicesMethodInventaire')
   );
}

// Get the name and the version of the plugin - Needed
function plugin_version_webservices() {
   global $LANG;

   return array('name'           => $LANG['webservices'][0],
                'version'        => '1.2.0',
                'author'         => 'Remi Collet, Nelly Lasson, Walid Nouh',
                'homepage'       => 'https://forge.indepnet.net/projects/show/webservices',
                'minGlpiVersion' => '0.80');
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_webservices_check_prerequisites() {
   global $LANG;

   if (version_compare(GLPI_VERSION,'0.80','lt') || version_compare(GLPI_VERSION,'0.81','ge')) {
      echo $LANG['webservices'][21]. "0.80.x";
   } else if (!extension_loaded("soap")) {
      echo $LANG['webservices'][22]. "soap";
   } else if (!function_exists("xmlrpc_encode")) {
      echo $LANG['webservices'][22]. "xmlrpc";
   } else if (!function_exists("json_encode")) {
      echo $LANG['webservices'][22]. "json";
   } else {
      return true;
   }
   return false;
}


// Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
function plugin_webservices_check_config() {
   return TableExists("glpi_plugin_webservices_clients");
}

function plugin_webservices_getDocumentItemtypes() {
   global $CFG_GLPI;

   return $CFG_GLPI['document_types'];
}

function plugin_webservices_getNetworkPortItemtypes() {
   global $CFG_GLPI;

   return $CFG_GLPI['networkport_types'];
}

function plugin_webservices_getTicketItemtypes() {
   global $CFG_GLPI;

   return $CFG_GLPI['ticket_types'];

}
?>
