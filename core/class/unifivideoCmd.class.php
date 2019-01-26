<?php
/**
 * Created by PhpStorm.
 * User: DZ5747
 * Date: 22/01/2019
 * Time: 19:25
 */

/**
 * Class unifivideoCmd
 */
class unifivideoCmd extends cmd {
    /**
     * @param array $_options
     * @return bool
     */
    public function execute($_options = array()) {
        $eqLogic = $this->getEqLogic();
        switch ($this->getLogicalId()) {
            case 'takeScreenshot':
                return $eqLogic->takeScreenshotCmd();
            case 'volumeSet':
                return $eqLogic->volumeSetCmd($_options[ 'slider' ]);
            case 'disableRecordCmd':
                return $eqLogic->disableRecordCmd($_options);
            case 'enableRecordCmd':
                return $eqLogic->enableRecordCmd();
            case 'disablePrivacyFilterCmd':
                return $eqLogic->disablePrivacyFilterCmd();
            case 'enablePrivacyFilterCmd':
                return $eqLogic->enablePrivacyFilterCmd($_options);
        }
    }
}