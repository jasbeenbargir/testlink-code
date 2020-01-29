<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * @filesource: aliensEdit.php
 *
 * Allows users to create/edit aliens. 
 *
 * @package    TestLink
 * @copyright  2020 TestLink community 
 * @link       http://www.testlink.org/
 *  
**/
require_once("../../config.inc.php");
require_once("common.php");
require_once("csv.inc.php");
require_once("xml.inc.php");
require_once("aliensEnv.php");


testlinkInitPage($db);
$tplCfg = templateConfiguration();

$tplEngine = new TLSmarty();

$op = new stdClass();
$op->status = 0;

$args = initEnv($db);
$gui = initializeGui($db,$args);

$tprojectMgr = new testproject($db);
$alienMgr = new tlAlien($db,$args->tproject_id);

$action = $args->doAction;

switch ($action) {
  case "do_create":
  case "do_update":
  case "do_delete":
  case "edit":
  case "create":
  case "cfl":
  case "do_cfl":
    $op = $action($args,$gui,$tprojectMgr);
  break;
}


if($op->status == 1) {
  $tpl = $op->template;
} else {
  $tpl = $tplCfg->default_template;
  $gui->user_feedback = getAlienErrorMessage($op->status);
}

$gui->aliens = null;
$gui->submitCode = "";
if ($tpl != $tplCfg->default_template) {
  // I'm going to return to screen that display all aliens
  $kwe = getAliensEnv($db,$args->user,$args->tproject_id);
  foreach($kwe as $prop => $val) {
    $gui->$prop = $val;
  }  
  $setUpDialog = $gui->openByOther;  
} else {
  $setUpDialog = $gui->directAccess;  
  $gui->submitCode="return dialog_onSubmit($gui->dialogName)";
}

if( $setUpDialog ) {
  $gui->dialogName = 'alien_dialog';
  $gui->bodyOnLoad = "dialog_onLoad($gui->dialogName)";
  $gui->bodyOnUnload = "dialog_onUnload($gui->dialogName)";  

  if( $gui->directAccess ) {
    $gui->submitCode = "return dialog_onSubmit($gui->dialogName)";
  }  
}

$tplEngine->assign('gui',$gui);
$tplEngine->display($tplCfg->template_dir . $tpl);


/**
 * @return object returns the arguments for the page
 */
function initEnv(&$dbHandler) {
  $args = new stdClass();
  $_REQUEST = strings_stripSlashes($_REQUEST);
  $source = sizeof($_POST) ? "POST" : "GET";
  
  $ipcfg = 
    array( "doAction" => array($source,tlInputParameter::STRING_N,0,50),
           "id" => array($source, tlInputParameter::INT_N),
           "name" => array($source, tlInputParameter::STRING_N,0,100),
           "notes" => array($source, tlInputParameter::STRING_N),
           "tproject_id" => array($source, tlInputParameter::INT_N),
           "openByOther" => array($source, tlInputParameter::INT_N),
           "directAccess" => array($source, tlInputParameter::INT_N),
           "tcversion_id" => array($source, tlInputParameter::INT_N));
    
  $ip = I_PARAMS($ipcfg);

  $args = new stdClass();
  $args->doAction = $ip["doAction"];
  $args->notes = $ip["notes"];
  $args->name = $ip["name"];
  $args->alien_id = $ip["id"];
  $args->tproject_id = $ip["tproject_id"];
  $args->openByOther = intval($ip["openByOther"]);
  $args->directAccess = intval($ip["directAccess"]);
  $args->tcversion_id = intval($ip["tcversion_id"]);

  if( $args->tproject_id <= 0 ) {
    throw new Exception("Error Invalid Test Project ID", 1);
  }

  // Check rights before doing anything else
  // Abort if rights are not enough 
  $args->user = $_SESSION['currentUser'];
  $env['tproject_id'] = $args->tproject_id;
  $env['tplan_id'] = 0;
  
  $check = new stdClass();
  $check->items = array('mgt_modify_key','mgt_view_key');
  $check->mode = 'and';
  checkAccess($dbHandler,$args->user,$env,$check);

  // OK Go ahead
  $args->canManage = true;
  $args->mgt_view_events = 
    $args->user->hasRight($dbHandler,"mgt_view_events",$args->tproject_id);

  $treeMgr = new tree($dbHandler);
  $dummy = $treeMgr->get_node_hierarchy_info($args->tproject_id);
  $args->tproject_name = $dummy['name'];  

  return $args;
}

/*
 *  initialize variables to launch user interface (smarty template)
 *  to get information to accomplish create task.
*/
function create(&$argsObj,&$guiObj) {
  $guiObj->submit_button_action = 'do_create';
  $guiObj->submit_button_label = lang_get('btn_save');
  $guiObj->main_descr = lang_get('alien_management');
  $guiObj->action_descr = lang_get('create_alien');

  $ret = new stdClass();
  $ret->template = 'aliensEdit.tpl';
  $ret->status = 1;
  return $ret;
}

/*
 *  initialize variables to launch user interface (smarty template)
 *  to get information to accomplish edit task.
*/
function edit(&$argsObj,&$guiObj,&$alienMgr) {
  $guiObj->submit_button_action = 'do_update';
  $guiObj->submit_button_label = lang_get('btn_save');
  $guiObj->main_descr = lang_get('alien_management');
  $guiObj->action_descr = lang_get('edit_alien');

  $ret = new stdClass();
  $ret->template = 'aliensEdit.tpl';
  $ret->status = 1;

  $item = $alienMgr->getByID($argsObj->alien_id);
  if ($item) {
    $guiObj->name = $argsObj->name = $item->name;
    $guiObj->notes = $argsObj->notes = $item->notes;
    $guiObj->action_descr .= TITLE_SEP . $guiObj->item;
  }

  return $ret;
}

