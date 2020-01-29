<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later.
 *
 * @filesource  tlPlatform.class.php
 * @package     TestLink
 * @author      Francisco Mancardi
 * @copyright   2020, TestLink community
 * @link        http://www.testlink.org
 *
 */

/**
 * Class for alien (a special kind of keyword/tag)
 * 
 **/
class tlAlien extends tlObjectWithDB
{
  protected $tproject_id;
  protected $stdFields;

  const E_NAMENOTALLOWED = -1;
  const E_NAMELENGTH = -2;
  const E_NAMEALREADYEXISTS = -4;
  const E_DBERROR = -8;
  const E_WRONGFORMAT = -16;


  /**
   * @param $db database object
   * @param $tproject_id to work on. 
   */
  public function __construct(&$db, $tproject_id = null) {
    parent::__construct($db);
    $this->tproject_id = $tproject_id;
    $this->stdFields = "id, name, notes, link_type, 
                        testproject_id, issuetracker_id";

  }

  /**
   * 
   * 
   */
  public function setTestProjectID($tproject_id) {
    $this->tproject_id = intval($tproject_id);  
  }


  /**
   * 
   * @return tl::OK on success otherwise E_DBERROR;
   */
  public function create($item) {

    $op = array('status' => self::E_DBERROR, 'id' => -1);
    $safeName = $this->throwIfEmptyName($item->name);
    $alreadyExists = $this->getID($safeName);

    if ($alreadyExists) {
      $op = array('status' => self::E_NAMEALREADYEXISTS, 'id' => -1);
    } else {
      $sql = "INSERT INTO {$this->tables['aliens']} 
              (name, testproject_id, notes, link_type) 
              VALUES (" .
              "'" . $this->db->prepare_string($safeName) . "'" .
              "," . $this->tproject_id .
              ",'" . $this->db->prepare_string($item->notes) . "'" .
              "," . $item->link_type . ")";
      $result = $this->db->exec_query($sql);

      if( $result ) {
        $op['status'] = tl::OK;
        $op['id'] = $this->db->insert_id($this->tables['aliens']);
      } 
    }
    return $op;
  }

  /**
   * Gets info by ID
   *
   * @return array 
   */
  public function getByID($id,$opt=null) {
    $idSet = implode(',',(array)$id);
    $options = array('fields' => $this->stdFields,
                     'accessKey' => null);
    $options = array_merge($options,(array)$opt);
    
    $sql =  " SELECT {$options['fields']}
              FROM {$this->tables['aliens']} 
              WHERE id IN ($idSet) ";
    
    switch ($options['accessKey']) {
      case 'id':
      case 'name':
        $accessKey = $options['accessKey'];
      break;

      default:
        if (count($idSet) == 1) {
          return $this->db->fetchFirstRow($sql);
        }
        $accessKey = 'id';
      break;
    }          
    return $this->db->fetchRowsIntoMap($sql,$accessKey);
  }


  /**
   *
   */
  public function getByName($name)
  {
    $val = trim($name);
    $sql =  " SELECT {$this->stdFields} 
              FROM {$this->tables['aliens']} 
              WHERE name = '" . 
              $this->db->prepare_string($val) . "'" .
            " AND testproject_id = " . intval($this->tproject_id);
    
    $ret = $this->db->fetchFirstRow($sql);
    return is_array($ret) ? $ret : null;        
  }


  
  /**
   * Gets all info of an alien
   * @return array with keys id, name and notes
   */
  public function getAlien($id)
  {
    return $this->getByID($id);
  }

  /**
   * Updates values of a alien in database.
   * @param $id the id of the alien to update
   * @param $name the new name to be set
   * @param $notes new notes to be set
   *
   * @return tl::OK on success, otherwise E_DBERROR
   */
  public function update($item)
  {
    $safeName = $this->throwIfEmptyName($item->name);
    $sql = " UPDATE {$this->tables['aliens']} 
             SET name = '" . $this->db->prepare_string($item->name) . "' " .
           ", notes =  '". $this->db->prepare_string($item->notes) . "' " .
           ", link_type =  " . $item->link_type .
           "WHERE id = {$item->id}";

    $result =  $this->db->exec_query($sql);
    return $result ? tl::OK : self::E_DBERROR;
  }

  /**
   * Removes a alien from the database.
   * @TODO: remove all related data to this alien?
   *        YES!
   * @param $id the alien_id to delete
   *
   * @return tl::OK on success, otherwise E_DBERROR
   */
  public function delete($id)
  {
    $sql = "DELETE FROM {$this->tables['aliens']} WHERE id = {$id}";
    $result = $this->db->exec_query($sql);
    
    return $result ? tl::OK : self::E_DBERROR;
  }


  /**
   * Gets the id of a alien given by name
   *
   * @return integer alien_id
   */
  public function getID($name)
  {
    $sql = " SELECT id FROM {$this->tables['aliens']} 
             WHERE name = '" . $this->db->prepare_string($name) . "'" .
           " AND testproject_id = {$this->tproject_id} ";
    return $this->db->fetchOneValue($sql);
  }

  /**
   * get all available aliens on active test project
   *
   * @options array $options Optional params
   *                         ['include_linked_count'] => adds the number of
   *                         testplans this alien is used in
   *                         
   * @return array 
   */
  public function getAll($options = null) {
    $debugMsg = 'Class:' . __CLASS__ . ' - Method: ' . __FUNCTION__;
    $default = array('include_linked_count' => false);
    $options = array_merge($default, (array)$options);
    
    $tproject_filter = " WHERE ALIEN.testproject_id = {$this->tproject_id} ";

    $sql =  " SELECT {$this->stdFields} 
              FROM {$this->tables['aliens']} ALIEN 
              {$tproject_filter}
              ORDER BY name";
    echo $sql;
    $rs = $this->db->get_recordset($sql);
    return $rs;
  }

  /**
   * get all available aliens in the active testproject ($this->tproject_id)
   * @param string $orderBy
   * @return array Returns 
   *               as array($alien_id => $alien_name)
   */
  public function getAllAsMap($opt=null)
  {
    $options = array('accessKey' => 'id',
                     'output' => 'columns',
                     'orderBy' => ' ORDER BY name ',
                     'enable_on_design' => true,
                     'enable_on_execution' => true);

    $options = array_merge($options,(array)$opt);
    $accessKey = $options['accessKey'];
    $output = $options['output'];
    $orderBy = $options['orderBy'];
    
    $debugMsg = 'Class:' . __CLASS__ . ' - Method: ' . __FUNCTION__;
    $sql =  "/* $debugMsg */  
             SELECT {$this->stdFields}
             FROM {$this->tables['aliens']} 
             WHERE testproject_id = {$this->tproject_id} 
             {$orderBy}";

    if( $output == 'columns' ) {
      $rs = $this->db->fetchColumnsIntoMap($sql, $accessKey, 'name');
    } else {
      $rs = $this->db->fetchRowsIntoMap($sql, $accessKey);
    }  
    return $rs;
  }


  /**
   * 
   *         
   */
  public function throwIfEmptyName($name)
  {
    $safeName = trim($name);
    if (tlStringLen($safeName) == 0) {
      $msg = "Class: " . __CLASS__ . " - " . "Method: " . __FUNCTION__ ;
      $msg .= " Empty name ";
      throw new Exception($msg);
    }
    return $safeName;
  }


  /**
   * 
    *
    */
  public function deleteByTestProject($tproject_id)
  {
    $sql = "DELETE FROM {$this->tables['aliens']} 
            WHERE testproject_id = {$tproject_id}";
    $result = $this->db->exec_query($sql);
    
    return $result ? tl::OK : self::E_DBERROR;
  }


  /**
   *
   */
  public function testProjectCount($opt=null)
  {
    $debugMsg = '/* Class:' . __CLASS__ . ' - Method: ' . __FUNCTION__ . '*/ ';
    $my['opt'] = array('range' => 'tproject');
    $my['opt'] = array_merge($my['opt'],(array)$opt);
    
    
    // HINT: COALESCE(COUNT(ALIEN.id),0)
    //       allows to get 0 on alien_qty
    //
    $sql = "/* $debugMsg */ 
            SELECT COALESCE(COUNT(ALIEN.id),0) AS alien_qty, 
            TPROJ.id AS tproject_id 
            FROM {$this->tables['testprojects']} TPROJ 
            LEFT OUTER JOIN {$this->tables['aliens']} 
            ALIEN ON ALIEN.testproject_id = TPROJ.id ";
    
    switch ($my['opt']['range']) {
      case 'tproject':
        $sql .= " WHERE TPROJ.id = " . $this->tproject_id ;
      break;
    }
    $sql .= " GROUP BY TPROJ.id ";
    return ($this->db->fetchRowsIntoMap($sql,'tproject_id'));        
  }


  /**
   *
   */
  public function belongsToTestProject($id,$tproject_id = null)
  {
    $debugMsg = '/* Class:' . __CLASS__ . ' - Method: ' . __FUNCTION__ . '*/ ';
    $pid = intval(is_null($tproject_id) ? $this->tproject_id : $tproject_id);
    
    $sql = " SELECT id FROM {$this->tables['aliens']} " .
           " WHERE id = " . intval($id) . " AND testproject_id=" . $pid;
    $dummy =  $this->db->fetchRowsIntoMap($sql,'id');
    return isset($dummy['id']);
  }  


  /**
   *
   */
  function initViewGUI( &$userObj ) {
    $gaga = new stdClass();
    
    $gaga->tproject_id = $this->tproject_id;
    
    $cfg = getWebEditorCfg('alien');
    $gaga->editorType = $cfg['type'];
    $gaga->user_feedback = null;
    $gaga->user_feedback = array('type' => 'INFO', 'message' => '');

    $opx = array('include_linked_count' => true,
                 'enable_on_design' => null, 
                 'enable_on_execution' => null);
    $gaga->aliens = $this->getAll($opx);

    
    $rx = array('canManage' => 'keyword_management', 
                'mgt_view_events' => 'mgt_view_events');
    foreach($rx as $prop => $right) {
      $gaga->$prop = $userObj->hasRight($this->db->db,$right,
                                        $this->tproject_id);
    }

    return $gaga;
  }
}
