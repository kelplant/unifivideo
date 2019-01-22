<?php
/**
 * Created by PhpStorm.
 * User: kelplant
 * Date: 20/01/2019
 * Time: 12:21
 */

require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
require_once dirname(__FILE__).'/../../../../vendor/autoload.php';

/**
 * Class unifivideo
 */
class unifivideo extends eqLogic {
     /**
     * @param $uri
     * @return mixed
     */
    private function getInfosWithCurl($uri)
    {
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $uri,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
            CURLOPT_TIMEOUT        => 120,
        );
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $e = json_decode($response);
        curl_close($ch);
        return $e;
    }

    /**
     * @param $uri
     * @param $payload
     * @param $headers
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function sendPutToServer($uri, $payload, $headers)
    {
        $client = new \GuzzleHttp\Client(array('curl' => array( CURLOPT_SSL_VERIFYPEER => false ),'verify' => false));
        $request = $client->request('PUT', $uri, [
            'json' => $payload,
            'headers' => $headers,
        ]);
        return (string) $request->getStatusCode();
    }

    /**
     * @param $isSsl
     * @return string
     */
    private function returnHead($isSsl)
    {
        if($isSsl == 1) {
            $head = 'https';
        } else {
            $head = 'http';
        }
        return $head;
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
        if($type == 'action' && $subType == 'other'){
            $newCommand->setConfiguration('request', '-');
        }
        if($type == 'action' && $subType == 'slider') {
            $newCommand->setConfiguration('minValue', 0);
            $newCommand->setConfiguration('maxValue', 100);
            $newCommand->setConfiguration('lastCmdValue', 50);
            $newCommand->setValue($optionalParam);
        }
        if($type == 'info' && $subType == 'numeric') {
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
        if($type == 'info' && $subType == 'numeric') {
            return $newCommand->getId();
        }
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
        $this->addActionOtherCommand('disableRecordCmd', 'Arrêter Enregistrement', 'action', 'other', $this->getId(), '<i class="fa fa-stop"></i>', 'CAMERA_STOP', 1, 1);
        $this->addActionOtherCommand('enableRecordCmd', 'Démarrer Enregistrement', 'action', 'other', $this->getId(), '<i class="fa fa-play"></i>', 'CAMERA_RECORD', 2, 1);
        $this->addActionOtherCommand('disablePrivacyFilterCmd', 'Arrêter Privacy Filter', 'action', 'other', $this->getId(), '<i class="fa jeedom-volet-ferme"></i>', 'CAMERA_STOP', 3, 1);
        $this->addActionOtherCommand('enablePrivacyFilterCmd', 'Démarrer Privacy Filter', 'action', 'other', $this->getId(), '<i class="fa jeedom-volet-ouvert"></i>', 'CAMERA_RECORD', 4, 1);
        $volAsVolume = $this->addActionOtherCommand('volume_level', 'Volume', 'info', 'numeric', $this->getId(), '<i class="fa jeedom-volet-ouvert"></i>', 'LIGHT_STATE', 5, 0, '%');
        $this->addActionOtherCommand('volume_set', 'Volume Niveau', 'action', 'slider', $this->getId(), '<i class="fa fa-volume-control-phone"></i>', '', 6, 1, $volAsVolume);
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
     * @param $unifiServer
     * @param $srvPort
     * @param $apiKey
     * @param $secureSSL
     * @return mixed
     * @throws Exception
     */
    public function getInfosFromServer($unifiServer, $srvPort, $apiKey, $secureSSL)
    {
        $uri = 'https://' . $unifiServer . ':' . $srvPort . '/api/2.0/bootstrap?apiKey=' . $apiKey;
        foreach ($this->getInfosWithCurl($uri)->data[0]->cameras as &$value) {
            $eqLogic = unifivideo::byLogicalId($value->_id, 'unifivideo');
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
        }
        return true;
    }

    /**
     * @param $isSsl
     * @param $unifiServer
     * @param $srvPort
     * @param $camKey
     * @param $apikey
     * @param $cameraName
     * @param $actionResult
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function privacyAdmin($isSsl, $unifiServer, $srvPort, $camKey, $apikey, $cameraName, $actionResult)
    {
        return $this->sendPutToServer(
            $this->returnHead($isSsl) . '://' . $unifiServer . ':'. $srvPort .'/api/2.0/camera/' . $camKey . '?apiKey=' . $apikey,
            array(
                'name' => $cameraName,
                'enablePrivacyMasks' => $actionResult,
            ),
            array(
                'Accept' => 'application/json',
                'Referer' => '(intentionally removed)',
            )
        );
    }

    /**
     * @param $isSsl
     * @param $unifiServer
     * @param $srvPort
     * @param $camKey
     * @param $apikey
     * @param $cameraName
     * @param $actionResult
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function recordAdmin($isSsl, $unifiServer, $srvPort, $camKey, $apikey, $cameraName, $actionResult)
    {
        return $this->sendPutToServer(
            $this->returnHead($isSsl) . '://' . $unifiServer . ':'. $srvPort .'/api/2.0/camera/' . $camKey . '?apiKey=' . $apikey,
            array(
                'name' => $cameraName,
                'recordingSettings' => array(
                    'motionRecordEnabled' => $actionResult,
                    'fullTimeRecordEnabled' => 'false',
                    'channel' => '0',
                )
            ),
            array(
                'Accept' => 'application/json',
                'Referer' => '(intentionally removed)',
            )
        );
    }

    /**
     * @param $isSsl
     * @param $unifiServer
     * @param $srvPort
     * @param $camKey
     * @param $apikey
     * @param $cameraName
     * @param $micVolume
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function micAdmin($isSsl, $unifiServer, $srvPort, $camKey, $apikey, $cameraName, $micVolume)
    {
        return $this->sendPutToServer(
            $this->returnHead($isSsl) . '://' . $unifiServer . ':' . $srvPort . '/api/2.0/camera/' . $camKey . '?apiKey=' . $apikey,
            array(
                'name' => $cameraName,
                'micVolume' => $micVolume,
            ),
            array(
                'Accept' => 'application/json',
                'Referer' => '(intentionally removed)',
            )
        );
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function disableRecordCmd() {
        return $this->recordAdmin(urlencode(config::byKey('isSsl','unifivideo','',true)), urlencode(config::byKey('srvIpAddress','unifivideo','',true)), urlencode(config::byKey('srvPort','unifivideo','',true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey','unifivideo','',true)), urlencode($this->getConfiguration('camName')),urlencode('false'));
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function enableRecordCmd() {
        return $this->recordAdmin(urlencode(config::byKey('isSsl','unifivideo','',true)), urlencode(config::byKey('srvIpAddress','unifivideo','',true)), urlencode(config::byKey('srvPort','unifivideo','',true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey','unifivideo','',true)), urlencode($this->getConfiguration('camName')), urlencode('true'));
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function disablePrivacyFilterCmd() {
        return $this->privacyAdmin(urlencode(config::byKey('isSsl','unifivideo','',true)), urlencode(config::byKey('srvIpAddress','unifivideo','',true)), urlencode(config::byKey('srvPort','unifivideo','',true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey','unifivideo','',true)), urlencode($this->getConfiguration('camName')), urlencode('false'));
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function enablePrivacyFilterCmd() {
        return $this->privacyAdmin(urlencode(config::byKey('isSsl','unifivideo','',true)), urlencode(config::byKey('srvIpAddress','unifivideo','',true)), urlencode(config::byKey('srvPort','unifivideo','',true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey','unifivideo','',true)), urlencode($this->getConfiguration('camName')), urlencode('true'));
    }

    /**
     * @param $micVolume
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function volumeSetCmd($micVolume) {
        $this->getCmd(null, 'volume_set')->setConfiguration('lastCmdValue', $micVolume)->save();
        $this->getCmd(null, 'volume_level')->setValue($micVolume)->save();
        return $this->micAdmin(urlencode(config::byKey('isSsl','unifivideo','',true)), urlencode(config::byKey('srvIpAddress','unifivideo','',true)), urlencode(config::byKey('srvPort','unifivideo','',true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey','unifivideo','',true)), urlencode($this->getConfiguration('camName')), $micVolume);
    }
}


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
        if ($this->getLogicalId() == 'volume_set') {
            return $eqLogic->volumeSetCmd($_options['slider']);
        }
        if ($this->getLogicalId() == 'disableRecordCmd') {
            return $eqLogic->disableRecordCmd();
        }
        if ($this->getLogicalId() == 'enableRecordCmd') {
            return $eqLogic->enableRecordCmd();
        }
        if ($this->getLogicalId() == 'disablePrivacyFilterCmd') {
            return $eqLogic->disablePrivacyFilterCmd();
        }
        if ($this->getLogicalId() == 'enablePrivacyFilterCmd') {
            return $eqLogic->enablePrivacyFilterCmd();
        }
    }
}