document.getElementById('view').addEventListener('click', viewData);
var hari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
if (!firebase.auth().currentUser) {
    document.getElementById('private').disabled = true;
}

function viewData() {
    var tgl = document.getElementById('tgl').value;
    var d = new Date(tgl);
    var start = new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
    var end = new Date(d.getFullYear(), d.getMonth(), d.getDate() + 1).getTime();

    // console.log(isPrivate);

    var dataRef = firebase.database().ref();
    var monPKL = dataRef.child('mon_user').orderByChild('properties/time').startAt(start).endAt(end);
    var tempatPKL = dataRef.child('pkl');
    tempatPKL.on('value', gotDataTempat, showError);
    monPKL.on('value', gotData, showError);
    initMap();
}
firebase.auth().onAuthStateChanged(user => {
    if (user) {
        document.getElementById('client').innerHTML = user.displayName;
    }
});

var features = [];
var users = [];

var d = new Date();
var start = new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
var end = new Date(d.getFullYear(), d.getMonth(), d.getDate() + 1).getTime();

var dataRef = firebase.database().ref();
var monPKL = dataRef.child('mon_user').orderByChild('properties/time').startAt(start).endAt(end);
var tempatPKL = dataRef.child('pkl');
tempatPKL.on('value', gotDataTempat, showError);
monPKL.on('value', gotData, showError);

var geoJSON = {
    type: "FeatureCollection",
    features: features
};
// console.log(geoJSON.features[0]);

function gotDataTempat(data) {
    var markers = [];

    data.forEach(function(datamarker) {
        lat = parseFloat(datamarker.val().geometry.coordinates[1]);
        lng = parseFloat(datamarker.val().geometry.coordinates[0]);
        info = '<h3>' + datamarker.val().properties.instansi + '</h3><br>';
        info += datamarker.val().properties.alamat + '<br>';
        info += '<a target="_blank" href="https://www.google.com/maps/place/' + lat + '+' + lng + '/@' + lat + ',' + lng + ',15z"><img src="images/direction.png"></a><br>'
        info += 'Pembimbing : ' + datamarker.val().properties.pemb_lap;
        if (datamarker.val().properties.pemb_hp) {
            info += ' ' + sendWhatsapp(datamarker.val().properties.pemb_hp) +
                ' ' + call(datamarker.val().properties.pemb_hp) + '<br>';
        }
        info += 'Jumlah mhs : ' + datamarker.val().properties.mhs.length + '<br>';
        if (datamarker.val().properties.mhs[0].nama) {
            info += 'Contact mhs : ' + datamarker.val().properties.mhs[0].nama +
                ' ' + sendWhatsapp(datamarker.val().properties.hp_mhs) +
                ' ' + call(datamarker.val().properties.hp_mhs) + '<br>';
        }
        // console.log(datamarker.key);
        loc = {
            lat: lat,
            lng: lng
        };
        var img = 'images/office.png';
        markers.push(createMarker(loc, info, img));
    });
    // console.log(fitur[1].geometry);
    var markerCluster = new MarkerClusterer(map, markers, {
        imagePath: 'images/m'
    });
}

function gotData(data) {
    var markers = [];
    var fitur = [];
    var isPrivate = document.getElementById('private').checked;
    data.forEach(function(datamarker) {
        // console.log(datamarker.val().geometry, datamarker.val().properties);
        //panggil fungsi pushData disini
        if (datamarker.val().properties['npm'] === '1707051014')
            console.log(datamarker.key);
        if (isPrivate) {
            if (datamarker.val().properties['user']['email'] === currentUser.email)
                pushData(datamarker, fitur, markers);
        } else {
            pushData(datamarker, fitur, markers);
        }
    });
    // console.log(fitur[1].geometry);
    CreateTableFromJSON(fitur);
    console.log(JSON.stringify(users));
    var markerCluster = new MarkerClusterer(map, markers, {
        imagePath: 'images/m'
    });
}

