/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */


//? cron:
$('#bt_cronGenerator').on('click',function(){
    jeedom.getCronSelectModal({},function (result) {
        $('.eqLogicAttr[data-l1key=configuration][data-l2key=autorefresh]').value(result.value);
    });
});

//bouton pour accéder aux commandes infos équipement:
$("body").off('click','.listEquipementInfo').on( 'click','.listEquipementInfo', function () {
    var type = $(this).attr('data-type')
    var el = $(this).closest('.' + type).find('.expressionAttr[data-l1key=cmd]')
    jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function (result) {
        el.value(result.human)
        jeedom.cmd.displayActionOption(el.value(), '', function (html) {
            el.closest('.' + type).find('.logOptions').html(html)
            taAutosize()
        })
    })
})

//supprimer cmd ou scenar:
$("body").off('click', '.bt_removeAction').on( 'click', '.bt_removeAction',function () {
    var type = $(this).attr('data-type')
    $(this).closest('.' + type).remove()
})


$("#bt_addCmd").off('click').on( 'click',function () {
    addLog('', 'Cmd')
})

$("#bt_addScenario").off('click').on( 'click',function () {
    addLog('', 'Scenar')
})

$("#bt_addLogfile").off('click').on( 'click',function () {
    addLog('', 'Logfile')
})

