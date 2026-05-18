<?php
session_start();
require_once "config/db.php";
$title = "trivago - Find your ideal hotel";
include "layout/header.php";
?>

<style>
    .hero-section {
        background: linear-gradient(135deg, #007aff 0%, #0055cc 100%);
        padding: 70px 0 80px;
        margin-top: -20px;
    }

    .hero-title { font-size: 42px; font-weight: 700; color: #ffffff; line-height: 1.2; }
    .hero-title span { color: #ffd84d; }
    .hero-subtitle { color: rgba(255,255,255,0.85); font-size: 18px; margin-top: 10px; }

    .search-card {
        background: #ffffff; border-radius: 14px;
        padding: 24px; box-shadow: 0 8px 32px rgba(0,0,0,0.15);
    }

    .search-label {
        font-size: 12px; font-weight: 600; color: var(--trivago-muted);
        text-transform: uppercase; letter-spacing: 0.5px;
        margin-bottom: 6px; display: block;
    }

    .search-input {
        border: 1.5px solid #e0e0e0; border-radius: 8px;
        padding: 10px 12px; font-size: 14px;
        transition: border-color 0.2s ease;
    }

    .search-input:focus {
        border-color: var(--trivago-blue);
        box-shadow: 0 0 0 3px rgba(0,122,255,0.1);
    }

    .section-title { font-size: 22px; font-weight: 700; color: var(--trivago-dark); margin-bottom: 4px; }
    .section-sub   { color: var(--trivago-muted); font-size: 14px; margin-bottom: 0; }

    /* ── CAROUSEL WRAPPER ── */
    .carousel-outer {
        position: relative;
    }

    .carousel-track-wrapper {
        overflow: hidden;
        border-radius: 14px;
    }

    .carousel-track {
        display: flex;
        gap: 20px;
        transition: transform 0.45s cubic-bezier(0.4, 0, 0.2, 1);
        will-change: transform;
    }

    /* ── CAROUSEL ARROW BUTTONS ── */
    .carousel-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: none;
        background: #ffffff;
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: var(--trivago-dark);
        cursor: pointer;
        z-index: 10;
        transition: background 0.2s, box-shadow 0.2s, opacity 0.2s;
    }

    .carousel-btn:hover {
        background: var(--trivago-blue);
        color: #fff;
        box-shadow: 0 6px 20px rgba(0,122,255,0.3);
    }

    .carousel-btn.disabled {
        opacity: 0.3;
        pointer-events: none;
    }

    .carousel-btn-prev { left: -20px; }
    .carousel-btn-next { right: -20px; }

    /* ── HOTEL CAROUSEL CARD ── */
    .hotel-carousel-item {
        flex: 0 0 calc(25% - 15px);
        min-width: 0;
    }

    .hotel-cover {
        width: 100%; height: 160px; object-fit: cover;
        border-radius: 12px 12px 0 0;
    }

    .hotel-cover-placeholder {
        background: #f0f0f0; height: 160px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 12px 12px 0 0;
    }

    .hotel-name     { font-weight: 700; color: var(--trivago-dark); margin-bottom: 4px; }
    .hotel-location { font-size: 13px; }

    /* ── DESTINATION CAROUSEL CARD ── */
    .dest-carousel-item {
        flex: 0 0 calc(25% - 15px);
        min-width: 0;
    }

    .dest-card {
        border-radius: 14px; padding: 28px 20px; text-align: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease; cursor: pointer;
        height: 100%;
    }

    .dest-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.10); }
    .dest-icon    { font-size: 32px; color: var(--trivago-blue); }
    .dest-city    { font-weight: 700; color: var(--trivago-dark); margin: 10px 0 2px; }
    .dest-country { color: var(--trivago-muted); font-size: 13px; margin: 0; }

    /* ── HOW IT WORKS ── */
    .how-card {
        background: #ffffff; border-radius: 14px;
        padding: 32px 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); height: 100%;
    }

    .how-icon {
        width: 60px; height: 60px; background: #e8f0fe;
        border-radius: 50%; display: flex; align-items: center;
        justify-content: center; margin: 0 auto;
        font-size: 24px; color: var(--trivago-blue);
    }
