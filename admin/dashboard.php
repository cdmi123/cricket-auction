<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/auth.php';
requireAdmin();

$auction = getAuction();
$teams = getTeams();
$players = getPlayers();
?>

<?php include_once '../includes/header.php' ?>

    <!-- Main Content -->
    <div class="container bg-white rounded my-5 shadow-sm py-3">
        <div class="container py-4">
            <div class="row mb-4 g-2 align-items-center">
                <div class="col-12 col-md-6 text-center text-md-start mb-2 mb-md-0">
                    <h1 class="mb-0"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
                </div>
                <div class="col-12 col-md-6 text-center text-md-end">
                    <?php if ($auction && isset($auction['status'])): ?>
                        <div class="status-badge status-<?php echo strtolower($auction['status']); ?> d-inline-block">
                            <?php echo $auction['status']; ?>
                        </div>
                    <?php else: ?>
                        <div class="status-badge status-upcoming d-inline-block">
                            No Auction
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Dashboard Stats -->
            <div class="">
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <div class="stat-card text-center p-4 h-100 rounded-4 shadow-sm bg-white d-flex flex-column align-items-center justify-content-center w-100">
                            <i class="fas fa-users mb-2" style="font-size:2.2rem;color:#2563eb;"></i>
                            <h3 class="mb-1" style="font-size:2rem;"><?php echo count($teams); ?></h3>
                            <p class="mb-0" style="font-size:1rem;">Total Teams</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="stat-card text-center p-4 h-100 rounded-4 shadow-sm bg-white d-flex flex-column align-items-center justify-content-center w-100">
                            <i class="fas fa-user-friends mb-2" style="font-size:2.2rem;color:#2563eb;"></i>
                            <h3 class="mb-1" style="font-size:2rem;"><?php echo count($players); ?></h3>
                            <p class="mb-0" style="font-size:1rem;">Total Players</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="stat-card text-center p-4 h-100 rounded-4 shadow-sm bg-white d-flex flex-column align-items-center justify-content-center w-100">
                            <i class="fas fa-user-check mb-2" style="font-size:2.2rem;color:#2563eb;"></i>
                            <h3 class="mb-1" style="font-size:2rem;"><?php echo count(array_filter($players, fn($p) => $p['status'] === 'Sold')); ?></h3>
                            <p class="mb-0" style="font-size:1rem;">Sold Players</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="stat-card text-center p-4 h-100 rounded-4 shadow-sm bg-white d-flex flex-column align-items-center justify-content-center w-100">
                            <i class="fas fa-rupee-sign mb-2" style="font-size:2.2rem;color:#2563eb;"></i>
                            <h3 class="mb-1" style="font-size:2rem;"><?php echo number_format(array_sum(array_column(array_filter($players, fn($p) => $p['status'] === 'Sold'), 'sold_price')), 0); ?></h3>
                            <p class="mb-0" style="font-size:1rem;">Total Revenue</p>
                        </div>
                    </div>
                </div>
                <style>
                @media (max-width: 576px) {
                    .dashboard-stats .row > div {
                        margin-bottom: 1rem;
                    }
                    .stat-card {
                        width: 100% !important;
                        min-width: 100% !important;
                        box-shadow: 0 2px 12px rgba(37,99,235,0.08);
                        border-radius: 1.5rem !important;
                        padding: 1.5rem 0.7rem !important;
                    }
                    .stat-card i {
                        font-size: 2.2rem !important;
                    }
                    .stat-card h3 {
                        font-size: 1.7rem !important;
                    }
                    .stat-card p {
                        font-size: 1rem !important;
                    }
                }
                </style>
            </div>
            
            <!-- Dashboard Actions -->
            <!-- <div class="dashboard-actions">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <a href="manage_auction.php" class="btn btn-primary">
                        <i class="fas fa-cog"></i> Manage Auction
                    </a>
                    <a href="players.php" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Manage Players
                    </a>
                    <a href="teams.php" class="btn btn-info">
                        <i class="fas fa-users"></i> Manage Teams
                    </a>
                    <a href="users.php" class="btn btn-warning">
                        <i class="fas fa-user-cog"></i> Manage Users
                    </a>
                    <a href="analytics.php" class="btn btn-secondary">
                        <i class="fas fa-chart-line"></i> View Analytics
                    </a>
                    <a href="../auction_realtime.php" class="btn btn-danger">
                        <i class="fas fa-gavel"></i> Live Auction
                    </a>
                </div>
            </div> -->
            
            <!-- Recent Activity -->
            <div class="recent-activity">
                <h3 class="mb-3"><i class="fas fa-history"></i> Recent Activity</h3>
                <div class="activity-list">
                    <?php
                    $stmt = $pdo->query("SELECT b.*, p.name AS player_name, t.name AS team_name 
                                        FROM bids b 
                                        JOIN players p ON b.player_id = p.id 
                                        JOIN teams t ON b.team_id = t.id 
                                        ORDER BY b.bid_time DESC LIMIT 10");
                    $recentBids = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($recentBids)):
                    ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No recent activity</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-2">
                        <?php foreach ($recentBids as $bid): ?>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="activity-item p-3 mb-2 rounded shadow-sm bg-white h-100">
                                    <div class="activity-time mb-1">
                                        <i class="fas fa-clock"></i> <?php echo date('M j, Y g:i A', strtotime($bid['bid_time'])); ?>
                                    </div>
                                    <div class="activity-desc">
                                        <strong><?php echo htmlspecialchars($bid['team_name']); ?></strong> 
                                        bid <span class="text-primary">₹<?php echo number_format($bid['amount'], 2); ?></span> 
                                        for <strong><?php echo htmlspecialchars($bid['player_name']); ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>