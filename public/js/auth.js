// Initialize Firebase
const config = {
    apiKey: "AIzaSyArbN7yL_1ADsTSZ_5NEWiExxmZYZBpgMI",
    authDomain: "ilkomunila.firebaseapp.com",
    databaseURL: "https://ilkomunila.firebaseio.com",
    projectId: "ilkomunila",
    storageBucket: "ilkomunila.appspot.com",
    messagingSenderId: "351909993041",
    appId: "1:351909993041:web:e70d1179b97cf470"
  };
  
firebase.initializeApp(config);

var currentUser = null;
firebase.auth().onAuthStateChanged(user => {
    if (user) {
        // document.getElementById('client').innerHTML = user.displayName;
        // document.querySelector('client').style.display=block;
        app(user);
    }
});

function login() {
    function newLoginHappened(user) {
        if (user) {
            app(user);
        } else {
            var provider = new firebase.auth.GoogleAuthProvider();
            firebase.auth().signInWithRedirect(provider);
        }
    }
    firebase.auth().onAuthStateChanged(newLoginHappened);
}

function logout() {
    firebase.auth().signOut().then(function() {
        window.location.href = "/";
    }, function(error) {
        console.log(error);
    });
}

function app(user) {
    // console.log(user);
    currentUser = user;
    client = document.getElementById('client');
    client.innerHTML = "Selamat Datang <b>" + user.displayName + "</b>";
    if (document.getElementById('nama'))
        document.getElementById('nama').value = user.displayName;
    var btnLogout = document.createElement("button");
    if (document.getElementById('private'))
        document.getElementById('private').disabled = false;
    btnLogout.innerHTML = "Logout";
    btnLogout.addEventListener('click', logout);
    // Button Logout ditutup sementara
    client.appendChild(document.createElement("p"));
    client.appendChild(btnLogout);


}