</style>

<!-- HERO -->
<section class="hero-section">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h1 class="hero-title">Find your <span>ideal hotel</span> deal</h1>
                <?php if (isset($_SESSION['guest_id'])): ?>
                    <p class="hero-subtitle">Welcome back, <strong><?= htmlspecialchars($_SESSION['guest_name']) ?></strong>! Where are you headed next?</p>
                <?php else: ?>
                    <p class="hero-subtitle">Compare hotel prices from hundreds of sites in seconds.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="row justify-content-center mt-4">
            <div class="col-lg-10">
                <div class="search-card">
                    <form method="GET" action="results.php">
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-4 col-md-6">
                                <label class="search-label"><i class="bi bi-geo-alt me-1"></i>Destination</label>
                                <input type="text" name="destination" class="form-control search-input"
                                       placeholder="Where are you going?" required>
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
                                <label class="search-label"><i class="bi bi-person me-1"></i>Guests</label>
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
                                <button type="submit" class="btn btn-trivago w-100" style="padding:10px;">
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

<!-- POPULAR DESTINATIONS CAROUSEL -->
<section class="container mt-5">
    <div class="d-flex align-items-center justify-content-between mb-1">
        <div>
            <h4 class="section-title mb-0">Popular Destinations</h4>
            <p class="section-sub">Trending places our guests love</p>
        </div>
    </div>

    <?php
    $destinations = [
        ["city" => "Makati",   "country" => "Philippines", "icon" => "bi-buildings", "color" => "#e3f0ff"],
        ["city" => "Cebu",     "country" => "Philippines", "icon" => "bi-water",      "color" => "#e3f9f0"],
        ["city" => "Boracay",  "country" => "Philippines", "icon" => "bi-umbrella",   "color" => "#fff8e3"],
        ["city" => "Manila",   "country" => "Philippines", "icon" => "bi-bank",       "color" => "#f3e3ff"],
        ["city" => "Palawan",  "country" => "Philippines", "icon" => "bi-tree",       "color" => "#e3fff8"],
        ["city" => "Davao",    "country" => "Philippines", "icon" => "bi-flower1",    "color" => "#ffe3f0"],
        ["city" => "Baguio",   "country" => "Philippines", "icon" => "bi-cloud-fog2", "color" => "#f0e3ff"],
        ["city" => "Tagaytay", "country" => "Philippines", "icon" => "bi-wind",       "color" => "#e3f5ff"],
    ];
    $destTotal   = count($destinations);
    $destVisible = 4;
    $destMax     = max(0, $destTotal - $destVisible);
    ?>

    <div class="carousel-outer mt-2" id="destCarousel">
        <button class="carousel-btn carousel-btn-prev disabled" id="destPrev" onclick="slideCarousel('dest', -1)">
            <i class="bi bi-chevron-left"></i>
        </button>
        <div class="carousel-track-wrapper">
            <div class="carousel-track" id="destTrack">
                <?php foreach ($destinations as $dest): ?>
                <div class="dest-carousel-item">
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
        </div>
        <button class="carousel-btn carousel-btn-next <?= $destMax <= 0 ? 'disabled' : '' ?>" id="destNext" onclick="slideCarousel('dest', 1)">
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>
</section>

