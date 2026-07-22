<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Get report parameters
$report_type = isset($_GET['type']) ? $_GET['type'] : 'overview';
$format = isset($_GET['format']) ? $_GET['format'] : 'html';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Get data based on report type
$report_data = [];

// Overall Statistics
$total_students = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student'")->fetch_assoc()['total'];
$total_quizzes = $conn->query("SELECT COUNT(*) as total FROM quiz_results")->fetch_assoc()['total'];
$avg_score = $conn->query("SELECT AVG(score/total)*100 as avg FROM quiz_results")->fetch_assoc()['avg'];
$passing_rate = $conn->query("SELECT COUNT(*) as passed FROM quiz_results WHERE (score/total)*100 >= 70")->fetch_assoc()['passed'];
$passing_percentage = $total_quizzes > 0 ? round(($passing_rate / $total_quizzes) * 100, 1) : 0;

// Module Statistics
$modules = ['earthquake', 'flood', 'fire', 'cyclone', 'drought', 'tsunami', 'lightening'];
$module_names = [
    'earthquake' => 'Earthquake Safety',
    'flood' => 'Flood Preparedness',
    'fire' => 'Fire Safety',
    'cyclone' => 'Cyclone Preparedness',
    'drought' => 'Drought Management',
    'tsunami' => 'Tsunami Safety',
    'lightening' => 'Lightning Safety'
];

$module_stats = [];
foreach ($modules as $module) {
    $completed = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM quiz_results WHERE disaster='$module'")->fetch_assoc()['count'];
    $avg = $conn->query("SELECT AVG(score/total)*100 as avg FROM quiz_results WHERE disaster='$module'")->fetch_assoc()['avg'];
    $total_attempts = $conn->query("SELECT COUNT(*) as count FROM quiz_results WHERE disaster='$module'")->fetch_assoc()['count'];
    $module_stats[$module] = [
        'name' => $module_names[$module],
        'completed' => $completed,
        'avg_score' => round($avg, 1),
        'total_attempts' => $total_attempts,
        'completion_rate' => $total_students > 0 ? round(($completed / $total_students) * 100, 1) : 0
    ];
}

