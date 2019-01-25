<?php
/**
 * Created by PhpStorm.
 * User: DZ5747
 * Date: 22/01/2019
 * Time: 20:34
 */

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../../vendor/autoload.php';

class unifivideoServices {

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
    public function writeTofile($file, $content)
    {
        $fp = fopen($file, 'w+');
        fwrite($fp, $content);
        fclose($fp);
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
     * @param $uri
     * @param string $additionalParam
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getInfosWithCurl($uri, $additionalParam = 'decoded')
    {
        $client = new \GuzzleHttp\Client(array('curl' => array( CURLOPT_HEADER => false, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_AUTOREFERER => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false ),'verify' => false));
        $request = $client->request('GET', $uri, [])->getBody();
        if ($additionalParam == 'undecoded') {
            return $request;
        }
        if ($additionalParam == 'decoded') {
            return \GuzzleHttp\json_decode($request);
        }
    }

    /**
     * @param $isSsl
     * @param $unifiServer
     * @param $srvPort
     * @param $camKey
     * @param $apiKey
     * @param $camName
     * @param string $action
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
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
}