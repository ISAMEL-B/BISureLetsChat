<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../unauthorized.php");
  exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BISureChat Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #e6f4ea;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .sidebar {
      height: 100vh;
      background-color: #198754;
      color: white;
      padding: 1rem;
      position: fixed;
      width: 250px;
      transition: all 0.3s ease;
      z-index: 1000;
    }

    .sidebar h4 {
      margin-bottom: 2rem;
      font-weight: 600;
      position: relative;
    }

    .sidebar a {
      color: white;
      display: block;
      margin-bottom: 1rem;
      text-decoration: none;
      padding: 0.5rem;
      border-radius: 4px;
      transition: all 0.2s;
    }

    .sidebar a:hover {
      background-color: rgba(255, 255, 255, 0.1);
    }

    .sidebar a i {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }

    .main {
      margin-left: 250px;
      padding: 2rem;
      transition: all 0.3s ease;
    }

    .card-header {
      background-color: #198754;
      color: white;
      font-weight: 600;
    }

    .table thead th {
      background-color: #198754;
      color: white;
    }

    .badge-success {
      background-color: rgba(25, 135, 84, 0.2);
      color: #198754;
    }

    .badge-danger {
      background-color: rgba(220, 53, 69, 0.2);
      color: #dc3545;
    }

    .badge-warning {
      background-color: rgba(255, 193, 7, 0.2);
      color: #ffc107;
    }

    /* Mobile styles */
    @media (max-width: 992px) {
      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.active {
        transform: translateX(0);
      }

      .main {
        margin-left: 0;
      }

      .toggle-sidebar {
        display: block !important;
      }

      .close-sidebar {
        display: block !important;
      }
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
      width: 8px;
    }

    ::-webkit-scrollbar-track {
      background: #f1f1f1;
    }

    ::-webkit-scrollbar-thumb {
      background: #198754;
      border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: #157347;
    }

    .close-sidebar {
      display: none;
      position: absolute;
      right: 15px;
      top: 15px;
      background: transparent;
      border: none;
      color: white;
      font-size: 1.5rem;
      cursor: pointer;
    }

    .overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 999;
    }

    @media (min-width: 992px) {
      .overlay {
        display: none !important;
      }
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
  <div class="overlay"></div>
  <div class="sidebar">
    <h4><i class="fas fa-comment-dots"></i> BISureChat
      <button class="close-sidebar">&times;</button>
    </h4>
    <a href="../dashboard.php"><i class="fas fa-tachometer-alt"></i> Main Dashboard</a>
    <a href="#"><i class="fas fa-users"></i> Users</a>
    <a href="#"><i class="fas fa-server"></i> Logs</a>
    <a href="#"><i class="fas fa-cog"></i> Settings</a>
  </div>
  <div class="main">
    <button class="btn btn-success toggle-sidebar d-none mb-3">
      <i class="fas fa-bars"></i> Menu
    </button>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
      <h2 class="text-success mb-3 mb-md-0"><i class="fas fa-server"></i> Server Logs Dashboard</h2>
      <button class="btn btn-danger" onclick="deleteSelectedLogs()"><i class="fas fa-trash-alt"></i> Delete Logs</button>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-12 col-md-3">
        <input type="text" id="dateFilter" class="form-control" placeholder="Date Range" />
      </div>
      <div class="col-12 col-md-3">
        <select id="actionFilter" class="form-select">
          <option value="">All Actions</option>
          <option value="connected">Connected</option>
          <option value="disconnected">Disconnected</option>
          <option value="message_sent">Message Sent</option>
          <option value="message_deleted">Message Deleted</option>
          <option value="error">Error</option>
        </select>
      </div>
      <div class="col-12 col-md-2">
        <button class="btn btn-success w-100" onclick="loadLogs()"><i class="fas fa-filter"></i> Filter</button>
      </div>
      <div class="col-12 col-md-4 row g-2">
        <div class="col-6">
          <div class="card h-100">
            <div class="card-body text-center">
              <h6><i class="fas fa-database"></i> Total Logs</h6>
              <h4 id="totalLogs">0</h4>
            </div>
          </div>
        </div>
        <div class="col-6">
          <div class="card h-100">
            <div class="card-body text-center">
              <h6><i class="fas fa-exclamation-circle"></i> Error Logs</h6>
              <h4 id="errorCount">0</h4>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-12">
        <div class="card h-100">
          <div class="card-header"><i class="fas fa-exchange-alt"></i> Connected vs Disconnected (Hourly)</div>
          <div class="card-body" style="height: 300px;">
            <canvas id="hourlyChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-12">
        <div class="card h-100">
          <div class="card-header"><i class="fas fa-chart-line"></i> Weekly Trend (Last 7 Days)</div>
          <div class="card-body" style="height: 300px;">
            <canvas id="weeklyTrendChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-table"></i> All Logs Table</span>
        <div class="d-flex">
          <button class="btn btn-sm btn-outline-success me-2" onclick="exportLogs()">
            <i class="fas fa-download"></i> Export
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="logsTable" class="table table-striped table-hover">
            <thead>
              <tr>
                <th>#</th>
                <th>ID</th>
                <th>User ID</th>
                <th>Action</th>
                <th>Status</th>
                <th>IP</th>
                <th class="d-none d-md-table-cell">User Agent</th>
                <th>Timestamp</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

  <script>
    let table, hourlyChart, weeklyTrendChart;

    $(document).ready(() => {
      $('.toggle-sidebar').click(() => toggleSidebar());
      $('.close-sidebar, .overlay').click(() => toggleSidebar(false));

      table = $('#logsTable').DataTable({
        responsive: true,
        columnDefs: [{
            responsivePriority: 1,
            targets: 1
          },
          {
            responsivePriority: 2,
            targets: 2
          },
          {
            responsivePriority: 3,
            targets: 3
          },
          {
            responsivePriority: 4,
            targets: 4
          },
          {
            responsivePriority: 5,
            targets: -1
          }
        ],
        createdRow: function(row, data, dataIndex) {
          $('td:eq(0)', row).html(dataIndex + 1);
        }
      });

      flatpickr("#dateFilter", {
        mode: "range",
        dateFormat: "Y-m-d"
      });
      initCharts();
      loadLogs();
      drawHourlyLineGraph();
      loadWeeklyTrend();
    });

    function toggleSidebar(show = null) {
      if (show === null) $('.sidebar').toggleClass('active');
      else show ? $('.sidebar').addClass('active') : $('.sidebar').removeClass('active');

      $('.sidebar').hasClass('active') ? $('.overlay').fadeIn() : $('.overlay').fadeOut();
    }

    function initCharts() {
      weeklyTrendChart = new Chart(document.getElementById('weeklyTrendChart').getContext('2d'), {
        type: 'line',
        data: {
          labels: [],
          datasets: [{
            label: 'Daily Connections',
            data: [],
            borderColor: '#198754',
            backgroundColor: 'rgba(25, 135, 84, 0.1)',
            borderWidth: 2,
            tension: 0.4,
            fill: true
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                display: false
              }
            },
            x: {
              grid: {
                display: false
              }
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });
    }

    function drawHourlyLineGraph() {
      $.get('get_hourly_connections.php', res => {
        const ctx = document.getElementById('hourlyChart').getContext('2d');
        if (window.hourlyChartInstance) window.hourlyChartInstance.destroy();

        window.hourlyChartInstance = new Chart(ctx, {
          type: 'line',
          data: {
            labels: res.labels,
            datasets: [{
                label: 'Connected',
                data: res.connected,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40,167,69,0.1)',
                tension: 0.4,
                fill: true
              },
              {
                label: 'Disconnected',
                data: res.disconnected,
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220,53,69,0.1)',
                tension: 0.4,
                fill: true
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true
              },
              x: {
                display: true
              }
            },
            plugins: {
              legend: {
                display: true
              },
              title: {
                display: true,
                text: 'Hourly Connection Comparison (6AM-5AM)'
              }
            }
          }
        });
      }, 'json');
    }

    function loadLogs() {
      const [start, end] = $('#dateFilter').val().split(' to ');
      const action = $('#actionFilter').val();

      Swal.fire({
        title: 'Loading Logs',
        html: 'Please wait while we fetch the latest logs...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      $.get('get_logs.php', {
        start,
        end,
        action
      }, res => {
        if (res.status !== 'success') return Swal.fire('Error', 'Failed to load logs', 'error');

        const logs = res.logs;
        $('#totalLogs').text(logs.length);
        $('#errorCount').text(logs.filter(l => l.action === 'error').length);

        table.clear();
        logs.forEach((l, index) => {
          table.row.add([
            '',
            l.id,
            l.user_id,
            formatAction(l.action),
            formatStatus(l.status),
            l.ip_address,
            l.user_agent,
            formatDate(l.timestamp),
            l.details
          ]);
        });
        table.draw();
        Swal.close();
      }, 'json');
    }

    function loadWeeklyTrend() {
      $.get('get_weekly_trend.php', res => {
        weeklyTrendChart.data.labels = res.labels;
        weeklyTrendChart.data.datasets[0].data = res.data;
        weeklyTrendChart.update();
      }, 'json');
    }

    function deleteSelectedLogs() {
      Swal.fire({
        title: 'Delete logs older than how many days?',
        input: 'number',
        inputAttributes: {
          min: 0
        },
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Delete',
        icon: 'warning'
      }).then(result => {
        if (!result.value && result.value !== 0) return;
        Swal.fire({
          title: 'Deleting...',
          html: 'Please wait while we delete the logs',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        $.post('delete_logs.php', {
          days: result.value
        }, res => {
          Swal.fire('Done', res.message, 'success');
          loadLogs();
        }, 'json').fail(() => {
          Swal.fire('Error', 'Deletion failed', 'error');
        });
      });
    }

    function exportLogs() {
      Swal.fire({
        title: 'Export Logs',
        text: 'Select the format you want to export:',
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: 'CSV',
        denyButtonText: 'Excel',
        cancelButtonText: 'Cancel',
        icon: 'question'
      }).then((result) => {
        if (result.isConfirmed) Swal.fire('Exported!', 'Your logs have been exported as CSV.', 'success');
        else if (result.isDenied) Swal.fire('Exported!', 'Your logs have been exported as Excel.', 'success');
      });
    }

    function formatDate(timestamp) {
      const date = new Date(timestamp);
      return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }

    function formatAction(action) {
      const actions = {
        'connected': 'Connected',
        'disconnected': 'Disconnected',
        'message_sent': 'Message Sent',
        'message_deleted': 'Message Deleted',
        'error': 'Error'
      };
      return actions[action] || action;
    }

    function formatStatus(status) {
      let badgeClass = 'badge-success';
      if (status === 'failed') badgeClass = 'badge-danger';
      if (status === 'pending') badgeClass = 'badge-warning';

      return `<span class="badge ${badgeClass}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
    }
  </script>

</body>

</html>