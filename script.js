var jsonsrc = "";
var singlejsonsrc = "";
var formatter;

function setTableClick() {
    $('#devices').off('click');
    $('#devices').on('click', 'tr', function () {
        const device = jsonsrc[$(this)[0].id];
        console.log("device clicked:",device);
        if (!device) return; // click in details
        // details
        var sel = '#details-'+$(this)[0].id;
        sel = sel.replaceAll(".", "\\.");
        // controls
        var ctrl = '#control-'+$(this)[0].id;
        ctrl = ctrl.replaceAll(".", "\\.");

        if (!$(sel).hasClass("collapsed")) { // close
            $(sel).addClass("collapsed");
            $(ctrl).addClass("collapsed");
            return;
        }

        $('td.details').addClass("collapsed");
        $('td.control').addClass("collapsed");

        formatter = new JSONFormatter(device);
        document.querySelector(sel).innerHTML = "";
        document.querySelector(sel).appendChild(formatter.render());
        formatter.openAtDepth(2);

        $(sel).removeClass("collapsed");
        $(ctrl).removeClass("collapsed");
    });
}

function generateRowContent(row, device) {
    var groupnb = 0;
    var dyngroupnb = 0;
    if (device.status && device.status.multizone) {
        groupnb = device.status.multizone.groups.length;
        dyngroupnb = device.status.multizone.dynamic_groups.length;
    }
    if (device.live) {
        row.addClass("live");
    }
    if ((device.port == 8009)&&(device.status)&&(device.status.device_info)) { // full info available
        if (device.status.device_info.model_name == "Google Home Mini") {
            row.append($(' <td><span class="material-symbols-outlined">nest_mini</span></td>'));
        } else if (device.status.device_info.model_name == "Google Nest Hub") {
            row.append($(' <td><span class="material-symbols-outlined">nest_display</span></td>'));
        } else if (device.status.device_info.model_name == "Chromecast Audio") {
            row.append($(' <td><span class="material-symbols-outlined">speaker</span></td>'));
        } else {
            if (device.status.device_info.capabilities.display_supported) {
                row.append($(' <td><span class="material-symbols-outlined">tv_with_assistant</span></td>'));
            } else {
                row.append($(' <td><span class="material-symbols-outlined">home_speaker</span></td>'));
            }
        }
    } else if ((device.port == 8009)) {
        if (device.status) {
            // unknown
            row.append($(' <td><span class="material-symbols-outlined">question_mark</span></td>'));
        } else {
            // error
            row.append($(' <td><span class="material-symbols-outlined">signal_disconnected</span></td>'));
        }
    } else {
        // group
        row.append($(' <td><span class="material-symbols-outlined">speaker_group</span></td>'));
    }

    row.append($(' <td>'+device.friendlyname+'</td>'));
    row.append($(' <td>'+device.ip+'</td>'));
    row.append($(' <td>'+device.port+'</td>'));
    if ((device.port == 8009)&&(device.status)&&(device.status.device_info)) {
        row.append($(' <td>'+device.status.device_info.model_name+'</td>'));
        var wq = "";
        if (device.status.wifi.signal_level && device.status.wifi.noise_level) {
            wq = " ("+device.status.wifi.signal_level+"/"+device.status.wifi.noise_level+")";
        }
        row.append($(' <td>'+device.status.wifi.ssid+wq+'</td>'));
        row.append($(' <td>'+device.status.build_info.cast_build_revision+'</td>'));
        row.append($(' <td>'+groupnb+' / '+dyngroupnb+'</td>'));
    } else if ((device.port == 8009)&&(device.status)) {
        if (device.type) {
            row.append($(' <td>'+device.type+'</td>'));
        } else {
            row.append($(' <td>'+device.friendlyname+'</td>'));
        }
        var wq = "";
        if (device.status.signal_level && device.status.noise_level) {
            wq = " ("+device.status.signal_level+"/"+device.status.noise_level+")";
        }
        row.append($(' <td>'+device.status.ssid+wq+'</td>'));
        row.append($(' <td>'+device.status.cast_build_revision+'</td>'));
        row.append($(' <td>'+groupnb+' / '+dyngroupnb+'</td>'));
    } else {
        row.append($(' <td colspan=4></td>'));
    }
    return row;
}

