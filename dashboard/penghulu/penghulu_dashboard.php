<?php
session_start();
include '../../dbconnect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'penghulu') {
    header('Location: ../login.php');
    exit();
}

$penghulu_id = $_SESSION['user_id'];
$username = $_SESSION['user_name'];
$role = $_SESSION['user_role'];

// Fetch count of pending reports
// $ketua_id = $_SESSION['user_id'];
// $sql = "SELECT COUNT(*) AS pending_count FROM villager_report
//         WHERE ketua_id = '$ketua_id' AND report_status = 'Pending'";
// $result = mysqli_query($db, $sql);
// $row = mysqli_fetch_assoc($result);
// $pending_count = $row['pending_count'];


// //submit announcement
// if (isset($_POST['submitinformation'])) {

//     // Handle announcement publishing here
//     $type = $_POST['announcement_type'];
//     $title = $_POST['announcement_title'];
//     $description = $_POST['announcement_description'];
//     $date = $_POST['announcement_date'];
//     $location = $_POST['announcement_location'];

//     // Insert into database (example table: announcements)
//     $sqlinsertannouncement = "INSERT INTO `ketua_announce`( `ketua_id`, `announce_title`, `announce_type`, `announce_desc`, `announce_date`, `announce_location`)
//     VALUES ('$ketua_id','$title','$type','$description','$date', '$location');";

//     if (mysqli_query($db, $sqlinsertannouncement)) {
//         header("Location: ketuakampung_dashboard.php?success=1");
//         exit();
//     } else {
//         echo "<script>alert('Error publishing announcement: " . mysqli_error($db) . "');</script>";
//     }
// }


$sqlPejabatdaerah = "SELECT user_id, user_name FROM tbl_users WHERE user_role = 'pejabatdaerah'";
$resultPejabatdaerah = mysqli_query($db, $sqlPejabatdaerah);


//submit report to pejabatdaerah
if (isset($_POST['submit_to_pejabatdaerah'])) {

    $title = $_POST['pd_title'];
    $desc = $_POST['pd_desc'];
    $location = $_POST['pd_location'];
    $pejabatdaerah_id = $_POST['pejabatdaerah_id'];
    $db_table = 'penghulu_report';
    $status = 'Pending';


    // $sql = "INSERT INTO $db_table (penghulu_id, pejabat_daerah, report_title, report_desc, report_location, report_status) VALUES (?,?,?,?,?,?)";
    // $stmt = $db->prepare($sql);
    // $stmt->bind_param("iissss", $penghulu_id, $pejabatdaerah_id, $title, $desc, $location, $status);
    // $stmt->execute();

    if ($db->execute_query("INSERT INTO penghulu_report (penghulu_id , pejabat_daerah_id , report_title , report_desc , report_location , report_status) VALUES (?,?,?,?,?,?)", [$penghulu_id, $pejabatdaerah_id, $title, $desc, $location, $status])) {
        header("Location: penghulu_dashboard.php?success_reportpejabatdaerah=1");
        exit;
    } else {
        echo "<script>alert('Error publishing announcement: " . mysqli_error($db) . "');</script>";
    }

    // $prepared_penghulu_report = mysqli_prepare($db_name, "INSERT INTO $db_table (penghulu_id,p) VALUES (")

    // $sql = "INSERT INTO `penghulu_report`(`penghulu_id`, `pejabat_daerah_id`, `report_title`, `report_desc`, `report_location`, `report_status`)
    // VALUES ('$penghulu_id','$pejabatdaerah_id','$title','$desc','$location','Pending');";

    // if (mysqli_query($db, $sql)) {

    //     exit();
    // } else {
    //     echo "<script>alert('Error submitting report to pejabat daerah: " . mysqli_error($db) . "');</script>";
    // }
}

//map
// Villager reports
$report_sql = "SELECT r.latitude, r.longitude, r.report_title, r.report_type, r.report_status,
                u.user_name AS submitted_by
                FROM villager_report r
                JOIN tbl_users u ON r.villager_id = u.user_id
                WHERE r.report_status = 'Pending'";
$report_result = mysqli_query($db, $report_sql);
$reports = [];
while ($row = mysqli_fetch_assoc($report_result)) {
    $row['type'] = 'report';
    $reports[] = $row;
}

//SOS alerts
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
$allPins = array_merge($reports);
$pinreports_json = json_encode($allPins);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Penghulu Dashboard - DVMD</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../../css/style_villager_dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>

