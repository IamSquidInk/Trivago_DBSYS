<?php
session_start();
require_once "../config/db.php";

// ── RESTRICT TO ADMIN ONLY ──
if(!isset($_SESSION['guest_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../auth/login.php");
    exit();
}

$success = "";
$error   = "";

// ══════════════════════════════════════════════
//  ADD ROOM
// ══════════════════════════════════════════════
if(isset($_POST['add_room'])){
    $hotelId      = (int)$_POST['room_hotel_id'];
    $type         = $conn->real_escape_string($_POST['room_type']);
    $capacity     = (int)$_POST['room_capacity'];
    $petFriendly  = isset($_POST['room_pet_friendly']) ? 1 : 0;
    $availability = $conn->real_escape_string($_POST['room_availability']);

    $stmt = $conn->prepare("INSERT INTO Room
        (Room_HotelId, Room_Type, Room_Capacity, Room_PetFriendly, Room_Availability)
        VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isiis", $hotelId, $type, $capacity, $petFriendly, $availability);

    if($stmt->execute()){
        $success = "Room added successfully!";
    } else {
        $error = "Failed to add room.";
    }
}

// ══════════════════════════════════════════════
//  DELETE ROOM
// ══════════════════════════════════════════════
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];

    // Delete image files first
    $imgs = $conn->query("SELECT Image_Path FROM Room_Images WHERE Image_RoomId = $id");
    while($img = $imgs->fetch_assoc()){
        $filePath = "../" . $img['Image_Path'];
        if(file_exists($filePath)) unlink($filePath);
    }

    $conn->query("DELETE FROM Room WHERE Room_Id = $id");
    $success = "Room deleted successfully!";
}

// ══════════════════════════════════════════════
//  EDIT ROOM
// ══════════════════════════════════════════════
if(isset($_POST['edit_room'])){
    $id           = (int)$_POST['room_id'];
    $hotelId      = (int)$_POST['room_hotel_id'];
    $type         = $conn->real_escape_string($_POST['room_type']);
    $capacity     = (int)$_POST['room_capacity'];
    $petFriendly  = isset($_POST['room_pet_friendly']) ? 1 : 0;
    $availability = $conn->real_escape_string($_POST['room_availability']);

    $stmt = $conn->prepare("UPDATE Room SET
        Room_HotelId      = ?,
        Room_Type         = ?,
        Room_Capacity     = ?,
        Room_PetFriendly  = ?,
        Room_Availability = ?
        WHERE Room_Id     = ?");
    $stmt->bind_param("isiisi", $hotelId, $type, $capacity, $petFriendly, $availability, $id);

    if($stmt->execute()){
        $success = "Room updated successfully!";
    } else {
        $error = "Failed to update room.";
    }
}

// ══════════════════════════════════════════════
//  UPLOAD ROOM IMAGE
// ══════════════════════════════════════════════
if(isset($_POST['upload_image'])){
    $roomId  = (int)$_POST['image_room_id'];
    $isCover = isset($_POST['is_cover']) ? 1 : 0;
    $file    = $_FILES['room_image'];
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];

    if(!in_array($file['type'], $allowed)){
        $error = "Only JPG, PNG, and WEBP images are allowed.";
    } elseif($file['size'] > 5 * 1024 * 1024){
        $error = "Image must be under 5MB.";
    } else {
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "room_" . $roomId . "_" . time() . "." . $ext;
        $destPath = $_SERVER['DOCUMENT_ROOT'] . "/trivago/assets/images/rooms/" . $filename;
        $dbPath   = "assets/images/rooms/" . $filename;

        if(move_uploaded_file($file['tmp_name'], $destPath)){
            if($isCover){
                $conn->query("UPDATE Room_Images SET Image_IsCover = 0 WHERE Image_RoomId = $roomId");
            }

            $stmt      = $conn->prepare("INSERT INTO Room_Images
                (Image_RoomId, Image_Path, Image_IsCover, Image_AddedDate)
                VALUES (?, ?, ?, ?)");
            $addedDate = date('Y-m-d');
            $stmt->bind_param("isis", $roomId, $dbPath, $isCover, $addedDate);
            $stmt->execute();

            $success = "Image uploaded successfully!";
        } else {
            $error = "Failed to upload image. Check folder permissions.";
        }
    }
}

// ══════════════════════════════════════════════
//  SET COVER IMAGE
// ══════════════════════════════════════════════
if(isset($_GET['set_cover'])){
    $imageId = (int)$_GET['set_cover'];
    $roomId  = (int)$_GET['room_id'];

    $conn->query("UPDATE Room_Images SET Image_IsCover = 0 WHERE Image_RoomId = $roomId");
    $conn->query("UPDATE Room_Images SET Image_IsCover = 1 WHERE Image_Id = $imageId");
    $success = "Cover photo updated!";
}

// ══════════════════════════════════════════════
//  DELETE IMAGE
// ══════════════════════════════════════════════
if(isset($_GET['delete_image'])){
    $imageId = (int)$_GET['delete_image'];
    $img     = $conn->query("SELECT Image_Path FROM Room_Images WHERE Image_Id = $imageId")->fetch_assoc();

    if($img){
        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/trivago/" . $img['Image_Path'];
        if(file_exists($filePath)) unlink($filePath);
        $conn->query("DELETE FROM Room_Images WHERE Image_Id = $imageId");
        $success = "Image deleted successfully!";
    }
}

// ── FETCH ALL ROOMS ──
$rooms = $conn->query("
    SELECT r.*, h.Hotel_Name 
    FROM Room r
    JOIN Hotel h ON h.Hotel_Id = r.Room_HotelId
    ORDER BY h.Hotel_Name, r.Room_Type
");

// ── FETCH HOTELS + ROOMS FOR DROPDOWNS ──
$hotelsDropdown = $conn->query("SELECT Hotel_Id, Hotel_Name FROM Hotel ORDER BY Hotel_Name");
$roomsDropdown  = $conn->query("
    SELECT r.Room_Id, r.Room_Type, h.Hotel_Name
    FROM Room r
    JOIN Hotel h ON h.Hotel_Id = r.Room_HotelId
    ORDER BY h.Hotel_Name, r.Room_Type
");

$title = "Manage Rooms - trivago";
include "../layout/header.php";
?>

<style>
    .admin-wrapper { display: flex; min-height: calc(100vh - 64px); }

    .admin-sidebar {
        width: 240px; background: #ffffff;
        border-right: 1px solid #e8e8e8;
        padding: 24px 16px; position: sticky;
        top: 64px; height: calc(100vh - 64px); flex-shrink: 0;
    }

    .admin-sidebar h6 {
        font-size: 11px; text-transform: uppercase;
        letter-spacing: 1px; color: var(--trivago-muted);
        margin-bottom: 12px; padding-left: 12px;
    }

    .sidebar-link {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 12px; border-radius: 8px;
        font-size: 14px; font-weight: 600;
        color: var(--trivago-text); text-decoration: none;
        border-left: 3px solid transparent;
        transition: all 0.2s ease; margin-bottom: 4px;
    }

    .sidebar-link:hover { background: var(--trivago-gray); color: var(--trivago-blue); }

    .sidebar-link.active-sidebar {
        background: #e8f0fe; color: var(--trivago-blue);
        border-left: 3px solid var(--trivago-blue);
    }

    .admin-main { flex-grow: 1; padding: 32px; background: var(--trivago-gray); }

    .table-card {
        background: #ffffff; border-radius: 14px;
        padding: 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    }

    .table-card-title {
        font-size: 16px; font-weight: 700; color: var(--trivago-dark);
        margin-bottom: 16px; padding-bottom: 12px;
        border-bottom: 1px solid #f0f0f0;
        display: flex; justify-content: space-between; align-items: center;
    }

    .admin-table th {
        font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;
        color: var(--trivago-muted); font-weight: 600;
        border-bottom: 2px solid #f0f0f0; padding: 10px 12px;
    }

    .admin-table td {
        font-size: 13px; padding: 12px;
        vertical-align: middle; border-bottom: 1px solid #f8f8f8;
    }

    .availability-badge { border-radius: 6px; padding: 4px 10px; font-size: 12px; font-weight: 600; }
    .available   { background: #e6f9f0; color: #1a8c55; }
    .unavailable { background: #fde8e8; color: #c0392b; }

    /* ── IMAGE GRID ── */
    .image-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }

    .image-item {
        position: relative; width: 100px; height: 80px;
        border-radius: 8px; overflow: hidden;
        border: 2px solid #e8e8e8;
    }

    .image-item.is-cover { border-color: #1a8c55; }
    .image-item img { width: 100%; height: 100%; object-fit: cover; }

    .image-item .image-actions {
        position: absolute; bottom: 0; left: 0; right: 0;
        background: rgba(0,0,0,0.55);
        display: flex; justify-content: center;
        gap: 4px; padding: 4px;
        opacity: 0; transition: opacity 0.2s ease;
    }

    .image-item:hover .image-actions { opacity: 1; }

    .cover-badge {
        position: absolute; top: 4px; left: 4px;
        background: #1a8c55; color: #fff;
        font-size: 9px; font-weight: 700;
        padding: 2px 6px; border-radius: 4px;
    }
</style>

<div class="admin-wrapper">

    <!-- SIDEBAR -->
    <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
    <aside class="admin-sidebar">
        <h6>Admin Panel</h6>
        <a href="admin_dashboard.php" class="sidebar-link <?= $currentPage === 'admin_dashboard.php' ? 'active-sidebar' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="manage_hotels.php" class="sidebar-link <?= $currentPage === 'manage_hotels.php' ? 'active-sidebar' : '' ?>">
            <i class="bi bi-building"></i> Hotels
        </a>
        <a href="manage_rooms.php" class="sidebar-link <?= $currentPage === 'manage_rooms.php' ? 'active-sidebar' : '' ?>">
            <i class="bi bi-door-open"></i> Rooms
        </a>
        <a href="manage_partners.php" class="sidebar-link <?= $currentPage === 'manage_partners.php' ? 'active-sidebar' : '' ?>">
            <i class="bi bi-handshake"></i> Partners
        </a>
        <hr style="border-color:#f0f0f0; margin: 16px 0;">
        <a href="../index.php" class="sidebar-link">
            <i class="bi bi-house"></i> View Site
        </a>
        <a href="../auth/logout.php" class="sidebar-link text-danger">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </aside>

    <!-- MAIN -->
    <main class="admin-main">

        <h4 style="font-weight:700; margin-bottom:4px;">Manage Rooms</h4>
        <p class="text-muted small mb-4">Add, edit, remove rooms and manage their photos.</p>

        <?php if($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ADD ROOM -->
        <div class="table-card mb-4">
            <div class="table-card-title">
                <span><i class="bi bi-plus-circle me-2" style="color:var(--trivago-blue);"></i>Add New Room</span>
            </div>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Hotel</label>
                        <select name="room_hotel_id" class="form-control" required>
                            <option value="">-- Select Hotel --</option>
                            <?php
                            $hotelsDropdown->data_seek(0);
                            while($h = $hotelsDropdown->fetch_assoc()):
                            ?>
                                <option value="<?= $h['Hotel_Id'] ?>"><?= htmlspecialchars($h['Hotel_Name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Room Type</label>
                        <select name="room_type" class="form-control" required>
                            <?php foreach(['Single','Double','Twin','Suite','Deluxe','Family'] as $type): ?>
                                <option value="<?= $type ?>"><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Capacity (guests)</label>
                        <input type="number" name="room_capacity" class="form-control" min="1" max="20" value="2" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Availability</label>
                        <select name="room_availability" class="form-control" required>
                            <option value="Available">Available</option>
                            <option value="Unavailable">Unavailable</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input type="checkbox" name="room_pet_friendly" class="form-check-input" id="petFriendlyAdd">
                            <label class="form-check-label fw-semibold" for="petFriendlyAdd">Pet Friendly</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="add_room" class="btn btn-trivago">
                            <i class="bi bi-plus-circle me-1"></i>Add Room
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- UPLOAD ROOM IMAGE -->
        <div class="table-card mb-4">
            <div class="table-card-title">
                <span><i class="bi bi-image me-2" style="color:#8e44ad;"></i>Upload Room Photo</span>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Select Room</label>
                        <select name="image_room_id" class="form-control" required>
                            <option value="">-- Select Room --</option>
                            <?php
                            $roomsDropdown->data_seek(0);
                            while($r = $roomsDropdown->fetch_assoc()):
                            ?>
                                <option value="<?= $r['Room_Id'] ?>">
                                    <?= htmlspecialchars($r['Hotel_Name']) ?> — <?= htmlspecialchars($r['Room_Type']) ?> (ID: <?= $r['Room_Id'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Choose Image (JPG, PNG, WEBP — max 5MB)</label>
                        <input type="file" name="room_image" class="form-control"
                               accept="image/jpeg,image/png,image/webp" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end pb-1">
                        <div class="form-check">
                            <input type="checkbox" name="is_cover" class="form-check-input" id="isCoverRoom">
                            <label class="form-check-label fw-semibold" for="isCoverRoom">Set as Cover</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="upload_image" class="btn btn-trivago w-100">
                            <i class="bi bi-upload me-1"></i>Upload
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- ROOMS TABLE -->
        <div class="table-card">
            <div class="table-card-title">
                <span><i class="bi bi-door-open me-2" style="color:#1a8c55;"></i>All Rooms</span>
                <span class="badge bg-success"><?= $rooms->num_rows ?> rooms</span>
            </div>
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hotel</th>
                            <th>Type</th>
                            <th>Capacity</th>
                            <th>Pet Friendly</th>
                            <th>Availability</th>
                            <th>Photos</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rooms->data_seek(0);
                        while($r = $rooms->fetch_assoc()):
                            $roomImages = $conn->query("
                                SELECT * FROM Room_Images
                                WHERE Image_RoomId = {$r['Room_Id']}
                                ORDER BY Image_IsCover DESC, Image_AddedDate ASC
                            ");
                        ?>
                        <tr>
                            <td><?= $r['Room_Id'] ?></td>
                            <td><?= htmlspecialchars($r['Hotel_Name']) ?></td>
                            <td>
                                <span style="background:#e8f0fe; color:var(--trivago-blue);
                                             border-radius:6px; padding:3px 10px; font-size:12px; font-weight:600;">
                                    <?= htmlspecialchars($r['Room_Type']) ?>
                                </span>
                            </td>
                            <td><i class="bi bi-person me-1" style="color:var(--trivago-blue);"></i><?= $r['Room_Capacity'] ?></td>
                            <td>
                                <?php if($r['Room_PetFriendly']): ?>
                                    <i class="bi bi-check-circle-fill text-success"></i> Yes
                                <?php else: ?>
                                    <i class="bi bi-x-circle-fill text-danger"></i> No
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="availability-badge <?= $r['Room_Availability'] === 'Available' ? 'available' : 'unavailable' ?>">
                                    <?= $r['Room_Availability'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if($roomImages && $roomImages->num_rows > 0): ?>
                                    <div class="image-grid">
                                        <?php while($img = $roomImages->fetch_assoc()): ?>
                                        <div class="image-item <?= $img['Image_IsCover'] ? 'is-cover' : '' ?>">
                                            <img src="/trivago/<?= htmlspecialchars($img['Image_Path']) ?>" alt="Room photo">
                                            <?php if($img['Image_IsCover']): ?>
                                                <span class="cover-badge">COVER</span>
                                            <?php endif; ?>
                                            <div class="image-actions">
                                                <?php if(!$img['Image_IsCover']): ?>
                                                <a href="manage_rooms.php?set_cover=<?= $img['Image_Id'] ?>&room_id=<?= $r['Room_Id'] ?>"
                                                   title="Set as cover"
                                                   class="btn btn-sm btn-light p-0 px-1">
                                                    <i class="bi bi-star-fill" style="font-size:11px;"></i>
                                                </a>
                                                <?php endif; ?>
                                                <a href="manage_rooms.php?delete_image=<?= $img['Image_Id'] ?>"
                                                   title="Delete"
                                                   onclick="return confirm('Delete this image?')"
                                                   class="btn btn-sm btn-danger p-0 px-1">
                                                    <i class="bi bi-trash" style="font-size:11px;"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">No photos</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editRoomModal<?= $r['Room_Id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="manage_rooms.php?delete=<?= $r['Room_Id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete this room?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>

                        <!-- EDIT MODAL -->
                        <div class="modal fade" id="editRoomModal<?= $r['Room_Id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-4">
                                    <div class="modal-header border-0">
                                        <h5 class="modal-title">Edit Room</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST">
                                            <input type="hidden" name="room_id" value="<?= $r['Room_Id'] ?>">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold">Hotel</label>
                                                    <select name="room_hotel_id" class="form-control" required>
                                                        <?php
                                                        $hotelsDropdown->data_seek(0);
                                                        while($h = $hotelsDropdown->fetch_assoc()):
                                                        ?>
                                                            <option value="<?= $h['Hotel_Id'] ?>"
                                                                <?= $h['Hotel_Id'] == $r['Room_HotelId'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($h['Hotel_Name']) ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Room Type</label>
                                                    <select name="room_type" class="form-control" required>
                                                        <?php foreach(['Single','Double','Twin','Suite','Deluxe','Family'] as $type): ?>
                                                            <option value="<?= $type ?>" <?= $type == $r['Room_Type'] ? 'selected' : '' ?>>
                                                                <?= $type ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Capacity</label>
                                                    <input type="number" name="room_capacity" class="form-control"
                                                           min="1" max="20" value="<?= $r['Room_Capacity'] ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Availability</label>
                                                    <select name="room_availability" class="form-control" required>
                                                        <option value="Available"   <?= $r['Room_Availability'] === 'Available'   ? 'selected' : '' ?>>Available</option>
                                                        <option value="Unavailable" <?= $r['Room_Availability'] === 'Unavailable' ? 'selected' : '' ?>>Unavailable</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 d-flex align-items-end">
                                                    <div class="form-check mb-2">
                                                        <input type="checkbox" name="room_pet_friendly"
                                                               class="form-check-input"
                                                               id="petEdit<?= $r['Room_Id'] ?>"
                                                               <?= $r['Room_PetFriendly'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label fw-semibold"
                                                               for="petEdit<?= $r['Room_Id'] ?>">Pet Friendly</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <button type="submit" name="edit_room" class="btn btn-trivago">
                                                    <i class="bi bi-check-circle me-1"></i>Save Changes
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary ms-2"
                                                        data-bs-dismiss="modal">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<?php include "../layout/footer.php"; ?>