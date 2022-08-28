var jsonsrc = "";
var formatter;
var nosave = true;
var liveonly = true;
var wait = 3;
var domain = "_googlecast._tcp.local";

function setTableClick() {
    $('#devices').off('click');
    $('#devices').on('click', 'tr', function () {
        const device = jsonsrc[$(this)[0].id];
        //console.log("device clicked:",device);
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

function fillUpTable(json) {
    console.log(json);
    $('#devices').empty();

    var config = $('<thead><tr><th class="config collapsed" colspan="8" id="config">'+generateConfig()+'</th></tr></thead>');

    var header = $('<thead></thead>');
    header.append($('<th><a onclick="config();"><i style="font-size:12px" class="fa">&#xf0c9;</i></a>&nbsp;&nbsp;<a onclick="refreshJson();"><i style="font-size:12px" class="fa" id="refresh">&#xf021;</i></a></th>')); 
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
        var groupnb = 0;
        var dyngroupnb = 0;
        if (device.status && device.status.multizone) {
            groupnb = device.status.multizone.groups.length;
            dyngroupnb = device.status.multizone.dynamic_groups.length;
        }
        var row = $('<tr id="'+key+'">');
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
            row.append($(' <td>'+device.friendlyname+'</td>'));
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
        body.append(row);

        const controlRow = $('</tr><tr style="height: 0px"><td class="control collapsed" colspan="8" id="control-'+key+'">'+generateCtrl(key)+'</td></tr>');
        body.append(controlRow);
        const detailRow = $('</tr><tr style="height: 0px"><td class="details collapsed" colspan="8" id="details-'+key+'">loading...</td></tr>');
        body.append(detailRow);
    }
    $('#devices').append(config);
    $('#devices').append(header);
    $('#devices').append(body);
}

function refreshJson() {
    $('#refresh').addClass("fa-spin");
    var url = "update.php?domain="+domain+"&wait="+wait;
    if (nosave) {
        url += "&nosave=1";
    }
    if (liveonly) {
        url += "&live=1";
    }
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
    $('th.config').removeClass("collapsed");
}

function generateCtrl(key) {
    // TODO: add a delete from cache button
    // TODO: add single device refresh
    // TODO: allow manual set of IP address
    html = key+'&nbsp;<button onclick="refreshSingle(\''+key+'\')" type="button">refresh me</button>&nbsp;<button onclick="deleteDevice(\''+key+'\')" type="button">delete me</button>&nbsp;<button onclick="changeIP(\''+key+'\')" type="button">change IP</button>';
    return html;
}

function generateConfig() {
    html = 'CONFIG:&nbsp;&nbsp;&nbsp;';
    html += 'domain <input id="config_domain" type="text" value="'+domain+'"/>&nbsp;';
    html += 'wait <input id="config_wait" type="text" maxlength="4" size="4" value="'+wait+'"/>&nbsp;';
    html += 'live only <input id="config_liveonly" type="checkbox" '+(liveonly ? 'checked' : '')+'/>&nbsp;';
    html += 'do not save to cache <input id="config_nosave" type="checkbox" '+(nosave ? 'checked' : '')+'/>';
    html += '<button onclick="saveConfig();" type="button">save</button>'
    return html;
}

function refreshSingle(key) {
    console.log("refreshSingle "+key);
}

function deleteDevice(key) {
    console.log("deleteDevice "+key);
}

function changeIP(key) {
    console.log("changeIP "+key);
}

function saveConfig() {
    $('th.config').addClass("collapsed");
    domain = $('#config_domain').val();
    console.log("domain = "+domain);
    wait = $('#config_wait').val();
    console.log("wait = "+wait);
    nosave = $('#config_nosave').is(":checked");
    console.log("nosave = "+nosave);
    liveonly = $('#config_liveonly').is(":checked");
    console.log("live = "+liveonly);
}

$('document').ready(function() {
    refreshJson();
});
