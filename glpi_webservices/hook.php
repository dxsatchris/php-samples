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
// Purpose of file: Hook for the webservices plugin
// ----------------------------------------------------------------------

foreach (glob(GLPI_ROOT . '/plugins/webservices/inc/*.php') as $file) {
   include_once ($file);
}


function plugin_webservices_registerMethods() {
   global $WEBSERVICES_METHOD;

   // Not authenticated method
   $WEBSERVICES_METHOD['glpi.test']           = array('PluginWebservicesMethodCommon','methodTest');
   $WEBSERVICES_METHOD['glpi.status']         = array('PluginWebservicesMethodCommon','methodStatus');
   $WEBSERVICES_METHOD['glpi.listAllMethods'] = array('PluginWebservicesMethodCommon','methodList');
   $WEBSERVICES_METHOD['glpi.listEntities']   = array('PluginWebservicesMethodCommon','methodListEntities');
   $WEBSERVICES_METHOD['glpi.doLogin']        = array('PluginWebservicesMethodSession','methodLogin');

   // Authenticated method - Session
   $WEBSERVICES_METHOD['glpi.doLogout']       = array('PluginWebservicesMethodSession','methodLogout');
   $WEBSERVICES_METHOD['glpi.getMyInfo']      = array('PluginWebservicesMethodSession','methodGetMyInfo');
   $WEBSERVICES_METHOD['glpi.listMyProfiles'] = array('PluginWebservicesMethodSession','methodListMyProfiles');
   $WEBSERVICES_METHOD['glpi.setMyProfile']   = array('PluginWebservicesMethodSession','methodSetMyProfile');
   $WEBSERVICES_METHOD['glpi.listMyEntities'] = array('PluginWebservicesMethodSession','methodListMyEntities');
   $WEBSERVICES_METHOD['glpi.setMyEntity']    = array('PluginWebservicesMethodSession','methodSetMyEntity');

   // Authenticated method - Others
   $WEBSERVICES_METHOD['glpi.listDropdownValues'] = array('PluginWebservicesMethodCommon','methodListDropdownValues');
   $WEBSERVICES_METHOD['glpi.listGroups']         = array('PluginWebservicesMethodInventaire','methodListGroups');
   $WEBSERVICES_METHOD['glpi.listHelpdeskTypes']  = array('PluginWebservicesMethodHelpdesk','methodListHelpdeskTypes');
   $WEBSERVICES_METHOD['glpi.listHelpdeskItems']  = array('PluginWebservicesMethodHelpdesk','methodListHelpdeskItems');
   $WEBSERVICES_METHOD['glpi.listTickets']        = array('PluginWebservicesMethodHelpdesk','methodListTickets');
   $WEBSERVICES_METHOD['glpi.listUsers']          = array('PluginWebservicesMethodInventaire','methodListUsers');

   //Inventory
   $WEBSERVICES_METHOD['glpi.getDocument']          = array('PluginWebservicesMethodInventaire','methodGetDocument');
   $WEBSERVICES_METHOD['glpi.listInventoryObjects'] = array('PluginWebservicesMethodInventaire','methodListInventoryObjects');
   $WEBSERVICES_METHOD['glpi.listObjects']          = array('PluginWebservicesMethodInventaire','methodListObjects');
   $WEBSERVICES_METHOD['glpi.getObject']            = array('PluginWebservicesMethodInventaire','methodGetObject');

   // Inventory : write methods
   $WEBSERVICES_METHOD['glpi.createObjects'] = array('PluginWebservicesMethodInventaire','methodCreateObjects');
   $WEBSERVICES_METHOD['glpi.deleteObjects'] = array('PluginWebservicesMethodInventaire','methodDeleteObjects');
   $WEBSERVICES_METHOD['glpi.updateObjects'] = array('PluginWebservicesMethodInventaire','methodUpdateObjects');
   $WEBSERVICES_METHOD['glpi.linkObjects']   = array('PluginWebservicesMethodInventaire','methodLinkObjects');

   //Inventor : generic methods
   $WEBSERVICES_METHOD['glpi.getInfocoms']  = array('PluginWebservicesMethodInventaire','methodGetInfocoms');
   $WEBSERVICES_METHOD['glpi.getContracts'] = array('PluginWebservicesMethodInventaire','methodGetContracts');

   //Inventory : computer
   $WEBSERVICES_METHOD['glpi.getComputer']          = array('PluginWebservicesMethodInventaire','methodGetComputer');
   $WEBSERVICES_METHOD['glpi.getComputerInfoComs']  = array('PluginWebservicesMethodInventaire','methodGetComputerInfoComs');
   $WEBSERVICES_METHOD['glpi.getComputerContracts'] = array('PluginWebservicesMethodInventaire','methodGetComputerContracts');
   $WEBSERVICES_METHOD['glpi.getNetworkports']      = array('PluginWebservicesMethodInventaire','methodGetNetworkports');
   $WEBSERVICES_METHOD['glpi.listComputers']        = array('PluginWebservicesMethodInventaire','methodListComputers');
   $WEBSERVICES_METHOD['glpi.getContracts']         = array('PluginWebservicesMethodInventaire','methodGetContracts');
   $WEBSERVICES_METHOD['glpi.getPhones']            = array('PluginWebservicesMethodInventaire','methodGetPhones');

   //Inventory : network equipment
   $WEBSERVICES_METHOD['glpi.getNetworkEquipment']  = array('PluginWebservicesMethodInventaire','methodGetNetworkEquipment');

   //Helpdesk
   $WEBSERVICES_METHOD['glpi.getTicket']              = array('PluginWebservicesMethodHelpdesk','methodGetTicket');
   $WEBSERVICES_METHOD['glpi.createTicket']           = array('PluginWebservicesMethodHelpdesk','methodCreateTicket');
   $WEBSERVICES_METHOD['glpi.addTicketFollowup']      = array('PluginWebservicesMethodHelpdesk','methodAddTicketFollowup');
   $WEBSERVICES_METHOD['glpi.addTicketDocument']      = array('PluginWebservicesMethodHelpdesk','methodAddTicketDocument');
   $WEBSERVICES_METHOD['glpi.addTicketObserver']      = array('PluginWebservicesMethodHelpdesk','methodAddTicketObserver');
   $WEBSERVICES_METHOD['glpi.setTicketSatisfaction']  = array('PluginWebservicesMethodHelpdesk','methodsetTicketSatisfaction');
   $WEBSERVICES_METHOD['glpi.setTicketValidation']    = array('PluginWebservicesMethodHelpdesk','methodsetTicketValidation');
}


