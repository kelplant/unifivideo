<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes**********************************/
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
$libFile = dirname(__FILE__) . '/../../resources/UniFi-API-client/src/Client.php';
if(file_exists($libFile))
    require_once $libFile;

$_unifiController = null;

class unifi extends eqLogic {
    /***************************Attributs*******************************/
    private static $_eqLogics = null;

    public static function restore() {
        try {
            unifi::syncUnifi();
        } catch (Exception $e) {

        }
    }

    public static function cronDaily() {
        self::deamon_start();
        unifi::syncUnifi();
    }

    public static function dependancy_info() {
        $return = array();
        $return['progress_file'] = jeedom::getTmpFolder('unifi') . '/dependance';
        $libFile = dirname(__FILE__) . '/../../resources/UniFi-API-client/src/Client.php';
        $cmd = file_exists($libFile);

        $return['state'] = 'nok';
        if ($cmd) {
            $return['state'] = 'ok';
        }
        return $return;
    }

    public static function dependancy_install() {
        $dep_info = self::dependancy_info();
        log::remove(__CLASS__ . '_dep');
        return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder('unifi') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_dep'));
    }

    public static function deamon_info() {
        $return = array();
        $return['log'] = '';
        $return['state'] = 'nok';
        $cron = cron::byClassAndFunction('unifi', 'pull');
        if (is_object($cron) && $cron->running()) {
            $return['state'] = 'ok';
        }
        $return['launchable'] = 'ok';
        return $return;
    }

