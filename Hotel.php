<?php
session_start();
require_once "config/db.php";

// ── GET PARAMS ──
$hotel_id = isset($_GET['id'])       ? (int)$_GET['id']          : 0;
$checkin  = isset($_GET['checkin'])  ? $_GET['checkin']           : '';
$checkout = isset($_GET['checkout']) ? $_GET['checkout']          : '';
$guests   = isset($_GET['guests'])   ? (int)$_GET['guests']       : 1;

// ── FETCH HOTEL ──
$hotelQuery = $conn->query("SELECT * FROM Hotel WHERE Hotel_Id = $hotel_id LIMIT 1");

if(!$hotelQuery || $hotelQuery->num_rows === 0){
    header("Location: index.php");
    exit();
}

$hotel = $hotelQuery->fetch_assoc();
$title = htmlspecialchars($hotel['Hotel_Name']) . " - trivago";
include "layout/header.php";
?>

<style>
    /* ── HOTEL HERO ── */
    .hotel-hero {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        padding: 40px 0;
        color: #ffffff;
    }

    .hotel-hero-name {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .hotel-hero-location {
        font-size: 15px;
        color: rgba(255,255,255,0.75);
    }

    /* ── SECTION CARD ── */
    .section-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 28px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        margin-bottom: 20px;
    }

    .section-card-title {
        font-size: 17px;
        font-weight: 700;
        color: var(--trivago-dark);
        margin-bottom: 16px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f0f0f0;
    }

    /* ── ROOMS TABLE ── */
    .room-table th {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--trivago-muted);
        font-weight: 600;
        border-bottom: 2px solid #f0f0f0;
    }

    .room-table td {
        vertical-align: middle;
        font-size: 14px;
        padding: 14px 12px;
        border-bottom: 1px solid #f8f8f8;
    }

    .room-type-badge {
        background: #e8f0fe;
        color: var(--trivago-blue);
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 600;
    }

    .availability-badge {
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 600;
    }

    .available {
        background: #e6f9f0;
        color: #1a8c55;
    }

    .unavailable {
        background: #fde8e8;
        color: #c0392b;
    }

    /* ── PRICE COMPARISON ── */
    .partner-card {
        border: 1.5px solid #e8e8e8;
        border-radius: 12px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .partner-card:hover {
        border-color: var(--trivago-blue);
        box-shadow: 0 4px 16px rgba(0,122,255,0.08);
    }

    .partner-card.best-deal {
        border-color: #1a8c55;
        background: #f6fdf9;
    }

    .best-deal-badge {
        background: #1a8c55;
        color: #ffffff;
        border-radius: 6px;
        padding: 2px 8px;
        font-size: 11px;
        font-weight: 600;
        margin-left: 8px;
    }

    .partner-name {
        font-size: 15px;
        font-weight: 700;
        color: var(--trivago-dark);
    }

    .partner-model {
        font-size: 12px;
        color: var(--trivago-muted);
    }

    .partner-price {
        font-size: 24px;
        font-weight: 700;
        color: var(--trivago-blue);
        text-align: right;
    }

    .partner-price-night {
        font-size: 12px;
        color: var(--trivago-muted);
        text-align: right;
    }

    .btn-view-deal {
        background: var(--trivago-blue);
        color: #ffffff;
        border: none;
        padding: 8px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: background 0.2s ease;
        white-space: nowrap;
    }

    .btn-view-deal:hover {
        background: #005fcc;
        color: #ffffff;
    }

    /* ── HOTEL IMAGE PLACEHOLDER ── */
    .hotel-img-placeholder {
        background: #2a2a4a;
        height: 200px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
    }
</style>

<!-- ══════════════════════════════════════════════ -->
<!--                 HOTEL HERO                    -->
<!-- ══════════════════════════════════════════════ -->
<div class="hotel-hero">
    <div class="container">
        <a href="javascript:history.back()" class="text-white text-decoration-none small mb-3 d-inline-block">
            <i class="bi bi-arrow-left me-1"></i>Back to results
        </a>
        <h1 class="hotel-hero-name"><?= htmlspecialchars($hotel['Hotel_Name']) ?></h1>
        <p class="hotel-hero-location">
            <i class="bi bi-geo-alt-fill me-1"></i>
            <?= htmlspecialchars($hotel['Hotel_Address']) ?>,
            <?= htmlspecialchars($hotel['Hotel_City']) ?>,
            <?= htmlspecialchars($hotel['Hotel_Country']) ?>
        </p>
        <!-- STARS -->
        <div class="mt-2">
            <?php for($s = 1; $s <= 5; $s++): ?>
                <i class="bi <?= $s <= $hotel['Hotel_Rating'] ? 'bi-star-fill' : 'bi-star' ?>"
                   style="color:#f5a623;"></i>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════ -->
<!--               MAIN CONTENT                    -->
<!-- ══════════════════════════════════════════════ -->
<div class="container mt-4 mb-5">
    <div class="row g-4">

        <!-- LEFT COLUMN -->
        <div class="col-lg-8">

            <!-- IMAGE PLACEHOLDER -->
            <div class="hotel-img-placeholder">
                <i class="bi bi-building" style="font-size:60px; color:rgba(255,255,255,0.2);"></i>
            </div>

            <!-- ABOUT -->
            <div class="section-card">
                <p class="section-card-title">About this hotel</p>
                <p style="font-size:15px; color:#555; line-height:1.7;">
                    <?= htmlspecialchars($hotel['Hotel_Description']) ?>
                </p>
            </div>

            <!-- AVAILABLE ROOMS -->
            <div class="section-card">
                <p class="section-card-title">Available Rooms</p>

                <?php
                $roomQuery = $conn->query("
                    SELECT * FROM Room 
                    WHERE Room_HotelId = $hotel_id
                    AND Room_Capacity >= $guests
                    ORDER BY Room_Type ASC
                ");
                ?>

                <?php if($roomQuery && $roomQuery->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table room-table mb-0">
                        <thead>
                            <tr>
                                <th>Room Type</th>
                                <th>Capacity</th>
                                <th>Pet Friendly</th>
                                <th>Availability</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($room = $roomQuery->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="room-type-badge"><?= htmlspecialchars($room['Room_Type']) ?></span>
                                </td>
                                <td>
                                    <i class="bi bi-person me-1" style="color:var(--trivago-blue);"></i>
                                    <?= $room['Room_Capacity'] ?> guests
                                </td>
                                <td>
                                    <?php if($room['Room_PetFriendly']): ?>
                                        <i class="bi bi-check-circle-fill text-success"></i> Yes
                                    <?php else: ?>
                                        <i class="bi bi-x-circle-fill text-danger"></i> No
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="availability-badge <?= $room['Room_Availability'] === 'Available' ? 'available' : 'unavailable' ?>">
                                        <?= $room['Room_Availability'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="text-muted">No rooms available for <?= $guests ?> guest(s).</p>
                <?php endif; ?>
            </div>

        </div>

        <!-- RIGHT COLUMN: PRICE COMPARISON -->
        <div class="col-lg-4">
            <div class="section-card">
                <p class="section-card-title">
                    <i class="bi bi-bar-chart-line me-2" style="color:var(--trivago-blue);"></i>
                    Compare Prices
                </p>

                <?php if($checkin && $checkout): ?>
                    <p class="text-muted small mb-3">
                        <i class="bi bi-calendar me-1"></i>
                        <?= htmlspecialchars($checkin) ?> → <?= htmlspecialchars($checkout) ?>
                    </p>
                <?php endif; ?>

                <?php
                // ── FETCH PRICES PER PARTNER FOR THIS HOTEL ──
                $priceQuery = $conn->query("
                    SELECT 
                        bp.Bkprt_Id,
                        bp.Bkprt_Name,
                        bp.Bkprt_WebsiteURL,
                        bp.Bkprt_MarketplaceModel,
                        bp.Bkprt_VerificationStatus,
                        MIN(rbp.Rbp_Price) AS lowest_price,
                        ANY_VALUE(rbp.Rbp_Notes) AS Rbp_Notes
                    FROM Room_Booking_Partner rbp
                    JOIN Room r             ON r.Room_Id    = rbp.Rbp_RoomId
                    JOIN Booking_Partner bp ON bp.Bkprt_Id  = rbp.Rbp_BkprtId
                    WHERE r.Room_HotelId = $hotel_id
                      AND r.Room_Availability = 'Available'
                      AND r.Room_Capacity >= $guests
                      AND rbp.Rbp_Price > 0
                    GROUP BY bp.Bkprt_Id
                    ORDER BY lowest_price ASC
                ");

                $first = true;
                ?>

                <?php if($priceQuery && $priceQuery->num_rows > 0): ?>
                    <?php while($partner = $priceQuery->fetch_assoc()): ?>

                    <div class="partner-card <?= $first ? 'best-deal' : '' ?>">
                        <div>
                            <p class="partner-name mb-0">
                                <?= htmlspecialchars($partner['Bkprt_Name']) ?>
                                <?php if($first): ?>
                                    <span class="best-deal-badge">Best Deal</span>
                                <?php endif; ?>
                            </p>
                            <p class="partner-model mb-1"><?= $partner['Bkprt_MarketplaceModel'] ?> model</p>
                            <?php if($partner['Bkprt_VerificationStatus'] === 'Verified'): ?>
                                <small style="color:#1a8c55;">
                                    <i class="bi bi-patch-check-fill me-1"></i>Verified Partner
                                </small>
                            <?php endif; ?>
                            <?php if($partner['Rbp_Notes']): ?>
                                <p class="text-muted small mt-1 mb-0">
                                    <i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($partner['Rbp_Notes']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="text-end ms-3">
                            <p class="partner-price mb-0">₱<?= number_format($partner['lowest_price'], 2) ?></p>
                            <p class="partner-price-night mb-2">per night</p>
                            <a href="<?= htmlspecialchars($partner['Bkprt_WebsiteURL']) ?>"
                               target="_blank"
                               class="btn-view-deal">
                                View Deal <i class="bi bi-box-arrow-up-right ms-1"></i>
                            </a>
                        </div>
                    </div>

                    <?php $first = false; ?>
                    <?php endwhile; ?>

                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-exclamation-circle" style="font-size:32px; color:#ccc;"></i>
                        <p class="text-muted mt-2 small">No deals available for this hotel right now.</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<?php include "layout/footer.php"; ?>