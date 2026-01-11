<?php
session_start();
include('db_connect.php');

// Only Admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Handle search + filter
$search = $_GET['search'] ?? "";
$status = $_GET['status'] ?? "";

// Get total counts
$total_students = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as count FROM users WHERE role = 'User'"))['count'];
$active_students = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as count FROM users WHERE role = 'User' AND status = 'Active'"))['count'];
$recent_registrations = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as count FROM users WHERE role = 'User' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"))['count'];

// Build query for students
$query = "SELECT user_id, full_name, email, status, phone, created_at, hostel_block, room_no FROM users WHERE role = 'User'";
if (!empty($search)) {
    $query .= " AND (full_name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
}
if (!empty($status) && $status !== 'all') {
    $query .= " AND status = '$status'";
}
$query .= " ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - HFRS Admin</title>
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
        /* ====== UPDATED SIDEBAR ====== */
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
        .admin-profile-sidebar {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 15px;
            border-radius: 12px;
            margin-top: 15px;
        }
        .admin-avatar-sidebar {
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
        .admin-text-sidebar h4 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .admin-text-sidebar p {
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
            padding: 40px;
            padding-bottom: 80px;
        }
        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 25px;
            border-bottom: 2px solid #e5e7eb;
        }
        .page-title {
            font-size: 36px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 10px;
        }
        .page-subtitle {
            color: var(--gray);
            font-size: 16px;
        }
        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .admin-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }
        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .stats-icon.total { background: var(--primary); }
        .stats-icon.active { background: var(--secondary); }
        .stats-icon.recent { background: #8b5cf6; }
        .stats-content h3 {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .stats-content .value {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }
        .stats-content .description {
            font-size: 14px;
            color: var(--gray);
        }
        /* FILTER SECTION */
        .filter-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .filter-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
        }
        .filter-header .results-count {
            color: var(--gray);
            font-size: 14px;
            background: #f3f4f6;
            padding: 6px 12px;
            border-radius: 20px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            align-items: end;
        }
        .search-box {
            display: flex;
            gap: 15px;
        }
        .search-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .filter-select {
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            background: white;
            color: var(--dark);
            min-width: 180px;
        }
        .filter-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
        .btn-primary {
            padding: 12px 30px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.2);
        }
        .btn-secondary {
            padding: 12px 25px;
            background: #f3f4f6;
            color: var(--dark);
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        /* TABLE */
        .table-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        .table-header {
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
        }
        .table-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: #f9fafb;
        }
        th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e5e7eb;
        }
        td {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 15px;
            vertical-align: top;
        }
        tbody tr {
            transition: background 0.2s;
        }
        tbody tr:hover {
            background: #f9fafb;
        }
        /* STATUS BADGES */
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        .status-active::before {
            content: '✓';
        }
        .status-inactive {
            background: #fef3c7;
            color: #92400e;
        }
        .status-inactive::before {
            content: '⏸️';
        }
        /* ACTION BUTTONS */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .action-btn {
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            cursor: pointer;
            white-space: nowrap;
        }
        .btn-view {
            background: #f3f4f6;
            color: var(--dark);
        }
        .btn-view:hover {
            background: #e5e7eb;
        }
        .btn-edit {
            background: #dbeafe;
            color: var(--primary);
        }
        .btn-edit:hover {
            background: #bfdbfe;
        }
        .btn-delete {
            background: #fee2e2;
            color: var(--danger);
        }
        .btn-delete:hover {
            background: #fecaca;
        }
        /* MODAL STYLES (NEW) */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
        }
        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--gray);
            cursor: pointer;
            z-index: 2;
        }
        .modal-close:hover {
            color: var(--dark);
        }
        /* Modal Student Card */
        .modal-student-card {
            background: white;
        }
        .modal-student-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f3f4f6;
        }
        .student-avatar-large {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 32px;
            flex-shrink: 0;
        }
        .modal-student-info h2 {
            font-size: 24px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 5px;
        }
        .modal-student-info p {
            color: var(--gray);
            font-size: 14px;
            margin-bottom: 3px;
        }
        .modal-student-info .student-id {
            font-size: 13px;
            color: #9ca3af;
            margin-top: 5px;
        }
        .modal-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .modal-detail-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 10px;
        }
        .modal-detail-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }
        .modal-detail-content h4 {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 5px;
        }
        .modal-detail-content p {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f3f4f6;
        }
    </style>
