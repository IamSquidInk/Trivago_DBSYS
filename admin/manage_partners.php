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
//  ADD PARTNER
// ══════════════════════════════════════════════
if(isset($_POST['add_partner'])){
    $name               = $conn->real_escape_string($_POST['partner_name']);
    $url                = $conn->real_escape_string($_POST['partner_url']);
    $verificationStatus = $conn->real_escape_string($_POST['partner_verification']);
    $commissionRate     = (float)$_POST['partner_commission'];
    $marketplaceModel   = $conn->real_escape_string($_POST['partner_model']);
    $addedDate          = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO Booking_Partner
        (Bkprt_Name, Bkprt_WebsiteURL, Bkprt_VerificationStatus, Bkprt_CommissionRate, Bkprt_MarketplaceModel, Bkprt_PartnerAddedDate)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdss", $name, $url, $verificationStatus, $commissionRate, $marketplaceModel, $addedDate);

    if($stmt->execute()){
        $success = "Booking partner added successfully!";
    } else {
        $error = "Failed to add partner.";
    }
}

// ══════════════════════════════════════════════
//  DELETE PARTNER
// ══════════════════════════════════════════════
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM Booking_Partner WHERE Bkprt_Id = $id");
    $success = "Partner deleted successfully!";
}

// ══════════════════════════════════════════════
//  EDIT PARTNER
// ══════════════════════════════════════════════
if(isset($_POST['edit_partner'])){
    $id                 = (int)$_POST['partner_id'];
    $name               = $conn->real_escape_string($_POST['partner_name']);
    $url                = $conn->real_escape_string($_POST['partner_url']);
    $verificationStatus = $conn->real_escape_string($_POST['partner_verification']);
    $commissionRate     = (float)$_POST['partner_commission'];
    $marketplaceModel   = $conn->real_escape_string($_POST['partner_model']);

    $stmt = $conn->prepare("UPDATE Booking_Partner SET
        Bkprt_Name               = ?,
        Bkprt_WebsiteURL         = ?,
        Bkprt_VerificationStatus = ?,
        Bkprt_CommissionRate     = ?,
        Bkprt_MarketplaceModel   = ?
        WHERE Bkprt_Id           = ?");
    $stmt->bind_param("sssdsi", $name, $url, $verificationStatus, $commissionRate, $marketplaceModel, $id);

    if($stmt->execute()){
        $success = "Partner updated successfully!";
    } else {
        $error = "Failed to update partner.";
    }
}

// ── FETCH ALL PARTNERS ──
$partners = $conn->query("SELECT * FROM Booking_Partner ORDER BY Bkprt_Name ASC");

$title = "Manage Partners - trivago";
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

        <h4 style="font-weight:700; margin-bottom:4px;">Manage Booking Partners</h4>
        <p class="text-muted small mb-4">Add, edit, or remove booking partners from the platform.</p>

        <!-- ALERTS -->
        <?php if($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ADD PARTNER FORM -->
        <div class="table-card mb-4">
            <div class="table-card-title">
                <span><i class="bi bi-plus-circle me-2" style="color:var(--trivago-blue);"></i>Add New Partner</span>
            </div>

            <form method="POST">
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Partner Name</label>
                        <input type="text" name="partner_name" class="form-control"
                               placeholder="e.g. Booking.com" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Website URL</label>
                        <input type="url" name="partner_url" class="form-control"
                               placeholder="https://www.example.com" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Verification Status</label>
                        <select name="partner_verification" class="form-control" required>
                            <option value="Unverified">Unverified</option>
                            <option value="Verified">Verified</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Commission Rate (%)</label>
                        <input type="number" name="partner_commission" class="form-control"
                               min="0" max="100" step="0.01" placeholder="e.g. 12.50" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Marketplace Model</label>
                        <select name="partner_model" class="form-control" required>
                            <option value="CPC">CPC (Cost Per Click)</option>
                            <option value="CPA">CPA (Cost Per Acquisition)</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <button type="submit" name="add_partner" class="btn btn-trivago">
                            <i class="bi bi-plus-circle me-1"></i>Add Partner
                        </button>
                    </div>

                </div>
            </form>
        </div>

        <!-- PARTNERS TABLE -->
        <div class="table-card">
            <div class="table-card-title">
                <span><i class="bi bi-handshake me-2" style="color:#e6a817;"></i>All Booking Partners</span>
                <span class="badge bg-warning text-dark"><?= $partners->num_rows ?> partners</span>
            </div>

            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Website</th>
                            <th>Status</th>
                            <th>Commission</th>
                            <th>Model</th>
                            <th>Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($p = $partners->fetch_assoc()): ?>
                        <tr>
                            <td><?= $p['Bkprt_Id'] ?></td>
                            <td><strong><?= htmlspecialchars($p['Bkprt_Name']) ?></strong></td>
                            <td>
                                <a href="<?= htmlspecialchars($p['Bkprt_WebsiteURL']) ?>"
                                   target="_blank"
                                   style="color:var(--trivago-blue); font-size:12px;">
                                    <?= htmlspecialchars($p['Bkprt_WebsiteURL']) ?>
                                    <i class="bi bi-box-arrow-up-right ms-1"></i>
                                </a>
                            </td>
                            <td>
                                <span class="badge <?= $p['Bkprt_VerificationStatus'] === 'Verified' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?php if($p['Bkprt_VerificationStatus'] === 'Verified'): ?>
                                        <i class="bi bi-patch-check-fill me-1"></i>
                                    <?php endif; ?>
                                    <?= $p['Bkprt_VerificationStatus'] ?>
                                </span>
                            </td>
                            <td><?= number_format($p['Bkprt_CommissionRate'], 2) ?>%</td>
                            <td>
                                <span style="background:#fff8e3; color:#e6a817; border-radius:6px;
                                             padding:3px 10px; font-size:12px; font-weight:600;">
                                    <?= $p['Bkprt_MarketplaceModel'] ?>
                                </span>
                            </td>
                            <td><?= $p['Bkprt_PartnerAddedDate'] ?></td>
                            <td>
                                <!-- EDIT -->
                                <button class="btn btn-sm btn-outline-primary me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editPartnerModal<?= $p['Bkprt_Id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <!-- DELETE -->
                                <a href="manage_partners.php?delete=<?= $p['Bkprt_Id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete <?= htmlspecialchars($p['Bkprt_Name']) ?>? This will remove all room associations.')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>

                        <!-- EDIT MODAL -->
                        <div class="modal fade" id="editPartnerModal<?= $p['Bkprt_Id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content rounded-4">
                                    <div class="modal-header border-0">
                                        <h5 class="modal-title">Edit Partner</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST">
                                            <input type="hidden" name="partner_id" value="<?= $p['Bkprt_Id'] ?>">
                                            <div class="row g-3">

                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">Partner Name</label>
                                                    <input type="text" name="partner_name" class="form-control"
                                                           value="<?= htmlspecialchars($p['Bkprt_Name']) ?>" required>
                                                </div>

                                                <div class="col-md-8">
                                                    <label class="form-label fw-semibold">Website URL</label>
                                                    <input type="url" name="partner_url" class="form-control"
                                                           value="<?= htmlspecialchars($p['Bkprt_WebsiteURL']) ?>" required>
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">Verification Status</label>
                                                    <select name="partner_verification" class="form-control" required>
                                                        <option value="Unverified" <?= $p['Bkprt_VerificationStatus'] === 'Unverified' ? 'selected' : '' ?>>Unverified</option>
                                                        <option value="Verified"   <?= $p['Bkprt_VerificationStatus'] === 'Verified'   ? 'selected' : '' ?>>Verified</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">Commission Rate (%)</label>
                                                    <input type="number" name="partner_commission" class="form-control"
                                                           min="0" max="100" step="0.01"
                                                           value="<?= $p['Bkprt_CommissionRate'] ?>" required>
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">Marketplace Model</label>
                                                    <select name="partner_model" class="form-control" required>
                                                        <option value="CPC" <?= $p['Bkprt_MarketplaceModel'] === 'CPC' ? 'selected' : '' ?>>CPC (Cost Per Click)</option>
                                                        <option value="CPA" <?= $p['Bkprt_MarketplaceModel'] === 'CPA' ? 'selected' : '' ?>>CPA (Cost Per Acquisition)</option>
                                                    </select>
                                                </div>

                                            </div>
                                            <div class="mt-3">
                                                <button type="submit" name="edit_partner" class="btn btn-trivago">
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