<?php
include ("../../../inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST["itemtype"])) {
	if ($_POST["itemtype"]!="0") {
      Dropdown::show($_POST["itemtype"],['name'=>"plugin_object_link"]);
   }
}