    public static function deamon_start() {
        self::deamon_stop();
        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }
        $cron = cron::byClassAndFunction('unifi', 'pull');
        if (!is_object($cron)) {
            throw new Exception(__('Tache cron introuvable', __FILE__));
        }
        $cron->run();
    }

    public static function deamon_stop() {
        $cron = cron::byClassAndFunction('unifi', 'pull');
        if (!is_object($cron)) {
            throw new Exception(__('Tache cron introuvable', __FILE__));
        }
        self::killController();
        $cron->halt();
    }

    public static function deamon_changeAutoMode($_mode) {
        $cron = cron::byClassAndFunction('unifi', 'pull');
        if (!is_object($cron)) {
            throw new Exception(__('Tache cron introuvable', __FILE__));
        }
        $cron->setEnable($_mode);
        $cron->save();
    }
    /*public static function interact($_query, $_parameters = array()) {
        return array('reply' => 'Non supporté');
    }*/

    public static function getController() {
        global $_unifiController;
        //log::add('unifi', 'debug', "_unifiController (".is_object($_unifiController).')');
        if ($_unifiController === null) {
            $_unifiController=self::login();
        }
        return $_unifiController;
    }

    public static function killController() {
        global $_unifiController;
        if ($_unifiController !== null) {
            $_unifiController=self::logout();
        }
        unset($_unifiController);
        $_unifiController=null;
    }

    public static function login () {
        log::add('unifi', 'debug', "Login en cours..");
        $controller_user = config::byKey('controller_user','unifi','',true);
        $controller_password = config::byKey('controller_password','unifi','',true);
        $controller_url = 'https://'.config::byKey('controller_ip','unifi','',true).':'.config::byKey('controller_port','unifi','8443',true);
        $site_id = config::byKey('site_id','unifi','default',true);

        $controller = new UniFi_API\Client($controller_user, $controller_password, $controller_url, $site_id, /*version*/'', false);
        if (is_object($controller)) {
            $login            = $controller->login();
            if($login !== true) {
                log::add('unifi', 'error', "Erreur d'authentification, Vérifiez le nom d'utilisateur et mot de passe (".$login.') '.$controller->get_last_error_message());
                return false;
            }
        } else {
            log::add('unifi', 'error', "Error Creation client:".$controller->get_last_error_message());
            return false;
        }

        log::add('unifi', 'info', "Login OK (".$login.')');
        return $controller;
    }

    public static function getInsight($sta,$insights,$mac) {
        if(!is_array($insights)) return;
        $mac = ((isset($sta->mac))?$sta->mac:$mac);
        //if(isset($sta->blocked)) return ['insight'=>null,'blocked'=>$sta->blocked];
        foreach ($insights as $insight) {
            if($insight->mac != $mac) continue;
            if(isset($insight->blocked)) {
                return ['insight'=>$insight,'blocked'=>$insight->blocked];
            } else return ['insight'=>$insight,'blocked'=>false];
        }
    }

    public static function getSettings($controller) {
        if (!$controller) $controller = self::getController();
        if (!$controller) {
            log::add('unifi', 'error', "no controller ");
            return;
        }

        $settings = json_decode(json_encode($controller->list_settings()), true);
        $site_settings=[];

        foreach ($settings as $setting) {
            $key = $setting['key'];
            switch($key) {
                case "mgmt":
                    $setting['x_ssh_password']="OBFUSCATED";
                    $setting['x_ssh_sha512passwd']="OBFUSCATED";
                    break;
                case "radius":
                    $setting['x_secret']="OBFUSCATED";
                    break;
                case "super_cloudaccess":
                    $setting['x_private_key']="OBFUSCATED";
                    $setting['x_certificate_pem']="OBFUSCATED";
                    $setting['x_certificate_arn']="OBFUSCATED";
                    $setting['device_auth']="OBFUSCATED";
                    break;
                case "super_mgmt":
                    $setting['google_maps_api_key']="OBFUSCATED";
                    break;
            }
            $site_settings[$key]=$setting;
        }
        return $site_settings;
    }

    public static function getStateTxt($stateNum) {
        $stateTxt = [	0 => __("Déconnecté", __FILE__),
            1 => __("Connecté", __FILE__),
            4 => __("Mise à jour", __FILE__),
            5 => __("Provisionnement", __FILE__),
            6 => __("Pulsation manquée", __FILE__),
            7 => __("Adoption", __FILE__)
        ];
        return $stateTxt[$stateNum];
    }

    public static function pull($_eqLogic_id = null) {
        $mt = getMicroTime();
        $changed = false;
        $controller = self::getController();
        if (!$controller) {
            log::add('unifi', 'error', "no controller ");
            return;
        }

        /*$state = $controller->stat_status();
        log::add('unifi', 'debug', "controller status is ".$state);
        if($state != 'true') {

            //return;
        }*/
        $insights = $controller->list_users(); // needed to know blocked clients :'(
        $site_settings=unifi::getSettings($controller);

        if (self::$_eqLogics == null) {
            self::$_eqLogics = self::byType('unifi');
        }
        foreach (self::$_eqLogics as $eqLogic) {
            if ($_eqLogic_id != null && $_eqLogic_id != $eqLogic->getId()) {
                continue;
            }
            if ($eqLogic->getIsEnable() == 0) {
                $eqLogic->refresh();
                if ($eqLogic->getIsEnable() == 0) {
                    continue;
                }
            }
            $logicalId = $eqLogic->getLogicalId();
            if ($logicalId == '') {
                continue;
            }

            try {
                $changed = false;

                switch($eqLogic->getConfiguration('type','')) {
                    case 'wlan':
                        $wlan = $controller->list_wlanconf($logicalId)[0];
                        if($wlan) {
                            $wlan->x_passphrase="OBFUSCATED";
                            $wlangroup=null;
                            $wlangroups = $controller->list_wlan_groups();
                            foreach($wlangroups as $wlg) {
                                if($wlg->_id != $wlan->wlangroup_id) continue;
                                $wlangroup=$wlg;
                                break;
                            }
                            $changed = $eqLogic->checkAndUpdateCmd($logicalId.'::enabled', $wlan->enabled) || $changed;
                        }
                        if($changed) {
                            log::add('unifi', 'info', "Update WLAN(".$wlan->name.'::enabled to '.$wlan->enabled.')');
                            log::add('unifi', 'debug', "Update WLAN(".$wlan->name.') json : '.json_encode($wlan).json_encode($wlangroup));
                        }
                        break;
                    case 'uap' :
                        $device = $controller->list_devices($logicalId)[0];
                        $name=((isset($device->name) && $device->name)?$device->name:$device->model.'_'.$device->ip);
                        if($device) {
                            $changed = $eqLogic->checkAndUpdateCmd('last_seen', date("d-m-Y H:i:s", $device->last_seen)) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('system-stats::cpu', $device->{'system-stats'}->cpu) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('system-stats::mem', $device->{'system-stats'}->mem) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('system-stats::uptime', unifi::secondsToTime($device->{'system-stats'}->uptime)) || $changed;

                            $state = (($device->state==1)?'1':'0');
                            $changed = $eqLogic->checkAndUpdateCmd('stateBin', $state) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('stateNum', $device->state) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('stateTxt', unifi::getStateTxt($device->state)) || $changed;

                            $changed = $eqLogic->checkAndUpdateCmd('locating', unifi::convertState($device->locating)) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('upgradable', unifi::convertState($device->upgradable)) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('version_incompatible', unifi::convertState($device->version_incompatible)) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('spectrum_scanning', unifi::convertState($device->spectrum_scanning)) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('num_sta', $device->num_sta) || $changed;

                            foreach($device->radio_table_stats as $radio) {
                                $changed = $eqLogic->checkAndUpdateCmd($radio->radio.'::num_sta', $radio->num_sta) || $changed;
                            }

                            foreach($device->vap_table as $vap) {
                                $changed = $eqLogic->checkAndUpdateCmd($vap->id.'::'.$vap->name.'::is_guest', $vap->is_guest) || $changed;
                                $changed = $eqLogic->checkAndUpdateCmd($vap->id.'::'.$vap->name.'::channel', $vap->channel) || $changed;
                                $changed = $eqLogic->checkAndUpdateCmd($vap->id.'::'.$vap->name.'::up', (($vap->up)?'1':'0')) || $changed;
                                $changed = $eqLogic->checkAndUpdateCmd($vap->id.'::'.$vap->name.'::state', $vap->state) || $changed;
                                $changed = $eqLogic->checkAndUpdateCmd($vap->id.'::'.$vap->name.'::num_sta', $vap->num_sta) || $changed;
                            }
                        } else {
                            $state='0';
                            $changed = $eqLogic->checkAndUpdateCmd('state', $state) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('stateNum', $state) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('stateTxt', unifi::getStateTxt($state)) || $changed;
                        }
                        if($changed) {
                            log::add('unifi', 'info', "Update UAP(".$name.'::state is '.$device->state.'('.$state.'))');
                            log::add('unifi', 'debug', "Update UAP(".$name.') json : '.json_encode($device));
                        }
                        break;
                    case 'ugw' :
                        $device = $controller->list_devices($logicalId)[0];
                        $name=((isset($device->name) && $device->name)?$device->name:$device->model.'_'.$device->ip);
                        if($device) {
                            $changed = $eqLogic->checkAndUpdateCmd('last_seen', date("d-m-Y H:i:s", $device->last_seen)) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('system-stats::cpu', $device->{'system-stats'}->cpu) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('system-stats::mem', $device->{'system-stats'}->mem) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('system-stats::uptime', unifi::secondsToTime($device->{'system-stats'}->uptime)) || $changed;

                            $state = (($device->state==1)?'1':'0');
                            $changed = $eqLogic->checkAndUpdateCmd('stateBin', $state) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('stateNum', $device->state) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('stateTxt', unifi::getStateTxt($device->state)) || $changed;

                            $changed = $eqLogic->checkAndUpdateCmd('upgradable', unifi::convertState($device->upgradable)) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('version_incompatible', unifi::convertState($device->version_incompatible)) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('num_sta', $device->num_sta) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('ipWan', $device->uplink->ip) || $changed;

                            foreach($device->port_table as $port) {
                                $changed = $eqLogic->checkAndUpdateCmd($port->ifname.'::enable', (($port->enable)?'1':'0')) || $changed;
                                $changed = $eqLogic->checkAndUpdateCmd($port->ifname.'::up', (($port->up)?'1':'0')) || $changed;
                                $changed = $eqLogic->checkAndUpdateCmd($port->ifname.'::ip', $port->ip) || $changed;
                            }
                        } else {
                            $state='0';
                            $changed = $eqLogic->checkAndUpdateCmd('state', $state) || $changed;
                        }
                        if($changed) {
                            log::add('unifi', 'info', "Update UGW(".$name.'::state is '.$device->state.'('.$state.'))');
                            log::add('unifi', 'debug', "Update UGW(".$name.') json : '.json_encode($device));
                        }
                        break;
                    case 'usw' :
                        $device = $controller->list_devices($logicalId)[0];
                        $name=((isset($device->name) && $device->name)?$device->name:$device->model.'_'.$device->ip);
                        if($device) {
                            $changed = $eqLogic->checkAndUpdateCmd('last_seen', date("d-m-Y H:i:s", $device->last_seen)) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('system-stats::cpu', $device->{'system-stats'}->cpu) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('system-stats::mem', $device->{'system-stats'}->mem) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('system-stats::uptime', unifi::secondsToTime($device->{'system-stats'}->uptime)) || $changed;

                            $state = (($device->state==1)?'1':'0');
                            $changed = $eqLogic->checkAndUpdateCmd('stateBin', $state) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('stateNum', $device->state) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('stateTxt', unifi::getStateTxt($device->state)) || $changed;

                            $changed = $eqLogic->checkAndUpdateCmd('upgradable', unifi::convertState($device->upgradable)) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('version_incompatible', unifi::convertState($device->version_incompatible)) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('num_sta', $device->num_sta) || $changed;
                            $changed = $eqLogic->checkAndUpdateCmd('overheating', unifi::convertState($device->overheating)) || $changed;

                            foreach($device->port_table as $port) {
                                $changed = $eqLogic->checkAndUpdateCmd($port->portconf_id.'::enable::'.$port->port_idx, (($port->enable)?'1':'0')) || $changed;
                                $changed = $eqLogic->checkAndUpdateCmd($port->portconf_id.'::up::'.$port->port_idx, (($port->up)?'1':'0')) || $changed;
                                $changed = $eqLogic->checkAndUpdateCmd($port->portconf_id.'::port_poe::'.$port->port_idx, (($port->port_poe)?'1':'0')) || $changed;
                            }
                        } else {
                            $state='0';
                            $changed = $eqLogic->checkAndUpdateCmd('state', $state) || $changed;
                        }
                        if($changed) {
                            log::add('unifi', 'info', "Update USW(".$name.'::state is '.$device->state.'('.$state.'))');
                            log::add('unifi', 'debug', "Update USW(".$name.') json : '.json_encode($device));
                        }
                        break;
                    case 'sta' :
                        $sta = $controller->list_clients($logicalId)[0];
                        $insight=self::getInsight($sta,$insights,$logicalId);

                        if($sta && isset($sta->mac)) {
                            $present='1';
                            $data=$sta;
                        } else {
                            $present='0';
                            $data=$insight['insight'];
                        }
                        $name= ((isset($data->name) && $data->name)?$data->name:((isset($data->hostname) && $data->hostname)?$data->hostname:$data->mac));

                        if(isset($data->last_seen))	$changed = $eqLogic->checkAndUpdateCmd('last_seen', date("d-m-Y H:i:s", $data->last_seen)) || $changed;
                        $changed = $eqLogic->checkAndUpdateCmd('present', $present) || $changed;
                        $changed = $eqLogic->checkAndUpdateCmd('ip', ((isset($data->ip))?$data->ip:((isset($data->fixed_ip))?$data->fixed_ip:''))) || $changed;
                        $changed = $eqLogic->checkAndUpdateCmd('blocked', (($insight['blocked'])?'1':'0')) || $changed;

                        if(isset($data->uptime)) $changed = $eqLogic->checkAndUpdateCmd('uptime', unifi::secondsToTime($data->uptime)) || $changed;
                        if(isset($data->essid)) $changed = $eqLogic->checkAndUpdateCmd('essid', $data->essid) || $changed;
                        if(isset($data->vlan)) $changed = $eqLogic->checkAndUpdateCmd('vlan', $data->vlan) || $changed;
                        if(isset($data->channel)) $changed = $eqLogic->checkAndUpdateCmd('channel', $data->channel) || $changed;

                        if($changed) {
                            log::add('unifi', 'info', "Update STA(".$name.'::present is '.$present.(($insight['blocked'])?' but blocked':'').') '.((isset($sta->last_seen))?'lastSeen sta '.date("d-m-Y H:i:s", $sta->last_seen):'').'/'.((isset($insight['insight']->last_seen))?'lastSeen insight '.date("d-m-Y H:i:s", $insight['insight']->last_seen):''));
                            log::add('unifi', 'debug', "Update STA(".$name.") json : ". (($present)?json_encode($sta).json_encode($insight['insight']):json_encode($insight['insight'])));
                        }
                        break;
                    case "site":
                        $led_enabled=$site_settings['mgmt']['led_enabled'];
                        $changed = $eqLogic->checkAndUpdateCmd('led_enabled', (($led_enabled)?'1':'0')) || $changed;
                        $name=$eqLogic->getName();

                        if($changed) {
                            log::add('unifi', 'info', "Update Site(".$name.')');
                            log::add('unifi', 'debug', "Update Site(".$name.") json : ". json_encode($site_settings));
                        }
                        break;
                }

                if ($changed)
                    $eqLogic->refreshWidget();

                if ($eqLogic->getConfiguration('unifiNumberFailed', 0) > 0) {
                    foreach (message::byPluginLogicalId('unifi', 'unifiLost' . $eqLogic->getId()) as $message) {
                        $message->remove();
                    }
                    $eqLogic->setConfiguration('unifiNumberFailed', 0);
                    $eqLogic->save();
                }
            } catch (Exception $e) {
                if ($_eqLogic_id != null) {
                    log::add('unifi', 'error', $e->getMessage());
                } else {
                    $eqLogic->refresh();
                    if ($eqLogic->getIsEnable() == 0) {
                        continue;
                    }
                    if ($eqLogic->getConfiguration('unifiNumberFailed', 0) == 150) {
                        log::add('unifi', 'error', __('Erreur sur ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $e->getMessage(), 'unifiLost' . $eqLogic->getId());
                    } else {
                        $eqLogic->setConfiguration('unifiNumberFailed', $eqLogic->getConfiguration('unifiNumberFailed', 0) + 1);
                        $eqLogic->save();
                    }
                }
            }
        }
    }

    public static function syncUnifi() {
        log::add('unifi', 'info', "syncUnifi");

        $controller = self::getController();
        if (!$controller)
            return;
        $state = $controller->stat_status();
        log::add('unifi', 'debug', "status :".$state);
        if($state != 'true')
            return;


        $devices = $controller->list_devices();

        foreach($devices as $device) {
            $name=((isset($device->name) && $device->name)?$device->name:$device->model.'_'.$device->mac);
            $jdevice = unifi::byLogicalId($device->mac, 'unifi');
            if (!is_object($jdevice)) {
                log::add('unifi', 'info', "Trouvé Device ".$name."(".$device->mac."):".json_encode($device));
                $eqLogic = new unifi();
                $eqLogic->setName($name);
                $eqLogic->setIsEnable(1);
                $eqLogic->setIsVisible(1);
                $eqLogic->setLogicalId($device->mac);
                $eqLogic->setEqType_name('unifi');
                $eqLogic->setConfiguration('type', $device->type);
                $eqLogic->setConfiguration('model', $device->model);
                $eqLogic->setConfiguration('modelName',self::getFullModelName($device->model));
                $eqLogic->setConfiguration('serial',$device->serial);
                $eqLogic->setConfiguration('device_id',$device->device_id);
                $eqLogic->setConfiguration('oui','Unifi');
                $eqLogic->setConfiguration('image',$eqLogic->getImage());
                $eqLogic->setConfiguration('mac',$device->mac);
            } else {
                log::add('unifi', 'info', "Mise à jour Device ".$name."(".$device->mac."):".json_encode($device));
                $eqLogic = $jdevice;
            }

            if($device->type == "ugw") $eqLogic->setConfiguration('ip', $device->config_network->ip);
            else $eqLogic->setConfiguration('ip', $device->ip);

            if(isset($device->version)) $eqLogic->setConfiguration('version',$device->version);

            $eqLogic->save();

            if(!is_object($jdevice)) { // NEW
                event::add('jeedom::alert', array(
                    'level' => 'warning',
                    'page' => 'unifi',
                    'message' => __('Périphérique inclu avec succès : ' .$name, __FILE__),
                ));
            } else { // UPDATED
                event::add('jeedom::alert', array(
                    'level' => 'warning',
                    'page' => 'unifi',
                    'message' => __('Périphérique mis à jour avec succès : ' .$name, __FILE__),
                ));
            }
        }

        $wlans = $controller->list_wlanconf();

        foreach($wlans as $wlan) {
            $wlan->x_passphrase="OBFUSCATED";

            $wlangroup=null;
            $wlangroups = $controller->list_wlan_groups();
            foreach($wlangroups as $wlg) {
                if($wlg->_id != $wlan->wlangroup_id) continue;
                $wlangroup=$wlg;
                break;
            }
            //log::add('unifi', 'debug', "wlangroup :".json_encode($wlangroup));
            $jwlan = unifi::byLogicalId($wlan->_id, 'unifi');
            if (!is_object($jwlan)) {
                log::add('unifi', 'info', "Trouvé WLAN ".$wlan->name.":".json_encode($wlan));
                $eqLogic = new unifi();
                $eqLogic->setName($wlan->name);
                $eqLogic->setIsEnable(0);
                $eqLogic->setIsVisible(0);
                $eqLogic->setLogicalId($wlan->_id);
                $eqLogic->setEqType_name('unifi');
                $eqLogic->setConfiguration('type', 'wlan');
                $eqLogic->setConfiguration('device_id',$wlan->_id);
                $eqLogic->setConfiguration('image',$eqLogic->getImage());
            } else {
                log::add('unifi', 'info', "Mise à jour WLAN ".$wlan->name.":".json_encode($wlan));
                $eqLogic = $jwlan;
            }

            if(isset($wlan->is_guest)) $eqLogic->setConfiguration('model', (($wlan->is_guest)?'Guest':'Not Guest'));
            $eqLogic->setConfiguration('modelName', $wlangroup->name);

            $eqLogic->save();

            if(!is_object($jwlan)) { // NEW
                event::add('jeedom::alert', array(
                    'level' => 'warning',
                    'page' => 'unifi',
                    'message' => __('Wifi inclu avec succès : ' .$wlan->name, __FILE__),
                ));
            } else { // UPDATED
                event::add('jeedom::alert', array(
                    'level' => 'warning',
                    'page' => 'unifi',
                    'message' => __('Wifi mis à jour avec succès : ' .$wlan->name, __FILE__),
                ));
            }
        }

        $stas = $controller->list_clients();

        foreach($stas as $sta) {
            $mac = $sta->mac;
            $name= ((isset($sta->name) && $sta->name)?$sta->name:((isset($sta->hostname) && $sta->hostname)?$sta->hostname:$sta->ip));

            $jsta = unifi::byLogicalId($mac, 'unifi');
            if (!is_object($jsta)) {
                log::add('unifi', 'info', "Trouvé Client ".$name."(".$mac."):".json_encode($sta));
                $eqLogic = new unifi();
                $eqLogic->setName($name);
                $eqLogic->setIsEnable(0);
                $eqLogic->setIsVisible(0);
                $eqLogic->setLogicalId($mac);
                $eqLogic->setEqType_name('unifi');
                $eqLogic->setConfiguration('type', 'sta');
                $eqLogic->setConfiguration('image',$eqLogic->getImage());
                $eqLogic->setConfiguration('device_id',$sta->_id);
                $eqLogic->setConfiguration('oui',$sta->oui);
                $eqLogic->setConfiguration('mac',$mac);
            } else {
                log::add('unifi', 'info', "Mise à jour Client ".$name."(".$mac."):".json_encode($sta));
                $eqLogic = $jsta;
            }

            $eqLogic->setConfiguration('ip', $sta->ip);
            if(isset($sta->is_wired)) $eqLogic->setConfiguration('model',(($sta->is_wired)?'wired':'wireless'));
            if(isset($sta->hostname)) $eqLogic->setConfiguration('modelName',$sta->hostname);

            if(isset($sta->is_wired) && $sta->is_wired) {
                if(isset($sta->sw_port)) $eqLogic->setConfiguration('sw_port',$sta->sw_port);
                if(isset($sta->sw_mac)) $eqLogic->setConfiguration('sw_mac',$sta->sw_mac);
            } else {
                if(isset($sta->sw_port)) $eqLogic->setConfiguration('sw_port',$sta->sw_port);
                if(isset($sta->ap_mac)) $eqLogic->setConfiguration('ap_mac',$sta->ap_mac);
            }
            if(isset($sta->gw_mac)) $eqLogic->setConfiguration('gw_mac',$sta->gw_mac);

            $eqLogic->save();

            if(!is_object($jsta)) { // NEW
                event::add('jeedom::alert', array(
                    'level' => 'warning',
                    'page' => 'unifi',
                    'message' => __('Client inclu avec succès : ' .$name, __FILE__),
                ));
            } else { // UPDATED
                event::add('jeedom::alert', array(
                    'level' => 'warning',
                    'page' => 'unifi',
                    'message' => __('Client mis à jour avec succès : ' .$name, __FILE__),
                ));
            }
        }

        $sites = $controller->stat_sites();

        foreach($sites as $site) {
            $name= ((isset($site->name) && $site->name)?$site->name:'default');

            $jsite = unifi::byLogicalId($name, 'unifi');
            if (!is_object($jsite)) {
                log::add('unifi', 'info', "Trouvé Site ".$name.":".json_encode($site));
                $eqLogic = new unifi();
                $eqLogic->setName($name);
                $eqLogic->setIsEnable(1);
                $eqLogic->setIsVisible(1);
                $eqLogic->setLogicalId($name);
                $eqLogic->setEqType_name('unifi');
                $eqLogic->setConfiguration('type', 'site');
                $eqLogic->setConfiguration('image',$eqLogic->getImage());
                $eqLogic->setConfiguration('device_id',$site->_id);
            } else {
                log::add('unifi', 'info', "Mise à jour Site ".$name.":".json_encode($site));
                $eqLogic = $jsite;
            }

            $eqLogic->save();

            if(!is_object($jsta)) { // NEW
                event::add('jeedom::alert', array(
                    'level' => 'warning',
                    'page' => 'unifi',
                    'message' => __('Site inclu avec succès : ' .$name, __FILE__),
                ));
            } else { // UPDATED
                event::add('jeedom::alert', array(
                    'level' => 'warning',
                    'page' => 'unifi',
                    'message' => __('Site mis à jour avec succès : ' .$name, __FILE__),
                ));
            }
        }

        //self::deamon_start();
    }

    public static function getFullModelName($device = false) {
        $file = dirname(__FILE__) . '/../config/names.json';
        if (!file_exists($file)) {
            return false;
        }
        try {
            $content = file_get_contents($file);
            $return = json_decode($content, true);
        } catch (Exception $e) {
            return false;
        }

        if($device && $return[$device]) return $return[$device];
        return false;
    }

    public static function getDeviceCommands($device = '') {
        $path = dirname(__FILE__) . '/../config/' . $device.'/';
        if (!is_dir($path)) {
            return false;
        }
        try {
            $file = $path . '/' . $device.'.json';
            $content = file_get_contents($file);
            log::add('unifi','debug','content:'.$content);
            $return = json_decode($content, true);

            log::add('unifi','debug','commands:'.$content);
        } catch (Exception $e) {
            return false;
        }

        return $return;
    }

    public static function getCommonCommands() {
        $file = dirname(__FILE__) . '/../config/common.json';
        if (!file_exists($file)) {
            return false;
        }
        try {
            $content = file_get_contents($file);
            $return = json_decode($content, true);
        } catch (Exception $e) {
            return false;
        }

        return $return['commands'];
    }

    public static function convertState($_state) {
        switch ($_state) {
            case 'true':
                return 1;
            case 'false':
                return 0;
        }
        return $_state;
    }

    public static function secondsToTime($ss) {
        $s = $ss%60;
        $m = floor(($ss%3600)/60);
        $h = floor(($ss%86400)/3600);
        $d = floor(($ss)/86400);

        if($d)
            return sprintf("%d.%02d:%02d:%02d",$d,$h,$m,$s);
        else
            return sprintf("%02d:%02d:%02d",$h,$m,$s);
    }

    public function preSave() {
        $this->setCategory('monitoring', 1);
    }

    public function getImage($which='static'){
        $type=$this->getConfiguration('type',false);
        $model=$this->getConfiguration('model',false);
        if($type == 'wlan' || $type == 'sta' || $type == 'site') return 'plugins/unifi/core/config/uap/missing/'.$which.'@2x.png';
        if(!$model || !$type)
            return 'plugins/unifi/plugin_info/unifi_icon.png';
        $base = dirname(__FILE__) . '/../../../../';
        $path = 'plugins/unifi/core/config/'.$type.'/'.$model.'/'.$which.'@2x.png';
        $pathDefault = 'plugins/unifi/core/config/'.$type.'/default/'.$which.'@2x.png';
        $pathMissing = 'plugins/unifi/core/config/'.$type.'/missing/'.$which.'@2x.png';

        if(file_exists($base.$path)) return $path;
        else if(file_exists($base.$pathDefault)) return $pathDefault;
        else if(file_exists($base.$pathMissing)) return $pathMissing;
        else return 'plugins/unifi/plugin_info/unifi_icon.png';
    }

    public function postSave() {
        log::add('unifi','debug','postSave');
        $order=0;

        $type=$this->getConfiguration('type','');
        log::add('unifi','debug','type:'.$type);
        if($type == 'wlan' || $type == 'sta' || $type == 'site') {
            $commonCommands = [];
            $specificCommands = self::getDeviceCommands($type);
        } else {
            $commonCommands = self::getCommonCommands();
            log::add('unifi','debug','common:'.json_encode($commonCommands));
            $specificCommands = self::getDeviceCommands($type);
            log::add('unifi','debug','specific:'.json_encode($specificCommands));
        }


        if($specificCommands) {
            $controller = self::getController();
            if (!$controller)
                return;
            $state = $controller->stat_status();
            if($state != 'true')
                return;

            $commands = array_merge($commonCommands,$specificCommands['commands']);

            /* // TO ADAPT
            $arrayToRemove = [];
            foreach ($this->getCmd() as $eqLogic_cmd) {
                $exists = 0;
                foreach ($commands as $command) {
                    if ($command['logicalId'] == $eqLogic_cmd->getLogicalId()) {
                        $exists++;
                    }
                }
                if ($exists < 1) {
                    $arrayToRemove[] = $eqLogic_cmd;
                }
            }
            foreach ($arrayToRemove as $cmdToRemove) {
                try {
                    $cmdToRemove->remove();
                } catch (Exception $e) {

                }
            }*/

            foreach($commands as $cmd) {
                $order++;
                if(isset($specificCommands['replace']) && $specificCommands['replace']) {
                    if($type == 'wlan') {
                        $device = $controller->list_wlanconf($this->getLogicalId())[0];
                    } else {
                        $device = $controller->list_devices($this->getLogicalId())[0];
                    }
                    foreach($specificCommands['replace'] as $what => $by) {
                        $cmd['name']=str_replace('#'.$what.'#',$device->{$by},$cmd['name']);
                        $cmd['logicalId']=str_replace('#'.$what.'#',$device->{$by},$cmd['logicalId']);
                        if(isset($cmd['value']) && $cmd['value']) $cmd['value']=str_replace('#'.$what.'#',$device->{$by},$cmd['value']);
                    }
                }
                $this->createCmd($cmd,$order);
            }

            if(isset($specificCommands['multiples']) && $specificCommands['multiples']) {
                $device = $controller->list_devices($this->getLogicalId())[0];

                foreach($specificCommands['multiples'] as $table => $fields) { // for each multiples in config file
                    foreach($device->{$table} as $thisMultiple) { // for each item of the table in device
                        foreach($fields['commands'] as $cmd) { // for each commands in multiple
                            $order++;
                            if($fields['replace']) {
                                foreach($fields['replace'] as $what => $by) { // for each replacement
                                    $cmd['name']=str_replace('#'.$what.'#',$thisMultiple->{$by},$cmd['name']);
                                    $cmd['logicalId']=str_replace('#'.$what.'#',$thisMultiple->{$by},$cmd['logicalId']);
                                    if(isset($cmd['value']) && $cmd['value']) $cmd['value']=str_replace('#'.$what.'#',$thisMultiple->{$by},$cmd['value']);
                                }
                            }
                            $this->createCmd($cmd,$order);
                        }
                    }
                }
            }
        }
    }

    public function createCmd($cmd,$order) {

        log::add('unifi','debug','Création commande:'.$cmd['logicalId']);
        $newCmd = $this->getCmd(null, $cmd['logicalId']);
        if (!is_object($newCmd)) {
            $newCmd = new unifiCmd();
            $newCmd->setLogicalId($cmd['logicalId']);
            $newCmd->setIsVisible($cmd['isVisible']);
            $newCmd->setOrder($order);
            $newCmd->setName(__($cmd['name'], __FILE__));
        }
        if(isset($cmd['unit'])) {
            $newCmd->setUnite( $cmd['unit'] );
        }
        $newCmd->setType($cmd['type']);
        if(isset($cmd['configuration'])) {
            foreach($cmd['configuration'] as $configuration_type=>$configuration_value) {
                $newCmd->setConfiguration($configuration_type, $configuration_value);
            }
        }
        if(isset($cmd['template'])) {
            foreach($cmd['template'] as $template_type=>$template_value) {
                $newCmd->setTemplate($template_type, $template_value);
            }

        }
        if(isset($cmd['display'])) {
            foreach($cmd['display'] as $display_type=>$display_value) {
                $newCmd->setDisplay($display_type, $display_value);
            }
        }
        $newCmd->setSubType($cmd['subtype']);
        $newCmd->setEqLogic_id($this->getId());
        if($cmd['type'] == 'action' && isset($cmd['value'])) {
            $linkStatus = $this->getCmd(null, $cmd['value']);
            if(is_object($linkStatus))
                $newCmd->setValue($linkStatus->getId());
        }
        $newCmd->save();

    }
}

class unifiCmd extends cmd {
    /***************************Attributs*******************************/


    /*************************Methode static****************************/

    /***********************Methode d'instance**************************/
    //public static $_widgetPossibility = array('custom' => false);
    public function dontRemoveCmd() { return true; }

    public function execute($_options = null) {
        if ($this->getType() == '') {
            return '';
        }

        $controller = unifi::getController();
        if (!$controller)
            return;
        $state = $controller->stat_status();
        if($state != 'true')
            return;

        $eqLogic = $this->getEqlogic();
        $logical = $this->getLogicalId();
        $mac = $eqLogic->getLogicalId();

        $result=null;
        if ($logical != 'refresh'){
            if(strpos($logical, '::') !== false) { // subcommand
                $command = explode('::',$logical);
                switch ($command[1]) {
                    case 'disable_wlan':
                    case 'enable_wlan':
                        if($command[2]==1)
                            $command[2]=true;
                        elseif($command[2]==0)
                            $command[2]=false;

                        $result=$controller->disable_wlan($command[0],$command[2]);
                        log::add('unifi','info','ACTION:disable_wlan sur '.$eqLogic->getName().'('.$command[0].'),'.$command[2]);
                        break;
                    case 'power_cycle_switch_port':
                        $val=$command[2];
                        $result=$controller->power_cycle_switch_port($mac,$val);
                        log::add('unifi','info','ACTION:power_cycle_switch_port sur '.$eqLogic->getName().'('.$mac.'),'.$val);
                        break;
                }
            } else {
                $id  = $eqLogic->getConfiguration('device_id');
                $result=null;
                switch($logical) {
                    case 'locating_on':
                        $val=true;
                        $result=$controller->locate_ap($mac,$val);
                        break;
                    case 'locating_off':
                        $val=false;
                        $result=$controller->locate_ap($mac,$val);
                        break;
                    case 'upgrade':
                        $upgradable=$eqLogic->getCmd(null,'upgradable')->execCmd();
                        if($upgradable)
                            $result=$controller->upgrade_device($mac);
                        break;
                    case 'restart_ap':
                        $result=$controller->restart_ap($mac);
                        break;
                    case 'state_on':
                        $val=false;
                        $mac=$id;
                        $result=$controller->disable_ap($id,$val);
                        break;
                    case 'state_off':
                        $val=true;
                        $mac=$id;
                        $result=$controller->disable_ap($id,$val);
                        break;
                    case 'led_on':
                        $val=true;
                        $mac='';
                        $result=$controller->site_leds($val);
                        break;
                    case 'led_off':
                        $val=false;
                        $mac='';
                        $result=$controller->site_leds($val);
                        break;
                    case 'spectrum_scan':
                        $spectrum_scanning=$eqLogic->getCmd(null,'spectrum_scanning')->execCmd();
                        if(!$spectrum_scanning)
                            $result=$controller->spectrum_scan($mac);
                        break;
                    case 'block_sta':
                        $result=$controller->block_sta($mac);
                        break;
                    case 'unblock_sta':
                        $result=$controller->unblock_sta($mac);
                        break;
                    case 'reconnect_sta':
                        $result=$controller->reconnect_sta($mac);
                        break;
                    case 'adopt_device' :
                        $result=$controller->adopt_device($mac);
                        break;
                }
                log::add('unifi','info','ACTION:'.$logical.' sur '.$eqLogic->getName().'('.$mac.')'.((isset($val))?','.$val:''));
            }
            $result=unifi::convertState($result);

            if(!$result)
                log::add('unifi','error',json_encode($result).'-'.$controller->get_last_results_raw(true));
            else
                log::add('unifi','debug',json_encode($result).'-'.$controller->get_last_results_raw(true));
        } else {
            unifi::pull($eqLogic->getId());
        }
    }

    /************************Getteur Setteur****************************/
}
?>
