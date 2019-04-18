// Initialize Firebase
var config = {
    apiKey: "AIzaSyBCmIfe6uYdwYtS6c7ET-IDEOKMq_sbDsQ",
    authDomain: "latihan-168007.firebaseapp.com",
    databaseURL: "https://latihan-168007.firebaseio.com",
    projectId: "latihan-168007",
    storageBucket: "latihan-168007.appspot.com",
    messagingSenderId: "760935327738"
};
f   irebase.initializeApp(config);

var markerRef = firebase.database().ref('pkl');

var fitur = [];
console.log(typeof(fitur));
markerRef.on('value', getData, showError);
var geoJSON = {
    type: "FeatureCollection",
    features: fitur
};
coba=[1,2,3,4,4,4,2,2,2,21,1,1,1,1,3,32,2];
console.log(coba);
function getData(data) {
    data.forEach(function(datamarker) {
    fitur.push(datamarker.val().properties);
    });
}

function showError(err) {
    console.log(err);
}
