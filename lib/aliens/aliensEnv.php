<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * @filesource: aliensView.php
 *
 * utilities functions 
 */

/**
 */
function getAliensEnv(&$dbH,&$user,$tproject_id,$opt=null) {
  $alEnv = new stdClass();

  $options = array('usage' => null);
  $options = array_merge($options,(array)$opt);

  $tproject = new testproject($dbH);
  $alienMgr = new tlAlien($dbH,$tproject_id);
  
  $alEnv->aliens = $alienMgr->getAll();
  echo '<pre>GGG';
  var_dump($alEnv);
  echo '</pre>';

  
  $alEnv->alExecStatus = null;
  $alEnv->alFreshStatus = null;
  $alEnv->alOnTCV = null;

  if( null != $alEnv->aliens ) {
    $als = array();
    $alNames = array();
    $alNotes = array();
    $more = ($options['usage'] == 'csvExport');

    foreach( $alEnv->aliens as $alo ) {
      $als[] = $alo->dbID;
      if( $more ) {
        $alNames[$alo->dbID] = $alo->name;
        $alNotes[$alo->dbID] = $alo->notes;        
      }
    }

    /*
    // Count how many times the alien has been used
    $alEnv->alOnTCV = (array)$tproject->countAlienUsageInTCVersions($tproject_id);
    if( $more && count($alEnv->alOnTCV) > 0) {
      foreach($alEnv->alOnTCV as $kk => $dummy) {
        $alEnv->alOnTCV[$kk]['alien'] = $alNames[$kk];
        $alEnv->alOnTCV[$kk]['notes'] = $alNotes[$kk];        
      }
    }
    */

    /*
    $alCfg = config_get('aliens');

    if( $alCfg->onDeleteCheckExecutedTCVersions ) {
      $alEnv->alExecStatus = 
        $tproject->getAliensExecStatus($als,$tproject_id);        
    }

    if( $alCfg->onDeleteCheckFrozenTCVersions ) {
      $alEnv->alFreshStatus = 
        $tproject->getAliensFreezeStatus($als,$tproject_id);  
    }
    */
  }

  $alEnv->canManage = $user->hasRight($dbH,"mgt_modify_key",$tproject_id);
  $alEnv->canAssign = $user->hasRight($dbH,"keyword_assignment",$tproject_id);

  $alEnv->editUrl = $_SESSION['basehref'] . "lib/aliens/aliensEdit.php?" .
                   "tproject_id={$tproject_id}"; 
  return $alEnv;
}