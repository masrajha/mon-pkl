// console.log(new Date().getTime());
var currentUser = null;
var currentMarker = null;
firebase.auth().onAuthStateChanged(user => {
    if (user) {
        app(user);
        getUserInfo(user);
        if (document.getElementById('save')) {
            // document.getElementById('save').disabled = true;
            document.getElementById('save').addEventListener('click', doSave);
            // document.getElementById('catatan').disabled = true;
        }
        currentUser = user;
        if (currentUser.photoURL) {
            var icon = {
                url: currentUser.photoURL, // url
                scaledSize: new google.maps.Size(32, 32), // scaled size
                origin: new google.maps.Point(0, 0), // origin
                anchor: new google.maps.Point(0, 0) // anchor
            };
            currentMarker.setIcon(icon);
        }
    } else {
        document.getElementById('instansi').disabled = true;
        document.getElementById('npm').disabled = true;
        document.getElementById('save').disabled = true;
        // console.log("test");
        // document.getElementById('save').value = "true";
    }
});

var markerRef = firebase.database().ref('pkl');

function getUserInfo(user) {
    var monRef = firebase.database().ref('users/' + user.uid);
    console.log(monRef.toString());
    monRef.on('value', function(data) {
        setValue('npm', data.val().npm);
        setValue('nama', data.val().nama);
        setValue('instansi', data.val().instansi.nama);
        setValue('lat_instansi', data.val().instansi.lat);
        setValue('lng_instansi', data.val().instansi.lng);
        document.getElementById('instansi').disabled = ((document.getElementById('npm').value).length != 10);
        setButtonLabel();
    });

}

function doSave() {
    var dataRef = firebase.database().ref('mon_pkl');
    var monUserRef = firebase.database().ref('mon_user');
    var coordinates = [
        [parseFloat(document.getElementById('lng').value), parseFloat(document.getElementById('lat').value)],
        [parseFloat(document.getElementById('lng_instansi').value), parseFloat(document.getElementById('lat_instansi').value)]
    ];
    var type = "LineString";
    var geometry = {
        type: type,
        coordinates: coordinates
    };

    var info = getDeviceInfo();
    var properties = {};
    properties.keterangan = document.getElementById('save').value;
    properties.npm = document.getElementById('npm').value;
    properties.nama = document.getElementById('nama').value;
    properties.instansi = document.getElementById('instansi').value;
    properties.time = new Date().getTime();
    properties.device = info;
    properties.url = window.location.href;
    properties.catatan = document.getElementById('catatan').value;
    properties.imgURL = document.getElementById('imgURL').value;

    var user = {
        displayName: currentUser.displayName,
        email: currentUser.email,
        uid: currentUser.uid,
        photoURL: currentUser.photoURL
    };
    properties.user = user;
    // console.log(geometry, properties);
    var path = monUserRef.child(properties.npm);
    saveData(dataRef, geometry, properties);
    saveData(path, geometry, properties);
}

function saveData(dataRef, geometry, properties) {
    // if (info && imageIcon) info.imageIcon = imageIcon;
    var newdataRef = dataRef.push();
    data = {
        type: 'Feature',
        geometry: geometry,
        properties: properties
    };
    // console.log(JSON.stringify(data));
    newdataRef.set(data,
        function(error) {
            if (error) {
                // console.log(error);

                document.getElementById('msg').style.background = '#D83E50';
                document.getElementById('msg').style.color = '#ffffff';
                document.getElementById('msg').innerHTML = "Gagal Menyimpan Data";
                document.getElementById('msg').style.display = 'block';
                setTimeout(function() {
                    document.getElementById('msg').style.display = 'none';
                }, 5000);
                // window.location.href='monitoringmap.html';
            } else {
                document.getElementById('save').disabled = true;
                document.getElementById('msg').style.background = '#79c879';
                document.getElementById('msg').style.color = '#ffffff';
                document.getElementById('msg').innerHTML = "Data Berhasil Disimpan";
                document.getElementById('msg').style.display = 'block';
                setTimeout(function() {
                    document.getElementById('msg').style.display = 'none';
                }, 5000);
                window.location.href = 'index.html';
            }
        });
    // console.log(newMarkerRef);
}

