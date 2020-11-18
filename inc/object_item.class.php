<?php
/*
 This file is part of the genericobject plugin.

 Genericobject plugin is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Genericobject plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Genericobject. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 @package   genericobject
 @author    the genericobject plugin team
 @copyright Copyright (c) 2010-2011 Order plugin team
 @license   GPLv2+
            http://www.gnu.org/licenses/gpl.txt
 @link      https://forge.indepnet.net/projects/genericobject
 @link      http://www.glpi-project.org/
 @since     2009
 ---------------------------------------------------------------------- */

class PluginGenericobjectObject_Item extends CommonDBChild {

   public $dohistory = true;

   // From CommonDBRelation
   static public $itemtype_1 = "PluginGenericobjectObject";
   static public $items_id_1 = 'plugin_genericobject_objects_id';

   static public $itemtype_2 = 'itemtype';
   static public $items_id_2 = 'items_id';

   //Get itemtype name
   static function getTypeName($nb = 0) {
      global $LANG;
      $class    = get_called_class();
      //Datainjection : Don't understand why I need this trick : need to be investigated !
      if (preg_match("/Injection$/i", $class)) {
         $class = str_replace("Injection", "", $class);
      }
      $item     = new $class();
      //Itemtype name can be contained in a specific locale field : try to load it
      PluginGenericobjectType::includeLocales($item->objecttype->fields['name']);
      if (isset($LANG['genericobject'][$class][0])) {
         return $LANG['genericobject'][$class][0];
      } else {
         return $item->objecttype->fields['name'];
      }
   }

   static function canView() {
      return Session::haveRight(self::$itemtype_1, READ);
   }

   static function canCreate() {
      return Session::haveRight(self::$itemtype_1, CREATE);
   }

   /**
    *
    * Enter description here ...
    * @since 2.2.0
    * @param CommonDBTM $item
    */
   static function showItemsForSource(CommonDBTM $item) {
      $item->showLinkedTypesForm();
      print_r($item);
   }

   /**
    *
    * Enter description here ...
    * @since 2.2.0
    * @param CommonDBTM $item
    */
   static function showItemsForTarget(CommonDBTM $item) {

   }

   /**
    *
    * Enter description here ...
    * @since 2.2.0
    */
   static function registerType() {
      Plugin::registerClass(get_called_class(), ['addtabon' => self::getLinkedItemTypes()]);
   }

   static function getLinkedItemTypes() {
      $source_itemtype = self::getItemType1();
      $source_item = new $source_itemtype;
      return $source_item->getLinkedItemTypesAsArray();
   }

   static function getItemType1() {
      $classname   = get_called_class();
      return $classname::$itemtype_1;
   }

