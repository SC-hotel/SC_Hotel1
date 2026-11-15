<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
redirectIfNotAdmin();

$message = '';
$messageType = '';

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Count total rooms for pagination
$totalRooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$totalPages = ceil($totalRooms / $limit);

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $roomId = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT image FROM rooms WHERE id = ?");
    $stmt->execute([$roomId]);
    $room = $stmt->fetch();
    if ($room && $room['image'] != 'default_room.jpg') {
        $imagePath = UPLOAD_DIR . 'rooms/' . $room['image'];
        if (file_exists($imagePath)) unlink($imagePath);
    }
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
    if ($stmt->execute([$roomId])) {
        $message = "Room deleted successfully!";
        $messageType = "success";
        header("Location: manage_rooms.php?msg=" . urlencode($message) . "&type=$messageType&page=" . $page);
        exit;
    } else {
        $message = "Error deleting room.";
        $messageType = "danger";
    }
}

// Handle Add / Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_number = sanitizeInput($_POST['room_number']);
    $type = sanitizeInput($_POST['type']);
    $price = floatval($_POST['price']);
    $desc = sanitizeInput($_POST['description']);
    $capacity = intval($_POST['capacity']);
    $status = sanitizeInput($_POST['status']);
    $image = $_POST['existing_image'] ?? 'default_room.jpg';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $uploaded = uploadFile($_FILES['image'], UPLOAD_DIR . 'rooms/');
        if ($uploaded) {
            if ($image != 'default_room.jpg') {
                $oldPath = UPLOAD_DIR . 'rooms/' . $image;
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $image = $uploaded;
        }
    }
    
    if (isset($_POST['room_id']) && !empty($_POST['room_id'])) {
        $roomId = $_POST['room_id'];
        $stmt = $pdo->prepare("UPDATE rooms SET room_number=?, type=?, price_per_night=?, description=?, capacity=?, status=?, image=? WHERE id=?");
        if ($stmt->execute([$room_number, $type, $price, $desc, $capacity, $status, $image, $roomId])) {
            $message = "Room updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error updating room.";
            $messageType = "danger";
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO rooms (room_number, type, price_per_night, description, capacity, status, image) VALUES (?,?,?,?,?,?,?)");
        if ($stmt->execute([$room_number, $type, $price, $desc, $capacity, $status, $image])) {
            $message = "Room added successfully!";
            $messageType = "success";
        } else {
            $message = "Error adding room.";
            $messageType = "danger";
        }
    }
    header("Location: manage_rooms.php?msg=" . urlencode($message) . "&type=$messageType&page=" . $page);
    exit;
}

// Handle message from redirect
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $messageType = $_GET['type'] ?? 'info';
}

