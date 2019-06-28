// Initialize Firebase
var config = {
    apiKey: "AIzaSyBCmIfe6uYdwYtS6c7ET-IDEOKMq_sbDsQ",
    authDomain: "latihan-168007.firebaseapp.com",
    databaseURL: "https://latihan-168007.firebaseio.com",
    projectId: "latihan-168007",
    storageBucket: "latihan-168007.appspot.com",
    messagingSenderId: "760935327738"
};
firebase.initializeApp(config);
// var d = new Date(2019, 0, 25);
var d = new Date();
var start = new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
var end = new Date(d.getFullYear(), d.getMonth(), d.getDate() + 1).getTime();

var dataRef = firebase.database().ref();
var monPKL = dataRef.child('mon_pkl').orderByChild('properties/time').startAt(start).endAt(end);
// monPKL.orderByChild('properties/user/email').equalTo("febriansyahfaiz@gmail.com");
monPKL.once('value', getData, showError);

function getData(data) {
    var markers = [];
    data.forEach(function (datamarker) {
        markers.push(datamarker.val());
        console.log(datamarker.val().properties.npm, datamarker.val().properties.nama, new Date(datamarker.val().properties.time));
    });
    console.log(markers.length);
}

function showError(err) {
    console.log(err);
}