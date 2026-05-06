<?php
session_start();
require_once "config/db.php";
$title = "trivago - Find your ideal hotel";
include "layout/header.php";
?>

<!-- ══════════════════════════════════════════════ -->
<!--                  HERO SECTION                 -->
<!-- ══════════════════════════════════════════════ -->
<section class="hero-section">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h1 class="hero-title">Find your <span>ideal hotel</span> deal</h1>
                <p class="hero-subtitle">Compare hotel prices from hundreds of sites in seconds.</p>
            </div>
        </div>

        <!-- SEARCH CARD -->
        <div class="row justify-content-center mt-4">
            <div class="col-lg-10">
                <div class="search-card">
                    <form method="GET" action="results.php">
                        <div class="row g-2 align-items-end">

                            <!-- DESTINATION -->
                            <div class="col-lg-4 col-md-6">
                                <label class="search-label">
                                    <i class="bi bi-geo-alt me-1"></i>Destination
                                </label>
                                <input type="text" name="destination" class="form-control search-input"
                                       placeholder="Where are you going?" required>
                            </div>

                            <!-- CHECK-IN -->
                            <div class="col-lg-2 col-md-6">
                                <label class="search-label">
                                    <i class="bi bi-calendar me-1"></i>Check-in
                                </label>
                                <input type="date" name="checkin" class="form-control search-input" required>
                            </div>

                            <!-- CHECK-OUT -->
                            <div class="col-lg-2 col-md-6">
                                <label class="search-label">
                                    <i class="bi bi-calendar-check me-1"></i>Check-out
                                </label>
                                <input type="date" name="checkout" class="form-control search-input" required>
                            </div>

                            <!-- GUESTS -->
                            <div class="col-lg-2 col-md-6">
                                <label class="search-label">
                                    <i class="bi bi-person me-1"></i>Guests
                                </label>
                                <select name="guests" class="form-control search-input">
                                    <?php for($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?> Guest<?= $i > 1 ? 's' : '' ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- SEARCH BUTTON -->
                            <div class="col-lg-2 col-md-12">
                                <button type="submit" class="btn btn-trivago w-100 search-btn">
                                    <i class="bi bi-search me-1"></i>Search
                                </button>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- ══════════════════════════════════════════════ -->
<!--             FEATURED DESTINATIONS             -->
<!-- ══════════════════════════════════════════════ -->
<section class="container mt-5">
    <h4 class="section-title">Popular Destinations</h4>
    <p class="section-sub">Trending places our guests love</p>

    <div class="row g-3 mt-2">
        <?php
        $destinations = [
            ["city" => "Makati",   "country" => "Philippines", "icon" => "bi-buildings",     "color" => "#e3f0ff"],
            ["city" => "Cebu",     "country" => "Philippines", "icon" => "bi-water",          "color" => "#e3f9f0"],
            ["city" => "Boracay",  "country" => "Philippines", "icon" => "bi-umbrella",       "color" => "#fff8e3"],
            ["city" => "Manila",   "country" => "Philippines", "icon" => "bi-bank",           "color" => "#f3e3ff"],
        ];
        foreach($destinations as $dest): ?>
        <div class="col-lg-3 col-md-6">
            <a href="results.php?destination=<?= urlencode($dest['city']) ?>" class="text-decoration-none">
                <div class="dest-card" style="background:<?= $dest['color'] ?>;">
                    <i class="bi <?= $dest['icon'] ?> dest-icon"></i>
                    <h6 class="dest-city"><?= $dest['city'] ?></h6>
                    <p class="dest-country"><?= $dest['country'] ?></p>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ══════════════════════════════════════════════ -->
<!--               FEATURED HOTELS                 -->
<!-- ══════════════════════════════════════════════ -->
<section class="container mt-5">
    <h4 class="section-title">Featured Hotels</h4>
    <p class="section-sub">Highly rated properties on our platform</p>

    <div class="row g-3 mt-2">
        <?php
        $hotels = $conn->query("SELECT * FROM Hotel ORDER BY Hotel_Rating DESC LIMIT 4");
        while($hotel = $hotels->fetch_assoc()):
        ?>
        <div class="col-lg-3 col-md-6">
            <a href="hotel.php?id=<?= $hotel['Hotel_Id'] ?>" class="text-decoration-none">
                <div class="card card-trivago h-100">
                    <!-- PLACEHOLDER IMAGE -->
                    <div class="hotel-img-placeholder">
                        <i class="bi bi-building" style="font-size:40px; color:#aaa;"></i>
                    </div>
                    <div class="card-body">
                        <h6 class="hotel-name"><?= htmlspecialchars($hotel['Hotel_Name']) ?></h6>
                        <p class="hotel-location text-muted mb-1">
                            <i class="bi bi-geo-alt-fill me-1" style="color:var(--trivago-blue);"></i>
                            <?= htmlspecialchars($hotel['Hotel_City']) ?>, <?= htmlspecialchars($hotel['Hotel_Country']) ?>
                        </p>
                        <!-- STAR RATING -->
                        <div class="mb-2">
                            <?php for($s = 1; $s <= 5; $s++): ?>
                                <i class="bi <?= $s <= $hotel['Hotel_Rating'] ? 'bi-star-fill' : 'bi-star' ?>"
                                   style="color:#f5a623; font-size:13px;"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="hotel-desc text-muted small">
                            <?= htmlspecialchars(substr($hotel['Hotel_Description'], 0, 70)) ?>...
                        </p>
                    </div>
                </div>
            </a>
        </div>
        <?php endwhile; ?>
    </div>
</section>

<!-- ══════════════════════════════════════════════ -->
<!--               HOW IT WORKS                    -->
<!-- ══════════════════════════════════════════════ -->
<section class="container mt-5 mb-5">
    <h4 class="section-title text-center">How trivago works</h4>
    <p class="section-sub text-center">Find the best deal in 3 easy steps</p>

    <div class="row g-4 mt-2 text-center">

        <div class="col-md-4">
            <div class="how-card">
                <div class="how-icon"><i class="bi bi-search"></i></div>
                <h6 class="mt-3 fw-bold">1. Search</h6>
                <p class="text-muted small">Enter your destination, dates, and number of guests.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="how-card">
                <div class="how-icon"><i class="bi bi-bar-chart-line"></i></div>
                <h6 class="mt-3 fw-bold">2. Compare</h6>
                <p class="text-muted small">Browse and compare hotels from our booking partners.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="how-card">
                <div class="how-icon"><i class="bi bi-check-circle"></i></div>
                <h6 class="mt-3 fw-bold">3. Book</h6>
                <p class="text-muted small">Choose your ideal deal and get redirected to book.</p>
            </div>
        </div>

    </div>
</section>

<style>
    /* ── HERO ── */
    .hero-section {
        background: linear-gradient(135deg, #007aff 0%, #0055cc 100%);
        padding: 70px 0 80px;
        margin-top: -20px;
    }

    .hero-title {
        font-size: 42px;
        font-weight: 700;
        color: #ffffff;
        line-height: 1.2;
    }

    .hero-title span {
        color: #ffd84d;
    }

    .hero-subtitle {
        color: rgba(255,255,255,0.85);
        font-size: 18px;
        margin-top: 10px;
    }

    /* ── SEARCH CARD ── */
    .search-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 24px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.15);
    }

    .search-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--trivago-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
        display: block;
    }

    .search-input {
        border: 1.5px solid #e0e0e0;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 14px;
        transition: border-color 0.2s ease;
    }

    .search-input:focus {
        border-color: var(--trivago-blue);
        box-shadow: 0 0 0 3px rgba(0,122,255,0.1);
    }

    .search-btn {
        padding: 10px;
        border-radius: 8px;
        font-size: 15px;
    }

    /* ── SECTION HEADINGS ── */
    .section-title {
        font-size: 22px;
        font-weight: 700;
        color: var(--trivago-dark);
        margin-bottom: 4px;
    }

    .section-sub {
        color: var(--trivago-muted);
        font-size: 14px;
        margin-bottom: 0;
    }

    /* ── DESTINATION CARDS ── */
    .dest-card {
        border-radius: 14px;
        padding: 28px 20px;
        text-align: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
    }

    .dest-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.10);
    }

    .dest-icon {
        font-size: 32px;
        color: var(--trivago-blue);
    }

    .dest-city {
        font-weight: 700;
        color: var(--trivago-dark);
        margin: 10px 0 2px;
    }

    .dest-country {
        color: var(--trivago-muted);
        font-size: 13px;
        margin: 0;
    }

    /* ── HOTEL CARDS ── */
    .hotel-img-placeholder {
        background: #f0f0f0;
        height: 160px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px 12px 0 0;
    }

    .hotel-name {
        font-weight: 700;
        color: var(--trivago-dark);
        margin-bottom: 4px;
    }

    .hotel-location {
        font-size: 13px;
    }

    /* ── HOW IT WORKS ── */
    .how-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 32px 20px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        height: 100%;
    }

    .how-icon {
        width: 60px;
        height: 60px;
        background: #e8f0fe;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        font-size: 24px;
        color: var(--trivago-blue);
    }
</style>

<?php include "layout/footer.php"; ?>