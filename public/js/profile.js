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
    var monRef = firebase.database().ref('users/' + user.uid);
    console.log(monRef.toString());
    monRef.on('value', function(data) {
        setValue('npm', data.val().npm);
        setValue('instansi', data.val().instansi.nama);
        setValue('dosen', data.val().dosen);
        setValue('pembimbing', data.val().pembimbing);
        setValue('imgURL', data.val().photoURL);
        document.getElementById('instansi').disabled = ((document.getElementById('npm').value).length != 10);

    });

}

function setUserInfo(uid, user) {
    var userRef = firebase.database().ref('users/' + uid);
    userRef.child('nama').set(user.nama);
    userRef.child('dosen').set(user.dosen);
    userRef.child('pembimbing').set(user.pembimbing);
    userRef.child('photoURL').set(user.photoURL);

}

function doSave() {
    var userId = firebase.auth().currentUser.uid;
    var nama = document.getElementById('nama').value;
    var dosen = document.getElementById('dosen').value;
    var pembimbing = document.getElementById('pembimbing').value;
    var photoURL = document.getElementById('imgURL').value;
    user = {
        nama: nama,
        dosen: dosen,
        pembimbing: pembimbing,
        photoURL: photoURL
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