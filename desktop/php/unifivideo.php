<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('unifivideo');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un équipement}}</a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
                foreach ($eqLogics as $eqLogic) {
                    $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
                    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '" style="' . $opacity . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
                }
                ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay">
        <legend><i class="fa fa-cog"></i> {{Gestion}}</legend>
        <div class="eqLogicThumbnailContainer">
            <?php
            if (config::byKey('include_mode', 'unifivideo', 0) == 1) {
                echo '<div class="cursor changeIncludeState include card" data-mode="1" data-state="0" style="background-color : #ffffff; height : 140px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >';
                echo '<center class="includeicon">';
                echo '<i class="fa fa-spinner fa-pulse" style="font-size : 6em;color:red"></i>';
                echo '</center>';
                echo '<span class="includeicon_text" style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:red;font-weight: bold;"><center>{{Arrêter Scan}}</center></span>';
                echo '</div>';
            } else {
                echo '<div class="cursor changeIncludeState include card" data-mode="1" data-state="1" style="background-color : #ffffff; height : 140px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >';
                echo '<center class="includeicon">';
                echo '<i class="fa fa-bullseye" style="font-size : 6em;color:#94ca02;"></i>';
                echo '</center>';
                echo '<span class="includeicon_text" style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02";font-weight: normal;><center>{{Lancer Scan}}</center></span>';
                echo '</div>';
            }
            ?>
            <!-- Bouton d ajout d un objet -->
            <div class="cursor eqLogicAction" data-action="add"
                 style="background-color : #ffffff; height : 140px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <center>
                    <i class="fa fa-plus-circle" style="font-size : 6em;color:#94ca02;"></i>
                </center>
                <span
                        style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02"><center>{{Ajouter}}</center></span>
            </div>
            <!-- Bouton d accès à la configuration -->
            <div class="cursor eqLogicAction" data-action="gotoPluginConf"
                 style="background-color : #ffffff; height : 140px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <center>
                    <i class="fa fa-wrench" style="font-size : 6em;color:#767676;"></i>
                </center>
                <span
                        style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Configuration}}</center></span>
            </div>
        </div>
        <!-- Début de la liste des objets -->
        <legend><i class="fa fa-table"></i> {{Mes objects}}</legend>
        <!-- Container de la liste -->
        <div class="eqLogicThumbnailContainer">
            <!-- Boucle sur les objects -->
            <?php
            foreach ($eqLogics as $eqLogic) {
                $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
                echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="text-align: center; background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
                echo '<img src="' . $plugin->getPathImgIcon() . '" height="105" width="95" />';
                echo "<br>";
                echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">' . $eqLogic->getHumanName(true, true) . '</span>';
                echo '</div>';
            }
            ?>
        </div>
    </div>


    <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
        <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
        <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
        <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
        <a class="btn btn-default eqLogicAction pull-right" data-action="copy"><i class="fa fa-files-o"></i> {{Dupliquer}}</a>
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
            <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
            <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
        </ul>
        <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <br/>
                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">{{Nom de l'équipement sous Jeedom}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label" >{{Objet parent}}</label>
                            <div class="col-sm-3">
                                <select class="form-control eqLogicAttr" data-l1key="object_id">
                                    <option value="">{{Aucun}}</option>
                                    <?php
                                    foreach (object::all() as $object) {
                                        echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group expertModeVisible">
                            <label class="col-sm-2 control-label">{{Nom de la Caméra (NVR)}}</label>
                            <div class="col-sm-2">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="camName" placeholder="{{Camera Name}}"/>
                            </div>
                            <div class="col-sm-1">
                                <i class="fa fa-question-circle cursor floatright" id="bt_cronGenerator"></i>
                            </div>
                        </div>
                        <div class="form-group expertModeVisible">
                            <label class="col-sm-2 control-label">{{Clé Secrète de la Caméra (NVR)}}</label>
                            <div class="col-sm-2">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="camKey" placeholder="{{Camera Key}}"/>
                            </div>
                            <div class="col-sm-1">
                                <i class="fa fa-question-circle cursor floatright" id="bt_cronGenerator"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">{{Catégorie}}</label>
                            <div class="col-sm-8">
                                <?php
                                foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                    echo '<label class="checkbox-inline">';
                                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value[ 'name' ];
                                    echo '</label>';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label"></label>
                            <div class="col-sm-9">
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
                            </div>
                        </div>


                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{URL de retour}}</label>
                            <div class="col-sm-9 callback">
        <span>
	<?php
    echo network::getNetworkAccess('external') . '/core/api/jeeApi.php?plugin=unifivideo&apikey=' . jeedom::getApiKey($plugin->getId()) . '&type=unifivideo&id=#cmd_id#&value=#value#';
    ?>
	</span>
                            </div>
                        </div>
                    </fieldset>
                </form>

            </div>
            <div role="tabpanel" class="tab-pane" id="commandtab">
                <table id="table_cmd" class="table table-bordered table-condensed">
                    <thead>
                    <tr>
                        <th>{{Nom}}</th><th>{{Action}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>


<?php include_file('desktop', 'unifivideo', 'js', 'unifivideo'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
