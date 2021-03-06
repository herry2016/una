<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaStudio UNA Studio
 * @{
 */

define('BX_DOL_STUDIO_STG_TYPE_SYSTEM', 'system');
define('BX_DOL_STUDIO_STG_TYPE_DEFAULT', BX_DOL_STUDIO_STG_TYPE_SYSTEM);

define('BX_DOL_STUDIO_STG_GROUP_SYSTEM', 'system');
define('BX_DOL_STUDIO_STG_GROUP_MODULES', 'modules');
define('BX_DOL_STUDIO_STG_GROUP_LANGUAGES', 'languages');
define('BX_DOL_STUDIO_STG_GROUP_TEMPLATES', 'templates');

define('BX_DOL_STUDIO_STG_CATEGORY_SYSTEM', 'system');
define('BX_DOL_STUDIO_STG_CATEGORY_LANGUAGES', 'languages');
define('BX_DOL_STUDIO_STG_CATEGORY_TEMPLATES', 'templates');

define('BX_DOL_STUDIO_STG_MIX_SYSTEM', 'system');
define('BX_DOL_STUDIO_STG_MIX_DEFAULT', BX_DOL_STUDIO_STG_MIX_SYSTEM);

class BxDolStudioSettings extends BxTemplStudioPage
{
    protected $sType;
    protected $sCategory;
    protected $aCategories;
    protected $aCustomCategories;

    protected $bReadOnly;	//--- Enable/Disable ReadOnly mode for system settings. Note. It doesn't affect Editable mixes.

    protected $bMixes;	//--- Enable/Disable mixes feature.
    protected $sMix;
    protected $aMix;

    protected $sStorage;
    protected $sTranscoder;

    protected $sErrorMessage;

    function __construct($sType = '', $mixedCategory = '')
    {
        parent::__construct('settings');

        $this->oDb = new BxDolStudioSettingsQuery();

        $this->sType = BX_DOL_STUDIO_STG_TYPE_DEFAULT;
        if(!empty($sType) && is_string($sType))
            $this->sType = $sType;

        $this->sCategory = '';
        if(!empty($mixedCategory)) {
        	if(is_array($mixedCategory))
        		$this->sCategory = $mixedCategory;
        	else if(is_string($mixedCategory)) {
        		$this->sCategory = json_decode($mixedCategory);
        		if(empty($this->sCategory))
        			$this->sCategory = $mixedCategory;
        	}
        }

        $this->bReadOnly = false;

		$this->bMixes = false;
		$this->sMix = '';
		$this->aMix = array();

		$this->sStorage = 'sys_images_custom';
		$this->sTranscoder = 'sys_custom_images';

		$this->sErrorMessage = '';

        //--- Check actions ---//
        if(($sAction = bx_get('stg_action')) !== false) {
            $sAction = bx_process_input($sAction);

            $sValue = '';
            if(bx_get('stg_value') !== false)
            	$sValue = bx_process_input(bx_get('stg_value'));

            $aResult = array('code' => 0, 'message' => '');
            if(!empty($sAction)) {
                switch($sAction) {
                	case 'select-mix':
                		$aResult = array_merge($aResult, $this->selectMix($sValue));
                		break;

                	case 'create-mix':
                		$aResult = array_merge($aResult, $this->getPopupCodeCreateMix());
						break;

					case 'import-mix':
                		$aResult = array_merge($aResult, $this->getPopupCodeImportMix());
						break;

					case 'export-mix':
                		$aResult = array_merge($aResult, $this->exportMix((int)$sValue));
						break;

					case 'download-mix':
                		$aResult = array_merge($aResult, $this->downloadMix((int)$sValue));
						break;

                	case 'delete-mix':
                		$aResult = array_merge($aResult, $this->deleteMix((int)$sValue));
						break;

                    case 'get-page-by-type':
                        $this->sType = $sValue;
                        $aResult['content'] = $this->getPageCode();
                        break;
                }

                echo json_encode($aResult);
            }
            exit;
        }
    }

	public function enableReadOnly($bReadOnly = true)
    {
    	$this->bReadOnly = $bReadOnly;
    }

    public function enableMixes($bMixes = true)
    {
    	$this->bMixes = $bMixes;
    }

    public function selectMix($sName)
    {
		$this->oDb->updateMixes(array('active' => 0), array(
			'type' => $this->sType,
			'category' => is_string($this->sCategory) ? $this->sCategory : '',
			'active' => 1
		));

		$aResult = array();
		if($sName == BX_DOL_STUDIO_STG_MIX_SYSTEM || $this->oDb->updateMixes(array('active' => 1), array('name' => $sName))) {
		    BxDolCacheUtilities::getInstance()->clear('css');

			$aResult = array('eval' => $this->getPageJsObject() . '.onMixSelect(oData);');
		}
		else 
    		$aResult = array('message' => _t('_adm_stg_err_cannot_perform')); 

    	return $aResult;
    }

    public function exportMix($iId)
    {
    	$aMix = array();
    	$this->oDb->getMixes(array('type' => 'by_id', 'value' => $iId), $aMix, false);
    	if(empty($aMix) || !is_array($aMix))
    		return array('message' => _t('_adm_stg_err_cannot_perform'));

		return array(
			'url' => bx_append_url_params(BX_DOL_URL_STUDIO . 'settings.php', array(
				'stg_action' => 'download-mix',
    			'stg_value' => $iId,
			)),
			'eval' => $this->getPageJsObject() . '.onMixExport(oData);'
		);
    }

    public function downloadMix($iId)
    {
    	$aMix = array();
    	$this->oDb->getMixes(array('type' => 'by_id', 'value' => $iId), $aMix, false);
    	if(empty($aMix) || !is_array($aMix))
    		BxDolStudioTemplate::getInstance()->displayPageNotFound();

		$aOptions = array();
		$this->oDb->getMixesOptions(array('type' => 'by_mix_id_pair_option_value', 'value' => $iId, 'for_export' => 1), $aOptions, false);

    	$aContent = array(
    		'mix' => array(
    			'type' => $aMix['type'],
    			'category' => $aMix['category'],
    			'name' => $aMix['name'],
    			'title' => $aMix['title']
    		),
    		'options' => $aOptions
    	);
    	$sContent = json_encode($aContent);

    	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-type: application/json");
        header("Content-Length: " . strlen($sContent));
        header("Content-Disposition: attachment; filename=\"". strtolower($aMix['name']) . ".json\"");

        echo $sContent;
        exit;
    }
    
    public function deleteMix($iId)
    {
    	$aResult = array();
    	if($this->oDb->deleteMixesOptions(array('mix_id' => $iId)) && $this->oDb->deleteMixes(array('id' => $iId)))
    		$aResult = array('eval' => $this->getPageJsObject() . '.onMixDelete(oData);');
    	else 
    		$aResult = array('message' => _t('_adm_stg_err_cannot_perform')); 

    	return $aResult;
    }

    function saveChanges(&$oForm)
    {
    	$iMixId = $oForm->getCleanValue('mix_id');
        $aCategories = explode(',', $oForm->getCleanValue('categories'));

        foreach ($aCategories as $sCategory) {
            $aOptions = array();
            $iOptions = $this->oDb->getOptions(array('type' => 'by_category_name_full', 'value' => $sCategory), $aOptions);

            $aData = array();
            foreach($aOptions as $aOption) {
                $aData[$aOption['name']] = $this->getSubmittedValue($aOption, $oForm);
                if($aData[$aOption['name']] === false && !empty($this->sErrorMessage)) {
                	$this->sCategory = $sCategory;
					return $this->getJsResult(_t('_adm_stg_err_save_error_message', _t($aOption['caption']), _t($this->sErrorMessage)), false);
                }

                if(!empty($aOption['check'])) {
                    $sCheckerHelper = '';
                    if(!empty($aOption['type_name']) && BxDolRequest::serviceExists($aOption['type_name'], 'get_settings_checker_helper'))
                        $sCheckerHelper = BxDolService::call($aOption['type_name'], 'get_settings_checker_helper');

                    if($sCheckerHelper == '') {
                        bx_import('BxDolStudioForm');
                        $sCheckerHelper = 'BxDolStudioFormCheckerHelper';
                    }

                    $oChecker = new $sCheckerHelper();
                    $aCheckFunction = array($oChecker, 'check' . bx_gen_method_name($aOption['check']));
                    $aCheckFunctionParams = array($aData[$aOption['name']]);
                    if(!empty($aOption['check_params']))
                        $aCheckFunctionParams = array_merge($aCheckFunctionParams, unserialize($aOption['check_params']));

                    if(is_callable($aCheckFunction) && !call_user_func_array($aCheckFunction, $aCheckFunctionParams)) {
                        $this->sCategory = $sCategory;
                        return $this->getJsResult(_t('_adm_stg_err_save_error_message', _t($aOption['caption']), _t($aOption['check_error'])), false);
                    }
                }

                if(isset($aData[$aOption['name']]))
                    $aData[$aOption['name']] = $this->getProcessedValue($aOption, $aData[$aOption['name']]);
                else
                    $aData[$aOption['name']] = $this->getEmptyValue($aOption);

                if($this->oDb->setParam($aOption['name'], $aData[$aOption['name']], $iMixId)) {
                	$aCategoryInfo = array();
		            $this->oDb->getCategories(array('type' => 'by_name', 'value' => $sCategory), $aCategoryInfo, false);

	        		bx_alert('system', 'save_setting', 0, 0, array('category' => $aCategoryInfo, 'option' => $aOption['name'], 'value' => $aData[$aOption['name']]));
                }
            }
        }

        return $this->getJsResult('_adm_stg_scs_save');
    }

    protected function isReadOnly()
    {
    	$bMix = !empty($this->aMix);
    	return (!$bMix && $this->bReadOnly) || ($bMix && (int)$this->aMix['editable'] == 0);
    }

    protected function getSubmittedValue($aOption, &$oForm)
    {
    	$mixedValue = '';

    	switch($aOption['type']) {
    		case 'image':
    			$mixedValue = (int)getParam($aOption['name']);

    			$aIds = $oForm->getCleanValue($aOption['name']);
    			if(empty($aIds))
    				break;

    			$oStorage = BxDolStorage::getObjectInstance($this->sStorage);
		        if(!$oStorage)
					break;

				//--- Concatenation integer values as strings is required to get unique content id
				$iContentId = (int)($aOption['id'] . (int)$oForm->getCleanValue('mix_id'));
    			foreach($aIds as $iId) {
    				$oStorage->updateGhostsContentId($iId, false, $iContentId);
    				$mixedValue = $iId;
    			}
    			break;

    		default: 
    			$mixedValue = $oForm->getCleanValue($aOption['name']);
    	}

    	return $mixedValue;
    }

    protected function getProcessedValue($aOption, $mixedValue)
    {
        if(is_array($mixedValue))
            $mixedValue = implode(',', $mixedValue);

        return $mixedValue;
    }

    protected function getEmptyValue($aOption)
    {
        $mixedValue = '';
        switch($aOption['type']) {
            case 'digit':
                $mixedValue = 0;
                break;
            case 'select':
                if (BxDolService::isSerializedService($aOption['extra']))
                    $aValues = BxDolService::callSerialized($aOption['extra']);
                else
                    $aValues = explode(',', $aOption['extra']);
                $mixedValue = $aValues[0];
                break;
            case 'text':
            case 'checkbox':
            case 'file':
                $mixedValue = "";
                break;
        }
        return $mixedValue;
    }
}

/** @} */
