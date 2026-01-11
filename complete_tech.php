<?php
session_start();
include('db_connect.php');

// Only technicians can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Technician") {
    header("Location: login.php");
    exit();
}

$tech_id = $_SESSION['user_id'];
$tech_name = $_SESSION['user_name'];

// Handle filters
$date_filter = $_GET['date'] ?? 'this_month';
$facility_filter = $_GET['facility'] ?? 'all';
$problem_filter = $_GET['problem'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Date range calculations for queries
switch($date_filter) {
    case 'this_week':
        $date_condition = "AND r.report_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $analytics_range = "Last 7 Days";
        $chart_date_condition = "AND report_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'this_month':
        $date_condition = "AND r.report_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $analytics_range = "Last 30 Days";
        $chart_date_condition = "AND report_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'last_month':
        $date_condition = "AND r.report_date >= DATE_SUB(NOW(), INTERVAL 60 DAY) 
                          AND r.report_date < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $analytics_range = "Previous Month";
        $chart_date_condition = "AND report_date >= DATE_SUB(NOW(), INTERVAL 60 DAY) 
                                AND report_date < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'all':
    default:
        $date_condition = "";
        $analytics_range = "All Time";
        $chart_date_condition = "";
}

// Build query for completed tasks
$query = "SELECT r.*, f.facility_name, u.full_name as reporter_name,
                 (SELECT COUNT(*) FROM task_photos tp WHERE tp.task_id = r.report_id) as photo_count,
                 (SELECT photo_path FROM task_photos tp WHERE tp.task_id = r.report_id 
                  AND photo_type = 'after' LIMIT 1) as sample_photo
          FROM reports r
          LEFT JOIN facilities f ON r.facility_id = f.facility_id
          LEFT JOIN users u ON r.user_id = u.user_id
          WHERE r.assigned_to = $tech_id AND r.status = 'Completed'";

$query .= $date_condition;

// Apply filters
if ($facility_filter !== 'all') {
    $query .= " AND r.facility_id = " . intval($facility_filter);
}

if (!empty($search)) {
    $safe_search = mysqli_real_escape_string($conn, $search);
    $query .= " AND (f.facility_name LIKE '%$safe_search%' 
                     OR r.issue_description LIKE '%$safe_search%'
                     OR r.progress_notes LIKE '%$safe_search%')";
}

// For counting total records
$count_query = str_replace("SELECT r.*, f.facility_name, u.full_name as reporter_name", "SELECT COUNT(*) as total", $query);
$count_result = mysqli_query($conn, $count_query);
$total_tasks = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_tasks / $items_per_page);

// Add ordering and pagination
$query .= " ORDER BY r.report_date DESC LIMIT $items_per_page OFFSET $offset";
$tasks_result = mysqli_query($conn, $query);

// ========== ANALYTICAL DATA ==========
// 1. Performance metrics - Simple counts only
$metrics_query = "SELECT 
    COUNT(*) as total_completed,
    COUNT(DISTINCT facility_id) as unique_facilities
    FROM reports 
    WHERE assigned_to = $tech_id AND status = 'Completed'";

// Add date condition if it exists
if (!empty($date_condition)) {
    $clean_date_condition = str_replace("AND r.report_date", "AND report_date", $date_condition);
    $metrics_query .= " " . $clean_date_condition;
}

$metrics = mysqli_fetch_assoc(mysqli_query($conn, $metrics_query));