<!--Report to Pejabat Daerah Form style -->
<style>
    .btn-with-badge {
        position: relative;
        display: inline-block;
        padding: 10px 20px;
        background-color: #1e40af;
        color: white;
        text-decoration: none;
        border-radius: 5px;
    }

    .btn-with-badge .badge {
        position: absolute;
        top: -5px;
        right: -10px;
        background-color: red;
        color: white;
        border-radius: 50%;
        padding: 5px 10px;
        font-size: 12px;
    }

    #reportform {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
    }

    .notificationformpenghulu {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        width: 400px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .notificationformpenghulu h2 {
        text-align: center;
        margin: 0 auto;
    }

    .notificationformpenghulu label {
        display: block;
        margin-bottom: 5px;
    }

    .notificationformpenghulu input,
    .notificationformpenghulu select,
    .notificationformpenghulu textarea {
        width: 100%;
        padding: 8px;
        margin-bottom: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;

    }

    .notificationformpenghulu .btn {
        background-color: #4CAF50;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;

    }
</style>

<body>
    <div class="dashboard">

        <!-- Sidebar -->
        <aside class="sidebar">
            <h2>Penghulu</h2>
            <ul>
                <li><a href="penghulu_dashboard.php"><i class="fa fa-home"></i> Home</a></li>
                <li><a href="penghulu_report_list.php"><i class="fa-solid fa-city"></i> Monitor All Villages - Review Issues - Notify Ketua Kampung</a></li>
                <li><a href="penghulu_ketua_report_list.php"><i class="fa-solid fa-file-lines"></i> Reports from Ketua Kampung</a></li>
                <li><a href="../../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main -->
        <div class="main">

            <!-- Header -->
            <div class="header">
                <h1>Welcome,<?php echo $username, $penghulu_id, $role; ?></h1>
                <p>Digital Village Management Dashboard (DVMD)</p>
            </div>

            <!-- Content -->
            <div class="content">




                <!-- Monitor villages -->
                <div class="card">
                    <h3>Monitor Village Status</h3>
                    <p>Track safety, emergencies, and village conditions, .</p>
                    <a href="penghulu_report_list.php"><button>Monitor Villages</button></a>
                </div>

                <!-- Review issues -->
                <div class="card">
                    <h3>Reports from Ketua Kampung</h3>
                    <p>Review Reported Issues, Analyze incidents escalated by Ketua Kampung , Send directives or alerts to Ketua Kampung..</p>
                    <a href="penghulu_ketua_report_list.php"><button>Review Issues</button></a>
                </div>

                <!-- Report to Pejabat Daerah -->
                <div class="card critical">
                    <h3>Report to Pejabat Daerah</h3>
                    <p>Escalate critical issues for district action.</p>
                    <button class="danger-btn" onclick="openPejabatdaerahForm()">Open Form</button>
                </div>

                <!-- Map / Incident Location -->
                <div class="card">
                    <h3>Incident Map</h3>
                    <p>Identify incident points using GPS/maps.</p>
                    <div id="incident-map" class="map-placeholder" onclick="openFullMap()"></div>
                </div>

            </div>

        </div>

        <div id="pejabatdaerahform" style="display:none;" class="notificationformpenghulu">
            <form method="POST">
                <h2>Report to Pejabat Daerah</h2>

                <label>Report Title</label>
                <input type="text" name="pd_title" required>

                <label>Description</label>
                <textarea name="pd_desc" required></textarea>

                <label>Location</label>
                <input type="text" name="pd_location" required>

                <label>Penghulu</label>
                <select name="pejabatdaerah_id" required>
                    <option value="">Select Pejabat Daerah</option>
                    <?php while ($rowP = mysqli_fetch_assoc($resultPejabatdaerah)): ?>
                                                                                                                                                                                                                                                                                                                                                                                                                                                        <option value="<?= htmlspecialchars($rowP['user_id']) ?>">
                                                                                                                                                                                                                                                                                                                                                                                                                                                            <?= htmlspecialchars($rowP['user_name']) ?>
                                                                                                                                                                                                                                                                                                                                                                                                                                                        </option>
                    <?php endwhile; ?>
                </select>

                <button class="btn" name="submit_to_pejabatdaerah">Submit</button>
                <button type="button" class="btn" onclick="closePejabatdaerahForm()">Cancel</button>
            </form>

            <?php if (isset($_GET['success_reportpejabatdaerah'])): ?>
                                                                                                                                                                                                                                                                                                                                                                                                                                                <script>
                                                                                                                                                                                                                                                                                                                                                                                                                                                    alert("report to Pejabat Daerah successfully!");
                                                                                                                                                                                                                                                                                                                                                                                                                                               </script>
            <?php endif; ?>


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



<script>
    var reportform = document.getElementById("reportform");

    function openForm() {
        reportform.style.display = "flex";
    }

    function closeForm() {
        reportform.style.display = "none";
    }


    function openPejabatdaerahForm() {
        document.getElementById("pejabatdaerahform").style.display = "block";
    }

    function closePejabatdaerahForm() {
        document.getElementById("pejabatdaerahform").style.display = "none";
    }

    // Map functionality
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



    let map;
    let reportMarker;
    let sosMarker;

    function openMapPicker(type) { // type = 'report' or 'sos'
        document.getElementById("mapModal").style.display = "block";

        setTimeout(() => {
            if (map) {
                map.remove();
            }

            map = L.map('map').setView([6.4432, 100.2056], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            map.on('click', function(e) {
                let lat = e.latlng.lat;
                let lng = e.latlng.lng;

                if (type === 'report') {
                    if (reportMarker) {
                        reportMarker.setLatLng(e.latlng);
                    } else {
                        reportMarker = L.marker(e.latlng, {
                            icon: greenIcon
                        }).addTo(map);
                    }
                    document.getElementById("latitude").value = lat;
                    document.getElementById("longitude").value = lng;

                } else if (type === 'sos') {
                    if (sosMarker) {
                        sosMarker.setLatLng(e.latlng);
                    } else {
                        sosMarker = L.marker(e.latlng, {
                            icon: redIcon
                                                }).addTo(map);
                    }
                    document.getElementById("sos_latitude").value = lat;
                    document.getElementById("sos_longitude").value = lng;
                }
            });

        }, 300);
    }

    function closeMap() {
        document.getElementById("mapModal").style.display = "none";
    }

    // Display incident pins on dashboard map
    let incidentMap = L.map('incident-map').setView([6.4432, 100.2056], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(incidentMap);

    // Get pins from PHP
    var pins = <?php echo $pinreports_json; ?>;

    pins.forEach(function(pin) {
        if (pin.latitude && pin.longitude) {
            let icon, popupContent;

            if (pin.type === 'report') {
                icon = greenIcon;
                popupContent = `<b>Report: ${pin.report_type}</b><br>
                            Title: ${pin.report_title}<br>
                            Status: ${pin.report_status}<br>
                            Submitted by: ${pin.submitted_by}`;
            } else if (pin.type === 'sos') {
                icon = redIcon;
                popupContent = `<b>SOS Alert</b><br>
                            Status: ${pin.sos_status}<br>
                            Sent by: ${pin.sent_by}`;
            }

            L.marker([pin.latitude, pin.longitude], {
                    icon: icon
                })
                .addTo(incidentMap)
                .bindPopup(popupContent);
        }
    });

    // Fullscreen Map
    // Open full map modal
    function openFullMap() {
        document.getElementById('fullMapModal').style.display = 'block';

        setTimeout(() => {
            // Remove previous map instance if exists
            if (window.fullMap) {
                window.fullMap.remove();
            }

            // Initialize full map
            window.fullMap = L.map('fullIncidentMap').setView([6.4432, 100.2056], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(window.fullMap);

            // Add all pins
            pins.forEach(function(pin) {
                if (pin.latitude && pin.longitude) {
                    let icon, popupContent;

                    if (pin.type === 'report') {
                        icon = greenIcon;
                        popupContent = `<b>Report: ${pin.report_type}</b><br>
                            Title: ${pin.report_title}<br>
                            Status: ${pin.report_status}<br>
                            Submitted by: ${pin.submitted_by}`;
                    } else if (pin.type === 'sos') {
                        icon = redIcon;
                        popupContent = `<b>SOS Alert</b><br>
                            Status: ${pin.sos_status}<br>
                            Sent by: ${pin.sent_by}`;
                    }

                    L.marker([pin.latitude, pin.longitude], {
                            icon: icon
                        })
                        .addTo(window.fullMap)
                        .bindPopup(popupContent);
                }
            });

        }, 200);
    }

    // Close full map modal
    function closeFullMap() {
        document.getElementById('fullMapModal').style.display = 'none';
        if (window.fullMap) window.fullMap.remove();
    }
</script>




</html>
