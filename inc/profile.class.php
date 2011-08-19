<?php


/*----------------------------------------------------------------------
   GLPI - Gestionnaire Libre de Parc Informatique
   Copyright (C) 2003-2008 by the INDEPNET Development Team.

   http://indepnet.net/   http://glpi-project.org/
   ----------------------------------------------------------------------
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
   along with GLPI; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
   ----------------------------------------------------------------------*/
/*----------------------------------------------------------------------
    Original Author of file: 
    Purpose of file:
    ----------------------------------------------------------------------*/
class PluginGenericobjectProfile extends CommonDBTM {

   /* if profile deleted */
   function cleanProfiles($id) {
      $this->deleteByCriteria(array('id' => $id));
   }

   /* profiles modification */
   function showForm($id) {
      global $LANG;


      if (!haveRight("profile", "r")) {
         return false;
      }
      $canedit = haveRight("profile", "w");
      if ($id) {
         $this->getProfilesFromDB($id);
      }

      echo "<form action='" . $this->getSearchURL() . "' method='post'>";
      echo "<table class='tab_cadre_fixe'>";


      $types = PluginGenericobjectType::getTypes(true);
      
      if (!empty ($types)) {

         echo "<tr><th colspan='2' align='center'><strong>"; 
         echo $LANG["genericobject"]['profile'][0]."</strong></th></tr>";
         
         foreach ($types as $tmp => $type) {
            $itemtype   = $type['itemtype'];
            $objecttype = new PluginGenericobjectType($itemtype);
            $profile    = self::getProfileforItemtype($id, $itemtype);
            echo "<tr><th align='center' colspan='2' class='tab_bg_2'>".
               call_user_func(array($itemtype, 'getTypeName'))."</th></tr>";
            echo "<tr class='tab_bg_2'>";
            $right = $type['itemtype']."_right";
            echo "<td>" . $LANG['genericobject']['profile'][2] . ":</td><td>";
            Profile::dropdownNoneReadWrite($right,  $profile['right'], 1, 1, 1);
            echo "</td></tr>";
            if ($objecttype->canUseTickets()) {
               echo "<tr class='tab_bg_2'>";
               echo "<td>" . $LANG["genericobject"]['profile'][1] . ":</td><td>";
               $right_openticket = $type['itemtype']."_open_ticket";
               Dropdown::showYesNo($right_openticket,  $profile['open_ticket']);
               echo "</td></tr>";
            }

         }
         if ($canedit) {
            echo "<tr class='tab_bg_1'>";
            echo "<td align='center' colspan='2'>";
            echo "<input type='hidden' name='profiles_id' value='".$id."'>";
            echo "<input type='hidden' name='id' value=$id>";
            echo "<input type='submit' name='update_user_profile' value=\"" . $LANG['buttons'][7] . "\" class='submit'>";
            echo "</td></tr>";
         
         }


      } else {
         echo "<tr><td class='center'><strong>"; 
         echo $LANG["genericobject"]['profile'][3]."</strong></td></tr>";
      }

      echo "</table></form>";

   }

   static function getProfileforItemtype($profiles_id, $itemtype) {
      $results = getAllDatasFromTable(getTableForItemType(__CLASS__), 
                                      "`itemtype`='$itemtype' AND `profiles_id`='$profiles_id'");
      if (!empty($results)) {
         return array_pop($results);
      } else {
         return array();
      }
   }
   
   function getProfilesFromDB($id) {
      global $DB;
      $prof_datas = array ();
      foreach (getAllDatasFromTable(getTableForItemType(__CLASS__),
                                    "`profiles_id`='" . $id . "'") as $prof) {
         $prof_datas[$prof['itemtype']]       = $prof['right'];
         $prof_datas[$prof['itemtype'].'_open_ticket'] = $prof['open_ticket'];
      }
      
      $prof_datas['id']   = $id;
      $this->fields       = $prof_datas;
   
      return true;
   }

