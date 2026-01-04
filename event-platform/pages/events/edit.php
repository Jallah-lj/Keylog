<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['organizer', 'admin'])) {
    die("Access denied");
}

$event_id = $_GET['id'] ?? null;
if (!$event_id) {
    header("Location: ../dashboard.php");
    exit();
}

$msg = '';

// Fetch existing event
if ($_SESSION['user_role'] == 'admin') {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND organizer_id = ?");
    $stmt->execute([$event_id, $_SESSION['user_id']]);
}
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die("Event not found or permission denied.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $event_date = $_POST['event_date'];
    $category = $_POST['category'];
    $currency = $_POST['currency'];

    $image_path = $event['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../../uploads/';
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = $file_name;
        }
    }

    // Insert Ticket Types logic
    if (isset($_POST['ticket_name']) && is_array($_POST['ticket_name'])) {
        // ... (Ticket logic remains same) ...
        $ins_stmt = $pdo->prepare("INSERT INTO ticket_types (event_id, name, price, quantity) VALUES (?, ?, ?, ?)");
        $upd_stmt = $pdo->prepare("UPDATE ticket_types SET name=?, price=?, quantity=? WHERE id=? AND event_id=?");
        
        for ($i = 0; $i < count($_POST['ticket_name']); $i++) {
            $t_id = $_POST['ticket_id'][$i] ?? null;
            $t_name = $_POST['ticket_name'][$i];
            $t_price = $_POST['ticket_price'][$i];
            $t_qty = $_POST['ticket_qty'][$i];
            
            if ($t_name && $t_qty) {
                if ($t_id) {
                    $upd_stmt->execute([$t_name, $t_price, $t_qty, $t_id, $event_id]);
                } else {
                    $ins_stmt->execute([$event_id, $t_name, $t_price, $t_qty]);
                }
            }
        }
    }

    $update = $pdo->prepare("UPDATE events SET title=?, description=?, location=?, event_date=?, image=?, category=?, currency=? WHERE id=?");
    if ($update->execute([$title, $description, $location, $event_date, $image_path, $category, $currency, $event_id])) {
        $msg = "Event updated successfully!";
        // Refresh data
        $event['title'] = $title;
        $event['description'] = $description;
        $event['location'] = $location;
        $event['event_date'] = $event_date;
        $event['image'] = $image_path;
        $event['category'] = $category;
    } else {
        $msg = "Error updating event.";
    }
}

// Fetch Ticket Types for Display
$tt_stmt = $pdo->prepare("SELECT * FROM ticket_types WHERE event_id = ?");
$tt_stmt->execute([$event_id]);
$ticket_types = $tt_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Event - Organizer Panel</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="background-color: var(--dark-bg);">