// Install process for plugin : need to return true if succeeded
function plugin_webservices_install() {
   global $DB, $LANG;

   if (TableExists("glpi_plugin_webservices")) {
      $DB->query("RENAME TABLE `glpi_plugin_webservices` to `glpi_plugin_webservices_clients`");

      $query = "ALTER TABLE `glpi_plugin_webservices_clients` ";

      if (FieldExists("glpi_plugin_webservices_clients", "ID")) {
         $query .= " CHANGE `ID` `id` int(11) NOT NULL auto_increment,";
      }
      if (FieldExists("glpi_plugin_webservices_clients", "FK_entities")) {
         $query .= " CHANGE `FK_entities` `entities_id` INT NOT NULL DEFAULT '0',";
      }
      if (FieldExists("glpi_plugin_webservices_clients", "recursive")) {
         $query .= " CHANGE `recursive` `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT '0',";
      }
      if (FieldExists("glpi_plugin_webservices_clients", "active")) {
         $query .= " CHANGE `active` `is_active` TINYINT( 1 ) NOT NULL DEFAULT '0',";
      }
      if (FieldExists("glpi_plugin_webservices_clients", "comments")) {
         $query .= " CHANGE `comments` `comment` TEXT NULL,";
      }
      if (!FieldExists("glpi_plugin_webservices_clients", "deflate")) {
         $query .= " ADD `deflate` TINYINT NOT NULL DEFAULT '0' AFTER `is_active`,";
      }
      if (!FieldExists("glpi_plugin_webservices_clients", "debug")) {
         $query .= " ADD `debug` TINYINT NOT NULL DEFAULT '0' AFTER `do_log`,";
      }
      $query .= " ADD KEY `entities_id` (`entities_id`)";
      $DB->query($query) or die($DB->error());

   } else {
      if (!TableExists("glpi_plugin_webservices_clients")) {
      $sql = "CREATE TABLE `glpi_plugin_webservices_clients` (
               `id` INT NOT NULL AUTO_INCREMENT,
               `entities_id` INT NOT NULL DEFAULT '0',
               `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT '0',
               `name` VARCHAR( 255 ) NOT NULL ,
               `pattern` VARCHAR( 255 ) NOT NULL ,
               `ip_start` BIGINT NULL ,
               `ip_end` BIGINT NULL ,
               `username` VARCHAR( 255 ) NULL ,
               `password` VARCHAR( 255 ) NULL ,
               `do_log` TINYINT NOT NULL DEFAULT '0',
               `debug` TINYINT NOT NULL DEFAULT '0',
               `is_active` TINYINT NOT NULL DEFAULT '0',
               `deflate` TINYINT NOT NULL DEFAULT '0',
               `comment` TEXT NULL ,
               PRIMARY KEY (`id`),
               KEY `entities_id` (`entities_id`)
             ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci ";
      $DB->query($sql) or die ("SQL Error");

      $sql = "INSERT INTO
              `glpi_plugin_webservices_clients` (`id`, `entities_id`, `is_recursive`, `name`,
                                                 `pattern`, `ip_start`, `ip_end` , `do_log`,
                                                 `is_active`, `comment`)
              VALUES (NULL, 0, 1, '".$LANG['webservices'][100]."', '.*', INET_ATON('127.0.0.1'),
                      INET_ATON('127.0.0.1'), 1, 1, '".$LANG['webservices'][101]."')";
      $DB->query($sql);
      }
   }

   return true;
}


// Uninstall process for plugin : need to return true if succeeded
function plugin_webservices_uninstall() {
   global $DB;

   $tables = array ("glpi_plugin_webservices",
                    "glpi_plugin_webservices_clients");

   foreach ($tables as $table) {
      $query = "DROP TABLE IF EXISTS `$table`;";
      $DB->query($query) or die($DB->error());
   }

   return true;
}


function plugin_webservices_giveItem($type,$ID,$data,$num) {
   global $LANG;

   $searchopt = &Search::getOptions($type);

   $table=$searchopt[$ID]["table"];
   $field=$searchopt[$ID]["field"];

   switch ($table.'.'.$field) {
      case "glpi_plugin_webservices_clients.do_log" :
            switch ($data["ITEM_$num"]) {
               case 2 :
                  return $LANG['Menu'][30];

               case 1:
                  return $LANG['title'][38];

               default:
                  return $LANG['choice'][0];
            }
         break;
   }
}


function plugin_webservices_addSelect($type,$ID,$num) {

   $searchopt = &Search::getOptions($type);

   $table=$searchopt[$ID]["table"];
   $field=$searchopt[$ID]["field"];

   switch ($table.'.'.$field) {
      case 'glpi_plugin_webservices_clients.ip' :
         return " CONCAT(INET_NTOA(ip_start), ' - ', INET_NTOA(ip_end)) AS ITEM_$num, ";
   }
   return '';
}


function plugin_webservices_addWhere($link,$nott,$type,$ID,$val) {

   $NOT = ($nott ? " NOT" : "");

   $searchopt = &Search::getOptions($type);

   $table=$searchopt[$ID]["table"];
   $field=$searchopt[$ID]["field"];

   switch ($table.'.'.$field) {
      case 'glpi_plugin_webservices_clients.ip' :
         return " $link $NOT (INET_ATON('$val')>=ip_start
                              AND INET_ATON('$val')<=ip_end) ";

      case 'pattern' :
         return " $link '$val' $NOT REGEXP pattern ";
   }
   return '';
}


function plugin_webservices_addOrderBy($type,$ID,$order,$key=0) {

   $searchopt = &Search::getOptions($type);
   $table=$searchopt[$ID]["table"];
   $field=$searchopt[$ID]["field"];

   switch ($table.'.'.$field) {
      case 'glpi_plugin_webservices_clients.ip' :
         return " ORDER BY INET_NTOA(`ip_start`) $order, INET_NTOA(`ip_end`) $order ";
   }
   return '';
}


function cron_plugin_webservices() {

   logInFile('webservices',"cron called\n");
   plugin_webservices_soap_create_wdsl();
   return 1;

}
?>