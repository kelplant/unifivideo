<?php
/**
 * Created by PhpStorm.
 * User: kelplant
 * Date: 20/01/2019
 * Time: 12:21
 */

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../../vendor/autoload.php';
require_once dirname(__FILE__) . '/../../core/class/unifivideoCmd.class.php';
require_once dirname(__FILE__) . '/../../core/service/unifivideo.service.php';
/**
 * Class unifivideo
 */
class unifivideo extends eqLogic {

    /**
     * @param $unifiServer
     * @param $srvPort
     * @param $apiKey
     * @param $secureSSL
     * @return mixed
     * @throws Exception
     */
    public function getInfosFromServer($isSsl, $unifiServer, $srvPort, $apiKey)
    {
        $uri = 'https://' . $unifiServer . ':' . $srvPort . '/api/2.0/bootstrap?apiKey=' . $apiKey;
        $unifivideoServices = new unifivideoServices();
        foreach ($unifivideoServices->getInfosWithCurl($uri)->data[ 0 ]->cameras as &$value) {
            $unifivideo = new eqLogic();
            $eqLogic = $unifivideo->byLogicalId($value->_id, 'unifivideo');
            if (!is_object($eqLogic)) {
                $eqLogic = new self();
                $eqLogic->setLogicalId($value->_id);
                $eqLogic->setCategory('security', 1);
                $eqLogic->setName($value->name);
                $eqLogic->setConfiguration('camName', $value->name);
                $eqLogic->setConfiguration('camKey', $value->_id);
                $eqLogic->setEqType_name('unifivideo');
                $eqLogic->setIsVisible(1);
                $eqLogic->setIsEnable(1);
                $eqLogic->save();
            }
            $unifivideoServices->getSnapshotFromServer($isSsl, $unifiServer, $srvPort, $value->_id, $apiKey, $value->name, 'full');
        }
        return true;
    }

    /**
     * @param $logicalId1
     * @param $logicalId2
     * @param $configurationValue
     */
    private function updateCmdInfos($logicalId1, $logicalId2, $configurationValue)
    {
        $this->getCmd(null, $logicalId1)->setConfiguration('lastCmdValue', $configurationValue)->save();
        $this->getCmd(null, $logicalId2)->setValue($configurationValue)->save();
    }
    /**
     * @param $logicalId
     * @param $commandName
     * @param $type
     * @param $subType
     * @param $eqLogicId
     * @param $icon
     * @param $genericType
     * @param $order
     * @param $visible
     * @param int $optionalParam
     * @return mixed
     * @throws Exception
     */
    private function addActionOtherCommand($logicalId, $commandName, $type, $subType, $eqLogicId, $icon, $genericType, $order, $visible, $optionalParam = 1)
    {
        $newCommand = $this->getCmd(null, $logicalId);
        if (!is_object($newCommand)) {
            $newCommand = new unifivideoCmd();
            $newCommand->setName(__($commandName, __FILE__));
        }
        if ($type == 'action' && $subType == 'other') {
            $newCommand->setConfiguration('request', '-');
        }
        if ($type == 'action' && $subType == 'slider') {
            $newCommand->setConfiguration('minValue', 0);
            $newCommand->setConfiguration('maxValue', 100);
        }
        if ($type == 'action') {
            $newCommand->setConfiguration('lastCmdValue', null);
            $newCommand->setValue($optionalParam);
        }
        if ($type == 'info' && $subType == 'numeric') {
            $newCommand->setUnite($optionalParam);
        }
        $newCommand->setIsVisible($visible);
        $newCommand->setType($type);
        $newCommand->setLogicalId($logicalId);
        $newCommand->setEqLogic_id($eqLogicId);
        $newCommand->setSubType($subType);
        $newCommand->setOrder($order);
        $newCommand->setDisplay('icon', $icon);
        $newCommand->setDisplay('generic_type', $genericType);
        $newCommand->save();

        return $newCommand->getId();
    }

    /**
     *
     */
    public function preInsert() {

    }

    /**
     * @throws Exception
     */
    public function postInsert()
    {
        $recordingStateInfoCmdId = $this->addActionOtherCommand('recordState', 'Etat Enregistrement', 'info', 'binary', $this->getId(), '<i class="fa jeedom-volet-ouvert"></i>', 'CAMERA_RECORD_STATE', 10, 0);
        $this->addActionOtherCommand('disableRecordCmd', 'Arrêter Enregistrement', 'action', 'other', $this->getId(), '<i class="fa fa-stop"></i>', 'CAMERA_STOP', 20, 1, $recordingStateInfoCmdId);
        $this->addActionOtherCommand('enableRecordCmd', 'Démarrer Enregistrement', 'action', 'other', $this->getId(), '<i class="fa fa-play"></i>', 'CAMERA_RECORD', 30, 1, $recordingStateInfoCmdId);
        $privacyStateInfoCmdId = $this->addActionOtherCommand('privacyState', 'Etat Filtre Confidentialité', 'info', 'binary', $this->getId(), '<i class="fa jeedom-volet-ouvert"></i>', 'CAMERA_RECORD_STATE', 10, 0);
        $this->addActionOtherCommand('disablePrivacyFilterCmd', 'Arrêter Filtre Confidentialité', 'action', 'other', $this->getId(), '<i class="fa jeedom-volet-ferme"></i>', 'CAMERA_STOP', 50, 1, $privacyStateInfoCmdId);
        $this->addActionOtherCommand('enablePrivacyFilterCmd', 'Démarrer Filtre Confidentialité', 'action', 'other', $this->getId(), '<i class="fa jeedom-volet-ouvert"></i>', 'CAMERA_RECORD', 60, 1, $privacyStateInfoCmdId);
        $volumeStateInfoCmdId = $this->addActionOtherCommand('volumeLevel', 'Volume', 'info', 'numeric', $this->getId(), '<i class="fa jeedom-volet-ouvert"></i>', 'LIGHT_STATE', 70, 0, '%');
        $this->addActionOtherCommand('volumeSet', 'Volume Niveau', 'action', 'slider', $this->getId(), '<i class="fa fa-volume-control-phone"></i>', '', 80, 1, $volumeStateInfoCmdId);
        $this->addActionOtherCommand('takeScreenshot', 'Prendre une Capture d\'Ecran', 'action', 'other', $this->getId(), '<i class="fa fa-closed-captioning"></i>', 'CAMERA_SCREENSHOT', 90, 1);
    }

