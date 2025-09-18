$(document).ready(function() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Initialize popovers
    $('[data-bs-toggle="popover"]').popover();
    
    // Format number with commas
    function number_format(number, decimals = 2) {
    number = parseFloat(number);
    if (isNaN(number)) return '0.00';
    return number.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    // Enhanced notification system
    function showNotification(message, type = 'info', duration = 3000) {
        const notification = $('#notification');
        
        // Remove existing notifications
        notification.removeClass('success info warning error').removeClass('show');
        
        // Add new notification
        notification.addClass(type).text(message);
        
        // Show with animation
        setTimeout(() => {
        notification.addClass('show');
        }, 100);
        
        // Hide after duration
        setTimeout(() => {
            notification.removeClass('show');
        }, duration);
    }
    
    // Enhanced timer update with animations
    function updateTimer() {
        $.ajax({
            url: 'api/get_timer.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                const timerDisplay = $('#timer-display');
                
                if (data.time_remaining > 0) {
                    const minutes = Math.floor(data.time_remaining / 60);
                    const seconds = data.time_remaining % 60;
                    const timeString = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    
                    // Add pulse animation for last 10 seconds
                    if (data.time_remaining <= 10) {
                        timerDisplay.addClass('pulse-warning');
                    } else {
                        timerDisplay.removeClass('pulse-warning');
                    }
                    
                    timerDisplay.text(timeString);
                } else {
                    timerDisplay.text('Time Expired');
                    timerDisplay.addClass('text-danger');
                    
                    // Move to next player
                    setTimeout(() => {
                    moveToNextPlayer();
                    }, 2000);
                }
                
                // Check auction status
                if (data.auction_status !== 'Live') {
                    showNotification('Auction status changed: ' + data.auction_status, 'info');
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                }
            },
            error: function() {
                $('#timer-display').text('--:--').addClass('text-muted');
            }
        });
    }
    
    // Enhanced move to next player with loading state
    function moveToNextPlayer() {
        const mainContent = $('.auction-main');
        mainContent.addClass('loading');
        
        $.ajax({
            url: 'api/get_next_player.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('Moving to next player...', 'info');
                    setTimeout(() => {
                    location.reload();
                    }, 1500);
                } else {
                    showNotification(response.message, 'info');
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                }
            },
            error: function() {
                showNotification('Error moving to next player', 'error');
                mainContent.removeClass('loading');
                setTimeout(() => {
                    location.reload();
                }, 3000);
            }
        });
    }
    
    // Enhanced auction data update
    function updateAuctionData() {
        $.ajax({
            url: 'api/get_auction_data.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                // Update current player
                if (data.current_player) {
                    const currentPlayerId = $('#player-id').val();
                    if (currentPlayerId != data.current_player.id) {
                        showNotification('New player up for auction!', 'info');
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                        return;
                    }

                    // Always update highest bid section in real time
                    if (data.highest_bid) {
                        const bidAmount = $('.bid-amount');
                        const oldAmount = parseFloat(bidAmount.text().replace(/[$,]/g, ''));
                        const newAmount = data.highest_bid.amount;

                        if (newAmount > oldAmount) {
                            bidAmount.addClass('bid-increase');
                            setTimeout(() => {
                                bidAmount.removeClass('bid-increase');
                            }, 1000);
                        }

                        // Always update the highest bid and team name
                        bidAmount.text('$' + number_format(data.highest_bid.amount, 2));
                        $('.current-bid p:last').text('By: ' + data.highest_bid.team_name);

                        // Update bid amount input min value
                        const minBid = data.highest_bid.amount + data.min_bid_increment;
                        $('#bid-amount').attr('min', minBid);

                        // Debug log for team IDs
                        console.log('Highest Bid Team ID:', data.highest_bid.team_id, 'Current Team ID:', data.current_team_id);
                        // Disable bid button if current team is the last highest bidder
                        if (data.highest_bid.team_id && data.current_team_id && data.highest_bid.team_id == data.current_team_id) {
                            $('.bid-button').prop('disabled', true).addClass('btn-secondary').removeClass('primary');
                        } else {
                            $('.bid-button').prop('disabled', false).addClass('primary').removeClass('btn-secondary');
                        }
                    }
                }
                
                // Update team budgets
                if (data.teams) {
                    data.teams.forEach(team => {
                        const teamCard = $(`.team-card:contains("${team.name}")`);
                        if (teamCard.length) {
                            const budgetElement = teamCard.find('.budget');
                            budgetElement.text('$' + number_format(team.budget, 0));
                        }
                    });
                }
            },
            error: function() {
                console.log('Error updating auction data');
            }
        });
    }
    
    // Enhanced bid placement
    function placeBid() {
        const bidAmount = $('#bid-amount').val();
        const playerId = $('#player-id').val();

        if (!bidAmount || !playerId) {
            showNotification('Please enter a valid bid amount', 'warning');
            return;
        }

        // Add loading state
        const bidButton = $('.bid-button');
        const originalText = bidButton.html();
        bidButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Placing Bid...');

        $.ajax({
            url: 'api/place_bid.php',
            method: 'POST',
            data: {
                player_id: playerId,
                amount: bidAmount
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('Bid placed successfully!', 'success');

                    // Update current bid display
                    $('.bid-amount').text('$' + number_format(bidAmount, 2));
                    $('.current-bid p:last').text('By: Your Team');

                    // Clear bid input
                    $('#bid-amount').val('');

                    // Add bid to history
                    addBidToHistory(bidAmount, 'Your Team');

                    // Do not disable the bid button here; let updateAuctionData handle it on next poll
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function() {
                showNotification('Error placing bid. Please try again.', 'error');
            },
            complete: function() {
                // Always re-enable the button after placing bid; let updateAuctionData handle disabling for the last bidder
                bidButton.prop('disabled', false).html(originalText);
            }
        });
    }
    
    // Add bid to history with animation
    function addBidToHistory(amount, teamName) {
        const bidHistory = $('.bid-history');
        const newBid = $(`
            <div class="bid-item new-bid">
                <div>
                    <div class="amount">$${number_format(amount, 2)}</div>
                    <div class="team">${teamName}</div>
                </div>
                <div class="time">${new Date().toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit'})}</div>
            </div>
        `);
        
        bidHistory.prepend(newBid);
        
        // Add highlight animation
        setTimeout(() => {
            newBid.removeClass('new-bid');
        }, 2000);
    }
    
    // Enhanced quick bid functionality
    function setBidAmount(amount) {
        const bidInput = $('#bid-amount');
        bidInput.val(amount);
        
        // Add visual feedback
        bidInput.addClass('bid-highlight');
        setTimeout(() => {
            bidInput.removeClass('bid-highlight');
        }, 500);
        
        // Focus on input
        bidInput.focus();
    }
    
    // Auto-refresh functionality
    if ($('#timer-display').length) {
        setInterval(updateTimer, 1000);
        setInterval(updateAuctionData, 5000);
    }
    
    // Handle bid form submission
    $('#bid-form').on('submit', function(e) {
        e.preventDefault();
        placeBid();
    });
    
    // Quick bid button handlers
    $(document).on('click', '.quick-bid-btn', function() {
        const amount = $(this).data('amount');
        if (amount) {
            setBidAmount(amount);
        }
    });
    
    // Enhanced form validation
    $('.form-control').on('input', function() {
        const $this = $(this);
        const value = $this.val();
        
        if (value) {
            $this.addClass('is-valid').removeClass('is-invalid');
        } else {
            $this.removeClass('is-valid is-invalid');
        }
    });
    
    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        const target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 800);
        }
    });
    
    // Enhanced card hover effects
    $('.card').hover(
        function() {
            $(this).addClass('card-hover');
        },
        function() {
            $(this).removeClass('card-hover');
        }
    );
    
    // Loading state management
    function setLoadingState(element, isLoading) {
        if (isLoading) {
            element.addClass('loading');
        } else {
            element.removeClass('loading');
        }
    }
    
    // Auto-hide notifications after 5 seconds
    setTimeout(() => {
        $('.notification.show').removeClass('show');
    }, 5000);
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + Enter to place bid
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            if ($('#bid-form').length) {
                placeBid();
            }
        }
        
        // Escape to close notifications
        if (e.key === 'Escape') {
            $('.notification').removeClass('show');
        }
    });
    
    // Responsive navigation toggle
    $('.navbar-toggler').on('click', function() {
        $('.navbar-collapse').toggleClass('show');
    });
    
    // Initialize any charts or advanced features
    if (typeof Chart !== 'undefined') {
        // Initialize charts if Chart.js is loaded
        initializeCharts();
    }
    
    // Chart initialization function
    function initializeCharts() {
        // Add chart initialization code here if needed
        console.log('Charts initialized');
    }
    
    // Export functions to global scope for onclick handlers
    window.setBidAmount = setBidAmount;
    window.placeBid = placeBid;
    window.showNotification = showNotification;
});

// Additional CSS animations
const additionalStyles = `
    .bid-increase {
        animation: bidIncrease 0.5s ease-in-out;
    }
    
    @keyframes bidIncrease {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); color: #28a745; }
        100% { transform: scale(1); }
    }
    
    .pulse-warning {
        animation: pulseWarning 1s infinite;
    }
    
    @keyframes pulseWarning {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .new-bid {
        animation: newBidHighlight 2s ease-out;
    }
    
    @keyframes newBidHighlight {
        0% { background-color: rgba(40, 167, 69, 0.2); }
        100% { background-color: transparent; }
    }
    
    .bid-highlight {
        animation: bidInputHighlight 0.5s ease-in-out;
    }
    
    @keyframes bidInputHighlight {
        0% { border-color: #007bff; }
        50% { border-color: #28a745; }
        100% { border-color: #007bff; }
    }
    
    .card-hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
    
    .loading {
        position: relative;
        pointer-events: none;
    }
    
    .loading::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }
    
    .loading::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 30px;
        height: 30px;
        margin: -15px 0 0 -15px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #007bff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        z-index: 1001;
    }
`;

// Inject additional styles
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);