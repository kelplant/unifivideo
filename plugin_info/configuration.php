<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
?>
<form class="form-horizontal">
    <fieldset>
        <legend><i class="fa fa-list-alt"></i> {{Général}}</legend>
        <div class="form-group">
            <label class="col-sm-4 control-label">{{Adresse IP du serveur}}</label>
            <div class="col-sm-2">
                <input class="configKey form-control" data-l1key="srvIpAddress" placeholder="Your Server IP Adress (Or CDN)"/>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label">{{Port du serveur}}</label>
            <div class="col-sm-2">
                <input class="configKey form-control" data-l1key="srvPort" placeholder="7443 (SSL mandatory for Auto Detection)"/>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label">{{API key}}</label>
            <div class="col-sm-2">
                <input class="configKey form-control" data-l1key="apiKey" placeholder="Your Api Key"/>
            </div>
        </div>
    </fieldset>
</form>
