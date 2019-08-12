var markerRef = firebase.database().ref('pkl');
var data_all = new Array();

function saveMarker(coord, info = null, imageIcon = null) {
    if (info && imageIcon) info.imageIcon = imageIcon;
    var newMarkerRef = markerRef.push();
    newMarkerRef.set({
        type: 'Feature',
        geometry: {
            coordinates: coord,
            type: 'Point'
        },
        properties: info
    });
    // console.log(newMarkerRef);
}
markerRef.on('value', getData, showError);

function getData(data) {
    var markers = [];

    data.forEach(function(datamarker) {
        data_all.push(datamarker.val());
    });

    console.log(data_all);
    CreateTableFromJSON();
}

function CreateTableFromJSON() {

    var arrHead = new Array();
    arrHead = ['no', 'instansi', 'mhs', 'kota', 'map'];

    // CREATE DYNAMIC TABLE.
    var table = document.createElement("table");
    // table.setAttribute('class', 'table table-striped table-hover');
    table.setAttribute('id', 'table_1');
    table.setAttribute('class', 'display');

    // CREATE HTML TABLE HEADER ROW USING THE EXTRACTED HEADERS ABOVE.
    // ADD JSON DATA TO THE TABLE AS ROWS.
    for (var i = 0; i < data_all.length; i++) {
        tr = table.insertRow(-1);
        for (var j = 0; j < arrHead.length; j++) {
            var tabCell = tr.insertCell(-1);
            if (j == 0) {
                tabCell.innerHTML = (i + 1);
            } else if (arrHead[j] == 'mhs') {
                let content = '<ol>';
                let mhs = data_all[i]['properties']['mhs'];
                let hp_mhs = data_all[i]['properties']['hp_mhs'];
                console.log(mhs);
                mhs.forEach((element, index) => {
                    if (index == 0) {
                        content += '<li>' + element.nama + '<br>' + sendWhatsapp(hp_mhs) +
                            ' ' + call(hp_mhs) + '</li>';
                    } else { content += '<li>' + element.nama + '</li>'; }
                });
                content += '</ol>';
                content += '<p>';
                tabCell.innerHTML = content;
            } else if (arrHead[j] == 'instansi') {
                let pemb_hp = data_all[i]['properties']['pemb_hp'];
                let content = ' <p class="font-weight-bold">' + data_all[i]['properties']['instansi'] + '</p>';
                content += ' <span class="font-italic">' + data_all[i]['properties']['alamat'] + '<br>';
                content += ' Pembimbing: ' + data_all[i]['properties']['pemb_lap'] + '</span>';
                if (pemb_hp) {
                    content += ' ' + sendWhatsapp(pemb_hp) +
                        ' ' + call(pemb_hp);
                }
                tabCell.innerHTML = content;
            } else if (arrHead[j] == 'kota') {
                let content = '<p class="font-italic">' + data_all[i]['properties']['kota'] + '</p>';
                tabCell.innerHTML = content;
            } else if (arrHead[j] == 'map') {
                let lng = data_all[i]['geometry']['coordinates'][0];
                let lat = data_all[i]['geometry']['coordinates'][1];
                let content = '<a target="_blank" href="https://www.google.com/maps/place/' + lat + '+' + lng + '/@' + lat + ',' + lng + ',15z">';
                content += '<img src="images/directions-md.png"></a>';
                tabCell.innerHTML = content;
            }
        }
    }

    var thead = document.createElement("thead");
    table.appendChild(thead);
    var trhead = table.insertRow(-1); // TABLE ROW.
    thead.appendChild(trhead);
    for (var i = 0; i < arrHead.length; i++) {
        var th = document.createElement("th"); // TABLE HEADER.
        th.innerHTML = arrHead[i].toUpperCase();
        trhead.appendChild(th);
    }
    // FINALLY ADD THE NEWLY CREATED TABLE WITH JSON DATA TO A CONTAINER.
    var divContainer = document.getElementById("showData");
    divContainer.innerHTML = "";
    divContainer.appendChild(table);
    var opts = {
        "columnDefs": [
            { "width": "50%", "targets": 1 }
        ],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        fixedColumns: true
    };
    jQuery(function($) {
        $('#table_1').DataTable(opts);
        console.log(opts);
    });
}

function showError(err) {
    document.querySelector('.alert').style.display = 'block';
    document.getElementById("alert").innerHTML = "Gagal Menyimpan Data";
    document.querySelector('.alert').style.background = 'red';
    setTimeout(function() {
        document.querySelector('.alert').style.display = 'none';
    }, 3000);
}

function createMarker(coords, contentString = null, imageIcon = null) {
    // var marker=new google.maps.Marker({position:coords,map:map});
    var marker = new google.maps.Marker({
        position: coords,
        icon: imageIcon
    });
    if (contentString) {
        var infowindow = new google.maps.InfoWindow();
        infowindow.setContent(contentString);
        marker.addListener('click', function() {
            infowindow.open(map, marker);
        });
    }
    // console.log(marker);
    return marker;
}

function setValue(id, val) {
    document.getElementById(id).value = val;
}

function setKabOption() {
    var kota = ["Bandar Lampung",
        "Asahan",
        "Jakarta Barat",
        "Jakarta Selatan",
        "Jakarta Utara",
        "Kotabumi",
        "Lampung Selatan",
        "Lampung Tengah",
        "Metro",
        "Painan",
        "Palembang",
        "Pesawaran",
        "Prabumulih",
        "Pringsewu",
        "Tanggamus",
        "Tulang Bawang Barat"
    ];
    select = document.getElementById('kota');

    for (var i = 0; i < kota.length; i++) {
        var opt = document.createElement('option');
        opt.value = kota[i];
        opt.innerHTML = kota[i];
        select.appendChild(opt);
    }
}

function sendWhatsapp(number) {
    var img = '<img src="images/whatsapp.png">';
    if (number.substring(0, 1) == '0')
        return '<a href=https://api.whatsapp.com/send?phone=62' + number.substring(1) + '>' + img + '</a>';
    if (number.substring(0, 1) == '8')
        return '<a href=https://api.whatsapp.com/send?phone=62' + number.substring(0) + '>' + img + '</a>';
    if (number.substring(0, 1) == '+')
        return '<a href=https://api.whatsapp.com/send?phone=' + number.substring(1) + '>' + img + '</a>';
    return '<a href=https://api.whatsapp.com/send?phone=' + number.substring(0) + '>' + img + '</a>';
}

function call(number) {
    var img = '<img src="images/call.png">';
    if (number.substring(0, 1) == '0')
        return '<a href=tel:+62' + number.substring(1) + '>' + img + '</a>';
    if (number.substring(0, 1) == '8')
        return '<a href=tel:+62' + number.substring(0) + '>' + img + '</a>';
    if (number.substring(0, 1) == '+')
        return '<a href=tel:' + number.substring(0) + '>' + img + '</a>';
    return '<a href=tel:' + number.substring(0) + '>' + img + '</a>';
}