function fillUpConfigTable() {
    $('#devices').empty();
    $('#devices').append($('<thead><tr><th class="config" colspan="8">UI configuration</th></tr></thead>'));
    $('#devices').append($('<thead><tr><th class="config" colspan="8" id="config_ui">'+generateConfig('ui')+'</th></tr></thead>'));
    $('#devices').append($('<thead><tr><th class="config" colspan="8">Metrics configuration</th></tr></thead>'));
    $('#devices').append($('<thead><tr><th class="config" colspan="8" id="config_metrics">'+generateConfig('metrics')+'</th></tr></thead>'));
    $('#devices').append($('<thead><tr><th class="config" colspan="8"><button onclick="saveAllConfigToFile();" type="button">save all and close</button></th></tr></thead>'));
}

function fillUpTable(json) {
    //console.log(json);
    $('#devices').empty();

    //var config = $('<thead><tr><th class="config collapsed" colspan="8" id="config">'+generateConfig()+'</th></tr></thead>');

    var header = $('<thead></thead>');
    header.append($('<th><a class="tooltip" onclick="config();" data-title="Edit the configuration"><i style="font-size:12px" class="fa">&#xf013;</i></a>&nbsp;&nbsp;<a class="tooltip" onclick="refreshJson();" data-title="Refresh all devices"><i style="font-size:12px" class="fa" id="refresh">&#xf021;</i></a></th>')); 
    header.append($('<th>Device</th>'));
    header.append($('<th>IP</th>'));
    header.append($('<th>Port</th>'));
    header.append($('<th>Type</th>'));
    header.append($('<th>WiFi</th>'));
    header.append($('<th>Version</th>'));
    header.append($('<th>Groups #</th>'));
    
    var body = $('<tbody></tobody>');
    var deviceIDs = Object.keys(json);
    for (let index = 0; index < deviceIDs.length; index++) {
        const key = deviceIDs[index];
        const device = json[key];
        var row = $('<tr id="'+key+'">');

        row = generateRowContent(row, device);
        body.append(row);

        const controlRow = $('</tr><tr style="height: 0px"><td class="control collapsed" colspan="8" id="control-'+key+'">'+generateCtrl(key)+'</td></tr>');
        body.append(controlRow);
        const detailRow = $('</tr><tr style="height: 0px"><td class="details collapsed" colspan="8" id="details-'+key+'">loading...</td></tr>');
        body.append(detailRow);
    }
    //$('#devices').append(config);
    $('#devices').append(header);
    $('#devices').append(body);
}

function updateUpTable(singleJson, key) {
    jsonsrc[key] = singleJson;
    rowsel = "#"+key;
    rowsel = rowsel.replaceAll(".", "\\.");
    //console.log("rowsel = ["+rowsel+"]");
    row = $(rowsel);
    //console.log("target row:",row);
    row.empty();
    generateRowContent(row, singleJson);
    setTableClick();
}

function refreshJson() {
    $('#refresh').addClass("fa-spin");
    var url = "update.php?" + generateUrlParams();
    var settings = {
        "url": url,
        "method": "GET",
        "timeout": 0,
    };
    
    $.ajax(settings).done(function (response) {
        jsonsrc = response;
        fillUpTable(response);
        setTableClick();
        $('i.fa').removeClass("fa-spin");
    });
}

function config() {
    document.location = "config.php";
    // if ($('th.config').hasClass("collapsed")) {
    //     $('th.config').removeClass("collapsed");
    // } else {
    //     $('th.config').addClass("collapsed");
    // }
}

function generateCtrl(key) {
    html = key;
    if (jsonsrc[key].port == 8009) {
        html += '&nbsp;<button onclick="refreshSingle(\''+key+'\')" type="button" title="refresh device info"><span class="material-symbols-outlined">refresh</span></button>';
        html += '&nbsp;<button onclick="deleteDevice(\''+key+'\')" type="button" title="delete device from cache"><span class="material-symbols-outlined">delete</span></button>';
        html += '&nbsp;<button onclick="changeIP(\''+key+'\')" type="button" title="change device IP address in cache"><span class="material-symbols-outlined">settings_ethernet</span></button>';
    }
    return html;
}

