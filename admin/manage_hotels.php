<?php
session_start();
require_once "../config/db.php";

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

    if($stmt->execute()) $success = "Hotel added successfully!";
    else $error = "Failed to add hotel.";
}

// ══════════════════════════════════════════════
//  DELETE HOTEL
// ══════════════════════════════════════════════
if(isset($_GET['delete'])){
    $id   = (int)$_GET['delete'];
    $imgs = $conn->query("SELECT Image_Path FROM Hotel_Images WHERE Image_HotelId = $id");
    while($img = $imgs->fetch_assoc()){
        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/trivago/" . $img['Image_Path'];
        if(file_exists($filePath)) unlink($filePath);
    }
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
        Hotel_Name=?, Hotel_Address=?, Hotel_City=?, Hotel_Country=?,
        Hotel_Rating=?, Hotel_Description=? WHERE Hotel_Id=?");
    $stmt->bind_param("ssssisi", $name, $address, $city, $country, $rating, $description, $id);

    if($stmt->execute()) $success = "Hotel updated successfully!";
    else $error = "Failed to update hotel.";
}

// ══════════════════════════════════════════════
//  UPLOAD HOTEL IMAGE
// ══════════════════════════════════════════════
if(isset($_POST['upload_image'])){
    $hotelId  = (int)$_POST['image_hotel_id'];
    $isCover  = isset($_POST['is_cover']) ? 1 : 0;
    $caption  = $conn->real_escape_string($_POST['image_caption'] ?? '');
    $file     = $_FILES['hotel_image'];
    $allowed  = ['image/jpeg', 'image/png', 'image/webp'];

    if(!in_array($file['type'], $allowed)){
        $error = "Only JPG, PNG, and WEBP images are allowed.";
    } elseif($file['size'] > 5 * 1024 * 1024){
        $error = "Image must be under 5MB.";
    } else {
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "hotel_" . $hotelId . "_" . time() . "." . $ext;
        $destPath = $_SERVER['DOCUMENT_ROOT'] . "/trivago/assets/images/hotels/" . $filename;
        $dbPath   = "assets/images/hotels/" . $filename;

        if(move_uploaded_file($file['tmp_name'], $destPath)){
            if($isCover){
                $conn->query("UPDATE Hotel_Images SET Image_IsCover = 0 WHERE Image_HotelId = $hotelId");
            }
            $addedDate = date('Y-m-d');
            $stmt = $conn->prepare("INSERT INTO Hotel_Images
                (Image_HotelId, Image_Path, Image_IsCover, Image_Caption, Image_AddedDate)
                VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isiss", $hotelId, $dbPath, $isCover, $caption, $addedDate);
            $stmt->execute();
            $success = "Image uploaded successfully!";
        } else {
            $error = "Failed to upload image. Check folder permissions.";
        }
    }
}

// ══════════════════════════════════════════════
//  SET COVER IMAGE
// ══════════════════════════════════════════════
if(isset($_GET['set_cover'])){
    $imageId = (int)$_GET['set_cover'];
    $hotelId = (int)$_GET['hotel_id'];
    $conn->query("UPDATE Hotel_Images SET Image_IsCover = 0 WHERE Image_HotelId = $hotelId");
    $conn->query("UPDATE Hotel_Images SET Image_IsCover = 1 WHERE Image_Id = $imageId");
    $success = "Cover photo updated!";
}

// ══════════════════════════════════════════════
//  DELETE IMAGE
// ══════════════════════════════════════════════
if(isset($_GET['delete_image'])){
    $imageId  = (int)$_GET['delete_image'];
    $img      = $conn->query("SELECT Image_Path FROM Hotel_Images WHERE Image_Id = $imageId")->fetch_assoc();
    if($img){
        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/trivago/" . $img['Image_Path'];
        if(file_exists($filePath)) unlink($filePath);
        $conn->query("DELETE FROM Hotel_Images WHERE Image_Id = $imageId");
        $success = "Image deleted successfully!";
    }
}

$hotels         = $conn->query("SELECT * FROM Hotel ORDER BY Hotel_AddedDate DESC");
$hotelsDropdown = $conn->query("SELECT Hotel_Id, Hotel_Name FROM Hotel ORDER BY Hotel_Name");

$title = "Manage Hotels - trivago";
include "../layout/header.php";
?>

<style>
    .admin-wrapper { display: flex; min-height: calc(100vh - 64px); }
    .admin-sidebar {
        width: 240px; background: #ffffff; border-right: 1px solid #e8e8e8;
        padding: 24px 16px; position: sticky; top: 64px;
        height: calc(100vh - 64px); flex-shrink: 0;
    }
    .admin-sidebar h6 {
        font-size: 11px; text-transform: uppercase; letter-spacing: 1px;
        color: var(--trivago-muted); margin-bottom: 12px; padding-left: 12px;
    }
    .sidebar-link {
        display: flex; align-items: center; gap: 10px; padding: 10px 12px;
        border-radius: 8px; font-size: 14px; font-weight: 600;
        color: var(--trivago-text); text-decoration: none;
        border-left: 3px solid transparent; transition: all 0.2s ease; margin-bottom: 4px;
    }
    .sidebar-link:hover { background: var(--trivago-gray); color: var(--trivago-blue); }
    .sidebar-link.active-sidebar { background: #e8f0fe; color: var(--trivago-blue); border-left: 3px solid var(--trivago-blue); }
    .admin-main { flex-grow: 1; padding: 32px; background: var(--trivago-gray); }
    .table-card { background: #ffffff; border-radius: 14px; padding: 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
    .table-card-title {
        font-size: 16px; font-weight: 700; color: var(--trivago-dark);
        margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0;
        display: flex; justify-content: space-between; align-items: center;
    }
    .admin-table th {
        font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;
        color: var(--trivago-muted); font-weight: 600;
        border-bottom: 2px solid #f0f0f0; padding: 10px 12px;
    }
    .admin-table td { font-size: 13px; padding: 12px; vertical-align: middle; border-bottom: 1px solid #f8f8f8; }
    .image-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
    .image-item { position: relative; width: 100px; height: 80px; border-radius: 8px; overflow: hidden; border: 2px solid #e8e8e8; }
    .image-item.is-cover { border-color: var(--trivago-blue); }
    .image-item img { width: 100%; height: 100%; object-fit: cover; }
    .image-item .image-actions {
        position: absolute; bottom: 0; left: 0; right: 0;
        background: rgba(0,0,0,0.55); display: flex;
        justify-content: center; gap: 4px; padding: 4px;
        opacity: 0; transition: opacity 0.2s ease;
    }
    .image-item:hover .image-actions { opacity: 1; }
    .cover-badge {
        position: absolute; top: 4px; left: 4px; background: var(--trivago-blue);
        color: #fff; font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 4px;
    }
    .img-caption-label {
        position: absolute; bottom: 0; left: 0; right: 0;
        background: rgba(0,0,0,0.5); color: #fff;
        font-size: 9px; padding: 2px 4px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
</style>

<div class="admin-wrapper">
    <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
    <aside class="admin-sidebar">
        <h6>Admin Panel</h6>
        <a href="admin_dashboard.php" class="sidebar-link <?= $currentPage === 'admin_dashboard.php' ? 'active-sidebar' : '' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="manage_hotels.php"   class="sidebar-link <?= $currentPage === 'manage_hotels.php'   ? 'active-sidebar' : '' ?>"><i class="bi bi-building"></i> Hotels</a>
        <a href="manage_rooms.php"    class="sidebar-link <?= $currentPage === 'manage_rooms.php'    ? 'active-sidebar' : '' ?>"><i class="bi bi-door-open"></i> Rooms</a>
        <a href="manage_partners.php" class="sidebar-link <?= $currentPage === 'manage_partners.php' ? 'active-sidebar' : '' ?>"><i class="bi bi-handshake"></i> Partners</a>
        <a href="manage_prices.php" class="sidebar-link <?=$currentPage==='manage_prices.php'?'active-sidebar':''?>"><i class="bi bi-tag"></i> Prices</a>
        
        <hr style="border-color:#f0f0f0; margin:16px 0;">
        <a href="../index.php"        class="sidebar-link"><i class="bi bi-house"></i> View Site</a>
        <a href="../auth/logout.php"  class="sidebar-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </aside>

    <main class="admin-main">
        <h4 style="font-weight:700; margin-bottom:4px;">Manage Hotels</h4>
        <p class="text-muted small mb-4">Add, edit, remove hotels and manage their photos.</p>

        <?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- ADD HOTEL -->
        <div class="table-card mb-4">
            <div class="table-card-title"><span><i class="bi bi-plus-circle me-2" style="color:var(--trivago-blue);"></i>Add New Hotel</span></div>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label fw-semibold">Hotel Name</label><input type="text" name="hotel_name" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Address</label><input type="text" name="hotel_address" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">City</label><input type="text" name="hotel_city" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Country</label><input type="text" name="hotel_country" class="form-control" required></div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Star Rating</label>
                        <select name="hotel_rating" class="form-control" required>
                            <?php for($i=1;$i<=5;$i++): ?><option value="<?=$i?>"><?=$i?> Star<?=$i>1?'s':''?></option><?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label fw-semibold">Description</label><textarea name="hotel_description" class="form-control" rows="3" required></textarea></div>
                    <div class="col-12"><button type="submit" name="add_hotel" class="btn btn-trivago"><i class="bi bi-plus-circle me-1"></i>Add Hotel</button></div>
                </div>
            </form>
        </div>

        <!-- UPLOAD IMAGE -->
        <div class="table-card mb-4">
            <div class="table-card-title"><span><i class="bi bi-image me-2" style="color:#8e44ad;"></i>Upload Hotel Photo</span></div>
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Select Hotel</label>
                        <select name="image_hotel_id" class="form-control" required>
                            <option value="">-- Select Hotel --</option>
                            <?php $hotelsDropdown->data_seek(0); while($h=$hotelsDropdown->fetch_assoc()): ?>
                                <option value="<?=$h['Hotel_Id']?>"><?=htmlspecialchars($h['Hotel_Name'])?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Choose Image (JPG, PNG, WEBP — max 5MB)</label>
                        <input type="file" name="hotel_image" class="form-control" accept="image/jpeg,image/png,image/webp" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Caption <span class="text-muted">(e.g. Pool, Lobby)</span></label>
                        <input type="text" name="image_caption" class="form-control" placeholder="e.g. Swimming Pool">
                    </div>
                    <div class="col-md-1 d-flex align-items-end pb-1">
                        <div class="form-check">
                            <input type="checkbox" name="is_cover" class="form-check-input" id="isCover">
                            <label class="form-check-label fw-semibold" for="isCover">Cover</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="upload_image" class="btn btn-trivago w-100"><i class="bi bi-upload me-1"></i>Upload</button>
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
                    <thead><tr><th>ID</th><th>Name</th><th>City</th><th>Country</th><th>Rating</th><th>Photos</th><th>Added</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php $hotels->data_seek(0); while($h=$hotels->fetch_assoc()):
                            $hotelImages = $conn->query("SELECT * FROM Hotel_Images WHERE Image_HotelId={$h['Hotel_Id']} ORDER BY Image_IsCover DESC, Image_AddedDate ASC");
                        ?>
                        <tr>
                            <td><?=$h['Hotel_Id']?></td>
                            <td><?=htmlspecialchars($h['Hotel_Name'])?></td>
                            <td><?=htmlspecialchars($h['Hotel_City'])?></td>
                            <td><?=htmlspecialchars($h['Hotel_Country'])?></td>
                            <td><?php for($s=1;$s<=$h['Hotel_Rating'];$s++): ?><i class="bi bi-star-fill" style="color:#f5a623;font-size:11px;"></i><?php endfor; ?></td>
                            <td>
                                <?php if($hotelImages && $hotelImages->num_rows > 0): ?>
                                <div class="image-grid">
                                    <?php while($img=$hotelImages->fetch_assoc()): ?>
                                    <div class="image-item <?=$img['Image_IsCover']?'is-cover':''?>">
                                        <img src="/trivago/<?=htmlspecialchars($img['Image_Path'])?>" alt="">
                                        <?php if($img['Image_IsCover']): ?><span class="cover-badge">COVER</span><?php endif; ?>
                                        <?php if(!empty($img['Image_Caption'])): ?>
                                            <span class="img-caption-label"><?=htmlspecialchars($img['Image_Caption'])?></span>
                                        <?php endif; ?>
                                        <div class="image-actions">
                                            <?php if(!$img['Image_IsCover']): ?>
                                            <a href="manage_hotels.php?set_cover=<?=$img['Image_Id']?>&hotel_id=<?=$h['Hotel_Id']?>" title="Set as cover" class="btn btn-sm btn-light p-0 px-1">
                                                <i class="bi bi-star-fill" style="font-size:11px;"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="manage_hotels.php?delete_image=<?=$img['Image_Id']?>" onclick="return confirm('Delete this image?')" class="btn btn-sm btn-danger p-0 px-1">
                                                <i class="bi bi-trash" style="font-size:11px;"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                                <?php else: ?><span class="text-muted small">No photos</span><?php endif; ?>
                            </td>
                            <td><?=$h['Hotel_AddedDate']?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal<?=$h['Hotel_Id']?>"><i class="bi bi-pencil"></i></button>
                                <a href="manage_hotels.php?delete=<?=$h['Hotel_Id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete <?=htmlspecialchars($h['Hotel_Name'])?>? This will also delete its rooms and images.')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>

                        <!-- EDIT MODAL -->
                        <div class="modal fade" id="editModal<?=$h['Hotel_Id']?>" tabindex="-1">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content rounded-4">
                                    <div class="modal-header border-0"><h5 class="modal-title">Edit Hotel</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <form method="POST">
                                            <input type="hidden" name="hotel_id" value="<?=$h['Hotel_Id']?>">
                                            <div class="row g-3">
                                                <div class="col-md-6"><label class="form-label fw-semibold">Hotel Name</label><input type="text" name="hotel_name" class="form-control" value="<?=htmlspecialchars($h['Hotel_Name'])?>" required></div>
                                                <div class="col-md-6"><label class="form-label fw-semibold">Address</label><input type="text" name="hotel_address" class="form-control" value="<?=htmlspecialchars($h['Hotel_Address'])?>" required></div>
                                                <div class="col-md-4"><label class="form-label fw-semibold">City</label><input type="text" name="hotel_city" class="form-control" value="<?=htmlspecialchars($h['Hotel_City'])?>" required></div>
                                                <div class="col-md-4"><label class="form-label fw-semibold">Country</label><input type="text" name="hotel_country" class="form-control" value="<?=htmlspecialchars($h['Hotel_Country'])?>" required></div>
                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">Star Rating</label>
                                                    <select name="hotel_rating" class="form-control" required>
                                                        <?php for($i=1;$i<=5;$i++): ?><option value="<?=$i?>" <?=$i==$h['Hotel_Rating']?'selected':''?>><?=$i?> Star<?=$i>1?'s':''?></option><?php endfor; ?>
                                                    </select>
                                                </div>
                                                <div class="col-12"><label class="form-label fw-semibold">Description</label><textarea name="hotel_description" class="form-control" rows="3" required><?=htmlspecialchars($h['Hotel_Description'])?></textarea></div>
                                            </div>
                                            <div class="mt-3">
                                                <button type="submit" name="edit_hotel" class="btn btn-trivago"><i class="bi bi-check-circle me-1"></i>Save Changes</button>
                                                <button type="button" class="btn btn-outline-secondary ms-2" data-bs-dismiss="modal">Cancel</button>
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