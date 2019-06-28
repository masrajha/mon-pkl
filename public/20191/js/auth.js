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