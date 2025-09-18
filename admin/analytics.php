<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/auth.php';
requireAdmin();

// Get auction data
$auction = getAuction();
$teams = getTeams();
$players = getPlayers();

// Calculate analytics data
$totalPlayers = count($players);
$soldPlayers = count(array_filter($players, fn($p) => $p['status'] === 'Sold'));
$unsoldPlayers = count(array_filter($players, fn($p) => $p['status'] === 'Unsold'));
$totalSpent = array_sum(array_column(array_filter($players, fn($p) => $p['status'] === 'Sold'), 'sold_price'));
$averagePrice = $soldPlayers > 0 ? $totalSpent / $soldPlayers : 0;

// Team analytics
$teamAnalytics = [];
foreach ($teams as $team) {
    $teamPlayers = getTeamPlayers($team['id']);
    $teamStats = getTeamStats($team['id']);
    
    $teamAnalytics[] = [
        'id' => $team['id'],
        'name' => $team['name'],
        'total_players' => $teamStats['total_players'],
        'total_spent' => $teamStats['total_spent'] ?? 0,
        'avg_price' => $teamStats['avg_price'] ?? 0,
        'remaining_budget' => $team['remaining_budget'],
        'budget_utilization' => $team['budget'] > 0 ? (($team['budget'] - $team['remaining_budget']) / $team['budget']) * 100 : 0
    ];
}

// Role-wise analytics
$roleAnalytics = [];
$roles = ['Batsman', 'Bowler', 'All-rounder', 'Wicket-keeper'];
foreach ($roles as $role) {
    $rolePlayers = array_filter($players, fn($p) => $p['role'] === $role);
    $soldRolePlayers = array_filter($rolePlayers, fn($p) => $p['status'] === 'Sold');
    $roleTotalSpent = array_sum(array_column($soldRolePlayers, 'sold_price'));
    $roleAveragePrice = count($soldRolePlayers) > 0 ? $roleTotalSpent / count($soldRolePlayers) : 0;
    
    $roleAnalytics[] = [
        'role' => $role,
        'total_players' => count($rolePlayers),
        'sold_players' => count($soldRolePlayers),
        'unsold_players' => count($rolePlayers) - count($soldRolePlayers),
        'total_spent' => $roleTotalSpent,
        'average_price' => $roleAveragePrice
    ];
}

// Country-wise analytics
$countryAnalytics = [];
$countries = array_unique(array_column($players, 'country'));
foreach ($countries as $country) {
    $countryPlayers = array_filter($players, fn($p) => $p['country'] === $country);
    $soldCountryPlayers = array_filter($countryPlayers, fn($p) => $p['status'] === 'Sold');
    $countryTotalSpent = array_sum(array_column($soldCountryPlayers, 'sold_price'));
    $countryAveragePrice = count($soldCountryPlayers) > 0 ? $countryTotalSpent / count($soldCountryPlayers) : 0;
    
    $countryAnalytics[] = [
        'country' => $country,
        'total_players' => count($countryPlayers),
        'sold_players' => count($soldCountryPlayers),
        'unsold_players' => count($countryPlayers) - count($soldCountryPlayers),
        'total_spent' => $countryTotalSpent,
        'average_price' => $countryAveragePrice
    ];
}
?>

