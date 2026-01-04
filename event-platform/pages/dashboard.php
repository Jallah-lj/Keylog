<?php
session_start();
require_once '../config/database.php';
require_once '../includes/icons.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['user_role'] ?? 'attendee';
$user_name = $_SESSION['user_name'];

require_once '../includes/header.php';
?>
<main>
    <!-- Custom Dashboard Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; padding: 20px; background: rgba(255,255,255,0.03); border-radius: 16px; border: 1px solid var(--border);">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                <h2 style="margin: 0; font-size: 2rem; font-weight: 800; color: #fff;">
                    Welcome back, <?php echo htmlspecialchars($user_name); ?>
                </h2>
                <span style="background: rgba(99, 102, 241, 0.2); color: var(--primary-light); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; border: 1px solid rgba(99, 102, 241, 0.3);">
                    ATTENDEE
                </span>
            </div>
            <p style="color: var(--text-muted); font-size: 1rem;">Experience the best events, curated just for you.</p>
        </div>
        <div style="display: flex; gap: 15px;">
            <a href="auth/logout.php" class="btn-outline-danger" style="display: flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 10px; font-size: 0.9rem; font-weight: 600; text-decoration: none; border: 1px solid rgba(248, 113, 113, 0.3); color: var(--danger); transition: all 0.3s;" onmouseover="this.style.background='rgba(248,113,113,0.1)'" onmouseout="this.style.background='transparent'">
                <?php echo Icons::get('logout', 'width:18px; height:18px;'); ?> Logout
            </a>
        </div>
    </div>

    <?php if ($role != 'organizer'): 
        // Quick Stats
        $stmt_total = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
        $stmt_total->execute([$user_id]);
        $total_bookings = $stmt_total->fetchColumn();

        $stmt_up = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN events e ON b.event_id = e.id WHERE b.user_id = ? AND e.event_date >= NOW()");
        $stmt_up->execute([$user_id]);
        $upcoming_count = $stmt_up->fetchColumn();
    ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 50px;">
        <div class="glass-card" style="padding: 25px; display: flex; align-items: center; gap: 20px; border-radius: 20px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; opacity: 0.05; transform: rotate(-15deg);">
                <?php echo Icons::get('ticket', 'width:120px; height:120px;'); ?>
            </div>
            <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(99, 102, 241, 0.1)); padding: 15px; border-radius: 15px; color: var(--primary-light); box-shadow: 0 10px 20px rgba(99, 102, 241, 0.1);">
                <?php echo Icons::get('ticket', 'width:28px; height:28px;'); ?>
            </div>
            <div>
                <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Total Bookings</div>
                <div style="font-size: 2rem; font-weight: 900; color: #fff; margin-top: 5px;"><?php echo $total_bookings; ?></div>
            </div>
        </div>

        <div class="glass-card" style="padding: 25px; display: flex; align-items: center; gap: 20px; border-radius: 20px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; opacity: 0.05; transform: rotate(-15deg);">
                <?php echo Icons::get('calendar', 'width:120px; height:120px;'); ?>
            </div>
            <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.1)); padding: 15px; border-radius: 15px; color: var(--success); box-shadow: 0 10px 20px rgba(16, 185, 129, 0.1);">
                <?php echo Icons::get('calendar', 'width:28px; height:28px;'); ?>
            </div>
            <div>
                <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Upcoming Events</div>
                <div style="font-size: 2rem; font-weight: 900; color: #fff; margin-top: 5px;"><?php echo $upcoming_count; ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Organizer Dashboard has moved to pages/organizer/dashboard.php -->
    
    <?php if ($role != 'organizer'): // Should basically always be true if redirect works, but safe fallback ?>
        <section class="dashboard-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h3 style="border-left: 4px solid var(--primary); padding-left: 15px; margin: 0; font-size: 1.5rem; color: #fff;">My Tickets & Bookings</h3>
                <div style="display: flex; gap: 10px;">
                    <span style="font-size: 0.85rem; color: var(--text-muted);">View:</span>
                    <a href="#" style="color: var(--primary-light); font-size: 0.85rem; font-weight: 600; text-decoration: none;">Upcoming</a>
                    <span style="color: var(--border);">|</span>
                    <a href="#" style="color: var(--text-muted); font-size: 0.85rem; text-decoration: none;">Past</a>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px;">
                <?php
                $stmt = $pdo->prepare("
                    SELECT b.ticket_code, b.booking_date, b.status as booking_status, e.title, e.event_date, e.location 
                    FROM bookings b 
                    JOIN events e ON b.event_id = e.id 
                    WHERE b.user_id = ? 
                    ORDER BY e.event_date DESC
                ");
                $stmt->execute([$user_id]);
                $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($bookings) > 0):
                    foreach ($bookings as $booking):
                        $status_color = $booking['booking_status'] == 'paid' ? 'var(--success)' : 'var(--warning)';
                ?>
                    <div class="glass-card" style="padding: 0; border-radius: 24px; overflow: hidden; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); border: 1px solid var(--border);" onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 20px 40px rgba(99, 102, 241, 0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        <!-- Card Header -->
                        <div style="padding: 20px 25px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); background: rgba(255,255,255,0.02);">
                            <div style="color: var(--text-muted); font-size: 0.7rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;">
                                ID: #<?php echo strtoupper($booking['ticket_code']); ?>
                            </div>
                            <div style="background: rgba(52, 211, 153, 0.1); color: var(--success); padding: 4px 10px; border-radius: 30px; font-size: 0.65rem; font-weight: 800; border: 1px solid rgba(52, 211, 153, 0.2);">
                                <?php echo strtoupper($booking['booking_status'] == 'paid' ? 'CONFIRMED' : $booking['booking_status']); ?>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div style="padding: 25px;">
                            <h4 style="font-size: 1.4rem; margin-bottom: 15px; color: #fff; font-weight: 700; line-height: 1.3;"><?php echo htmlspecialchars($booking['title']); ?></h4>
                            
                            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 25px;">
                                <div style="display: flex; align-items: center; gap: 10px; color: var(--text-muted); font-size: 0.9rem;">
                                    <div style="color: var(--primary-light);"><?php echo Icons::get('calendar', 'width:16px; height:16px;'); ?></div>
                                    <?php echo date('D, M j, Y • g:i A', strtotime($booking['event_date'])); ?>
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px; color: var(--text-muted); font-size: 0.9rem;">
                                    <div style="color: var(--primary-light);"><?php echo Icons::get('map-pin', 'width:16px; height:16px;'); ?></div>
                                    <?php echo htmlspecialchars($booking['location']); ?>
                                </div>
                            </div>

                            <a href="tickets/view.php?code=<?php echo $booking['ticket_code']; ?>" 
                               style="display: block; width: 100%; text-align: center; text-decoration: none; padding: 14px; border-radius: 12px; font-size: 0.95rem; font-weight: 700; background: linear-gradient(135deg, #6366f1, #d946ef); color: #fff; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3); transition: all 0.3s;"
                               onmouseover="this.style.filter='brightness(1.1)'; this.style.boxShadow='0 6px 20px rgba(217, 70, 239, 0.4)'" 
                               onmouseout="this.style.filter='none'; this.style.boxShadow='0 4px 15px rgba(99, 102, 241, 0.3)'">
                                View Scannable Ticket
                            </a>
                        </div>
                    </div>
                <?php 
                    endforeach;
                else:
                ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 80px 40px; background: rgba(255,255,255,0.02); border-radius: 30px; border: 2px dashed var(--border);">
                        <div style="color:var(--text-muted); margin-bottom: 20px; opacity: 0.3;"><?php echo Icons::get('ticket', 'width: 80px; height: 80px;'); ?></div>
                        <h4 style="color: #fff; font-size: 1.5rem; margin-bottom: 10px;">No adventures yet?</h4>
                        <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto 25px;">Discover amazing events happening around you and start filling your calendar with memories.</p>
            <a href="/index.php" class="hero-btn" style="padding: 12px 30px; text-decoration: none; font-size: 1rem;">Browse Events</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php require_once '../includes/footer.php'; ?>