// Fetch rooms for current page
$stmt = $pdo->prepare("SELECT * FROM rooms ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - SC Hotel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>assets/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* [All styles unchanged – keep exactly as before] */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        
        /* Fixed Sidebar - Deep Green Theme */
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh; width: 280px;
            background: linear-gradient(135deg, #0b3d1a 0%, #145c2e 100%);
            z-index: 1000; transition: all 0.3s ease; box-shadow: 5px 0 30px rgba(0,0,0,0.1); overflow-y: auto;
        }
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-track { background: #0b3d1a; }
        .sidebar::-webkit-scrollbar-thumb { background: #2ecc71; border-radius: 5px; }
        .sidebar-brand { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .sidebar-brand h3 { color: white; font-weight: 600; margin: 0; letter-spacing: 1px; }
        .sidebar-brand p { color: #a0d4a4; font-size: 0.8rem; margin-top: 8px; }
        .sidebar-nav { padding: 0 20px; }
        .sidebar-nav a { display: flex; align-items: center; padding: 12px 16px; margin: 8px 0; color: #cfdfed; text-decoration: none; border-radius: 12px; transition: all 0.3s ease; font-weight: 500; }
        .sidebar-nav a i { width: 28px; font-size: 1.2rem; margin-right: 12px; }
        .sidebar-nav a:hover { background: rgba(46, 204, 113, 0.2); color: white; transform: translateX(5px); }
        .sidebar-nav a.active { background: linear-gradient(90deg, #27ae60, #2ecc71); color: white; box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3); }
        
        .main-content { margin-left: 280px; min-height: 100vh; background: #f4f6f9; }
        .top-navbar { background: white; padding: 15px 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 999; }
        .page-title { font-size: 1.5rem; font-weight: 600; color: #2c3e50; margin: 0; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-avatar-badge { width: 40px; height: 40px; background: #2ecc71; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        
        .premium-card { background: white; border-radius: 20px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s; overflow: hidden; }
        .premium-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
        .room-img { width: 60px; height: 60px; object-fit: cover; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        .table th { font-weight: 600; border-top: none; }
        
        .btn-primary { background: linear-gradient(90deg, #27ae60, #2ecc71); border: none; transition: all 0.3s; }
        .btn-primary:hover { background: linear-gradient(90deg, #2ecc71, #27ae60); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3); }
        .btn-warning, .btn-danger, .btn-secondary { border-radius: 8px; }
        .modal-header { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; border-radius: 20px 20px 0 0; }
        .modal-content { border-radius: 20px; border: none; }
        .form-control, .form-select { border-radius: 12px; padding: 10px 15px; }
        .btn-close-white { filter: brightness(0) invert(1); }
        
        .pagination .page-item.active .page-link { background-color: #27ae60; border-color: #27ae60; }
        .pagination .page-link { color: #27ae60; }
        .pagination .page-link:hover { color: #2ecc71; }
        
        @media (max-width: 768px) { .sidebar { width: 80px; } .main-content { margin-left: 80px; } .sidebar-brand h3, .sidebar-brand p, .sidebar-nav a span { display: none; } }


        .sidebar-logo {
    width: 120px;
    height: auto;
    background: #e9e9e4;
    padding: 5px;
    display: inline-block;
    border-radius: 50%;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
    </style>
</head>
<body>

<!-- Fixed Sidebar (Deep Green) -->
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="<?= SITE_URL ?>assets/img/logo.png" alt="SC Hotel Logo" class="sidebar-logo">
        <p>Admin Panel</p>
    </div>
    <div class="sidebar-nav">
        <a href="index.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
        <a href="manage_users.php"><i class="fas fa-users"></i><span>Manage Users</span></a>
        <a href="manage_rooms.php" class="active"><i class="fas fa-bed"></i><span>Manage Rooms</span></a>
        <a href="manage_bookings.php"><i class="fas fa-calendar-check"></i><span>Manage Bookings</span></a>
        <a href="reports.php"><i class="fas fa-chart-line"></i><span>Reports</span></a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<div class="main-content">
    <div class="top-navbar">
        <h4 class="page-title"><i class="fas fa-bed me-2" style="color: #2ecc71;"></i>Manage Rooms</h4>
        <div class="user-info">
            <span>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <div class="user-avatar-badge"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
        </div>
    </div>

    <div class="container-fluid p-4">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show shadow-sm rounded-3" role="alert">
                <i class="fas fa-<?= $messageType == 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0"><i class="fas fa-list text-success me-2"></i>All Rooms (<?= $totalRooms ?> total)</h5>
            <button class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                <i class="fas fa-plus me-2"></i>Add New Room
            </button>
        </div>

        <div class="premium-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Image</th><th>Room No.</th><th>Type</th><th>Price/Night</th><th>Capacity</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if (count($rooms) > 0): ?>
                                <?php foreach ($rooms as $room): ?>
                                <tr>
                                    
                                    <td><img src="../assets/uploads/rooms/<?= htmlspecialchars($room['image']) ?>" class="room-img" onerror="this.src='../assets/uploads/rooms/default_room.jpg'"></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($room['room_number']) ?></td>
                                    <td><?= htmlspecialchars($room['type']) ?></td>
                                    <td>₱<?= number_format($room['price_per_night'], 2) ?></td>
                                    <td><?= $room['capacity'] ?> persons</td>
                                    <td>
                                        <span class="badge rounded-pill bg-<?= $room['status'] == 'available' ? 'success' : 'danger' ?> px-3 py-2">
                                            <?= $room['status'] == 'available' ? 'Available' : 'Not Available' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-room" 
                                                data-id="<?= $room['id'] ?>"
                                                data-number="<?= htmlspecialchars($room['room_number']) ?>"
                                                data-type="<?= htmlspecialchars($room['type']) ?>"
                                                data-price="<?= $room['price_per_night'] ?>"
                                                data-capacity="<?= $room['capacity'] ?>"
                                                data-desc="<?= htmlspecialchars($room['description']) ?>"
                                                data-status="<?= $room['status'] ?>"
                                                data-image="<?= htmlspecialchars($room['image']) ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=<?= $room['id'] ?>&page=<?= $page ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this room?')"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center text-muted py-5">No rooms found. Add your first room using the "Add New Room" button.<?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white border-0 py-3">
                    <nav>
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item"><a class="page-link" href="?page=<?= $page-1 ?>">Previous</a></li>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item"><a class="page-link" href="?page=<?= $page+1 ?>">Next</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Room Modal - Updated Room Types -->
<div class="modal fade" id="addRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add New Room</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label fw-semibold">Room Number *</label><input type="text" name="room_number" class="form-control" required></div>
                        <div class="col-md-4 mb-3"><label class="form-label fw-semibold">Room Type *</label>
                            <select name="type" class="form-select" required>
                                <option value="Standard Twin">Standard Twin</option>
                                <option value="Standard Double">Standard Double</option>
                                <option value="Deluxe">Deluxe</option>
                                <option value="Superior">Superior</option>
                                <option value="Suite">Suite</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3"><label class="form-label fw-semibold">Price per Night (₱) *</label><input type="number" step="0.01" name="price" class="form-control" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Capacity (persons) *</label><input type="number" name="capacity" class="form-control" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Status</label><select name="status" class="form-select"><option value="available">Available</option><option value="not_available">Not Available</option></select></div>
                        <div class="col-12 mb-3"><label class="form-label fw-semibold">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Room Image</label><input type="file" name="image" class="form-control" accept="image/*"><small class="text-muted">Leave empty to use default image</small><input type="hidden" name="existing_image" value="default_room.jpg"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Room Modal - Updated Room Types -->
<div class="modal fade" id="editRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Room</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="room_id" id="edit_room_id">
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label fw-semibold">Room Number *</label><input type="text" name="room_number" id="edit_room_number" class="form-control" required></div>
                        <div class="col-md-4 mb-3"><label class="form-label fw-semibold">Room Type *</label>
                            <select name="type" id="edit_type" class="form-select" required>
                                <option value="Standard Twin">Standard Twin</option>
                                <option value="Standard Double">Standard Double</option>
                                <option value="Deluxe">Deluxe</option>
                                <option value="Superior">Superior</option>
                                <option value="Suite">Suite</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3"><label class="form-label fw-semibold">Price per Night (₱) *</label><input type="number" step="0.01" name="price" id="edit_price" class="form-control" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Capacity (persons) *</label><input type="number" name="capacity" id="edit_capacity" class="form-control" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Status</label><select name="status" id="edit_status" class="form-select"><option value="available">Available</option><option value="not_available">Not Available</option></select></div>
                        <div class="col-12 mb-3"><label class="form-label fw-semibold">Description</label><textarea name="description" id="edit_desc" class="form-control" rows="3"></textarea></div>
                        <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Room Image</label><input type="file" name="image" class="form-control" accept="image/*"><small class="text-muted">Leave empty to keep current image</small><input type="hidden" name="existing_image" id="edit_existing_image"><div id="currentImagePreview" class="mt-2"></div></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('.edit-room').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_room_id').value = this.dataset.id;
            document.getElementById('edit_room_number').value = this.dataset.number;
            document.getElementById('edit_type').value = this.dataset.type;
            document.getElementById('edit_price').value = this.dataset.price;
            document.getElementById('edit_capacity').value = this.dataset.capacity;
            document.getElementById('edit_desc').value = this.dataset.desc;
            document.getElementById('edit_status').value = this.dataset.status;
            document.getElementById('edit_existing_image').value = this.dataset.image;
            const previewDiv = document.getElementById('currentImagePreview');
            const imageName = this.dataset.image;
            if (imageName && imageName !== 'default_room.jpg') {
                previewDiv.innerHTML = `<img src="../assets/uploads/rooms/${imageName}" width="80" class="img-thumbnail rounded"> <small>Current image</small>`;
            } else {
                previewDiv.innerHTML = '<small class="text-muted">No custom image uploaded</small>';
            }
            new bootstrap.Modal(document.getElementById('editRoomModal')).show();
        });
    });
</script>
</body>
</html>