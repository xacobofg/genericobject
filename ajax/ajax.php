<?php

/*
   ------------------------------------------------------------------------
   GLPI Plugin MantisBT
   Copyright (C) 2014 by the GLPI Plugin MantisBT Development Team.

   https://forge.indepnet.net/projects/mantis
   ------------------------------------------------------------------------

   LICENSE

   This file is part of GLPI Plugin GenericObject project.

   GLPI Plugin GenericObject is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 3 of the License, or
   (at your option) any later version.

   GLPI Plugin GenericObject is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with GLPI Plugin GenericObject. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   GLPI Plugin GenericObject
   @author    Stanislas Kita (teclib')
   @co-author François Legastelois (teclib')
   @co-author Le Conseil d'Etat
   @copyright Copyright (c) 2014 GLPI Plugin GenericObject Development team
   @license   GPLv3 or (at your option) any later version
              http://www.gnu.org/licenses/gpl.html
   @link      https://forge.indepnet.net/projects/mantis
   @since     2014

   ------------------------------------------------------------------------
 */

include('../../../inc/includes.php');

if (isset($_POST['action'])) {

    global $CFG_GLPI;

   switch ($_POST['action']) {


       case 'updateDropdown':

           $object =  new $_POST['object']();
           $itemType = $_POST['itemType'];
	   $id = $_POST['id'];
           PluginGenericobjectObject_Item::getDropdownItemLinked($object,$itemType,$id);

           break;
       case 'updateLink':

           PluginGenericobjectObject_Item::getItemListForObject($_POST['itemType'], $_POST['rand'],$_POST['objItem'], $_POST['idItemType']);

           break;


      case 'linkObject':

          $idMainObject = $_POST['idMainobject'];
          $nameMainObject = $_POST['mainobject'];
          $nameObjectToAdd = $_POST['objectToAdd'];
          $nameToAdd = $_POST['name'];

          $objectToAdd = new  $nameObjectToAdd();
          $mainObject = new  $nameMainObject;
          $mainObject->getFromDB(1);



          $nameMainObject = $nameMainObject.'_item';
          $nameObjectToAdd = $nameObjectToAdd.'_item';

          $mainObjectItem = new  $nameMainObject ();
          $mainObjectToAddItem = new  $nameObjectToAdd ();



          //id de l'objet rajouté
          $objectToAdd->getFromDBByQuery("WHERE name = '".$nameToAdd."'");
          $idObjectToAdd = $objectToAdd->fields['id'];

          $coluimn = str_replace('glpi_','',$mainObject->table.'_id');
          $coluimn1 = str_replace('glpi_','',$objectToAdd->table.'_id');

          $res = $mainObjectItem->getFromDBByQuery("WHERE `items_id` = ".$idObjectToAdd." AND `".$coluimn."` = ".$idMainObject." AND `itemtype` = '". $_POST['objectToAdd']."'");

          if($res){

              echo returnError(__('This Object is already link','genericobject'));

          }else{

              $values= array();
              //$values['id'] = null;
              $values['items_id'] = $idObjectToAdd;
              $values[$coluimn] = $idMainObject;
              $values['itemtype'] = $_POST['objectToAdd'];
              $mainObjectItem->add($values);


              $values= array();
              //$values['id'] = null;
              $values['items_id'] = $idMainObject;
              $values[$coluimn1] = $idObjectToAdd;
              $values['itemtype'] = $_POST['mainobject'];
              $mainObjectToAddItem->add($values);


              echo returnSuccess();
          }

         break;


       case 'delLinkObject':


           $itemType = $_POST['itemType'];
           $nameObject = $itemType.'_item';
           $mainObjectItem = new  $nameObject ();
           $mainObjectItem->getFromDB($_POST['id']);

           $values = array();
           $values['id'] = $_POST['id'];

           $mainObjectItem->delete($values);






           $targetTable = $mainObjectItem->fields['itemtype'];
           $targetTableItem = $mainObjectItem->fields['itemtype']."_item";
           $idMainItem = $_POST['idMainItem'];


           $targetObject = new $targetTable();
           $targetObjectItem = new $targetTableItem();


           $column = str_replace('glpi_','',$targetObject->table.'_id');


           $targetObjectItem->getFromDBByQuery("WHERE `itemtype` = '".$itemType."' AND `items_id` = ".$idMainItem." and `".$column."` = ".$mainObjectItem->fields['items_id']);





           $values = array();
           $values['id'] = $targetObjectItem->fields['id'];


           $targetObjectItem->delete($values);

           break;


      default:
         echo 0;
   }

} else {
   echo 0;
}

function returnSuccess(){
    global $CFG_GLPI;
    return "<img src='" . $CFG_GLPI['root_doc'] . "/plugins/genericobject/pics/check16.png'/>";
}

function returnError($error){
    global $CFG_GLPI;
    return "<div><img src='" . $CFG_GLPI['root_doc'] . "/plugins/genericobject/pics/cross16.png'/> ".$error."</div>";
}

