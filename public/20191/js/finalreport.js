var features = [];
var userData = [];
var userEmail = [];

var d = new Date();
var start = new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
var end = new Date(d.getFullYear(), d.getMonth(), d.getDate() + 1).getTime();

firebase.auth().onAuthStateChanged(user => {
    if (user) {
        document.getElementById('client').innerHTML = user.displayName;
    }
});
var dataRef = firebase.database().ref();
// var monPKL = dataRef.child('mon_pkl').orderByChild('properties/time').startAt(start).endAt(end);
// var monPKL = dataRef.child('mon_pkl');
// var users = dataRef.child('users');
// monPKL.on('value', gotData, showError);

var geoJSON = {
    type: "FeatureCollection",
    features: features
};
$.getJSON("data/monpkl.json", function(json) {
    // console.log(json); // this will show the info it in firebug console
    json.forEach(function (item){
        features.push(item);
    });
    $.getJSON("data/userdata.json", function(json) {
        json.forEach(function (item){
            userData.push(item);
        });
    
        $.getJSON("data/userEmail.json", function(json) {
            json.forEach(function (item){
                userEmail.push(item);
            });
            
            gotData();   
        });
        
    });
});

document.getElementById('view').addEventListener('click', viewData);
var hari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
if (!firebase.auth().currentUser) {
    // document.getElementById('private').disabled = true;
}

function viewData() {
    var start = document.getElementById('start').value;
    var end = document.getElementById('end').value;
    var sabtu = document.getElementById('sabtu').checked;
    var minggu = document.getElementById('minggu').checked;
    var libur = document.getElementById('libur').checked;
    if (currentUser) {
        console.log(start, end, sabtu, minggu);
        var report = createReportAll(userEmail, userData, start, end, sabtu, minggu, libur);
        CreateTableFromJSON(report);
        // CreateTableFromJSON(dataPKL);
    } else {
        var para = document.createElement("p");
        para.setAttribute('class', 'p-3 mb-2 bg-danger text-white justify-content-center');
        para.innerHTML = 'Silahkan Login Terlebih Dahulu';
        document.getElementById('client').appendChild(para);

        // document.getElementById('chart-waktu').innerHTML = 'Silahkan Login Terlebih Dahulu';
    }
}


function gotData() {
    
    // // var isPrivate = (currentUser != null && currentUser.email != 'didikunila@gmail.com'); //for admin
    // var isPrivate = null; //admin mode off
    // if (currentUser != null)
    //     data.forEach(function (item) {
    //         // console.log(item.val().geometry, item.val().properties);
    //         //panggil fungsi pushData disini
    //         if (isPrivate) {
    //             if (item.val().properties['user']['email'] === currentUser.email)
    //                 pushData(item, features, userData);
    //         } else {
    //             //admin zone
    //             pushData(item, features, userData);
    //             if (item.val().properties['user']['email'] === currentUser.email)
    //                 console.log(item.key);
    //         }
    //     });
    // if (currentUser) {
        // var dataPKL = laporanMhs(userData, currentUser.uid, '2019/1/24');
        var dataPKL = laporanMhs(userData, '1617051001', '2019/1/24');
        var report = createReportAll(userEmail, userData, '2019/1/24');
        
        CreateTableFromJSON(report);
    // } else {
    //     console.log("test");
    //     var para = document.createElement("p");
    //     para.setAttribute('class', 'p-3 mb-2 bg-danger text-white justify-content-center');
    //     para.innerHTML = 'Silahkan Login Terlebih Dahulu';
    //     document.getElementById('client').appendChild(para);
    //     // document.getElementById('chart-waktu').innerHTML = 'Silahkan Login Terlebih Dahulu';
    // }
}

function createReportAll(dataEmail, userData, start, end = 'now', sabtu = false, minggu = false, libur = false) {
    var allreport = [];
    dataEmail.forEach(function (elm) {
        if (elm) {
            var dataPKL = laporanMhs(userData, elm, start, end, sabtu, minggu, libur);
            var resume = resumeMhs(dataPKL);
            allreport.push(resume);
        }
    });
    return allreport;
}

