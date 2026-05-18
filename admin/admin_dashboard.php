<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['guest_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// ══════════════════════════════════════════════
//  HANDLE APPROVE / DENY
// ══════════════════════════════════════════════
$actionSuccess = '';
$actionError   = '';

if (isset($_POST['approve'])) {
    $reqId    = (int)$_POST['req_id'];
    $guestId  = (int)$_POST['guest_id'];

    $conn->query("UPDATE Verification_Request SET Vreq_Status = 'Approved' WHERE Vreq_Id = $reqId");
    $conn->query("UPDATE Guest SET Guest_VerifiedEmail = 1, Guest_MemberStatus = 'Member' WHERE Guest_Id = $guestId");
    $actionSuccess = "Verification approved. Guest has been upgraded to Member.";
}

if (isset($_POST['deny'])) {
    $reqId   = (int)$_POST['req_id'];
    $conn->query("UPDATE Verification_Request SET Vreq_Status = 'Denied' WHERE Vreq_Id = $reqId");
    $actionSuccess = "Verification request denied.";
}

// ── COUNTS ──
$totalHotels   = $conn->query("SELECT COUNT(*) AS cnt FROM Hotel")->fetch_assoc()['cnt'];
$totalRooms    = $conn->query("SELECT COUNT(*) AS cnt FROM Room")->fetch_assoc()['cnt'];
$totalPartners = $conn->query("SELECT COUNT(*) AS cnt FROM Booking_Partner")->fetch_assoc()['cnt'];
$totalGuests   = $conn->query("SELECT COUNT(*) AS cnt FROM Guest")->fetch_assoc()['cnt'];

// ── PENDING VERIFICATION REQUESTS ──
$pendingRequests = $conn->query("
    SELECT vr.Vreq_Id, vr.Vreq_CreatedAt,
           g.Guest_Id, g.Guest_Name, g.Guest_Email, g.Guest_MemberStatus
    FROM Verification_Request vr
    JOIN Guest g ON vr.Vreq_GuestId = g.Guest_Id
    WHERE vr.Vreq_Status = 'Pending'
    ORDER BY vr.Vreq_CreatedAt ASC
");
$pendingCount = $pendingRequests ? $pendingRequests->num_rows : 0;

// ── RECENT DATA ──
$recentHotels = $conn->query("SELECT * FROM Hotel ORDER BY Hotel_AddedDate DESC LIMIT 5");
$recentGuests = $conn->query("SELECT * FROM Guest ORDER BY Guest_CreatedDate DESC LIMIT 5");

$title = "Admin Dashboard - trivago";
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

    .stat-card { background: #ffffff; border-radius: 14px; padding: 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); display: flex; align-items: center; gap: 16px; transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.09); }

    .stat-icon { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
    .stat-count { font-size: 28px; font-weight: 700; color: var(--trivago-dark); line-height: 1; margin-bottom: 4px; }
    .stat-label { font-size: 13px; color: var(--trivago-muted); margin: 0; }

    .table-card { background: #ffffff; border-radius: 14px; padding: 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
    .table-card-title { font-size: 16px; font-weight: 700; color: var(--trivago-dark); margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; justify-content: space-between; }

    .admin-table th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--trivago-muted); font-weight: 600; border-bottom: 2px solid #f0f0f0; padding: 10px 12px; }
    .admin-table td { font-size: 13px; padding: 12px; vertical-align: middle; border-bottom: 1px solid #f8f8f8; }
    .admin-table tbody tr:last-child td { border-bottom: none; }

    /* Verification request row */
    .vreq-row { background: #fffdf4; }
    .vreq-row:hover { background: #fffbe8; }

    .btn-approve { background: #1a8c55; color: #fff; border: none; border-radius: 6px; padding: 5px 14px; font-size: 12px; font-weight: 700; cursor: pointer; transition: background 0.2s; }
    .btn-approve:hover { background: #156e43; }

    .btn-deny { background: #fde8e8; color: #c0392b; border: none; border-radius: 6px; padding: 5px 14px; font-size: 12px; font-weight: 700; cursor: pointer; transition: background 0.2s; }
    .btn-deny:hover { background: #f9c9c5; }

    .pending-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #e6a817; margin-right: 6px; animation: pulse 1.5s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
</style>

<div class="admin-wrapper">
    <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
    <aside class="admin-sidebar">
        <h6>Admin Panel</h6>
        <a href="admin_dashboard.php" class="sidebar-link <?= $currentPage === 'admin_dashboard.php' ? 'active-sidebar' : '' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="manage_hotels.php"   class="sidebar-link <?= $currentPage === 'manage_hotels.php'   ? 'active-sidebar' : '' ?>"><i class="bi bi-building"></i> Hotels</a>
        <a href="manage_rooms.php"    class="sidebar-link <?= $currentPage === 'manage_rooms.php'    ? 'active-sidebar' : '' ?>"><i class="bi bi-door-open"></i> Rooms</a>
        <a href="manage_partners.php" class="sidebar-link <?= $currentPage === 'manage_partners.php' ? 'active-sidebar' : '' ?>"><i class="bi bi-handshake"></i> Partners</a>
        <a href="manage_prices.php"   class="sidebar-link <?= $currentPage === 'manage_prices.php'   ? 'active-sidebar' : '' ?>"><i class="bi bi-tag"></i> Prices</a>
        <hr style="border-color:#f0f0f0; margin:16px 0;">
        <a href="../index.php"        class="sidebar-link"><i class="bi bi-house"></i> View Site</a>
        <a href="../auth/logout.php"  class="sidebar-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </aside>

    <main class="admin-main">
        <h4 style="font-weight:700; margin-bottom:4px;">Dashboard</h4>
        <p class="text-muted small mb-4">Welcome back, <?= htmlspecialchars($_SESSION['guest_name'] ?? 'Admin') ?>!</p>

        <?php if ($actionSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($actionSuccess) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- STAT CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e8f0fe;">
                        <i class="bi bi-building" style="color:var(--trivago-blue);"></i>
                    </div>
                    <div>
                        <p class="stat-count"><?= $totalHotels ?></p>
                        <p class="stat-label">Total Hotels</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e6f9f0;">
                        <i class="bi bi-door-open" style="color:#1a8c55;"></i>
                    </div>
                    <div>
                        <p class="stat-count"><?= $totalRooms ?></p>
                        <p class="stat-label">Total Rooms</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fff8e3;">
                        <i class="bi bi-handshake" style="color:#e6a817;"></i>
                    </div>
                    <div>
                        <p class="stat-count"><?= $totalPartners ?></p>
                        <p class="stat-label">Booking Partners</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#f3e3ff;">
                        <i class="bi bi-people" style="color:#8e44ad;"></i>
                    </div>
                    <div>
                        <p class="stat-count"><?= $totalGuests ?></p>
                        <p class="stat-label">Registered Guests</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- VERIFICATION REQUESTS -->
        <div class="table-card mb-4">
            <div class="table-card-title">
                <span>
                    <?php if ($pendingCount > 0): ?>
                        <span class="pending-dot"></span>
                    <?php endif; ?>
                    <i class="bi bi-envelope-check me-2" style="color:#e6a817;"></i>
                    Verification Requests
                </span>
                <span class="badge <?= $pendingCount > 0 ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                    <?= $pendingCount ?> pending
                </span>
            </div>

            <?php if ($pendingCount > 0): ?>
            <table class="table admin-table mb-0">
                <thead>
                    <tr>
                        <th>Guest</th>
                        <th>Email</th>
                        <th>Current Status</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($req = $pendingRequests->fetch_assoc()): ?>
                    <tr class="vreq-row">
                        <td><strong><?= htmlspecialchars($req['Guest_Name']) ?></strong></td>
                        <td style="font-size:12px;"><?= htmlspecialchars($req['Guest_Email']) ?></td>
                        <td>
                            <span class="badge bg-secondary"><?= htmlspecialchars($req['Guest_MemberStatus']) ?></span>
                        </td>
                        <td style="font-size:12px; color:var(--trivago-muted);">
                            <?= date('M j, Y · g:i A', strtotime($req['Vreq_CreatedAt'])) ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="req_id"   value="<?= $req['Vreq_Id'] ?>">
                                <input type="hidden" name="guest_id" value="<?= $req['Guest_Id'] ?>">
                                <button type="submit" name="approve" class="btn-approve me-1"
                                        onclick="return confirm('Approve verification for <?= htmlspecialchars(addslashes($req['Guest_Name'])) ?>?')">
                                    <i class="bi bi-check-lg"></i> Approve
                                </button>
                                <button type="submit" name="deny" class="btn-deny"
                                        onclick="return confirm('Deny verification for <?= htmlspecialchars(addslashes($req['Guest_Name'])) ?>?')">
                                    <i class="bi bi-x-lg"></i> Deny
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div style="text-align:center; padding:32px 20px; color:var(--trivago-muted);">
                    <i class="bi bi-envelope-check" style="font-size:2rem; opacity:0.3; display:block; margin-bottom:8px;"></i>
                    <p style="margin:0; font-size:13px;">No pending verification requests.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- RECENT TABLES -->
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="table-card">
                    <p class="table-card-title">
                        <i class="bi bi-building me-2" style="color:var(--trivago-blue);"></i>
                        Recently Added Hotels
                    </p>
                    <table class="table admin-table mb-0">
                        <thead>
                            <tr><th>Hotel</th><th>City</th><th>Rating</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($h = $recentHotels->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($h['Hotel_Name']) ?></td>
                                <td><?= htmlspecialchars($h['Hotel_City']) ?></td>
                                <td>
                                    <?php for ($s = 1; $s <= $h['Hotel_Rating']; $s++): ?>
                                        <i class="bi bi-star-fill" style="color:#f5a623; font-size:11px;"></i>
                                    <?php endfor; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="table-card">
                    <p class="table-card-title">
                        <i class="bi bi-people me-2" style="color:#8e44ad;"></i>
                        Recently Registered Guests
                    </p>
                    <table class="table admin-table mb-0">
                        <thead>
                            <tr><th>Name</th><th>Email</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($g = $recentGuests->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($g['Guest_Name']) ?></td>
                                <td style="font-size:12px;"><?= htmlspecialchars($g['Guest_Email']) ?></td>
                                <td>
                                    <span class="badge <?= $g['Guest_MemberStatus'] === 'Member' ? 'bg-primary' : 'bg-secondary' ?>">
                                        <?= $g['Guest_MemberStatus'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<?php include "../layout/footer.php"; ?>