// 2. Monthly trend - ALWAYS USE BAR CHART FOR CONSISTENCY
if ($date_filter == 'today') {
    // For today, show hourly breakdown with proper time format
    $trend_query = "SELECT 
        CONCAT(
            LPAD(HOUR(report_date), 2, '0'), 
            ':00-', 
            LPAD(HOUR(report_date), 2, '0'), 
            ':59'
        ) as hour_range,
        HOUR(report_date) as hour,
        COUNT(*) as completed
        FROM reports
        WHERE assigned_to = $tech_id AND status = 'Completed'
        AND DATE(report_date) = CURDATE()
        GROUP BY HOUR(report_date)
        ORDER BY hour";
    
    $trend_result = mysqli_query($conn, $trend_query);
    $hourly_trend = [];
    $hour_labels = [];
    $hour_data = [];
    
    // Create array for all 24 hours
    $all_hours = [];
    for ($i = 0; $i < 24; $i++) {
        $all_hours[$i] = 0;
    }
    
    while($row = mysqli_fetch_assoc($trend_result)) {
        $all_hours[$row['hour']] = $row['completed'];
        $hour_labels[] = sprintf("%02d:00", $row['hour']);
        $hour_data[] = $row['completed'];
    }
    
    // Fill in missing hours
    if (count($hour_labels) < 24) {
        $hour_labels = [];
        $hour_data = [];
        for ($i = 0; $i < 24; $i++) {
            $hour_labels[] = sprintf("%02d:00", $i);
            $hour_data[] = $all_hours[$i];
        }
    }
    
    $chart_labels = $hour_labels;
    $chart_data = $hour_data;
    $chart_title = "Tasks Completed Per Hour Today";
    
} elseif ($date_filter == 'this_week') {
    // For this week, show daily breakdown with actual dates
    $trend_query = "SELECT 
        DATE(report_date) as date,
        DATE_FORMAT(report_date, '%W %d %b') as day_label,
        DATE_FORMAT(report_date, '%a') as day_short,
        COUNT(*) as completed
        FROM reports
        WHERE assigned_to = $tech_id AND status = 'Completed'
        AND report_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(report_date)
        ORDER BY date";
    
    $trend_result = mysqli_query($conn, $trend_query);
    
    // Create array for last 7 days
    $last_7_days = [];
    $day_labels = [];
    $day_data = [];
    
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $last_7_days[$date] = 0;
    }
    
    // Fill with actual data
    while($row = mysqli_fetch_assoc($trend_result)) {
        $last_7_days[$row['date']] = $row['completed'];
    }
    
    // Format labels
    foreach ($last_7_days as $date => $count) {
        $day_labels[] = date('D d', strtotime($date));
        $day_data[] = $count;
    }
    
    $chart_labels = $day_labels;
    $chart_data = $day_data;
    $chart_title = "Tasks Completed Per Day (Last 7 Days)";
    
} elseif ($date_filter == 'this_month' || $date_filter == 'last_month') {
    // For monthly, show weekly breakdown with actual date ranges
    if ($date_filter == 'this_month') {
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
    } else {
        // Last month
        $start_date = date('Y-m-01', strtotime('-1 month'));
        $end_date = date('Y-m-t', strtotime('-1 month'));
    }
    
    // Get all weeks in the month
    $trend_query = "SELECT 
        CONCAT(
            'Week ', 
            WEEK(report_date, 5) - WEEK(DATE_SUB(DATE(report_date), INTERVAL DAYOFMONTH(report_date)-1 DAY), 5) + 1,
            ' (',
            DATE_FORMAT(
                DATE_ADD(
                    DATE_SUB(DATE(report_date), INTERVAL DAYOFMONTH(report_date)-1 DAY),
                    INTERVAL (WEEK(report_date, 5) - WEEK(DATE_SUB(DATE(report_date), INTERVAL DAYOFMONTH(report_date)-1 DAY), 5)) * 7 DAY
                ),
                '%d %b'
            ),
            '-',
            DATE_FORMAT(
                LEAST(
                    DATE_ADD(
                        DATE_SUB(DATE(report_date), INTERVAL DAYOFMONTH(report_date)-1 DAY),
                        INTERVAL (WEEK(report_date, 5) - WEEK(DATE_SUB(DATE(report_date), INTERVAL DAYOFMONTH(report_date)-1 DAY), 5)) * 7 + 6 DAY
                    ),
                    LAST_DAY(DATE(report_date))
                ),
                '%d %b'
            ),
            ')'
        ) as week_label,
        COUNT(*) as completed
        FROM reports
        WHERE assigned_to = $tech_id AND status = 'Completed'
        AND DATE(report_date) BETWEEN '$start_date' AND '$end_date'
        GROUP BY YEAR(report_date), WEEK(report_date, 5)
        ORDER BY MIN(report_date)";
    
    $trend_result = mysqli_query($conn, $trend_query);
    
    // Calculate weeks in month
    $weeks_in_month = [];
    $week_labels = [];
    $week_data = [];
    
    // Determine number of weeks in the month
    $first_day = date('N', strtotime($start_date)); // 1 (Monday) through 7 (Sunday)
    $days_in_month = date('t', strtotime($start_date));
    $total_weeks = ceil(($first_day + $days_in_month - 1) / 7);
    
    // Initialize all weeks with 0
    for ($week = 1; $week <= $total_weeks; $week++) {
        $weeks_in_month[$week] = 0;
    }
    
    // Fill with actual data
    while($row = mysqli_fetch_assoc($trend_result)) {
        // Extract week number from label (e.g., "Week 1 (01 Jan-07 Jan)")
        if (preg_match('/Week (\d+)/', $row['week_label'], $matches)) {
            $week_num = intval($matches[1]);
            if ($week_num >= 1 && $week_num <= $total_weeks) {
                $weeks_in_month[$week_num] = $row['completed'];
            }
        }
    }
    
    // Create labels for all weeks
    for ($week = 1; $week <= $total_weeks; $week++) {
        // Calculate week start date
        $week_start = date('d M', strtotime($start_date . " + " . (($week - 1) * 7 - ($first_day - 1)) . " days"));
        
        // Calculate week end date
        $week_end_days = min(($week * 7 - $first_day), $days_in_month - 1);
        $week_end = date('d M', strtotime($start_date . " + " . $week_end_days . " days"));
        
        $week_labels[] = "Week $week\n($week_start-$week_end)";
        $week_data[] = $weeks_in_month[$week];
    }
    
    $chart_labels = $week_labels;
    $chart_data = $week_data;
    $chart_title = ($date_filter == 'this_month') ? "Tasks Completed Per Week (This Month)" : "Tasks Completed Per Week (Last Month)";
    
} else {
    // For all time, show monthly breakdown (last 12 months)
    $trend_query = "SELECT 
        DATE_FORMAT(report_date, '%Y-%m') as month,
        DATE_FORMAT(report_date, '%b %Y') as month_label,
        COUNT(*) as completed
        FROM reports
        WHERE assigned_to = $tech_id AND status = 'Completed'
        AND report_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(report_date, '%Y-%m')
        ORDER BY month";
    
    $trend_result = mysqli_query($conn, $trend_query);
    $monthly_trend = [];
    $month_labels = [];
    $month_data = [];
    while($row = mysqli_fetch_assoc($trend_result)) {
        $monthly_trend[] = $row;
        $month_labels[] = $row['month_label'];
        $month_data[] = $row['completed'];
    }
    $chart_labels = $month_labels;
    $chart_data = $month_data;
    $chart_title = "Tasks Completed Per Month (Last 12 Months)";
}

