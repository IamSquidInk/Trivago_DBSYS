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
//  ADD HOTEL
// ══════════════════════════════════════════════
if(isset($_POST['add_hotel'])){
    $name        = $conn->real_escape_string($_POST['hotel_name']);
    $address     = $conn->real_escape_string($_POST['hotel_address']);
    $city        = $conn->real_escape_string($_POST['hotel_city']);
    $country     = $conn->real_escape_string($_POST['hotel_country']);
    $rating      = (int)$_POST['hotel_rating'];
    $description = $conn->real_escape_string($_POST['hotel_description']);
    $addedDate   = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO Hotel 
        (Hotel_Name, Hotel_Address, Hotel_City, Hotel_Country, Hotel_Rating, Hotel_Description, Hotel_AddedDate)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssiss", $name, $address, $city, $country, $rating, $description, $addedDate);

    if($stmt->execute()){
        $success = "Hotel added successfully!";
    } else {
        $error = "Failed to add hotel.";
    }
}

// ══════════════════════════════════════════════
//  DELETE HOTEL
// ══════════════════════════════════════════════
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM Hotel WHERE Hotel_Id = $id");
    $success = "Hotel deleted successfully!";
}

// ══════════════════════════════════════════════
//  EDIT HOTEL
// ══════════════════════════════════════════════
if(isset($_POST['edit_hotel'])){
    $id          = (int)$_POST['hotel_id'];
    $name        = $conn->real_escape_string($_POST['hotel_name']);
    $address     = $conn->real_escape_string($_POST['hotel_address']);
    $city        = $conn->real_escape_string($_POST['hotel_city']);
    $country     = $conn->real_escape_string($_POST['hotel_country']);
    $rating      = (int)$_POST['hotel_rating'];
    $description = $conn->real_escape_string($_POST['hotel_description']);

    $stmt = $conn->prepare("UPDATE Hotel SET
        Hotel_Name        = ?,
        Hotel_Address     = ?,
        Hotel_City        = ?,
        Hotel_Country     = ?,
        Hotel_Rating      = ?,
        Hotel_Description = ?
        WHERE Hotel_Id    = ?");
    $stmt->bind_param("ssssisi", $name, $address, $city, $country, $rating, $description, $id);

    if($stmt->execute()){
        $success = "Hotel updated successfully!";
    } else {
        $error = "Failed to update hotel.";
    }
}

// ── FETCH ALL HOTELS ──
$hotels = $conn->query("SELECT * FROM Hotel ORDER BY Hotel_AddedDate DESC");

$title = "Manage Hotels - trivago";
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

        <h4 style="font-weight:700; margin-bottom:4px;">Manage Hotels</h4>
        <p class="text-muted small mb-4">Add, edit, or remove hotels from the platform.</p>

        <!-- ALERTS -->
        <?php if($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ADD HOTEL FORM -->
        <div class="table-card mb-4">
            <div class="table-card-title">
                <span><i class="bi bi-plus-circle me-2" style="color:var(--trivago-blue);"></i>Add New Hotel</span>
            </div>

            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Hotel Name</label>
                        <input type="text" name="hotel_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Address</label>
                        <input type="text" name="hotel_address" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">City</label>
                        <input type="text" name="hotel_city" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Country</label>
                        <input type="text" name="hotel_country" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Star Rating</label>
                        <select name="hotel_rating" class="form-control" required>
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?> Star<?= $i > 1 ? 's' : '' ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="hotel_description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="add_hotel" class="btn btn-trivago">
                            <i class="bi bi-plus-circle me-1"></i>Add Hotel
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- HOTELS TABLE -->
        <div class="table-card">
            <div class="table-card-title">
                <span><i class="bi bi-building me-2" style="color:var(--trivago-blue);"></i>All Hotels</span>
                <span class="badge bg-primary"><?= $hotels->num_rows ?> hotels</span>
            </div>

            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>City</th>
                            <th>Country</th>
                            <th>Rating</th>
                            <th>Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($h = $hotels->fetch_assoc()): ?>
                        <tr>
                            <td><?= $h['Hotel_Id'] ?></td>
                            <td><?= htmlspecialchars($h['Hotel_Name']) ?></td>
                            <td><?= htmlspecialchars($h['Hotel_City']) ?></td>
                            <td><?= htmlspecialchars($h['Hotel_Country']) ?></td>
                            <td>
                                <?php for($s = 1; $s <= $h['Hotel_Rating']; $s++): ?>
                                    <i class="bi bi-star-fill" style="color:#f5a623; font-size:11px;"></i>
                                <?php endfor; ?>
                            </td>
                            <td><?= $h['Hotel_AddedDate'] ?></td>
                            <td>
                                <!-- EDIT BUTTON -->
                                <button class="btn btn-sm btn-outline-primary me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editModal<?= $h['Hotel_Id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <!-- DELETE BUTTON -->
                                <a href="manage_hotels.php?delete=<?= $h['Hotel_Id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete <?= htmlspecialchars($h['Hotel_Name']) ?>? This will also delete its rooms.')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>

                        <!-- EDIT MODAL -->
                        <div class="modal fade" id="editModal<?= $h['Hotel_Id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content rounded-4">
                                    <div class="modal-header border-0">
                                        <h5 class="modal-title">Edit Hotel</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST">
                                            <input type="hidden" name="hotel_id" value="<?= $h['Hotel_Id'] ?>">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Hotel Name</label>
                                                    <input type="text" name="hotel_name" class="form-control"
                                                           value="<?= htmlspecialchars($h['Hotel_Name']) ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Address</label>
                                                    <input type="text" name="hotel_address" class="form-control"
                                                           value="<?= htmlspecialchars($h['Hotel_Address']) ?>" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">City</label>
                                                    <input type="text" name="hotel_city" class="form-control"
                                                           value="<?= htmlspecialchars($h['Hotel_City']) ?>" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">Country</label>
                                                    <input type="text" name="hotel_country" class="form-control"
                                                           value="<?= htmlspecialchars($h['Hotel_Country']) ?>" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">Star Rating</label>
                                                    <select name="hotel_rating" class="form-control" required>
                                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                                            <option value="<?= $i ?>" <?= $i == $h['Hotel_Rating'] ? 'selected' : '' ?>>
                                                                <?= $i ?> Star<?= $i > 1 ? 's' : '' ?>
                                                            </option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold">Description</label>
                                                    <textarea name="hotel_description" class="form-control" rows="3" required><?= htmlspecialchars($h['Hotel_Description']) ?></textarea>
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <button type="submit" name="edit_hotel" class="btn btn-trivago">
                                                    <i class="bi bi-check-circle me-1"></i>Save Changes
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary ms-2" data-bs-dismiss="modal">
                                                    Cancel
                                                </button>
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