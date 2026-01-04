<?php
require_once 'config/database.php';
require_once 'includes/header.php';
?>
    <main>
        <!-- Hero Section -->
        <section class="hero">
            <h2>Discover Amazing Events</h2>
            <p>From local workshops to global conferences, find the perfect experience for you.</p>
            <a href="#browse" class="hero-btn">Browse Events</a>
        </section>

        <!-- Category Browser -->
        <div style="max-width: 1200px; margin: -30px auto 40px; padding: 0 20px; position: relative; z-index: 10;">
            <div style="display: flex; gap: 15px; overflow-x: auto; padding-bottom: 10px; scrollbar-width: none;">
                <?php
                $cat_file = 'uploads/categories.json';
                $categories = [];
                if (file_exists($cat_file)) {
                    $categories = json_decode(file_get_contents($cat_file), true);
                } else {
                    $categories = [
                        ['name' => 'Music', 'icon' => 'music'],
                        ['name' => 'Tech', 'icon' => 'tech'],
                        ['name' => 'Sports', 'icon' => 'sports'],
                        ['name' => 'Art', 'icon' => 'art'],
                        ['name' => 'Workshop', 'icon' => 'workshop'],
                        ['name' => 'General', 'icon' => 'star']
                    ];
                }
                foreach($categories as $cat) {
                    $isActive = ($_GET['category'] ?? '') == $cat['name'];
                    $bg = $isActive ? 'var(--primary)' : 'rgba(30, 41, 59, 0.8)';
                    $border = $isActive ? 'var(--primary)' : 'rgba(255,255,255,0.1)';
                    echo "
                    <a href='?category={$cat['name']}#browse' style='flex: 0 0 auto; background: {$bg}; backdrop-filter: blur(10px); border: 1px solid {$border}; padding: 10px 18px; border-radius: 50px; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; transition: transform 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.1);' onmouseover=\"this.style.transform='translateY(-2px)'\" onmouseout=\"this.style.transform='translateY(0)'\">
                        <span style='display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; background: rgba(255,255,255,0.1); border-radius: 50%;'>" . Icons::get($cat['icon'], 'width:14px; height:14px;') . "</span>
                        <span style='font-weight: 500;'>{$cat['name']}</span>
                    </a>";
                }
                ?>
            </div>
        </div>

        <!-- Featured Events Carousel -->
        <?php
        // Fetch featured events (Top 5 upcoming)
        $feat_stmt = $pdo->prepare("SELECT * FROM events WHERE event_date >= NOW() AND status = 'published' ORDER BY event_date ASC LIMIT 5");
        $feat_stmt->execute();
        $featured = $feat_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Phase 17: Fetch Ticket Tiers for Trending Events
        $trending_tickets = [];
        if (count($featured) > 0) {
            $f_ids = array_column($featured, 'id');
            $placeholders = implode(',', array_fill(0, count($f_ids), '?'));
            $t_stmt = $pdo->prepare("SELECT tt.*, e.title as event_title, e.image as event_image, e.currency 
                                     FROM ticket_types tt 
                                     JOIN events e ON tt.event_id = e.id 
                                     WHERE tt.event_id IN ($placeholders) 
                                     ORDER BY tt.price ASC");
            $t_stmt->execute($f_ids);
            $trending_tickets = $t_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        ?>

        <!-- Trending Now Bar & Ticket Carousel -->
        <div style="max-width: 1200px; margin: 0 auto 80px; padding: 0 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid var(--border);">
                <h3 style="margin: 0; font-size: 1.4rem; color: #fff; display: flex; align-items: center; gap: 12px; font-weight: 700;">
                    <span style='display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: rgba(99, 102, 241, 0.1); border-radius: 10px; color: var(--primary-light);'>
                        <?php echo Icons::get('fire', 'width:22px; height:22px;'); ?>
                    </span> 
                    Trending Ticket Options
                </h3>
                <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500; background: rgba(255,255,255,0.05); padding: 4px 12px; border-radius: 20px;">
                    Public Preview • Updated Live
                </span>
            </div>

            <?php if (count($trending_tickets) > 0): ?>
            <div class="marquee-container">
                <div class="marquee-content">
                    <?php 
                    // Duplicate items for infinite scroll effect
                    $display_tickets = array_merge($trending_tickets, $trending_tickets);
                    foreach ($display_tickets as $t): 
                        $tier_class = 'tier-regular';
                        $badge_class = 'badge-regular';
                        $tier_name = strtolower($t['name']);
                        
                        if (strpos($tier_name, 'vip') !== false) {
                            $tier_class = 'tier-vip';
                            $badge_class = 'badge-vip';
                        } elseif (strpos($tier_name, 'gold') !== false) {
                            $tier_class = 'tier-golden';
                            $badge_class = 'badge-golden';
                        }
                    ?>
                    <div class="ticket-tier-card <?php echo $tier_class; ?>">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                <span class="tier-badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($t['name']); ?></span>
                                <div style="color: var(--success); font-size: 0.75rem; font-weight: 700;">AVAILABLE</div>
                            </div>
                            <h5 style="margin: 0 0 5px 0; font-size: 1rem; color: #fff;"><?php echo htmlspecialchars($t['event_title']); ?></h5>
                            <p style="margin: 0; font-size: 0.8rem; color: var(--text-muted);">Starting from</p>
                        </div>
                        <div style="margin-top: 20px;">
                            <div class="price-display">
                                <?php echo ($t['currency'] == 'USD' ? '$' : 'L$') . number_format($t['price'], 2); ?>
                            </div>
                            <a href="/pages/event_details.php?id=<?php echo $t['event_id']; ?>" class="buy-btn-ghost">
                                View Options
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: var(--text-muted); font-style: italic;">
                    Price discovery options loading...
                </div>
            <?php endif; ?>
        </div>

        <?php if (count($featured) > 0): ?>
        <div style="max-width: 1200px; margin: 0 auto 50px; padding: 0 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                <h3 style="margin: 0; font-size: 1.5rem; background: linear-gradient(to right, #fbbf24, #f59e0b); -webkit-background-clip: text; color: transparent; display: flex; align-items: center; gap: 10px;">
                    <span style='display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: rgba(251, 191, 36, 0.1); border-radius: 50%; color: #fbbf24;'>
                        <?php echo Icons::get('sparkles', 'width:20px; height:20px;'); ?>
                    </span> Top Experiences
                </h3>
                <div style="display: flex; gap: 10px;">
                    <button onclick="document.getElementById('featCarousel').scrollBy({left: -300, behavior: 'smooth'})" class="nav-btn" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid var(--border); background: var(--card-bg); color: #fff; cursor: pointer;">←</button>
                    <button onclick="document.getElementById('featCarousel').scrollBy({left: 300, behavior: 'smooth'})" class="nav-btn" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid var(--border); background: var(--card-bg); color: #fff; cursor: pointer;">→</button>
                </div>
            </div>
            
            <div id="featCarousel" style="display: flex; gap: 20px; overflow-x: auto; scroll-behavior: smooth; scrollbar-width: none; padding-bottom: 10px;">
                <?php foreach ($featured as $f): 
                    $f_img = $f['image'] ? 'uploads/'.htmlspecialchars($f['image']) : 'assets/images/placeholder.jpg';
                ?>
                <div onclick="window.location.href='/pages/event_details.php?id=<?php echo $f['id']; ?>'" style="flex: 0 0 350px; background: var(--card-bg); border-radius: 16px; overflow: hidden; border: 1px solid var(--border); position: relative; cursor: pointer; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="height: 200px; position: relative;">
                        <img src="<?php echo $f_img; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <div style="position: absolute; bottom: 10px; left: 10px; background: rgba(0,0,0,0.7); padding: 5px 10px; border-radius: 8px; font-size: 0.8rem; color: #fff; backdrop-filter: blur(4px); display: flex; align-items: center; gap: 6px;">
                            <?php echo Icons::get('calendar', 'width:14px; height:14px;'); ?> <?php echo date('M j', strtotime($f['event_date'])); ?>
                        </div>
                    </div>
                    <div style="padding: 20px;">
                        <span style="font-size: 0.75rem; color: var(--accent); text-transform: uppercase; letter-spacing: 1px; font-weight: 700;"><?php echo htmlspecialchars($f['category']); ?></span>
                        <h4 style="margin: 8px 0; font-size: 1.1rem; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($f['title']); ?></h4>
                        <div style="color: var(--text-muted); font-size: 0.9rem; display: flex; align-items: center; gap: 6px;">
                            <?php echo Icons::get('map-pin', 'width:14px; height:14px; color: var(--accent);'); ?> <?php echo htmlspecialchars($f['location']); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <h3 id="browse" style="border-left: 4px solid var(--primary); padding-left: 15px; margin-bottom: 30px;">Find Your Next Experience</h3>
        
        <!-- Simplified Search Trigger Bar -->
        <form method="get" action="#browse" class="search-container">
            <div class="search-icon-wrapper">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </div>
            <!-- Functional Search Input -->
            <input type="text" name="search" class="search-main-input" placeholder="Search events..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            
            <!-- Filter Trigger (Icon Only) -->
            <div class="filter-trigger" title="Open Filters" onclick="openFilterPanel(event)">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 6H20" stroke="#9C4DFF" stroke-width="2" stroke-linecap="round"/>
                    <path d="M7 12H17" stroke="#9C4DFF" stroke-width="2" stroke-linecap="round"/>
                    <path d="M10 18H14" stroke="#9C4DFF" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
        </form>

        <!-- Filter Panel Overlay -->
        <div class="filter-overlay" id="filterOverlay" onclick="closeFilterPanel()"></div>

        <!-- Slide-in Filter Panel -->
        <aside class="filter-panel" id="filterPanel">
            <form method="get" action="#browse" style="display: flex; flex-direction: column; height: 100%; box-shadow: none; border: none; background: transparent; padding: 0; margin: 0; max-width: none;">
                
                <div class="filter-header">
                    <div class="filter-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9C4DFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                        Filter Mode
                    </div>
                    <button type="button" class="close-filter-btn" onclick="closeFilterPanel()">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>

                <div class="filter-body">
                    
                    <!-- Search Input -->
                    <div class="panel-search-wrapper">
                        <svg class="panel-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="text" name="search" class="panel-search-input" placeholder="Search events (Press Enter)" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" autofocus>
                    </div>

                    <!-- Category -->
                    <div class="filter-group">
                        <label class="filter-label">Category</label>
                        <select name="category" class="glass-input glass-select">
                            <option value="">All Categories</option>
                            <?php
                            $cats = ['General', 'Music', 'Tech', 'Sports', 'Art', 'Workshop'];
                            foreach($cats as $c) {
                                $sel = ($_GET['category'] ?? '') == $c ? 'selected' : '';
                                echo "<option value='$c' $sel>$c</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div class="filter-group">
                        <label class="filter-label">Date Range</label>
                        <div class="range-inputs">
                            <div class="range-input-wrapper">
                                <input type="date" name="date_start" class="glass-input" value="<?php echo htmlspecialchars($_GET['date_start'] ?? ''); ?>" style="color-scheme: dark;">
                            </div>
                            <div class="range-input-wrapper">
                                <input type="date" name="date_end" class="glass-input" value="<?php echo htmlspecialchars($_GET['date_end'] ?? ''); ?>" style="color-scheme: dark;">
                            </div>
                        </div>
                    </div>

                    <!-- Price Range -->
                    <div class="filter-group">
                        <label class="filter-label">Price Range</label>
                        <div class="range-inputs">
                            <div class="range-input-wrapper">
                                <span class="input-icon">$</span>
                                <input type="number" name="price_min" class="glass-input with-icon" placeholder="Min" value="<?php echo htmlspecialchars($_GET['price_min'] ?? ''); ?>">
                            </div>
                            <div class="range-input-wrapper">
                                <div style="position: absolute; left: -10px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.2);">—</div>
                                <span class="input-icon">$</span>
                                <input type="number" name="price_max" class="glass-input with-icon" placeholder="Max" value="<?php echo htmlspecialchars($_GET['price_max'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="apply-filters-btn">Apply Filters</button>
                    
                </div>
            </form>
        </aside>

        <script>
            function openFilterPanel(event) {
                // Prevent form submission or propagation if inside a form/clickable area
                if(event) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                document.getElementById('filterPanel').classList.add('active');
                document.getElementById('filterOverlay').classList.add('active');
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
                
                // Sync main search value to panel search
                const mainSearch = document.querySelector('.search-main-input').value;
                document.querySelector('.panel-search-input').value = mainSearch;
                
                // Focus panel search
                setTimeout(() => document.querySelector('.panel-search-input').focus(), 100);
            }

            function closeFilterPanel() {
                document.getElementById('filterPanel').classList.remove('active');
                document.getElementById('filterOverlay').classList.remove('active');
                document.body.style.overflow = '';
            }

            // Close on Escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === "Escape") {
                    closeFilterPanel();
                }
            });
        </script>

        <div class="events-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <?php
            
            $where = "event_date >= NOW() AND status = 'published'";
            $params = [];
            
            if (!empty($_GET['search'])) {
                $where .= " AND (title LIKE ? OR description LIKE ?)";
                $params[] = "%".$_GET['search']."%";
                $params[] = "%".$_GET['search']."%";
            }
            
            if (!empty($_GET['category'])) {
                $where .= " AND category = ?";
                $params[] = $_GET['category'];
            }

            if (!empty($_GET['date_start'])) {
                $where .= " AND event_date >= ?";
                $params[] = $_GET['date_start'];
            }
            if (!empty($_GET['date_end'])) {
                $where .= " AND event_date <= ?";
                $params[] = $_GET['date_end'] . ' 23:59:59';
            }

            if (!empty($_GET['price_min'])) {
                $where .= " AND price >= ?";
                $params[] = $_GET['price_min'];
            }
            if (!empty($_GET['price_max'])) {
                $where .= " AND price <= ?";
                $params[] = $_GET['price_max'];
            }
            
            $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 9; // Number of events per page
            $offset = ($page - 1) * $limit;
            
            // Count total for pagination
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE $where");
            $count_stmt->execute($params);
            $total_events = $count_stmt->fetchColumn();
            $total_pages = ceil($total_events / $limit);

            $stmt = $pdo->prepare("SELECT * FROM events WHERE $where ORDER BY event_date ASC LIMIT $limit OFFSET $offset");
            $stmt->execute($params);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($events) > 0) {
                foreach ($events as $event) {
                    $currency_symbol = ($event['currency'] ?? 'USD') == 'USD' ? '$' : 'L$';
                    $image_src = $event['image'] ? 'uploads/'.htmlspecialchars($event['image']) : 'assets/images/placeholder.jpg';
                    ?>
                    <div class="event-card" onclick="window.location.href='/pages/event_details.php?id=<?php echo $event['id']; ?>'" style="cursor: pointer;">
                        <div style="position: relative; height: 180px; border-radius: 12px; overflow: hidden; margin-bottom: 20px;">
                            <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                            <div style="position: absolute; top: 15px; right: 15px; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); padding: 5px 12px; border-radius: 20px; font-weight: 700; color: var(--accent); font-size: 0.9rem;">
                                <?php echo $currency_symbol . htmlspecialchars($event['price']); ?>
                            </div>
                        </div>
                        
                        <div style="background: rgba(255,255,255,0.05); display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; color: var(--text-muted); font-weight: 600; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px;">
                            <?php echo htmlspecialchars($event['category']); ?>
                        </div>

                        <h3 style="margin: 0 0 10px 0; font-size: 1.2rem; color: #fff; line-height: 1.4;"><?php echo htmlspecialchars($event['title']); ?></h3>
                        
                        <div style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 20px;">
                            <div style="display: flex; gap: 15px; margin-bottom: 20px; font-size: 0.85rem; color: var(--text-muted);">
                                <span style="display: flex; align-items: center; gap: 6px;">
                                    <?php echo Icons::get('calendar', 'width:14px; height:14px; color: var(--primary);'); ?> <?php echo date('M j, Y • g:i a', strtotime($event['event_date'])); ?>
                                </span>
                                <span style="display: flex; align-items: center; gap: 6px;">
                                    <?php echo Icons::get('map-pin', 'width:14px; height:14px; color: var(--primary);'); ?> <?php echo htmlspecialchars($event['location']); ?>
                                </span>
                            </div>
                        </div>

                        <a href="/pages/event_details.php?id=<?php echo $event['id']; ?>" class="hero-btn" style="display: block; text-align: center; text-decoration: none; padding: 10px; font-size: 0.9rem; margin-top: auto;">Explore Event</a>
                    </div>
                    <?php
                }
            } else {
                echo '<div style="grid-column: 1/-1; text-align: center; padding: 60px; color: var(--text-muted);">
                    <div style="color: var(--text-muted); margin-bottom: 20px;">
                        ' . Icons::get('search', 'width:48px; height:48px; margin: 0 auto 20px; opacity: 0.3;') . '
                    </div>
                        <h3>No upcoming events found.</h3>
                        <p>Try adjusting your search filters или browse other categories.</p>
                      </div>';
            }
            ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php 
                $query_params = $_GET;
                
                // Prev
                if ($page > 1) {
                    $query_params['page'] = $page - 1;
                    $prev_link = '?' . http_build_query($query_params) . '#browse';
                    echo '<a href="'.$prev_link.'" class="page-btn">&laquo; Previous</a>';
                } else {
                    echo '<span class="page-btn disabled">&laquo; Previous</span>';
                }

                // Page Numbers
                for ($i = 1; $i <= $total_pages; $i++) {
                    $query_params['page'] = $i;
                    $link = '?' . http_build_query($query_params) . '#browse';
                    $active = $i == $page ? 'active' : '';
                    echo '<a href="'.$link.'" class="page-btn '.$active.'">'.$i.'</a>';
                }

                // Next
                if ($page < $total_pages) {
                    $query_params['page'] = $page + 1;
                    $next_link = '?' . http_build_query($query_params) . '#browse';
                    echo '<a href="'.$next_link.'" class="page-btn">Next &raquo;</a>';
                } else {
                    echo '<span class="page-btn disabled">Next &raquo;</span>';
                }
            ?>
        </div>
        <?php endif; ?>

        <!-- Organizer CTA Section -->
        <section style="margin-top: 80px; padding: 60px 20px; text-align: center; background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('/assets/images/cta-bg.jpg'); background-size: cover; border-radius: 20px; position: relative; overflow: hidden; border: 1px solid rgba(255,255,255,0.1);">
            <div style="position: absolute; inset: 0; background: linear-gradient(45deg, var(--primary), transparent); opacity: 0.2;"></div>
            <div style="position: relative; z-index: 2; max-width: 600px; margin: 0 auto;">
                <h2 style="font-size: 2.5rem; margin-bottom: 20px; background: linear-gradient(to right, #fff, #cbd5e1); -webkit-background-clip: text; color: transparent;">Host Your Own Event</h2>
                <p style="font-size: 1.1rem; color: #cbd5e1; margin-bottom: 40px; line-height: 1.6;">Join thousands of organizers who use our platform to sell tickets, manage attendees, and grow their community.</p>
                <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                    <a href="/pages/auth/register.php?role=organizer" class="hero-btn" style="padding: 15px 40px; font-size: 1.1rem;">Get Started for Free</a>
                    <a href="/pages/about.php" style="padding: 15px 40px; font-size: 1.1rem; color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 50px; text-decoration: none; transition: all 0.2s; background: rgba(255,255,255,0.05);" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">Learn More</a>
                </div>
            </div>
        </section>

    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
