<?php
require_once __DIR__ . '/includes/functions.php';
ensure_upcoming_events();
$events = db_all("SELECT events.*, categories.name AS category_name FROM events JOIN categories ON categories.id = events.category_id WHERE events.event_date >= CURDATE() ORDER BY events.event_date ASC");
$page_title = 'Events map';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap section">
    <h1>Interactive events map</h1>
    <p class="lede">Find upcoming fandom events and explore them on the map.</p>
    <div class="filters">
        <button class="btn" type="button" id="nearbyEvents">Find events near me</button>
        <label for="nearbyRadius">Within</label>
        <select id="nearbyRadius" aria-label="Nearby search radius">
            <option value="25">25 km</option>
            <option value="50">50 km</option>
            <option value="100" selected>100 km</option>
            <option value="250">250 km</option>
        </select>
    </div>
    <p class="meta" id="nearbyStatus" role="status" aria-live="polite">Your location is used in this browser to find nearby events.</p>
    <div id="nearbyResults" aria-live="polite"></div>
    <div id="map"></div>
</section>
<?php
$payload = array();
foreach ($events as $ev) {
    $payload[] = array(
        'title' => $ev['title'],
        'city' => $ev['city'],
        'lat' => (float) $ev['latitude'],
        'lng' => (float) $ev['longitude'],
        'date' => $ev['event_date'],
        'url' => site_url('/event.php?id=' . (int) $ev['id']),
    );
}
$extra_js = '<script>
var events=' . json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';
var map=L.map("map").setView([20,0],2);
L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{attribution:"© OpenStreetMap"}).addTo(map);
var eventMarkers=[];
events.forEach(function(ev){var marker=L.marker([ev.lat,ev.lng]).addTo(map),popup=document.createElement("div"),title=document.createElement("strong"),details=document.createElement("div"),link=document.createElement("a");title.textContent=ev.title;details.textContent=ev.city+" · "+ev.date;link.href=ev.url;link.textContent="Details";popup.appendChild(title);popup.appendChild(details);popup.appendChild(link);marker.bindPopup(popup);eventMarkers.push({event:ev,marker:marker});});
var nearbyCircle=null;
function distanceKm(a,b){var rad=Math.PI/180,dLat=(b.lat-a.lat)*rad,dLng=(b.lng-a.lng)*rad,x=Math.sin(dLat/2)*Math.sin(dLat/2)+Math.cos(a.lat*rad)*Math.cos(b.lat*rad)*Math.sin(dLng/2)*Math.sin(dLng/2);return 6371*2*Math.atan2(Math.sqrt(x),Math.sqrt(1-x));}
document.getElementById("nearbyEvents").addEventListener("click",function(){var status=document.getElementById("nearbyStatus"),results=document.getElementById("nearbyResults");if(!navigator.geolocation){status.textContent="Location search is unavailable in this browser.";return;}status.textContent="Finding your location…";navigator.geolocation.getCurrentPosition(function(pos){var origin={lat:pos.coords.latitude,lng:pos.coords.longitude},radius=Number(document.getElementById("nearbyRadius").value),nearby=[];eventMarkers.forEach(function(entry){var km=distanceKm(origin,entry.event);if(km<=radius){nearby.push({event:entry.event,marker:entry.marker,km:km});}});nearby.sort(function(a,b){return a.km-b.km;});if(nearbyCircle){map.removeLayer(nearbyCircle);}nearbyCircle=L.circle([origin.lat,origin.lng],{radius:radius*1000,color:"#168b77",fillOpacity:0.08}).addTo(map);map.setView([origin.lat,origin.lng],radius<=50?9:7);eventMarkers.forEach(function(entry){if(nearby.some(function(item){return item.marker===entry.marker;})){entry.marker.addTo(map);}else{map.removeLayer(entry.marker);}});results.replaceChildren();if(!nearby.length){status.textContent="No events found within "+radius+" km.";return;}status.textContent=nearby.length+" event"+(nearby.length===1?"":"s")+" within "+radius+" km, sorted by distance.";nearby.forEach(function(item){var row=document.createElement("p"),link=document.createElement("a");link.href=item.event.url;link.textContent=item.event.title+" · "+item.event.city+" · "+item.event.date;row.appendChild(link);row.appendChild(document.createTextNode(" · "+Math.round(item.km)+" km"));results.appendChild(row);});},function(){status.textContent="We couldn’t read your location. Allow location access and try again.";},{enableHighAccuracy:false,timeout:10000,maximumAge:300000});});
window.addEventListener("resize",function(){map.invalidateSize();});
</script>';
require __DIR__ . '/includes/footer.php';
?>

