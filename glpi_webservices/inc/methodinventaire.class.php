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

class PluginWebservicesMethodInventaire extends PluginWebservicesMethodCommon {

   //----------------------------------------------------//
   //----------------- Read methods --------------------//
   //--------------------------------------------------//

   /**
    * Get a list of objects
    * for an authenticated user
    *
    * @param $params array of options
    * @param $protocol the commonication protocol used
    */
   static function methodListObjects($params, $protocol) {
      global $DB, $CFG_GLPI;

      if (isset ($params['help'])) {
         return array('start'         => 'integer,optional',
                      'limit'         => 'integer,optional',
                      'name'          => 'string,optional',
                      'serial'        => 'string,optional',
                      'otherserial'   => 'string,optional',
                      'locations_id'  => 'integer,optional',
                      'location_name' => 'integer,optional',
                      'room'          => 'string (Location only)',
                      'building'      => 'string (Location only)',
                      'itemtype'      => 'string,mandatory',
                      'show_label'    => 'bool, optional (0 default)',
                      'help'          => 'bool,optional');
      }

      if (!getLoginUserID()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      //Must be superadmin to use this method
      if(!haveRight('config', 'w')){
         return self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED);
      }

      $resp = array();

      $start = 0;
      $limit = $_SESSION['glpilist_limit'];
      if (isset ($params['limit']) && is_numeric($params['limit'])) {
         $limit = $params['limit'];
      }
      if (isset ($params['start']) && is_numeric($params['start'])) {
         $start = $params['start'];
      }
      foreach (array('show_label','show_name') as $key) {
          $params[$key] = (isset($params[$key])?$params[$key]:false);
      }

      if (!isset($params['itemtype'])) {
         return self::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER, '', 'itemtype');
      }
      if (!class_exists($params['itemtype'])) {
         return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'itemtype');
      }
     
      //Fields to return to the client when search search is performed
      $params['return_fields'][$params['itemtype']] = array('id','name', 'interface', 'is_default',
                                                            'locations_id', 'otherserial', 'serial');

      $output = array();
      $item   = new $params['itemtype'];
      if (!$item->canView()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED, '');
      }
      $table = $item->getTable();

      //Restrict request
      if ($item->isEntityAssign()) {
         $where = getEntitiesRestrictRequest('WHERE', $table);
      } else {
         $where = "WHERE 1 ";
      }
      if ($item->maybeDeleted()) {
         $where .= " AND `$table`.`is_deleted` = '0'";
      }
      if ($item->maybeTemplate()) {
         $where .= " AND `$table`.`is_template` = '0'";
      }
      $left_join = "";
      if ($item->getField('entities_id') != NOT_AVAILABLE) {
         $left_join = " LEFT JOIN `glpi_entities` ON (`$table`.`entities_id` = `glpi_entities`.`id`) ";
      }
      $already_joined = array();
      $left_join.= self::listInventoryObjectsRequestLeftJoins($params, $item, $table, $already_joined).
                   getEntitiesRestrictRequest(" AND ", $table);

      $where = self::listInventoryObjectsRequestParameters($params, $item, $table, $where);
      $query = "SELECT `$table`.* FROM `$table`
                   $left_join
                   $where
                ORDER BY `id`
                LIMIT $start,$limit";

      foreach ($DB->request($query) as $data) {
         $tmp = array();
         $toformat = array('table' => $table, 'data'  => $data,
                           'searchOptions' => Search::getOptions($params['itemtype']),
                           'options' => $params);
         parent::formatDataForOutput($toformat, $tmp);
         $output[] = $tmp;
      }
      return $output;
   }


   /**
    * Get an object
    * for a authenticated user
    *
    * @param $params array of options
    * @param $protocol the commonication protocol used
    */
   static function methodGetObject($params, $protocol) {
      global $CFG_GLPI,$WEBSERVICE_LINKED_OBJECTS;

      if (isset ($params['help'])) {
         $options =  array('id'         => 'integer',
                           'help'       => 'bool,optional',
                           'show_label' => 'bool, optional',
                           'show_name'  => 'bool, optional');
          foreach ($WEBSERVICE_LINKED_OBJECTS as $option => $value) {
            $options[$option] = $value['help'];
          }
          return $options;
      }

      if (!getLoginUserID()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      $p['itemtype']      = '';
      $p['id']            = false;
      $p['return_fields'] = array();
      $p['show_label']    = $p['show_name'] = false;
      foreach ($params as $key => $value) {
         $p[$key]         = $value;
      }

      //Check mandatory parameters
      foreach (array('itemtype','id') as $mandatory_field) {
         if (!isset ($p[$mandatory_field])) {
            return self::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER, '',
                               $mandatory_field);
         }
      }

      //Check mandatory parameters validity
      if (!is_numeric($p['id'])) {
          return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '',
                                            'id=' . $p['id']);
      }
      if (!class_exists($p['itemtype'])) {
          return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '',
                                            'itemtype=' . $p['itemtype']);
      }

      $item = new $p['itemtype'];
      if (!$item->canView()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED, '', $params['itemtype']);
      }
      if (!$item->getFromDB($p['id'])
             || !$item->can($p['id'], 'r')) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTFOUND);
      }

      $output = array();
      $toformat = array('data'          => $item->fields,
                        'options'       => $p,
                        'searchOptions' => Search::getOptions($params['itemtype']),
                        'itemtype'      => $p['itemtype']);
      parent::formatDataForOutput($toformat, $output);
      self::processLinkedItems($output, $params, $p , $protocol, $toformat);
      return $output;
   }

   /**
    * Process itemtypes linked to the primary type
    * @param $output the array to be populated
    * @param $params
    * @param $p
    * @param $protocol
    * @param $toformat
    */
   static function processLinkedItems(&$output, $params, $p , $protocol, $toformat) {
      global $WEBSERVICE_LINKED_OBJECTS;

      //-------------------------------//
      //--- Process linked objects ---//
      //-----------------------------//
      foreach ($WEBSERVICE_LINKED_OBJECTS as $key => $option) {
         //If option is allowed and itemtype is allowed for this option
         if (isset($p[$key])
               && $p[$key] == 1
                  && class_exists($p['itemtype'])
                     && in_array($p['itemtype'], $option['allowed_types'])) {
            $toformat['options']['linked_itemtype'] = $option['itemtype'];
            $toformat['options']['source_itemtype'] = $p['itemtype'];
            $function_name = "get".$option['itemtype']."s";
            if (method_exists($option['class'], $function_name)) {
                $result = call_user_func(array($option['class'], $function_name),
                                         $protocol, $toformat, $p);
                if (!empty($result)) {
                   $output[$option['itemtype']] = $result;
                }
            }
         }
      }

   }

   static function getItems($protocol, $params = array(), $original_params = array()) {

      $flip = (isset($params['options']['flip_itemtypes'])
                  ?$params['options']['flip_itemtypes']:false);
      if (!$flip) {
         //Source itemtype (used to find the right _items table)
         $source_itemtype = $params['options']['source_itemtype'];
         //Linked itemtype : items to look for in the _items table
         $linked_itemtype = $params['options']['linked_itemtype'];
         $item = new $linked_itemtype();
          $source_item = new $source_itemtype();
          $fk = getForeignKeyFieldForTable($source_item->getTable());
      } else {
         //Source itemtype (used to find the right _items table)
         $linked_itemtype = $params['options']['source_itemtype'];
         //Linked itemtype : items to look for in the _items table
         $source_itemtype = $params['options']['linked_itemtype'];
         $item = new $source_itemtype();
         $linked_item = new $linked_itemtype();
         $fk = "items_id";
      }

      $output = array();
      foreach (getAllDatasFromTable('glpi_'.(strtolower($source_itemtype)).'s_items',
                                    "`itemtype`='".$linked_itemtype."' " .
                                       " AND `$fk` = '".addslashes_deep($params['data']['id'])."'") as $data) {

            $item->getFromDB($data['items_id']);
            $resp     = array();
            $toformat = array('data'          => $item->fields,
                              'searchOptions' => Search::getOptions(get_class($item)),
                              'options'       => $params['options']);
            parent::formatDataForOutput($toformat, $resp);
            $output[$item->fields['id']] = $resp;
      }
      return $output;
   }

   /**
    * Get netwok ports for an object
    * for a authenticated user
    *
    * @param protocol the commonication protocol used
    * @param $params : parameters as an array
    */
   static function getNetworkports($protocol, $params = array(), $original_params = array()) {
      global $DB;
      
      if (!haveRight("networking", "r")) {
         return array();
      }
      
      $item = new $params['options']['itemtype']();
      $resp = array();

      if ($item->can($params['data']['id'], 'r')) {
         //Get all ports for the object
         $ports = getAllDatasFromTable('glpi_networkports',
                                       "`itemtype`='".addslashes_deep($params['options']['itemtype']).
                                       "' AND `items_id`='".addslashes_deep($params['data']['id'])."'");
         $output = array();
         $oneport = new NetworkPort();
         foreach ($ports as $port) {
            $resp = array();
            $toformat = array('data'          => $port,
                              'searchOptions' => Search::getOptions('NetworkPort'),
                              'options'       => $params['options']);
            parent::formatDataForOutput($toformat, $resp);

            //Get VLANS
            $port_vlan = new NetworkPort_Vlan();
            $onevlan = new Vlan;
            foreach ($port_vlan->getVlansForNetworkPort($port['id']) as $vlans_id ) {
               $onevlan->getFromDB($vlans_id);
               $vlan = array();
               $params_vlan = array('data'           => $onevlan->fields,
                                     'searchOptions' => Search::getOptions('Vlan'),
                                     'options'       => $params['options']);
               parent::formatDataForOutput($params_vlan, $vlan);
               $resp['Vlan'][$vlans_id] = $vlan;
            }

            $output[$port['id']] = $resp;
         }
      }

      return $output;
   }

   static function getSoftwares($protocol, $params = array(), $original_params = array()) {
      global $DB, $WEBSERVICE_LINKED_OBJECTS;

      if (!haveRight("software", "r")) {
         return array();
      }
      $item = new $params['options']['itemtype']();
      $resp = array();
      $software = new Software();

      //Store softwares, versions and licenses
      $softwares = array();

      if ($item->can($params['data']['id'], 'r') && $software->can(-1,'r')) {

         foreach (array('SoftwareVersion', 'SoftwareLicense') as $itemtype) {
            $link_table = "glpi_computers_".addslashes_deep(strtolower($itemtype))."s";
            $table = getTableForItemType($itemtype);
            $query = "SELECT DISTINCT `gsv`.*
                      FROM `".addslashes_deep($link_table)."` AS gcsv, `".addslashes_deep($table)."` AS gsv
                      WHERE `gcsv`.`computers_id` = '".addslashes_deep($params['data']['id'])."'
                              AND `gcsv`.`".getForeignKeyFieldForTable($table)."` = `gsv`.`id`
                     GROUP BY `gsv`.`softwares_id`
                     ORDER BY `gsv`.`softwares_id` ASC";

            foreach ($DB->request($query) as $version_or_license) {

               //Software is not yet in the list
               if (!isset($softwares['Software'][$version_or_license['softwares_id']])) {
                  $software->getFromDB($version_or_license['softwares_id']);
                  $toformat = array('data'          => $software->fields,
                                    'searchOptions' => Search::getOptions('Software'),
                                    'options'       => $params['options']);
                  $tmp = array();
                  parent::formatDataForOutput($toformat, $tmp);
                  $softwares['Software'][$version_or_license['softwares_id']] = $tmp;

               }

               $toformat2 = array('data'          => $version_or_license,
                                  'searchOptions' => Search::getOptions($itemtype),
                                  'options'       => $params['options']);
               $tmp = array();
               parent::formatDataForOutput($toformat2, $tmp);
               $softwares['Software'][$version_or_license['softwares_id']][$itemtype][$version_or_license['id']]
                  = $tmp;

            }
         }
      }
      return $softwares;
   }

   static function getSoftwareVersions($protocol, $params = array(), $original_params = array()) {
      return self::getSoftwareVersionsOrLicenses($protocol, $params, new SoftwareVersion());
   }

   static function getSoftwareLicenses($protocol, $params = array(), $original_params = array()) {
      return self::getSoftwareVersionsOrLicenses($protocol, $params, new SoftwareLicense());
   }

   static function getSoftwareVersionsOrLicenses($protocol, $params = array(), CommonDBTM $item) {
      global $DB;

      $software = new Software;
      $resp = array();

      if ($software->can($params['data']['id'], 'r')) {
         $query = "SELECT `gsv`.*
                   FROM `".addslashes_deep($item->getTable())."` AS gsv, `glpi_softwares` AS gs
                   WHERE `gsv`.`softwares_id` = `gs`.`id`
                      AND `gsv`.`softwares_id` = '".addslashes_deep($params['data']['id'])."'
                   GROUP BY `gsv`.`softwares_id`
                   ORDER BY `gsv`.`softwares_id` ASC";

        $toformat = array('searchOptions' => Search::getOptions(get_class($item)),
                          'options'       => $params['options']);

         foreach ($DB->request($query) as $version_or_license) {
           $toformat['data'] = $version_or_license;
           $result           = array();

           parent::formatDataForOutput($toformat, $result);
           $resp[$version_or_license['id']] = $result;
         }
      }

      return $resp;
   }

   static function getMonitors($protocol, $params = array(), $original_params = array())  {
      return self::getItems($protocol, $params);
   }

   static function getPrinters($protocol, $params = array(), $original_params = array())  {
      return self::getItems($protocol, $params);
   }

   static function getPhones($protocol, $params = array(), $original_params = array())  {
      return self::getItems($protocol, $params);
   }

   static function getPeripherals($protocol, $params = array(), $original_params = array())  {
      return self::getItems($protocol, $params);
   }

   static function getDocuments($protocol, $params = array(), $original_params = array())  {
      $params['options']['flip_itemtypes'] = true;
      return self::getItems($protocol, $params);
   }

   /**
    * Check standard parameters for get requests
    *
    * @param $params the input parameters
    * @param $protocol the commonication protocol used
    *
    * @return 1 if checks are ok, an error if checks failed
   **/
   static function checkStandardParameters($params, $protocol) {

      if (!getLoginUserID()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      if (!isset ($params['id'])) {
         return self::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER, '', 'id');

      } else if (!isset ($params['itemtype'])) {
         return self::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER, '', 'itemtype');

      } else {
         if (!is_numeric($params['id'])) {
            return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '',
                               $params['itemtype'].'=' . $params['id']);
         }
      }
      return 1;
   }

   //-----------------------------------------------//
   //--------- Itemtype independant methods -------//
   //---------------------------------------------//

   /**
    * Contruct parameters restriction for listInventoryObjects sql request
    *
    * @param $params the input parameters
    * @param $item CommonDBTM object
    * @param $table
    * @param $where
   **/
   static function listInventoryObjectsRequestParameters($params, CommonDBTM $item, $table,
                                                         $where="WHERE 1") {

      $already_used = array();

      foreach ($params as $key => $value) {
         //Key representing the FK associated with the _name value
         $key_transformed = preg_replace("/_name/", "s_id", $key);
         $fk_table = getTableNameForForeignKeyField($key);
         $option   = $item->getSearchOptionByField('field', $key_transformed);

         if (!empty($option)) {
            if (!in_array($key, $already_used)
               && isset ($params[$key])
               && $params[$key]
                  && $item->getField($option['linkfield']) != NOT_AVAILABLE) {

               if (getTableNameForForeignKeyField($key)) {
                  $where .= " AND `$table`.`$key`='" . addslashes_deep($params[$key]) . "'";

               } else {
                  //
                  if (($key != $key_transformed) || ($table != $option['table'])) {
                     $where .= " AND `".addslashes_deep($option['table'])."`.`".addslashes_deep($option['field']);
                     $where .= "` LIKE '%" . addslashes_deep($params[$key]) . "%'";

                  } else {
                     $where .= " AND `$table`.`$key` LIKE '%" . addslashes_deep($params[$key]) . "%'";
                  }
               }
               $already_used[] = $key;

            }
         }
      }

      return $where;
   }

   /**
    * Contruct parameters restriction for listInventoryObjects sql request
    *
    * @param $params the input parameters
    * @param $item CommonDBTM object
    * @param $table
   **/
   static function listInventoryObjectsRequestLeftJoins($params, CommonDBTM $item, $table, 
                                                        $already_joined) {

      $join           = "";

      foreach ($params as $key => $value) {

         //Key representing the FK associated with the _name value
         $key_transformed = preg_replace("/_name/", "s_id", $key);
         $option = $item->getSearchOptionByField('field', $key_transformed);

         if (!empty($option)
            && !isset($option['common'])
               && $table != $option['table']
                  && !in_array($option['table'], $already_joined)) {
            $join.= " \nINNER JOIN `".addslashes_deep($option['table']).
                     "` ON (`".addslashes_deep($table)."`.`".addslashes_deep($option['linkfield']).
                        "` = `".addslashes_deep($option['table'])."`.`id`) ";
            $already_joined[] = $option['table'];
         }

      }
      return $join;
   }

   /**
    * List inventory objects (global search)
    *
    * @param $params the input parameters
    * @param $protocol the commonication protocol used
    *
   **/
   static function methodListInventoryObjects($params, $protocol) {
      global $DB, $CFG_GLPI;

      //Display help for this function
      if (isset ($params['help'])) {

         foreach (Search::getOptions('States') as $itemtye => $option) {

            if (!isset($option['common'])) {
               if (isset($option['linkfield'])
                  && $option['linkfield'] != '' ) {

                  if (in_array($option['field'], array('name', 'completename'))) {
                     $fields[$option['linkfield']] = 'integer,optional';
                     $name_associated = str_replace("s_id", "_name", $option['linkfield']);
                     if (!isset($option['datatype']) || $option['datatype'] == 'text') {
                        $fields[$name_associated] = 'string,optional';
                     }
                  } else {
                     $fields[$option['field']] = 'string,optional';
                  }

               } else {
                  $fields[$option['field']] = 'string,optional';
               }

            }
         }
         $fields['start'] = 'integer,optional';
         $fields['limit'] = 'integer,optional';
         return $fields;
      }

      if (!getLoginUserID()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      //Must be superadmin to use this method
      if(!haveRight('config', 'w')){
         return self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED);
      }

      $resp = array();

      $itemtypes = array();
      //If several itemtypes given, build an array
      if (isset($params['itemtype'])) {
         if (!is_array($params['itemtype'])) {
            $itemtypes = array($params['itemtype']);

         } else {
            $itemtypes = $params['itemtype'];
         }
      } else {
         $itemtypes = plugin_webservices_getTicketItemtypes();
      }
      
      //Check read right on each itemtype
      foreach ($itemtypes as $itemtype) {
         $item = new $itemtype();
         if (!$item->canView()) {
            $key = array_search($itemtype, $itemtypes);
            unset($itemtypes[$key]);
            $resp[] = self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED, '', $itemtype);
         }
      }
 
      //If nothing in the array, no need to go further !
      if (empty($itemtypes)) {
         return $resp;
      }

      $start = 0;
      $limit = $_SESSION['glpilist_limit'];
      if (isset ($params['limit']) && is_numeric($params['limit'])) {
         $limit = $params['limit'];
      }
      if (isset ($params['start']) && is_numeric($params['start'])) {
         $start = $params['start'];
      }

      $first = true;
      $query = "";

      foreach ($itemtypes as $itemtype) {
         if (in_array($itemtype, $itemtypes)) {
            $item        = new $itemtype();
            $item->getEmpty();
            $table        = getTableForItemType($itemtype);
            $already_joined = array();
            if (!$first) {
               $query    .= " UNION ";
            }
            
            $query.= "\nSELECT `".addslashes_deep($table)."`.`name`,
                               `".addslashes_deep($table)."`.`id`,
                               `glpi_entities`.`completename` AS entities_name,
                               `glpi_entities`.`id` AS entities_id,
                               '".addslashes_deep($itemtype)."' AS itemtype";
            if(FieldExists($table, 'serial')) {
               $query.= ", `".addslashes_deep($table)."`.`serial`";
            } else {
               $query.= ", '' as `serial`";
            }
            if(FieldExists($table, 'otherserial')) {
               $query.= ", `".addslashes_deep($table)."`.`otherserial`";
            } else {
               $query.= ", '' as `otherserial`";
            }
            $query .= " FROM `".addslashes_deep($table)."`";
            if (!in_array($table, $already_joined)) {
               $query.= " LEFT JOIN `glpi_entities` ON (`".addslashes_deep($table).
                           "`.`entities_id` = `glpi_entities`.`id`)";
               $already_joined[] = 'glpi_entities';
            }
            $query.= self::listInventoryObjectsRequestLeftJoins($params, $item, $table, 
                                                                $already_joined).
                      getEntitiesRestrictRequest(" AND ", $table);
            if ($item->maybeTemplate()) {
               $query .= " AND `".addslashes_deep($table)."`.`is_template`='0' ";

            }
            if ($item->maybeDeleted()) {
               $query .= " AND `".addslashes_deep($table)."`.`is_deleted`='0' ";

            }
            $query .= self::listInventoryObjectsRequestParameters($params, $item, $table);
            $first  = false;
         }
      }
      $query .= " ORDER BY `name`
                  LIMIT $start, $limit";

      foreach ($DB->request($query) as $data) {
         if (!class_exists($data['itemtype'])) {
            continue;
         }
         $item                  = new $data['itemtype']();
         $data['itemtype_name'] = html_clean($item->getTypeName());
         $resp[]                = $data;
      }
      return $resp;
   }


   //----------------------------------------------------//
   //----------------- Write methods --------------------//
   //--------------------------------------------------//

   /**
    * Create inventory objects
    *
    * @param $params the input parameters
    * @param $protocol the commonication protocol used
    *
   **/
   static function methodCreateObjects($params, $protocol) {
      global $CFG_GLPI;

      
      if (isset ($params['help'])) {
         if(!is_array($params['help'])) {
            return array('fields'   => 'array, mandatory',
                         'help'     => 'bool, optional');
         } else {
            $resp = array();
            foreach($params['help'] as $itemtype) {
               $item = new $itemtype();
               //If user has right access on this itemtype
               if ($item->canCreate()) {
                  $item->getEmpty();
                  $blacklisted_field = array($item->getIndexName());
                  foreach($item->fields as $field => $default_v) {
                     if(!in_array($field,$blacklisted_field)) {
                        $resp[$itemtype][] = $field;
                     }
                  }
               }
            }
            return $resp;
         }
      }

      if (!getLoginUserID()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      //Must be superadmin to use this method
      if(!haveRight('config', 'w')){
         $errors[$itemtype][] = self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED);
      }

      if ((!isset ($params['fields']) || empty($params['fields'])
            || !is_array($params['fields']))) {
         return self::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER, '', 'fields');
      }

      $datas   = array();
      $resp    = array();
      $errors  = array();

      foreach($params['fields'] as $itemtype => $items) {
         foreach($items as $fields) {
            $item = new $itemtype;

            foreach($fields as $field => $value) {
               if($item->isField($field)
                     || in_array($field,array('withtemplate'))) {
                  $datas[$field] = $value;
               }
            }

            if($item->isField('entities_id')
                  && !isset($datas['entities_id'])
                  && isset($_SESSION["glpiactive_entity"])) {
               $datas['entities_id'] = $_SESSION["glpiactive_entity"];
            }

            if(!$item->can(-1, 'w', $datas)){
               $errors[$itemtype][] = self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED,
                                                  '', self::getDisplayError());
            } else {
               if ($newID = $item->add($datas)) {

                  $resp[$itemtype][] = self::methodGetObject(
                                                array('itemtype' => $itemtype, 'id' => $newID),
                                                $protocol);
               } else {
                  $errors[$itemtype][] = self::Error($protocol, WEBSERVICES_ERROR_FAILED,
                                                     '', self::getDisplayError());
               }
            }
         }
      }

      if (count($errors)) {
         $resp = array($resp,$errors);
      }

      return $resp;
   }

   /**
    * Delete inventory objects
    * @param params the input parameters
    * @param protocol the commonication protocol used
    */
   static function methodDeleteObjects($params, $protocol) {
      global $CFG_GLPI;

      if (isset($params['help'])) {
         return array('fields'   => 'array, mandatory',
                      'help'     => 'bool, optional');
      }

      if (!getLoginUserID()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      //Must be superadmin to use this method
      if(!haveRight('config', 'w')){
         return self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED);
      }

      if ((!isset ($params['fields']) || empty($params['fields'])
            || !is_array($params['fields']))) {
         return self::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER, '', 'fields');
      }

      $resp    = array();
      $errors  = array();

      foreach($params['fields'] as $itemtype => $items) {
         foreach($items as $items_id => $force) {
            $item = new $itemtype();
            if(!$item->can($items_id, 'd')){
               $errors[$itemtype][$items_id] = self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED,
                                                           '', self::getDisplayError());
            } else {
               $resp[$itemtype][$items_id] = $item->delete(array('id' => $items_id), $force);
            }
         }
      }

      if (count($errors)) {
         $resp = array($resp, $errors);
      }

      return $resp;
   }

   /**
    * Update inventory objects
    *
    * @param $params the input parameters
    * @param $protocol the commonication protocol used
    *
   **/
   static function methodUpdateObjects($params, $protocol) {
      global $CFG_GLPI;

      if (isset ($params['help'])) {
         return array('fields'   => 'array, mandatory',
                      'help'     => 'bool, optional');
      }

      if (!getLoginUserID()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      if ((!isset ($params['fields']) || empty($params['fields'])
            || !is_array($params['fields']))) {
         return self::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER, '', 'fields');
      }

      if(!isset($_SESSION["glpi_currenttime"])) {
         $_SESSION["glpi_currenttime"] = date("Y-m-d H:i:s");
      }

      $resp    = array();
      $datas   = array();
      $errors  = array();

      foreach($params['fields'] as $itemtype => $items) {
         foreach($items as $fields) {
            $item = new $itemtype;
            $id_item = $item->getIndexName();

            if(!isset($fields[$id_item])) {
               $errors[$itemtype][] = self::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER, '',
                                                  'id');
            } else {
               if(!$item->getFromDB($fields[$id_item])) {
                  $errors[$itemtype][] =self::Error($protocol, WEBSERVICES_ERROR_FAILED,
                                                     '',self::getDisplayError());
               }
            }
            $datas = $item->fields;
            foreach($fields as $field => $value) {
               $datas[$field] = $value;
            }


            if(!$item->can($fields[$id_item],'w',$datas)){
               $errors[$itemtype][] = self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED,
                                                  '',self::getDisplayError());
            } else {
               if ($item->update($datas)) {
                  $resp[$itemtype][] = self::methodGetObject(
                                                array('itemtype' => $itemtype,
                                                      'id'       => $fields[$id_item]),
                                                $protocol);
               } else {
                  $errors[$itemtype][] = self::Error($protocol, WEBSERVICES_ERROR_FAILED,
                                                     '',self::getDisplayError());
               }
            }
         }
      }

      if (count($errors)) {
         $resp = array($resp,$errors);
      }

      return $resp;
   }

   /**
    * Link inventory object to another one
    *
    * @param $params the input parameters
    * @param $protocol the commonication protocol used
    *
   **/
   static function methodLinkObjects($params, $protocol) {
      global $CFG_GLPI;

      if (isset ($params['help'])) {
         return array(  'fields' => 'array, mandatory',
                        'help'   => 'bool, optional');
      }

      if (!getLoginUserID()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      //Must be superadmin to use this method
      if(!haveRight('config', 'w')){
         return self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED);
      }

      if ((!isset ($params['fields']) || empty($params['fields'])
            || !is_array($params['fields']))) {
         return self::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER, '', 'fields');
      }

      if(!isset($_SESSION["glpi_currenttime"])) {
         $_SESSION["glpi_currenttime"] = date("Y-m-d H:i:s");
      }

      $resp    = array();
      $errors  = array();

      foreach($params['fields'] as $links) {
         if(!in_array($links['from_item']['itemtype'],array('Computer'))
               && !preg_match("/Device/",$links['from_item']['itemtype'])) {
            $errors[] = self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED,
                                    '',self::getDisplayError());
         }

         switch($links['from_item']['itemtype']) {

            case 'Computer':

                  // Direct connections
                  if(in_array($links['to_item']['itemtype'],
                        array('Printer','Monitor','Peripheral','Phone'))) {

                     $comp_item = new Computer_Item;

                     $data = array();
                     $data['items_id']       = $links['to_item']['id'];
                     $data['computers_id']   = $links['from_item']['id'];
                     $data['itemtype']       = $links['to_item']['itemtype'];

                     if(!$comp_item->can(-1,'w',$data)){
                        $errors[] = self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED,
                                                   '',self::getDisplayError());
                     } else {
                        if ($comp_item->add($data)) {
                           $resp['Computer'][$data['computers_id']] =
                              self::methodGetObject(
                                          array('itemtype'        => 'Computer',
                                                'id'              => $data['computers_id'],
                                                'with_printer'    => 1,
                                                'with_monitor'    => 1,
                                                'with_phone'      => 1,
                                                'with_peripheral' => 1),
                                          $protocol);
                        } else {
                           $errors[] = self::Error($protocol, WEBSERVICES_ERROR_FAILED,
                                                   '',self::getDisplayError());
                        }
                     }
                  }

                  // Device connection
                  if(preg_match("/Device/",$links['to_item']['itemtype'])) {
                     $comp_device = new Computer_Device;

                     $links_field = getPlural(strtolower($links['to_item']['itemtype']))."_id";

                     $data = array();
                     $data['computers_id']   = $links['from_item']['id'];
                     $data[$links_field]     = $links['to_item']['id'];
                     $data['itemtype']       = $links['to_item']['itemtype'];

                     if(!isset($links['to_item']['quantity'])
                           || !is_numeric($links['to_item']['quantity'])) {
                        $quantity = 1;
                     } else {
                        $quantity = $links['to_item']['quantity'];
                     }

                     if(isset($links['to_item']['specificity'])){
                        if(!is_numeric($links['to_item']['specificity'])) {
                           $errors[] = self::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER,
                                                      '', 'specificity');
                        } else {
                           $data['specificity'] = $links['to_item']['specificity'];
                        }
                     }

                     $linked = false;

                     for($i=0;$i<$quantity;$i++) {
                        if(!$comp_device->can(-1,'w',$data)){
                           $errors[] = self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED,
                                                      '',self::getDisplayError());
                        } else {
                           if ($comp_device->add($data)) {
                              $linked = true;
                           }
                        }
                     }

                     if($linked){
                        $resp['Computer'][$data['computers_id']] =
                           self::methodGetObject(
                                       array('itemtype' => 'Computer',
                                             'id'=>$data['computers_id']),
                                       $protocol);
                     } else {
                        $errors[] = self::Error($protocol, WEBSERVICES_ERROR_FAILED,
                                                   '',self::getDisplayError());
                     }
                  }
                  //other link object
               break;

            //itemtype
         }

      }

      if (count($errors)) {
         $resp = array($resp,$errors);
      }

      return $resp;

   }

   //----------------------------------------------------//
   //----------- Deprecated methods --------------------//
   //--------------------------------------------------//


   /**
    * This method is deprecated. Please use listObjects instead
    * @deprecated since 1.1.0
   **/
   static function methodListComputers($params, $protocol) {
      global $DB, $CFG_GLPI;

      if (isset ($params['help'])) {
         return array('warning', 'This method is deprecated ! Please use listObjects instead',
                      'count'       => 'bool,optional',
                      'start'       => 'integer,optional',
                      'limit'       => 'integer,optional',
                      'name'        => 'string,optional',
                      'serial'      => 'string,optional',
                      'otherserial' => 'string,optional',
                      'help'        => 'bool,optional');
      }

      if (!getLoginUserID()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      $where = getEntitiesRestrictRequest(" WHERE ", "glpi_computers");

      if (isset ($params['name'])) {
         $where .= " AND `glpi_computers`.`name` LIKE '%" . addslashes_deep($params['name']) . "%'";
      }
      if (isset ($params['serial'])) {
         $where .= " AND `glpi_computers`.`serial` LIKE '%" . addslashes_deep($params['serial']) . "%'";
      }
      if (isset ($params['otherserial'])) {
         $where .= " AND `glpi_computers`.`otherserial` LIKE '%" . addslashes_deep($params['otherserial']) . "%'";
      }

      $resp = array ();
      if (isset ($params['count'])) {
         $query = "SELECT COUNT(DISTINCT `glpi_computers`.`id`) AS count
                   FROM `glpi_computers` " .
                   $where;

         foreach ($DB->request($query) as $data) {
            $resp = $data;
         }
      } else {
         $start = 0;
         $limit = $_SESSION['glpilist_limit'];
         if (isset ($params['limit']) && is_numeric($params['limit'])) {
            $limit = $params['limit'];
         }
         if (isset ($params['start']) && is_numeric($params['start'])) {
            $start = $params['start'];
         }

         $query = "SELECT DISTINCT `glpi_computers`.`id` AS `id`,
                          `glpi_computers`.`name`,
                          `glpi_computers`.`serial`,
                          `glpi_computers`.`otherserial`,
                          `glpi_computers`.`entities_id`,
                          `glpi_entities`.`completename` AS entities_name,
                          `glpi_locations`.`completename` AS `locations_name`
                   FROM `glpi_computers`
                   LEFT JOIN `glpi_entities`
                        ON (`glpi_computers`.`entities_id` = `glpi_entities`.`id`)
                   LEFT JOIN `glpi_locations`
                        ON (`glpi_computers`.`locations_id` = `glpi_locations`.`id`)
                   $where
                   ORDER BY `glpi_computers`.`id`
                   LIMIT $start,$limit";

         foreach ($DB->request($query) as $data) {
            $resp[] = $data;
         }
      }
      return $resp;
   }

   /**
    * Get a Computer for an authenticated user
    *
    * @param $params array of options (computer, id2name)
    * @param $protocol the commonication protocol used
    *
    * @return hashtable -fields of glpi_computer
    * @deprecated since 1.1.0
    */
   static function methodGetComputer($params, $protocol) {

      if (isset ($params['help'])) {
         return array('id'           => 'integer',
                      'computer'     => 'integer, optional (deprecated, use id instead)',
                      'id2name'      => 'bool,optional',
                      'infocoms'     => 'bool,optional',
                      'contracts'    => 'bool,optional',
                      'networkports' => 'bool,optionnal',
                      'phones'       => 'bool,optionnal',
                      'help'         => 'bool,optional');
      }

      if (!getLoginUserID()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      //Must be superadmin to use this method
      if(!haveRight('config', 'w')){
         return self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED);
      }

      $params['itemtype'] = 'Computer';
      //param 'computer' is deprecated. If still in use, then initialize 'id' instead
      //if 'id' is defined, then ignore 'computer'
      if (isset($params['computer']) && !isset($param['id'])) {
         $params['id'] = $params['computer'];
      }

      $computer = new Computer();

      if (!isset ($params['id'])) {
         return self::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER, '', 'id');
      } else {
         if (!is_numeric($params['id'])) {
            return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '',
                                            'computer=' . $params['id']);
         } else {
            if (!$computer->getFromDB($params['id'])
                || !$computer->can($params['id'], 'r')) {
               return self::Error($protocol, WEBSERVICES_ERROR_NOTFOUND);
            }
         }
      }

      $resp = $computer->fields;

      // TODO : more dropdown value
      if (isset($params['id2name'])) {
         $resp['autoupdatesystems_name'] = html_clean(Dropdown::getDropdownName('glpi_autoupdatesystems',
                                                         $computer->fields['autoupdatesystems_id']));
         $resp['networks_name'] = html_clean(Dropdown::getDropdownName('glpi_networks',
                                                             $computer->fields['networks_id']));
         $resp['domains_name'] = html_clean(Dropdown::getDropdownName('glpi_domains',
                                                            $computer->fields['domains_id']));
         $resp['manufacturers_name'] = html_clean(Dropdown::getDropdownName('glpi_manufacturers',
                                                               $computer->fields['manufacturers_id']));
         $resp['states_name'] = html_clean(Dropdown::getDropdownName('glpi_states',
                                                           $computer->fields['states_id']));
      }
      self::getRelatedObjects($params,$protocol,$resp);
      return $resp;
   }

   /**
    * Get a Network device
    * for a authenticated user
    *
    * @param $params array of options (id, id2name, etc)
    * @param protocol the commonication protocol used
    * @return hashtable -fields of glpi_computer
    * @deprecated since 1.1.0
    */
   static function methodGetNetworkEquipment($params, $protocol) {

      if (isset ($params['help'])) {
         return array('id'           => 'integer, mandatory',
                      'id2name'      => 'bool,optional',
                      'infocoms'     => 'bool,optional',
                      'contracts'    => 'bool,optional',
                      'networkports' => 'bool,optionnal',
                      'help'         => 'bool,optional');
      }
      if (!getLoginUserID()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      $params['itemtype']  = 'NetworkEquipment';
      $networkequipment    = new NetworkEquipment();


      if (!isset ($params['id'])) {
         return self::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER, '', 'id');
      } else {
         if (!is_numeric($params['id'])) {
            return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '',
                                            'Networkequipment=' . $params['id']);
         } else {
            if (!$networkequipment->getFromDB($params['id'])
                || !$networkequipment->can($params['id'], 'r')) {
               return self::Error($protocol, WEBSERVICES_ERROR_NOTFOUND);
            }
         }
      }

      $resp = $networkequipment->fields;

      // TODO : more dropdown value
      if (isset($params['id2name'])) {
         $resp['networks_name'] = html_clean(Dropdown::getDropdownName('glpi_networks',
                                                             $networkequipment->fields['networks_id']));
         $resp['domains_name'] = html_clean(Dropdown::getDropdownName('glpi_domains',
                                                            $networkequipment->fields['domains_id']));
         $resp['manufacturers_name'] = html_clean(Dropdown::getDropdownName('glpi_manufacturers',
                                                               $networkequipment->fields['manufacturers_id']));
         $resp['states_name'] = html_clean(Dropdown::getDropdownName('glpi_states',
                                                           $networkequipment->fields['states_id']));
         $resp['networkequipmentmodels_name'] = html_clean(Dropdown::getDropdownName('glpi_networkequipmentmodels',
                                                           $networkequipment->fields['networkequipmentmodels_id']));
         $resp['networkequipmenttypes_name'] = html_clean(Dropdown::getDropdownName('glpi_networkequipmenttypes',
                                                           $networkequipment->fields['networkequipmenttypes_id']));
         $resp['networkequipmentfirmwares_name'] = html_clean(Dropdown::getDropdownName('glpi_networkequipmentfirmwares',
                                                           $networkequipment->fields['networkequipmentfirmwares_id']));
         $resp['groups_name'] = html_clean(Dropdown::getDropdownName('glpi_groups',
                                                           $networkequipment->fields['groups_id']));

      }
      self::getRelatedObjects($params,$protocol,$resp);
      return $resp;
   }


   /**
    * Get Infocom for a Computer
    * This method is deprecated. Please use getInfocoms() instead !
    * for an authenticated user
    *
    * @param $params array of options (computer)
    * @param $protocol the communication protocol used
    *
    * @return hashtable -fields of glpi_computer
    * @deprecated since 1.1.0
    */
   static function methodGetComputerInfoComs($params, $protocol) {
      $params['itemtype'] = 'Computer';
      return self::methodGetInfocoms($params,$protocol);
   }


   /**
    * Get contracts for a Computer
    * This method is deprecated. Please use getContract() instead !
    * for an authenticated user
    *
    * @param $params array of options (computer)
    * @param $protocol the commonication protocol used
    *
    * @return hashtable -fields of glpi_contracts
    * @deprecated since 1.1.0
   **/
   static function methodGetComputerContracts($params, $protocol) {
      $params['itemtype'] = "Computer";
      return self::methodGetContracts($params,$protocol);
   }

   /**
    * List all users of the current entity, with search criterias
    * for an authenticated user
    *
    * @param $params array of options (user, group, location, login, name)
    * @param $protocol the commonication protocol used
    *
    * @return array of hashtable
   **/
   static function methodListUsers($params, $protocol) {
      global $DB, $CFG_GLPI;

      if (isset ($params['help'])) {
         return array('count'    => 'bool,optional',
                      'start'    => 'integer,optional',
                      'limit'    => 'integer,optional',
                      'order'    => 'string,optional',
                      'entity'   => 'integer,optional',
                      'parent'   => 'bool,optional',
                      'user'     => 'integer,optional',
                      'group'    => 'integer,optional',
                      'location' => 'integer,optional',
                      'login'    => 'string,optional',
                      'name'     => 'string,optional',
                      'help'     => 'bool,optional');
      }

      if (!getLoginUserID()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      $orders = array(
         'id'     => '`glpi_users`.`id`',
         'name'   => ($_SESSION['glpinames_format'] == FIRSTNAME_BEFORE
                      ? '`glpi_users`.`firstname`,`glpi_users`.`realname`'
                      : '`glpi_users`.`realname`,`glpi_users`.`firstname`'),
         'login'  => '`glpi_users`.`name`',
      );

      $parent = 1;
      if (isset($params['parent'])) {
         $parent = ($params['parent'] ? 1 : 0);
      }

      if (isset($params['entity'])) {
         if (!haveAccessToEntity($params['entity'])) {
            return self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED, '', 'entity');
         }
         $ent = $params['entity'];
      } else {
         $ent = '';
      }

      $query = "LEFT JOIN `glpi_profiles_users`
                     ON (`glpi_users`.`id` = `glpi_profiles_users`.`users_id`)
                WHERE `glpi_users`.`is_deleted` = '0'
                  AND `glpi_users`.`is_active` = '1' ".
                  getEntitiesRestrictRequest('AND', "glpi_profiles_users", '', $ent, $parent);

      if (isset($params['user']) && is_numeric($params['user'])) {
         $query .= " AND `glpi_users`.`id` = '" . $params['user'] . "'";
      }
      if (isset($params['group']) && is_numeric($params['group'])) {
         $query .= " AND `glpi_users`.`id` IN (SELECT `users_id`
                                               FROM `glpi_groups_users`
                                               WHERE `groups_id` = '" . $params['group'] . "')";
      }
      if (isset($params['location']) && is_numeric($params['location'])) {
         $query .= " AND `glpi_users`.`locations_id` = '" . $params['location'] . "'";
      }
      if (isset($params['login'])) {
         $query .= " AND `glpi_users`.`name` LIKE '" . addslashes($params['login']) . "'";
      }
      if (isset($params['name'])) {
         if ($_SESSION['glpinames_format'] == FIRSTNAME_BEFORE) {
            $query .= " AND CONCAT(`glpi_users`.`firstname`,' ',`glpi_users`.`realname`)";
         } else {
            $query .= " AND CONCAT(`glpi_users`.`realname`,' ',`glpi_users`.`firstname`)";
         }
         $query .= " LIKE '" . addslashes($params['name']) . "'";
      }

      $resp = array ();
      if (isset($params['count'])) {
         $query = "SELECT COUNT(DISTINCT `glpi_users`.`id`) AS count
                   FROM `glpi_users`
                   $query";

         $resp = $DB->request($query)->next();
      } else {
         $start = 0;
         $limit = $_SESSION['glpilist_limit'];
         if (isset($params['limit']) && is_numeric($params['limit'])) {
            $limit = $params['limit'];
         }
         if (isset($params['start']) && is_numeric($params['start'])) {
            $start = $params['start'];
         }
         if (isset($params['order']) && isset($orders[$params['order']])) {
            $order = $orders[$params['order']];
         } else {
            $order = $orders['id'];
         }

         $query = "SELECT DISTINCT(`glpi_users`.`id`) AS id, `glpi_users`.`name`, `firstname`,
                          `realname`, `email`, `phone`, `glpi_users`.`locations_id`,
                          `glpi_locations`.`completename` AS locations_name
                   FROM `glpi_users`
                   LEFT JOIN `glpi_locations` ON (`glpi_users`.`locations_id` = `glpi_locations`.`id`)
                   $query
                   ORDER BY $order
                   LIMIT $start,$limit";

         foreach ($DB->request($query) as $data) {
            $data['displayname'] = formatUserName(0, $data['name'], $data['realname'], $data['firstname']);
            $resp[] = $data;
         }
      }

      return $resp;
   }


   /**
    * Get a Document the authenticated user can view
    *
    * @param $params array of options (document, ticket)
    * @param $protocol the commonication protocol used
    *
    * @return a hashtable
   **/
   static function methodGetDocument($params, $protocol) {

      if (isset ($params['help'])) {
         return array('document' => 'integer,mandatory',
                      'ticket'   => 'interger,optional',
                      'id2name'  => 'bool,optional',
                      'help'     => 'bool,optional');
      }

      $doc = new Document();

      // Authenticated ?
      if (!getLoginUserID()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }
      // Option parameter ticket
      if (isset ($params['ticket']) && !is_numeric($params['ticket'])) {
         return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '','ticket=' . $params['ticket']);
      }
      $options=array();
      if (isset ($params['ticket'])) {
         $options['tickets_id'] = $params['ticket'];
      }
      // Mandatory parameter document
      if (!isset ($params['document'])) {
         return self::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER, '', 'document');
      }
      if (!is_numeric($params['document'])) {
         return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '','document=' . $params['document']);
      }
      if (!$doc->getFromDB($params['document'])) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTFOUND);
      }
      if (!$doc->canViewFile($options)) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED);
      }
      $resp = $doc->fields;
      $resp['base64'] = base64_encode(file_get_contents(GLPI_DOC_DIR."/".$doc->fields['filepath']));

      if (isset ($params['id2name'])) {
         $resp['users_name'] = html_clean(getUserName($doc->fields['users_id']));
         $resp['documentcategories_name'] = html_clean(Dropdown::getDropdownName('glpi_documentcategories',
                                                            $doc->fields['documentcategories_id']));
      }
      return $resp;
   }

   /**
    * This method return groups list allowed
    * for an authenticated user
    *
    * @param $params array of options
    * @param $protocol the commonication protocol used
    *
    * @return an response ready to be encode (ID + completename)
    * @deprecated since 1.1.0
   **/
   static function methodListGroups($params, $protocol) {
      global $DB, $CFG_GLPI;

      if (isset($params['help'])) {
         return array ('count'   => 'bool,optional',
                       'start'   => 'integer,optional',
                       'limit'   => 'integer,optional',
                       'mine'    => 'bool,optional',
                       'id2name' => 'bool,optional',
                       'help'    => 'bool,optional');
      }

      if (!getLoginUserID()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      $withparent = (isset ($params['withparent']) && $params['withparent']);

      $restrict = getEntitiesRestrictRequest('', 'glpi_groups', '', '', $withparent);
      if (isset($params['mine'])) {
         if (count($_SESSION['glpigroups'])) {
            $restrict .= "AND `id` IN ('".implode("','", $_SESSION['glpigroups'])."')";
         } else {
            $restrict .= "AND 0";
         }
      }

      $resp = array ();
      if (isset ($params['count'])) {
         $resp['count'] = countElementsInTable('glpi_groups', $restrict);
         return $resp;
      }

      $start = 0;
      $limit = $_SESSION['glpilist_limit'];
      if (isset ($params['limit']) && is_numeric($params['limit'])) {
         $limit = $params['limit'];
      }
      if (isset ($params['start']) && is_numeric($params['start'])) {
         $start = $params['start'];
      }
      $sql = "SELECT *
              FROM `glpi_groups`
              WHERE $restrict
              ORDER BY `id`
              LIMIT $start,$limit";


      foreach ($DB->request($sql) as $data) {

         $data['member'] = (in_array($data['id'], $_SESSION['glpigroups']) ? 1 : 0);
         if (isset ($params['id2name'])) {
            $data['users_name'] = html_clean(getUserName($data['users_id']));
         }
         $resp[] = $data;
      }
      return $resp;
   }

   /**
    * Generic method to get contracts for an object
    * for an authenticated user
    *
    * @param $params array of options (computer)
    * @param $protocol the commonication protocol used
    *
    * @return hashtable -fields of glpi_contracts
    * @deprecated since 1.1.0
   **/
   static function methodGetContracts($params, $protocol) {

      if (isset ($params['help'])) {
         $params = array('itemtype' =>'string, mandatory',
                         'id'       => 'integer, mandatory',
                         'id2name'  => 'bool,optional',
                         'help'     => 'bool,optional');
         //Do not use computer parameter but id instead.
         //DEPRECATED, must be removed in the next release
         if ($params['itemtype'] == 'Computer') {
            $params['computer'] = 'integer, optional, deprecated (use id instead)';
         }
         return $params;
      }
      $check = self::checkStandardParameters($params,$protocol);
      if ($check == 1) {
         return self::getItemContracts($protocol, $params['itemtype'], $params['id'],
                                                    isset($params['id2name']));
      }
      else {
         return $check;
      }

   }

   /**
    * Get Infocom for a Computer
    * for a authenticated user
    *
    * @param $params array of options (computer)
    * @param protocol the commonication protocol used
    * @return hashtable -fields of glpi_computer
    * @deprecated since 1.1.0
    */
   static function methodGetInfocoms($params, $protocol) {

      if (isset ($params['help'])) {
         $params = array('WARNING'  => 'This method is deprecated !',
                         'itemtype' =>'string, mandatory',
                         'id'       => 'integer, mandatory',
                         'help'     => 'bool,optional');
         //Do not use computer parameter but id instead.
         //DEPRECATED, must be removed in the next release
         if ($params['itemtype'] == 'Computer') {
            $params['computer'] = 'integer, optional, deprecated (use id instead)';
         }
         return $params;
      }

      $check = self::checkStandardParameters($params,$protocol);
      if ($check == 1) {
         return self::getItemInfocoms($protocol, $params['itemtype'], $params['id'],
                                                    isset($params['id2name']));
      }
      else {
         return $check;
      }
   }

   /**
    * Get Phone for a Computer
    * for an authenticated user
    *
    * @param $params array of options (computer)
    * @param $protocol the commonication protocol used
    *
    * @return hashtable -fields of glpi_computer
    * @deprecated since 1.1.0
   **/
   static function methodGetPhones($params, $protocol) {

      if (isset ($params['help'])) {
         $params = array('itemtype' => 'string, mandatory',
                         'id'       => 'integer, mandatory',
                         'id2name'  => 'bool,optional',
                         'help'     => 'bool,optional');
         //Do not use computer parameter but id instead.
         //DEPRECATED, must be removed in the next release
         if ($params['itemtype'] == 'Computer') {
            $params['computer'] = 'integer, optional, deprecated (use id instead)';
         }
         return $params;
      }

      $check = self::checkStandardParameters($params,$protocol);
      if ($check == 1) {
         return self::getItemPhones($protocol, $params['itemtype'], $params['id'],
                                                    isset($params['id2name']));
      }
      else {
         return $check;
      }
   }

   /**
    * Get netwok ports for an object
    * for an authenticated user
    *
    * @param $params array of options (computer)
    * @param protocol the commonication protocol used
    *
    * @return hashtable -fields of glpi_contracts
    * @deprecated since 1.1.0
   **/
   static function methodGetNetworkports($params, $protocol) {

      if (isset ($params['help'])) {
         return array('id' => 'integer, mandatory',
                      'id2name'  => 'bool,optional',
                      'itemtype' => 'string, mandatory',
                      'help'     => 'bool,optional');
      }
      //If no right on network, return an empty array
      if (!haveRight('networking', 'r')) {
         return array();
      }
      $check = self::checkStandardParameters($params,$protocol);
      if ($check == 1) {
         return self::getItemNetworkports($protocol, $params['itemtype'], $params['id'],
                                                    isset($params['id2name']));
      }
      else {
         return $check;
      }
   }

   /**
    * Return Infocom for an object
    *
    * @param $protocol the commonication protocol used
    * @param $params array
    *
    * @return a hasdtable, fields of glpi_infocoms
    * @deprecated since 1.1.0
   **/
   static function getInfocoms($protocol, $params=array()) {

      $infocom = new InfoCom();
      $output = array();
      if (!haveRight("infocom", "r")) {
         return $output;
      }
      $item = new $params['options']['itemtype']();
      if ($infocom->getFromDBforDevice($params['options']['itemtype'], $params['data']['id'])
            || !$item->can($params['data']['id'], 'r')) {
          $params = array('data'          => $infocom->fields,
                          'searchOptions' => $infocom->getSearchOptions(),
                          'options'       => $params['options']);
          parent::formatDataForOutput($params, $output);
          return $output;
      }
   }

   /**
    * Return phones for an object
    *
    * @param $protocol the commonication protocol used
    * @param $params : the parameters to get data
    *
    * @return a hasdtable, fields of glpi_infocoms
    * @deprecated since 1.1.0
   **/
   static function getContracts($protocol, $params=array()) {
      global $DB;
      
      if (!haveRight("networking", "r")) {
         return array();
      }
      
      $item = new $params['options']['itemtype']();
      if (!$item->can($params['data']['id'], 'r')) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTFOUND);
      }

      $query = "SELECT `glpi_contracts`.*
                FROM `glpi_contracts_items`, `glpi_contracts`
                LEFT JOIN `glpi_entities` ON (`glpi_contracts`.`entities_id` = `glpi_entities`.`id`)
                WHERE `glpi_contracts`.`id` = `glpi_contracts_items`.`contracts_id`
                      AND `glpi_contracts_items`.`items_id` = '".$params['data']['id']."'
                      AND `glpi_contracts_items`.`itemtype` = '".$params['options']['itemtype']."'".
                      getEntitiesRestrictRequest(" AND","glpi_contracts",'','',true)."
                ORDER BY `glpi_contracts`.`name`";
      $output = array();
      $contract = new Contract();
      foreach ($DB->request($query) as $data) {
         $resp   = array();
         $params = array('data'          => $data,
                         'searchOptions' => $contract->getSearchOptions(),
                         'options'       => $params['options']);
         parent::formatDataForOutput($params, $resp);
         $output[] = $resp;
      }

      return $output;
   }

   static function getRelatedObjects($params,$protocol,&$resp) {
      if (isset ($params['infocoms'])) {
         $infocoms = self::methodGetInfocoms($params, $protocol);
         if (!self::isError($protocol, $infocoms)) {
            $resp['infocoms'] = $infocoms;
         }
      }

      if (isset ($params['contracts'])) {
         $contracts = self::methodGetContracts($params, $protocol);
         if (!self::isError($protocol, $contracts)) {
            $resp['contracts'] = $contracts;
         }
      }

      if (isset ($params['networkports'])) {
         $networkports = self::methodGetNetworkports($params, $protocol);
         if (!self::isError($protocol, $networkports)) {
            $resp['networkports'] = $networkports;
         }
      }
   }


   /**
    * Return Infocom for an object
    *
    * @param protocol the commonication protocol used
    * @param $item_type : type of the item
    * @param $item_id : ID of the item
    * @param $id2name : translate id of dropdown to name
    *
    * @return a hasdtable, fields of glpi_infocoms
    */
   static function getItemInfoComs($protocol, $item_type, $item_id, $id2name = false) {

      if (!haveRight("infocom", "r")) {
         return array();
      }
      $infocom = new InfoCom();
      $item = new $item_type();

      $item->getTypeName();
      if (!$infocom->getFromDBforDevice($item_type, $item_id) || !$item->can($item_id, 'r')) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTFOUND);
      }

      $resp = $infocom->fields;
      $resp['warranty_expiration'] = getWarrantyExpir($infocom->fields['buy_date'],
                                                      $infocom->fields['warranty_duration']);

      if ($id2name) {
         // TODO : more dropdown value
         $resp['suppliers_name'] = html_clean(Dropdown::getDropdownName('glpi_suppliers',
                                                              $infocom->fields['suppliers_id']));
         $resp['budgets_names'] = html_clean(Dropdown::getDropdownName('glpi_budgets',
                                                             $infocom->fields['budgets_id']));
      }
      return $resp;
   }


   /**
    * Return Infocom for an object
    *
    * @param protocol the commonication protocol used
    * @param $item_type : type of the item
    * @param $item_id : ID of the item
    * @param $id2name : translate id of dropdown to name
    *
    * @return a hasdtable, fields of glpi_infocoms
    */
   static function getItemContracts($protocol, $item_type, $item_id, $id2name = false) {
      global $DB;

      $item = new $item_type();
      if (!$item->getFromDB($item_id) || !haveRight('contract','r')) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTFOUND);
      }

      $contract = new Contract();

      $query = "SELECT `glpi_contracts`.*
                FROM `glpi_contracts_items`, `glpi_contracts`
                LEFT JOIN `glpi_entities` ON (`glpi_contracts`.`entities_id` = `glpi_entities`.`id`)
                WHERE `glpi_contracts`.`id` = `glpi_contracts_items`.`contracts_id`
                      AND `glpi_contracts_items`.`items_id` = '$item_id'
                      AND `glpi_contracts_items`.`itemtype` = '$item_type'".
                      getEntitiesRestrictRequest(" AND","glpi_contracts",'','',true)."
                ORDER BY `glpi_contracts`.`name`";

      $result = $DB->query($query);

      $resp=array();
      while ($datas = $DB->fetch_array($result)) {
         $contract->getFromDB($datas['id']);
         $resp[$datas['id']] = $contract->fields;

         if ($id2name) {
            $resp[$datas['id']]['contracttypes_name'] =
              html_clean(Dropdown::getDropdownName('glpi_contracttypes',$contract->fields['contracttypes_id']));
         }
      }

      return $resp;
   }

   /**
    * Get netwok ports for an object
    * for an authenticated user
    *
    * @param $protocol the commonication protocol used
    * @param $item_type : type of the item
    * @param $item_id : ID of the item
    * @param $id2name : translate id of dropdown to name
    *
   **/
   static function getItemNetworkports($protocol, $item_type, $item_id, $id2name=false) {
      global $DB;
      $item = new $item_type();
      $resp = array();

      if ($item->getFromDB($item_id)  && $item->canView()) {
         //Get all ports for the object
         $ports = getAllDatasFromTable('glpi_networkports',
                                       "`itemtype`='$item_type' AND `items_id`='$item_id'");

         foreach ($ports as $port) {

            if ($id2name) {
               if ($port['networkinterfaces_id'] > 0) {
                  $port['networkinterfaces_name'] =
                     html_clean(Dropdown::getDropdownName('glpi_networkinterfaces',
                                                          $port['networkinterfaces_id']));
               }
            }

            if ($port['netpoints_id'] > 0) {
               //Get netpoint informations
               $netpoint = new Netpoint();
               $netpoint->getFromDB($port['netpoints_id']);
               if ($id2name) {
                  $netpoint->fields['location_name'] =
                        html_clean(Dropdown::getDropdownName('glpi_locations',
                                                             $netpoint->fields['locations_id']));
               }
               $port['netpoints'][$netpoint->fields['id']] = $netpoint->fields;
            }

            //Get VLANS
            $vlan = new NetworkPort_Vlan();
            $tmp  = new Vlan();
            foreach ($vlan->getVlansForNetworkPort($port['id']) as $vlans_id ) {
               $tmp->getFromDB($vlans_id);
               $port['vlans'][$tmp->fields['id']] = $tmp->fields;
            }

            $resp[$port['id']] = $port;
         }
      }
      return $resp;
   }
}


?>