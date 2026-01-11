<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('db_connect.php');

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$role = $_SESSION['role'];

// Fetch real data for user
$stats = [];
$recent_reports = [];

// Get report counts for user
$count_query = "SELECT status, COUNT(*) as count FROM reports WHERE user_id = ? GROUP BY status";
$stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Initialize counts
$status_counts = [
    'Pending' => 0,
    'Ongoing' => 0,
    'Completed' => 0,
    'Total' => 0
];

while($row = mysqli_fetch_assoc($result)) {
    $status_counts[$row['status']] = $row['count'];
}

// Calculate total
$total_query = "SELECT COUNT(*) as total FROM reports WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $total_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$total_result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$status_counts['Total'] = $total_result['total'];

// Get recent reports
$recent_query = "SELECT r.*, f.facility_name 
                 FROM reports r 
                 LEFT JOIN facilities f ON r.facility_id = f.facility_id 
                 WHERE r.user_id = ? 
                 ORDER BY r.report_date DESC 
                 LIMIT 5";
$stmt = mysqli_prepare($conn, $recent_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$recent_reports = mysqli_stmt_get_result($stmt);

// Get total urgent/high priority reports (if priority column exists, otherwise use 0)
$urgent_count = 0;
// Check if priority column exists
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM reports LIKE 'priority'");
if (mysqli_num_rows($check_column) > 0) {
    // Column exists, use it
    $priority_query = "SELECT COUNT(*) as urgent FROM reports WHERE user_id = ? AND (priority = 'High' OR priority = 'Urgent')";
    $stmt = mysqli_prepare($conn, $priority_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $priority_result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $urgent_count = $priority_result['urgent'] ?? 0;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard - HFRS</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    /* [Keep all the CSS styles from the previous version - they remain the same] */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    :root {
      --primary: #0a1930;
      --secondary: #00a8ff;
      --success: #2ed573;
      --warning: #ffa502;
      --danger: #ff4757;
      --light: #f8f9fa;
      --dark: #343a40;
      --gray: #6c757d;
    }

    body {
      background-color: #f4f6fa;
      color: #333;
    }

    /* Navbar */
    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 60px;
      background-color: var(--primary);
      color: white;
      position: sticky;
      top: 0;
      z-index: 1000;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .logo img {
      height: 45px;
    }

    .logo h1 {
      font-size: 22px;
      font-weight: 600;
    }

    .navbar ul {
      list-style: none;
      display: flex;
      gap: 30px;
    }

    .navbar a {
      text-decoration: none;
      color: white;
      font-weight: 500;
      transition: color 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .navbar a:hover {
      color: var(--secondary);
    }

    /* Dashboard Container */
    .dashboard-container {
      padding: 30px 60px;
      max-width: 1400px;
      margin: 0 auto;
    }

    /* Welcome Section */
    .welcome-section {
      background: linear-gradient(135deg, var(--primary) 0%, #1a3a5f 100%);
      color: white;
      padding: 30px 40px;
      border-radius: 16px;
      margin-bottom: 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 8px 20px rgba(10, 25, 48, 0.2);
    }

    .welcome-text h1 {
      font-size: 32px;
      margin-bottom: 10px;
    }

    .welcome-text p {
      opacity: 0.9;
      font-size: 16px;
    }

    .role-badge {
      background: rgba(255,255,255,0.1);
      padding: 8px 20px;
      border-radius: 50px;
      font-weight: 600;
      border: 1px solid rgba(255,255,255,0.2);
    }

    /* Quick Stats */
    .quick-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      display: flex;
      align-items: center;
      gap: 20px;
      transition: transform 0.3s;
    }

    .stat-card:hover {
      transform: translateY(-5px);
    }

    .stat-icon {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
    }

    .stat-icon.pending { background: rgba(255, 165, 2, 0.1); color: var(--warning); }
    .stat-icon.progress { background: rgba(0, 168, 255, 0.1); color: var(--secondary); }
    .stat-icon.completed { background: rgba(46, 213, 115, 0.1); color: var(--success); }
    .stat-icon.total { background: rgba(52, 58, 64, 0.1); color: var(--dark); }
    .stat-icon.urgent { background: rgba(255, 71, 87, 0.1); color: var(--danger); }
    .stat-icon.time { background: rgba(108, 117, 125, 0.1); color: var(--gray); }

    .stat-content h3 {
      font-size: 14px;
      color: var(--gray);
      margin-bottom: 5px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .stat-content .number {
      font-size: 32px;
      font-weight: 700;
      color: var(--primary);
    }

    .stat-content .subtext {
      font-size: 13px;
      color: var(--gray);
      margin-top: 5px;
    }

    /* Main Content Grid */
    .dashboard-grid {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 30px;
      margin-bottom: 40px;
    }

    @media (max-width: 1024px) {
      .dashboard-grid {
        grid-template-columns: 1fr;
      }
    }

    /* Chart Container */
    .chart-container {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      height: 300px;
    }

    .chart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }

    .chart-header h3 {
      color: var(--primary);
      font-size: 16px;
    }

    #reportChart {
      height: 200px !important;
    }

    /* Recent Activity */
    .activity-container {
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }

    .activity-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .activity-header h3 {
      color: var(--primary);
      font-size: 18px;
    }

    .activity-list {
      list-style: none;
    }

    .activity-item {
      padding: 15px 0;
      border-bottom: 1px solid #eee;
      display: flex;
      align-items: center;
      gap: 15px;
      transition: background-color 0.2s;
    }

    .activity-item:hover {
      background-color: #f8f9fa;
      border-radius: 8px;
      padding-left: 15px;
      padding-right: 15px;
      margin-left: -15px;
      margin-right: -15px;
    }

    .activity-item:last-child {
      border-bottom: none;
    }

    .activity-icon {
      width: 40px;
      height: 40px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 16px;
    }

    .activity-icon.pending { background: var(--warning); }
    .activity-icon.ongoing { background: var(--secondary); }
    .activity-icon.completed { background: var(--success); }

    .activity-details {
      flex: 1;
    }

    .activity-details h4 {
      font-size: 15px;
      margin-bottom: 5px;
      color: var(--primary);
    }

    .activity-details p {
      font-size: 13px;
      color: var(--gray);
    }

    .activity-time {
      font-size: 12px;
      color: var(--gray);
      text-align: right;
      min-width: 60px;
    }

    /* Status Badge */
    .status-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .status-pending { background: #fff3cd; color: #856404; }
    .status-ongoing { background: #cce5ff; color: #004085; }
    .status-completed { background: #d4edda; color: #155724; }

    /* Quick Actions */
    .quick-actions {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }

    .action-card {
      background: white;
      border-radius: 12px;
      padding: 25px;
      text-align: center;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      transition: all 0.3s;
      border: 2px solid transparent;
    }

    .action-card:hover {
      border-color: var(--secondary);
      transform: translateY(-5px);
    }

    .action-icon {
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, var(--secondary), #0077cc);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 15px;
      color: white;
      font-size: 24px;
    }

    .action-card h3 {
      color: var(--primary);
      margin-bottom: 10px;
      font-size: 16px;
    }

    .action-card p {
      color: var(--gray);
      font-size: 14px;
      margin-bottom: 15px;
    }

    .btn-action {
      display: inline-block;
      padding: 8px 20px;
      background: var(--primary);
      color: white;
      text-decoration: none;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 500;
      transition: background 0.3s;
    }

    .btn-action:hover {
      background: var(--secondary);
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 40px 20px;
      color: var(--gray);
    }

    .empty-state i {
      font-size: 48px;
      margin-bottom: 15px;
      opacity: 0.5;
    }

    .empty-state h4 {
      font-size: 18px;
      margin-bottom: 10px;
      color: var(--primary);
    }

    /* Footer */
    footer {
      background-color: var(--primary);
      color: white;
      text-align: center;
      padding: 20px;
      margin-top: 40px;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .navbar {
        padding: 15px 20px;
        flex-direction: column;
        gap: 15px;
      }
      
      .dashboard-container {
        padding: 20px;
      }
      
      .welcome-section {
        flex-direction: column;
        text-align: center;
        gap: 15px;
        padding: 25px;
      }
      
      .quick-stats {
        grid-template-columns: 1fr;
      }
      
      .quick-actions {
        grid-template-columns: 1fr;
      }
      
      .activity-item {
        flex-direction: column;
        text-align: center;
        gap: 10px;
      }
      
      .activity-time {
        text-align: center;
        margin-top: 5px;
      }
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <header class="navbar">
    <div class="logo">
      <img src="images/utemlogo3.png" alt="UTeM Logo">
      <h1>Hostel Facilities Report System</h1>
    </div>
    <nav>
      <ul>
        <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="report.php"><i class="fas fa-plus-circle"></i> Report</a></li>
        <li><a href="myreport.php"><i class="fas fa-list-alt"></i> My Reports</a></li>
        <li><a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </nav>
  </header>

  <!-- Dashboard Container -->
  <div class="dashboard-container">
    <!-- Welcome Section -->
    <section class="welcome-section">
      <div class="welcome-text">
        <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?>! 👋</h1>
        <p>Track your maintenance reports and stay updated on their progress.</p>
      </div>
      <div class="role-badge">
        <?php echo htmlspecialchars($role); ?>
      </div>
    </section>

    <!-- Quick Stats -->
    <section class="quick-stats">
      <div class="stat-card">
        <div class="stat-icon pending">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
          <h3>Pending Reports</h3>
          <div class="number"><?php echo $status_counts['Pending']; ?></div>
          <div class="subtext">Waiting for assignment</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon progress">
          <i class="fas fa-tools"></i>
        </div>
        <div class="stat-content">
          <h3>In Progress</h3>
          <div class="number"><?php echo $status_counts['Ongoing']; ?></div>
          <div class="subtext">Being worked on</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon completed">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <h3>Completed</h3>
          <div class="number"><?php echo $status_counts['Completed']; ?></div>
          <div class="subtext">Successfully resolved</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon total">
          <i class="fas fa-file-alt"></i>
        </div>
        <div class="stat-content">
          <h3>Total Reports</h3>
          <div class="number"><?php echo $status_counts['Total']; ?></div>
          <div class="subtext">All-time submissions</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon urgent">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
          <h3>Recent Reports</h3>
          <div class="number"><?php echo min(5, $status_counts['Total']); ?></div>
          <div class="subtext">Last 5 submissions</div>
        </div>
      </div>
    </section>

    <!-- Main Content Grid -->
    <div class="dashboard-grid">
      <!-- Chart Section -->
      <div class="chart-container">
        <div class="chart-header">
          <h3>Report Status Overview</h3>
          <select id="timeFilter" style="padding: 5px 10px; border-radius: 6px; border: 1px solid #ddd; font-size: 13px;">
            <option value="all">All Time</option>
            <option value="month">This Month</option>
            <option value="week">This Week</option>
          </select>
        </div>
        <canvas id="reportChart"></canvas>
      </div>

      <!-- Recent Activity -->
      <div class="activity-container">
        <div class="activity-header">
          <h3>Recent Reports</h3>
          <a href="myreport.php" style="font-size: 14px; color: var(--secondary);">View All</a>
        </div>
        <?php if (mysqli_num_rows($recent_reports) > 0): ?>
          <ul class="activity-list">
            <?php while($report = mysqli_fetch_assoc($recent_reports)): ?>
              <li class="activity-item">
                <div class="activity-icon <?php echo strtolower($report['status']); ?>">
                  <i class="fas fa-<?php 
                    echo $report['status'] == 'Completed' ? 'check' : 
                         ($report['status'] == 'Ongoing' ? 'wrench' : 'clock'); 
                  ?>"></i>
                </div>
                <div class="activity-details">
                  <h4><?php echo htmlspecialchars($report['facility_name'] ?: 'General Issue'); ?></h4>
                  <p>
                    <span class="status-badge status-<?php echo strtolower($report['status']); ?>">
                      <?php echo $report['status']; ?>
                    </span>
                  </p>
                </div>
                <div class="activity-time">
                  <?php 
                    $report_date = strtotime($report['report_date']);
                    $now = time();
                    $diff = $now - $report_date;
                    
                    if ($diff < 3600) {
                      echo '<span title="' . date('M d, Y H:i', $report_date) . '">' . round($diff/60) . 'm ago</span>';
                    } elseif ($diff < 86400) {
                      echo '<span title="' . date('M d, Y H:i', $report_date) . '">' . round($diff/3600) . 'h ago</span>';
                    } else {
                      echo '<span title="' . date('M d, Y H:i', $report_date) . '">' . date('M d', $report_date) . '</span>';
                    }
                  ?>
                </div>
              </li>
            <?php endwhile; ?>
          </ul>
        <?php else: ?>
          <div class="empty-state">
            <i class="fas fa-clipboard-list"></i>
            <h4>No Reports Yet</h4>
            <p>You haven't submitted any reports yet.</p>
            <a href="report.php" class="btn-action" style="margin-top: 15px;">Submit Your First Report</a>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick Actions -->
    <section class="quick-actions">
      <div class="action-card">
        <div class="action-icon">
          <i class="fas fa-plus-circle"></i>
        </div>
        <h3>New Report</h3>
        <p>Submit a new maintenance request</p>
        <a href="report.php" class="btn-action">Create Report</a>
      </div>
      
      <div class="action-card">
        <div class="action-icon">
          <i class="fas fa-list-alt"></i>
        </div>
        <h3>My Reports</h3>
        <p>View all your submitted reports</p>
        <a href="view_report.php" class="btn-action">View All</a>
      </div>
      
      <div class="action-card">
        <div class="action-icon">
          <i class="fas fa-history"></i>
        </div>
        <h3>Report History</h3>
        <p>View your past reports</p>
        <a href="myreport.php" class="btn-action">View History</a>
      </div>
    </section>
  </div>

  <footer>
    <p>© 2025 Hostel Facilities Report System | Developed for UTeM</p>
  </footer>

  <script>
    // Initialize Chart
    const ctx = document.getElementById('reportChart').getContext('2d');
    
    // Chart data from PHP
    const chartData = {
      labels: ['Pending', 'In Progress', 'Completed'],
      datasets: [{
        data: [
          <?php echo $status_counts['Pending']; ?>,
          <?php echo $status_counts['Ongoing']; ?>,
          <?php echo $status_counts['Completed']; ?>
        ],
        backgroundColor: [
          'rgba(255, 165, 2, 0.7)',
          'rgba(0, 168, 255, 0.7)',
          'rgba(46, 213, 115, 0.7)'
        ],
        borderColor: [
          'rgba(255, 165, 2, 1)',
          'rgba(0, 168, 255, 1)',
          'rgba(46, 213, 115, 1)'
        ],
        borderWidth: 1
      }]
    };
    
    const reportChart = new Chart(ctx, {
      type: 'doughnut',
      data: chartData,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 20,
              usePointStyle: true,
              font: {
                size: 12
              }
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                let label = context.label || '';
                if (label) {
                  label += ': ';
                }
                label += context.parsed + ' reports';
                return label;
              }
            }
          }
        },
        cutout: '60%'
      }
    });

    // Time filter functionality
    document.getElementById('timeFilter').addEventListener('change', function(e) {
      console.log('Filter changed to:', e.target.value);
      // Note: For full functionality, this would need AJAX to fetch filtered data
      alert('Filter would update the chart data. Requires backend implementation.');
    });

    // Auto-refresh dashboard every 2 minutes
    setTimeout(() => {
      window.location.reload();
    }, 120000); // 2 minutes
    
    // Make activity items clickable
    document.querySelectorAll('.activity-item').forEach(item => {
      if (item.querySelector('.activity-details h4')) {
        item.style.cursor = 'pointer';
        item.addEventListener('click', function() {
          // This would typically redirect to the report details page
          alert('Would navigate to report details page');
        });
      }
    });
  </script>
</body>
</html>