<?php include_once '../includes/header.php' ?>

    <!-- Main Content -->
    <div class="main-container">
        <div class="container py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-chart-line"></i> Auction Analytics</h1>
                <div class="d-flex gap-2">
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Export Report
                    </button>
                </div>
            </div>

            <!-- Analytics Overview -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-chart-pie"></i> Auction Overview</h3>
                </div>
                <div class="card-body">
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <i class="fas fa-users"></i>
                            <h3><?php echo $totalPlayers; ?></h3>
                            <p>Total Players</p>
                        </div>

                        <div class="stat-card">
                            <i class="fas fa-user-check"></i>
                            <h3><?php echo $soldPlayers; ?></h3>
                            <p>Sold Players</p>
                        </div>

                        <div class="stat-card">
                            <i class="fas fa-user-times"></i>
                            <h3><?php echo $unsoldPlayers; ?></h3>
                            <p>Unsold Players</p>
                        </div>

                        <div class="stat-card">
                            <i class="fas fa-dollar-sign"></i>
                            <h3><?php echo number_format($totalSpent, 0); ?></h3>
                            <p>Total Spent</p>
                        </div>

                        <div class="stat-card">
                            <i class="fas fa-coins"></i>
                            <h3><?php echo number_format($averagePrice, 0); ?></h3>
                            <p>Average Price</p>
                        </div>

                        <div class="stat-card">
                            <i class="fas fa-percentage"></i>
                            <h3><?php echo $totalPlayers > 0 ? round(($soldPlayers / $totalPlayers) * 100, 1) : 0; ?>%
                            </h3>
                            <p>Success Rate</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Analytics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-shield-alt"></i> Team Analytics</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-users"></i> Team</th>
                                    <th><i class="fas fa-user-friends"></i> Players</th>
                                    <th><i class="fas fa-dollar-sign"></i> Total Spent</th>
                                    <th><i class="fas fa-coins"></i> Average Price</th>
                                    <th><i class="fas fa-wallet"></i> Remaining Budget</th>
                                    <th><i class="fas fa-chart-bar"></i> Budget Utilization</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teamAnalytics as $team): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($team['name']); ?></strong></td>
                                    <td><span class="badge bg-primary"><?php echo $team['total_players']; ?></span></td>
                                    <td><strong><?php echo number_format($team['total_spent'], 2); ?></strong></td>
                                    <td><?php echo number_format($team['avg_price'], 2); ?></td>
                                    <td class="text-success"><?php echo number_format($team['remaining_budget'], 2); ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 8px;">
                                                <div class="progress-bar bg-primary"
                                                    style="width: <?php echo $team['budget_utilization']; ?>%"></div>
                                            </div>
                                            <small
                                                class="text-muted"><?php echo round($team['budget_utilization']); ?>%</small>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="chart-container mt-4">
                        <canvas id="teamSpendingChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Role Analytics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-tag"></i> Role-wise Analytics</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-tag"></i> Role</th>
                                    <th><i class="fas fa-users"></i> Total Players</th>
                                    <th><i class="fas fa-user-check"></i> Sold Players</th>
                                    <th><i class="fas fa-user-times"></i> Unsold Players</th>
                                    <th><i class="fas fa-dollar-sign"></i> Total Spent</th>
                                    <th><i class="fas fa-coins"></i> Average Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roleAnalytics as $role): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($role['role']); ?></strong></td>
                                    <td><span class="badge bg-secondary"><?php echo $role['total_players']; ?></span>
                                    </td>
                                    <td><span class="badge bg-success"><?php echo $role['sold_players']; ?></span></td>
                                    <td><span class="badge bg-warning"><?php echo $role['unsold_players']; ?></span>
                                    </td>
                                    <td><strong><?php echo number_format($role['total_spent'], 2); ?></strong></td>
                                    <td><?php echo number_format($role['average_price'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="chart-container mt-4">
                        <canvas id="roleDistributionChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Country Analytics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-flag"></i> Country-wise Analytics</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-flag"></i> Country</th>
                                    <th><i class="fas fa-users"></i> Total Players</th>
                                    <th><i class="fas fa-user-check"></i> Sold Players</th>
                                    <th><i class="fas fa-user-times"></i> Unsold Players</th>
                                    <th><i class="fas fa-dollar-sign"></i> Total Spent</th>
                                    <th><i class="fas fa-coins"></i> Average Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($countryAnalytics as $country): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($country['country']); ?></strong></td>
                                    <td><span class="badge bg-secondary"><?php echo $country['total_players']; ?></span>
                                    </td>
                                    <td><span class="badge bg-success"><?php echo $country['sold_players']; ?></span>
                                    </td>
                                    <td><span class="badge bg-warning"><?php echo $country['unsold_players']; ?></span>
                                    </td>
                                    <td><strong><?php echo number_format($country['total_spent'], 2); ?></strong></td>
                                    <td><?php echo number_format($country['average_price'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="chart-container mt-4">
                        <canvas id="countrySpendingChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Key Insights -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-lightbulb"></i> Key Insights</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="insight-item">
                                <i class="fas fa-trophy text-warning"></i>
                                <div>
                                    <h5>Top Spending Team</h5>
                                    <p>
                                        <?php 
                                        $topTeam = array_reduce($teamAnalytics, function($carry, $item) {
                                            return $carry['total_spent'] > $item['total_spent'] ? $carry : $item;
                                        });
                                        echo htmlspecialchars($topTeam['name']) . ' - ' . number_format($topTeam['total_spent'], 2);
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="insight-item">
                                <i class="fas fa-star text-success"></i>
                                <div>
                                    <h5>Most Expensive Role</h5>
                                    <p>
                                        <?php 
                                        $topRole = array_reduce($roleAnalytics, function($carry, $item) {
                                            return $carry['average_price'] > $item['average_price'] ? $carry : $item;
                                        });
                                        echo htmlspecialchars($topRole['role']) . ' - ' . number_format($topRole['average_price'], 2);
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="insight-item">
                                <i class="fas fa-chart-line text-primary"></i>
                                <div>
                                    <h5>Budget Utilization</h5>
                                    <p>
                                        <?php 
                                        $avgUtilization = array_sum(array_column($teamAnalytics, 'budget_utilization')) / count($teamAnalytics);
                                        echo round($avgUtilization, 1) . '% average budget utilization across all teams';
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="insight-item">
                                <i class="fas fa-percentage text-info"></i>
                                <div>
                                    <h5>Success Rate</h5>
                                    <p>
                                        <?php 
                                        $successRate = $totalPlayers > 0 ? ($soldPlayers / $totalPlayers) * 100 : 0;
                                        echo round($successRate, 1) . '% of players were successfully sold';
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
    // Team Spending Chart
    const teamSpendingCtx = document.getElementById('teamSpendingChart').getContext('2d');
    const teamSpendingChart = new Chart(teamSpendingCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php foreach ($teamAnalytics as $team): ?> '<?php echo addslashes($team['name']); ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'Total Spent ($)',
                data: [
                    <?php foreach ($teamAnalytics as $team): ?>
                    <?php echo $team['total_spent']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: 'rgba(37, 99, 235, 0.8)',
                borderColor: 'rgba(37, 99, 235, 1)',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Team Spending Comparison',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount ($)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Role Distribution Chart
    const roleDistributionCtx = document.getElementById('roleDistributionChart').getContext('2d');
    const roleDistributionChart = new Chart(roleDistributionCtx, {
        type: 'doughnut',
        data: {
            labels: [
                <?php foreach ($roleAnalytics as $role): ?> '<?php echo addslashes($role['role']); ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach ($roleAnalytics as $role): ?>
                    <?php echo $role['sold_players']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: [
                    'rgba(37, 99, 235, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ],
                borderColor: [
                    'rgba(37, 99, 235, 1)',
                    'rgba(16, 185, 129, 1)',
                    'rgba(245, 158, 11, 1)',
                    'rgba(239, 68, 68, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Player Distribution by Role',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    position: 'right',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        }
    });

    // Country Spending Chart
    const countrySpendingCtx = document.getElementById('countrySpendingChart').getContext('2d');
    const countrySpendingChart = new Chart(countrySpendingCtx, {
        type: 'line',
        data: {
            labels: [
                <?php foreach ($countryAnalytics as $country): ?> '<?php echo addslashes($country['country']); ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'Total Spent ($)',
                data: [
                    <?php foreach ($countryAnalytics as $country): ?>
                    <?php echo $country['total_spent']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Spending by Country',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount ()'
                    },
                    ticks: {
                        callback: function(value) {
                            return '' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    </script>
</body>

</html>