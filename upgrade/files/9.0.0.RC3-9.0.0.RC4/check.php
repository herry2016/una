<?php

$mixCheckResult = 'Update can not be applied';

$sVer = $this->oDb->getOne("SELECT `version` FROM `sys_modules` WHERE `name` = 'system'");
if ('9.0.0.RC3' == $sVer || '9.0.0-RC3' == $sVer)
    $mixCheckResult = true;

return $mixCheckResult;