// 3. Problem type analysis
$problem_query = "SELECT 
    CASE 
        WHEN issue_description LIKE '%wiring%' OR issue_description LIKE '%electri%' THEN 'Electrical'
        WHEN issue_description LIKE '%pipe%' OR issue_description LIKE '%water%' OR issue_description LIKE '%leak%' THEN 'Plumbing'
        WHEN issue_description LIKE '%air%' OR issue_description LIKE '%ac%' OR issue_description LIKE '%cool%' THEN 'AC/ Cooling'
        WHEN issue_description LIKE '%furniture%' OR issue_description LIKE '%table%' OR issue_description LIKE '%chair%' THEN 'Furniture'
        WHEN issue_description LIKE '%door%' OR issue_description LIKE '%window%' OR issue_description LIKE '%lock%' THEN 'Doors/ Windows'
        ELSE 'General'
    END as problem_type,
    COUNT(*) as count
    FROM reports
    WHERE assigned_to = $tech_id AND status = 'Completed'";
    
// Add date condition if it exists
if (!empty($date_condition)) {
    $clean_date_condition = str_replace("AND r.report_date", "AND report_date", $date_condition);
    $problem_query .= " " . $clean_date_condition;
}

$problem_query .= " GROUP BY problem_type ORDER BY count DESC";

$problem_result = mysqli_query($conn, $problem_query);
$problem_types = [];
while($row = mysqli_fetch_assoc($problem_result)) {
    $problem_types[] = $row;
}

// 4. Get facilities list for filter
$facilities_query = "SELECT DISTINCT f.facility_id, f.facility_name 
                     FROM reports r
                     JOIN facilities f ON r.facility_id = f.facility_id
                     WHERE r.assigned_to = $tech_id AND r.status = 'Completed'
                     ORDER BY f.facility_name";
