<?php
session_start();
require_once "config/db.php";

$destination = isset($_GET['destination']) ? $conn->real_escape_string($_GET['destination']) : '';
$checkin     = isset($_GET['checkin'])     ? $_GET['checkin']     : '';
$checkout    = isset($_GET['checkout'])    ? $_GET['checkout']    : '';
$guests      = isset($_GET['guests'])      ? (int)$_GET['guests'] : 1;

$title = "Results for \"" . htmlspecialchars($destination) . "\" - trivago";
include "layout/header.php";
?>

<style>
    .search-bar-compact {
        background: #ffffff; border-bottom: 1px solid #e8e8e8;
        padding: 14px 0; margin-top: -20px;
        position: sticky; top: 64px; z-index: 100;
    }

    .search-input-sm {
        border: 1.5px solid #e0e0e0; border-radius: 8px;
        padding: 8px 12px; font-size: 13px; transition: border-color 0.2s ease;
    }

    .search-input-sm:focus {
        border-color: var(--trivago-blue);
        box-shadow: 0 0 0 3px rgba(0,122,255,0.1);
    }

    .search-label-sm {
        font-size: 11px; font-weight: 600; color: var(--trivago-muted);
        text-transform: uppercase; letter-spacing: 0.5px;
        margin-bottom: 4px; display: block;
    }

    .results-count { font-size: 14px; color: var(--trivago-muted); margin-bottom: 16px; }

    .hotel-result-card {
        background: #ffffff; border-radius: 14px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        margin-bottom: 16px; overflow: hidden;
        transition: box-shadow 0.2s ease; display: flex;
    }

    .hotel-result-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,0.10); }

    .hotel-result-img {
        width: 220px; min-height: 160px; flex-shrink: 0;
        background: #f0f0f0; overflow: hidden;
    }

    .hotel-result-img img {
        width: 100%; height: 100%; object-fit: cover;
    }

    .hotel-result-img-placeholder {
        width: 100%; height: 100%; min-height: 160px;
        display: flex; align-items: center; justify-content: center;
    }

    .hotel-result-body {
        padding: 20px; flex-grow: 1;
        display: flex; justify-content: space-between;
        align-items: center; gap: 16px;
    }

    .hotel-result-info  { flex-grow: 1; }
    .hotel-result-name  { font-size: 18px; font-weight: 700; color: var(--trivago-dark); margin-bottom: 4px; }
    .hotel-result-location { font-size: 13px; color: var(--trivago-muted); margin-bottom: 8px; }
    .hotel-result-desc  { font-size: 13px; color: #555; margin-bottom: 0; }

    .hotel-result-price { text-align: right; flex-shrink: 0; min-width: 160px; }
    .price-from   { font-size: 11px; color: var(--trivago-muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .price-amount { font-size: 26px; font-weight: 700; color: var(--trivago-blue); line-height: 1.1; }
    .price-night  { font-size: 12px; color: var(--trivago-muted); margin-bottom: 10px; }

    .no-results {
        background: #ffffff; border-radius: 14px;
        padding: 60px 20px; text-align: center;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    }
</style>

<!-- COMPACT SEARCH BAR -->
<div class="search-bar-compact">
    <div class="container">
        <form method="GET" action="results.php">
            <div class="row g-2 align-items-end">
                <div class="col-lg-4 col-md-6">
                    <label class="search-label-sm"><i class="bi bi-geo-alt me-1"></i>Destination</label>
                    <input type="text" name="destination" class="form-control search-input-sm"
                           value="<?= htmlspecialchars($destination) ?>" required>
                </div>
                <div class="col-lg-4 col-md-6">
                    <label class="search-label"><i class="bi bi-calendar me-1"></i>Check-in — Check-out</label>
                    <div class="d-flex align-items-center border rounded-3 overflow-hidden"
                        style="border: 1.5px solid #e0e0e0 !important; background:#fff;">
                        <input type="date" name="checkin" class="form-control border-0 shadow-none search-input"
                            style="border-radius:0; flex:1;" required>
                        <div style="width:1px; height:24px; background:#e0e0e0; flex-shrink:0;"></div>
                        <input type="date" name="checkout" class="form-control border-0 shadow-none search-input"
                            style="border-radius:0; flex:1;" required>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="search-label-sm"><i class="bi bi-person me-1"></i>Guests</label>
                    
                    <div class="d-flex align-items-center border rounded-3 overflow-hidden" style="height:40px;">
                        <button type="button" onclick="changeGuests(-1)"
                                style="width:36px; height:100%; border:none; background:#f5f5f5;
                                    font-size:18px; cursor:pointer; flex-shrink:0;">−</button>
                        <input type="number" name="guests" id="guestCount" value="1" min="1" max="20"
                            class="form-control border-0 text-center shadow-none"
                            style="height:100%; border-radius:0;">
                        <button type="button" onclick="changeGuests(1)"
                                style="width:36px; height:100%; border:none; background:#f5f5f5;
                                    font-size:18px; cursor:pointer; flex-shrink:0;">+</button>
                    </div>

                </div>
                <div class="col-lg-2 col-md-12">
                    <button type="submit" class="btn btn-trivago w-100" style="padding:8px;">
                        <i class="bi bi-search me-1"></i>Search
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- RESULTS -->
<div class="container mt-4 mb-5">
    <?php if($destination): ?>
        <?php
        $query = "
            SELECT DISTINCT
                h.Hotel_Id, h.Hotel_Name, h.Hotel_City, h.Hotel_Country,
                h.Hotel_Rating, h.Hotel_Description,
                MIN(rbp.Rbp_Price) AS lowest_price
            FROM Hotel h
            JOIN Room r              ON r.Room_HotelId  = h.Hotel_Id
            JOIN Room_Booking_Partner rbp ON rbp.Rbp_RoomId = r.Room_Id
            WHERE (h.Hotel_City    LIKE '%$destination%'
               OR  h.Hotel_Country LIKE '%$destination%'
               OR  h.Hotel_Name    LIKE '%$destination%')
              AND r.Room_Availability = 'Available'
              AND r.Room_Capacity    >= $guests
            GROUP BY h.Hotel_Id
            ORDER BY lowest_price ASC
        ";
        $results = $conn->query($query);
        $count   = $results ? $results->num_rows : 0;
        ?>

        <p class="results-count">
            <strong><?= $count ?></strong> propert<?= $count == 1 ? 'y' : 'ies' ?> found
            for <strong>"<?= htmlspecialchars($destination) ?>"</strong>
            <?= $checkin && $checkout ? '· ' . htmlspecialchars($checkin) . ' – ' . htmlspecialchars($checkout) : '' ?>
            · <?= $guests ?> guest<?= $guests > 1 ? 's' : '' ?>
        </p>

        <?php if($count > 0): ?>
            <?php while($hotel = $results->fetch_assoc()):
                $cover = $conn->query("
                    SELECT Image_Path FROM Hotel_Images
                    WHERE Image_HotelId = {$hotel['Hotel_Id']} AND Image_IsCover = 1
                    LIMIT 1
                ")->fetch_assoc();
            ?>
            <a href="hotel.php?id=<?= $hotel['Hotel_Id'] ?>&checkin=<?= urlencode($checkin) ?>&checkout=<?= urlencode($checkout) ?>&guests=<?= $guests ?>"
               class="text-decoration-none">
                <div class="hotel-result-card">
                    <div class="hotel-result-img">
                        <?php if($cover): ?>
                            <img src="/trivago/<?= htmlspecialchars($cover['Image_Path']) ?>"
                                 alt="<?= htmlspecialchars($hotel['Hotel_Name']) ?>">
                        <?php else: ?>
                            <div class="hotel-result-img-placeholder">
                                <i class="bi bi-building" style="font-size:40px; color:#ccc;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="hotel-result-body">
                        <div class="hotel-result-info">
                            <p class="hotel-result-name"><?= htmlspecialchars($hotel['Hotel_Name']) ?></p>
                            <p class="hotel-result-location">
                                <i class="bi bi-geo-alt-fill me-1" style="color:var(--trivago-blue);"></i>
                                <?= htmlspecialchars($hotel['Hotel_City']) ?>, <?= htmlspecialchars($hotel['Hotel_Country']) ?>
                            </p>
                            <div class="mb-2">
                                <?php for($s = 1; $s <= 5; $s++): ?>
                                    <i class="bi <?= $s <= $hotel['Hotel_Rating'] ? 'bi-star-fill' : 'bi-star' ?>"
                                       style="color:#f5a623; font-size:13px;"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="hotel-result-desc">
                                <?= htmlspecialchars(substr($hotel['Hotel_Description'], 0, 100)) ?>...
                            </p>
                        </div>
                        <div class="hotel-result-price">

                            <?php
                            $nights = 1;
                            if ($checkin && $checkout) {
                                $d1 = new DateTime($checkin);
                                $d2 = new DateTime($checkout);
                                $nights = max(1, $d2->diff($d1)->days);
                            }
                            $total = $hotel['lowest_price'] * $nights;
                            ?>
                            <p class="price-from">From</p>
                            <p class="price-amount">₱<?= number_format($hotel['lowest_price'], 2) ?></p>
                            <p class="price-night">per night</p>
                            <?php if ($nights > 1): ?>
                                <p style="font-size:13px; color:#1a8c55; font-weight:600; margin-bottom:10px;">
                                    ₱<?= number_format($total, 2) ?> total · <?= $nights ?> nights
                                </p>
                            <?php else: ?>
                                <p style="font-size:12px; color:var(--trivago-muted); margin-bottom:10px;">1 night</p>
                            <?php endif; ?>
                            <span class="btn btn-trivago btn-sm">See deals</span>

                        </div>
                    </div>
                </div>
            </a>
            <?php endwhile; ?>

        <?php else: ?>
            <div class="no-results">
                <i class="bi bi-search" style="font-size:48px; color:#ccc;"></i>
                <h5 class="mt-3">No hotels found</h5>
                <p class="text-muted">Try a different destination or adjust your filters.</p>
                <a href="index.php" class="btn btn-trivago mt-2">Back to Search</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function changeGuests(amount){
    const input = document.getElementById('guestCount');
    const newVal = parseInt(input.value) + amount;
    if(newVal >= 1 && newVal <= 20) input.value = newVal;
}
</script>

<?php include "layout/footer.php"; ?>