//Import de commandes:
$('#bt_importinfos').on('click', function () {
    $('#md_modal').dialog({title: "{{Importation de commande infos}}"});
    $('#md_modal').load('index.php?v=d&plugin=jeelog&modal=infos.import&id=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
});

$('#bt_import').on('click', function ()
{
    $('#md_modal .log').each(function ()
    {
        if ($(this).find("#isEnable").prop('checked'))
        {
          name = $(this).find("#cmdName").val()
          addLog(name, 'Cmd')
        }
    });
    $('#md_modal').dialog("close")
});

//===========
function getScenariosList()
{
    LIST = []
    LIST.push([0,''])
    $.ajax({
        type: "POST",
        url: "core/ajax/scenario.ajax.php",
        data: {
            action: 'all',
            version: 'scenario'
        },
        dataType: 'json',
        async: ('function' == typeof(_callback)),
        global: false,
        error: function(request, status, error) {
            handleAjaxError(request, status, error)
        },
        success: function(data) {
            for(var i in data.result){
                LIST.push([data.result[i].id,data.result[i].humanName])
            }
            if (data.state != 'ok') {
                $('#div_alert').showAlert({
                    message: data.result,
                    level: 'danger'
                })
                return
            }
            if ('function' == typeof(_callback)) {
                _callback(html)
                return
            }
        }
    })
    return LIST
}

function getLogfilesList()
{
    LIST = []
    $.ajax({
        type: "POST",
        url: "plugins/jeelog/core/ajax/jeelog.ajax.php",
        data: {
            action: 'getLogFiles'
        },
        dataType: 'json',
        async: ('function' == typeof(_callback)),
        global: false,
        error: function(request, status, error) {
            handleAjaxError(request, status, error)
        },
        success: function(data) {
            LIST = data.result
            if (data.state != 'ok') {
                $('#div_alert').showAlert({
                    message: data.result,
                    level: 'danger'
                })
                return
            }
            if ('function' == typeof(_callback)) {
                _callback(html)
                return
            }
        }
    })
    return LIST
}

function addLog(_argName='', _type='Scenar', _CmdType=null, _displayName, _isEnable=true, _isInversed=false, _noRepeat=false, _fileLines=false)
{
    if (_type == 'Scenar') {
        button = 'btn-danger'
    }
    if (_type == 'Cmd') {
        button = 'btn-success'
    }

    var div = '<div class="' + _type + ' log col-sm-12" type="'+_type+'" style="padding-top:5px">'
    div += '<div class="form-group">'
    div += '<input type="checkbox" id="isEnable" class="expressionAttr col-sm-1" data-l1key="options" style="width:12px" title="{{Décocher pour desactiver le log}}" />'
    div += '<div class="col-sm-5">'

    if (_type == 'Logfile') {
            div += '<div class="input-group input-group-sm">'
            div += '<span class="input-group-btn">'
            div += '<a class="btn btn-default bt_removeAction btn-sm" data-type="' + _type + '"><i class="fa fa-minus-circle"></i></a>'
            div += '</span>'
            div += '<span class="input-group-addon">Fichier log</span>'


            div += '<select class="expressionAttr form-control input-sm" style="display:inline-block" id="argName">'
            for(var i in LOGFILES_LIST){
                div += '<option value="'+LOGFILES_LIST[i]+'">'+LOGFILES_LIST[i]+'</option>'
            }
            div += '</select>'

            div += '</div>'
            div += '</div>'

            div += '<div class="col-sm-2">'
            div += '<input type="text" class="form-control" id="fileLines" placeholder="{{0}}" title="Nombre de lignes, 0 pour le log complet."/>'
            div += '</div>'

            div += '<div class="col-sm-4">'
            div += '<span class="jqAlert alert-danger">'
                div += "Warning: Saving will keep only one log file, other entries will be DELETED."
            div += '</span>'

            div += '</div>'
    }

    if (_type == 'Scenar') {
            div += '<div class="input-group input-group-sm">'
            div += '<span class="input-group-btn">'
            div += '<a class="btn btn-default bt_removeAction btn-sm" data-type="' + _type + '"><i class="fa fa-minus-circle"></i></a>'
            div += '</span>'
            div += '<span class="input-group-addon">Scénario</span>'

            div += '<select class="expressionAttr form-control input-sm" style="display:inline-block" id="argName">'
            for(var i in SCENARS_LIST){
                div += '<option value="'+SCENARS_LIST[i][0]+'">'+SCENARS_LIST[i][1]+'</option>'
            }
            div += '</select>'

            div += '</div>'
            div += '</div>'

            div += '<div class="col-sm-2">'
            div += '<input type="text" class="form-control" id="displayName" placeholder="{{Nom}}" />'
            div += '</div>'
            div += '</div>'
    }

    if (_type == 'Cmd') {
            div += '<div class="input-group">'
            div += '<span class="input-group-btn">'
            div += '<a class="btn btn-default bt_removeAction btn-sm" data-type="' + _type + '"><i class="fa fa-minus-circle"></i></a>'
            div += '</span>'

            div += '<span class="input-group-addon">Info</span>'
            div += '<input class="expressionAttr form-control input-sm cmdAction" data-l1key="cmd" id="argName" data-type="' + _type + '" />'
            div += '<span class="input-group-btn">'
            div += '<a class="btn ' + button + ' btn-sm listEquipementInfo" data-type="' + _type + '"><i class="fa fa-list-alt"></i></a>'
            div += '</span>'

            div += '</div>'
            div += '</div>'

            div += '<div class="col-sm-2">'
            div += '<input type="text" class="form-control" id="displayName" placeholder="{{Nom}}" />'
            div += '</div>'

            div += '<select class="input-sm col-sm-2" style="display:inline-block" id="CmdType">'
            for(var i in CMD_TYPE){
                    div += '<option value="'+CMD_TYPE[i]+'">'+CMD_TYPE[i]+'</option>'
                }
            div += '</select>'

            div += '<div class="col-sm-3" style="width:100px; padding-right:0px">'
            div += '<input type="checkbox" id="isInversed" class="expressionAttr" data-l1key="options" />'
            div += 'Inverser'
            div += '</div>'
            div += '<div class="col-sm-2" style="width:140px; padding-right:0px">'
            div += '<input type="checkbox" id="noRepeat" class="expressionAttr" data-l1key="options" />'
            div += 'Ne pas répéter'
            div += '</div>'
    }

    div += '</div>'
    div += '</div>'

    //add it to UI:
    _el = $("#div_logs")
    _el.append(div)

    //set options:
    _el.find('.log:last').find("#isEnable").prop('checked', _isEnable)

    if (_type == 'Logfile') {
        if (_argName != "") _el.find('.log:last').find("#argName").val(_argName)
        if (_fileLines) _el.find('.log:last').find("#fileLines").val(_fileLines)
    }
    if (_type == 'Scenar') {
        if (_argName != "") _el.find('.log:last').find("#argName").val(_argName)
    }
    if (_type == 'Cmd') {
        if (_argName != "") _el.find('.log:last').find("#argName").val(_argName)
        if (_CmdType) _el.find('.log:last').find("#CmdType").val(_CmdType)
        if (_isInversed) _el.find('.log:last').find("#isInversed").prop('checked', _isInversed)
        if (_noRepeat) _el.find('.log:last').find("#noRepeat").prop('checked', _noRepeat)
    }
    if (_displayName != "") _el.find('.log:last').find("#displayName").val(_displayName)
}

function saveEqLogic(_eqLogic) {
    if (!isset(_eqLogic.configuration)) {
        _eqLogic.configuration = {}
    }

    _eqLogic.configuration.logs = []

    $('#div_logs .log').each(function () {
        log = {}
        log.type = $(this).attr('type')
        if (log.type == 'Cmd')
        {
            log.CmdType = $(this).find("#CmdType option:selected").text()
            log.argName = $(this).find("#argName").val()
            log.displayName = $(this).find("#displayName").val()
            log.isEnable =  $(this).find("#isEnable").prop('checked')
            log.isInversed =  $(this).find("#isInversed").prop('checked')
            log.noRepeat =  $(this).find("#noRepeat").prop('checked')
            if (log.argName != "") _eqLogic.configuration.logs.push(log)
        }
        if (log.type == 'Scenar')
        {
            delete log.CmdType
            log.argName = $(this).find("#argName option:selected").val()
            log.displayName = $(this).find("#displayName").val()
            log.isEnable =  $(this).find("#isEnable").prop('checked')
            if (log.argName != 0) _eqLogic.configuration.logs.push(log)
        }
        if (log.type == 'Logfile')
        {
            //Delete other commands, only one log file per jeelog !
            _eqLogic.configuration.logs = []
            log = {}
            log.type = 'Logfile'
            log.argName = $(this).find("#argName option:selected").val()
            log.fileLines = $(this).find("#fileLines").val()
            log.isEnable =  $(this).find("#isEnable").prop('checked')
            _eqLogic.configuration.logs.push(log)
            return _eqLogic
        }
    });
    return _eqLogic
}

function printEqLogic(_eqLogic) {
    //console.log(_eqLogic.configuration)

    $('#div_logs').empty()
    SCENARS_LIST = getScenariosList()
    LOGFILES_LIST = getLogfilesList()

    CMD_TYPE = []
    CMD_TYPE.push("Eteint | Allumé")
    CMD_TYPE.push("Fermeture | Ouverture")
    CMD_TYPE.push("Off | On")
    CMD_TYPE.push("Presence")
    CMD_TYPE.push("Valeur")

    for (var i in _eqLogic.configuration.logs) {
        _type = _eqLogic.configuration.logs[i].type
        _CmdType = _eqLogic.configuration.logs[i].CmdType
        _argName = _eqLogic.configuration.logs[i].argName
        _displayName = _eqLogic.configuration.logs[i].displayName
        _isEnable = _eqLogic.configuration.logs[i].isEnable
        _isInversed = _eqLogic.configuration.logs[i].isInversed
        _fileLines = _eqLogic.configuration.logs[i].fileLines
        try {
          _noRepeat = _eqLogic.configuration.logs[i].noRepeat
        }
        catch(error) {
          _noRepeat = false
        }
        addLog(_argName, _type, _CmdType, _displayName, _isEnable, _isInversed, _noRepeat, _fileLines)
    }

    $("#div_logs").sortable();
}
