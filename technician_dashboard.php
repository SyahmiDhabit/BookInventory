<?php
session_start();
include('db_connect.php');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Technician") {
    header("Location: login.php");
    exit();
}
$tech_id = $_SESSION['user_id'];
$tech_name = $_SESSION['user_name'];

// FETCH ONLY ESSENTIAL DATA
// Get current tasks by status
$current_tasks = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'Ongoing' THEN 1 ELSE 0 END) as ongoing,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
    FROM reports 
    WHERE assigned_to = $tech_id
");
$tasks = mysqli_fetch_assoc($current_tasks);

// Get urgent tasks (if they exist)
$urgent_count = 0;
$check_priority = mysqli_query($conn, "SHOW COLUMNS FROM reports LIKE 'priority'");
if (mysqli_num_rows($check_priority) > 0) {
    $urgent = mysqli_query($conn, "
        SELECT COUNT(*) as urgent 
        FROM reports 
        WHERE assigned_to = $tech_id 
        AND status != 'Completed' 
        AND priority IN ('High', 'Urgent')
    ");
    $urgent_count = mysqli_fetch_assoc($urgent)['urgent'] ?? 0;
}

// Get today's active tasks (what matters most RIGHT NOW)
$today_tasks = mysqli_query($conn, "
    SELECT r.*, f.facility_name, u.full_name as reporter,
        CASE r.priority
            WHEN 'Urgent' THEN 'danger'
            WHEN 'High' THEN 'warning'
            WHEN 'Medium' THEN 'info'
            WHEN 'Low' THEN 'success'
            ELSE 'secondary'
        END as priority_class
    FROM reports r
    LEFT JOIN facilities f ON r.facility_id = f.facility_id
    LEFT JOIN users u ON r.user_id = u.user_id
    WHERE r.assigned_to = $tech_id 
    AND r.status != 'Completed'
    ORDER BY 
        CASE r.priority
            WHEN 'Urgent' THEN 1
            WHEN 'High' THEN 2
            WHEN 'Medium' THEN 3
            WHEN 'Low' THEN 4
            ELSE 5
        END,
        r.report_date ASC
    LIMIT 8
");

// Calculate completion rate (the most important metric)
$completion_rate = ($tasks['total'] > 0) ? 
    round(($tasks['completed'] / $tasks['total']) * 100) : 100;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Technician</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --sidebar-bg: #0a1930;
            --sidebar-hover: #1e3a5c;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        body {
            display: flex;
            background: #f8fafc;
            min-height: 100vh;
            color: var(--dark);
        }
        
        /* ====== SIMPLIFIED SIDEBAR ====== */
        .sidebar {
            width: 240px;
            background: linear-gradient(180deg, var(--sidebar-bg) 0%, #0d2342 100%);
            color: white;
            padding: 25px 0;
            position: fixed;
            height: 100vh;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
        }
        
        .sidebar-header {
            padding: 0 25px 25px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-header h2 {
            font-size: 22px;
            font-weight: 800;
            color: white;
            margin-bottom: 5px;
        }
        
        .tech-profile {
            padding: 0 25px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .tech-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 22px;
            margin: 0 auto 15px;
        }
        
        .tech-name {
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 8px;
        }
        
        .tech-role {
            display: block;
            text-align: center;
            font-size: 12px;
            color: #00a8ff;
            background: rgba(0, 168, 255, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
            width: fit-content;
            margin: 0 auto;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu ul {
            list-style: none;
            padding: 0 20px;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 500;
            font-size: 14px;
        }
        
        .sidebar-menu a:hover {
            background: var(--sidebar-hover);
            color: white;
        }
        
        .sidebar-menu a.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        .sidebar-menu a i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }
        
        /* MAIN CONTENT - CLEANER */
        .main-content {
            flex: 1;
            margin-left: 240px;
            padding: 30px;
        }
        
        /* HEADER - SIMPLIFIED */
        .header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 32px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .page-subtitle {
            color: var(--gray);
            font-size: 15px;
        }
        
        /* QUICK STATS - MINIMALIST */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--primary);
        }
        
        .stat-card.urgent {
            border-left-color: var(--danger);
        }
        
        .stat-card.ongoing {
            border-left-color: var(--primary);
        }
        
        .stat-card.completed {
            border-left-color: var(--secondary);
        }
        
        .stat-card.rate {
            border-left-color: #8b5cf6;
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }
        
        .stat-card.urgent .stat-icon { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .stat-card.ongoing .stat-icon { background: rgba(37, 99, 235, 0.1); color: var(--primary); }
        .stat-card.completed .stat-icon { background: rgba(16, 185, 129, 0.1); color: var(--secondary); }
        .stat-card.rate .stat-icon { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
        
        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--dark);
            line-height: 1;
        }
        
        .stat-label {
            font-size: 13px;
            color: var(--gray);
            margin-top: 5px;
        }
        
        /* CURRENT TASKS - SIMPLE LIST */
        .current-tasks {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .task-list {
            list-style: none;
        }
        
        .task-item {
            padding: 18px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: background 0.2s;
        }
        
        .task-item:hover {
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .task-item:last-child {
            border-bottom: none;
        }
        
        .task-priority {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .priority-urgent { background: var(--danger); }
        .priority-high { background: var(--warning); }
        .priority-medium { background: var(--primary); }
        .priority-low { background: var(--secondary); }
        
        .task-details {
            flex: 1;
        }
        
        .task-title {
            font-weight: 600;
            font-size: 15px;
            color: var(--dark);
            margin-bottom: 4px;
        }
        
        .task-meta {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: var(--gray);
        }
        
        .task-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-ongoing { background: #dbeafe; color: #1e40af; }
        
        /* QUICK ACTIONS */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        
        .action-btn {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: var(--dark);
        }
        
        .action-btn:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.1);
        }
        
        .action-btn i {
            font-size: 24px;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .action-label {
            font-weight: 600;
            font-size: 14px;
        }
        
        /* EMPTY STATE */
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
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>HFRS Technician</h2>
            <p style="font-size: 13px; color: rgba(255,255,255,0.7);">Control Panel</p>
        </div>
        
        <div class="tech-profile">
            <div class="tech-avatar">
                <?php echo strtoupper(substr($tech_name, 0, 1)); ?>
            </div>
            <div class="tech-name"><?php echo htmlspecialchars($tech_name); ?></div>
            <span class="tech-role">Technician</span>
        </div>
        
        <nav class="sidebar-menu">
            <ul>
                <li><a href="technician_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
                <li><a href="technician_profiles.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="mytasktech.php"><i class="fas fa-tasks"></i>My Tasks</a></li>
                <li><a href="complete_tech.php"><i class="fas fa-check-circle"></i>Completed</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($tech_name); ?>. Here's what needs your attention.</p>
        </div>

        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="stat-card urgent">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <span style="font-size: 12px; color: var(--danger); font-weight: 600;">Urgent</span>
                </div>
                <div class="stat-value"><?php echo $urgent_count; ?></div>
                <div class="stat-label">High priority tasks</div>
            </div>
            
            <div class="stat-card ongoing">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <span style="font-size: 12px; color: var(--primary); font-weight: 600;">Active</span>
                </div>
                <div class="stat-value"><?php echo $tasks['ongoing'] ?? 0; ?></div>
                <div class="stat-label">Tasks in progress</div>
            </div>
            
            <div class="stat-card completed">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <span style="font-size: 12px; color: var(--secondary); font-weight: 600;">Done</span>
                </div>
                <div class="stat-value"><?php echo $tasks['completed'] ?? 0; ?></div>
                <div class="stat-label">Tasks completed</div>
            </div>
            
            <div class="stat-card rate">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <span style="font-size: 12px; color: #8b5cf6; font-weight: 600;">Rate</span>
                </div>
                <div class="stat-value"><?php echo $completion_rate; ?>%</div>
                <div class="stat-label">Completion rate</div>
            </div>
        </div>

        <!-- Current Tasks -->
        <div class="current-tasks">
            <div class="section-header">
                <h2 class="section-title">Current Tasks</h2>
                <span style="font-size: 14px; color: var(--gray);">
                    <?php echo ($tasks['pending'] + $tasks['ongoing']) ?? 0; ?> tasks awaiting action
                </span>
            </div>
            
            <?php if (mysqli_num_rows($today_tasks) > 0): ?>
                <ul class="task-list">
                    <?php while($task = mysqli_fetch_assoc($today_tasks)): ?>
                    <li class="task-item">
                        <div class="task-priority priority-<?php echo $task['priority_class'] ?? 'medium'; ?>"></div>
                        <div class="task-details">
                            <div class="task-title">
                                <?php echo htmlspecialchars($task['facility_name']); ?>
                                <?php if ($task['priority'] == 'Urgent' || $task['priority'] == 'High'): ?>
                                    <span style="color: var(--danger); font-size: 12px; margin-left: 8px;">
                                        <i class="fas fa-exclamation-circle"></i> <?php echo $task['priority']; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="task-meta">
                                <span><i class="far fa-user"></i> <?php echo htmlspecialchars($task['reporter']); ?></span>
                                <span><i class="far fa-clock"></i> <?php echo date("h:i A", strtotime($task['report_date'])); ?></span>
                            </div>
                        </div>
                        <div class="task-status status-<?php echo strtolower($task['status']); ?>">
                            <?php echo $task['status']; ?>
                        </div>
                    </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>No active tasks</h3>
                    <p>All your assigned tasks are completed. Great work!</p>
                </div>
            <?php endif; ?>
        </div>

      
    </div>

    <script>
        // Simple page interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Task item click for more details
            const taskItems = document.querySelectorAll('.task-item');
            taskItems.forEach(item => {
                item.addEventListener('click', function() {
                    const taskId = this.dataset.taskId;
                    if(taskId) {
                        window.location.href = `task_details.php?id=${taskId}`;
                    }
                });
            });
        });
    </script>
</body>
</html>