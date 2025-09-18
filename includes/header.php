<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
       <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auction Analytics - Cricket Auction System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <nav class="navbar d-flex navbar-expand-md navbar-light bg-white px-0">
                <a class="navbar-brand d-flex align-items-center gap-2" href="#" style="font-size:1.7rem;">
                    <i class="fas fa-gavel"></i> CAS
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-nav ms-auto mb-2 mb-md-0 gap-1">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Admin Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="players.php">Manage Players</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="teams.php">Manage Teams</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_auction.php">Manage Auction</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="analytics.php">Analytics</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../auction_realtime.php">Live Auction</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
        <style>
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.2rem !important;
                text-align: center;
            }
            .navbar-nav .nav-link {
                font-size: 1rem !important;
                padding: 0.5rem 0.8rem !important;
            }
        }
        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 1rem !important;
            }
            .navbar-nav .nav-link {
                font-size: 0.95rem !important;
                padding: 0.4rem 0.6rem !important;
            }
        }
        </style>
    </header>