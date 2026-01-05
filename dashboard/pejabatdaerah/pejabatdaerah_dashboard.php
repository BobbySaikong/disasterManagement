<?php
session_start();

include '../../dbconnect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pejabatdaerah') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['user_name'];
$role = $_SESSION['user_role'];


//map
// penghulu reports
$report_sql = "SELECT r.latitude, r.longitude, r.report_title, r.report_type, r.report_status,
                u.user_name AS submitted_by
                FROM villager_report r
                JOIN tbl_users u ON
                r.villager_id = u.user_id
                WHERE r.report_status = 'Pending'";
$report_result = mysqli_query($db, $report_sql);
$reports = [];
while ($row = mysqli_fetch_assoc($report_result)) {
    $row['type'] = 'report';
    $reports[] = $row;
}

// SOS alerts
// change to penghulu reports?
$sos_sql = "SELECT s.latitude, s.longitude, s.sos_status, u.user_name AS sent_by
            FROM sos_villager s
            JOIN tbl_users u ON s.villager_id = u.user_id
            WHERE s.sos_status = 'Sent'";
$sos_result = mysqli_query($db, $sos_sql);
$sos = [];
while ($row = mysqli_fetch_assoc($sos_result)) {
    $row['type'] = 'sos';
    $sos[] = $row;
}


// Combine
$allPins = array_merge($reports, $sos);
$pinreports_json = json_encode($allPins);

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Pejabat Daerah Dashboard - DVMD</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="../../css/style_villager_dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>

<body>
  <div class="dashboard">

    <!-- Sidebar -->
    <aside class="sidebar">
      <h2>Pejabat Daerah</h2>
      <ul>
        <li><a href="pejabatdaerah_dashboard.php"><i class="fa fa-home"></i> Home</a></li>
        <!--todo penghulu report list-->
        <li><a href="pejabatdaerah_report_list.php"><i class="fa-solid fa-city"></i> Monitor All Villages</a></li>
        <!--make file just like village report list-->
        <li><a href="pejabatdaerah_penghulu_report_list.php"><i class="fa-solid fa-file-lines"></i> Reports From Penghulu </a></li>
        <li><a href="../../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
      </ul>
    </aside>

    <!-- Main -->
    <main class="main">

      <!-- Header -->
      <div class="header">
        <h1>Welcome, <?php echo $username; ?></h1>
        <p>Digital Village Management Dashboard (DVMD)</p>
      </div>

      <!-- Content -->
      <section class="content">

        <!-- Full village access -->
        <div class="card">
          <h3>Access and Monitor All Villages</h3>
          <p>View and manage report for all villages in the district.</p>
          <a href = "pejabatdaerah_report_list.php" ><button>View Villages</button></a>
        </div>

        <!-- Aid distribution -->
        <div class="card">
          <h3>Aid Distribution Management</h3>
          <p>Initiate and track aid distribution to affected areas.</p>
          <a href = "#"><button>Manage Aid</button></a>
        </div>


        <!-- Reports from Penghulu -->
        <div class="card">
          <h3> Reports From Penghulu</h3>
          <p>communicate with Penghulu and Review reports received from Penghulu.</p>
          <!--create pejabatdaerah-penghulu report list-->
          <a href= "pejabatdaerah_penghulu_report_list.php"><button>View Reports</button></a>
        </div>

        <!-- Emergency commands -->
        <div class="card critical">
          <h3> Disaster Commands</h3>
          <p>Issue district-level emergency commands , Send notifications to all villages and officials.</p>
          <a href= "#"><button class="danger-btn">Issue Command</button></a>
        </div>


        <!-- Map -->
        <div class="card">
          <h3>Incident Map</h3>
          <p>Track emergencies using GPS/maps.</p>
          <div id="incident-map" class="map-placeholder" onclick="openFullMap()"></div>
        </div>



      </section>
    </main>
  </div>

  <!-- Map Modal -->
  <div id="mapModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:999;">
      <div style="background:#fff; width:90%; max-width:600px; height:400px; margin:50px auto; padding:10px;">
          <h3>Click on map to select location</h3>
          <div id="map" style="height:300px;"></div>
          <button onclick="closeMap()">Done</button>
      </div>
  </div>

  <!-- Fullscreen Map Modal -->
  <div id="fullMapModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); z-index:9999;">
      <div style="position:relative; width:100%; height:100%;">
          <span style="position:absolute; top:10px; right:20px; font-size:30px; color:white; cursor:pointer; z-index:1000;" onclick="closeFullMap()">&times;</span>
          <div id="fullIncidentMap" style="width:100%; height:100%;"></div>
      </div>
  </div>

