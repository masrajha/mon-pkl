var markerRef = firebase.database().ref('pkl');

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
var features = [];
markerRef.on('value', getData, showError);
var geoJSON = {
    type: "FeatureCollection",
    features: features
};
// console.log(geoJSON.features[0]);

function getData(data) {
    var markers = [];
    var fitur = [];
    data.forEach(function (datamarker) {
        // console.log(datamarker.val().geometry, datamarker.val().properties);
        fitur.push(datamarker.val());
        // console.log(datamarker.val());
        lat = parseFloat(datamarker.val().geometry.coordinates[1]);
        lng = parseFloat(datamarker.val().geometry.coordinates[0]);
        // info = '<h3>' + datamarker.val().properties.instansi + '</h3><br>';
        // info += datamarker.val().properties.alamat + '<br>';
        // info += '<a target="_blank" href="https://www.google.com/maps/place/' + lat + '+' + lng + '/@' + lat + ',' + lng + ',15z"><img src="images/direction.png"></a><br>'
        // info += 'Pembimbing : ' + datamarker.val().properties.pemb_lap;
        // if (datamarker.val().properties.pemb_hp) {
        //     info += ' ' + sendWhatsapp(datamarker.val().properties.pemb_hp) +
        //         ' ' + call(datamarker.val().properties.pemb_hp) + '<br>';
        // }
        // if (datamarker.val().properties.mhs) {
        //     info += 'Jumlah mhs : ' + datamarker.val().properties.mhs.length + '<br>';
        //     if (datamarker.val().properties.mhs[0].nama) {
        //         info += 'Contact mhs : ' + datamarker.val().properties.mhs[0].nama +
        //             ' ' + sendWhatsapp(datamarker.val().properties.hp_mhs) +
        //             ' ' + call(datamarker.val().properties.hp_mhs) + '<br>';
        //     }
        // }

        let info = '';
        if (datamarker && datamarker.val() && datamarker.val().properties) {
            let properties = datamarker.val().properties;

            // Pastikan setiap key memiliki nilai default
            let instansi = properties.instansi || 'Instansi tidak tersedia';
            let alamat = properties.alamat || 'Alamat tidak tersedia';
            let pemb_lap = properties.pemb_lap || 'Pembimbing tidak tersedia';
            let pemb_hp = properties.pemb_hp || null;
            let mhs = properties.mhs || [];
            let hp_mhs = properties.hp_mhs || null;

            // Latitude dan longitude juga harus didefinisikan
            let lat = datamarker.val().lat || '0';
            let lng = datamarker.val().lng || '0';

            info += '<h3>' + instansi + '</h3><br>';
            info += alamat + '<br>';
            info += '<a target="_blank" href="https://www.google.com/maps/place/' + lat + '+' + lng + '/@' + lat + ',' + lng + ',15z"><img src="images/direction.png"></a><br>';
            info += 'Pembimbing : ' + pemb_lap + '<br>';

            // Tambahkan kontak pembimbing jika ada
            if (pemb_hp) {
                info += sendWhatsapp(pemb_hp) + ' ' + call(pemb_hp) + '<br>';
            }

            // Jumlah mahasiswa dan kontak mahasiswa jika ada
            if (mhs.length > 0) {
                info += 'Jumlah mhs : ' + mhs.length + '<br>';
                if (mhs[0].nama) {
                    info += 'Contact mhs : ' + mhs[0].nama + ' ' + sendWhatsapp(hp_mhs) + ' ' + call(hp_mhs) + '<br>';
                }
            }
        } else {
            info = 'Data tidak tersedia.';
        }


        loc = {
            lat: lat,
            lng: lng
        };
        var img = 'images/placeholder.png';
        if (datamarker.val().properties.visited == 1)
            img = 'images/placeholder-visited.png';

        console.log(loc, info, img);
        markers.push(createMarker(loc, info, img));
    });
    // console.log(fitur[1].geometry);
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
    setTimeout(function () {
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
        navigator.geolocation.getCurrentPosition(function (position) {
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
        marker.addListener('click', function () {
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
    if (number) {
        var img = '<img src="images/whatsapp.png">';
        if (number.substring(0, 1) == '0')
            return '<a href=https://api.whatsapp.com/send?phone=62' + number.substring(1) + '>' + img + '</a>';
        if (number.substring(0, 1) == '8')
            return '<a href=https://api.whatsapp.com/send?phone=62' + number.substring(0) + '>' + img + '</a>';
        if (number.substring(0, 1) == '+')
            return '<a href=https://api.whatsapp.com/send?phone=' + number.substring(1) + '>' + img + '</a>';
        return '<a href=https://api.whatsapp.com/send?phone=' + number.substring(0) + '>' + img + '</a>';
    }
    return '<img src="images/whatsapp.png" alt="Not Available" style="filter: grayscale(100%);">';

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
