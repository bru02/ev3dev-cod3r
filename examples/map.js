var geo = new xGeo();
var z = 10;
geo.getLocation(function (lat, lon) {

    let res = geo.latlonToXY(lat, lon, z);
    fetch(`http://a.tile.stamen.com/toner/${z}/${res.x}/${res.y}.png`).then(function (response) {
        return response.blob();
    }).then(function (response) {
        let reader = new FileReader();
        reader.addEventListener('load', () => {
            // reader.result holds a data URL representation of response
            ev3.screen.decodeImage(reader.result);
            geo.toStreet(lat, lon, function (a) {
                ev3.screen.print(a);
            });
        }, false);
        reader.addEventListener('error', () => {
            reject(reader.error);
        }, false);
        reader.readAsDataURL(response);
    });
});