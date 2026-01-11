<?php
include('db_connect.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login first!'); window.location='login.php';</script>";
    exit();
}

// Initialize variables
$user_data = [];
$facilities = [];
$hostel_blocks = ['Tuah', 'Jebat', 'Lestari', 'Lekiu', 'Kasturi', 'Al Jazari'];
$priority_levels = [
    'Low' => 'Minor issue, non-urgent',
    'Medium' => 'Important but not emergency',
    'High' => 'Affects daily activities',
    'Urgent' => 'Safety hazard or critical issue'
];

try {
    // Get user info - using a safer approach
    $user_id = $_SESSION['user_id'];
    
    // First, let's see what columns exist in users table
    $check_columns = mysqli_query($conn, "SHOW COLUMNS FROM users");
    $existing_columns = [];
    while($col = mysqli_fetch_assoc($check_columns)) {
        $existing_columns[] = $col['Field'];
    }
    
    // Build query based on available columns
    $columns_to_select = ['user_id'];
    
    if (in_array('full_name', $existing_columns)) {
        $columns_to_select[] = 'full_name';
    }
    if (in_array('email', $existing_columns)) {
        $columns_to_select[] = 'email';
    }
    if (in_array('hostel_block', $existing_columns)) {
        $columns_to_select[] = 'hostel_block';
    }
    if (in_array('room_no', $existing_columns)) {
        $columns_to_select[] = 'room_no';
    }
    
    $columns_str = implode(', ', $columns_to_select);
    $user_query = mysqli_query($conn, "SELECT $columns_str FROM users WHERE user_id = '$user_id'");
    
    if ($user_query && mysqli_num_rows($user_query) > 0) {
        $user_data = mysqli_fetch_assoc($user_query);
    }

    // Get facilities list
    $facilities_query = mysqli_query($conn, "SHOW TABLES LIKE 'facilities'");
    if (mysqli_num_rows($facilities_query) > 0) {
        $facilities_result = mysqli_query($conn, "SELECT * FROM facilities ORDER BY facility_name");
        if ($facilities_result) {
            while($row = mysqli_fetch_assoc($facilities_result)) {
                $facilities[] = $row;
            }
        }
    }

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data with validation
    $issue_description = mysqli_real_escape_string($conn, $_POST['issue_description']);
    $location_details = isset($_POST['location_details']) ? mysqli_real_escape_string($conn, $_POST['location_details']) : '';
    $hostel_block = isset($_POST['hostel_block']) ? mysqli_real_escape_string($conn, $_POST['hostel_block']) : '';
    $room_no = isset($_POST['room_no']) ? mysqli_real_escape_string($conn, $_POST['room_no']) : '';
    
    // Handle facility_id - check if it exists in facilities table
    $facility_id = NULL;
    if (!empty($_POST['facility_id'])) {
        $selected_facility_id = intval($_POST['facility_id']);
        
        // Check if the facility exists in the facilities table
        $check_facility = mysqli_query($conn, "SELECT facility_id FROM facilities WHERE facility_id = $selected_facility_id");
        if (mysqli_num_rows($check_facility) > 0) {
            $facility_id = $selected_facility_id;
        } else {
            // Facility doesn't exist, we'll insert without facility_id
            $facility_id = NULL;
        }
    }

    // Check if priority column exists in reports table
    $check_priority = mysqli_query($conn, "SHOW COLUMNS FROM reports LIKE 'priority'");
    $priority = 'Medium';
    if (mysqli_num_rows($check_priority) > 0 && isset($_POST['priority'])) {
        $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    }

    // Generate simple report number
    $report_no = 'RPT' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    $image_path = "";
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            if ($_FILES['image']['size'] <= 5 * 1024 * 1024) { // 5MB limit
                $target_file = $target_dir . 'report_' . time() . '_' . uniqid() . '.' . $file_extension;
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image_path = $target_file;
                }
            }
        }
    }

    // Check what columns exist in reports table
    $check_reports_columns = mysqli_query($conn, "SHOW COLUMNS FROM reports");
    $reports_columns = [];
    while($col = mysqli_fetch_assoc($check_reports_columns)) {
        $reports_columns[] = $col['Field'];
    }

    // Build the INSERT query dynamically based on existing columns
    $sql_columns = [];
    $sql_values = [];

    // Always include these basic columns
    $sql_columns[] = "user_id";
    $sql_values[] = "'$user_id'";

    // Only add facility_id if it exists and is valid
    if ($facility_id !== NULL) {
        $sql_columns[] = "facility_id";
        $sql_values[] = "$facility_id";
    }

    $sql_columns[] = "issue_description";
    $sql_values[] = "'$issue_description'";

    // Use progress_noises for location details if it exists
    if (in_array('progress_noises', $reports_columns)) {
        $sql_columns[] = "progress_noises";
        $sql_values[] = "'$location_details'";
    }

    // Add priority if column exists
    if (in_array('priority', $reports_columns)) {
        $sql_columns[] = "priority";
        $sql_values[] = "'$priority'";
    }

    // Add hostel_block if column exists
    if (in_array('hostel_block', $reports_columns) && !empty($hostel_block)) {
        $sql_columns[] = "hostel_block";
        $sql_values[] = "'$hostel_block'";
    }

    // Add room_no if column exists
    if (in_array('room_no', $reports_columns) && !empty($room_no)) {
        $sql_columns[] = "room_no";
        $sql_values[] = "'$room_no'";
    }

    // Add image_path if column exists
    if (in_array('image_path', $reports_columns)) {
        $sql_columns[] = "image_path";
        $sql_values[] = "'$image_path'";
    }

    // Add status and report_date (should exist)
    $sql_columns[] = "status";
    $sql_values[] = "'Pending'";

    $sql_columns[] = "report_date";
    $sql_values[] = "NOW()";

    // Build the final SQL query
    $columns_str = implode(', ', $sql_columns);
    $values_str = implode(', ', $sql_values);
    $sql = "INSERT INTO reports ($columns_str) VALUES ($values_str)";

    if (mysqli_query($conn, $sql)) {
        $last_id = mysqli_insert_id($conn);
        echo "<script>
            alert('Report #$report_no submitted successfully!');
            window.location='myreport.php';
        </script>";
    } else {
        $error_msg = addslashes(mysqli_error($conn));
        echo "<script>
            alert('Failed to submit report. Error: $error_msg');
            console.error('SQL Error: ', '$error_msg');
            console.error('SQL Query: ', '$sql');
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Report Issue - HFRS</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* ===== GLOBAL STYLES ===== */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background: #f4f6fa;
      color: #333;
    }

    /* ===== NAVBAR ===== */
    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 60px;
      background: #0a1930;
      color: white;
      position: sticky;
      top: 0;
      z-index: 1000;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .navbar .logo {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .navbar img {
      height: 45px;
    }

    .navbar h1 {
      font-size: 22px;
      font-weight: 600;
    }

    .navbar nav ul {
      display: flex;
      list-style: none;
      gap: 30px;
      margin: 0;
    }

    .navbar a {
      color: white;
      text-decoration: none;
      font-weight: 500;
      font-size: 16px;
      transition: color 0.3s;
    }

    .navbar a:hover,
    .navbar a.active {
      color: #00a8ff;
    }

    /* ===== REPORT PAGE HEADER ===== */
    .report-header {
      background: linear-gradient(135deg, #0a1930 0%, #1a3a5f 100%);
      color: white;
      padding: 40px 60px;
      text-align: center;
    }

    .report-header h1 {
      font-size: 36px;
      margin-bottom: 10px;
      font-weight: 700;
    }

    .report-header p {
      opacity: 0.9;
      font-size: 18px;
      max-width: 700px;
      margin: 0 auto;
    }

    /* ===== REPORT FORM CONTAINER ===== */
    .report-container {
      max-width: 900px;
      margin: 40px auto;
      padding: 0 20px;
    }

    /* ===== FORM STEPS ===== */
    .form-steps {
      display: flex;
      justify-content: center;
      gap: 30px;
      margin-bottom: 40px;
    }

    .step {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      opacity: 0.5;
      transition: all 0.3s;
    }

    .step.active {
      opacity: 1;
    }

    .step-number {
      width: 40px;
      height: 40px;
      background: #0a1930;
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
    }

    .step.active .step-number {
      background: #00a8ff;
    }

    .step-label {
      font-size: 14px;
      font-weight: 500;
      color: #0a1930;
    }

    /* ===== FORM SECTIONS ===== */
    .form-section {
      background: white;
      border-radius: 12px;
      padding: 40px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.08);
      margin-bottom: 30px;
      border-left: 4px solid #00a8ff;
    }

    .section-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 25px;
    }

    .section-icon {
      width: 50px;
      height: 50px;
      background: #e8f4fc;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .section-icon i {
      font-size: 22px;
      color: #00a8ff;
    }

    .section-title {
      font-size: 22px;
      color: #0a1930;
      font-weight: 600;
    }

    /* ===== FORM GRID ===== */
    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }

    @media (max-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr;
      }
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group.full-width {
      grid-column: 1 / -1;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 8px;
      color: #0a1930;
      font-size: 15px;
    }

    .form-group label .required {
      color: #ff4757;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid #e1e5e9;
      border-radius: 8px;
      font-size: 15px;
      transition: all 0.3s;
      background: white;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      border-color: #00a8ff;
      box-shadow: 0 0 0 3px rgba(0,168,255,0.1);
      outline: none;
    }

    .form-group select {
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%230a1930' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 16px center;
      background-size: 16px;
    }

    .form-group textarea {
      resize: vertical;
      min-height: 120px;
    }

    /* ===== FILE UPLOAD ===== */
    .file-upload {
      position: relative;
      border: 2px dashed #00a8ff;
      border-radius: 8px;
      padding: 30px;
      text-align: center;
      background: #f8fcff;
      cursor: pointer;
      transition: all 0.3s;
    }

    .file-upload:hover {
      background: #e8f4fc;
      border-color: #0170b5;
    }

    .file-upload i {
      font-size: 48px;
      color: #00a8ff;
      margin-bottom: 15px;
    }

    .file-upload p {
      margin-bottom: 10px;
      color: #0a1930;
      font-weight: 500;
    }

    .file-upload .file-info {
      font-size: 14px;
      color: #666;
      margin-top: 10px;
    }

    .file-upload input[type="file"] {
      position: absolute;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      opacity: 0;
      cursor: pointer;
    }

    .preview-container {
      margin-top: 20px;
      display: none;
    }

    .image-preview {
      max-width: 200px;
      border-radius: 8px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }

    /* ===== PRIORITY INDICATORS ===== */
    .priority-options {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 10px;
    }

    @media (max-width: 768px) {
      .priority-options {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    .priority-option {
      padding: 15px;
      border: 2px solid #e1e5e9;
      border-radius: 8px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
      background: white;
    }

    .priority-option:hover {
      border-color: #00a8ff;
      transform: translateY(-2px);
    }

    .priority-option.selected {
      border-color: #00a8ff;
      background: #e8f4fc;
    }

    .priority-icon {
      font-size: 20px;
      margin-bottom: 8px;
    }

    .priority-low .priority-icon {
      color: #2ed573;
    }

    .priority-medium .priority-icon {
      color: #ffa502;
    }

    .priority-high .priority-icon {
      color: #ff6348;
    }

    .priority-urgent .priority-icon {
      color: #ff4757;
    }

    .priority-label {
      font-weight: 600;
      margin-bottom: 5px;
    }

    .priority-desc {
      font-size: 12px;
      color: #666;
    }

    .priority-option input[type="radio"] {
      display: none;
    }

    /* ===== FORM ACTIONS ===== */
    .form-actions {
      display: flex;
      justify-content: space-between;
      gap: 20px;
      margin-top: 40px;
    }

    .btn {
      padding: 14px 32px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      border: none;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      text-decoration: none;
    }

    .btn-primary {
      background: #00a8ff;
      color: white;
    }

    .btn-primary:hover {
      background: #0170b5;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0,168,255,0.3);
    }

    .btn-secondary {
      background: #f1f2f6;
      color: #0a1930;
      border: 2px solid #e1e5e9;
    }

    .btn-secondary:hover {
      background: #dfe4ea;
      border-color: #00a8ff;
    }

    /* ===== TIPS SECTION ===== */
    .tips-section {
      background: #e8f4fc;
      border-radius: 12px;
      padding: 25px;
      margin-top: 30px;
      border-left: 4px solid #00a8ff;
    }

    .tips-section h4 {
      display: flex;
      align-items: center;
      gap: 10px;
      color: #0a1930;
      margin-bottom: 15px;
      font-size: 18px;
    }

    .tips-section h4 i {
      color: #00a8ff;
    }

    .tips-list {
      list-style: none;
      padding-left: 0;
    }

    .tips-list li {
      padding: 8px 0;
      color: #555;
      display: flex;
      align-items: flex-start;
      gap: 10px;
    }

    .tips-list li i {
      color: #00a8ff;
      margin-top: 3px;
      font-size: 14px;
    }

    /* ===== FOOTER ===== */
    footer {
      background: #0a1930;
      color: white;
      text-align: center;
      padding: 25px;
      margin-top: 60px;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
      .navbar {
        padding: 15px 20px;
        flex-direction: column;
        gap: 15px;
      }
      
      .report-header {
        padding: 30px 20px;
      }
      
      .report-header h1 {
        font-size: 28px;
      }
      
      .form-section {
        padding: 25px;
      }
      
      .form-steps {
        gap: 15px;
        flex-wrap: wrap;
      }
      
      .step-label {
        font-size: 12px;
        text-align: center;
      }
      
      .form-actions {
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
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
        <li><a href="report.php" class="active"><i class="fas fa-plus-circle"></i> Report Issue</a></li>
        <li><a href="myreport.php"><i class="fas fa-list-alt"></i> My Reports</a></li>
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </nav>
  </header>

  <!-- Report Header -->
  <section class="report-header">
    <h1>Report a Facility Issue</h1>
    <p>Fill out the form below to report maintenance issues. Your report will be reviewed and assigned to our technical team.</p>
  </section>

  <!-- Form Steps -->
  <div class="report-container">
    <div class="form-steps">
      <div class="step active">
        <div class="step-number">1</div>
        <div class="step-label">Report Details</div>
      </div>
      <div class="step">
        <div class="step-number">2</div>
        <div class="step-label">Location Info</div>
      </div>
      <div class="step">
        <div class="step-number">3</div>
        <div class="step-label">Submit Report</div>
      </div>
    </div>

    <!-- Report Form -->
    <form method="POST" action="report.php" enctype="multipart/form-data" id="reportForm">
      <!-- Section 1: Report Details -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon">
            <i class="fas fa-clipboard-list"></i>
          </div>
          <h3 class="section-title">Issue Details</h3>
        </div>

        <div class="form-grid">
          <!-- Facility Selection - Made optional -->
          <div class="form-group full-width">
            <label>Select Facility Type (Optional)</label>
            <select name="facility_id">
              <option value="">-- Optional: Choose facility type --</option>
              <?php if (!empty($facilities)): ?>
                <?php foreach($facilities as $facility): ?>
                  <option value="<?php echo $facility['facility_id']; ?>">
                    <?php echo htmlspecialchars($facility['facility_name']); ?>
                  </option>
                <?php endforeach; ?>
              <?php else: ?>
                <!-- If no facilities in database, user can leave it empty -->
                <option value="">No facility types available</option>
              <?php endif; ?>
            </select>
            <small style="color: #666; font-size: 13px; display: block; margin-top: 5px;">
              If you don't see your facility type, leave this blank and describe it below.
            </small>
          </div>

          <!-- Issue Description -->
          <div class="form-group full-width">
            <label>Issue Description <span class="required">*</span></label>
            <textarea name="issue_description" placeholder="Describe the issue in detail. Be specific about what's wrong, when it started, and how it affects you..." required></textarea>
          </div>
        </div>
      </div>

      <!-- Section 2: Location Information -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon">
            <i class="fas fa-map-marker-alt"></i>
          </div>
          <h3 class="section-title">Location Information</h3>
        </div>

        <div class="form-grid">
          <!-- Hostel Block -->
          <div class="form-group">
            <label>Hostel Block <span class="required">*</span></label>
            <select name="hostel_block" required>
              <option value="">-- Select block --</option>
              <?php foreach($hostel_blocks as $block): ?>
                <option value="<?php echo $block; ?>" <?php echo (isset($user_data['hostel_block']) && $user_data['hostel_block'] == $block) ? 'selected' : ''; ?>>
                  <?php echo $block; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Room Number -->
          <div class="form-group">
            <label>Room Number <span class="required">*</span></label>
            <input type="text" name="room_no" placeholder="e.g., A-2-07" value="<?php echo isset($user_data['room_no']) ? htmlspecialchars($user_data['room_no']) : ''; ?>" required>
          </div>

          <!-- Location Details -->
          <div class="form-group full-width">
            <label>Specific Location Details (Optional)</label>
            <input type="text" name="location_details" placeholder="e.g., Bathroom sink, Study table drawer, Window near bed">
          </div>
        </div>
      </div>

      <!-- Section 3: Media & Priority -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon">
            <i class="fas fa-camera"></i>
          </div>
          <h3 class="section-title">Add Photos</h3>
        </div>

        <!-- Image Upload -->
        <div class="form-group full-width">
          <label>Upload Photo (Optional but Recommended)</label>
          <div class="file-upload">
            <i class="fas fa-cloud-upload-alt"></i>
            <p>Click to upload photo</p>
            <p class="file-info">JPG, PNG, GIF up to 5MB</p>
            <input type="file" name="image" id="imageUpload" accept="image/*">
          </div>
          <div class="preview-container">
            <img id="imagePreview" class="image-preview" src="" alt="Preview">
          </div>
        </div>

        <!-- Only show priority if the column exists in database -->
        <?php
        $check_priority_col = mysqli_query($conn, "SHOW COLUMNS FROM reports LIKE 'priority'");
        if (mysqli_num_rows($check_priority_col) > 0):
        ?>
        <!-- Priority Selection -->
        <div class="form-group full-width">
          <label>Select Priority Level</label>
          <div class="priority-options">
            <?php foreach($priority_levels as $level => $desc): ?>
              <label class="priority-option priority-<?php echo strtolower($level); ?>">
                <input type="radio" name="priority" value="<?php echo $level; ?>" <?php echo $level === 'Medium' ? 'checked' : ''; ?>>
                <div class="priority-icon">
                  <?php 
                    $icons = [
                      'Low' => 'fas fa-info-circle',
                      'Medium' => 'fas fa-exclamation-circle',
                      'High' => 'fas fa-exclamation-triangle',
                      'Urgent' => 'fas fa-skull-crossbones'
                    ];
                    echo '<i class="' . $icons[$level] . '"></i>';
                  ?>
                </div>
                <div class="priority-label"><?php echo $level; ?></div>
                <div class="priority-desc"><?php echo $desc; ?></div>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Tips Section -->
      <div class="tips-section">
        <h4><i class="fas fa-lightbulb"></i> Tips for better reporting:</h4>
        <ul class="tips-list">
          <li><i class="fas fa-check-circle"></i> Be specific about the location and issue details</li>
          <li><i class="fas fa-check-circle"></i> Take clear photos from multiple angles if possible</li>
          <li><i class="fas fa-check-circle"></i> Mention how long the issue has been occurring</li>
          <li><i class="fas fa-check-circle"></i> Emergency/safety issues should be marked as "Urgent"</li>
        </ul>
      </div>

      <!-- Form Actions -->
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">
          <i class="fas fa-arrow-left"></i> Back to Home
        </button>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-paper-plane"></i> Submit Report
        </button>
      </div>
    </form>
  </div>

  <footer>
    <p>© 2025 Hostel Facilities Report System | Developed for UTeM</p>
  </footer>

  <script>
    // Image preview functionality
    document.getElementById('imageUpload').addEventListener('change', function(e) {
      const file = e.target.files[0];
      const previewContainer = document.querySelector('.preview-container');
      const previewImage = document.getElementById('imagePreview');
      
      if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
          previewImage.src = e.target.result;
          previewContainer.style.display = 'block';
        }
        
        reader.readAsDataURL(file);
      } else {
        previewContainer.style.display = 'none';
      }
    });

    // Priority selection styling
    document.querySelectorAll('.priority-option').forEach(option => {
      option.addEventListener('click', function() {
        // Remove selected class from all options
        document.querySelectorAll('.priority-option').forEach(opt => {
          opt.classList.remove('selected');
        });
        
        // Add selected class to clicked option
        this.classList.add('selected');
        
        // Check the radio button
        const radio = this.querySelector('input[type="radio"]');
        radio.checked = true;
      });
    });

    // Initialize priority selection
    document.querySelectorAll('.priority-option').forEach(option => {
      const radio = option.querySelector('input[type="radio"]');
      if (radio && radio.checked) {
        option.classList.add('selected');
      }
    });

    // Form validation
    document.getElementById('reportForm').addEventListener('submit', function(e) {
      const issueDesc = document.querySelector('textarea[name="issue_description"]');
      const hostelBlock = document.querySelector('select[name="hostel_block"]');
      const roomNo = document.querySelector('input[name="room_no"]');
      
      // Basic validation
      if (hostelBlock.value === '') {
        e.preventDefault();
        alert('Please select your hostel block.');
        hostelBlock.focus();
        return;
      }
      
      if (roomNo.value.trim() === '') {
        e.preventDefault();
        alert('Please enter your room number.');
        roomNo.focus();
        return;
      }
      
      if (issueDesc.value.trim().length < 10) {
        e.preventDefault();
        alert('Please provide a more detailed description of the issue (at least 10 characters).');
        issueDesc.focus();
        return;
      }
    });

    // Step indicator based on scroll
    window.addEventListener('scroll', function() {
      const steps = document.querySelectorAll('.step');
      const sections = document.querySelectorAll('.form-section');
      const scrollPosition = window.scrollY + 200;
      
      sections.forEach((section, index) => {
        const sectionTop = section.offsetTop;
        const sectionBottom = sectionTop + section.offsetHeight;
        
        if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
          steps.forEach(step => step.classList.remove('active'));
          steps[index].classList.add('active');
        }
      });
    });
  </script>
</body>
</html>