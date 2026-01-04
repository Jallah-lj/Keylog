<?php
// Ensure settings are available
if (!isset($settings)) {
    // If we are in included file and $pdo might be set by parent
    // But to be safe, we can try to require database if $pdo is missing, 
    // or just assume $pdo exists if the page loads. 
    // Best practice: Check if $pdo exists.
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT * FROM site_settings");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $settings = [];
        foreach ($results as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }
    } else {
        // Fallback defaults if DB not accessible (e.g. 404 page)
        $settings = [
            'site_name' => 'Event Platform',
            'facebook_url' => '#',
            'copyright_text' => '© 2025 Event Platform. All rights reserved.'
        ];
    }
}
?>
<footer style="margin-top: auto; background: var(--card-bg); border-top: 1px solid var(--border); padding-top: 80px;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <!-- Top Section: 4 Columns -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 60px; margin-bottom: 60px;">
            
            <!-- Brand & Info -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <h3 style="margin: 0; font-size: 1.5rem; background: linear-gradient(to right, var(--primary-light), var(--secondary)); -webkit-background-clip: text; color: transparent; font-weight: 800; letter-spacing: -0.5px;">
                    <?php echo htmlspecialchars($settings['site_name'] ?? 'Event Platform'); ?>
                </h3>
                <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; margin: 0;">
                    Elevating the event experience. Discover, book, and enjoy the most exclusive events in your city with our premium ticketing platform.
                </p>
                <div style="display: flex; gap: 15px;">
                    <a href="<?php echo htmlspecialchars($settings['facebook_url'] ?? '#'); ?>" style="color: var(--text-muted); transition: color 0.3s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-muted)'">
                        <?php echo Icons::get('facebook', 'width: 20px; height: 20px;'); ?>
                    </a>
                    <a href="<?php echo htmlspecialchars($settings['instagram_url'] ?? '#'); ?>" style="color: var(--text-muted); transition: color 0.3s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-muted)'">
                        <?php echo Icons::get('instagram', 'width: 20px; height: 20px;'); ?>
                    </a>
                    <a href="<?php echo htmlspecialchars($settings['twitter_url'] ?? '#'); ?>" style="color: var(--text-muted); transition: color 0.3s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-muted)'">
                        <?php echo Icons::get('twitter', 'width: 20px; height: 20px;'); ?>
                    </a>
                </div>
            </div>

            <!-- Explore Links -->
            <div>
                <h4 style="color: #fff; font-size: 1.1rem; margin-bottom: 25px; font-weight: 700;">Explore</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px;">
                    <li><a href="/index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='var(--text-muted)'">Browse Events</a></li>
                    <li><a href="/index.php?category=Music" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='var(--text-muted)'">Music Events</a></li>
                    <li><a href="/index.php?category=Tech" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='var(--text-muted)'">Tech & Workshops</a></li>
                    <li><a href="/index.php#trending" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='var(--text-muted)'">Trending Now</a></li>
                </ul>
            </div>

            <!-- Support & Company -->
            <div>
                <h4 style="color: #fff; font-size: 1.1rem; margin-bottom: 25px; font-weight: 700;">Support</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px;">
                    <li><a href="/pages/contact.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='var(--text-muted)'">Get in Touch</a></li>
                    <li><a href="/pages/about.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='var(--text-muted)'">About Our Platform</a></li>
                    <li><a href="/pages/privacy.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='var(--text-muted)'">Terms of Service</a></li>
                </ul>
            </div>

            <!-- Newsletter Column -->
            <div>
                <h4 style="color: #fff; font-size: 1.1rem; margin-bottom: 25px; font-weight: 700;">Newsletter</h4>
                <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.5; margin-bottom: 20px;">
                    Subscribe to receive weekly updates on premium events.
                </p>
                <form onsubmit="event.preventDefault(); alert('Subscribed successfully!');" style="display: flex; flex-direction: column; gap: 12px;">
                    <input type="email" placeholder="Email address" required style="width: 100%; padding: 12px 16px; border-radius: 10px; border: 1px solid var(--border); background: rgba(255,255,255,0.03); color: #fff; font-size: 0.9rem;">
                    <button type="submit" class="hero-btn" style="padding: 12px; font-size: 0.9rem; border-radius: 10px; border: none; font-weight: 700;">Subscribe</button>
                </form>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div style="border-top: 1px solid var(--border); padding: 30px 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
                <?php echo htmlspecialchars($settings['copyright_text'] ?? '© 2025 Event Platform.'); ?> Built for lovers of great experiences.
            </p>
            <div style="display: flex; gap: 30px;">
                <a href="/pages/privacy.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-muted)'">Privacy</a>
                <a href="#" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-muted)'">Terms</a>
                <a href="#" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-muted)'">Cookies</a>
            </div>
        </div>
    </div>
</footer>
