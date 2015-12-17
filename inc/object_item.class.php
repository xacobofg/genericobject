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

    static public $register = array();


    static public function getregistrerClass(){
        return self::$register;
    }

    static public function setRegistrerClass($liste){
        self::$register = $liste;
    }


static function getSpecificValueToDisplay($field, $values, array $options = array())
    {
        if (!is_array($values)) {
            $values = array($field => $values);
        }


        switch ($field) {
            case 'id' :


                $table   = get_called_class();

                $objectItem = new $table;
                $objectItem->getFromDB($values['id']);

                $namelinkedObject = $objectItem->fields['itemtype'];
                $oobjectLinked = new $namelinkedObject();
                $oobjectLinked->getFromDB($objectItem->fields['items_id']);


                $url = $oobjectLinked->getLink()." - (".strtoupper($oobjectLinked->getTypeName()).")";

                return $url;

                break;

        }


    }

   //Get itemtype name
   static function getTypeName($nb=0) {
      global $LANG;
      $class    = get_called_class();
      //Datainjection : Don't understand why I need this trick : need to be investigated !
      if(preg_match("/Injection$/i",$class)) {
         $class = str_replace("Injection", "", $class);
      }
      $item     = new $class();
      //Itemtype name can be contained in a specific locale field : try to load it
      PluginGenericobjectType::includeLocales($item->objecttype->fields['name']);
      if(isset($LANG['genericobject'][$class][0])) {
         return $LANG['genericobject'][$class][0];
      } else {
         return $item->objecttype->fields['name'];
      }
   }
   
   static function canView() {
      return plugin_genericobject_haveRight($this->$itemtype_1, 'r');
   }
   
   static function canCreate() {
      return plugin_genericobject_haveRight($this->$itemtype_1, 'w');
   }

   /**
    *
    * Enter description here ...
    * @since 2.2.0
    * @param CommonDBTM $item
    */
   static function showItemsForSource(CommonDBTM $item) {

      echo "source";

   }
   
   /**
    *
    * Enter description here ...
    * @since 2.2.0
    * @param CommonDBTM $item
    */
   static function showItemsForTarget(CommonDBTM $item) {

       global $DB;

       $linkedItem = $item->getLinkedItemTypesAsArray();

       foreach($linkedItem as $itemL){

           $object = new $itemL;
           $rand = rand();


           echo "<br>";
           echo "<table class='tab_cadre_fixe' >";

           echo "<tr>";
           echo "<th colspan='6'>".strtoupper($object->getTypeName())."</th>";
           echo "</tr>";

           echo "<tr>";
           echo "<td style='width:300px;'>" . __("Select an object to link", "genericobject")."</td>";

           echo "<td style='width:300px;' id='dropdown_".$rand."'>";

           self::getDropdownItemLinked($object , $item->getType(),$item->fields['id']);

           echo "</td>";


           echo "<td>";
           echo '<input id=\'addObject\'  class=\'submit\' type=\'submit\' name=\'add\' value=\''.__('Link','genericobject').'\' onClick=\'addObject("'.$item->getType().'","'.$object->getTypeName().'","'.$itemL.'","'.$item->fields['id'].'","'.$rand.'");\'>';
           echo "</td>";

           echo "<td id='info".$rand."'>";
           echo "</td>";

           echo "</tr>";

           echo "</table>";


           echo "<div id='result_".$rand."'>";
           self::getItemListForObject($item->accesObjectType()->fields['itemtype'],$rand,$object->accesObjectType()->fields['itemtype'],$item->fields['id']);
           echo "</div>";

           echo "<br>";
           echo "<br>";

       }

       return false;

   }
   
   /**
    *
    * Enter description here ...
    * @since 2.2.0
    */
   static function registerType() {

       foreach(self::getLinkedItemTypes() as $class){

           if(!in_array($class, self::getregistrerClass(),true)){

               $register = self::getregistrerClass();

               $register[] = $class;
               self::setRegistrerClass($register);
               Plugin::registerClass(get_called_class(),
                   array('addtabon' => $class));

           }
       }

/*
       //I don't know why i need this tricks but without tabs are double
       //need to check if already register
       if(!in_array(self::getLinkedItemTypes() , self::getregistrerClass(),true)){
           $register = self::getregistrerClass();
           array_push($register,self::getLinkedItemTypes());
           self::setRegistrerClass($register);

           Plugin::registerClass(get_called_class(),
               array('addtabon' => array_unique(self::getLinkedItemTypes())));
       }*/


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

    public static function getDropdownItemLinked($object , $itemType , $id)
    {

	$obj = new $itemType();
        $nameMainObjectItem = $itemType."_Item";
        $mainObjectItem = new $nameMainObjectItem();
        $listeRecord = $mainObjectItem->find();

$column = str_replace('glpi_','',$obj->table."_id");

        $listeId = array();
        foreach($listeRecord as $record){

 if($record[$column] == $id) $listeId[] = $record['items_id'];
           // $listeId[] = $record['items_id'];
        }

        $list = array(0 => "------");
        foreach($object->find('','name ASC','') as $obj){
            if(!in_array($obj['id'],$listeId)) $list[] = $obj['name'];
        }
        Dropdown::showFromArray($object->getTypeName(),$list , array('rand' => ''));

    }

    function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

       if (!$withtemplate) {
            $ong = array();
            //$ong[0] = $item->getTypeName();
               if(count($item->getLinkedItemTypesAsArray()) > 0){
                  $ong[1] = __("Linked objects", "genericobject");
               }
            return $ong;
         }

        return '';

      /*if (!$withtemplate) {
         $itemtypes = self::getLinkedItemTypes();
         if (in_array(get_class($item), $itemtypes) || get_class($item) == self::getItemType1()) {
            return array(1 => __("Objects management", "genericobject"));
         }
      if (!$withtemplate) {
      return '';*/
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

     if($tabnum == 1 ){
         self::showItemsForTarget($item);
     }
     /* $itemtypes = self::getLinkedItemTypes();


       if (get_class($item) == self::getItemType1()) {
         self::showItemsForSource($item);
      } elseif (in_array(get_class($item), $itemtypes)) {
         self::showItemsForTarget($item);
      }*/

      return true;
   }



    static function getItemListForObject($itemtype,$rand,$obj_item,$idItemType)
    {

        global $CFG_GLPI;

        $nameMainObject = $itemtype.'_item';
        $objectItem = new $nameMainObject();
        $mainObject = new $itemtype();

        $column = str_replace('glpi_','',$mainObject->table.'_id');


        $resultat = $objectItem->find("`itemtype` = '".$obj_item."' and `".$column."` = $idItemType");


        if(count($resultat) > 0){


            echo "<table class='tab_cadre_fixe'  id='tableResult".$rand."'>";
            echo "<th colspan='4'>".__('Links','genericobject')."</th>";

            echo "<tr class='headerRow'>";
            echo "<th>" . __("ID", "genericobject") . "</th>";
            echo "<th>" . __("Name", "genericobject") . "</th>";
            echo "<th>" . __("Type", "genericobject") . "</th>";
            echo "<th>" . __("Remove", "genericobject") . "</th>";
            echo "</tr>";

            foreach($resultat as $item){

                $obj = new $item['itemtype']();
                $obj->getFromDB($item['items_id']);



                echo "<tr>";
                echo "<td class='center'>".$item['items_id']."</td>";
                echo "<td class='center'>".$obj->getLink()."</td>";
                echo "<td class='center'>".strtoupper($obj->getTypeName())."</td>";
                echo "<td class='center'><img src='../pics/bin16.png'  onclick=\"deleteLink('" .$item['id']. "','".$itemtype."','".$rand."','".$obj_item."','".$idItemType."')\";
                     style='cursor: pointer;' title='" . __("Delete link", "renamer") . "'/></td>";
                echo "</tr>";
            }

            echo "</table>";


        }else{
            echo "<table class='tab_cadre_fixe'  id='tableResult".$rand."'>";

            echo "<tr>";
            echo "<th colspan='1'>".__('Links','genericobject')."</th>";
            echo "</tr>";

            echo"<td class='center' >".__('Nothing to show','genericobject')."</td>";

            echo "</table>";
        }




    }

}
