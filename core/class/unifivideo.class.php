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
     * @throws Exception
     */
    public function postInsert() {

        $disableRecordCmd = $this->getCmd(null, 'disableRecordCmd');
        if (!is_object($disableRecordCmd)) {
            $disableRecordCmd = new unifivideoCmd();
            $disableRecordCmd->setName(__('Arrêter Enregistrement', __FILE__));
        }
        $disableRecordCmd->setConfiguration('request', '-');
        $disableRecordCmd->setType('action');
        $disableRecordCmd->setLogicalId('disableRecordCmd');
        $disableRecordCmd->setEqLogic_id($this->getId());
        $disableRecordCmd->setSubType('other');
        $disableRecordCmd->setOrder(999);
        $disableRecordCmd->setDisplay('icon', '<i class="fa fa-stop"></i>');
        $disableRecordCmd->setDisplay('generic_type', 'CAMERA_STOP');
        $disableRecordCmd->save();


        $enableRecordCmd = $this->getCmd(null, 'enableRecordCmd');
        if (!is_object($enableRecordCmd)) {
            $enableRecordCmd = new unifivideoCmd();
            $enableRecordCmd->setName(__('Démarrer Enregistrement', __FILE__));
        }

        $enableRecordCmd->setConfiguration('request', '-');
        $enableRecordCmd->setType('action');
        $enableRecordCmd->setLogicalId('enableRecordCmd');
        $enableRecordCmd->setEqLogic_id($this->getId());
        $enableRecordCmd->setSubType('other');
        $enableRecordCmd->setOrder(999);
        $enableRecordCmd->setDisplay('icon', '<i class="fa fa-play"></i>');
        $enableRecordCmd->setDisplay('generic_type', 'CAMERA_RECORD');
        $enableRecordCmd->save();


        $disablePrivacyFilterCmd = $this->getCmd(null, 'disablePrivacyFilterCmd');
        if (!is_object($disablePrivacyFilterCmd)) {
            $disablePrivacyFilterCmd = new unifivideoCmd();
            $disablePrivacyFilterCmd->setName(__('Arrêter Privacy Filter', __FILE__));
        }
        $disablePrivacyFilterCmd->setConfiguration('request', '-');
        $disablePrivacyFilterCmd->setType('action');
        $disablePrivacyFilterCmd->setLogicalId('disablePrivacyFilterCmd');
        $disablePrivacyFilterCmd->setEqLogic_id($this->getId());
        $disablePrivacyFilterCmd->setSubType('other');
        $disablePrivacyFilterCmd->setOrder(999);
        $disablePrivacyFilterCmd->setDisplay('icon', '<i class="icon jeedom-volet-ferme"></i>');
        $disablePrivacyFilterCmd->setDisplay('generic_type', 'CAMERA_STOP');
        $disablePrivacyFilterCmd->save();


        $enablePrivacyFilterCmd = $this->getCmd(null, 'enablePrivacyFilterCmd');
        if (!is_object($enablePrivacyFilterCmd)) {
            $enablePrivacyFilterCmd = new unifivideoCmd();
            $enablePrivacyFilterCmd->setName(__('Démarrer Privacy Filter', __FILE__));
        }
        $enablePrivacyFilterCmd->setConfiguration('request', '-');
        $enablePrivacyFilterCmd->setType('action');
        $enablePrivacyFilterCmd->setLogicalId('enablePrivacyFilterCmd');
        $enablePrivacyFilterCmd->setEqLogic_id($this->getId());
        $enablePrivacyFilterCmd->setSubType('other');
        $enablePrivacyFilterCmd->setOrder(999);
        $enablePrivacyFilterCmd->setDisplay('icon', '<i class="icon jeedom-volet-ouvert"></i>');
        $enablePrivacyFilterCmd->setDisplay('generic_type', 'CAMERA_RECORD');
        $enablePrivacyFilterCmd->save();
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
    private function sendToServer($uri, $payload, $headers)
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

        return $this->sendToServer($uri, $payload, $headers);
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