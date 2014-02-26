<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) BoonEx Pty Limited - http://www.boonex.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    DolphinCore Dolphin Core
 * @{
 */

bx_import('BxTemplFormView');

/**
 * Login Form.
 */
class BxBaseFormLogin extends BxTemplFormView 
{
    protected $_iRole = BX_DOL_ROLE_MEMBER;

    public function __construct($aInfo, $oTemplate) 
    {
        parent::__construct($aInfo, $oTemplate);

        $sRelocate = bx_process_input(bx_get('relocate'));
        $this->aInputs['relocate']['value'] = $sRelocate && 0 == strncmp(BX_DOL_URL_ROOT, $sRelocate, strlen(BX_DOL_URL_ROOT)) ? $sRelocate : BX_DOL_URL_ROOT . 'member.php';
    }

    function isValid () 
    {
        if (!parent::isValid ())
            return false;

        $sErrorString = bx_check_password($this->getCleanValue('ID'), $this->getCleanValue('Password'), $this->getRole());
        $this->_setCustomError ($sErrorString);
        return $sErrorString ? false : true;
    }    

    protected function genCustomInputSubmitText ($aInput) 
    {
        bx_import('BxDolPermalinks');
        return '<div class="bx-form-right-line-aligned">
                    <a href="' . BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=forgot-password') . '">' . _t("_sys_txt_forgot_pasword") . '</a>
                </div>
                <div class="clear_both"></div>';
    }

    public function getRole() 
    {
        return $this->_iRole;
    }

    public function setRole ($iRole) 
    {
        $this->_iRole = $iRole == BX_DOL_ROLE_ADMIN ? BX_DOL_ROLE_ADMIN : BX_DOL_ROLE_MEMBER;
    }

    public function getLoginError () 
    {
        return isset($this->aInputs['ID']['error']) ? $this->aInputs['ID']['error'] : '';
    }

    protected function _setCustomError ($s) 
    {
        $this->aInputs['ID']['error'] = $s;
    }
}

/** @} */
