<?php
require_once "config/db.php";

// Check hotel images
$imgs = $conn->query("SELECT * FROM Hotel_Images");
while($img = $imgs->fetch_assoc()){
    echo "Hotel Image ID: " . $img['Image_Id'] . "<br>";
    echo "Path in DB: " . $img['Image_Path'] . "<br>";
    echo "Is Cover: " . $img['Image_IsCover'] . "<br>";
    echo "File exists: " . (file_exists($_SERVER['DOCUMENT_ROOT'] . "/trivago/" . $img['Image_Path']) ? "YES" : "NO") . "<br>";
    echo "<img src='/trivago/" . $img['Image_Path'] . "' style='width:100px;'><br><br>";
}

// Check room images
$rimgs = $conn->query("SELECT * FROM Room_Images");
while($img = $rimgs->fetch_assoc()){
    echo "Room Image ID: " . $img['Image_Id'] . "<br>";
    echo "Path in DB: " . $img['Image_Path'] . "<br>";
    echo "Is Cover: " . $img['Image_IsCover'] . "<br>";
    echo "File exists: " . (file_exists($_SERVER['DOCUMENT_ROOT'] . "/trivago/" . $img['Image_Path']) ? "YES" : "NO") . "<br>";
    echo "<img src='/trivago/" . $img['Image_Path'] . "' style='width:100px;'><br><br>";
}
?>