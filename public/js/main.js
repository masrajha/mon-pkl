var markerRef = firebase.database().ref('pkl');
var features = [];
markerRef.on('value', getData, showError);
var geoJSON = {
    type: "FeatureCollection",
    fatures: features
};
console.log(geoJSON);

function getData(data) {
    var markers = [];
    data.forEach(function(datamarker) {
        // console.log(datamarker.val().geometry, datamarker.val().properties);
        features.push(datamarker.val());
        // console.log(datamarker.val());
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
        var img = 'images/placeholder.png';
        if (datamarker.val().properties.visited == 1)
            img = 'images/placeholder-visited.png';
        markers.push(createMarker(loc, info, img));
    });
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
    var markerCluster = new MarkerClusterer(map, markers, {
        imagePath: 'images/m'
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


//Map preparing
var map = null;
var center = {
    lat: -5.367284,
    lng: 105.244935
};

function initMap() {
    document.getElementById("save").disabled = true;
    setKabOption();
    let cl = new CurrentLocation();
    cl.getLocation();
    // console.log(coords);
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 17
    });
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            initialLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
            map.setCenter(initialLocation);
            marker = createMarker(initialLocation, '<h3>My Location</h3><hr>');
            marker.setMap(map);
        });
    }
    // console.log(map);
    // marker = createMarker(center, '<h3>Jurusan Ilmu Komputer</h3><hr>' +
    //     '<p><a href="http://ilkom.unila.ac.id" target="blank">http://ilkom.unila.ac.id</a>');
    // marker.setMap(map);
    google.maps.event.addListener(map, "click", function(e) {
        // //lat and lng is available in e object
        var latLng = e.latLng;
        marker.setPosition(latLng);
        // // console.log(marker.getPosition().lat());
        setValue('lat', latLng.lat());
        setValue('lng', latLng.lng());
        document.getElementById("save").disabled = false;
        // createMarker(e.latLng);
        // coord={lat:latLng.lat(),lng:latLng.lng()};
        // saveData(coord);
    });
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

document.getElementById("save").addEventListener("click", function() {
    // console.log("save click");
    lat = parseFloat(document.getElementById('lat').value);
    lng = parseFloat(document.getElementById('lng').value);
    coord = [lng, lat];
    var info = null;
    // if (document.getElementById('info').value) {
    //     info = document.getElementById('info').value;
    // }
    let npm = [];
    npms = document.getElementsByName("npm");
    npms.forEach(element => {
        if (element.value) {
            npm.push(element.value);
        }
    });
    // console.log(npm);

    let nama = [];
    namas = document.getElementsByName("nama");
    namas.forEach(element => {
        if (element.value) {
            nama.push(element.value);
        }
    });
    let mhs = [];
    for (var i = 0; i < npm.length; i++) {
        mhs.push({
            npm: npm[i],
            nama: nama[i]
        })
    }
    instansi = document.getElementById('instansi').value;
    pemb_lap = document.getElementById('pemb_lap').value;
    alamat = document.getElementById('alamat').value;
    pemb_hp = document.getElementById('pemb_hp').value;
    hp_mhs = document.getElementById('hp_mhs').value;
    kota = document.getElementById('kota').value;
    var d = new Date().getTime();
    // console.log(nama);
    info = {
        instansi: instansi,
        pemb_lap: pemb_lap,
        alamat: alamat,
        pemb_hp: pemb_hp,
        mhs: mhs,
        hp_mhs: hp_mhs,
        kota: kota,
        visited: 0,
        time: d,
        imageIcon: null
    };
    // console.log(info);
    saveData(coord, info);
});

function saveData(coord, info = null, imageIcon = null) {
    if (info && imageIcon) info.imageIcon = imageIcon;
    var newMarkerRef = markerRef.push();
    newMarkerRef.set({
        type: 'Feature',
        geometry: {
            coordinates: coord,
            type: 'Point'
        },
        properties: info
    }, function(error) {
        if (error) {
            document.querySelector('.alert').style.display = 'block';
            document.getElementById("alert").innerHTML = "Gagal Menyimpan Data";
            document.querySelector('.alert').style.background = 'red';
            setTimeout(function() {
                document.querySelector('.alert').style.display = 'none';
            }, 3000);
        } else {
            document.getElementById("save").disabled = true;
            document.querySelector('.alert').style.display = 'block';
            document.getElementById("alert").innerHTML = "Data Disimpan ke Firebase";
            setTimeout(function() {
                document.querySelector('.alert').style.display = 'none';
            }, 3000);

        }
    });
    // console.log(newMarkerRef);
}

function setKabOption() {
    var kota = ["Bandar Lampung",
        "Asahan",
        "Jakarta Barat",
        "Jakarta Selatan",
        "Jakarta Utara",
        "Kotabumi",
        "Kota Pagar Alam",
        "Kota Serang",
        "Lampung Selatan",
        "Lampung Tengah",
        "Metro",
        "Palembang",
        "Pesawaran",
        "Prabumulih",
        "Pringsewu",
        "Tanggamus",
        "Tulang Bawang Barat",
        "Way Kanan"
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