let coords = null;
class CurrentLocation {

    constructor() {
        this.alertdiv = document.getElementById("myloc");
        coords = { lat: -5.367284, lng: 105.244935 }; //koordinat unila
    }
    getAlertDiv() {
        return this.alertdiv;
    }
    getCoords() {
        return coords;
    }
    setCoords(objCoords) {
        coords = objCoords;
    }
    setCoords(lat, lng) {
        this.coords = { lat: lat, lng: lng };
    }
    getLocation() {
        if (navigator.geolocation) {
            let koord = null;
            navigator.geolocation.getCurrentPosition(function(position) {
                    let cl = new CurrentLocation();
                    if (cl.getAlertDiv()) cl.getAlertDiv().innerHTML = "Latitude: " + position.coords.latitude +
                        "<br>Longitude: " + position.coords.longitude;
                    center = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    cl.setCoords(center);
                    // console.log(CurrentLocation.coords);
                    coords = center;
                    setValue('lat', position.coords.latitude);
                    setValue('lng', position.coords.longitude);
                    if (document.getElementById("save"))
                        document.getElementById("save").disabled = false;
                    document.querySelector('.myloc').style.display = 'block';
                    setTimeout(function() {
                        document.querySelector('.myloc').style.display = 'none';
                    }, 3000);
                },

                function(error) {
                    document.querySelector('.myloc').style.display = 'block';
                    document.querySelector('.myloc').style.background = 'red';
                    var x = document.getElementById("myloc");
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            x.innerHTML = "User denied the request for Geolocation."
                            break;
                        case error.POSITION_UNAVAILABLE:
                            x.innerHTML = "Location information is unavailable."
                            break;
                        case error.TIMEOUT:
                            x.innerHTML = "The request to get user location timed out."
                            break;
                        case error.UNKNOWN_ERROR:
                            x.innerHTML = "An unknown error occurred."
                            break;
                    }
                    setTimeout(function() {
                        document.querySelector('.myloc').style.display = 'none';
                    }, 3000);
                });
            console.log(coords);
        } else {
            this.x.innerHTML = "Geolocation is not supported by this browser.";
        }
    }

    showPosition(position) {
        this.x.innerHTML = "Latitude: " + position.coords.latitude +
            "<br>Longitude: " + position.coords.longitude;
        center = {
            lat: position.coords.latitude,
            lng: position.coords.longitude
        };
        console.log(center);
        setValue('lat', position.coords.latitude);
        setValue('lng', position.coords.longitude);
        document.getElementById("save").disabled = false;
        document.querySelector('.myloc').style.display = 'block';
        setTimeout(function() {
            document.querySelector('.myloc').style.display = 'none';
        }, 3000);
    }

    showError(error) {
        document.querySelector('.myloc').style.display = 'block';
        document.querySelector('.myloc').style.background = 'red';
        switch (error.code) {
            case error.PERMISSION_DENIED:
                x.innerHTML = "User denied the request for Geolocation."
                break;
            case error.POSITION_UNAVAILABLE:
                x.innerHTML = "Location information is unavailable."
                break;
            case error.TIMEOUT:
                x.innerHTML = "The request to get user location timed out."
                break;
            case error.UNKNOWN_ERROR:
                x.innerHTML = "An unknown error occurred."
                break;
        }
        setTimeout(function() {
            document.querySelector('.myloc').style.display = 'none';
        }, 3000);
    }
}