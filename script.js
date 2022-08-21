var jsonsrc = "";
var formatter;

function setTableClick() {
    $('#devices').off('click');
    $('#devices').on('click', 'tr', function () {
        const device = jsonsrc[$(this)[0].id];
        //console.log("device clicked:",device);
        if (!device) return; // click in details
        var sel = '#details-'+$(this)[0].id;
        sel = sel.replaceAll(".", "\\.");
        if (!$(sel).hasClass("collapsed")) { // close
            $(sel).addClass("collapsed");
            return;
        }
        formatter = new JSONFormatter(device);
        $('td.details').addClass("collapsed");
        document.querySelector(sel).innerHTML = "";
        document.querySelector(sel).appendChild(formatter.render());
        formatter.openAtDepth(2);
        $(sel).removeClass("collapsed");
    });
}

function fillUpTable(json) {
    console.log(json);
    $('#devices').empty();

    var header = $('<thead><tr></tr></thead>');
    header.append($('<th><a onclick="refreshJson();"><i style="font-size:12px" class="fa">&#xf021;</i></a></th>')); // TODO: add a loading state
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
            // unknown
            row.append($(' <td><span class="material-symbols-outlined">question_mark</span></td>'));
        } else {
            // group
            row.append($(' <td><span class="material-symbols-outlined">speaker_group</span></td>'));
        }

        // TODO: add a delete from cache button
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
            row.append($(' <td>'+groupnb+'/'+dyngroupnb+'</td>'));
        } else if ((device.port == 8009)&&(device.status)) {
            row.append($(' <td>'+device.friendlyname+'</td>'));
            var wq = "";
            if (device.status.signal_level && device.status.noise_level) {
                wq = " ("+device.status.signal_level+"/"+device.status.noise_level+")";
            }
            row.append($(' <td>'+device.status.ssid+wq+'</td>'));
            row.append($(' <td>'+device.status.cast_build_revision+'</td>'));
            row.append($(' <td>'+groupnb+'/'+dyngroupnb+'</td>'));
        } else {
            row.append($(' <td colspan=4></td>'));
        }
        body.append(row);
        // TODO: add a command row per device (check possible ones)
        // TODO: add single device refresh
        // TODO: allow manual set of IP address
        // TODO: add device remove
        const detailRow = $('</tr><tr style="height: 0px"><td class="details collapsed" colspan="8" id="details-'+key+'">loading...</td></tr>');
        body.append(detailRow);
    }
    $('#devices').append(header);
    $('#devices').append(body);
}

function refreshJson() {
    $('i.fa').addClass("fa-spin");
    var settings = {
        "url": "update.php",
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

$('document').ready(function() {
    refreshJson();
});
