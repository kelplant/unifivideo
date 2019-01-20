<?php
/**
 * Created by PhpStorm.
 * User: DZ5747
 * Date: 20/01/2019
 * Time: 22:52
 */

try {
    require_once dirname(__FILE__) . '/../../../../plugins/unifivideo/core/class/unifivideo.class.php';
    //include_file('plugins.unifivideo.core.class', 'unifivideo.class', 'php');
    include_file('core', 'authentification', 'php');

    ajax::init();

    if (init('action') == 'changeIncludeState') {
        $test = new unifivideo();
        $test->getInfosFromServer(urlencode(config::byKey('srvIpAddress','unifivideo','',true)), urlencode(config::byKey('srvPort','unifivideo','',true)), urlencode(config::byKey('apiKey','unifivideo','',true)), 'false');
        ajax::success();
    }

    if (init('action') == 'cleanTTScache') {
        ajax::success();
    }

    if (init('action') == 'nowplaying') {
        ajax::success();
    }

    if (init('action') == 'refreshall') {
        ajax::success();
    }


    if (init('action') == 'sendcmd') {
        $ret = googlecast::sendDisplayAction(init('uuid'),init('cmd'), init('options'));
        if ($ret) {
            ajax::success();
        }
        else {
            ajax::error();
        }
    }

    throw new Exception(__('Aucune methode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