</body>

<!-- Map Script -->
    <script>
        var greenIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34]
        });

        var redIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34]
        });

        // Pins from PHP
        var pins = <?php echo $pinreports_json; ?>;

        // ---- Incident Map on dashboard ----
        let incidentMap = L.map('incident-map').setView([6.4432, 100.2056], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(incidentMap);

        // Add pins
        pins.forEach(function(pin) {
            if (pin.latitude && pin.longitude) {
                let icon = pin.type === 'report' ? greenIcon : redIcon;
                let popup = pin.type === 'report' ?
                    `<b>Report: ${pin.report_type}</b><br>Title: ${pin.report_title}<br>Status: ${pin.report_status}<br>Submitted by: ${pin.submitted_by}` :
                    `<b>SOS Alert</b><br>Status: ${pin.sos_status}<br>Sent by: ${pin.sent_by}`;

                L.marker([pin.latitude, pin.longitude], {
                        icon: icon
                    })
                    .addTo(incidentMap)
                    .bindPopup(popup);
            }
        });

        // ---- Fullscreen Map ----
        let fullMap; // global variable
        function openFullMap() {
            const modal = document.getElementById('fullMapModal');
            modal.style.display = 'block';

            setTimeout(() => {
                // Remove old map if exists
                if (fullMap) fullMap.remove();

                // Initialize map
                fullMap = L.map('fullIncidentMap');
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(fullMap);

                // Add all pins
                pins.forEach(function(pin) {
                    if (pin.latitude && pin.longitude) {
                        let icon = pin.type === 'report' ? greenIcon : redIcon;
                        let popup = pin.type === 'report' ?
                            `<b>Report: ${pin.report_type}</b><br>Title: ${pin.report_title}<br>Status: ${pin.report_status}<br>Submitted by: ${pin.submitted_by}` :
                            `<b>SOS Alert</b><br>Status: ${pin.sos_status}<br>Sent by: ${pin.sent_by}`;

                        L.marker([pin.latitude, pin.longitude], {
                                icon: icon
                            })
                            .addTo(fullMap)
                            .bindPopup(popup);
                    }
                });

                // Zoom to fit all pins
                let group = L.featureGroup(pins.map(pin => L.marker([pin.latitude, pin.longitude])));
                fullMap.fitBounds(group.getBounds().pad(0.2));

                // Fix map size
                fullMap.invalidateSize();
            }, 100); // small delay ensures modal is visible
        }

        function closeFullMap() {
            document.getElementById('fullMapModal').style.display = 'none';
            if (fullMap) fullMap.remove();
        }

        // ---- Map Picker for Report / SOS ----
        let mapPicker, reportMarker, sosMarker;

        function openMapPicker(type) {
            document.getElementById("mapModal").style.display = "block";

            setTimeout(() => {
                if (mapPicker) mapPicker.remove();

                mapPicker = L.map('map').setView([6.4432, 100.2056], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(mapPicker);

                mapPicker.on('click', function(e) {
                    let lat = e.latlng.lat;
                    let lng = e.latlng.lng;

                    if (type === 'report') {
                        if (reportMarker) reportMarker.setLatLng(e.latlng);
                        else reportMarker = L.marker(e.latlng, {
                            icon: greenIcon
                        }).addTo(mapPicker);

                        document.getElementById("latitude").value = lat;
                        document.getElementById("longitude").value = lng;

                    } else if (type === 'sos') {
                        if (sosMarker) sosMarker.setLatLng(e.latlng);
                        else sosMarker = L.marker(e.latlng, {
                            icon: redIcon
                        }).addTo(mapPicker);

                        document.getElementById("sos_latitude").value = lat;
                        document.getElementById("sos_longitude").value = lng;
                    }
                });

                mapPicker.invalidateSize();
            }, 100);
        }

        function closeMap() {
            document.getElementById("mapModal").style.display = "none";
        }
    </script>

</html>