</head>
<body>
   <!-- ====== SIDEBAR ====== -->
    <!-- This sidebar now matches your admin_dashboard.php -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>HFRS Admin</h2>
            <p>Control Panel</p>
            <div class="admin-profile-sidebar">
                <div class="admin-avatar-sidebar">
                    <?php echo substr($_SESSION['user_name'] ?? 'A', 0, 1); ?>
                </div>
                <div class="admin-text-sidebar">
                    <h4><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></h4>
                    <p>Administrator</p>
                </div>
            </div>
        </div>
        
        <nav>
            <ul>
                <li><a href="admin_dashboard.php" ><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a></li>
                <li><a href="admin_reports.php" ><i class="fa-solid fa-file-alt"></i> <span>Manage Reports</span></a></li>
                <li><a href="admin_technicians.php"><i class="fa-solid fa-user-gear"></i> <span>Technicians</span></a></li>
                <li><a href="admin_pending_technicians.php"><i class="fa-solid fa-hourglass-half"></i> <span>Pending Registrations</span></a></li>
                <li><a href="admin_students.php" class="active"><i class="fa-solid fa-users"></i> <span>Students</span></a></li>
                <li><a href="assign_task.php"><i class="fa-solid fa-share-nodes"></i> <span>Assign Tasks</span></a></li>
                <li><a href="logout.php" style="color: #ef4444;"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li>
            </ul>
        </nav>
    </div>


    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- HEADER -->
        <div class="header">
            <div>
                <h1 class="page-title">Manage Students</h1>
                <p class="page-subtitle">View and manage all registered students</p>
            </div>
            <div class="admin-info">
                <div class="admin-avatar">A</div>
                <div>
                    <h4>Admin</h4>
                    <p>Administrator</p>
                </div>
            </div>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-grid">
            <div class="stats-card">
                <div class="stats-header">
                    <div class="stats-icon total">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stats-content">
                    <h3>Total Students</h3>
                    <div class="value"><?php echo $total_students; ?></div>
                    <p class="description">Registered in the system</p>
                </div>
            </div>
            <div class="stats-card">
                <div class="stats-header">
                    <div class="stats-icon active">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <div class="stats-content">
                    <h3>Active Students</h3>
                    <div class="value"><?php echo $active_students; ?></div>
                    <p class="description">Currently active accounts</p>
                </div>
            </div>
            <div class="stats-card">
                <div class="stats-header">
                    <div class="stats-icon recent">
                        <i class="fas fa-user-plus"></i>
                    </div>
                </div>
                <div class="stats-content">
                    <h3>Recent Registrations</h3>
                    <div class="value"><?php echo $recent_registrations; ?></div>
                    <p class="description">Last 7 days</p>
                </div>
            </div>
        </div>

        <!-- FILTER SECTION -->
        <div class="filter-card">
            <div class="filter-header">
                <h3>Filter Students</h3>
                <span class="results-count"><?php echo mysqli_num_rows($result); ?> students found</span>
            </div>
            <form method="GET" action="admin_students.php">
                <div class="filter-grid">
                    <div class="search-box">
                        <input type="text" name="search" class="search-input" 
                               placeholder="Search by name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div>
                        <select name="status" class="filter-select">
                            <option value="all" <?php echo $status === 'all' || $status === '' ? 'selected' : ''; ?>>All Status</option>
                            <option value="Active" <?php echo $status === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo $status === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="admin_students.php" class="btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <!-- STUDENTS TABLE -->
        <div class="table-container">
            <div class="table-header">
                <h3>Student List</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) == 0): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <i class="fas fa-user-slash" style="font-size: 36px; color: #9ca3af; margin-bottom: 15px; display: block;"></i>
                                <p style="color: var(--gray);">No students found. Try adjusting your filters.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['user_id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo !empty($row['phone']) ? htmlspecialchars($row['phone']) : 'N/A'; ?></td>
                            <td>
                                <?php if (!empty($row['hostel_block']) && !empty($row['room_no'])): ?>
                                    <?php echo htmlspecialchars($row['hostel_block'] . ' - Room ' . $row['room_no']); ?>
                                <?php else: ?>
                                    Not specified
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- STUDENT DETAIL MODAL -->
    <div id="studentModal" class="modal">
        <div class="modal-content">
            <button type="button" class="modal-close" onclick="closeStudentModal()">
                <i class="fas fa-times"></i>
            </button>
            <div id="modalContent">
                <!-- Modal content will be loaded by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Function to show student modal
        function showStudentModal(studentId) {
            // Show loading spinner
            document.getElementById('modalContent').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 36px; color: var(--primary); margin-bottom: 20px;"></i>
                    <p>Loading student details...</p>
                </div>
            `;
            
            // Show modal
            document.getElementById('studentModal').style.display = 'block';
            
            // Load student details using AJAX
            fetch('get_student_details.php?id=' + studentId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    document.getElementById('modalContent').innerHTML = data;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('modalContent').innerHTML = `
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-exclamation-circle" style="font-size: 36px; color: var(--danger); margin-bottom: 20px;"></i>
                            <p>Error loading student details. Please try again.</p>
                        </div>
                    `;
                });
        }
        
        // Function to close modal
        function closeStudentModal() {
            document.getElementById('studentModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('studentModal');
            if (event.target == modal) {
                closeStudentModal();
            }
        }
        
        // Close modal with ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeStudentModal();
            }
        });
    </script>
</body>
</html>