   function saveProfileToDB($params) {
      global $DB;

      $types = PluginGenericobjectType::getTypes();
      if (!empty ($types)) {
         foreach ($types as $tmp => $profile) {
            $query = "UPDATE `".getTableForItemType(__CLASS__)."` " .
                     "SET `right`='".$params[$profile['itemtype']."_right"]."' ";

            if (isset($params[$profile['itemtype'].'_open_ticket'])) {
               $query.=", `open_ticket`='".$params[$profile['itemtype'].'_open_ticket']."' ";
            }

            $query.="WHERE `profiles_id`='".$params['profiles_id']."' " .
                    "AND `itemtype`='".$profile['itemtype']."'";
            $DB->query($query);
         }
      }
   }
   
   
   /**
    * Create rights for the current profile
    * @param profileID the profile ID
    * @return nothing
    */
   public static function createFirstAccess() {
      if (!self::profileExists($_SESSION["glpiactiveprofile"]["id"])) {
         self::createAccess($_SESSION["glpiactiveprofile"]["id"],true);
      }
   }
   
   
   /**
    * Check if rights for a profile still exists
    * @param profileID the profile ID
    * @return true if exists, no if not
    */
   public static function profileExists($profiles_id) {
      return (countElementsInTable(getTableForItemType(__CLASS__), 
                                  "`profiles_id`='$profiles_id'")>0?true:false);
   }
   
   /**
    * Create rights for the profile if it doesn't exists
    * @param profileID the profile ID
    * @return nothing
    */
   public static function createAccess($profiles_id, $first=false) {
      $types          = PluginGenericobjectType::getTypes(true);
      $profile = new self();
      foreach ($types as $tmp => $value) {
         if (!self::profileForTypeExists($profiles_id, $value["itemtype"])) {
            $input["itemtype"]    = $value["itemtype"];
            $input["right"]       = ($first?'w':'');
            $input["open_ticket"] = ($first?1:0);
            $input["profiles_id"] = $profiles_id;
            $profile->add($input);
         }
      }
   }

   /**
    * Check if rights for a profile and type still exists
    * @param profileID the profile ID
    * @param itemtype name of the type 
    * @return true if exists, no if not
    */
   public static function profileForTypeExists($profiles_id, $itemtype) {
      global $DB;
      return (countElementsInTable(getTableForItemType(__CLASS__), 
                                  "`profiles_id`='$profiles_id' " .
                                  "AND `itemtype`='$itemtype'")>0?true:false);
   }

   /**
    * Delete type from the rights
    * @param name the name of the type
    * @return nothing
    */
   public static function deleteTypeFromProfile($itemtype) {
      $profile = new self();
      $profile->deleteByCriteria(array("itemtype" => $itemtype));
   }
   
   public static function changeProfile() {
      $profile = new self();
      if($profile->getProfilesFromDB($_SESSION['glpiactiveprofile']['id'])) {
         $_SESSION["glpi_plugin_genericobject_profile"]=$profile->fields;
      } else {
         unset($_SESSION["glpi_plugin_genericobject_profile"]);
      }

   }
   
   function haveRight($module, $right) {
      $matches = array ("" => array ("", "r", "w"), "r" => array ("r", "w"), "w" => array ("w"),
                        "1" => array ("1"), "0" => array ("0", "1"));
   
      if (isset ($_SESSION["glpi_plugin_genericobject_profile"][$module]) 
            && in_array($_SESSION["glpi_plugin_genericobject_profile"][$module], $matches[$right])) {
         return true;
      } else {
         return false;
      }
   }

   static function install(Migration $migration) {
      global $DB;
      if (!TableExists(getTableForItemType(__CLASS__))) {
         $query = "CREATE TABLE `".getTableForItemType(__CLASS__)."` (
                           `id` int(11) NOT NULL auto_increment,
                           `profiles_id` int(11) NOT NULL  DEFAULT '0',
                           `itemtype` VARCHAR( 255 ) default NULL,
                           `right` char(1) default NULL,
                           `open_ticket` char(1) NOT NULL DEFAULT 0,
                           PRIMARY KEY  (`id`),
                           KEY `name` (`profiles_id`)
                           ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->query($query) or die($DB->error());
      }
      self::createFirstAccess();
   }
   
   static function uninstall() {
      global $DB;
      $query = "DROP TABLE IF EXISTS `".getTableForItemType(__CLASS__)."`";
      $DB->query($query) or die($DB->error());
   } 
}