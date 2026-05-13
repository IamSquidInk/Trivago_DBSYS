<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['guest_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$success = "";
$error   = "";

// ══════════════════════════════════════════════
//  ADD
// ══════════════════════════════════════════════
if (isset($_POST['add_price'])) {
    $room_id  = (int)$_POST['room_id'];
    $bkprt_id = (int)$_POST['bkprt_id'];
    $price    = floatval($_POST['price']);
    $notes    = $conn->real_escape_string($_POST['notes'] ?? '');

    $check = $conn->prepare("SELECT 1 FROM Room_Booking_Partner WHERE Rbp_RoomId = ? AND Rbp_BkprtId = ?");
    $check->bind_param("ii", $room_id, $bkprt_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "This room–partner combination already exists. Use Edit to update the price.";
    } else {
        $stmt = $conn->prepare("INSERT INTO Room_Booking_Partner (Rbp_RoomId, Rbp_BkprtId, Rbp_Price, Rbp_Notes) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iids", $room_id, $bkprt_id, $price, $notes);
        if ($stmt->execute()) $success = "Price entry added successfully!";
        else $error = "Failed to add price entry.";
        $stmt->close();
    }
    $check->close();
}

// ══════════════════════════════════════════════
//  EDIT
// ══════════════════════════════════════════════
if (isset($_POST['edit_price'])) {
    $room_id  = (int)$_POST['room_id'];
    $bkprt_id = (int)$_POST['bkprt_id'];
    $price    = floatval($_POST['price']);
    $notes    = $conn->real_escape_string($_POST['notes'] ?? '');

    $stmt = $conn->prepare("UPDATE Room_Booking_Partner SET Rbp_Price = ?, Rbp_Notes = ? WHERE Rbp_RoomId = ? AND Rbp_BkprtId = ?");
    $stmt->bind_param("dsii", $price, $notes, $room_id, $bkprt_id);
    if ($stmt->execute()) $success = "Price entry updated successfully!";
    else $error = "Failed to update price entry.";
    $stmt->close();
}

// ══════════════════════════════════════════════
//  DELETE
// ══════════════════════════════════════════════
if (isset($_GET['delete'])) {
    $room_id  = (int)$_GET['delete'];
    $bkprt_id = (int)$_GET['partner'];
    $stmt = $conn->prepare("DELETE FROM Room_Booking_Partner WHERE Rbp_RoomId = ? AND Rbp_BkprtId = ?");
    $stmt->bind_param("ii", $room_id, $bkprt_id);
    if ($stmt->execute()) $success = "Price entry deleted successfully!";
    else $error = "Failed to delete price entry.";
    $stmt->close();
}

// ══════════════════════════════════════════════
//  FETCH DATA
// ══════════════════════════════════════════════
$prices = $conn->query("
    SELECT
        rbp.Rbp_RoomId, rbp.Rbp_BkprtId, rbp.Rbp_Price, rbp.Rbp_Notes,
        h.Hotel_Name, r.Room_Type, r.Room_Capacity,
        bp.Bkprt_Name, bp.Bkprt_WebsiteURL, bp.Bkprt_VerificationStatus
    FROM Room_Booking_Partner rbp
    JOIN Room r             ON rbp.Rbp_RoomId  = r.Room_Id
    JOIN Hotel h            ON r.Room_HotelId  = h.Hotel_Id
    JOIN Booking_Partner bp ON rbp.Rbp_BkprtId = bp.Bkprt_Id
    ORDER BY h.Hotel_Name, r.Room_Type, rbp.Rbp_Price ASC
");

$roomsDropdown    = $conn->query("SELECT r.Room_Id, r.Room_Type, r.Room_Capacity, h.Hotel_Name FROM Room r JOIN Hotel h ON h.Hotel_Id = r.Room_HotelId ORDER BY h.Hotel_Name, r.Room_Type");
$partnersDropdown = $conn->query("SELECT Bkprt_Id, Bkprt_Name, Bkprt_VerificationStatus FROM Booking_Partner ORDER BY Bkprt_Name");

$rooms    = [];
while ($r = $roomsDropdown->fetch_assoc()) $rooms[] = $r;
$partners = [];
while ($p = $partnersDropdown->fetch_assoc()) $partners[] = $p;

$title = "Manage Prices - trivago";
include "../layout/header.php";
?>

<style>
    .admin-wrapper { display: flex; min-height: calc(100vh - 64px); }
    .admin-sidebar {
        width: 240px; background: #ffffff; border-right: 1px solid #e8e8e8;
        padding: 24px 16px; position: sticky; top: 64px;
        height: calc(100vh - 64px); flex-shrink: 0;
    }
    .admin-sidebar h6 { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--trivago-muted); margin-bottom: 12px; padding-left: 12px; }
    .sidebar-link { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; font-size: 14px; font-weight: 600; color: var(--trivago-text); text-decoration: none; border-left: 3px solid transparent; transition: all 0.2s ease; margin-bottom: 4px; }
    .sidebar-link:hover { background: var(--trivago-gray); color: var(--trivago-blue); }
    .sidebar-link.active-sidebar { background: #e8f0fe; color: var(--trivago-blue); border-left: 3px solid var(--trivago-blue); }
    .admin-main { flex-grow: 1; padding: 32px; background: var(--trivago-gray); }
    .table-card { background: #ffffff; border-radius: 14px; padding: 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
    .table-card-title { font-size: 16px; font-weight: 700; color: var(--trivago-dark); margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
    .admin-table th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--trivago-muted); font-weight: 600; border-bottom: 2px solid #f0f0f0; padding: 10px 12px; }
    .admin-table td { font-size: 13px; padding: 12px; vertical-align: middle; border-bottom: 1px solid #f8f8f8; }
    .status-badge { border-radius: 6px; padding: 4px 10px; font-size: 12px; font-weight: 600; }
    .verified   { background: #e6f9f0; color: #1a8c55; }
    .unverified { background: #fde8e8; color: #c0392b; }
</style>

<div class="admin-wrapper">
    <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
    <aside class="admin-sidebar">
        <h6>Admin Panel</h6>
        <a href="admin_dashboard.php" class="sidebar-link <?=$currentPage==='admin_dashboard.php'?'active-sidebar':''?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="manage_hotels.php"   class="sidebar-link <?=$currentPage==='manage_hotels.php'?'active-sidebar':''?>"><i class="bi bi-building"></i> Hotels</a>
        <a href="manage_rooms.php"    class="sidebar-link <?=$currentPage==='manage_rooms.php'?'active-sidebar':''?>"><i class="bi bi-door-open"></i> Rooms</a>
        <a href="manage_partners.php" class="sidebar-link <?=$currentPage==='manage_partners.php'?'active-sidebar':''?>"><i class="bi bi-handshake"></i> Partners</a>
        <a href="manage_prices.php"   class="sidebar-link <?=$currentPage==='manage_prices.php'?'active-sidebar':''?>"><i class="bi bi-tag"></i> Prices</a>
        <hr style="border-color:#f0f0f0; margin:16px 0;">
        <a href="../index.php"        class="sidebar-link"><i class="bi bi-house"></i> View Site</a>
        <a href="../auth/logout.php"  class="sidebar-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </aside>

    <main class="admin-main">
        <h4 style="font-weight:700; margin-bottom:4px;">Manage Prices</h4>
        <p class="text-muted small mb-4">Link rooms to booking partners with pricing.</p>

        <?php if($success): ?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif; ?>
        <?php if($error):   ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>

        <!-- ADD PRICE ENTRY -->
        <div class="table-card mb-4">
            <div class="table-card-title">
                <span><i class="bi bi-plus-circle me-2" style="color:var(--trivago-blue);"></i>Add New Price Entry</span>
            </div>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Room</label>
                        <select name="room_id" class="form-control" required>
                            <option value="">-- Select Room --</option>
                            <?php foreach ($rooms as $r): ?>
                                <option value="<?=$r['Room_Id']?>">
                                    <?=htmlspecialchars($r['Hotel_Name'])?> — <?=htmlspecialchars($r['Room_Type'])?> (ID: <?=$r['Room_Id']?>, <?=$r['Room_Capacity']?> guests)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Booking Partner</label>
                        <select name="bkprt_id" class="form-control" required>
                            <option value="">-- Select Partner --</option>
                            <?php foreach ($partners as $p): ?>
                                <option value="<?=$p['Bkprt_Id']?>">
                                    <?=htmlspecialchars($p['Bkprt_Name'])?><?=$p['Bkprt_VerificationStatus']==='Verified'?' ✓':''?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Price / Night (₱)</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" placeholder="e.g. 2500.00" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Notes <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Free cancellation">
                    </div>
                    <div class="col-12">
                        <button type="submit" name="add_price" class="btn btn-trivago">
                            <i class="bi bi-plus-circle me-1"></i>Add Price Entry
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- PRICES TABLE -->
        <div class="table-card">
            <div class="table-card-title">
                <span><i class="bi bi-tag me-2" style="color:#1a8c55;"></i>All Price Entries</span>
                <span class="badge bg-success"><?=$prices->num_rows?> <?=$prices->num_rows===1?'entry':'entries'?></span>
            </div>
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Hotel</th>
                            <th>Room</th>
                            <th>Booking Partner</th>
                            <th>Price / Night</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($prices && $prices->num_rows > 0):
                        $prices->data_seek(0);
                        while ($row = $prices->fetch_assoc()):
                    ?>
                        <tr>
                            <td><strong><?=htmlspecialchars($row['Hotel_Name'])?></strong></td>
                            <td>
                                <span style="background:#e8f0fe; color:var(--trivago-blue); border-radius:6px; padding:3px 10px; font-size:12px; font-weight:600;">
                                    <?=htmlspecialchars($row['Room_Type'])?>
                                </span><br>
                                <small class="text-muted">
                                    <i class="bi bi-people me-1"></i><?=$row['Room_Capacity']?> guests &nbsp;·&nbsp; ID #<?=$row['Rbp_RoomId']?>
                                </small>
                            </td>
                            <td>
                                <a href="<?=htmlspecialchars($row['Bkprt_WebsiteURL'])?>" target="_blank"
                                   style="color:var(--trivago-blue); font-weight:600; text-decoration:none;">
                                    <?=htmlspecialchars($row['Bkprt_Name'])?>
                                    <i class="bi bi-box-arrow-up-right" style="font-size:11px; margin-left:3px;"></i>
                                </a><br>
                                <span class="status-badge <?=$row['Bkprt_VerificationStatus']==='Verified'?'verified':'unverified'?>">
                                    <?php if ($row['Bkprt_VerificationStatus'] === 'Verified'): ?>
                                        <i class="bi bi-patch-check-fill"></i> Verified
                                    <?php else: ?>
                                        <i class="bi bi-x-circle"></i> Unverified
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color:var(--trivago-blue); font-size:14px;">
                                    ₱<?=number_format($row['Rbp_Price'], 2)?>
                                </strong>
                            </td>
                            <td style="color:var(--trivago-muted); font-size:13px;">
                                <?=$row['Rbp_Notes'] ? htmlspecialchars($row['Rbp_Notes']) : '<span style="opacity:0.35">—</span>'?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-1"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal_<?=$row['Rbp_RoomId']?>_<?=$row['Rbp_BkprtId']?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="manage_prices.php?delete=<?=$row['Rbp_RoomId']?>&partner=<?=$row['Rbp_BkprtId']?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete this price entry?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>

                        <!-- EDIT MODAL (per row, same pattern as manage_rooms) -->
                        <div class="modal fade" id="editModal_<?=$row['Rbp_RoomId']?>_<?=$row['Rbp_BkprtId']?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-4">
                                    <div class="modal-header border-0">
                                        <h5 class="modal-title">Edit Price Entry</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST">
                                            <input type="hidden" name="room_id"  value="<?=$row['Rbp_RoomId']?>">
                                            <input type="hidden" name="bkprt_id" value="<?=$row['Rbp_BkprtId']?>">
                                            <!-- Read-only context -->
                                            <div class="mb-3 p-3" style="background:#f8f9ff; border-radius:8px; border:1px solid #e8eeff;">
                                                <div class="text-muted small fw-semibold mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">Room</div>
                                                <div class="fw-semibold" style="font-size:13px;"><?=htmlspecialchars($row['Hotel_Name'])?> — <?=htmlspecialchars($row['Room_Type'])?></div>
                                                <div class="text-muted small fw-semibold mt-2 mb-1" style="font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">Partner</div>
                                                <div class="fw-semibold" style="font-size:13px;"><?=htmlspecialchars($row['Bkprt_Name'])?></div>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Price / Night (₱)</label>
                                                    <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?=$row['Rbp_Price']?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Notes <span class="text-muted fw-normal">(optional)</span></label>
                                                    <input type="text" name="notes" class="form-control" value="<?=htmlspecialchars($row['Rbp_Notes'] ?? '')?>">
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <button type="submit" name="edit_price" class="btn btn-trivago">
                                                    <i class="bi bi-check-circle me-1"></i>Save Changes
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary ms-2" data-bs-dismiss="modal">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-tag" style="font-size:2rem; display:block; margin-bottom:8px; opacity:0.3;"></i>
                                No price entries yet. Use the form above to add one.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<?php include "../layout/footer.php"; ?>