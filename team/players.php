<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/auth.php';
requireTeam();

$teamId = $_SESSION['team_id'];
$team = $pdo->query("SELECT * FROM teams WHERE id = $teamId")->fetch(PDO::FETCH_ASSOC);

// Get filter parameters
$role = $_GET['role'] ?? 'All';
$country = $_GET['country'] ?? 'All';
$status = $_GET['status'] ?? 'All';
$minPrice = $_GET['min_price'] ?? 0;
$maxPrice = $_GET['max_price'] ?? 10000000;
$sortBy = $_GET['sort_by'] ?? 'name';
$sortOrder = $_GET['sort_order'] ?? 'ASC';

// Build query
$sql = "SELECT p.*, t.name AS team_name FROM players p LEFT JOIN teams t ON p.sold_to = t.id WHERE 1=1";
$params = [];

if ($role !== 'All') {
    $sql .= " AND p.role = ?";
    $params[] = $role;
}

if ($country !== 'All') {
    $sql .= " AND p.country = ?";
    $params[] = $country;
}

if ($status !== 'All') {
    $sql .= " AND p.status = ?";
    $params[] = $status;
}

$sql .= " AND p.base_price BETWEEN ? AND ?";
$params[] = $minPrice;
$params[] = $maxPrice;

$sql .= " ORDER BY p.$sortBy $sortOrder";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique countries and roles for filters
$countries = $pdo->query("SELECT DISTINCT country FROM players ORDER BY country")->fetchAll(PDO::FETCH_COLUMN);
$roles = ['Batsman', 'Bowler', 'All-rounder', 'Wicket-keeper'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Players - <?php echo htmlspecialchars($team['name']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .player-filters {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .player-count {
            margin: 15px 0;
            font-weight: bold;
        }
        
        .player-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .player-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .player-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .player-image {
            height: 200px;
            background-color: #ecf0f1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .player-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        
        .player-placeholder {
            width: 100px;
            height: 100px;
            background-color: #bdc3c7;
            border-radius: 50%;
        }
        
        .player-info {
            padding: 15px;
        }
        
        .player-info h3 {
            margin-bottom: 10px;
        }
        
        .player-price {
            font-weight: bold;
            color: #e74c3c;
            margin-top: 10px;
        }
        
        .player-sold {
            color: #28a745;
            font-weight: bold;
        }
        
        .player-unsold {
            color: #dc3545;
            font-weight: bold;
        }
        
        .player-available {
            color: #17a2b8;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Dashboard - <?php echo htmlspecialchars($team['name']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
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
                                <a class="nav-link active" href="dashboard.php">Team Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="players.php">Players</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="bids.php">My Bids</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../auction_realtime.php">Live Auction</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../logout.php">Logout</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>
    
    <div class="container">
        <h1>Players</h1>
        
        <div class="player-filters">
            <h2>Filter Players</h2>
            <form method="get" action="">
                <div class="filter-grid">
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="All" <?php echo $role === 'All' ? 'selected' : ''; ?>>All</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?php echo $r; ?>" <?php echo $role === $r ? 'selected' : ''; ?>><?php echo $r; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="country" class="form-label">Country</label>
                        <select class="form-select" id="country" name="country">
                            <option value="All" <?php echo $country === 'All' ? 'selected' : ''; ?>>All</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?php echo $c; ?>" <?php echo $country === $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="All" <?php echo $status === 'All' ? 'selected' : ''; ?>>All</option>
                            <option value="Available" <?php echo $status === 'Available' ? 'selected' : ''; ?>>Available</option>
                            <option value="Sold" <?php echo $status === 'Sold' ? 'selected' : ''; ?>>Sold</option>
                            <option value="Unsold" <?php echo $status === 'Unsold' ? 'selected' : ''; ?>>Unsold</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="min_price" class="form-label">Min Price ($)</label>
                        <input type="number" class="form-control" id="min_price" name="min_price" value="<?php echo $minPrice; ?>" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label for="max_price" class="form-label">Max Price ($)</label>
                        <input type="number" class="form-control" id="max_price" name="max_price" value="<?php echo $maxPrice; ?>" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label for="sort_by" class="form-label">Sort By</label>
                        <select class="form-select" id="sort_by" name="sort_by">
                            <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name</option>
                            <option value="country" <?php echo $sortBy === 'country' ? 'selected' : ''; ?>>Country</option>
                            <option value="role" <?php echo $sortBy === 'role' ? 'selected' : ''; ?>>Role</option>
                            <option value="base_price" <?php echo $sortBy === 'base_price' ? 'selected' : ''; ?>>Base Price</option>
                            <option value="sold_price" <?php echo $sortBy === 'sold_price' ? 'selected' : ''; ?>>Sold Price</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">Order</label>
                        <select class="form-select" id="sort_order" name="sort_order">
                            <option value="ASC" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                            <option value="DESC" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                        </select>
                    </div>
                    
                    <div class="mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="player-count">
            <p><?php echo count($players); ?> players found</p>
        </div>
        
        <div class="player-grid">
            <?php foreach ($players as $player): ?>
                <div class="player-card">
                    <div class="player-image">
                        <?php if ($player['image']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($player['image']); ?>" alt="<?php echo htmlspecialchars($player['name']); ?>">
                        <?php else: ?>
                            <div class="player-placeholder"></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="player-info">
                        <h3><?php echo htmlspecialchars($player['name']); ?></h3>
                        <p><?php echo htmlspecialchars($player['country']); ?> | <?php echo htmlspecialchars($player['role']); ?></p>
                        <p class="player-price">Base Price: <?php echo number_format($player['base_price'], 2); ?></p>
                        
                        <?php if ($player['status'] === 'Sold'): ?>
                            <p class="player-sold">Sold to <?php echo htmlspecialchars($player['team_name']); ?> for <?php echo number_format($player['sold_price'], 2); ?></p>
                        <?php elseif ($player['status'] === 'Unsold'): ?>
                            <p class="player-unsold">Unsold</p>
                        <?php else: ?>
                            <p class="player-available">Available</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>