// console.log(new Date().getTime());
function doSave() {
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
}

function saveData(coord, info = null, imageIcon = null) {
    var dataRef = firebase.database().ref('pkl');
    if (info && imageIcon) info.imageIcon = imageIcon;
    var newdataRef = dataRef.push();

    data = {
        type: 'Feature',
        geometry: {
            coordinates: coord,
            type: 'Point'
        },
        properties: info
    }
    newdataRef.set(data,
        function(error) {
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
}