function setButtonLabel() {

    var d = new Date(); // for now
    var time = d.getHours() * 10000 + d.getMinutes() * 100 + d.getSeconds();
    var lng = document.getElementById('lng').value;
    var lat = document.getElementById('lat').value;
    var imgURL = document.getElementById('imgURL').value;
    var nama = document.getElementById('nama').value;
    var catatan = document.getElementById('catatan').value;
    document.getElementById('save').disabled = (isNaN(parseFloat(lng)) && isNaN(parseFloat(lat))) || imgURL.length <= 0 || nama.length <= 0 || catatan.length < 50;
    console.log(document.getElementById('save').disabled);
    var msgText = null;
    if (document.getElementById('save').disabled) {
        if (isNaN(parseFloat(document.getElementById('lng').value))) {
            msgText = "GPS Tidak Aktif";
        } else if (imgURL.length <= 0) {
            msgText = "Belum ambil foto kamera";
        } else if (nama.length <= 0) {
            msgText = "Belum Login";
        } else if (catatan.length < 50) {
            msgText = "Catatan harian minimal 50 karakter";
        } else {
            msgText = "Bukan Jam Kerja";
        }
        document.getElementById('save').value = "Tidak Aktif";
        document.getElementById('msg').style.background = '#D83E50';
        document.getElementById('msg').style.color = '#ffffff';
        document.getElementById('msg').innerHTML = "Error: " + msgText;
        document.getElementById('msg').style.display = 'block';
    } else {
        document.getElementById('msg').style.display = 'none';
    }

    var lbl_catatan = "<p>Minggu ini adalah untuk Bab III, yang harus diperoleh mencakup:<br /><strong>A. Deskripsi kegiatan</strong><br />" +
        "Kegiatan apa yang akan dilakukan sebagai bahan untuk mengajukan solusi alternatif setelah mengamati dan menemukan adanya " +
        "permasalahan yang dihadapi pada proses bisnis perusahaan/instansi yang sedang berjalan<br /><strong>" +
        "B. Sumber data</strong><br />Bagian ini merupakan bagian paling penting, yaitu mengumpulkan sumber-sumber" +
        " data (misal: Formulir, SOP, Raw Data, Daftar Tabel, hasil wawancara, hasil observasi, dsb) yang digunakan untuk mengajukan solusi alternatif.<br /><strong>C. Metode pengumpulan data</strong>" +
        " <br /><strong>D. Metode penyelesaian masalah atau metode untuk mengerjakan solusi alternatif</strong> " +
        "<br />Mendeskripsikan bagaimana tahap-tahap umum solusi alternatif dalam rangka penyelesaian masalah.</p>" +
        "<br><b>RENCANA</b> Hari ini:";

    if (time < 70000) {
        document.getElementById('save').disabled = true;
    } else if (time < 80000) {
        document.getElementById('save').value = "Masuk";
        document.getElementById('lbl-catatan').innerHTML = lbl_catatan;
    } else if (time < 120000) {
        document.getElementById('save').value = "Datang Terlambat";
        document.getElementById('lbl-catatan').innerHTML = lbl_catatan;
    } else if (time < 160000) {
        document.getElementById('save').value = "Pulang Cepat";
        document.getElementById('lbl-catatan').innerHTML = "<b>[REALITA]</b> Yang saya lakukan hari ini sbb:\n";
    } else if (time < 190000) {
        document.getElementById('save').value = "Pulang";
        document.getElementById('lbl-catatan').innerHTML = "<b>[REALITA]</b> Yang saya lakukan hari ini sbb:\n";
    } else { document.getElementById('save').disabled = true; }
}
var features = [];
markerRef.on('value', getData, showError);
var geoJSON = {
    type: "FeatureCollection",
    features: features
};
// console.log(geoJSON.features[0]);
var dataInstansi = [];

