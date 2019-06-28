var markerRef = firebase.database().ref('mon_pkl');
var data_all = [];

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
markerRef.once('value', getData, showError);

function getData(data) {
    // var data_all = [];
    data.forEach(function(datamarker) {
        data_all.push(datamarker.val());
        if ('trioadhitya876@gmail.com'==datamarker.val().properties.user.email)
            console.log(datamarker.key);
    });

    console.log(data_all);
    CreateTableFromJSON();
}

function leadingZero(number) {
    return number.toString().length == 1 ? '0' + number : number;
}

function CreateTableFromJSON() {

    var arrHead = new Array();
    arrHead = ['img', 'mahasiswa', 'lokasi', 'waktu', 'keterangan', 'map'];

    // CREATE DYNAMIC TABLE.
    var table = document.createElement("table");
    // table.setAttribute('class', 'table table-striped table-hover');
    table.setAttribute('id', 'table_1');
    table.setAttribute('class', 'display');

    // CREATE HTML TABLE HEADER ROW USING THE EXTRACTED HEADERS ABOVE.
    // ADD JSON DATA TO THE TABLE AS ROWS.
    for (var i = 0; i < data_all.length; i++) {
        tr = table.insertRow(-1);
        var date = new Date(data_all[i]['properties']['time']);
        var bln = leadingZero(date.getMonth() + 1);
        var tgl = leadingZero(date.getDate());
        console.log(tgl);
        var mhsLatlng = {
            lng: data_all[i]['geometry']['coordinates'][0][0].toFixed(5),
            lat: data_all[i]['geometry']['coordinates'][0][1].toFixed(5)
        };
        var instansiLatlng = {
            lng: data_all[i]['geometry']['coordinates'][1][0].toFixed(5),
            lat: data_all[i]['geometry']['coordinates'][1][1].toFixed(5)
        };
        var a = new google.maps.LatLng(mhsLatlng.lat, mhsLatlng.lng);
        var b = new google.maps.LatLng(instansiLatlng.lat, instansiLatlng.lng);
        var jarak = google.maps.geometry.spherical.computeDistanceBetween(a, b).toFixed(2);

        for (var j = 0; j < arrHead.length; j++) {
            var tabCell = tr.insertCell(-1);
            if (j == 0) {
                content = '<img src="' + data_all[i]['properties']['user']['photoURL'] + '" width=50 height=50>';
                tabCell.innerHTML = content;
            } else if (arrHead[j] == 'mahasiswa') {
                let content = '';
                content += data_all[i]['properties']['nama'] + '<br>';
                content += data_all[i]['properties']['npm'] + '<br>';
                content += 'Lat: ' + mhsLatlng.lat + ' Lng: ' + mhsLatlng.lng;
                tabCell.innerHTML = content;
            } else if (arrHead[j] == 'lokasi') {
                let content = ' <p class="font-weight-bold">' + data_all[i]['properties']['instansi'] + '</p>';
                content += 'Lat: ' + instansiLatlng.lat + ' Lng: ' + instansiLatlng.lng;
                tabCell.innerHTML = content;
            } else if (arrHead[j] == 'waktu') {
                let content = '<p class="">' + date.getFullYear() + '-' + bln + '-' + tgl + ' ' +
                    leadingZero(date.getHours()) + ':' + leadingZero(date.getMinutes()) + ':' +
                    leadingZero(date.getSeconds()) + '</p>';
                tabCell.innerHTML = content;
            } else if (arrHead[j] == 'keterangan') {
                let content = data_all[i]['properties']['keterangan'] + '<br>';
                content += 'Jarak : ' + jarak + ' m';
                tabCell.innerHTML = content;
            } else if (arrHead[j] == 'map ') {
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
    jQuery(function($) {
        $('#table_1').DataTable(
            {
                "pageLength": 50
            }
        );
        // $('#table_1').DataTable({
        //     "columnDefs": [{
        //         "width": "50%",
        //         "targets": 1
        //     }]
        // });
        // console.log("coba");
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