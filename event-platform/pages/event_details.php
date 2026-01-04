<?php
session_start();
require_once '../config/database.php';
require_once '../includes/icons.php';

if (!isset($_GET['id'])) {
    header("Location: ../index.php");
    exit();
}

$event_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT e.*, u.name as organizer_name FROM events e JOIN users u ON e.organizer_id = u.id WHERE e.id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "Event not found.";
    exit();
}

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rating'], $_SESSION['user_id'])) {
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    $uid = $_SESSION['user_id'];
    
    // Check if user booked
    $check_booking = $pdo->prepare("SELECT id FROM bookings WHERE user_id = ? AND event_id = ?");
    $check_booking->execute([$uid, $event_id]);
    
    if ($check_booking->rowCount() > 0) {
        $ins_review = $pdo->prepare("INSERT INTO reviews (user_id, event_id, rating, comment) VALUES (?, ?, ?, ?)");
        $ins_review->execute([$uid, $event_id, $rating, $comment]);
        header("Location: event_details.php?id=$event_id&msg=reviewed");
        exit();
    } else {
        $error = "You must book this event to review it.";
    }
}

// Fetch Reviews
$rev_stmt = $pdo->prepare("SELECT r.*, u.name, u.avatar FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.event_id = ? ORDER BY r.created_at DESC");
$rev_stmt->execute([$event_id]);
$reviews = $rev_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Average
$avg_rating = 0;
if (count($reviews) > 0) {
    $sum = 0;
    foreach($reviews as $r) $sum += $r['rating'];
    $avg_rating = round($sum / count($reviews), 1);
}
?>
<?php require_once '../includes/header.php'; ?>
<main style="background: var(--card-bg); backdrop-filter: blur(10px); padding:40px; border-radius:16px;">
    <div style="display:flex; gap:40px; flex-wrap:wrap;">
        <div style="flex:1; min-width:300px;">
            <?php if ($event['image']): ?>
                <img src="/uploads/<?php echo htmlspecialchars($event['image']); ?>" style="width:100%; border-radius:10px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
            <?php else: ?>
                <div style="width:100%; aspect-ratio:1; background:rgba(255,255,255,0.05); border:1px dashed var(--border); border-radius:10px; display:flex; align-items:center; justify-content:center; color:var(--text-muted);">
                    No Preview Available
                </div>
            <?php endif; ?>
        </div>
        
        <div style="flex:1; min-width:300px;">
            <span class="category-tag" style="background: rgba(99, 102, 241, 0.2); color: #a5b4fc; padding: 4px 10px; border-radius: 20px; font-weight:600; font-size: 0.9em;"><?php echo htmlspecialchars($event['category']); ?></span>
            <h2 style="font-size: 2.5em; margin: 10px 0; color:#fff;"><?php echo htmlspecialchars($event['title']); ?></h2>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin: 20px 0; background: rgba(0,0,0,0.2); padding:20px; border-radius:10px; border: 1px solid var(--border);">
                <div>
                    <strong style="color:var(--primary);">Organizer</strong><br>
                    <a href="/pages/organizer.php?id=<?php echo $event['organizer_id']; ?>" style="color:#fff; text-decoration:none; font-weight:600;"><?php echo htmlspecialchars($event['organizer_name']); ?></a>
                </div>
                <div>
                    <strong style="color:var(--primary);">Date</strong><br>
                    <span style="color:#fff;"><?php echo date('F j, Y, g:i a', strtotime($event['event_date'])); ?></span>
                </div>
                <div>
                    <strong style="color:var(--primary);">Location</strong><br>
                    <span style="color:#fff;"><?php echo htmlspecialchars($event['location']); ?></span>
                </div>
                <div>
                    <strong style="color:var(--primary);">Price</strong><br>
                    <span style="color:var(--accent); font-weight:700;"><?php echo (($event['currency'] ?? 'USD') == 'USD' ? '$' : 'L$') . htmlspecialchars($event['price']); ?></span>
                </div>
            </div>

            <h3 style="color:#fff;">About Event</h3>
            <p style="color:#cbd5e1; line-height:1.8; margin-bottom:40px;"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
            
            <div style="margin-top:30px; background: rgba(0,0,0,0.3); padding:30px; border-radius:16px; border: 1px solid var(--border);">
                <h3 style="margin-top:0; color:#fff; display: flex; align-items: center; gap: 10px;">
                    Reviews (<?php echo count($reviews); ?>) 
                    <span style="color:#facc15; display: flex; align-items: center; gap: 4px;">
                        <?php echo Icons::get('star', 'width:18px; height:18px; fill: currentColor;'); ?> <?php echo $avg_rating; ?>
                    </span>
                </h3>
                
                <?php if(count($reviews) > 0): ?>
                    <div style="max-height:300px; overflow-y:auto; margin-bottom:30px; padding-right:15px;" class="custom-scrollbar">
                        <?php foreach($reviews as $review): ?>
                            <div style="border-bottom:1px solid rgba(255,255,255,0.05); padding:15px 0;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <strong style="color:var(--primary-light);"><?php echo htmlspecialchars($review['name']); ?></strong>
                                    <div style="color:#facc15; display: flex; gap: 2px;">
                                        <?php for($i=0; $i<$review['rating']; $i++): ?>
                                            <?php echo Icons::get('star', 'width:12px; height:12px; fill: currentColor;'); ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p style="font-size:0.9rem; margin-top:8px; color:#cbd5e1;"><?php echo htmlspecialchars($review['comment']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-muted); font-style:italic; margin-bottom:20px;">No reviews yet. Be the first!</p>
                <?php endif; ?>

                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_role'] == 'attendee'): ?>
                    <form method="post" class="modern-form" style="margin-top:20px; border-top:1px solid rgba(255,255,255,0.1); padding-top:25px;">
                        <h4 style="color:#fff; margin-bottom:15px;">Leave a Review</h4>
                        <div style="display:flex; gap:10px; margin-bottom:15px;">
                            <select name="rating" style="flex:1; background:rgba(0,0,0,0.5); border:1px solid var(--border); color:#fff; padding:10px; border-radius:8px;">
                                <option value="5">5 - Excellent</option>
                                <option value="4">4 - Good</option>
                                <option value="3">3 - Average</option>
                                <option value="2">2 - Poor</option>
                                <option value="1">1 - Terrible</option>
                            </select>
                            <button type="submit" class="hero-btn" style="width:auto; padding: 10px 20px; font-size: 0.9rem;">Submit Review</button>
                        </div>
                        <textarea name="comment" rows="2" placeholder="Describe your experience..." required style="width:100%; background:rgba(0,0,0,0.5); border:1px solid var(--border); color:#fff; padding:12px; border-radius:10px; resize:none;"></textarea>
                    </form>
                <?php endif; ?>
            </div>

            <hr style="border-color: rgba(255,255,255,0.05); margin: 40px 0;">
            
            <?php
            // Fetch Ticket Types
            $tt_stmt = $pdo->prepare("SELECT * FROM ticket_types WHERE event_id = ?");
            $tt_stmt->execute([$event['id']]);
            $ticket_types = $tt_stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <?php if(isset($_SESSION['user_id'])): ?>
                <?php if($_SESSION['user_role'] == 'attendee'): ?>
                    <form action="/pages/book_event.php" method="post" class="modern-form" style="margin:0;">
                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                        
                        <h3 style="margin-bottom:20px; color:#fff;">Reserve Your Spot</h3>
                        <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:25px;">
                            <?php 
                            $any_ticket_available = false;
                            foreach($ticket_types as $tt): 
                                $b_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE ticket_type_id = ?");
                                $b_stmt->execute([$tt['id']]);
                                $sold = $b_stmt->fetchColumn();
                                $remaining = $tt['quantity'] - $sold;
                                $is_sold_out = $remaining <= 0;
                                
                                if (!$is_sold_out) $any_ticket_available = true;

                                $tier_class = 'tier-regular';
                                $badge_class = 'badge-regular';
                                $tier_name = strtolower($tt['name']);
                                
                                if (strpos($tier_name, 'vip') !== false) {
                                    $tier_class = 'tier-vip';
                                    $badge_class = 'badge-vip';
                                } elseif (strpos($tier_name, 'gold') !== false) {
                                    $tier_class = 'tier-golden';
                                    $badge_class = 'badge-golden';
                                }
                            ?>
                                <label style="background:rgba(255,255,255,0.03); padding:18px; border-radius:12px; display:flex; justify-content:space-between; align-items:center; cursor:<?php echo $is_sold_out ? 'not-allowed' : 'pointer'; ?>; border:1px solid var(--border); opacity: <?php echo $is_sold_out ? '0.4' : '1'; ?>; transition:all 0.3s;" class="ticket-type-label <?php echo $tier_class; ?>">
                                    <div style="display:flex; align-items:center; gap:15px;">
                                        <input type="radio" name="ticket_type_id" value="<?php echo $tt['id']; ?>" required <?php echo $is_sold_out ? 'disabled' : ''; ?> style="accent-color: var(--primary);">
                                        <div>
                                            <div style="font-weight:700; font-size:1.1rem; color:#fff; display:flex; align-items:center; gap:8px;">
                                                <span class="tier-badge <?php echo $badge_class; ?>" style="font-size:0.6rem; padding: 1px 6px;"><?php echo htmlspecialchars($tt['name']); ?></span>
                                            </div>
                                            <?php if($is_sold_out): ?>
                                                <span style="color:var(--danger); font-size:0.75rem; font-weight:700; text-transform:uppercase;">Sold Out</span>
                                            <?php elseif($remaining < 10): ?>
                                                <span style="color:var(--warning); font-size:0.75rem; font-weight:700;">Only <?php echo $remaining; ?> left!</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div style="font-weight:800; color:var(--accent); font-size:1.4rem;">
                                        <?php echo (($event['currency'] ?? 'USD') == 'USD' ? '$' : 'L$') . htmlspecialchars($tt['price']); ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div style="margin-bottom:30px; display:flex; align-items:center; background:rgba(255,255,255,0.03); padding:15px; border-radius:12px; border:1px solid var(--border);">
                            <label style="margin-right:20px; font-weight:700; color:#fff; font-size:0.95rem;">Group Size:</label>
                            <input type="number" name="quantity" value="1" min="1" max="10" style="width:100px; padding:10px; border-radius:8px; border:1px solid var(--border); background:rgba(0,0,0,0.5); color:#fff; text-align:center; font-weight:700;">
                        </div>

                        <?php if ($any_ticket_available): ?>
                            <button type="submit" class="hero-btn" style="padding: 18px; font-size: 1.1rem; width:100%; border:none;">Book Your Ticket Now</button>
                        <?php else: ?>
                            <div style="background:var(--danger); color:white; padding:18px; text-align:center; border-radius:12px; font-weight:800; font-size:1.1rem; opacity:0.8;">
                                EVENT COMPLETELY SOLD OUT
                            </div>
                        <?php endif; ?>
                    </form>
                <?php elseif($_SESSION['user_role'] == 'organizer'): ?>
                    <div style="background: rgba(236, 72, 153, 0.1); padding:20px; border-radius:12px; text-align:center; border: 1px solid rgba(236, 72, 153, 0.2);">
                        <p style="color: var(--secondary); font-weight: 700;">Switch to an Attendee account to book events.</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <a href="/pages/auth/login.php" class="hero-btn" style="text-align:center; display:block; padding:18px; text-decoration:none;">Sign In to Reserve Tickets</a>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php require_once '../includes/footer.php'; ?>
