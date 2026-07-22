<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Get statistics
$total_results = $conn->query("SELECT COUNT(*) as total FROM quiz_results")->fetch_assoc()['total'];
$total_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student'")->fetch_assoc()['total'];
$avg_score_query = $conn->query("SELECT AVG(score/total)*100 as avg FROM quiz_results");
$avg_score_result = $avg_score_query->fetch_assoc();
$avg_score = round($avg_score_result['avg'] ?? 0, 1);

// Get results with user details - Updated with all disaster types
$q = "SELECT users.name, users.email, quiz_results.disaster, quiz_results.score, quiz_results.total, 
      quiz_results.created_at, (quiz_results.score/quiz_results.total)*100 as percentage
      FROM quiz_results 
      JOIN users ON quiz_results.user_id = users.id
      ORDER BY quiz_results.created_at DESC";

$res = $conn->query($q);

// Get statistics by disaster
$disaster_stats = [];
$disasters = ['earthquake', 'flood', 'fire', 'cyclone', 'drought', 'tsunami', 'lightening'];
foreach ($disasters as $disaster) {
    $stat = $conn->query("SELECT COUNT(*) as count, AVG(score/total)*100 as avg FROM quiz_results WHERE disaster='$disaster'");
    $data = $stat->fetch_assoc();
    $disaster_stats[$disaster] = [
        'count' => $data['count'] ?? 0,
        'avg' => round($data['avg'] ?? 0, 1)
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Disaster Management System</title>
    <link rel="stylesheet" href="../admin/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Additional styles for disaster stats */
        .disaster-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .disaster-stat-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .disaster-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .disaster-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .disaster-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        
        .disaster-count {
            font-size: 1.5em;
            font-weight: bold;
            color: #1e3c72;
        }
        
        .disaster-avg {
            font-size: 0.85em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <div class="header-title">
                <h2>
                    <i class="fas fa-shield-alt"></i>
                    Admin Dashboard
                </h2>
                <p>Disaster Preparedness Training System</p>
            </div>
            <div class="admin-info">
                <i class="fas fa-user-shield"></i>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-number"><?php echo $total_results; ?></div>
                <div class="stat-label">Total Quiz Attempts</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-number"><?php echo $avg_score; ?>%</div>
                <div class="stat-label">Average Score</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-number"><?php echo $res->num_rows; ?></div>
                <div class="stat-label">Results Displayed</div>
            </div>
        </div>

        <!-- Disaster-wise Statistics -->
        <div class="results-section" style="margin-bottom: 30px;">
            <div class="section-header">
                <h3>
                    <i class="fas fa-chart-pie"></i>
                    Disaster-wise Statistics
                </h3>
            </div>
            <div class="disaster-stats-grid">
                <?php foreach ($disaster_stats as $disaster => $stats): 
                    $icons = [
                        'earthquake' => '🌍',
                        'flood' => '🌊',
                        'fire' => '🔥',
                        'cyclone' => '🌀',
                        'drought' => '💧',
                        'tsunami' => '🌊',
                        'lightening' => '⚡'
                    ];
                    $names = [
                        'earthquake' => 'Earthquake',
                        'flood' => 'Flood',
                        'fire' => 'Fire',
                        'cyclone' => 'Cyclone',
                        'drought' => 'Drought',
                        'tsunami' => 'Tsunami',
                        'lightening' => 'Lightning'
                    ];
                ?>
                    <div class="disaster-stat-card">
                        <div class="disaster-icon"><?php echo $icons[$disaster] ?? '📚'; ?></div>
                        <div class="disaster-name"><?php echo $names[$disaster] ?? ucfirst($disaster); ?></div>
                        <div class="disaster-count"><?php echo $stats['count']; ?></div>
                        <div class="disaster-avg">Avg: <?php echo $stats['avg']; ?>%</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-filter">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search by name, email, or disaster..." onkeyup="filterTable()">
                <i class="fas fa-search"></i>
            </div>
            <select id="filterDisaster" class="filter-select" onchange="filterTable()">
                <option value="all">All Disasters</option>
                <option value="earthquake">Earthquake</option>
                <option value="flood">Flood</option>
                <option value="fire">Fire</option>
                <option value="cyclone">Cyclone</option>
                <option value="drought">Drought</option>
                <option value="tsunami">Tsunami</option>
                <option value="lightening">Lightning</option>
            </select>
            <select id="filterScore" class="filter-select" onchange="filterTable()">
                <option value="all">All Scores</option>
                <option value="high">High (≥70%)</option>
                <option value="medium">Medium (50-69%)</option>
                <option value="low">Low (<50%)</option>
            </select>
        </div>

        <!-- Export Section -->
        <div class="export-section">
            <button class="export-btn" onclick="exportToCSV()">
                <i class="fas fa-file-csv"></i> Export CSV
            </button>
            <button class="export-btn" onclick="window.print()">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>

        <!-- Results Table -->
        <div class="results-section">
            <div class="section-header">
                <h3>
                    <i class="fas fa-chart-line"></i>
                    Student Results
                </h3>
                <div class="result-stats">
                    <span><i class="fas fa-trophy"></i> Overall Average: <?php echo $avg_score; ?>%</span>
                    <span><i class="fas fa-users"></i> Active Students: <?php echo $total_users; ?></span>
                </div>
            </div>
            <div class="table-container">
                <table class="results-table" id="resultsTable">
                    <thead>
                          thy
                            <th onclick="sortTable(0)">Name <i class="fas fa-sort"></i></th>
                            <th onclick="sortTable(1)">Email <i class="fas fa-sort"></i></th>
                            <th onclick="sortTable(2)">Disaster <i class="fas fa-sort"></i></th>
                            <th onclick="sortTable(3)">Score <i class="fas fa-sort"></i></th>
                            <th onclick="sortTable(4)">Percentage <i class="fas fa-sort"></i></th>
                            <th onclick="sortTable(5)">Date <i class="fas fa-sort"></i></th>
                            <th>Status</th>
                          </tr>
                    </thead>
                    <tbody>
                        <?php if ($res && $res->num_rows > 0): ?>
                            <?php while ($row = $res->fetch_assoc()): 
                                $percentage = $row['percentage'];
                                $scoreClass = $percentage >= 70 ? 'score-high' : ($percentage >= 50 ? 'score-medium' : 'score-low');
                                $status = $percentage >= 70 ? 'Passed' : ($percentage >= 50 ? 'Needs Review' : 'Failed');
                                
                                $disasterIcons = [
                                    'earthquake' => '🌍',
                                    'flood' => '🌊',
                                    'fire' => '🔥',
                                    'cyclone' => '🌀',
                                    'drought' => '💧',
                                    'tsunami' => '🌊',
                                    'lightening' => '⚡'
                                ];
                                $icon = $disasterIcons[$row['disaster']] ?? '📚';
                            ?>
                                <tr>
                                    <td><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo $icon . ' ' . ucfirst(htmlspecialchars($row['disaster'])); ?></td>
                                    <td><?php echo $row['score']; ?>/<?php echo $row['total']; ?></td>
                                    <td>
                                        <div class="score-badge <?php echo $scoreClass; ?>">
                                            <?php echo round($percentage); ?>%
                                        </div>
                                    </td>
                                    <td><i class="fas fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <?php if ($percentage >= 70): ?>
                                            <i class="fas fa-check-circle" style="color: #27ae60;"></i> <?php echo $status; ?>
                                        <?php elseif ($percentage >= 50): ?>
                                            <i class="fas fa-exclamation-triangle" style="color: #f39c12;"></i> <?php echo $status; ?>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle" style="color: #e74c3c;"></i> <?php echo $status; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fas fa-chart-line"></i>
                                        <p>No quiz results found yet.</p>
                                        <small>Students need to complete quizzes to see results here.</small>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="pagination" id="pagination"></div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="add_alert.php" class="action-btn btn-primary">
                <i class="fas fa-plus-circle"></i> Add New Alert
            </a>
            <button class="action-btn btn-success" onclick="generateReport()">
                <i class="fas fa-chart-bar"></i> Generate Report
            </button>
            <button class="action-btn btn-info" onclick="viewAnalytics()">
                <i class="fas fa-chart-line"></i> View Analytics
            </button>
        </div>
    </div>

    <script>
        // Table filtering
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const disasterFilter = document.getElementById('filterDisaster').value;
            const scoreFilter = document.getElementById('filterScore').value;
            const table = document.getElementById('resultsTable');
            const tr = table.getElementsByTagName('tr');
            
            let visibleCount = 0;
            
            for (let i = 1; i < tr.length; i++) {
                let showRow = true;
                const tdName = tr[i].getElementsByTagName('td')[0];
                const tdEmail = tr[i].getElementsByTagName('td')[1];
                const tdDisaster = tr[i].getElementsByTagName('td')[2];
                const tdPercentage = tr[i].getElementsByTagName('td')[4];
                
                if (tdName && tdEmail && tdDisaster && tdPercentage) {
                    const nameValue = tdName.textContent || tdName.innerText;
                    const emailValue = tdEmail.textContent || tdEmail.innerText;
                    const disasterValue = tdDisaster.textContent || tdDisaster.innerText;
                    const percentageText = tdPercentage.textContent || tdPercentage.innerText;
                    const percentage = parseInt(percentageText);
                    
                    // Search filter
                    if (filter && !nameValue.toLowerCase().includes(filter) && 
                        !emailValue.toLowerCase().includes(filter) && 
                        !disasterValue.toLowerCase().includes(filter)) {
                        showRow = false;
                    }
                    
                    // Disaster filter
                    if (disasterFilter !== 'all' && !disasterValue.toLowerCase().includes(disasterFilter)) {
                        showRow = false;
                    }
                    
                    // Score filter
                    if (scoreFilter !== 'all') {
                        if (scoreFilter === 'high' && percentage < 70) showRow = false;
                        if (scoreFilter === 'medium' && (percentage < 50 || percentage >= 70)) showRow = false;
                        if (scoreFilter === 'low' && percentage >= 50) showRow = false;
                    }
                    
                    tr[i].style.display = showRow ? '' : 'none';
                    if (showRow) visibleCount++;
                }
            }
            
            updatePagination(visibleCount);
        }
        
        // Table sorting
        let sortDirection = {};
        
        function sortTable(columnIndex) {
            const table = document.getElementById('resultsTable');
            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = Array.from(tbody.getElementsByTagName('tr')).filter(row => row.style.display !== 'none');
            
            sortDirection[columnIndex] = !sortDirection[columnIndex];
            
            rows.sort((a, b) => {
                let aVal = a.getElementsByTagName('td')[columnIndex]?.textContent || '';
                let bVal = b.getElementsByTagName('td')[columnIndex]?.textContent || '';
                
                if (columnIndex === 4) {
                    aVal = parseInt(aVal);
                    bVal = parseInt(bVal);
                }
                
                if (sortDirection[columnIndex]) {
                    return aVal > bVal ? 1 : -1;
                } else {
                    return aVal < bVal ? 1 : -1;
                }
            });
            
            rows.forEach(row => tbody.appendChild(row));
        }
        
        // Pagination
        let currentPage = 1;
        const rowsPerPage = 10;
        
        function updatePagination(totalRows) {
            const pageCount = Math.ceil(totalRows / rowsPerPage);
            const paginationDiv = document.getElementById('pagination');
            
            if (pageCount <= 1) {
                paginationDiv.innerHTML = '';
                return;
            }
            
            let html = '';
            for (let i = 1; i <= pageCount; i++) {
                html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
            }
            paginationDiv.innerHTML = html;
            
            // Show only current page rows
            const table = document.getElementById('resultsTable');
            const rows = Array.from(table.getElementsByTagName('tr')).filter(row => row.style.display !== 'none');
            rows.forEach((row, index) => {
                if (index >= (currentPage - 1) * rowsPerPage && index < currentPage * rowsPerPage) {
                    row.style.display = '';
                } else if (index > 0) {
                    row.style.display = 'none';
                }
            });
        }
        
        function goToPage(page) {
            currentPage = page;
            filterTable();
        }
        
        // Export to CSV
        function exportToCSV() {
            const table = document.getElementById('resultsTable');
            const rows = table.querySelectorAll('tr');
            const csv = [];
            
            rows.forEach(row => {
                const cols = row.querySelectorAll('td, th');
                const rowData = Array.from(cols).map(col => col.textContent.trim());
                csv.push(rowData.join(','));
            });
            
            const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `admin_results_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            URL.revokeObjectURL(url);
            
            showNotification('Report exported successfully!', 'success');
        }
        
        // Generate report
        function generateReport() {
            showNotification('Generating detailed report...', 'success');
            setTimeout(() => {
                window.location.href = 'generate_report.php';
            }, 1000);
        }
        
        // View analytics
        function viewAnalytics() {
            showNotification('Loading analytics dashboard...', 'success');
            setTimeout(() => {
                window.location.href = 'analytics.php';
            }, 1000);
        }
        
        // Show notification
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        // Initialize
        filterTable();
    </script>
</body>
</html>