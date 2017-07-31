<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

/**
 * All queries related to account
 */
class BxDolAccountQuery extends BxDolDb implements iBxDolSingleton
{
    protected function __construct()
    {
        if (isset($GLOBALS['bxDolClasses'][get_class($this)]))
            trigger_error ('Multiple instances are not allowed for the class: ' . get_class($this), E_USER_ERROR);

        parent::__construct();
    }

    /**
     * Prevent cloning the instance
     */
    public function __clone()
    {
        if (isset($GLOBALS['bxDolClasses'][get_class($this)]))
            trigger_error('Clone is not allowed for the class: ' . get_class($this), E_USER_ERROR);
    }

    /**
     * Get singleton instance of the class
     */
    public static function getInstance()
    {
        if(!isset($GLOBALS['bxDolClasses'][__CLASS__]))
            $GLOBALS['bxDolClasses'][__CLASS__] = new BxDolAccountQuery();

        return $GLOBALS['bxDolClasses'][__CLASS__];
    }

    public function getOperators ()
    {
        $sSql = $this->prepare("SELECT `id` FROM `sys_accounts` WHERE `role`&" . BX_DOL_ROLE_ADMIN);
        return $this->getColumn($sSql);
    }
    
    /**
     * Get account by specified field name and value.
     * It is for internal usage only.
     * Use other funtions to get account info, like getInfoById, etc.
     * @param  string $sField database field name
     * @param  mixed  $sValue database field value
     * @return array  with porfile info
     */
    protected function _getDataByField ($sField, $sValue)
    {
        $sSql = $this->prepare("SELECT * FROM `sys_accounts` WHERE `$sField` = ? LIMIT 1", $sValue);
        return $this->getRow($sSql);
    }

    /**
     * Get account info by id
     * @param  int   $iID account id
     * @return array with account info
     */
    public function getInfoById( $iID )
    {
        return $this->_getDataByField('id', (int)$iID);
    }

    /**
     * get account id by emial
     */
    public function getIdByEmail($sEmail)
    {
        return (int)$this->_getFieldByField('id', 'email', $sEmail);
    }

    /**
     * get account id by id
     */
    public function getIdById($iId)
    {
        return (int)$this->_getFieldByField('id', 'id', $iId);
    }

    /**
     * get first studio operator id
     */
    public function getStudioOperatorId()
    {
        return (int)$this->_getFieldByField('id', 'role', 3);
    }

    /**
     * Get account email by id
     * @param  string  $s search account by this id
     * @return account email
     */
    public function getEmail($iID)
    {
        return $this->_getFieldByField('email', 'id', (int)$iID);
    }

    /**
     * Get account password by id
     * @param  string  $s search account by this id
     * @return account password
     */
    public function getPassword($iID)
    {
        return $this->_getFieldByField('password', 'id', (int)$iID);
    }