function pushData(datamarker, fitur, markers) {
    fitur.push(datamarker.val());
    // console.log(datamarker.val().properties['user']['email']);
    mhslat = parseFloat(datamarker.val().geometry.coordinates[0][1]);
    mhslng = parseFloat(datamarker.val().geometry.coordinates[0][0]);
    inslat = parseFloat(datamarker.val().geometry.coordinates[1][1]);
    inslng = parseFloat(datamarker.val().geometry.coordinates[1][0]);

    userid = datamarker.val().properties.user.uid;
    usernama = datamarker.val().properties.nama;
    usernpm = datamarker.val().properties.npm;
    useremail = datamarker.val().properties.user.email;
    userinstansi = datamarker.val().properties.instansi;
    var imgURL = null;
    var catatan = null;
    if (datamarker.val().properties.catatan) {
        catatan = datamarker.val().properties.catatan;
    }
    if (datamarker.val().properties.imgURL) {
        imgURL = datamarker.val().properties.imgURL;
    }

    userdata = {};
    userdata[userid] = {
        nama: usernama,
        npm: usernpm,
        email: useremail,
        catatan: catatan,
        imgURL: imgURL,
        instansi: {
            nama: userinstansi,
            lat: inslat,
            lng: inslng
        }
    };
    users.push(userdata);
    // console.log(JSON.stringify(userdata));

    var a = new google.maps.LatLng(mhslat, mhslng);
    var b = new google.maps.LatLng(inslat, inslng);
    var jarak = google.maps.geometry.spherical.computeDistanceBetween(a, b).toFixed(2);
    let waktu = new Date(datamarker.val().properties.time);

    info = '<h5>' + datamarker.val().properties.nama.toUpperCase() + ' NPM ';
    info += datamarker.val().properties.npm + '</h5>';
    // info += '<a target="_blank" href="https://www.google.com/maps/place/' + mhslat + '+' + mhslng + '/@' + mhslat + ',' + mhslng + ',15z"><img src="images/direction.png"></a><br>'
    info += '<p class="font-weight-bold">' + datamarker.val().properties.instansi + '</p>';
    info += '<p class="font-weight-bold">' + hari[waktu.getDay()] + ', ' + waktu.getDate() + '/' + (waktu.getMonth() + 1) + '/' + waktu.getFullYear() + '</p>';
    info += '<h6> Keterangan: </h6>';
    info += '<ul><li>' + datamarker.val().properties.keterangan + '</li>';

    info += '<li>Jam : ' + waktu.getHours() + ':' + waktu.getMinutes() + ':' + waktu.getSeconds() + '</li>';
    // info += '<li>' + waktu + '</li>';
    info += '<li>Jarak : ' + jarak + '</li></ul>';

    // console.log(datamarker.key);
    loc = {
        lat: mhslat,
        lng: mhslng
    };

    if (datamarker.val().properties.user.photoURL) {
        var icon = {
            url: datamarker.val().properties.user.photoURL, // url
            scaledSize: new google.maps.Size(32, 32), // scaled size
            origin: new google.maps.Point(0, 0), // origin
            anchor: new google.maps.Point(0, 0) // anchor
        };
        markers.push(createMarker(loc, info, icon));
    } else {
        var img = 'images/placeholder.png';
        markers.push(createMarker(loc, info, img));
    }

    var mhsToOffice = [{
            lat: mhslat,
            lng: mhslng
        },
        {
            lat: inslat,
            lng: inslng
        }
    ];
    var mhsPath = new google.maps.Polyline({
        path: mhsToOffice,
        geodesic: true,
        strokeColor: '#FF0000',
        strokeOpacity: 1.0,
        strokeWeight: 0.5
    });

    mhsPath.setMap(map);
}

function CreateTableFromJSON(data_all) {
    var arrHead = new Array();
    arrHead = ['img', 'mahasiswa', 'lokasi', 'waktu', 'keterangan', 'catatan'];

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
        // console.log(tgl);
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
                var img = (data_all[i]['properties']['imgURL']) ?
                    data_all[i]['properties']['imgURL'] :
                    data_all[i]['properties']['user']['photoURL'];

                content = '<img src="' + img + '" width=80>';
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
            } else if ('catatan' == arrHead[j]) {
                content = data_all[i]['properties']['catatan'];
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
    var divContainer = document.getElementById("data-table");
    divContainer.innerHTML = "";
    divContainer.appendChild(table);
    jQuery(function($) {
        $('#table_1').DataTable({
            "pageLength": 50,
            "columns": [
                null,
                null,
                null,
                null,
                null,
                { "width": "15%" }
            ]
        });
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
    console.log(err);
    document.querySelector('.alert').style.display = 'block';
    document.getElementById("alert").innerHTML = "Gagal Menyimpan Data";
    document.querySelector('.alert').style.background = 'red';
    setTimeout(function() {
        document.querySelector('.alert').style.display = 'none';
    }, 3000);
}

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


//Map preparing
var map = null;
var center = {
    lat: -5.367284,
    lng: 105.244935
};

function initMap() {
    let cl = new CurrentLocation();
    cl.getLocation();
    // console.log(coords);
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 12
    });
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            initialLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
            map.setCenter(initialLocation);
            marker = createMarker(initialLocation, '<h3>My Location</h3><hr>');
            marker.setMap(map);
            setValue('lat', position.coords.latitude);
            setValue('lng', position.coords.longitude);
        });
    }
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

    //script dibawah ini ditutup jika menggunakan clustermarke
    // marker.setMap(map);
    return marker;
}

function setValue(id, val) {
    if (document.getElementById(id)) document.getElementById(id).value = val;
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

function leadingZero(number) {
    return number.toString().length == 1 ? '0' + number : number;
}