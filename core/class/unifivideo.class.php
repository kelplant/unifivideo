<?php
/**
 * Created by PhpStorm.
 * User: kelplant
 * Date: 20/01/2019
 * Time: 12:21
 */

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../../../../vendor/autoload.php';

/**
 * Class unifivideo
 */
class unifivideo extends eqLogic {

    /**
     * @param $repertoire
     */
    private function testFolderCreateIfNotExist($repertoire)
    {
        if (is_dir($repertoire) === false) {
            mkdir($repertoire, 0755);
        };
    }

    /**
     * @param $file
     * @param $content
     */
    private function writeTofile($file, $content)
    {
        $fp = fopen($file, 'w+');
        fwrite($fp, $content);
        fclose($fp);
    }

    /**
     * @param $uri
     * @param string $additionalParam
     * @return mixed
     */
    private function getInfosWithCurl($uri, $additionalParam = 'decoded')
    {
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $uri,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_AUTOREFERER    => true, // set referrer on redirect
            CURLOPT_CONNECTTIMEOUT => 120, // time-out on connect
            CURLOPT_TIMEOUT        => 120,
        );
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        if ($additionalParam == 'undecoded') {
            $e = $response;

        } else {
            $e = json_decode($response);
        }
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
        $client = new \GuzzleHttp\Client(array('curl' => array(CURLOPT_SSL_VERIFYPEER => false), 'verify' => false));
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
        if ($isSsl == 1) {
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
        $this->getSnapshotFromServer(config::byKey('isSsl', 'unifivideo', '', true), config::byKey('srvIpAddress', 'unifivideo', '', true), config::byKey('srvPort', 'unifivideo', '', true), $this->getConfiguration('camKey'), config::byKey('apiKey'), $this->getConfiguration('camName'), 'current');
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
     * @param $isSsl
     * @param $unifiServer
     * @param $srvPort
     * @param $camKey
     * @param $apiKey
     * @param $camName
     * @return mixed
     */
    public function getSnapshotFromServer($isSsl, $unifiServer, $srvPort, $camKey, $apiKey, $camName, $action = 'current')
    {
        $uri = $this->returnHead($isSsl) . '://' . $unifiServer . ':' . $srvPort . '/api/2.0/snapshot/camera/' . $camKey . '?force=true&apiKey=' . $apiKey;
        $response = $this->getInfosWithCurl($uri, 'undecoded');
        if ($_SERVER[ 'PHP_SELF' ] == '/plugins/unifivideo/core/ajax/unifivideo.ajax.php') {
            $repertoire = "../../captures/";
        } else {
            $repertoire = "../../plugins/unifivideo/captures/";
        }
        $this->testFolderCreateIfNotExist($repertoire);
        $this->writeTofile($repertoire . $camName . '_current.jpg', $response);
        if ($action == 'full') {
            $this->testFolderCreateIfNotExist($repertoire . $camName);
            $repertoireToFetch = opendir($repertoire . $camName);
            $file_list = array();
            $dont_show = array("", "php", ".", "..");
            while ($file = readdir($repertoireToFetch)) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if (!in_array($ext, $dont_show)) array_push($file_list, substr($file, strlen($camName) + 1, 4));
            }
            $this->writeTofile($repertoire . $camName . '/' . $camName . '_' . str_pad(max($file_list) + 1, 4, '0', STR_PAD_LEFT) . '.jpg', $response);
        }
        return $response;
    }

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
        foreach ($this->getInfosWithCurl($uri)->data[ 0 ]->cameras as &$value) {
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
            $this->getSnapshotFromServer($isSsl, $unifiServer, $srvPort, $value->_id, $apiKey, $value->name, 'full');
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
            $this->returnHead($isSsl) . '://' . $unifiServer . ':' . $srvPort . '/api/2.0/camera/' . $camKey . '?apiKey=' . $apikey,
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
            $this->returnHead($isSsl) . '://' . $unifiServer . ':' . $srvPort . '/api/2.0/camera/' . $camKey . '?apiKey=' . $apikey,
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
        $this->updateCmdInfos('disableRecordCmd', 'recordState', 0);
        return $this->recordAdmin(urlencode(config::byKey('isSsl', 'unifivideo', '', true)), urlencode(config::byKey('srvIpAddress', 'unifivideo', '', true)), urlencode(config::byKey('srvPort', 'unifivideo', '', true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey', 'unifivideo', '', true)), urlencode($this->getConfiguration('camName')), urlencode('false'));
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function enableRecordCmd() {
        $this->updateCmdInfos('enableRecordCmd', 'recordState', 1);
        return $this->recordAdmin(urlencode(config::byKey('isSsl', 'unifivideo', '', true)), urlencode(config::byKey('srvIpAddress', 'unifivideo', '', true)), urlencode(config::byKey('srvPort', 'unifivideo', '', true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey', 'unifivideo', '', true)), urlencode($this->getConfiguration('camName')), urlencode('true'));
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function disablePrivacyFilterCmd() {
        $this->updateCmdInfos('disablePrivacyFilterCmd', 'privacyState', 0);
        return $this->privacyAdmin(urlencode(config::byKey('isSsl', 'unifivideo', '', true)), urlencode(config::byKey('srvIpAddress', 'unifivideo', '', true)), urlencode(config::byKey('srvPort', 'unifivideo', '', true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey', 'unifivideo', '', true)), urlencode($this->getConfiguration('camName')), urlencode('false'));
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function enablePrivacyFilterCmd() {
        $this->updateCmdInfos('enablePrivacyFilterCmd', 'privacyState', 1);
        return $this->privacyAdmin(urlencode(config::byKey('isSsl', 'unifivideo', '', true)), urlencode(config::byKey('srvIpAddress', 'unifivideo', '', true)), urlencode(config::byKey('srvPort', 'unifivideo', '', true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey', 'unifivideo', '', true)), urlencode($this->getConfiguration('camName')), urlencode('true'));
    }

    /**
     * @param $micVolume
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function volumeSetCmd($micVolume) {
        $this->updateCmdInfos('volumeSet', 'volumeLevel', $micVolume);
        return $this->micAdmin(urlencode(config::byKey('isSsl', 'unifivideo', '', true)), urlencode(config::byKey('srvIpAddress', 'unifivideo', '', true)), urlencode(config::byKey('srvPort', 'unifivideo', '', true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey', 'unifivideo', '', true)), urlencode($this->getConfiguration('camName')), $micVolume);
    }

    /**
     * @return bool
     */
    public function takeScreenshotCmd()
    {
        $this->getSnapshotFromServer(urlencode(config::byKey('isSsl', 'unifivideo', '', true)), urlencode(config::byKey('srvIpAddress', 'unifivideo', '', true)), urlencode(config::byKey('srvPort', 'unifivideo', '', true)), urlencode($this->getConfiguration('camKey')), urlencode(config::byKey('apiKey', 'unifivideo', '', true)), urlencode($this->getConfiguration('camName')),'full');
        return true;
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
        if ($this->getLogicalId() == 'takeScreenshot') {
            return $eqLogic->takeScreenshotCmd();
        }
        if ($this->getLogicalId() == 'volumeSet') {
            return $eqLogic->volumeSetCmd($_options[ 'slider' ]);
        }
        if ($this->getLogicalId() == 'disableRecordCmd') {
            return $eqLogic->disableRecordCmd($_options);
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