<div class="admin-layout">
    <?php 
    if ($_SESSION['user_role'] == 'admin') {
        include '../admin/sidebar.php';
    } else {
        include '../organizer/sidebar.php';
    }
    ?>

    <main class="main-content">
        <div class="top-bar">
            <div class="breadcrumbs">
                <?php echo $_SESSION['user_role'] == 'admin' ? 'Admin / Management' : 'Organizer / Events'; ?> / Edit
            </div>
        </div>

        <div class="dashboard-panel">
            <h3 class="panel-title">Edit Event: <?php echo htmlspecialchars($event['title']); ?></h3>

            <?php if($msg): ?>
                <div style="background:rgba(52, 211, 153, 0.1); color:var(--success); padding:15px; border-radius:8px; margin-bottom:20px; text-align:center;">
                    <?php echo htmlspecialchars($msg); ?>
                    <div style="margin-top:10px;">
                        <a href="<?php echo $_SESSION['user_role'] == 'admin' ? '../admin/events.php' : '../organizer/events.php'; ?>" style="color:var(--success); font-weight:700;">Back to Events &rarr;</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data" class="modern-form">
                <div style="display:grid; grid-template-columns: 2fr 1fr; gap:30px;">
                    <!-- Left Column -->
                    <div>
                        <div style="margin-bottom:20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Title</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                        </div>

                        <div style="margin-bottom:20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Description</label>
                            <textarea name="description" rows="5" style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;"><?php echo htmlspecialchars($event['description']); ?></textarea>
                        </div>
                        
                        <div style="margin-bottom:20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Location</label>
                            <input type="text" name="location" value="<?php echo htmlspecialchars($event['location']); ?>" style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div>
                        <div style="margin-bottom:20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Date</label>
                            <input type="datetime-local" name="event_date" value="<?php echo date('Y-m-d\TH:i', strtotime($event['event_date'])); ?>" required style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                        </div>

                        <div style="margin-bottom:20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Category</label>
                            <select name="category" style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                                <?php
                                $categories = ['General', 'Music', 'Tech', 'Sports', 'Art', 'Workshop'];
                                foreach($categories as $cat) {
                                    $selected = ($event['category'] ?? 'General') === $cat ? 'selected' : '';
                                    echo "<option value='$cat' $selected>$cat</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div style="margin-bottom:20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Currency</label>
                            <select name="currency" required style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                                <option value="USD" <?php echo ($event['currency'] ?? 'USD') == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                <option value="LD" <?php echo ($event['currency'] ?? 'USD') == 'LD' ? 'selected' : ''; ?>>LD (L$)</option>
                            </select>
                        </div>

                         <div style="margin-bottom:20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Current Image</label>
                            <?php if($event['image']): ?>
                                <img src="../../uploads/<?php echo htmlspecialchars($event['image']); ?>" width="100%" style="border-radius:8px; border:1px solid var(--border); margin-bottom:10px;"><br>
                            <?php endif; ?>
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Change Image</label>
                            <input type="file" name="image" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                        </div>
                    </div>
                </div>

                <h3 style="margin:30px 0 20px 0; color:#fff; border-bottom:1px solid var(--border); padding-bottom:10px;">Ticket Types</h3>
                
                <div id="ticket-list">
                    <?php foreach($ticket_types as $tt): ?>
                        <div class="ticket-row" style="display:flex; gap:15px; margin-bottom:15px;">
                            <input type="hidden" name="ticket_id[]" value="<?php echo $tt['id']; ?>">
                            <input type="text" name="ticket_name[]" value="<?php echo htmlspecialchars($tt['name']); ?>" required style="flex:2; padding:10px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff;">
                            <input type="number" name="ticket_price[]" value="<?php echo htmlspecialchars($tt['price']); ?>" step="0.01" required style="flex:1; padding:10px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff;">
                            <input type="number" name="ticket_qty[]" value="<?php echo htmlspecialchars($tt['quantity']); ?>" required style="flex:1; padding:10px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff;">
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" onclick="addTicketRow()" style="background:rgba(255,255,255,0.1); border:1px solid var(--border); color:#fff; padding:8px 15px; border-radius:6px; cursor:pointer; margin-bottom:30px;">+ Add Another Ticket Type</button>

                <script>
                function addTicketRow() {
                    const div = document.createElement('div');
                    div.className = 'ticket-row';
                    div.style.cssText = 'display:flex; gap:15px; margin-bottom:15px;';
                    div.innerHTML = `
                        <input type="hidden" name="ticket_id[]" value="">
                        <input type="text" name="ticket_name[]" placeholder="Ticket Name" required style="flex:2; padding:10px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff;">
                        <input type="number" name="ticket_price[]" placeholder="Price" step="0.01" required style="flex:1; padding:10px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff;">
                        <input type="number" name="ticket_qty[]" placeholder="Qty" required style="flex:1; padding:10px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff;">
                        <button type="button" onclick="this.parentElement.remove()" style="background:rgba(248, 113, 113, 0.2); color:#f87171; border:none; width:40px; border-radius:8px; cursor:pointer;">×</button>
                    `;
                    document.getElementById('ticket-list').appendChild(div);
                }
                </script>
                
                <div style="border-top:1px solid var(--border); padding-top:20px; text-align:right;">
                <a href="/pages/organizer/events.php" style="color:var(--text-muted); text-decoration:none; margin-right:20px;">Cancel</a>
                    <button type="submit" class="hero-btn" style="padding:12px 30px; font-size:1rem;">Update Event</button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>
