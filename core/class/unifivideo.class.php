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
     *
     */
    public function preInsert() {

    }

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


    private function addNewCommand($logicalId, $commandName, $eqLogicId, $icon, $genericType)
    {
        $newCommand = $this->getCmd(null, $logicalId);
        if (!is_object($newCommand)) {
            $newCommand = new unifivideoCmd();
            $newCommand->setName(__($commandName, __FILE__));
        }
        $newCommand->setConfiguration('request', '-');
        $newCommand->setType('action');
        $newCommand->setLogicalId($logicalId);
        $newCommand->setEqLogic_id($eqLogicId);
        $newCommand->setSubType('other');
        $newCommand->setOrder(999);
        $newCommand->setDisplay('icon', $icon);
        $newCommand->setDisplay('generic_type', $genericType);
        $newCommand->save();
    }

    /**
     * @throws Exception
     */
    public function postInsert() {
        $this->addNewCommand('disableRecordCmd', 'Arrêter Enregistrement', $this->getId(), '<i class="fa fa-stop"></i>', 'CAMERA_STOP');
        $this->addNewCommand('enableRecordCmd', 'Démarrer Enregistrement', $this->getId(), '<i class="fa fa-play"></i>', 'CAMERA_RECORD');
        $this->addNewCommand('disablePrivacyFilterCmd', 'Arrêter Privacy Filter', $this->getId(), '<i class="fa jeedom-volet-ferme"></i>', 'CAMERA_STOP');
        $this->addNewCommand('enablePrivacyFilterCmd', 'Démarrer Privacy Filter', $this->getId(), '<i class="fa jeedom-volet-ouvert"></i>', 'CAMERA_RECORD');
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
     * @param $uri
     * @param $payload
     * @param $headers
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function sendPutToServer($uri, $payload, $headers)
    {
        $client = new \GuzzleHttp\Client();
        $request = $client->request('PUT', $uri, [
            'json' => $payload,
            'headers' => $headers,
        ]);
        return (string) $request->getStatusCode();
    }

    /**
     * @param $unifiServer
     * @param $srvPort
     * @param $camKey
     * @param $apikey
     * @param $cameraName
     * @param $actionResult
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function privacyAdmin($unifiServer, $srvPort, $camKey, $apikey, $cameraName, $actionResult)
    {
        $uri = 'http://' . $unifiServer . ':'. $srvPort .'/api/2.0/camera/' . $camKey . '?apiKey=' . $apikey;
        $headers = [
            'Accept' => 'application/json',
            'Referer' => '(intentionally removed)',
        ];

        $payload = array(
            'name' => $cameraName,
            'enablePrivacyMasks' => $actionResult,
        );

        return $this->sendPutToServer($uri, $payload, $headers);
    }

    /**
     * @param $unifiServer
     * @param $srvPort
     * @param $camKey
     * @param $apikey
     * @param $cameraName
     * @param $actionResult
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function recordAdmin($unifiServer, $srvPort, $camKey, $apikey, $cameraName, $actionResult)
    {
        $uri = 'http://' . $unifiServer . ':' . $srvPort . '/api/2.0/camera/' . $camKey . '?apiKey=' . $apikey;
        $headers = [
            'Accept' => 'application/json',
            'Referer' => '(intentionally removed)',
        ];

        $payload = array(
            'name' => $cameraName,
            'recordingSettings' => array(
                'motionRecordEnabled' => $actionResult,
                'fullTimeRecordEnabled' => 'false',
                'channel' => '0',
            )
        );

        return $this->sendToServer($uri, $payload, $headers);;
    }

    /**
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function disableRecordCmd() {
        return $this->recordAdmin(urlencode(config::byKey('srvIpAddress','unifivideo','',true)),urlencode(config::byKey('srvPort','unifivideo','',true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey','unifivideo','',true)), urlencode($this->getConfiguration('camName')),'false');
    }

    /**
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function enableRecordCmd() {
        return $this->recordAdmin(urlencode(config::byKey('srvIpAddress','unifivideo','',true)),urlencode(config::byKey('srvPort','unifivideo','',true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey','unifivideo','',true)), urlencode($this->getConfiguration('camName')), 'true');
    }

    /**
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function disablePrivacyFilterCmd() {
        return $this->privacyAdmin(urlencode(config::byKey('srvIpAddress','unifivideo','',true)),urlencode(config::byKey('srvPort','unifivideo','',true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey','unifivideo','',true)), urlencode($this->getConfiguration('camName')), 'false');
    }

    /**
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function enablePrivacyFilterCmd() {
        return $this->privacyAdmin(urlencode(config::byKey('srvIpAddress','unifivideo','',true)),urlencode(config::byKey('srvPort','unifivideo','',true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey','unifivideo','',true)), urlencode($this->getConfiguration('camName')), 'true');
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