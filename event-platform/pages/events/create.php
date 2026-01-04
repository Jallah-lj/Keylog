<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['organizer', 'admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $event_date = $_POST['event_date'];
    $price = $_POST['price'];
    $max_capacity = $_POST['max_capacity'];
    $organizer_id = $_SESSION['user_id'];
    $currency = $_POST['currency'] ?? 'USD';

    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../../uploads/';
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = $file_name;
        } else {
            $error = "Failed to upload image.";
        }
    }

    if (empty($error)) {
        if (empty($title) || empty($event_date)) {
            $error = "Title and Date are required.";
        } else {
            $category = $_POST['category'] ?? 'General';
            $total_capacity = 0;
            if (isset($_POST['ticket_qty']) && is_array($_POST['ticket_qty'])) {
                 $total_capacity = array_sum($_POST['ticket_qty']);
            }

            // Insert Event (Price 0, Capacity = Sum of Tickets)
            $stmt = $pdo->prepare("INSERT INTO events (organizer_id, title, description, location, event_date, price, max_capacity, image, category, currency) VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, ?)");
            if ($stmt->execute([$organizer_id, $title, $description, $location, $event_date, $total_capacity, $image_path, $category, $currency])) {
                $event_id = $pdo->lastInsertId();

                // Insert Ticket Types
                if (isset($_POST['ticket_name']) && is_array($_POST['ticket_name'])) {
                    $t_stmt = $pdo->prepare("INSERT INTO ticket_types (event_id, name, price, quantity) VALUES (?, ?, ?, ?)");
                    for ($i = 0; $i < count($_POST['ticket_name']); $i++) {
                        $t_name = $_POST['ticket_name'][$i];
                        $t_price = $_POST['ticket_price'][$i];
                        $t_qty = $_POST['ticket_qty'][$i];
                        if ($t_name && $t_qty) {
                            $t_stmt->execute([$event_id, $t_name, $t_price, $t_qty]);
                        }
                    }
                }
                $success = "Event created successfully!";
            } else {
                $error = "Failed to create event.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Event - Organizer Panel</title>
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
                <?php echo $_SESSION['user_role'] == 'admin' ? 'Admin / Management' : 'Organizer / Events'; ?> / Create
            </div>
        </div>

        <div class="dashboard-panel">
            <h3 class="panel-title">Create New Event</h3>

            <?php if($success): ?>
                <div style="background:rgba(52, 211, 153, 0.1); color:var(--success); padding:15px; border-radius:8px; margin-bottom:20px; text-align:center;">
                    <?php echo htmlspecialchars($success); ?>
                    <div style="margin-top:10px;">
                        <a href="<?php echo $_SESSION['user_role'] == 'admin' ? '../admin/events.php' : '../organizer/events.php'; ?>" style="color:var(--success); font-weight:700;">View All Events &rarr;</a>
                    </div>
                </div>
            <?php endif; ?>
            <?php if($error): ?>
                <div style="background:rgba(248, 113, 113, 0.1); color:var(--danger); padding:15px; border-radius:8px; margin-bottom:20px; text-align:center;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="" enctype="multipart/form-data" class="modern-form">
                <div style="display:grid; grid-template-columns: 2fr 1fr; gap:30px;">
                    <!-- Left Column -->
                    <div>
                        <div style="margin-bottom:20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Event Title *</label>
                            <input type="text" name="title" required style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                        </div>
                        
                        <div style="margin-bottom:20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Description</label>
                            <textarea name="description" rows="5" style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;"></textarea>
                        </div>

                        <div style="margin-bottom:20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Location</label>
                            <input type="text" name="location" style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div>
                        <div style="margin-bottom:20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Date & Time *</label>
                            <input type="datetime-local" name="event_date" required style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                        </div>

                        <div style="margin-bottom:20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Category</label>
                            <select name="category" style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                                <option value="General">General</option>
                                <option value="Music">Music</option>
                                <option value="Tech">Tech</option>
                                <option value="Sports">Sports</option>
                                <option value="Art">Art</option>
                                <option value="Workshop">Workshop</option>
                            </select>
                        </div>

                        <div style="margin-bottom:20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Currency</label>
                            <select name="currency" required style="width:100%; padding:12px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                                <option value="USD">USD ($)</option>
                                <option value="LD">LD (L$)</option>
                            </select>
                        </div>

                        <div style="margin-bottom:20px;">
                            <label style="display:block; color:var(--text-muted); margin-bottom:8px;">Event Image</label>
                            <input type="file" name="image" accept="image/*" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; color:#fff;">
                        </div>
                    </div>
                </div>

                <h4 style="margin:30px 0 20px 0; color:#fff; border-bottom:1px solid var(--border); padding-bottom:10px;">Ticket Types</h4>
                
                <div id="ticket-list">
                    <div class="ticket-row" style="display:flex; gap:15px; margin-bottom:15px;">
                        <input type="text" name="ticket_name[]" placeholder="Ticket Name (e.g. VIP)" required style="flex:2; padding:10px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff;">
                        <input type="number" name="ticket_price[]" placeholder="Price" step="0.01" required style="flex:1; padding:10px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff;">
                        <input type="number" name="ticket_qty[]" placeholder="Qty" required style="flex:1; padding:10px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff;">
                    </div>
                </div>
                
                <button type="button" onclick="addTicketRow()" style="background:rgba(255,255,255,0.1); border:1px solid var(--border); color:#fff; padding:8px 15px; border-radius:6px; cursor:pointer; margin-bottom:30px;">+ Add Another Ticket Type</button>

                <script>
                function addTicketRow() {
                    const div = document.createElement('div');
                    div.className = 'ticket-row';
                    div.style.cssText = 'display:flex; gap:15px; margin-bottom:15px;';
                    div.innerHTML = `
                        <input type="text" name="ticket_name[]" placeholder="Ticket Name" required style="flex:2; padding:10px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff;">
                        <input type="number" name="ticket_price[]" placeholder="Price" step="0.01" required style="flex:1; padding:10px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff;">
                        <input type="number" name="ticket_qty[]" placeholder="Qty" required style="flex:1; padding:10px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff;">
                        <button type="button" onclick="this.parentElement.remove()" style="background:rgba(248, 113, 113, 0.2); color:#f87171; border:none; width:40px; border-radius:8px; cursor:pointer;">×</button>
                    `;
                    document.getElementById('ticket-list').appendChild(div);
                }
                </script>
                
                <div style="border-top:1px solid var(--border); padding-top:20px; text-align:right;">
                    <button type="submit" class="hero-btn" style="padding:12px 30px; font-size:1rem;">Publish Event</button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>
