<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    MapJoined Display last joined users on map
 * @ingroup     UnaModules
 *
 * @{
 */

bx_import('BxDolModuleDb');

class BxMapJoinedDb extends BxBaseModGeneralDb
{
    function __construct(&$oConfig)
    {
        parent::__construct($oConfig);
    }
    
    public function getLngLatData($iLastId)
    {
        $CNF = &$this->_oConfig->CNF;
        $sIntervalInHour = getParam('bx_mapjoined_initial_timeframe_users_shown_in_hours');
        $sSql = "";
        if ($iLastId == 0) {
            $sSql = $CNF['FIELD_JOINED'] . " > date_sub(now(), INTERVAL " . $sIntervalInHour . " hour) ";
        }
        else{
            $sSql = $CNF['FIELD_ID'] . " > " . $iLastId;
        }
        
        return $this->getAll("SELECT " . $CNF['FIELD_LNG'] . ", " . $CNF['FIELD_LAT'] . ", " . $CNF['FIELD_ID'] . " FROM " . $CNF['TABLE_ENTRIES'] . " WHERE " . $sSql);
    }
    
    public function addIpInfo($iAccountId, $sIp, $sLng, $sLat)
    {
        $CNF = &$this->_oConfig->CNF;
        $aBindings = array(
            'account_id' => $iAccountId,
            'ip' => $sIp,
            'lng' => $sLng,
            'lat' => $sLat
        );
        $this->query("INSERT INTO " . $CNF['TABLE_ENTRIES'] . " (" . $CNF['FIELD_ACCOUNT_ID'] . ", " . $CNF['FIELD_IP'] . ", " . $CNF['FIELD_LNG'] . ", " . $CNF['FIELD_LAT'] . ") values (:account_id, :ip, :lng, :lat)", $aBindings);
    }
}

/** @} */