// Student Performance
$students = $conn->query("
    SELECT u.id, u.name, u.email, 
           COUNT(r.id) as quizzes_taken,
           AVG(r.score/r.total)*100 as avg_score,
           SUM(CASE WHEN (r.score/r.total)*100 >= 70 THEN 1 ELSE 0 END) as passed_quizzes
    FROM users u
    LEFT JOIN quiz_results r ON u.id = r.user_id
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY avg_score DESC
");

// Recent Activity
$recent_activity = $conn->query("
    SELECT u.name, r.disaster, r.score, r.total, r.created_at
    FROM quiz_results r
    JOIN users u ON r.user_id = u.id
    WHERE DATE(r.created_at) BETWEEN '$date_from' AND '$date_to'
    ORDER BY r.created_at DESC
    LIMIT 100
");

// If CSV format, download file
if ($format == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="disaster_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, ['Disaster Ready India - Comprehensive Report']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Overall Statistics
    fputcsv($output, ['OVERALL STATISTICS']);
    fputcsv($output, ['Total Students', $total_students]);
    fputcsv($output, ['Total Quiz Attempts', $total_quizzes]);
    fputcsv($output, ['Average Score', $avg_score . '%']);
    fputcsv($output, ['Passing Rate', $passing_percentage . '%']);
    fputcsv($output, []);
    
    // Module Statistics
    fputcsv($output, ['MODULE STATISTICS']);
    fputcsv($output, ['Module', 'Students Completed', 'Completion Rate', 'Average Score', 'Total Attempts']);
    foreach ($module_stats as $stats) {
        fputcsv($output, [
            $stats['name'],
            $stats['completed'],
            $stats['completion_rate'] . '%',
            $stats['avg_score'] . '%',
            $stats['total_attempts']
        ]);
    }
    fputcsv($output, []);
    
    // Student Performance
    fputcsv($output, ['STUDENT PERFORMANCE']);
    fputcsv($output, ['Name', 'Email', 'Quizzes Taken', 'Average Score', 'Passed Quizzes']);
    while ($student = $students->fetch_assoc()) {
        fputcsv($output, [
            $student['name'],
            $student['email'],
            $student['quizzes_taken'],
            round($student['avg_score'], 1) . '%',
            $student['passed_quizzes']
        ]);
    }
    fputcsv($output, []);
    
    // Recent Activity
    fputcsv($output, ['RECENT ACTIVITY (' . $date_from . ' to ' . $date_to . ')']);
    fputcsv($output, ['Student', 'Module', 'Score', 'Date']);
    while ($activity = $recent_activity->fetch_assoc()) {
        fputcsv($output, [
            $activity['name'],
            ucfirst($activity['disaster']),
            $activity['score'] . '/' . $activity['total'],
            date('Y-m-d H:i', strtotime($activity['created_at']))
        ]);
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Report - Disaster Ready India</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }

        .report-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header */
        .report-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-title h1 {
            font-size: 1.8em;
            margin-bottom: 5px;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .filter-section h3 {
            margin-bottom: 20px;
            color: #333;
        }

        .filter-form {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 150px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            color: #666;
            font-size: 0.9em;
        }

        .filter-group input, .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9em;
        }

        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 60, 114, 0.3);
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
            transform: translateY(-2px);
        }

        /* Report Content */
        .report-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .report-section {
            margin-bottom: 40px;
        }

        .report-section h2 {
            color: #1e3c72;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-box .number {
            font-size: 2em;
            font-weight: bold;
            color: #1e3c72;
        }

        .stat-box .label {
            color: #666;
            margin-top: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: #f8f9fa;
            color: #1e3c72;
            font-weight: 600;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .score-high {
            color: #27ae60;
            font-weight: bold;
        }

        .score-medium {
            color: #f39c12;
            font-weight: bold;
        }

        .score-low {
            color: #e74c3c;
            font-weight: bold;
        }

        @media print {
            .filter-section, .back-btn, .btn {
                display: none;
            }
            
            .report-container {
                padding: 0;
            }
            
            .report-header {
                background: #1e3c72;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="report-header">
            <div class="header-title">
                <h1><i class="fas fa-chart-bar"></i> Generate Report</h1>
                <p>Create comprehensive reports on student performance</p>
            </div>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h3><i class="fas fa-filter"></i> Report Filters</h3>
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <label>Date From</label>
                    <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                <div class="filter-group">
                    <label>Date To</label>
                    <input type="date" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                <div class="filter-group">
                    <label>Report Type</label>
                    <select name="type">
                        <option value="overview" <?php echo $report_type == 'overview' ? 'selected' : ''; ?>>Overview</option>
                        <option value="detailed" <?php echo $report_type == 'detailed' ? 'selected' : ''; ?>>Detailed</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> Apply Filters
                    </button>
                </div>
                <div class="filter-group">
                    <a href="?format=csv&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&type=<?php echo $report_type; ?>" class="btn btn-success">
                        <i class="fas fa-download"></i> Export CSV
                    </a>
                </div>
            </form>
        </div>

        <!-- Report Content -->
        <div class="report-content" id="reportContent">
            <div class="report-section">
                <h2><i class="fas fa-chart-line"></i> Executive Summary</h2>
                <div class="stats-summary">
                    <div class="stat-box">
                        <div class="number"><?php echo $total_students; ?></div>
                        <div class="label">Total Students</div>
                    </div>
                    <div class="stat-box">
                        <div class="number"><?php echo $total_quizzes; ?></div>
                        <div class="label">Quiz Attempts</div>
                    </div>
                    <div class="stat-box">
                        <div class="number"><?php echo $avg_score; ?>%</div>
                        <div class="label">Average Score</div>
                    </div>
                    <div class="stat-box">
                        <div class="number"><?php echo $passing_percentage; ?>%</div>
                        <div class="label">Passing Rate</div>
                    </div>
                </div>
            </div>

            <div class="report-section">
                <h2><i class="fas fa-book"></i> Module Performance</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>Students Completed</th>
                            <th>Completion Rate</th>
                            <th>Average Score</th>
                            <th>Total Attempts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($module_stats as $stats): ?>
                            <tr>
                                <td><?php echo $stats['name']; ?></td>
                                <td><?php echo $stats['completed']; ?>/<?php echo $total_students; ?></td>
                                <td><?php echo $stats['completion_rate']; ?>%</td>
                                <td><?php echo $stats['avg_score']; ?>%</td>
                                <td><?php echo $stats['total_attempts']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="report-section">
                <h2><i class="fas fa-users"></i> Student Performance</h2>
                <table>
                    <thead>
                        人才
                            <th>Name</th>
                            <th>Email</th>
                            <th>Quizzes Taken</th>
                            <th>Average Score</th>
                            <th>Passed Quizzes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $students = $conn->query("
                            SELECT u.id, u.name, u.email, 
                                   COUNT(r.id) as quizzes_taken,
                                   AVG(r.score/r.total)*100 as avg_score,
                                   SUM(CASE WHEN (r.score/r.total)*100 >= 70 THEN 1 ELSE 0 END) as passed_quizzes
                            FROM users u
                            LEFT JOIN quiz_results r ON u.id = r.user_id
                            WHERE u.role = 'student'
                            GROUP BY u.id
                            ORDER BY avg_score DESC
                        ");
                        while ($student = $students->fetch_assoc()): 
                            $scoreClass = $student['avg_score'] >= 70 ? 'score-high' : ($student['avg_score'] >= 50 ? 'score-medium' : 'score-low');
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo $student['quizzes_taken']; ?></td>
                                <td class="<?php echo $scoreClass; ?>"><?php echo round($student['avg_score'], 1); ?>%</td>
                                <td class="<?php echo $scoreClass; ?>"><?php echo $student['passed_quizzes']; ?>/<?php echo $student['quizzes_taken']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="report-section">
                <h2><i class="fas fa-history"></i> Recent Activity (<?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Module</th>
                            <th>Score</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $recent_activity = $conn->query("
                            SELECT u.name, r.disaster, r.score, r.total, r.created_at
                            FROM quiz_results r
                            JOIN users u ON r.user_id = u.id
                            WHERE DATE(r.created_at) BETWEEN '$date_from' AND '$date_to'
                            ORDER BY r.created_at DESC
                            LIMIT 50
                        ");
                        while ($activity = $recent_activity->fetch_assoc()): 
                            $percentage = ($activity['score'] / $activity['total']) * 100;
                            $scoreClass = $percentage >= 70 ? 'score-high' : ($percentage >= 50 ? 'score-medium' : 'score-low');
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($activity['name']); ?></td>
                                <td><?php echo ucfirst($activity['disaster']); ?></td>
                                <td class="<?php echo $scoreClass; ?>"><?php echo $activity['score']; ?>/<?php echo $activity['total']; ?> (<?php echo round($percentage); ?>%)</td>
                                <td><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="report-section">
                <p style="text-align: center; color: #999; margin-top: 30px;">
                    <i class="fas fa-calendar-alt"></i> Report generated on <?php echo date('F j, Y, g:i a'); ?>
                </p>
            </div>
        </div>

        <div style="margin-top: 20px; text-align: center;">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Print Report
            </button>
            <button class="btn btn-success" onclick="window.location.href='?format=csv&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&type=<?php echo $report_type; ?>'">
                <i class="fas fa-download"></i> Download CSV
            </button>
        </div>
    </div>

    <script>
        // Auto-refresh print styles
        function printReport() {
            window.print();
        }
    </script>
</body>
</html>