function getData(data) {
    var markers = [];
    var fitur = [];
    data.forEach(function(datamarker) {
        // console.log(datamarker.val().geometry, datamarker.val().properties);
        fitur.push(datamarker.val());
        // console.log(datamarker.val());
        lat = parseFloat(datamarker.val().geometry.coordinates[1]);
        lng = parseFloat(datamarker.val().geometry.coordinates[0]);
        dataInstansi.push({ nama: datamarker.val().properties.instansi, lat: lat, lng: lng });

        info = '<h3>' + datamarker.val().properties.instansi + '</h3><br>';
        info += datamarker.val().properties.alamat + '<br>';
        info += '<a target="_blank" href="https://www.google.com/maps/place/' + lat + '+' + lng + '/@' + lat + ',' + lng + ',15z"><img src="images/direction.png"></a><br>'
        info += 'Pembimbing : ' + datamarker.val().properties.pemb_lap;
        if (datamarker.val().properties.pemb_hp) {
            info += ' ' + sendWhatsapp(datamarker.val().properties.pemb_hp) +
                ' ' + call(datamarker.val().properties.pemb_hp) + '<br>';
        }
        if (datamarker.val().properties.mhs) {
            info += 'Jumlah mhs : ' + datamarker.val().properties.mhs.length + '<br>';
            if (datamarker.val().properties.mhs[0].nama) {
                info += 'Contact mhs : ' + datamarker.val().properties.mhs[0].nama +
                    ' ' + sendWhatsapp(datamarker.val().properties.hp_mhs) +
                    ' ' + call(datamarker.val().properties.hp_mhs) + '<br>';
            }
        }
        // console.log(datamarker.key);
        loc = {
            lat: lat,
            lng: lng
        };
        var img = 'images/placeholder.png';
        if (datamarker.val().properties.visited == 1)
            img = 'images/placeholder-visited.png';
        markers.push(createMarker(loc, info, img));
    });
    console.log(fitur);
    // var marker= data.val();
    // var keys = Object.keys(data.val());
    // for (var i=0;i<keys.length;i++){
    //   var k = keys[i];
    //   // console.log(parseFloat(marker[k].coord.lat));
    //   lat = parseFloat(marker[k].coord.lat);
    //   lng = parseFloat(marker[k].coord.lng);
    //   loc = {lat:lat,lng:lng};
    //   // console.log(loc);
    //   markers.push(createMarker(loc,marker[k].info,'images/placeholder.png'));
    // }
    var instansi = document.getElementById('instansi');
    // console.log(dataInstansi);
    dataInstansi.sort(function(a, b) {
        if (a.nama < b.nama) { return -1; } else if (a.nama < b.nama) {
            return 1;
        } else return 0;

    });
    console.log(dataInstansi);
    dataInstansi.forEach(function(element) {
        var opt = document.createElement('option');
        opt.innerHTML = opt.value = element.nama;
        instansi.appendChild(opt);
    });
    var markerCluster = new MarkerClusterer(map, markers, {
        imagePath: 'images/m'
    });
}

function npmInput(val) {
    document.getElementById('instansi').disabled = (val.length != 10);
    console.log(val.length);
}

function catatanInput(val) {
    // if (val.length >= 50)
    setButtonLabel();
}

function instansiChange() {
    var instansi = document.getElementById('instansi');
    if (instansi.selectedIndex > 0) {
        console.log(dataInstansi[instansi.selectedIndex - 1]);
        setValue('lat_instansi', dataInstansi[instansi.selectedIndex - 1].lat);
        setValue('lng_instansi', dataInstansi[instansi.selectedIndex - 1].lng);
        setButtonLabel();
    } else {
        setValue('lat_instansi', null);
        setValue('lng_instansi', null);
        document.getElementById('save').disabled = true;
    }
}

function showError(err) {
    document.querySelector('.alert').style.display = 'block';
    document.getElementById("alert").innerHTML = "Gagal Menyimpan Data";
    document.querySelector('.alert').style.background = 'red';
    setTimeout(function() {
        document.querySelector('.alert').style.display = 'none';
    }, 3000);
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
        zoom: 15
    });
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            initialLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
            map.setCenter(initialLocation);
            if (currentUser) {
                currentMarker = createMarker(initialLocation, '<h3>My Location</h3><hr>', currentUser.photoURL);
            } else {
                currentMarker = createMarker(initialLocation, '<h3>My Location</h3><hr>');
            }
            currentMarker.setMap(map);
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