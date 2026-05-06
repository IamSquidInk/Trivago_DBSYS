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
        Room_HotelId     = ?,
        Room_Type        = ?,
        Room_Capacity    = ?,
        Room_PetFriendly = ?,
        Room_Availability = ?
        WHERE Room_Id    = ?");
    $stmt->bind_param("isiisi", $hotelId, $type, $capacity, $petFriendly, $availability, $id);

    if($stmt->execute()){
        $success = "Room updated successfully!";
    } else {
        $error = "Failed to update room.";
    }
}

// ── FETCH ALL ROOMS WITH HOTEL NAME ──
$rooms = $conn->query("
    SELECT r.*, h.Hotel_Name 
    FROM Room r
    JOIN Hotel h ON h.Hotel_Id = r.Room_HotelId
    ORDER BY h.Hotel_Name, r.Room_Type
");

// ── FETCH HOTELS FOR DROPDOWN ──
$hotelsDropdown = $conn->query("SELECT Hotel_Id, Hotel_Name FROM Hotel ORDER BY Hotel_Name");

$title = "Manage Rooms - trivago";
include "../layout/header.php";
?>

<style>
    .admin-wrapper {
        display: flex;
        min-height: calc(100vh - 64px);
    }

    .admin-sidebar {
        width: 240px;
        background: #ffffff;
        border-right: 1px solid #e8e8e8;
        padding: 24px 16px;
        position: sticky;
        top: 64px;
        height: calc(100vh - 64px);
        flex-shrink: 0;
    }

    .admin-sidebar h6 {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--trivago-muted);
        margin-bottom: 12px;
        padding-left: 12px;
    }

    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        color: var(--trivago-text);
        text-decoration: none;
        border-left: 3px solid transparent;
        transition: all 0.2s ease;
        margin-bottom: 4px;
    }

    .sidebar-link:hover {
        background: var(--trivago-gray);
        color: var(--trivago-blue);
    }

    .sidebar-link.active-sidebar {
        background: #e8f0fe;
        color: var(--trivago-blue);
        border-left: 3px solid var(--trivago-blue);
    }

    .admin-main {
        flex-grow: 1;
        padding: 32px;
        background: var(--trivago-gray);
    }

    .table-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 24px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    }

    .table-card-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--trivago-dark);
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .admin-table th {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--trivago-muted);
        font-weight: 600;
        border-bottom: 2px solid #f0f0f0;
        padding: 10px 12px;
    }

    .admin-table td {
        font-size: 13px;
        padding: 12px;
        vertical-align: middle;
        border-bottom: 1px solid #f8f8f8;
    }

    .availability-badge {
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 600;
    }

    .available   { background: #e6f9f0; color: #1a8c55; }
    .unavailable { background: #fde8e8; color: #c0392b; }
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
        <p class="text-muted small mb-4">Add, edit, or remove rooms from hotels.</p>

        <!-- ALERTS -->
        <?php if($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ADD ROOM FORM -->
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
                            <option value="Single">Single</option>
                            <option value="Double">Double</option>
                            <option value="Twin">Twin</option>
                            <option value="Suite">Suite</option>
                            <option value="Deluxe">Deluxe</option>
                            <option value="Family">Family</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Capacity (guests)</label>
                        <input type="number" name="room_capacity" class="form-control"
                               min="1" max="20" value="2" required>
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
                            <input type="checkbox" name="room_pet_friendly"
                                   class="form-check-input" id="petFriendlyAdd">
                            <label class="form-check-label fw-semibold" for="petFriendlyAdd">
                                Pet Friendly
                            </label>
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($r = $rooms->fetch_assoc()): ?>
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
                                <!-- EDIT -->
                                <button class="btn btn-sm btn-outline-primary me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editRoomModal<?= $r['Room_Id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <!-- DELETE -->
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
                                                               for="petEdit<?= $r['Room_Id'] ?>">
                                                            Pet Friendly
                                                        </label>
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