   static function dropdownHelpdeskItemtypes($options) {
      global $CFG_GLPI;

      $p['name']    = 'itemtypes';
      $p['values']  = [];
      $p['display'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $values = self::getHelpdeskItemtypes();

      $p['multiple'] = true;
      $p['size']     = 3;
      return Dropdown::showFromArray($p['name'], $values, $p);
   }

   static function getHelpdeskItemtypes() {
      global $CFG_GLPI;

      $values = [];
      foreach ($CFG_GLPI["asset_types"] as $itemtype) {
         if ($item = getItemForItemtype($itemtype)) {
            $values[$itemtype] = $item->getTypeName();
         }
      }
      return $values;
   }

   static function showAssociatedItems (CommonDBTM $item) {
      //$item->showLinkedTypesForm();
      $item->showFormHeader();
      echo "<form name='link' method='post'>";
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='2'>".__('Child items')."</td>";
      echo "<td colspan='2'><input type='hidden' name='_helpdesk_item_types' value='1'>";
      self::dropdownHelpdeskItemtypes(['values' => $item->getLinkedItemTypesAsArray()]);
      echo "</td>";
      echo "</tr>";
      echo "<input type='hidden' name='id' value='".$item->getID()."'>";
      $item->showFormButtons(['candel' => false, 'canadd' => false]);
      echo "</table>";
      Html::closeForm();
   }

   public function defineTabs($options = []) {
      $tabs = [];
      $this->addDefaultFormTab($tabs);

      return $tabs;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      return [1 => __("Associated child", "genericobject"),2=>__("Associated parent","genericobject")];
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch ($tabnum) {
        case 1:
          self::showChilds($item);
          break;
        
        case 2:
          self::showParents($item);
          break;
      }
      return true;
   }

   static function showChilds(CommonDBTM $item) {
      global $DB,$CFG_GLPI;

      $rand = mt_rand();
      $list=$item->getLinkedItemTypesAsArray();
      $out = "<form name='creditentity_form$rand' id='creditentity_form$rand' method='post'>";
      $out .="<input type='hidden' name='parentid' value='".$item->getID()."' />";
      $out .="<input type='hidden' name='parenttype' value='".$item->getType()."' />";
      $out .="<table class='tab_cadre_fixe'>";
      $out .= "<tr class='tab_bg_1'>";
      $out .= "<td>".__("Items seen")."</td>";
      $out .= "<td>";
      $out .=Dropdown::showItemTypes('plugin_item_links',$list,['display' => false,'rand'=>$rand]);
      $out .= "</td></tr>";
      $out .= "<tr class='tab_bg_1'>";
      $out .= "<td>".__("Add link")."</td>";
      $out .= "<td>";
      $out .= "<div id='plugin_object_link$rand'></div>";
      $out .= Ajax::updateItemOnSelectEvent(
        "dropdown_plugin_item_links$rand",
        "plugin_object_link$rand",
        $CFG_GLPI["root_doc"] . "/plugins/genericobject/ajax/dropdownItems.php",
        ['itemtype' => '__VALUE__'],
        false
      );
      $out .= "</td></tr>";
      $out .="</table>";
      $out .= "<input type='submit' name='link' value='"._sx('button', 'Add')."' class='submit'>";
      $out .=Html::closeForm(false);
      echo $out;

      $query=[
         'FROM'=>self::getTable(),
         'WHERE'=>[
            'parent_id'=>$item->getID(),
            'parentitemtype'=>$item->getType(),
         ]
      ];
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr>";
      $header_end ="";
      //$header_end .= "<th>".__('Id')."</th>";
      $header_end .= "<th>".__('Type')."</th>";
      $header_end .= "<th>".__('Name')."</th>";
      $header_end .= "<th>".__('Serial number')."</th>";
      $header_end .= "<th>".__('Inventory number')."</th>";
      echo $header_end;
      echo "</tr>";
      foreach ($DB->request($query) as $id => $row) {
        $itemtype = $row['childitemtype'];
        $child = getItemForItemtype($itemtype);
        $child->getFromDB($row['child_id']);

        echo "<tr class='tab_bg_1'>";
        //echo "<td class='center'>".$child->getID()."</td>";
        echo "<td class='center'>".$itemtype."</td>";
        $link = $itemtype::getFormURLWithID($child->getID());
        echo "<td class='center'><a href=\"".$link."\">".$child->fields['name']."</a></td>";
        if (array_key_exists('serial',$child->fields)) {
          echo "<td class='center'>".$child->fields['serial']."</td>";
        }else{
          echo "<td class='center'></td>";
        }
        if (array_key_exists('otherserial',$child->fields)) {
          echo "<td class='center'>".$child->fields['otherserial']."</td>";
        }else{
          echo "<td class='center'></td>";
        }
        echo "</tr>";
      }
      echo "</table>";
   }

   static function showParents(CommonDBTM $item) {
      global $DB;

      $query=[
         'FROM'=>self::getTable(),
         'WHERE'=>[
            'child_id'=>$item->getID(),
            'childitemtype'=>$item->getType(),
         ]
      ];
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr>";
      $header_end ="";
      //$header_end .= "<th>".__('Id')."</th>";
      $header_end .= "<th>".__('Type')."</th>";
      $header_end .= "<th>".__('Name')."</th>";
      $header_end .= "<th>".__('Serial number')."</th>";
      $header_end .= "<th>".__('Inventory number')."</th>";
      echo $header_end;
      echo "</tr>";
      foreach ($DB->request($query) as $id => $row) {
        $itemtype = $row['parentitemtype'];
        $parent = getItemForItemtype($itemtype);
        $parent->getFromDB($row['parent_id']);

        echo "<tr class='tab_bg_1'>";
        //echo "<td class='center'>".$child->getID()."</td>";
        echo "<td class='center'>".$itemtype."</td>";
        $link = $itemtype::getFormURLWithID($parent->getID());
        echo "<td class='center'><a href=\"".$link."\">".$parent->fields['name']."</a></td>";
        if (array_key_exists('serial',$parent->fields)) {
          echo "<td class='center'>".$parent->fields['serial']."</td>";
        }else{
          echo "<td class='center'></td>";
        }
        if (array_key_exists('otherserial',$parent->fields)) {
          echo "<td class='center'>".$parent->fields['otherserial']."</td>";
        }else{
          echo "<td class='center'></td>";
        }
        echo "</tr>";
      }
      echo "</table>";
   }

   static function addlink($parentid,$parenttype,$childid,$childtype){
      global $DB;

      $DB->insert(
         'glpi_plugin_genericobject_objects_items',[
            'parentitemtype'=>$parenttype,
            'parent_id'=>$parentid,
            'childitemtype'=>$childtype,
            'child_id'=>$childid,
         ]
      );
   }

   static function install(Migration $migration) {
      global $DB;

      $table = self::getTable();
      if (!$DB->tableExists($table)) {
         $query = "CREATE TABLE `$table` (
                           `id` INT( 11 ) NOT NULL AUTO_INCREMENT,
                           `parentitemtype` varchar(255) collate utf8_unicode_ci NOT NULL,
                           `parent_id` INT( 11 ) NOT NULL DEFAULT 0,
                           `childitemtype` varchar(255) collate utf8_unicode_ci NOT NULL,
                           `child_id` INT( 11 ) NOT NULL DEFAULT 0,
                           PRIMARY KEY ( `id` )
                           ) ENGINE = InnoDB COMMENT = 'Object types definition table' DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->query($query) or die($DB->error());
      }
   }


   static function uninstall() {
      global $DB;

      //Delete table
      $table = self::getTable();
      $query = "DROP TABLE IF EXISTS `$table`";
      $DB->query($query) or die($DB->error());
   }

}
