<?php
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'includes/auth.php';

$auction = getAuction();
$teams = getTeams();
$players = getPlayers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cricket Auction System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-gavel"></i> Cricket Auction System</h1>
                </div>
                <div class="col-md-6 text-end">
                    <nav>
                        <ul class="navbar-nav flex-row justify-content-end">
                            <li class="nav-item">
                                <a class="nav-link active" href="index.php">Home</a>
                            </li>
                            <?php if (isLoggedIn()): ?>
                                <?php if (isAdmin()): ?>
                                    <li class="nav-item">
                                        <a class="nav-link" href="admin/dashboard.php">Admin Dashboard</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="admin/manage_auction.php">Manage Auction</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="admin/analytics.php">Analytics</a>
                                    </li>
                                <?php else: ?>
                                    <li class="nav-item">
                                        <a class="nav-link" href="team/dashboard.php">Team Dashboard</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="team/players.php">Players</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="team/bids.php">My Bids</a>
                                    </li>
                                <?php endif; ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="auction_realtime.php">Live Auction</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="logout.php">Logout</a>
                                </li>
                            <?php else: ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="login.php">Login</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="container">
                <h1><i class="fas fa-gavel"></i> Cricket Auction System</h1>
                <p>A comprehensive platform for conducting cricket player auctions with real-time bidding and team management</p>
                <?php if ($auction && isset($auction['status']) && $auction['status'] === 'Live'): ?>
                    <a href="auction_realtime.php" class="btn btn-light btn-lg">
                        <i class="fas fa-play-circle"></i> Join Live Auction
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-light btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Login to System
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Stats Section -->
        <div class="container py-5">
            <h2 class="text-center mb-5">Auction Statistics</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3><?php echo count($teams); ?></h3>
                    <p>Teams</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-friends"></i>
                    <h3><?php echo count($players); ?></h3>
                    <p>Players</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-check"></i>
                    <h3><?php echo count(array_filter($players, fn($p) => $p['status'] === 'Sold')); ?></h3>
                    <p>Sold Players</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-dollar-sign"></i>
                    <h3><?php echo number_format(array_sum(array_column(array_filter($players, fn($p) => $p['status'] === 'Sold'), 'sold_price')), 0); ?></h3>
                    <p>Total Spent</p>
                </div>
            </div>
        </div>
        
        <!-- Features Section -->
        <div class="bg-light py-5">
            <div class="container">
                <h2 class="text-center mb-5">System Features</h2>
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-3x mb-3 text-primary"></i>
                                <h4>Real-time Auction</h4>
                                <p>Live bidding with real-time updates and automatic player transitions</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-line fa-3x mb-3 text-success"></i>
                                <h4>Analytics Dashboard</h4>
                                <p>Comprehensive analytics and reporting for auction insights</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-users-cog fa-3x mb-3 text-warning"></i>
                                <h4>Team Management</h4>
                                <p>Complete team management with budget tracking and player roster</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- CTA Section -->
        <div class="container py-5">
            <div class="text-center">
                <h2>Ready to Start Your Auction?</h2>
                <p class="mb-4">Join the most advanced cricket auction platform today</p>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="admin/manage_auction.php" class="btn btn-primary btn-lg me-2">
                            <i class="fas fa-cog"></i> Manage Auction
                        </a>
                    <?php else: ?>
                        <a href="team/dashboard.php" class="btn btn-primary btn-lg me-2">
                            <i class="fas fa-tachometer-alt"></i> Team Dashboard
                        </a>
                    <?php endif; ?>
                    <a href="auction_realtime.php" class="btn btn-success btn-lg">
                        <i class="fas fa-gavel"></i> View Auction
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary btn-lg me-2">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>