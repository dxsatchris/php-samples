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
// Purpose of file: Classes for XML-RPC plugin
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginWebservicesClient extends CommonDBTM {

   public $dohistory        = true;


   static function getTypeName() {
      global $LANG;

      return $LANG['webservices'][0];
   }

   function canCreate() {
      return haveRight('config', 'w');
   }

   function canView() {
      return haveRight('config', 'r');
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      $ong[1] = $LANG['title'][26];

      if ($this->fields['id'] > 0) {
         $ong[2]  = $LANG['webservices'][7];
         $ong[12] = $LANG['title'][38];
      }
      return $ong;
   }


   function prepareInputForAdd($input) {
      return $this->prepareInputForUpdate($input);
   }


   function prepareInputForUpdate($input) {

      if (isset($input['username'])) {
         if (empty($input['username'])) {
            $input['username'] = "NULL";
            $input['password'] = "NULL";
         } else {
            $input['password'] = md5(isset($input['password']) ? $input['password'] : '');
         }
      }

      if (isset($input['_start']) && isset($input['_end'])) {
         if (empty($input['_start'])) {
            $input['ip_start'] = "NULL";
            $input['ip_end'] = "NULL";
         } else {
            $input['ip_start'] = ip2long($input['_start']);
            if (empty($input['_end'])) {
               $input['ip_end'] = $input['ip_start'];
            } else {
               $input['ip_end'] = ip2long($input['_end']);
            }
            if ($input['ip_end'] < $input['ip_start']) {
               $tmp = $input['ip_end'];
               $input['ip_end'] = $input['ip_start'];
               $input['ip_start'] = $tmp;
            }
         }
      }
      return $input;
   }


   function showForm ($ID, $options=array()) {
      global $DB, $CFG_GLPI, $LANG;

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         $this->check(-1,'w');
         $this->getEmpty();
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG["common"][16]."&nbsp;:</td><td>";
      autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td rowspan='10' class='middle right'>".$LANG["common"][25]."&nbsp;:</td>";
      echo "<td class='center middle' rowspan='10'><textarea cols='45' rows='13' name='comment' >".
             $this->fields["comment"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".$LANG["webservices"][1]."&nbsp;:</td><td>";
      Dropdown::showYesNo("is_active",$this->fields["is_active"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['webservices'][9]."&nbsp;:</td><td>";
      Dropdown::showYesNo("deflate",$this->fields["deflate"])."</td></tr>";
      echo "<tr><td></td><td><i>  " . nl2br($LANG['webservices'][14]) . "</i></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".$LANG["webservices"][6]."&nbsp;:</td><td>";
      Dropdown::showFromArray("do_log", array(0 => $LANG['choice'][0], // Non
                                              1 => $LANG['title'][38], // Historique
                                              2 => $LANG['Menu'][30]), // Jounaux
                              array('value' => $this->fields["do_log"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".$LANG['setup'][137]."&nbsp;:</td><td>";
      Dropdown::showYesNo("debug",$this->fields["debug"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".$LANG["webservices"][2]."&nbsp;:</td><td>";
      autocompletionTextField($this,"pattern");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".$LANG['webservices'][3]."&nbsp;:</td><td>";
      echo "&nbsp;&nbsp;<input type='text' name='_start' value='".
            ($this->fields["ip_start"] ? long2ip($this->fields["ip_start"]) : '') .
            "' size='17'> - ";
      echo "<input type='text' name='_end' value='" .
            ($this->fields["ip_end"] ? long2ip($this->fields["ip_end"]) : '') .
            "' size='17'></td></tr>";

      echo "<tr class='tab_bg_1'><";
      echo "td >".$LANG['webservices'][4]."&nbsp;:</td><td>";
      autocompletionTextField($this, "username");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".$LANG['webservices'][5]."&nbsp;:</td><td>";
      echo "<input type='text' name='password' size='40' />";
      echo "</td></tr>";

      $this->showFormButtons($options);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";
   }


   function showMethods() {
      global $LANG, $WEBSERVICES_METHOD;

      echo "<div class='center'><br><table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='4'>".$LANG['webservices'][8]."</th></tr>";
      echo "<tr><th>".$LANG['webservices'][10]."</th>" .
           "<th>".$LANG['webservices'][12]."</th>" .
           "<th>".$LANG['webservices'][11]."</th>" .
           "<th>".$LANG['webservices'][13]."</th></tr>";

      // Allow all plugins to register their methods
      $WEBSERVICES_METHOD=array();
      doHook("webservices");

      foreach ($WEBSERVICES_METHOD as $method => $function) {
         // Display if MySQL REGEXP match
         if (countElementsInTable($this->getTable(), "ID='".$this->fields['id'].
                                  "' AND '".addslashes($method)."' REGEXP pattern")>0) {
            $result = $function;
            if (is_array($function)) {
               if ($tmp = isPluginItemType($function[0])) {
                  $plugin = $tmp['plugin'];
               } else {
                  $plugin="&nbsp;";
               }
               $result = implode('::',$function);
            } else if (preg_match('/^plugin_(.*)_method/', $function, $res)) {
               $plugin=$res[1];
            } else {
               $plugin="&nbsp;";
            }
            $call = (is_callable($function) ? $LANG['choice'][1] : $LANG['choice'][0]);
            $color = (is_callable($function) ? "greenbutton" : "redbutton");
            echo "<tr class='tab_bg_1'><td class='b'>$method</td><td>$plugin</td>".
                  "<td>$result</td><td class='center'>".
                  "<img src=\"".GLPI_ROOT."/pics/$color.png\" alt='ok'>&nbsp;$call</td></tr>";
         }
      }
      echo "</table></div>";
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['webservices'][0];

      $tab[1]['table']     = $this->getTable();
      $tab[1]['field']     = 'name';
      $tab[1]['linkfield'] = 'name';
      $tab[1]['name']      = $LANG['common'][16];
      $tab[1]['datatype']  = 'itemlink';

      $tab[3]['table']     = $this->getTable();
      $tab[3]['field']     = 'comment';
      $tab[3]['linkfield'] = 'comment';
      $tab[3]['name']      = $LANG['common'][25];
      $tab[3]['datatype']  = 'text';

      $tab[8]['table']     = $this->getTable();
      $tab[8]['field']     = 'is_active';
      $tab[8]['linkfield'] = 'is_active';
      $tab[8]['name']      = $LANG['webservices'][1];
      $tab[8]['datatype']  = 'bool';

      $tab[9]['table']     = $this->getTable();
      $tab[9]['field']     = 'do_log';
      $tab[9]['linkfield'] = 'do_log';
      $tab[9]['name']      = $LANG['webservices'][6];

      $tab[10]['table']     = $this->getTable();
      $tab[10]['field']     = 'deflate';
      $tab[10]['linkfield'] = 'deflate';
      $tab[10]['name']      = $LANG['webservices'][9];
      $tab[10]['datatype']  = 'bool';

      $tab[13]['table']     = $this->getTable();
      $tab[13]['field']     = 'ip';
      $tab[13]['linkfield'] = 'ip';
      $tab[13]['name']      = $LANG['networking'][14];

      $tab[17]['table']       = $this->getTable();
      $tab[17]['field']       = 'pattern';
      $tab[17]['linkfield']   = 'pattern';
      $tab[17]['name']        = $LANG['webservices'][2];

      return $tab;
   }

}

?>