$facilities_result = mysqli_query($conn, $facilities_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Tasks - Technician</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
       /* ====== SIDEBAR ====== */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, var(--sidebar-bg) 0%, #0d2342 100%);
            color: white;
            padding: 30px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
        }
        .sidebar-header {
            padding: 0 30px 30px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 25px;
        }
        .sidebar-header h2 {
            font-size: 28px;
            font-weight: 800;
            color: white;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        .sidebar-header p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 20px;
        }
        .technician-profile-sidebar {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 15px;
            border-radius: 12px;
            margin-top: 15px;
        }
        .technician-avatar-sidebar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
        }
        .technician-text-sidebar h4 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .technician-text-sidebar p {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
            margin: 0;
        }
        .sidebar nav ul {
            list-style: none;
            padding: 0 20px;
        }
        .sidebar nav li {
            margin-bottom: 8px;
        }
        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 20px;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s;
            font-weight: 500;
            font-size: 15px;
        }
        .sidebar nav a:hover {
            background: var(--sidebar-hover);
            color: white;
            transform: translateX(5px);
        }
        .sidebar nav a.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .sidebar nav a i {
            width: 22px;
            text-align: center;
            font-size: 18px;
        }
        
        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
        }
        
        /* HEADER */
        .page-header {
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
        
        .filter-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filter-select {
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            color: var(--dark);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        /* PERFORMANCE ANALYTICS - MOVED HERE */
        .analytics-container {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 1200px) {
            .analytics-container {
                grid-template-columns: 1fr;
            }
        }
        
        .analytics-panel {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            height: fit-content;
        }
        
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .panel-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
        }
        
        /* METRICS GRID */
        .metrics-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .metric-card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .metric-label {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* CHART CONTAINER */
        .chart-container {
            margin: 20px 0;
            padding: 15px;
            background: #f8fafc;
            border-radius: 10px;
            height: 280px;
            position: relative;
        }
        
        .chart-title {
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            color: var(--gray);
            margin-bottom: 15px;
        }
        
        /* PROBLEM TYPES */
        .problem-types {
            margin-top: 20px;
        }
        
        .problem-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .problem-item:last-child {
            border-bottom: none;
        }
        
        .problem-name {
            font-weight: 500;
            color: var(--dark);
        }
        
        .problem-stats {
            font-size: 13px;
            color: var(--gray);
        }
        
        /* TASKS LIST SECTION */
        .tasks-section {
            margin-top: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            gap: 20px;
        }
        
        .tasks-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .task-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--secondary);
            transition: all 0.3s;
        }
        
        .task-card:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .task-info {
            flex: 1;
        }
        
        .task-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .task-id {
            color: var(--gray);
            font-size: 14px;
        }
        
        .task-time {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
            font-size: 13px;
            color: var(--gray);
        }
        
        .time-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .time-quick { background: #d1fae5; color: #065f46; }
        .time-medium { background: #dbeafe; color: #1e40af; }
        .time-long { background: #fef3c7; color: #92400e; }
        
        .task-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        /* BUTTONS */
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: var(--dark);
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        /* PHOTO PREVIEW */
        .photo-preview {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .photo-preview:hover {
            transform: scale(1.1);
            border-color: var(--primary);
        }
        
        /* PAGINATION */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .page-link {
            padding: 8px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .page-link:hover {
            border-color: var(--primary);
            background: #f0f9ff;
        }
        
        .page-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .empty-state i {
            font-size: 48px;
            color: var(--gray);
            opacity: 0.5;
            margin-bottom: 20px;
        }
        
        /* INSIGHTS SECTION */
        .insights-section {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-radius: 12px;
            padding: 20px;
            margin-top: 25px;
            border-left: 4px solid var(--primary);
        }
        
        .insights-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .insight-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 14px;
            color: var(--dark);
        }
        
        .insight-item i {
            color: var(--secondary);
        }
        
        /* DATE FILTER BADGE */
        .date-filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: #f0f9ff;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
            margin-left: 10px;
        }
        
        /* ANALYTICS TITLE WITH FILTER */
        .analytics-title-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .analytics-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
        }
        
        /* CHART TOOLTIP STYLING */
        .chart-tooltip {
            background: rgba(0, 0, 0, 0.8) !important;
            border-radius: 6px !important;
            padding: 10px !important;
            font-size: 12px !important;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>HFRS Technician</h2>
            <p>Control Panel</p>
        <div class="technician-profile-sidebar">
            <div class="technician-avatar-sidebar">
                <?php echo strtoupper(substr($tech_name, 0, 1)); ?>
            </div>
            <div class="technician-text-sidebar">
                <h4><?php echo htmlspecialchars($tech_name); ?></h4>
            <span class="tech-role">Technician</span>
            </div>
        </div>
        
        </div>
         <nav class="sidebar-menu">
            <ul>
                <li><a href="technician_profiles.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="mytasktech.php"><i class="fas fa-tasks"></i>My Tasks</a></li>
                <li><a href="complete_tech.php" class="active"><i class="fas fa-check-circle"></i>Completed</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- Header -->
        <div class="page-header">
            <h1 class="page-title">Completed Tasks</h1>
            <p class="page-subtitle">Review your completed work</p>
        </div>

        <!-- PERFORMANCE ANALYTICS SECTION -->
        <div class="analytics-title-container">
            <h2 class="analytics-title">Performance Analytics</h2>
            <form method="GET" style="display: flex; align-items: center; gap: 10px;">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <label class="filter-label" style="margin: 0; font-size: 13px;">Date Range:</label>
                <select name="date" class="filter-select" onchange="this.form.submit()" style="min-width: 150px;">
                    <option value="all" <?php echo $date_filter == 'all' ? 'selected' : ''; ?>>All Time</option>
                    <option value="this_week" <?php echo $date_filter == 'this_week' ? 'selected' : ''; ?>>This Week</option>
                    <option value="this_month" <?php echo $date_filter == 'this_month' ? 'selected' : ''; ?>>This Month</option>
                    <option value="last_month" <?php echo $date_filter == 'last_month' ? 'selected' : ''; ?>>Last Month</option>
                </select>
            </form>
        </div>

        <div class="analytics-container">
            <!-- Left: Performance Metrics -->
            <div class="analytics-panel">
                <div class="panel-header">
                    <h2 class="panel-title">Performance Overview</h2>
                    <span style="font-size: 12px; color: var(--gray);">
                        <?php echo $analytics_range; ?>
                    </span>
                </div>
                
                <?php if ($metrics['total_completed'] > 0): ?>
                <!-- Metrics Grid -->
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-value"><?php echo $metrics['total_completed']; ?></div>
                        <div class="metric-label">Total Completed</div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-value"><?php echo $metrics['unique_facilities']; ?></div>
                        <div class="metric-label">Facilities</div>
                    </div>
                </div>
                
                <!-- Dynamic Chart - ALWAYS BAR CHART -->
                <?php if (isset($chart_labels) && !empty($chart_labels)): ?>
                <div class="chart-container">
                    <div class="chart-title"><?php echo isset($chart_title) ? $chart_title : "Tasks Completed"; ?></div>
                    <canvas id="dynamicChart" height="200"></canvas>
                </div>
                
                <div style="text-align: center; margin-top: 10px; font-size: 12px; color: var(--gray);">
                    <i class="fas fa-chart-bar"></i> Bar chart showing tasks distribution
                </div>
                <?php endif; ?>
                
                <!-- Problem Type Analysis -->
                <?php if (!empty($problem_types)): ?>
                <div style="margin-top: 25px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: var(--dark); margin-bottom: 15px;">
                        Problem Type Analysis
                    </h3>
                    <div class="problem-types">
                        <?php foreach($problem_types as $problem): ?>
                        <div class="problem-item">
                            <span class="problem-name"><?php echo $problem['problem_type']; ?></span>
                            <span class="problem-stats">
                                <?php echo $problem['count']; ?> tasks
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Performance Insights -->
                <?php if ($metrics['total_completed'] >= 5): ?>
                <div class="insights-section">
                    <div class="insights-title">
                        <i class="fas fa-lightbulb"></i> Performance Insights
                    </div>
                    
                    <div class="insight-item">
                        <i class="fas fa-bolt"></i>
                        You have completed <strong><?php echo $metrics['total_completed']; ?> tasks</strong>
                    </div>
                    
                    <?php if ($metrics['unique_facilities'] >= 3): ?>
                    <div class="insight-item">
                        <i class="fas fa-building"></i>
                        You've worked at <strong><?php echo $metrics['unique_facilities']; ?> different facilities</strong>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($problem_types) && $problem_types[0]['count'] >= 3): ?>
                    <div class="insight-item">
                        <i class="fas fa-tools"></i>
                        Most common: <strong><?php echo $problem_types[0]['problem_type']; ?></strong> 
                        (<?php echo $problem_types[0]['count']; ?> tasks)
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($metrics['total_completed'] >= 10): ?>
                    <div class="insight-item">
                        <i class="fas fa-trophy"></i>
                        Great work! You've completed <strong><?php echo $metrics['total_completed']; ?> tasks</strong> successfully
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div style="text-align: center; padding: 40px 20px; color: var(--gray);">
                    <i class="fas fa-chart-line" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                    <h3>No data yet</h3>
                    <p>Complete some tasks to see your analytics here</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Right: Tasks Statistics -->
            <div class="analytics-panel">
                <div class="panel-header">
                    <h2 class="panel-title">Tasks Summary</h2>
                </div>
                
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 48px; font-weight: 800; color: var(--primary); margin-bottom: 10px;">
                        <?php echo $total_tasks; ?>
                    </div>
                    <div style="font-size: 14px; color: var(--gray); margin-bottom: 25px;">
                        Total Tasks in <?php echo $analytics_range; ?>
                    </div>
                    
                    <div style="text-align: left; margin-top: 25px;">
                        <h4 style="font-size: 14px; font-weight: 600; color: var(--dark); margin-bottom: 15px;">
                            <i class="fas fa-filter"></i> Current Filters
                        </h4>
                        <div style="background: #f8fafc; padding: 15px; border-radius: 8px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span style="font-size: 13px; color: var(--gray);">Date Range:</span>
                                <span style="font-size: 13px; font-weight: 600; color: var(--dark);">
                                    <?php echo $analytics_range; ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($search)): ?>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span style="font-size: 13px; color: var(--gray);">Search:</span>
                                <span style="font-size: 13px; font-weight: 600; color: var(--dark);">
                                    "<?php echo htmlspecialchars($search); ?>"
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-size: 13px; color: var(--gray);">Page:</span>
                                <span style="font-size: 13px; font-weight: 600; color: var(--dark);">
                                    <?php echo $page; ?> of <?php echo $total_pages; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($total_tasks > 0): ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- TASKS LIST SECTION -->
        <div class="tasks-section">
            <div class="section-header">
                <div style="flex: 1;">
                    <h3 style="font-size: 18px; font-weight: 700; color: var(--dark); margin-bottom: 15px;">
                        Completed Tasks List
                        <?php if ($total_tasks > 0): ?>
                        <span style="font-size: 14px; font-weight: 400; color: var(--gray); margin-left: 10px;">
                            (<?php echo $total_tasks; ?> tasks found)
                        </span>
                        <?php endif; ?>
                    </h3>
                    
                    <!-- Search Box - Moved Here -->
                    <form method="GET" class="search-box" style="margin-top: 10px;">
                        <input type="hidden" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                        <input type="text" name="search" class="search-input" placeholder="Search tasks, notes, facilities..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if ($search): ?>
                        <a href="?date=<?php echo urlencode($date_filter); ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <?php if ($total_tasks > 0): ?>
                <div style="font-size: 14px; color: var(--gray); text-align: right;">
                    Showing <?php echo min($items_per_page, $total_tasks - $offset); ?> of <?php echo $total_tasks; ?> tasks
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Tasks List -->
            <div class="tasks-list">
                <?php if (mysqli_num_rows($tasks_result) > 0): ?>
                    <?php while($task = mysqli_fetch_assoc($tasks_result)): ?>
                    <div class="task-card">
                        <div class="task-header">
                            <div class="task-info">
                                <div class="task-title">
                                    <span class="task-id">#<?php echo $task['report_id']; ?></span>
                                    <?php echo htmlspecialchars($task['facility_name']); ?>
                                </div>
                                
                                <p style="font-size: 14px; color: var(--dark); margin: 10px 0;">
                                    <?php echo htmlspecialchars($task['issue_description']); ?>
                                </p>
                                
                                <div class="task-time">
                                    <span>
                                        <i class="far fa-calendar-check"></i>
                                        <?php echo date('d M Y, h:i A', strtotime($task['report_date'])); ?>
                                    </span>
                                    <?php if ($task['photo_count'] > 0): ?>
                                    <span>
                                        <i class="fas fa-camera"></i> <?php echo $task['photo_count']; ?> photos
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($task['progress_notes'])): ?>
                                <div style="margin-top: 10px; padding: 10px; background: #f8fafc; border-radius: 8px;">
                                    <div style="font-size: 12px; color: var(--gray); margin-bottom: 5px;">Notes:</div>
                                    <div style="font-size: 14px; color: var(--dark);">
                                        <?php echo nl2br(htmlspecialchars(substr($task['progress_notes'], 0, 200))); ?>
                                        <?php if (strlen($task['progress_notes']) > 200): ?>...<?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($task['sample_photo'])): ?>
                            <img src="<?php echo htmlspecialchars($task['sample_photo']); ?>" 
                                 alt="Completed work" 
                                 class="photo-preview"
                                 onclick="viewPhoto('<?php echo htmlspecialchars($task['sample_photo']); ?>')">
                            <?php endif; ?>
                        </div>
                        
                        <div class="task-actions">
                            <a href="tech_view_details.php?id=<?php echo $task['report_id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <?php if ($task['photo_count'] > 0): ?>
                            <a href="tech_view_photos.php?id=<?php echo $task['report_id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-images"></i> View Photos
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-check"></i>
                        <h3>No completed tasks found</h3>
                        <p><?php echo $search ? 'Try a different search term' : 'Complete some tasks first to see them here!'; ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&date=<?php echo $date_filter; ?>&search=<?php echo urlencode($search); ?>" 
                   class="page-link">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="page-link active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&date=<?php echo $date_filter; ?>&search=<?php echo urlencode($search); ?>" 
                           class="page-link"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&date=<?php echo $date_filter; ?>&search=<?php echo urlencode($search); ?>" 
                   class="page-link">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Photo Viewer Modal -->
    <div id="photoModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 2000; justify-content: center; align-items: center;">
        <div style="position: relative; max-width: 90%; max-height: 90%;">
            <img id="modalPhoto" src="" alt="Full size" style="max-width: 100%; max-height: 80vh; border-radius: 8px;">
            <button onclick="closePhoto()" style="position: absolute; top: -40px; right: 0; background: none; border: none; color: white; font-size: 24px; cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <script>
        // Dynamic Chart - ALWAYS BAR CHART FOR CONSISTENCY
        <?php if (isset($chart_labels) && !empty($chart_labels) && isset($chart_data)): ?>
        const chartCtx = document.getElementById('dynamicChart').getContext('2d');
        const dynamicChart = new Chart(chartCtx, {
            type: 'bar', // Always use bar chart
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Tasks Completed',
                    data: <?php echo json_encode($chart_data); ?>,
                    backgroundColor: function(context) {
                        const index = context.dataIndex;
                        const value = context.dataset.data[index];
                        return value > 0 ? 'rgba(37, 99, 235, 0.7)' : 'rgba(209, 213, 219, 0.5)';
                    },
                    borderColor: function(context) {
                        const index = context.dataIndex;
                        const value = context.dataset.data[index];
                        return value > 0 ? 'rgba(37, 99, 235, 1)' : 'rgba(209, 213, 219, 0.8)';
                    },
                    borderWidth: 2,
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            size: 13
                        },
                        bodyFont: {
                            size: 12
                        },
                        padding: 10,
                        callbacks: {
                            label: function(context) {
                                return 'Tasks: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        },
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        title: {
                            display: true,
                            text: 'Number of Tasks',
                            font: {
                                size: 12
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: <?php echo ($date_filter == 'this_month' || $date_filter == 'last_month') ? '45' : '0'; ?>,
                            font: {
                                size: 11
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });
        
        // Adjust chart height for weekly labels
        <?php if ($date_filter == 'this_month' || $date_filter == 'last_month'): ?>
        // Make chart container taller for weekly labels
        document.querySelector('.chart-container').style.height = '300px';
        <?php endif; ?>
        <?php endif; ?>
        
        // Photo Viewer
        function viewPhoto(photoUrl) {
            document.getElementById('modalPhoto').src = photoUrl;
            document.getElementById('photoModal').style.display = 'flex';
        }
        
        function closePhoto() {
            document.getElementById('photoModal').style.display = 'none';
        }
        
        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePhoto();
            }
        });
        
        // Auto-refresh page when date filter changes (for better UX)
        const dateSelect = document.querySelector('select[name="date"]');
        if (dateSelect) {
            dateSelect.addEventListener('change', function() {
                // Submit form immediately
                this.form.submit();
            });
        }
        
        // Add hover effect to chart bars
        document.addEventListener('DOMContentLoaded', function() {
            const chartCanvas = document.getElementById('dynamicChart');
            if (chartCanvas) {
                chartCanvas.addEventListener('mouseover', function(e) {
                    const bars = this.querySelectorAll('.chart-bar');
                    bars.forEach(bar => {
                        bar.style.transition = 'all 0.3s';
                    });
                });
            }
        });
    </script>
</body>
</html>