	/**
     * Is account online by id
     * @param  int $iId account id 
     * @return account online status
     */
    public function isOnline($iId)
    {
        $sSql = $this->prepare("SELECT 
        		`ta`.`id` 
        	FROM `sys_accounts` AS `ta` 
        	INNER JOIN `sys_sessions` AS `ts` ON `ta`.`id`=`ts`.`user_id` 
        	WHERE 
        		`ta`.`id` = ? AND 
        		`ts`.`date` > (UNIX_TIMESTAMP() - 60 * ?) 
        	LIMIT 1", $iId, (int)getParam('sys_account_online_time'));
        return (int)$this->getOne($sSql) > 0;
    }

    /**
     * Update last logged in time
     * @param $sPasswordHash - password hash
     * @param $sSalt - pasword salt
     * @param $iAccountId - account id to update password for
     * @return number of affected rows
     */
    public function updatePassword($sPasswordHash, $sSalt, $iAccountId)
    {
        $sQuery = $this->prepare("UPDATE `sys_accounts` SET `password` = ?, `salt` = ? WHERE `id`= ?", $sPasswordHash, $sSalt, $iAccountId);
        return $this->query($sQuery);
    }

    /**
     * Update last logged in time
     * @param  int    $iID account id
     * @return number of affected rows
     */
    public function updateLoggedIn($iID)
    {
        return $this->_updateField ($iID, 'logged', time());
    }

    /**
     * Update language
     * @param  int    $iID account id
     * @return number of affected rows
     */
    public function updateLanguage($iID, $iLangId)
    {
        return $this->_updateField ($iID, 'lang_id', $iLangId);
    }

    /**
     * Update current profile id associated with account
     * @param  int    $iID        account id
     * @param  int    $iProfileId set current profile id to this value
     * @return number of affected rows
     */
    public function updateCurrentProfile($iID, $iProfileId)
    {
        return $this->_updateField ($iID, 'profile_id', $iProfileId);
    }

    /**
     * Update 'email_confirmed' field.
     * @param  int    $isConfirmed - 0: mark email as unconfirmed, 1: as confirmed
     * @param  int    $iID         - account id
     * @return number of affected rows
     */
    public function updateEmailConfirmed($isConfirmed, $iID)
    {
        return $this->_updateField ($iID, 'email_confirmed', $isConfirmed ? 1 : 0);
    }

    /**
     * Get account field by specified field name and value.
     * In most cases it is for internal usage only.
     * Use other funtions to get account info, like getIdByEmail, etc.
     * @param  string    $sFieldRequested database field name to return
     * @param  string    $sFieldSearch    database field name to search for
     * @param  mixed     $sValue          database field value
     * @return specified account field value
     */
    protected function _getFieldByField ($sFieldRequested, $sFieldSearch, $sValue)
    {
        $sSql = $this->prepare("SELECT `$sFieldRequested` FROM `sys_accounts` WHERE `$sFieldSearch` = ? LIMIT 1", $sValue);
        return $this->getOne($sSql);
    }

    /**
     * Update some field by account id
     * In most cases it is for internal usage only.
     * Use other funtions to get account info, like updateLogged, etc.
     * @param  string    $sFieldRequested database field name to return
     * @param  string    $sFieldSearch    database field name to search for
     * @param  mixed     $sValue          database field value
     * @return specified account field value
     */
    protected function _updateField ($iId, $sFieldForUpdate, $sValue)
    {
        $sSql = $this->prepare("UPDATE `sys_accounts` SET `$sFieldForUpdate` = ? WHERE `id` = ? LIMIT 1", $sValue, $iId);
        return $this->query($sSql);
    }

    /**
     * Delete account info by id
     * @param  int      $iID profile id
     * @return affected rows
     */
    public function delete($iID)
    {
        $sSql = $this->prepare("DELETE FROM `sys_accounts` WHERE `id` = ? LIMIT 1", $iID);
        return $this->query($sSql);
    }

	/**
     * Get account(s) by params
     * @param  array   $aParams browse params
     * @return array with account(s)
     */
    public function getAccounts($aParams)
    {
        $aMethod = array('name' => 'getAll', 'params' => array(0 => 'query'));

    	$sFieldsClause = "`ta`.*"; 
    	$sJoinClause = $sWhereClause = $sGroupClause = $sOrderClause = "";

    	switch($aParams['type']) {
    		case 'by_join_date':
    			$aMethod['params'][1] = array(
    				'date' => $aParams['date']
    			);

    			$sWhereClause = " AND `ta`.`added` > :date AND `ta`.`added` < UNIX_TIMESTAMP()";
    			break;
    	}

    	$sGroupClause = $sGroupClause ? "GROUP BY " . $sGroupClause : "";
		$sOrderClause = $sOrderClause ? "ORDER BY " . $sOrderClause : "";

		$aMethod['params'][0] = "SELECT
				" . $sFieldsClause . "
            FROM `sys_accounts` AS `ta`" . $sJoinClause . "
            WHERE 1" . $sWhereClause . " " . $sGroupClause . " " . $sOrderClause;

        return call_user_func_array(array($this, $aMethod['name']), $aMethod['params']);
    }

    /**
     * Search account profile by keyword
     */
    public function searchByTerm($sTerm, $iLimit)
    {
    	$aBindings = array(
    		'system' => 'system',
    		'status' => BX_PROFILE_STATUS_ACTIVE,
    		'limit' => (int)$iLimit
    	);

        $sWhere = '';
        $aFieldsQuickSearch = array('name', 'email');
        foreach ($aFieldsQuickSearch as $sField) {
        	$aBindings[$sField] = '%' . $sTerm . '%';

            $sWhere .= " OR `c`.`$sField` LIKE :$sField ";
        }

        $sQuery = "SELECT `c`.`id` AS `content_id`, `p`.`account_id`, `p`.`id` AS `profile_id`, `p`.`status` AS `profile_status` FROM `sys_accounts` AS `c` INNER JOIN `sys_profiles` AS `p` ON (`p`.`content_id` = `c`.`id` AND `p`.`type` = :system) WHERE `p`.`status` = :status AND (0 $sWhere) ORDER BY `added` DESC LIMIT :limit";
        return $this->getAll($sQuery, $aBindings);
    }
}

/** @} */