function generateConfig(scope) {
    html = '&nbsp;';
    html += 'domain <input id="'+scope+'_config_domain" type="text" value="'+eval(scope).domain+'"/>&nbsp;';
    html += 'wait <input id="'+scope+'_config_wait" type="text" maxlength="4" size="4" value="'+eval(scope).wait+'"/>&nbsp;';
    html += 'live only <input id="'+scope+'_config_liveonly" type="checkbox" '+(eval(scope).liveonly ? 'checked' : '')+'/>&nbsp;';
    html += 'do not save to cache <input id="'+scope+'_config_nosave" type="checkbox" '+(eval(scope).nosave ? 'checked' : '')+'/>';
    html += '<button onclick="saveScopeConfig(\''+scope+'\');" type="button">save for '+scope+'</button>'
    return html;
}

function refreshSingle(key) {
    console.log("refreshSingle "+key);
    $('#refresh').addClass("fa-spin");

    var url = "update.php?single="+key;
    if (nosave) {
        url += "&nosave=1";
    }
    var settings = {
        "context": key,
        "url": url,
        "method": "GET",
        "timeout": 0,
    };
    
    $.ajax(settings).done(function (response) {
        //jsonsrc = response;
        updateUpTable(response, this);
        $('i.fa').removeClass("fa-spin");
    });
}

function deleteDevice(key) {
    console.log("deleteDevice "+key);
    $('#refresh').addClass("fa-spin");

    var url = "deleteme.php?key="+key;
    var settings = {
        "context": key,
        "url": url,
        "method": "GET",
        "timeout": 0,
    };
    
    $.ajax(settings).done(function (response) {
        //jsonsrc = response;
        refreshJson();
        //$('i.fa').removeClass("fa-spin");
    });
}

function changeIP(key) {
    console.log("changeIP "+key);
    newIP = prompt("Enter new IP address", jsonsrc[key].ip);
    //console.log(newIP);
    $('#refresh').addClass("fa-spin");

    var url = "changemyip.php?key="+key+"&ip="+newIP;
    var settings = {
        "context": key,
        "url": url,
        "method": "GET",
        "timeout": 0,
    };
    
    $.ajax(settings).done(function (response) {
        //jsonsrc = response;
        refreshSingle(this);
        //$('i.fa').removeClass("fa-spin");
    });
}

function saveScopeConfig(scope) { // TODO: update me for full page config
    //$('th.config').addClass("collapsed");
    domain = $('#'+scope+'_config_domain').val();
    console.log("domain = "+domain);
    wait = $('#'+scope+'_config_wait').val();
    console.log("wait = "+wait);
    nosave = $('#'+scope+'_config_nosave').is(":checked");
    console.log("nosave = "+nosave);
    liveonly = $('#'+scope+'_config_liveonly').is(":checked");
    console.log("live = "+liveonly);
    saveConfigToFile(scope);
}

function saveAllConfigToFile() {

    var url = "saveconfig.php?full=" + generateJsonParams();
    var settings = {
        "url": url,
        "method": "GET",
        "timeout": 0,
    };
    
    $.ajax(settings).done(function (response) {
        console.log(response);
        document.location = ".";
    });
}

function saveConfigToFile(scope) {
    var url = "saveconfig.php?" + generateUrlParams(scope);
    var settings = {
        "url": url,
        "method": "GET",
        "timeout": 0,
    };
    
    $.ajax(settings).done(function (response) {
        console.log(response);
        //document.location = ".";
    });
}

function generateJsonParams() { // TODO: make it generic
    var obj = {};
    obj.ui = {};
    obj.ui.domain = $('#ui_config_domain').val();
    obj.ui.nosave = $('#ui_config_nosave').is(":checked");
    obj.ui.live = $('#ui_config_liveonly').is(":checked");
    obj.ui.wait = $('#ui_config_wait').val()*1000;
    obj.metrics = {};
    obj.metrics.domain = $('#metrics_config_domain').val();
    obj.metrics.nosave = $('#metrics_config_nosave').is(":checked");
    obj.metrics.live = $('#metrics_config_liveonly').is(":checked");
    obj.metrics.wait = $('#metrics_config_wait').val()*1000;
    return encodeURI(JSON.stringify(obj));
}

function generateUrlParams(scope) {
    var params = "scope="+scope+"&domain="+domain+"&wait="+wait;
    if (nosave) {
        params += "&nosave=1";
    }
    if (liveonly) {
        params += "&live=1";
    }
    return params;
}