function createReport(dataPKL) {
    resume = resumeMhs(dataPKL);
    console.log(resume);
    var divPhoto = document.getElementById('photo');
    var divResume = document.getElementById('resume');
    while (divResume.hasChildNodes()) {
        divResume.removeChild(divResume.lastChild);
    }
    while (divPhoto.hasChildNodes()) {
        divPhoto.removeChild(divPhoto.lastChild);
    }
    let img = document.createElement('img');
    img.setAttribute('src', resume.photoURL);
    img.setAttribute('width', 200);
    img.setAttribute('height', 200);
    divPhoto.appendChild(img);

    addRow(divResume, 'Nama', resume.nama);
    addRow(divResume, 'NPM', resume.npm);
    addRow(divResume, 'Email', resume.email);
    addRow(divResume, 'Tempat KP', resume.instansi);
    addRow(divResume, 'Total Hari', resume.jmlHari);
    addRow(divResume, 'Total Jam', resume.durasi);
    addRow(divResume, 'Rata-rata Jarak', printJarak(resume.jarak.toFixed(2)));
    addRow(divResume, 'Jam Masuk', printJam(resume.minMasuk, 'masuk') + ' s.d ' + printJam(resume.maxMasuk, 'masuk'));
    addRow(divResume, 'Jam Pulang', printJam(resume.minPulang, 'pulang') + ' s.d ' + printJam(resume.maxPulang, 'pulang'));

}

function addRow(divResume, key, val) {

    let row = document.createElement('div');
    row.setAttribute("class", "row p-1 mb-1");
    let c1 = document.createElement('div');
    c1.setAttribute("class", "col-md-4");
    let c2 = document.createElement('div');
    c2.setAttribute("class", "col-md-8");
    c1.innerHTML = key;
    c2.innerHTML = val;
    row.appendChild(c1);
    row.appendChild(c2);
    divResume.appendChild(row);
}

function filterTgl(tgl) {
    return function (element) {
        var date = new Date(element.data.tanggal);
        var y = date.getFullYear();
        var m = date.getMonth();
        var d = date.getDate();
        return new Date(tgl).getTime() === new Date(y, m, d).getTime();
    }
}



function pushData(item, fitur, userData) {
    fitur.push(item.val());
    // console.log(item.val().properties['user']['email']);
    mhslat = parseFloat(item.val().geometry.coordinates[0][1]);
    mhslng = parseFloat(item.val().geometry.coordinates[0][0]);
    inslat = parseFloat(item.val().geometry.coordinates[1][1]);
    inslng = parseFloat(item.val().geometry.coordinates[1][0]);
    var a = new google.maps.LatLng(mhslat, mhslng);
    var b = new google.maps.LatLng(inslat, inslng);
    var jarak = google.maps.geometry.spherical.computeDistanceBetween(a, b).toFixed(2);
    let waktu = new Date(item.val().properties.time);
    let tanggal = waktu.getFullYear() + '/' + (waktu.getMonth() + 1) + '/' + waktu.getDate();
    info = '<h5>' + item.val().properties.nama.toUpperCase() + ' NPM ';
    info += item.val().properties.npm + '</h5>';
    // info += '<a target="_blank" href="https://www.google.com/maps/place/' + mhslat + '+' + mhslng + '/@' + mhslat + ',' + mhslng + ',15z"><img src="images/direction.png"></a><br>'
    info += '<p class="font-weight-bold">' + item.val().properties.instansi + '</p>';
    info += '<p class="font-weight-bold">' + hari[waktu.getDay()] + ', ' + waktu.getDate() + '/' + (waktu.getMonth() + 1) + '/' + waktu.getFullYear() + '</p>';
    info += '<h6> Keterangan: </h6>';
    info += '<ul><li>' + item.val().properties.keterangan + '</li>';

    info += '<li>Jam : ' + waktu.getHours() + ':' + waktu.getMinutes() + ':' + waktu.getSeconds() + '</li>';
    // info += '<li>' + waktu + '</li>';
    info += '<li>Jarak : ' + jarak + '</li></ul>';

    if (userEmail.indexOf(item.val().properties.user.email) == -1) {
        dataUser = userData.filter(filterUser(item.val().properties.npm));
        if (item.val().properties.npm.length == 10 && dataUser.length > 0) {
            function isNPMExist(element) {
                return element.npm == item.val().properties.npm;
            }
            var i = userData.findIndex(isNPMExist);
            data = {
                tanggal: tanggal,
                waktu: item.val().properties.time,
                jarak: jarak,
                keterangan: item.val().properties.keterangan
            };
            userData[i].data.push(data);
        } else {
            var i = userEmail.length;
            userEmail.push(item.val().properties.user.email);
            userData[i] = {
                nama: item.val().properties.nama,
                npm: item.val().properties.npm,
                instansi: item.val().properties.instansi,
                photoURL: item.val().properties.user.photoURL,
                email: item.val().properties.user.email,
                uid: item.val().properties.user.uid,
                data: [{
                    tanggal: tanggal,
                    waktu: item.val().properties.time,
                    jarak: jarak,
                    keterangan: item.val().properties.keterangan
                }]
            }
        }
    } else {
        var i = userEmail.indexOf(item.val().properties.user.email)
        data = {
            tanggal: tanggal,
            waktu: item.val().properties.time,
            jarak: jarak,
            keterangan: item.val().properties.keterangan
        };

        userData[i].data.push(data);
        if (userData[i].npm.length !== 10 && item.val().properties.npm.length === 10)
            userData[i].npm = item.val().properties.npm;
    }
    // console.log(item.key);
}