<!-- FEATURED HOTELS CAROUSEL -->
<section class="container mt-5">
    <div class="d-flex align-items-center justify-content-between mb-1">
        <div>
            <h4 class="section-title mb-0">Featured Hotels</h4>
            <p class="section-sub">Highly rated properties on our platform</p>
        </div>
    </div>

    <?php
    $hotels     = $conn->query("SELECT * FROM Hotel ORDER BY Hotel_Rating DESC LIMIT 12");
    $hotelItems = [];
    while ($hotel = $hotels->fetch_assoc()) {
        $cover = $conn->query("
            SELECT Image_Path FROM Hotel_Images
            WHERE Image_HotelId = {$hotel['Hotel_Id']} AND Image_IsCover = 1
            LIMIT 1
        ")->fetch_assoc();
        $hotel['cover'] = $cover;
        $hotelItems[]   = $hotel;
    }
    $hotelTotal   = count($hotelItems);
    $hotelVisible = 4;
    $hotelMax     = max(0, $hotelTotal - $hotelVisible);
    ?>

    <div class="carousel-outer mt-2" id="hotelCarousel">
        <button class="carousel-btn carousel-btn-prev disabled" id="hotelPrev" onclick="slideCarousel('hotel', -1)">
            <i class="bi bi-chevron-left"></i>
        </button>
        <div class="carousel-track-wrapper">
            <div class="carousel-track" id="hotelTrack">
                <?php foreach ($hotelItems as $hotel): ?>
                <div class="hotel-carousel-item">
                    <a href="hotel.php?id=<?= $hotel['Hotel_Id'] ?>" class="text-decoration-none">
                        <div class="card card-trivago h-100">
                            <?php if ($hotel['cover']): ?>
                                <img src="/trivago/<?= htmlspecialchars($hotel['cover']['Image_Path']) ?>"
                                     class="hotel-cover" alt="<?= htmlspecialchars($hotel['Hotel_Name']) ?>">
                            <?php else: ?>
                                <div class="hotel-cover-placeholder">
                                    <i class="bi bi-building" style="font-size:40px; color:#aaa;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h6 class="hotel-name"><?= htmlspecialchars($hotel['Hotel_Name']) ?></h6>
                                <p class="hotel-location text-muted mb-1">
                                    <i class="bi bi-geo-alt-fill me-1" style="color:var(--trivago-blue);"></i>
                                    <?= htmlspecialchars($hotel['Hotel_City']) ?>, <?= htmlspecialchars($hotel['Hotel_Country']) ?>
                                </p>
                                <div class="mb-2">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <i class="bi <?= $s <= $hotel['Hotel_Rating'] ? 'bi-star-fill' : 'bi-star' ?>"
                                           style="color:#f5a623; font-size:13px;"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-muted small"><?= htmlspecialchars(substr($hotel['Hotel_Description'], 0, 70)) ?>...</p>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <button class="carousel-btn carousel-btn-next <?= $hotelMax <= 0 ? 'disabled' : '' ?>" id="hotelNext" onclick="slideCarousel('hotel', 1)">
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>
</section>

<!-- HOW IT WORKS -->
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

<script>
function changeGuests(amount) {
    const input = document.getElementById('guestCount');
    const newVal = parseInt(input.value) + amount;
    if (newVal >= 1 && newVal <= 20) input.value = newVal;
}

// ── CAROUSEL ENGINE ──
const carousels = {
    dest: {
        track:   document.getElementById('destTrack'),
        prev:    document.getElementById('destPrev'),
        next:    document.getElementById('destNext'),
        dots:    document.getElementById('destDots'),
        visible: 4,
        current: 0,
        total:   <?= $destTotal ?>,
        gap:     20
    },
    hotel: {
        track:   document.getElementById('hotelTrack'),
        prev:    document.getElementById('hotelPrev'),
        next:    document.getElementById('hotelNext'),
        dots:    document.getElementById('hotelDots'),
        visible: 4,
        current: 0,
        total:   <?= $hotelTotal ?>,
        gap:     20
    }
};

function initCarousel(key) {
    
}

function getItemWidth(key) {
    const c     = carousels[key];
    const items = c.track.children;
    if (!items.length) return 0;
    return items[0].getBoundingClientRect().width + c.gap;
}

function goToSlide(key, index) {
    const c       = carousels[key];
    const maxStep = Math.max(0, c.total - c.visible);
    c.current     = Math.max(0, Math.min(index, maxStep));

    const itemW = getItemWidth(key);
    c.track.style.transform = `translateX(-${c.current * itemW}px)`;

    // Update buttons
    c.prev.classList.toggle('disabled', c.current === 0);
    c.next.classList.toggle('disabled', c.current >= maxStep);
}

function slideCarousel(key, dir) {
    const c = carousels[key];
    goToSlide(key, c.current + dir);
}

// Init both
initCarousel('dest');
initCarousel('hotel');

// Recalculate on resize
window.addEventListener('resize', () => {
    goToSlide('dest',  carousels.dest.current);
    goToSlide('hotel', carousels.hotel.current);
});
</script>

<?php include "layout/footer.php"; ?>