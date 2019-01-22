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
                break;
            case 'volumeSet':
                return $eqLogic->volumeSetCmd($_options[ 'slider' ]);
                break;
            case 'disableRecordCmd':
                return $eqLogic->disableRecordCmd($_options);
                break;
            case 'enableRecordCmd':
                return $eqLogic->enableRecordCmd();
                break;
            case 'disablePrivacyFilterCmd':
                return $eqLogic->disablePrivacyFilterCmd();
                break;
            case 'enablePrivacyFilterCmd':
                return $eqLogic->enablePrivacyFilterCmd($_options);
                break;
        }

//        if ($this->getLogicalId() == 'takeScreenshot') {
//            return $eqLogic->takeScreenshotCmd();
//        }
//        if ($this->getLogicalId() == 'volumeSet') {
//            return $eqLogic->volumeSetCmd($_options[ 'slider' ]);
//        }
//        if ($this->getLogicalId() == 'disableRecordCmd') {
//            return $eqLogic->disableRecordCmd($_options);
//        }
//        if ($this->getLogicalId() == 'enableRecordCmd') {
//            return $eqLogic->enableRecordCmd();
//        }
//        if ($this->getLogicalId() == 'disablePrivacyFilterCmd') {
//            return $eqLogic->disablePrivacyFilterCmd();
//        }
//        if ($this->getLogicalId() == 'enablePrivacyFilterCmd') {
//            return $eqLogic->enablePrivacyFilterCmd();
//        }
    }
}