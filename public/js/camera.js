var player = document.getElementById('player');
var snapshotCanvas = document.getElementById('snapshot');
var captureButton = document.getElementById('capture');

var handleSuccess = function(stream) {
    // Attach the video stream to the video element and autoplay.
    player.srcObject = stream;
};

captureButton.addEventListener('click', function() {
    var context = snapshot.getContext('2d');
    // Draw the video frame to the canvas.
    context.drawImage(player, 0, 0, snapshotCanvas.width,
        snapshotCanvas.height);
    player.style.display = 'none';
    snapshotCanvas.style.display = 'block';

    console.log(uploadFile(snapshotCanvas));
});

function uploadFile(canvas) {
    var storageRef = firebase.storage().ref();
    canvas.toBlob(function(blob) {
        var image = new Image();
        image.src = blob;
        var uploadTask = storageRef.child('images/' + "test.png").put(blob);
        uploadTask.on('state_changed', function(snapshot) {
            // Observe state change events such as progress, pause, and resume
            // See below for more detail
        }, function(error) {
            // Handle unsuccessful uploads
            console.log(error);
        }, function() {
            // Handle successful uploads on complete
            // For instance, get the download URL: https://firebasestorage.googleapis.com/...
            var downloadURL = uploadTask.snapshot.downloadURL;
            // console.log(downloadURL);
        });
    });
    console.log(uploadTask.downloadURL);
}

navigator.mediaDevices.getUserMedia({ video: true })
    .then(handleSuccess);