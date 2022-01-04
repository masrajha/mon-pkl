// Initialize Firebase
const config = {
    apiKey: "AIzaSyCQPT9kR18A8rqwMIgO2Ou6i9BcdZ4BDvE",
    authDomain: "ilkomunila.firebaseapp.com",
    databaseURL: "https://ilkomunila.firebaseio.com",
    projectId: "ilkomunila",
    storageBucket: "ilkomunila.appspot.com",
    messagingSenderId: "351909993041",
    appId: "1:351909993041:web:e70d1179b97cf470"
  };
firebase.initializeApp(config);

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