function CreateTableFromJSON(data_all) {
    var arrHead = ["Foto", "NAMA", "NPM", "Email", "Tempat", "Jml Hari", "Rata-rata Jarak",
        "Durasi (Jam)", "Jam Masuk", "Jam Pulang"
    ];
    var table = document.createElement("table");
    table.setAttribute('id', 'table_1');
    table.setAttribute('class', 'display');

    // CREATE HTML TABLE HEADER ROW USING THE EXTRACTED HEADERS ABOVE.
    // ADD JSON DATA TO THE TABLE AS ROWS.
    for (var i = 0; i < data_all.length; i++) {
        tr = table.insertRow(-1);
        for (var j = 0; j < arrHead.length; j++) {
            var tabCell = tr.insertCell(-1);
            if (j == 0) {
                content = '<img src="' + data_all[i].photoURL + '" width=50 height=50>';
                tabCell.innerHTML = content;
            } else if (arrHead[j] == 'NAMA') {
                let content = '<a href="laporan.html?npm=' + data_all[i].npm + '" target=_blank>';
                content += data_all[i].nama;
                content += '</a>';
                tabCell.innerHTML = content;
            } else if (arrHead[j] == 'NPM') {
                let content = data_all[i].npm;
                tabCell.innerHTML = content;
            } else if (arrHead[j] == 'Email') {
                let content = data_all[i].email;
                tabCell.innerHTML = content;
            } else if (arrHead[j] == 'Tempat') {
                let content = data_all[i].instansi;
                tabCell.innerHTML = content;
            } else if (arrHead[j] == 'Jam Masuk') {
                let content = printJam(data_all[i].minMasuk, 'masuk') + ' s.d ' + printJam(data_all[i].maxMasuk, 'masuk')
                tabCell.innerHTML = content;
            } else if (arrHead[j] == 'Jam Pulang') {
                let content = printJam(data_all[i].minPulang, 'pulang') + ' s.d ' + printJam(data_all[i].maxPulang, 'pulang')
                tabCell.innerHTML = content;
            } else if (arrHead[j] == 'Jml Hari') {
                let content = data_all[i].jmlHari;
                tabCell.innerHTML = content;
            } else if (arrHead[j] == 'Rata-rata Jarak') {
                let content = printJarak(data_all[i].jarak.toFixed(2));
                tabCell.innerHTML = content;
            } else if (arrHead[j] == 'Durasi (Jam)') {
                let content = data_all[i].durasi.toFixed(2);
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
    jQuery(function ($) {
        $('#table_1').removeAttr('width').DataTable({
            scrollX: true,
            scrollCollapse: true,
            columnDefs: [{
                    width: 200,
                    targets: 9
                },
                {
                    width: 200,
                    targets: 8
                },
                {
                    width: 20,
                    targets: 3
                }
            ],
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            fixedColumns: true
        });
    });
}

function showError(err) {
    console.log(err);
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

function diff(start, end) {
    var diff = Math.abs(end - start);
    var hours = Math.floor(diff / 1000 / 60 / 60);
    // console.log(hours);
    diff -= hours * 1000 * 60 * 60;

    var minutes = Math.floor(diff / 1000 / 60);

    // If using time pickers with 24 hours format, add the below line get exact hours
    if (hours < 0)
        hours = hours + 24;

    // return (hours <= 9 ? "0" : "") + hours + ":" + (minutes <= 9 ? "0" : "") + minutes;
    return (hours + minutes / 60);
}

function filterUser(cari) {
    return function (element) {
        return (cari === element.npm || cari === element.email || cari === element.uid);
    }
}

function laporanHarian(arr, npm, tgl) {
    report = {};
    dataUser = arr.filter(filterUser(npm));
    if (dataUser.length == 0) return null;
    report.nama = dataUser[0].nama;
    report.tanggal = tgl;
    report.npm = dataUser[0].npm;
    data = dataUser[0].data;
    report.email = dataUser[0].email;
    report.uid = dataUser[0].uid;
    report.photoURL = dataUser[0].photoURL;
    report.instansi = dataUser[0].instansi;
    // console.log(data);
    dataKehadiran = data.filter(function (elm) {
        return (new Date(elm.tanggal).getTime() == new Date(tgl).getTime());
    });
    if (dataKehadiran.length == 0) {
        report.jamMasuk = 0;
        report.jamPulang = 0;
        report.durasi = 0;
        report.jrkMasuk = 0;
        report.jrkPulang = 0;
    } else if (dataKehadiran.length == 1) {
        let t1 = new Date(dataKehadiran[0].waktu).getFullYear();
        let t2 = new Date(dataKehadiran[0].waktu).getMonth();
        let t3 = new Date(dataKehadiran[0].waktu).getDate();
        let t4 = new Date(dataKehadiran[0].waktu).getHours();
        let t5 = new Date(dataKehadiran[0].waktu).getMinutes();
        let tt = t4 * 100 + t5
        if (tt < 1200) {
            report.jamMasuk = dataKehadiran[0].waktu;
            report.jamPulang = new Date(t1, t2, t3, 13, t5).getTime();
            report.jrkMasuk = parseFloat(dataKehadiran[0].jarak);
            report.jrkPulang = report.jrkMasuk;
            report.durasi = diff(report.jamPulang, report.jamMasuk);

        } else {
            report.jamMasuk = new Date(t1, t2, t3, 11, t5).getTime();
            report.jamPulang = dataKehadiran[0].waktu;
            report.jrkMasuk = parseFloat(dataKehadiran[0].jarak);
            report.jrkPulang = report.jrkMasuk;
            report.durasi = diff(report.jamPulang, report.jamMasuk);

        }
    } else {
        report.durasi = diff(dataKehadiran[0].waktu, dataKehadiran[dataKehadiran.length - 1].waktu);
        report.jamMasuk = dataKehadiran[0].waktu;
        report.jamPulang = dataKehadiran[dataKehadiran.length - 1].waktu;
        report.jrkMasuk = parseFloat(dataKehadiran[0].jarak);
        report.jrkPulang = parseFloat(dataKehadiran[1].jarak);
    }
    return report;
}

function laporanMhs(arr, npm, tglMulai, tglSelesai = 'now', sabtu = false, minggu = false, libur = false) {
    let tglLibur = ['2019/2/5'];
    let rekap = [];
    let end = null;
    let start = new Date(tglMulai).getTime();
    end = (tglSelesai == 'now') ? Math.min(new Date().getTime(), new Date('2019/2/16').getTime()) : new Date(tglSelesai).getTime();
    for (tgl = start; tgl <= end; tgl = new Date(d.getFullYear(), d.getMonth(), d.getDate() + 1).getTime()) {
        d = new Date(tgl);
        if (!sabtu)
            if (d.getDay() == 6)
                continue;

        if (!minggu)
            if (d.getDay() == 0)
                continue;

        // console.log(d);
        let hari = d.getFullYear() + "/" + (d.getMonth() + 1) + "/" + d.getDate();
        if (!libur)
            if (tglLibur.indexOf(hari) > -1)
                continue;
        // console.log(npm, hari);
        // console.log();
        rekap.push(laporanHarian(arr, npm, hari));
    }
    return rekap;
}

function chartMhs(rekapMhs, div, kriteria = 'waktu') {

    google.charts.load('current', {
        'packages': ['line']
    });
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
        var subtitle = '';
        var data = new google.visualization.DataTable();
        if (kriteria == 'durasi') {
            data.addColumn('string', 'Day');
            data.addColumn('number', 'Durasi');
            data.addColumn('number', 'Warning Line');
        } else if (kriteria == 'jarak') {
            data.addColumn('string', 'Day');
            data.addColumn('number', 'Masuk');
            data.addColumn('number', 'Pulang');
            data.addColumn('number', 'Warning Line');
        } else {
            data.addColumn('string', 'Day');
            data.addColumn('number', 'Masuk');
            data.addColumn('number', 'Pulang');
        }
        var rowData = []
        if (rekapMhs.length < 1) {
            data.addRows([
                ['0', 0, 0]
            ])
        } else {
            rekapMhs.forEach(function (elm) {
                if (kriteria == 'waktu') {
                    title = 'Jam Masuk dan Jam Pulang';
                    subtitle = 'Dalam Jam';
                    var masuk = new Date(elm.jamMasuk).getHours() + new Date(elm.jamMasuk).getMinutes() / 60;
                    var pulang = new Date(elm.jamPulang).getHours() + new Date(elm.jamPulang).getMinutes() / 60;
                    if (elm.jamMasuk == 0) masuk = elm.jamMasuk;
                    if (elm.jamPulang == 0) pulang = elm.jamPulang;
                    var row = [elm.tanggal, masuk, pulang];
                    rowData.push(row);
                } else if (kriteria == 'jarak') {
                    title = 'Jarak dari kantor';
                    subtitle = 'Dalam Meter';
                    var row = [elm.tanggal, elm.jrkMasuk, elm.jrkPulang, 300];
                    rowData.push(row);
                } else if (kriteria == 'durasi') {
                    title = 'Durasi di Tempat Kerja Praktik';
                    subtitle = 'Dalam Jam';
                    var row = [elm.tanggal, elm.durasi, 3];
                    rowData.push(row);
                }
            });
        }
        data.addRows(rowData);
        var options = {
            chart: {
                title: title,
                subtitle: subtitle
            },
            theme: {
                chartArea: {
                    width: '100%',
                    height: '100%'
                }
            },
            axes: {
                x: {
                    0: {
                        side: 'bottom'
                    }
                }
            }
        };

        // while (divResume.hasChildNodes()) {
        //     div.removeChild(div.lastChild);
        // }
        var chart = new google.charts.Line(document.getElementById(div));
        chart.draw(data, google.charts.Line.convertOptions(options));
        document.getElementById('loader').style.display = 'none';
    }
}

function resumeMhs(data) {
    // console.log(data);
    var jamMasuk = [],
        jamPulang = [],
        durasi = 0,
        jarak = 0;
    jml0 = 0;
    data.forEach(function (elm) {
        if (elm.jamMasuk != 0) {
            var d = new Date(elm.jamMasuk);
            var masuk = d.getHours() * 60 * 60 + d.getMinutes() * 60 + d.getSeconds();
            jamMasuk.push(masuk);
        } else jml0++;
        if (elm.jamPulang != 0) {
            var d = new Date(elm.jamPulang);
            var pulang = d.getHours() * 60 * 60 + d.getMinutes() * 60 + d.getSeconds();
            jamPulang.push(pulang);
        }
        if (elm.durasi != 0) durasi += elm.durasi;
        if (elm.jamMasuk != 0) jarak += (elm.jrkMasuk + elm.jrkPulang) / 2;
    });
    // console.log(jamPulang.min());
    // console.log(jamPulang.max());
    var rataJarak = jarak / (data.length - jml0);
    return {
        nama: data[0].nama,
        npm: data[0].npm,
        email: data[0].email,
        photoURL: data[0].photoURL,
        minMasuk: jamMasuk.min(),
        maxMasuk: jamMasuk.max(),
        minPulang: jamPulang.min(),
        maxPulang: jamPulang.max(),
        jarak: rataJarak,
        durasi: durasi,
        jmlHari: (data.length - jml0),
        instansi: data[0].instansi
    };
}

function printJam(time, format = null) {
    if (time <= 86399 && time > 0) //only time like 23:59:59
        time = new Date('2019,1,1').getTime() + time * 1000;

    var d = new Date(time);
    let jam = d.getHours() * 100 + d.getMinutes();
    if (format == null) {
        if (time == 0) return '--:--:--';
        return leadingZero(d.getHours()) + ":" + leadingZero(d.getMinutes()) + ":" + leadingZero(d.getSeconds());
    } else if (format == 'masuk') {
        // console.log('masuuukkk');
        let content = '<span class="p-2 mb-2 ';
        content += time == 0 ? 'bg-danger' : jam < 900 ? 'bg-success' : jam < 1100 ? 'bg-warning' : 'bg-danger';
        content += '  text-white">';
        content += printJam(time);
        content += '</span>';
        return content;
    } else if (format == 'pulang') {
        let content = '<span class="p-2 mb-2 ';
        content += time == 0 ? 'bg-danger' : jam >= 1600 ? 'bg-success' : jam >= 1300 ? 'bg-warning' : 'bg-danger';
        content += ' text-white">';
        content += printJam(time);
        content += '</span>';
        return content;
    }
}

function printDurasi(durasi, format = null) {
    let content = '<span class="p-2 mb-2 ';
    content += durasi > 6 ? 'bg-success' : durasi > 4 ? 'bg-warning' : 'bg-danger';
    content += ' text-white">';
    content += durasi;
    content += '</span>';
    return content;
}

function printJarak(jarak, format = null) {
    let content = '<span class="p-2 mb-2 ';
    if (jarak == 0) content += 'bg-danger';
    else
        content += jarak < 50 ? 'bg-success' : jarak < 300 ? 'bg-warning' : 'bg-danger';
    content += ' text-white">';
    content += jarak;
    content += '</span>';
    return content;
}

Array.prototype.max = function () {
    return Math.max.apply(null, this);
};

Array.prototype.min = function () {
    return Math.min.apply(null, this);
};