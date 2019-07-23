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
    console.log(user.uid);
    var monRef = firebase.database().ref('users/' + user.uid);
    console.log(monRef.toString());
    monRef.on('value', function(data) {
        setValue('npm', data.val().npm);
        setValue('nama', data.val().nama);
        setValue('instansi', data.val().instansi.nama);
        setValue('lat_instansi', data.val().instansi.lat);
        setValue('lng_instansi', data.val().instansi.lng);
        setValue('dosen', data.val().dosen);
        setValue('pembimbing', data.val().pembimbing);
        setValue('imgURL', data.val().photoURL);
        setValue('email', data.val().email);
        document.getElementById('instansi').disabled = ((document.getElementById('npm').value).length != 10);
        setButtonLabel();
    });

}

function setUserInfo(uid, user) {
    var userRef = firebase.database().ref('users/' + uid);
    // userRef.child('nama').set(user.nama);
    // userRef.child('dosen').set(user.dosen);
    // userRef.child('pembimbing').set(user.pembimbing);
    // userRef.child('photoURL').set(user.photoURL);
    // userRef.child('instansi/nama').set(user.instansi);
    userRef.set(user,
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
}

function doSave() {
    var userId = firebase.auth().currentUser.uid;
    var nama = document.getElementById('nama').value;
    var dosen = document.getElementById('dosen').value;
    var pembimbing = document.getElementById('pembimbing').value;
    var photoURL = document.getElementById('imgURL').value;
    var instansi = document.getElementById('instansi').value;
    var lat_instansi = document.getElementById('lat_instansi').value;
    var lng_instansi = document.getElementById('lng_instansi').value;
    var email = document.getElementById('email').value;
    user = {
        nama: nama,
        dosen: dosen,
        pembimbing: pembimbing,
        photoURL: photoURL,
        email: email,
        instansi: {
            nama: instansi,
            lat: lat_instansi,
            lng: lng_instansi
        }
    };
    setUserInfo(userId, user);
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
    });
    console.log(fitur);
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


function setValue(id, val) {
    if (document.getElementById(id))
        if (val) document.getElementById(id).value = val;
}

function setButtonLabel() {
    var nama = document.getElementById('nama').value;
    var dosen = document.getElementById('dosen').value;
    var pembimbing = document.getElementById('pembimbing').value;
    var photoURL = document.getElementById('imgURL').value;
    document.getElementById('save').disabled = photoURL.length <= 0 || nama.length <= 0 || dosen.length <= 0 || pembimbing.length <= 0;
    console.log(document.getElementById('save').disabled);
    var msgText = null;
    if (document.getElementById('save').disabled) {
        if (imgURL.length <= 0) {
            msgText = "Belum ambil foto kamera";
        } else if (nama.length <= 0) {
            msgText = "Nama masih kosong";
        } else if (dosen.length < 50) {
            msgText = "Nama Dosen masih kosong";
        } else {
            msgText = "Nama Pembimbing masih kosong";
        }
        document.getElementById('msg').style.background = '#D83E50';
        document.getElementById('msg').style.color = '#ffffff';
        document.getElementById('msg').innerHTML = "Error: " + msgText;
        document.getElementById('msg').style.display = 'block';
    } else {
        document.getElementById('msg').style.display = 'none';
    }
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