    /**
     *
     */
    public function preSave() {

    }

    /**
     *
     */
    public function postSave() {
        $univideoServices = new unifivideoServices();
        $univideoServices->getSnapshotFromServer(config::byKey('isSsl', 'unifivideo', '', true), config::byKey('srvIpAddress', 'unifivideo', '', true), config::byKey('srvPort', 'unifivideo', '', true), $this->getConfiguration('camKey'), config::byKey('apiKey'), $this->getConfiguration('camName'), 'current');
    }

    /**
     *
     */
    public function preUpdate() {

    }

    /**
     *
     */
    public function postUpdate() {

    }

    /**
     *
     */
    public function preRemove() {

    }

    /**
     *
     */
    public function postRemove() {

    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function disableRecordCmd() {
        $univideoServices = new unifivideoServices();
        $this->updateCmdInfos('disableRecordCmd', 'recordState', 0);
        return $univideoServices->recordAdmin(urlencode(config::byKey('isSsl', 'unifivideo', '', true)), urlencode(config::byKey('srvIpAddress', 'unifivideo', '', true)), urlencode(config::byKey('srvPort', 'unifivideo', '', true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey', 'unifivideo', '', true)), urlencode($this->getConfiguration('camName')), urlencode('false'));
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function enableRecordCmd() {
        $univideoServices = new unifivideoServices();
        $this->updateCmdInfos('enableRecordCmd', 'recordState', 1);
        return $univideoServices->recordAdmin(urlencode(config::byKey('isSsl', 'unifivideo', '', true)), urlencode(config::byKey('srvIpAddress', 'unifivideo', '', true)), urlencode(config::byKey('srvPort', 'unifivideo', '', true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey', 'unifivideo', '', true)), urlencode($this->getConfiguration('camName')), urlencode('true'));
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function disablePrivacyFilterCmd() {
        $univideoServices = new unifivideoServices();
        $this->updateCmdInfos('disablePrivacyFilterCmd', 'privacyState', 0);
        return $univideoServices->privacyAdmin(urlencode(config::byKey('isSsl', 'unifivideo', '', true)), urlencode(config::byKey('srvIpAddress', 'unifivideo', '', true)), urlencode(config::byKey('srvPort', 'unifivideo', '', true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey', 'unifivideo', '', true)), urlencode($this->getConfiguration('camName')), urlencode('false'));
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function enablePrivacyFilterCmd() {
        $univideoServices = new unifivideoServices();
        $this->updateCmdInfos('enablePrivacyFilterCmd', 'privacyState', 1);
        return $univideoServices->privacyAdmin(urlencode(config::byKey('isSsl', 'unifivideo', '', true)), urlencode(config::byKey('srvIpAddress', 'unifivideo', '', true)), urlencode(config::byKey('srvPort', 'unifivideo', '', true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey', 'unifivideo', '', true)), urlencode($this->getConfiguration('camName')), urlencode('true'));
    }

    /**
     * @param $micVolume
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function volumeSetCmd($micVolume) {
        $univideoServices = new unifivideoServices();
        $this->updateCmdInfos('volumeSet', 'volumeLevel', $micVolume);
        return $univideoServices->micAdmin(urlencode(config::byKey('isSsl', 'unifivideo', '', true)), urlencode(config::byKey('srvIpAddress', 'unifivideo', '', true)), urlencode(config::byKey('srvPort', 'unifivideo', '', true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey', 'unifivideo', '', true)), urlencode($this->getConfiguration('camName')), $micVolume);
    }

    /**
     * @return bool
     */
    public function takeScreenshotCmd()
    {
        $univideoServices = new unifivideoServices();
        $univideoServices->getSnapshotFromServer(urlencode(config::byKey('isSsl', 'unifivideo', '', true)), urlencode(config::byKey('srvIpAddress', 'unifivideo', '', true)), urlencode(config::byKey('srvPort', 'unifivideo', '', true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey', 'unifivideo', '', true)), urlencode($this->getConfiguration('camName')),'full');
        return true;
    }
}