/*
 * Creates the keyword
 */
function do_create(&$args,&$guiObj,&$tproject_mgr) {
  $guiObj->submit_button_action = 'do_create';
  $guiObj->submit_button_label = lang_get('btn_save');
  $guiObj->main_descr = lang_get('keyword_management');
  $guiObj->action_descr = lang_get('create_alien');

  $op = $tproject_mgr->addAlien($args->tproject_id,$args->keyword,$args->notes);
  $ret = new stdClass();
  $ret->template = 'aliensView.tpl';
  $ret->status = $op['status'];
  return $ret;
}

/*
 * Updates the keyword
 */
function do_update(&$argsObj,&$guiObj,&$tproject_mgr) {
  $guiObj->submit_button_action = 'do_update';
  $guiObj->submit_button_label = lang_get('btn_save');
  $guiObj->main_descr = lang_get('keyword_management');
  $guiObj->action_descr = lang_get('edit_alien');

  $keyword = $tproject_mgr->getAlien($argsObj->keyword_id);
  if ($keyword) {
    $guiObj->action_descr .= TITLE_SEP . $keyword->name;
  }
  
  $ret = new stdClass();
  $ret->template = 'aliensView.tpl';
  $ret->status = $tproject_mgr->updateAlien($argsObj->tproject_id,
    $argsObj->keyword_id,$argsObj->keyword,$argsObj->notes);
  return $ret;
}

/*
 * Deletes the keyword 
 */
function do_delete(&$args,&$guiObj,&$tproject_mgr) {
  $guiObj->submit_button_action = 'do_update';
  $guiObj->submit_button_label = lang_get('btn_save');
  $guiObj->main_descr = lang_get('keyword_management');
  $guiObj->action_descr = lang_get('delete_alien');

  $ret = new stdClass();
  $ret->template = 'aliensView.tpl';

  $dko = array('context' => 'getTestProjectName',
               'tproject_id' => $args->tproject_id);
  $ret->status = $tproject_mgr->deleteAlien($args->keyword_id,$dko);

  return $ret;
}

/*
 *  initialize variables to launch user interface (smarty template)
 *  to get information to accomplish create task.
*/
function cfl(&$argsObj,&$guiObj) {
  $guiObj->submit_button_action = 'do_cfl';
  $guiObj->submit_button_label = lang_get('btn_create_and_link');
  $guiObj->main_descr = lang_get('keyword_management');
  $guiObj->action_descr = lang_get('create_alien_and_link');

  $ret = new stdClass();
  $ret->template = 'aliensEdit.tpl';
  $ret->status = 1;
  return $ret;
}

/*
 * Creates the keyword
 */
function do_cfl(&$args,&$guiObj,&$tproject_mgr) {
  $guiObj->submit_button_action = 'do_cfl';
  $guiObj->submit_button_label = lang_get('btn_save');
  $guiObj->main_descr = lang_get('keyword_management');
  $guiObj->action_descr = lang_get('create_alien');

  $op = $tproject_mgr->addAlien($args->tproject_id,$args->keyword,$args->notes);
  
  if( $op['status'] ) {
    $tcaseMgr = new testcase($tproject_mgr->db);
    $tbl = tlObject::getDBTables('nodes_hierarchy');
    $sql = "SELECT parent_id FROM {$tbl['nodes_hierarchy']}
            WHERE id=" . intval($args->tcversion_id);
    $rs = $tproject_mgr->db->get_recordset($sql);
    $tcase_id = intval($rs[0]['parent_id']);
    $tcaseMgr->addAliens($tcase_id,$args->tcversion_id,
        array($op['id']));
  }

  $ret = new stdClass();
  $ret->template = 'aliensView.tpl';
  $ret->status = $op['status'];
  return $ret;
}



/**
 *
 */
function getAlienErrorMessage($code) {

  switch($code) {
    case tlAlien::E_NAMENOTALLOWED:
      $msg = lang_get('aliens_char_not_allowed'); 
      break;

    case tlAlien::E_NAMELENGTH:
      $msg = lang_get('empty_alien_no');
      break;

    case tlAlien::E_DBERROR:
    case ERROR: 
      $msg = lang_get('alien_update_fails');
      break;

    case tlAlien::E_NAMEALREADYEXISTS:
      $msg = lang_get('keyword_already_exists');
      break;

    default:
      $msg = 'ok';
  }
  return $msg;
}

/**
 *
 *
 */
function initializeGui(&$dbH,&$args) {

  $gui = new stdClass();
  $gui->openByOther = $args->openByOther;
  $gui->directAccess = $args->directAccess;
  $gui->tcversion_id = $args->tcversion_id;

  $gui->user_feedback = '';

  // Needed by the smarty template to be launched
  $kr = array('canManage' => "mgt_modify_key", 
              'canAssign' => "keyword_assignment");
  foreach( $kr as $vk => $rk ) {
    $gui->$vk = 
      $args->user->hasRight($dbH,$rk,$args->tproject_id);
  }

  $gui->tproject_id = $args->tproject_id;
  $gui->canManage = $args->canManage;
  $gui->mgt_view_events = $args->mgt_view_events;
  $gui->notes = $args->notes;
  $gui->name = $args->alien;
  $gui->alien = $args->alien;
  $gui->alien_id = $args->alien_id;

  $gui->editUrl = $_SESSION['basehref'] . "lib/aliens/aliensEdit.php?" .
                  "tproject_id={$gui->tproject_id}"; 

  return $gui;
}
