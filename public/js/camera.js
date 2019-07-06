var player = document.getElementById('player');
var snapshotCanvas = document.getElementById('snapshot');
var captureButton = document.getElementById('capture');
var videoTracks;
var handleSuccess = function (stream) {
    // Attach the video stream to the video element and autoplay.
    player.srcObject = stream;
    videoTracks = stream.getVideoTracks();
};

captureButton.addEventListener('click', function () {
    var date = new Date();
    var thn = date.getFullYear();
    var bln = leadingZero(date.getMonth() + 1);
    var tgl = leadingZero(date.getDate());
    var context = snapshot.getContext('2d');
    // Draw the video frame to the canvas.
    context.drawImage(player, 0, 0, snapshotCanvas.width,
        snapshotCanvas.height);
    player.style.display = 'none';
    snapshotCanvas.style.display = 'block';
    videoTracks.forEach(function (track) { track.stop() });
    var npm = document.getElementById('npm').value;
    var ket = document.getElementById('save').value;
    var filename = npm + '_' + thn + bln + tgl + '_' + ket + '.png';
    console.log(filename);
    captureButton.disabled=true;
    captureButton.style.backgroundColor='grey';
    uploadFile(snapshotCanvas,filename);
});

function uploadFile(canvas,filename) {
    var storageRef = firebase.storage().ref();
    canvas.toBlob(function (blob) {
        var image = new Image();
        image.src = blob;
        var uploadTask = storageRef.child('images/' + filename + ".png").put(blob);
        uploadTask.on('state_changed', function (snapshot) {
            // Observe state change events such as progress, pause, and resume
            // See below for more detail
        }, function (error) {
            // Handle unsuccessful uploads
            console.log(error);
        }, function () {
            // Handle successful uploads on complete
            // For instance, get the download URL: https://firebasestorage.googleapis.com/...
            var downloadURL = uploadTask.snapshot.downloadURL;
            document.getElementById('imgURL').value = downloadURL;
            setButtonLabel();
            console.log(downloadURL);
        });
    });
    console.log(uploadTask.downloadURL);
}
function leadingZero(number) {
    return number.toString().length == 1 ? '0' + number : number;
}
navigator.mediaDevices.getUserMedia({ video: true })
    .then(handleSuccess);