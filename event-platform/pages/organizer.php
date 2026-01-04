<?php
session_start();
require_once '../config/database.php';
require_once '../includes/icons.php';

$organizer_id = $_GET['id'] ?? null;
if (!$organizer_id) {
    header("Location: ../index.php");
    exit();
}

// Fetch Organizer Details
$stmt = $pdo->prepare("SELECT name, email, avatar FROM users WHERE id = ? AND role = 'organizer'");
$stmt->execute([$organizer_id]);
$organizer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$organizer) {
    die("Organizer not found.");
}

// Fetch Organizer's Events
$estmt = $pdo->prepare("SELECT * FROM events WHERE organizer_id = ? AND event_date >= NOW() ORDER BY event_date ASC");
$estmt->execute([$organizer_id]);
$events = $estmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Past Events count
$pstmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE organizer_id = ? AND event_date < NOW()");
$pstmt->execute([$organizer_id]);
$past_count = $pstmt->fetchColumn();
?>
<?php require_once '../includes/header.php'; ?>
<main>
    <div style="text-align:center; background: var(--card-bg); backdrop-filter: blur(10px); padding: 50px; border-radius: 20px; border: 1px solid var(--border); margin-bottom: 50px;">
        <div style="position: relative; display: inline-block;">
            <?php if($organizer['avatar']): ?>
                <img src="/uploads/<?php echo htmlspecialchars($organizer['avatar']); ?>" style="width:140px; height:140px; border-radius:70px; object-fit:cover; border: 5px solid rgba(255,255,255,0.05); box-shadow: 0 15px 35px rgba(0,0,0,0.4);">
            <?php else: ?>
                <div style="width:140px; height:140px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius:70px; margin:auto; display:flex; align-items:center; justify-content:center; font-size: 3.5rem; color: #fff; font-weight:800; box-shadow: 0 15px 35px rgba(0,0,0,0.4);">
                    <?php echo strtoupper(substr($organizer['name'], 0, 1)); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <h2 style="margin: 20px 0 10px 0; font-size: 2.2rem; color: #fff; font-weight: 800;"><?php echo htmlspecialchars($organizer['name']); ?></h2>
        <div style="display:inline-block; padding: 4px 15px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 20px; font-size: 0.85rem; color: var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:1px; margin-bottom: 25px;">Verified Organizer</div>
        
        <div style="display:flex; justify-content:center; gap:30px; margin-top:10px;">
            <div style="text-align:center; min-width:120px; padding:15px; background:rgba(99, 102, 241, 0.1); border-radius:12px; border:1px solid rgba(99, 102, 241, 0.2);">
                <div style="font-size:1.5rem; font-weight:800; color:var(--primary-light);"><?php echo count($events); ?></div>
                <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:600; margin-top:3px;">Upcoming</div>
            </div>
            <div style="text-align:center; min-width:120px; padding:15px; background:rgba(236, 72, 153, 0.1); border-radius:12px; border:1px solid rgba(236, 72, 153, 0.2);">
                <div style="font-size:1.5rem; font-weight:800; color:var(--secondary);"><?php echo $past_count; ?></div>
                <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:600; margin-top:3px;">Past Events</div>
            </div>
        </div>
    </div>

    <h3 style="font-size: 1.5rem; margin-bottom: 25px; color: #fff; border-left: 4px solid var(--primary); padding-left: 15px;">Upcoming Events</h3>
    <div class="events-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px;">
        <?php
        if (count($events) > 0) {
            foreach ($events as $event) {
                $currency_symbol = ($event['currency'] ?? 'USD') == 'USD' ? '$' : 'L$';
                $image_src = $event['image'] ? '/uploads/'.htmlspecialchars($event['image']) : '/assets/images/placeholder.jpg';
                ?>
                <div class="event-card" onclick="window.location.href='/pages/event_details.php?id=<?php echo $event['id']; ?>'" style="cursor: pointer;">
                    <div style="position: relative; height: 180px; border-radius: 12px; overflow: hidden; margin-bottom: 18px;">
                        <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <div style="position: absolute; top: 12px; right: 12px; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); padding: 4px 10px; border-radius: 20px; font-weight: 700; color: var(--accent); font-size: 0.85rem;">
                            <?php echo $currency_symbol . htmlspecialchars($event['price']); ?>
                        </div>
                    </div>
                    
                    <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                        <?php echo htmlspecialchars($event['category'] ?? 'General'); ?>
                    </div>

                    <h4 style="margin: 0 0 15px 0; font-size: 1.15rem; color: #fff; line-height: 1.4;"><?php echo htmlspecialchars($event['title']); ?></h4>
                    
                    <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: auto;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;">
                            <?php echo Icons::get('calendar', 'width:14px; height:14px; opacity:0.7;'); ?> <?php echo date('M j, Y • g:i a', strtotime($event['event_date'])); ?>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <?php echo Icons::get('map-pin', 'width:14px; height:14px; opacity:0.7;'); ?> <?php echo htmlspecialchars($event['location']); ?>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<div style="grid-column: 1/-1; text-align: center; padding: 60px; background: rgba(255,255,255,0.02); border: 1px dashed var(--border); border-radius: 12px; color: var(--text-muted);">
                    <div style="margin-bottom: 15px; color: var(--text-muted); opacity: 0.3;">
                        ' . Icons::get('ticket', 'width:64px; height:64px; margin: 0 auto;') . '
                    </div>
                    <p>No upcoming events currently scheduled.</p>
                  </div>';
        }
        ?>
    </div>
</main>
<?php require_once '../includes/footer.php'; ?>
