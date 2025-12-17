<?php
session_start();
include '../../dbconnect.php';

// Access control: Only 'ketuakampung' role allowed
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ketuakampung') {
    header('Location: ../login.php');
    exit();
}
// Get ketua info
$username = $_SESSION['user_name'];
$role = $_SESSION['user_role'];

// Fetch count of pending reports
$ketua_id = $_SESSION['user_id'];
$sql = "SELECT COUNT(*) AS pending_count FROM villager_report
        WHERE ketua_id = '$ketua_id' AND report_status = 'Pending'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$pending_count = $row['pending_count'];

if (isset($_POST['submitinformation'])) {

    // Handle announcement publishing here
    $type = $_POST['announcement_type'];
    $title = $_POST['announcement_title'];
    $description = $_POST['announcement_description'];
    $date = $_POST['announcement_date'];
    $location = $_POST['announcement_location'];

    // Insert into database (example table: announcements)
    $sqlinsertannouncement = "INSERT INTO `ketua_announce`( `ketua_id`, `announce_title`, `announce_type`, `announce_desc`, `announce_date`, `announce_location`) 
    VALUES ('$ketua_id','$title','$type','$description','$date', '$location');";

    if (mysqli_query($conn, $sqlinsertannouncement)) {
        header("Location: ketuakampung_dashboard.php?success=1");
        exit();
    } else {
        echo "<script>alert('Error publishing announcement: " . mysqli_error($conn) . "');</script>";
    }
}





?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ketua Kampung Dashboard</title>

    <link rel="stylesheet" href="../../css/style_villager_dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

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

    .notificationformketua {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        width: 400px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .notificationformketua h2 {
        text-align: center;
        margin: 0 auto;
    }

    .notificationformketua label {
        display: block;
        margin-bottom: 5px;
    }

    .notificationformketua input,
    .notificationformketua select,
    .notificationformketua textarea {
        width: 100%;
        padding: 8px;
        margin-bottom: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;

    }

    .notificationformketua .btn {
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
        <!-- Sidebar / Drawer -->
        <div class="sidebar">
            <h2>Ketua Kampung</h2>
            <ul>
                <li><a href="#"><i class="fa fa-home"></i> Home</a></li>
                <li><a href="ketua_report_list.php"><i class="fa fa-edit"></i> Monitor Village Reports - Notify Village</a></li>
                <li><a href="#"><i class="fa fa-calendar-plus"></i> Announcement for villagers</a></li>
                <li><a href="#"><i class="fa fa-comments"></i> Communicate with Penghulu</a></li>
                <li><a href="#"><i class="fa-solid fa-map-location-dot"></i> Incident Map</a></li>
                <li><a href="../../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main content -->
        <div class="main">
            <!-- Header -->
            <div class="header">
                <h1>Welcome, <?php echo $username;  ?> !</h1>
            </div>

            <!-- Dashboard content -->
            <div class="content">
                <!-- Village Reports -->
                <div class="card">
                    <h3>Monitor Village Reports</h3>
                    <p>View local reports submitted by villagers and update status and classify villager reports.</p>
                    <p>Send directives or alerts to villager</p>

                    <a href="ketua_report_list.php" class="btn-with-badge">
                        View Reports
                        <?php if ($pending_count > 0): ?>
                            <span class="badge"><?= $pending_count ?></span>
                        <?php endif; ?>
                    </a>

                </div>


                <!-- Create Community Event -->
                <div class="card">
                    <h3>Announcement for villagers</h3>
                    <p>Publish event to villagers' dashboards.</p>
                    <button class="btn" onclick="openForm()">Publish Information</button></a>

                </div>


                <!-- Communicate with Penghulu -->
                <div class="card">
                    <h3>Communicate with Penghulu</h3>
                    <p>Send messages or requests to Penghulu.</p>
                    <button>Open Chat</button>
                </div>

                <!-- Map / Incident Location -->
                <div class="card">
                    <h3>Incident Map</h3>
                    <p>Identify incident points using GPS/maps.</p>
                    <div class="map-placeholder">Map area (Google Maps API later)</div>
                </div>
            </div>
        </div>

        <div id="reportform">
            <form method="POST" action="" class="notificationformketua">

                <div class="form-card">
                    <span class="close" onclick="closeForm()">&times;</span>
                    <h2>Publish Announcement</h2>

                    <label>Type</label>
                    <select name="announcement_type" required>
                        <option value="">Select Announcement Type</option>
                        <option value="event">Event</option>
                        <option value="alert">Alert</option>
                        <option value="info">Information</option>
                        <option value="community">Community</option>
                    </select>
                    
                    <label>Title</label>
                    <input type="text" name="announcement_title" required>

                    <label>Description</label>
                    <textarea name="announcement_description" required></textarea>

                    <label>Date</label>
                    <input type="date" name="announcement_date" required>

                    <label>Location</label>
                    <input type="text" name="announcement_location" placeholder="GPS / Address">

                    

                    <button class="btn" name="submitinformation">Confirm Publish</button>
                </div>
            </form>

            <?php if (isset($_GET['success'])): ?>
                <script>
                    alert("Information published successfully!");
                </script>
            <?php endif; ?>

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
</script>

</html>