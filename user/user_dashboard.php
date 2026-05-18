<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['guest_id'])) {
    header("Location: /trivago/auth/login.php");
    exit();
}

$guestId   = (int)$_SESSION['guest_id'];
$guestName = $_SESSION['guest_name'] ?? 'User';

$verifySuccess = '';
$verifyError   = '';

// ══════════════════════════════════════════════
//  HANDLE VERIFICATION REQUEST
// ══════════════════════════════════════════════
if (isset($_POST['request_verification'])) {
    $already = $conn->query("SELECT Guest_VerifiedEmail FROM Guest WHERE Guest_Id = $guestId")->fetch_assoc();

    if ($already['Guest_VerifiedEmail'] == 1) {
        $verifyError = "Your email is already verified.";
    } else {
        // Check if a pending request already exists
        $existing = $conn->query("SELECT Vreq_Id FROM Verification_Request WHERE Vreq_GuestId = $guestId AND Vreq_Status = 'Pending'");
        if ($existing->num_rows > 0) {
            $verifyError = "You already have a pending verification request.";
        } else {
            $conn->query("INSERT INTO Verification_Request (Vreq_GuestId) VALUES ($guestId)");
            $verifySuccess = "Verification request sent! An admin will review it shortly.";
        }
    }
}

// ══════════════════════════════════════════════
//  FETCH GUEST INFO
// ══════════════════════════════════════════════
$guestInfo = $conn->query("
    SELECT Guest_Name, Guest_Email, Guest_MemberStatus, Guest_VerifiedEmail, Guest_CreatedDate
    FROM Guest WHERE Guest_Id = $guestId
")->fetch_assoc();

// ══════════════════════════════════════════════
//  CHECK PENDING VERIFICATION REQUEST
// ══════════════════════════════════════════════
$pendingRequest = $conn->query("
    SELECT Vreq_Status, Vreq_CreatedAt FROM Verification_Request
    WHERE Vreq_GuestId = $guestId
    ORDER BY Vreq_CreatedAt DESC
    LIMIT 1
")->fetch_assoc();

// ══════════════════════════════════════════════
//  FETCH SEARCH HISTORY
// ══════════════════════════════════════════════
$searchHistory = $conn->query("
    SELECT * FROM Search_Query
    WHERE Schqr_GuestId = $guestId
    ORDER BY Schqr_TimeStamp DESC
    LIMIT 10
");
$searchCount = $searchHistory ? $searchHistory->num_rows : 0;

// ══════════════════════════════════════════════
//  FETCH BOOKING HISTORY (graceful if no table)
// ══════════════════════════════════════════════
$bookings     = null;
$bookingCount = 0;
$tableCheck   = $conn->query("SHOW TABLES LIKE 'Booking'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $bookings = $conn->query("
        SELECT
            b.Booking_Id, b.Booking_CheckIn, b.Booking_CheckOut,
            b.Booking_TotalPrice, b.Booking_Status, b.Booking_Date,
            h.Hotel_Name, h.Hotel_City, h.Hotel_Country,
            r.Room_Type, r.Room_Capacity,
            bp.Bkprt_Name
        FROM Booking b
        JOIN Room r             ON b.Booking_RoomId  = r.Room_Id
        JOIN Hotel h            ON r.Room_HotelId    = h.Hotel_Id
        JOIN Booking_Partner bp ON b.Booking_BkprtId = bp.Bkprt_Id
        WHERE b.Booking_GuestId = $guestId
        ORDER BY b.Booking_Date DESC
    ");
    $bookingCount = $bookings ? $bookings->num_rows : 0;
}

$isVerified = (bool)$guestInfo['Guest_VerifiedEmail'];

$title = "My Dashboard - trivago";
include "../layout/header.php";
?>

<style>
    .dashboard-wrapper {
        max-width: 960px;
        margin: 0 auto;
        padding: 36px 20px 60px;
    }

    .welcome-banner {
        background: linear-gradient(135deg, #007aff 0%, #0055cc 100%);
        border-radius: 16px;
        padding: 28px 32px;
        color: #fff;
        margin-bottom: 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .welcome-banner h4 { font-size: 22px; font-weight: 700; margin: 0 0 4px; }
    .welcome-banner p  { margin: 0; font-size: 14px; opacity: 0.85; }

    .member-badge {
        background: rgba(255,255,255,0.2);
        border: 1.5px solid rgba(255,255,255,0.4);
        border-radius: 20px;
        padding: 5px 14px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .stat-row { display: flex; gap: 14px; margin-bottom: 28px; flex-wrap: wrap; }

    .stat-chip {
        background: #ffffff;
        border-radius: 12px;
        padding: 18px 24px;
        flex: 1;
        min-width: 140px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .stat-chip-icon {
        width: 44px; height: 44px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; flex-shrink: 0;
    }

    .stat-chip-value { font-size: 22px; font-weight: 700; color: var(--trivago-dark); line-height: 1; margin-bottom: 2px; }
    .stat-chip-label { font-size: 12px; color: var(--trivago-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }

    .dash-card { background: #ffffff; border-radius: 14px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); margin-bottom: 24px; overflow: hidden; }

    .dash-card-header {
        padding: 18px 24px; border-bottom: 1px solid #f0f0f0;
        display: flex; align-items: center; justify-content: space-between;
    }

    .dash-card-header h6 { font-size: 15px; font-weight: 700; color: var(--trivago-dark); margin: 0; }
    .dash-card-body { padding: 20px 24px; }

    .profile-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 10px 0; border-bottom: 1px solid #f8f8f8; font-size: 14px;
    }
    .profile-row:last-child { border-bottom: none; }
    .profile-label { color: var(--trivago-muted); font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.04em; }
    .profile-value { font-weight: 600; color: var(--trivago-dark); }

    .history-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 0; border-bottom: 1px solid #f8f8f8; gap: 12px; flex-wrap: wrap;
    }
    .history-row:last-child { border-bottom: none; }
    .history-dest  { font-weight: 700; color: var(--trivago-dark); font-size: 14px; }
    .history-meta  { font-size: 12px; color: var(--trivago-muted); margin-top: 2px; }
    .history-tag   { background: #e8f0fe; color: var(--trivago-blue); border-radius: 6px; padding: 3px 10px; font-size: 11px; font-weight: 700; white-space: nowrap; }

    .booking-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 0; border-bottom: 1px solid #f8f8f8; gap: 12px; flex-wrap: wrap;
    }
    .booking-row:last-child { border-bottom: none; }
    .booking-hotel { font-weight: 700; color: var(--trivago-dark); font-size: 14px; margin-bottom: 2px; }
    .booking-meta  { font-size: 12px; color: var(--trivago-muted); }
    .booking-price { font-weight: 700; color: var(--trivago-blue); font-size: 15px; text-align: right; white-space: nowrap; }
    .booking-price-label { font-size: 11px; color: var(--trivago-muted); text-align: right; }

    .status-badge-confirmed { background: #e6f9f0; color: #1a8c55; border-radius: 6px; padding: 3px 10px; font-size: 11px; font-weight: 700; }
    .status-badge-pending   { background: #fff8e3; color: #b8860b; border-radius: 6px; padding: 3px 10px; font-size: 11px; font-weight: 700; }
    .status-badge-cancelled { background: #fde8e8; color: #c0392b; border-radius: 6px; padding: 3px 10px; font-size: 11px; font-weight: 700; }

    .btn-verify {
        background: #1a8c55; color: #fff; border: none; border-radius: 8px;
        padding: 9px 20px; font-size: 13px; font-weight: 700; cursor: pointer;
        transition: background 0.2s; display: inline-flex; align-items: center; gap: 7px;
    }
    .btn-verify:hover { background: #156e43; }

    .verified-chip {
        background: #e6f9f0; color: #1a8c55; border-radius: 8px;
        padding: 9px 16px; font-size: 13px; font-weight: 700;
        display: inline-flex; align-items: center; gap: 7px;
    }

    .pending-chip {
        background: #fff8e3; color: #b8860b; border-radius: 8px;
        padding: 9px 16px; font-size: 13px; font-weight: 700;
        display: inline-flex; align-items: center; gap: 7px;
    }

    .denied-chip {
        background: #fde8e8; color: #c0392b; border-radius: 8px;
        padding: 9px 16px; font-size: 13px; font-weight: 700;
        display: inline-flex; align-items: center; gap: 7px;
    }

    .empty-state { text-align: center; padding: 36px 20px; color: var(--trivago-muted); }
    .empty-state i { font-size: 2.2rem; opacity: 0.3; display: block; margin-bottom: 10px; }
    .empty-state p { margin: 0; font-size: 13px; }
</style>

<div class="dashboard-wrapper">

    <?php if ($verifySuccess): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($verifySuccess) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($verifyError): ?>
        <div class="alert alert-warning alert-dismissible fade show mb-4">
            <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($verifyError) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Welcome banner -->
    <div class="welcome-banner">
        <div>
            <h4>Hi, <?= htmlspecialchars($guestName) ?>! 👋</h4>
            <p>Welcome to your trivago dashboard. Here's your activity at a glance.</p>
        </div>
        <span class="member-badge">
            <i class="bi bi-person-check me-1"></i>
            <?= htmlspecialchars($guestInfo['Guest_MemberStatus']) ?>
        </span>
    </div>

    <!-- Stat chips -->
    <div class="stat-row">
        <div class="stat-chip">
            <div class="stat-chip-icon" style="background:#e8f0fe;">
                <i class="bi bi-search" style="color:var(--trivago-blue);"></i>
            </div>
            <div>
                <div class="stat-chip-value"><?= $searchCount ?></div>
                <div class="stat-chip-label">Searches</div>
            </div>
        </div>
        <div class="stat-chip">
            <div class="stat-chip-icon" style="background:#e6f9f0;">
                <i class="bi bi-calendar-check" style="color:#1a8c55;"></i>
            </div>
            <div>
                <div class="stat-chip-value"><?= $bookingCount ?></div>
                <div class="stat-chip-label">Bookings</div>
            </div>
        </div>
        <div class="stat-chip">
            <div class="stat-chip-icon" style="background:<?= $isVerified ? '#e6f9f0' : '#fde8e8' ?>;">
                <i class="bi <?= $isVerified ? 'bi-patch-check-fill' : 'bi-envelope-exclamation' ?>"
                   style="color:<?= $isVerified ? '#1a8c55' : '#c0392b' ?>;"></i>
            </div>
            <div>
                <div class="stat-chip-value" style="font-size:14px; padding-top:4px;">
                    <?= $isVerified ? 'Verified' : 'Unverified' ?>
                </div>
                <div class="stat-chip-label">Email</div>
            </div>
        </div>
        <?php if ($isVerified): ?>
        <div class="stat-chip">
            <div class="stat-chip-icon" style="background:#f3e3ff;">
                <i class="bi bi-calendar3" style="color:#8e44ad;"></i>
            </div>
            <div>
                <div class="stat-chip-value" style="font-size:13px; padding-top:4px;">
                    <?= date('M Y', strtotime($guestInfo['Guest_CreatedDate'])) ?>
                </div>
                <div class="stat-chip-label">Member Since</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">

            <!-- Profile -->
            <div class="dash-card mb-4">
                <div class="dash-card-header">
                    <h6><i class="bi bi-person me-2" style="color:var(--trivago-blue);"></i>My Profile</h6>
                </div>
                <div class="dash-card-body">
                    <div class="profile-row">
                        <span class="profile-label">Name</span>
                        <span class="profile-value"><?= htmlspecialchars($guestInfo['Guest_Name']) ?></span>
                    </div>
                    <div class="profile-row">
                        <span class="profile-label">Email</span>
                        <span class="profile-value" style="font-size:13px; word-break:break-all;">
                            <?= htmlspecialchars($guestInfo['Guest_Email']) ?>
                        </span>
                    </div>
                    <div class="profile-row">
                        <span class="profile-label">Status</span>
                        <span class="profile-value"><?= htmlspecialchars($guestInfo['Guest_MemberStatus']) ?></span>
                    </div>
                    <?php if ($isVerified): ?>
                    <div class="profile-row">
                        <span class="profile-label">Member Since</span>
                        <span class="profile-value"><?= date('F j, Y', strtotime($guestInfo['Guest_CreatedDate'])) ?></span>
                    </div>
                    <?php else: ?>
                    <div class="profile-row">
                        <span class="profile-label">Member Since</span>
                        <span style="font-size:12px; color:var(--trivago-muted); font-style:italic;">Not yet a member</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Email verification -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h6><i class="bi bi-envelope-check me-2" style="color:#1a8c55;"></i>Email Verification</h6>
                </div>
                <div class="dash-card-body">
                    <?php if ($isVerified): ?>
                        <p class="text-muted small mb-3">Your email address has been verified.</p>
                        <span class="verified-chip">
                            <i class="bi bi-patch-check-fill"></i> Email Verified
                        </span>

                    <?php elseif ($pendingRequest && $pendingRequest['Vreq_Status'] === 'Pending'): ?>
                        <p class="text-muted small mb-3">
                            Your request was submitted on
                            <strong><?= date('M j, Y', strtotime($pendingRequest['Vreq_CreatedAt'])) ?></strong>.
                            Please wait for an admin to review it.
                        </p>
                        <span class="pending-chip">
                            <i class="bi bi-hourglass-split"></i> Pending Admin Approval
                        </span>

                    <?php elseif ($pendingRequest && $pendingRequest['Vreq_Status'] === 'Denied'): ?>
                        <p class="text-muted small mb-3">
                            Your previous request was denied. You may submit a new request.
                        </p>
                        <form method="POST">
                            <button type="submit" name="request_verification" class="btn-verify">
                                <i class="bi bi-envelope-check"></i> Request Again
                            </button>
                        </form>

                    <?php else: ?>
                        <p class="text-muted small mb-3">
                            Your email hasn't been verified yet. Send a request for an admin to verify your account.
                        </p>
                        <form method="POST">
                            <button type="submit" name="request_verification" class="btn-verify">
                                <i class="bi bi-envelope-check"></i> Request Verification
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div class="col-lg-8">

            <!-- Booking history -->
            <div class="dash-card mb-4">
                <div class="dash-card-header">
                    <h6><i class="bi bi-calendar-check me-2" style="color:#1a8c55;"></i>Booking History</h6>
                    <span style="font-size:12px; color:var(--trivago-muted); font-weight:600;">
                        <?= $bookingCount ?> <?= $bookingCount === 1 ? 'booking' : 'bookings' ?>
                    </span>
                </div>
                <div class="dash-card-body">
                    <?php if ($bookings && $bookingCount > 0): ?>
                        <?php while ($b = $bookings->fetch_assoc()):
                            $nights = 1;
                            if ($b['Booking_CheckIn'] && $b['Booking_CheckOut']) {
                                $d1 = new DateTime($b['Booking_CheckIn']);
                                $d2 = new DateTime($b['Booking_CheckOut']);
                                $nights = max(1, $d2->diff($d1)->days);
                            }
                            $statusClass = match(strtolower($b['Booking_Status'] ?? 'confirmed')) {
                                'pending'   => 'status-badge-pending',
                                'cancelled' => 'status-badge-cancelled',
                                default     => 'status-badge-confirmed'
                            };
                        ?>
                        <div class="booking-row">
                            <div style="flex:1; min-width:180px;">
                                <div class="booking-hotel"><?= htmlspecialchars($b['Hotel_Name']) ?></div>
                                <div class="booking-meta">
                                    <i class="bi bi-geo-alt-fill me-1" style="color:var(--trivago-blue);"></i>
                                    <?= htmlspecialchars($b['Hotel_City']) ?>, <?= htmlspecialchars($b['Hotel_Country']) ?>
                                </div>
                                <div class="booking-meta mt-1">
                                    <i class="bi bi-door-open me-1"></i><?= htmlspecialchars($b['Room_Type']) ?>
                                    &nbsp;·&nbsp;
                                    <i class="bi bi-people me-1"></i><?= $b['Room_Capacity'] ?> guests
                                    &nbsp;·&nbsp;
                                    via <strong><?= htmlspecialchars($b['Bkprt_Name']) ?></strong>
                                </div>
                                <div class="booking-meta mt-1">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?= date('M j', strtotime($b['Booking_CheckIn'])) ?> –
                                    <?= date('M j, Y', strtotime($b['Booking_CheckOut'])) ?>
                                    · <?= $nights ?> night<?= $nights > 1 ? 's' : '' ?>
                                </div>
                            </div>
                            <div style="text-align:right; flex-shrink:0;">
                                <div class="booking-price">₱<?= number_format($b['Booking_TotalPrice'], 2) ?></div>
                                <div class="booking-price-label">total</div>
                                <div class="mt-2">
                                    <span class="<?= $statusClass ?>">
                                        <?= ucfirst(htmlspecialchars($b['Booking_Status'] ?? 'Confirmed')) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-calendar-x"></i>
                            <p>No bookings yet.<br>Bookings made via the trivago app will appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Search history -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h6><i class="bi bi-clock-history me-2" style="color:var(--trivago-blue);"></i>Recent Searches</h6>
                    <span style="font-size:12px; color:var(--trivago-muted); font-weight:600;">
                        Last <?= $searchCount ?> searches
                    </span>
                </div>
                <div class="dash-card-body">
                    <?php if ($searchHistory && $searchCount > 0):
                        $searchHistory->data_seek(0);
                        while ($s = $searchHistory->fetch_assoc()):
                    ?>
                    <div class="history-row">
                        <div>
                            <div class="history-dest">
                                <i class="bi bi-geo-alt-fill me-1" style="color:var(--trivago-blue);"></i>
                                <?= htmlspecialchars($s['Schqr_Destination']) ?>
                            </div>
                            <div class="history-meta">
                                <?php if ($s['Schqr_CheckInDate'] && $s['Schqr_CheckOutDate']): ?>
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?= date('M j', strtotime($s['Schqr_CheckInDate'])) ?> –
                                    <?= date('M j, Y', strtotime($s['Schqr_CheckOutDate'])) ?>
                                    &nbsp;·&nbsp;
                                <?php endif; ?>
                                <i class="bi bi-people me-1"></i><?= $s['Schqr_NumberOfGuests'] ?> guest<?= $s['Schqr_NumberOfGuests'] > 1 ? 's' : '' ?>
                                <?php if ($s['Schqr_PetFriendly']): ?>
                                    &nbsp;·&nbsp;<i class="bi bi-paw-fill me-1"></i>Pet friendly
                                <?php endif; ?>
                            </div>
                            <div class="history-meta mt-1">
                                <i class="bi bi-clock me-1"></i>
                                <?= date('M j, Y · g:i A', strtotime($s['Schqr_TimeStamp'])) ?>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                            <span class="history-tag">
                                <?= $s['Schqr_NumberOfRooms'] ?> room<?= $s['Schqr_NumberOfRooms'] > 1 ? 's' : '' ?>
                            </span>
                            <a href="/trivago/results.php?destination=<?= urlencode($s['Schqr_Destination']) ?>&checkin=<?= urlencode($s['Schqr_CheckInDate'] ?? '') ?>&checkout=<?= urlencode($s['Schqr_CheckOutDate'] ?? '') ?>&guests=<?= $s['Schqr_NumberOfGuests'] ?>"
                               class="btn btn-sm btn-outline-primary" style="font-size:12px; white-space:nowrap;">
                                <i class="bi bi-arrow-repeat me-1"></i>Search again
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-search"></i>
                            <p>No searches yet.<br>Your search history will appear here after your first search.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include "../layout/footer.php"; ?>