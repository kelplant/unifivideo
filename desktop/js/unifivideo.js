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

$('.changeIncludeState').on('click', function () {
    var mode = $(this).attr('data-mode');
    var state = $(this).attr('data-state');
    changeIncludeState(state, mode);
});

$('#bt_healthgooglecast').on('click', function () {
    $('#md_modal').dialog({title: "{{Santé GoogleCast}}"});
    $('#md_modal').load('index.php?v=d&plugin=googlecast&modal=googlecast.health').dialog('open');
});

$('#bt_healthrefresh').on('click', function () {
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // methode de transmission des données au fichier php
        url: "plugins/googlecast/core/php/googlecast.ajax.php", // url du fichier php
        data: {
            action: "refreshall"
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#md_modal').dialog({title: "{{Santé GoogleCast}}"});
            $('#md_modal').load('index.php?v=d&plugin=googlecast&modal=googlecast.health').dialog('open');
        }
    });
});

$('.bt_sidebarToogle').on('click', function () {
    $('.sidebar-container').toggle();
    $('.equipement-container').toggleClass('col-lg-10');
    $('.equipement-container').toggleClass('col-lg-12');
});

$('body').on('googlecast::includeState', function (_event,_options) {
    if (_options['mode'] == 'learn') {
        if (_options['state'] == 1) {
            if($('.include').attr('data-state') != 0){
                $.hideAlert();
                $('.include:not(.card)').removeClass('btn-default').addClass('btn-success');
                $('.include').attr('data-state', 0);
                $('.include.card span center').text('{{Arrêter le scan}}');
                $('.includeicon').empty().append('<i class="fa fa-spinner fa-pulse" style="font-size : 6em;color:red;font-weight: bold;"></i>');
                $('.includeicon_text').css('color', 'red').css('font-weight', 'bold');
                $('#div_inclusionAlert').showAlert({message: '{{Mode scan en cours pendant 1 minute... (Cliquer sur arrêter pour stopper avant)}}', level: 'warning'});
            }
        } else {
            if($('.include').attr('data-state') != 1){
                $.hideAlert();
                $('.include:not(.card)').addClass('btn-default').removeClass('btn-success btn-danger');
                $('.include').attr('data-state', 1);
                $('.includeicon').empty().append('<i class="fa fa-bullseye" style="font-size : 6em;color:#94ca02;font-weight: normal;"></i>');
                $('.includeicon_text').css('color', '#94ca02').css('font-weight', 'normal');
                $('.include.card span center').text('{{Lancer Scan}}');
                $('.include.card').css('background-color','#ffffff');
            }
        }
    }
});

$('body').on('googlecast::includeDevice', function (_event,_options) {
    if (modifyWithoutSave) {
        $('#div_inclusionAlert').showAlert({message: '{{Un GoogleCast vient d\'être inclu/exclu. Veuillez réactualiser la page}}', level: 'warning'});
    } else {
        if (_options == '') {
            window.location.reload();
        } else {
            window.location.href = 'index.php?v=d&p=googlecast&m=googlecast&id=' + _options;
        }
    }
});

function changeIncludeState(_state,_mode,_type='') {
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // methode de transmission des données au fichier php
        url: "plugins/unifivideo/core/ajax/unifivideo.ajax.php", // url du fichier php
        data: {
            action: "changeIncludeState",
            state: _state,
            mode: _mode,
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
        }
    });
}

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.template
 */
function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="id" style="display:none;"></span>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}">';
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }

    tr += '</td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    if (isset(_cmd.type)) {
        $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
    }
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}