<?php 

// Check dark mode preference
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled';

// ✅ FIXED: Updated paths
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$current_user_id = $_SESSION['user_id'];

// ✅ Fetch call history from calls table
$stmt = $conn->prepare("
    SELECT 
        c.id,
        c.caller_id,
        c.receiver_id,
        c.call_type,
        c.status,
        c.started_at,
        c.ended_at,
        c.created_at,
        -- Determine if current user is caller or receiver
        CASE 
            WHEN c.caller_id = ? THEN 'outgoing'
            WHEN c.status = 'missed' AND c.receiver_id = ? THEN 'missed'
            ELSE 'incoming'
        END AS direction,
        -- Get the other person's info
        CASE 
            WHEN c.caller_id = ? THEN u2.fullname
            ELSE u1.fullname
        END AS contact_name,
        CASE 
            WHEN c.caller_id = ? THEN u2.username
            ELSE u1.username
        END AS contact_username,
        CASE 
            WHEN c.caller_id = ? THEN u2.profile_photo
            ELSE u1.profile_photo
        END AS contact_photo,
        CASE 
            WHEN c.caller_id = ? THEN u2.id
            ELSE u1.id
        END AS contact_id,
        -- Calculate duration
        CASE 
            WHEN c.started_at IS NOT NULL AND c.ended_at IS NOT NULL 
            THEN TIMESTAMPDIFF(SECOND, c.started_at, c.ended_at)
            ELSE 0
        END AS duration_seconds
    FROM calls c
    LEFT JOIN users u1 ON c.caller_id = u1.id
    LEFT JOIN users u2 ON c.receiver_id = u2.id
    WHERE c.caller_id = ? OR c.receiver_id = ?
    ORDER BY c.created_at DESC
    LIMIT 50
");
$stmt->bind_param("iiiiiiii", 
    $current_user_id,  // direction: caller = outgoing
    $current_user_id,  // direction: missed check
    $current_user_id,  // contact_name caller
    $current_user_id,  // contact_username caller
    $current_user_id,  // contact_photo caller
    $current_user_id,  // contact_id caller
    $current_user_id,  // WHERE caller_id
    $current_user_id   // WHERE receiver_id
);
$stmt->execute();
$calls_result = $stmt->get_result();

// ✅ Convert to JSON for JavaScript
$callHistory = [];
while ($row = $calls_result->fetch_assoc()) {
    // Format duration
    $duration = '';
    $secs = (int)$row['duration_seconds'];
    if ($secs > 0) {
        $mins = floor($secs / 60);
        $remainingSecs = $secs % 60;
        $duration = $mins . ':' . str_pad($remainingSecs, 2, '0', STR_PAD_LEFT);
    } else {
        $duration = '0:00';
    }
    
    $callHistory[] = [
        'id' => (int)$row['id'],
        'contactName' => $row['contact_name'] ?? $row['contact_username'] ?? 'Unknown',
        'contactId' => (string)$row['contact_id'],
        'contactPhoto' => $row['contact_photo'] ?? '',
        'type' => $row['direction'],        // 'outgoing', 'incoming', 'missed'
        'callType' => $row['call_type'],    // 'voice' or 'video'
        'duration' => $duration,
        'date' => $row['created_at'],
        'timestamp' => strtotime($row['created_at']) * 1000
    ];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>BISure | Call History</title>
    <link rel="icon" href="../../favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ch-primary: #128C7E;
            --ch-primary-dark: #075E54;
            --ch-secondary: #25D366;
            --ch-bg: #e5ddd5;
            --ch-card: #ffffff;
            --ch-text: #2D3748;
            --ch-text-secondary: #718096;
            --ch-border: #E2E8F0;
            --ch-hover: rgba(18, 140, 126, 0.04);
            --ch-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            --ch-shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.12);
            --ch-incoming: #25D366;
            --ch-outgoing: #34B7F1;
            --ch-missed: #E74C3C;
            --ch-filter-active-bg: rgba(18, 140, 126, 0.1);
            --ch-filter-active-text: #128C7E;
            --ch-filter-hover: rgba(18, 140, 126, 0.05);
            --ch-input-bg: #f9f9f9;
            --ch-transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --nav-height: 60px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--ch-bg);
            color: var(--ch-text);
            min-height: 100vh;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .ch-page { display: flex; justify-content: center; min-height: 100vh; }
        .ch-wrapper {
            width: 100%; max-width: 600px; background: var(--ch-card);
            min-height: 100vh; box-shadow: var(--ch-shadow-lg);
            position: relative; transition: background 0.3s ease;
            padding-bottom: var(--nav-height);
        }

        /* =============================================
           HEADER WITH SIDEBAR TOGGLE ON LEFT
           ============================================= */
        .ch-header {
            background: linear-gradient(135deg, var(--ch-primary-dark), var(--ch-primary));
            padding: 1.2rem 1.5rem;
            color: white;
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-height: 60px;
        }

        /* Sidebar toggle - positioned on the LEFT */
        .ch-sidebar-toggle {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            z-index: 2;
            color: inherit;
        }

        /* Header title - centered */
        .ch-header-title {
            font-size: 1.35rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            justify-content: center;
            text-align: center;
            margin-left: -48px;
        }

        .ch-pro-badge {
            background: #FFD700;
            color: #000;
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        /* Right spacer for balance */
        .ch-header-spacer {
            flex-shrink: 0;
            width: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Options button in right spacer */
        .ch-header-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            transition: var(--ch-transition);
        }
        .ch-header-btn:hover { background: rgba(255,255,255,0.35); }

        /* =============================================
           FILTERS
           ============================================= */
        .ch-filters {
            display: flex;
            gap: 8px;
            padding: 1rem;
            background: var(--ch-card);
            border-bottom: 1px solid var(--ch-border);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        .ch-filters::-webkit-scrollbar { height: 0; }
        .ch-filter-btn {
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid var(--ch-border);
            background: var(--ch-card);
            color: var(--ch-text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            font-family: 'Poppins', sans-serif;
            transition: var(--ch-transition);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .ch-filter-btn:hover {
            background: var(--ch-filter-hover);
            border-color: var(--ch-primary);
        }
        .ch-filter-btn.ch-active {
            background: var(--ch-filter-active-bg);
            border-color: var(--ch-primary);
            color: var(--ch-filter-active-text);
            font-weight: 600;
        }

        /* =============================================
           SEARCH
           ============================================= */
        .ch-search-wrap {
            padding: 0.75rem 1rem;
            background: var(--ch-card);
            border-bottom: 1px solid var(--ch-border);
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        .ch-search-inner { position: relative; }
        .ch-search-inner i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--ch-text-secondary);
            font-size: 0.9rem;
        }
        .ch-search-inner input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            border: 1px solid var(--ch-border);
            border-radius: 24px;
            font-size: 0.9rem;
            outline: none;
            background: var(--ch-input-bg);
            color: var(--ch-text);
            font-family: 'Poppins', sans-serif;
            transition: var(--ch-transition);
        }
        .ch-search-inner input:focus {
            border-color: var(--ch-primary);
            box-shadow: 0 0 0 3px rgba(18, 140, 126, 0.1);
        }
        .ch-search-inner input::placeholder { color: #a0a0a0; }

        /* =============================================
           CALL HISTORY LIST
           ============================================= */
        .ch-history-list { padding: 0.25rem 0; }
        .ch-history-item {
            display: flex;
            align-items: center;
            padding: 0.85rem 1rem;
            gap: 12px;
            cursor: pointer;
            transition: var(--ch-transition);
            border-bottom: 1px solid var(--ch-border);
        }
        .ch-history-item:hover { background: var(--ch-hover); }
        .ch-history-item:active { transform: scale(0.99); }

        .ch-call-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        .ch-call-icon.ch-incoming { background: rgba(37, 211, 102, 0.12); color: var(--ch-incoming); }
        .ch-call-icon.ch-outgoing { background: rgba(52, 183, 241, 0.12); color: var(--ch-outgoing); }
        .ch-call-icon.ch-missed { background: rgba(231, 76, 60, 0.12); color: var(--ch-missed); }

        .ch-history-details { flex: 1; min-width: 0; }
        .ch-contact-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--ch-text);
            margin-bottom: 2px;
        }
        .ch-call-info {
            font-size: 0.78rem;
            color: var(--ch-text-secondary);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .ch-call-duration {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--ch-text-secondary);
            flex-shrink: 0;
        }

        /* =============================================
           LOAD MORE
           ============================================= */
        .ch-load-more { text-align: center; padding: 1.5rem; }
        .ch-load-btn {
            padding: 10px 24px;
            border-radius: 24px;
            border: 1px solid var(--ch-primary);
            background: transparent;
            color: var(--ch-primary);
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: var(--ch-transition);
        }
        .ch-load-btn:hover { background: var(--ch-primary); color: white; }

        /* =============================================
           EMPTY STATE
           ============================================= */
        .ch-empty {
            text-align: center;
            padding: 3rem 1.5rem;
            color: var(--ch-text-secondary);
        }
        .ch-empty i { font-size: 4rem; margin-bottom: 1rem; opacity: 0.3; }
        .ch-empty h3 { font-weight: 600; margin-bottom: 0.5rem; color: var(--ch-text); }

        /* =============================================
           DARK MODE
           ============================================= */
        body.dark-mode {
            --ch-bg: #0B141A;
            --ch-card: #1F2C33;
            --ch-text: #E9EDEF;
            --ch-text-secondary: #8696A0;
            --ch-border: #2A3942;
            --ch-hover: rgba(255, 255, 255, 0.04);
            --ch-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            --ch-shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.5);
            --ch-filter-active-bg: rgba(37, 211, 102, 0.12);
            --ch-filter-active-text: #25D366;
            --ch-filter-hover: rgba(37, 211, 102, 0.06);
            --ch-input-bg: #2A3942;
            background: var(--ch-bg);
        }
        body.dark-mode .ch-wrapper { background-color: #1F2C33; }
        body.dark-mode .ch-search-inner input { background: var(--ch-input-bg); border-color: #374248; color: var(--ch-text); }
        body.dark-mode .ch-search-inner input::placeholder { color: var(--ch-text-secondary); }
        body.dark-mode .ch-search-inner input:focus { border-color: #25D366; }
        body.dark-mode .ch-call-icon.ch-incoming { background: rgba(37, 211, 102, 0.18); }
        body.dark-mode .ch-call-icon.ch-outgoing { background: rgba(52, 183, 241, 0.18); }
        body.dark-mode .ch-call-icon.ch-missed { background: rgba(231, 76, 60, 0.18); }
        body.dark-mode .ch-load-btn { border-color: var(--ch-secondary); color: var(--ch-secondary); }
        body.dark-mode .ch-load-btn:hover { background: var(--ch-secondary); color: white; }
        body.dark-mode .ch-filter-btn { background: #1F2C33; border-color: #2A3942; color: #8696A0; }
        body.dark-mode .ch-filter-btn:hover { background: #2A3942; border-color: #25D366; }
        body.dark-mode .ch-filter-btn.ch-active { background: rgba(37, 211, 102, 0.12); border-color: #25D366; color: #25D366; }
        body.dark-mode .ch-history-item { border-bottom-color: #2A3942; }
        body.dark-mode .ch-search-wrap { border-bottom-color: #2A3942; }
        body.dark-mode .ch-filters { border-bottom-color: #2A3942; }

        /* =============================================
           RESPONSIVE
           ============================================= */
        @media (max-width: 480px) {
            .ch-header {
                padding: 0.9rem 1rem;
                min-height: 54px;
            }
            
            .ch-sidebar-toggle {
                margin-right: 8px;
            }
            
            .ch-header-title {
                font-size: 1.15rem;
                margin-left: -40px;
            }
            
            .ch-header-spacer {
                width: 40px;
            }
            
            .ch-pro-badge {
                font-size: 0.65rem;
                padding: 3px 8px;
            }

            .ch-filters {
                padding: 0.75rem 0.8rem;
                gap: 6px;
            }
            
            .ch-filter-btn {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
            
            .ch-search-wrap {
                padding: 0.6rem 0.8rem;
            }
            
            .ch-history-item {
                padding: 0.7rem 0.9rem;
                gap: 10px;
            }
            
            .ch-call-icon {
                width: 38px;
                height: 38px;
                font-size: 0.8rem;
            }
            
            .ch-contact-name {
                font-size: 0.9rem;
            }
            
            .ch-header-btn {
                width: 32px;
                height: 32px;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 360px) {
            .ch-header {
                padding: 0.8rem 0.75rem;
            }
            
            .ch-sidebar-toggle {
                margin-right: 6px;
            }
            
            .ch-header-title {
                font-size: 1.05rem;
                margin-left: -36px;
            }
            
            .ch-header-spacer {
                width: 36px;
            }
            
            .ch-filter-btn {
                padding: 5px 10px;
                font-size: 0.75rem;
            }
            
            .ch-history-item {
                padding: 0.6rem 0.75rem;
            }
            
            .ch-call-icon {
                width: 34px;
                height: 34px;
                font-size: 0.75rem;
            }
            
            .ch-header-btn {
                width: 28px;
                height: 28px;
                font-size: 0.8rem;
            }
        }

        @media (min-width: 768px) {
            .ch-wrapper {
                border-radius: 16px;
                margin: 20px 0;
                min-height: calc(100vh - 40px);
            }
            
            .ch-header {
                padding: 1.4rem 2rem;
            }
            
            .ch-header-title {
                font-size: 1.5rem;
                margin-left: -56px;
            }
            
            .ch-sidebar-toggle {
                margin-right: 16px;
            }
            
            .ch-header-spacer {
                width: 56px;
            }
        }
    </style>
</head>

<body class="<?= $darkMode ? 'dark-mode' : '' ?>">

<div class="ch-page">
    <div class="ch-wrapper">
        <!-- ✅ UPDATED HEADER with sidebar toggle on LEFT -->
        <div class="ch-header">
            <!-- Sidebar toggle on LEFT -->
            <div class="ch-sidebar-toggle">
                <?php if (file_exists(__DIR__ . '/../includes/cd_hamburger.php')) require_once __DIR__ . '/../includes/cd_hamburger.php'; ?>
            </div>
            
            <!-- Centered title -->
            <div class="ch-header-title">
                Call History <span class="ch-pro-badge">PRO</span>
            </div>
            
            <!-- Right spacer with options button -->
            <div class="ch-header-spacer">
                <button class="ch-header-btn" title="Options"><i class="fas fa-ellipsis-v"></i></button>
            </div>
        </div>

        <div class="ch-filters">
            <button class="ch-filter-btn ch-active" data-filter="all"><i class="fas fa-list"></i> All</button>
            <button class="ch-filter-btn" data-filter="incoming"><i class="fas fa-phone-alt"></i> Incoming</button>
            <button class="ch-filter-btn" data-filter="outgoing"><i class="fas fa-phone"></i> Outgoing</button>
            <button class="ch-filter-btn" data-filter="missed"><i class="fas fa-phone-slash"></i> Missed</button>
        </div>

        <div class="ch-search-wrap">
            <div class="ch-search-inner">
                <i class="fas fa-search"></i>
                <input type="text" id="chSearchInput" placeholder="Search call history..." autocomplete="off">
            </div>
        </div>

        <div class="ch-history-list" id="chHistoryList"></div>

        <div class="ch-load-more">
            <button class="ch-load-btn" id="chLoadMoreBtn">Load More</button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<script>
    // ✅ Real data from database - passed via PHP
    const callHistory = <?= json_encode($callHistory) ?>;
    
    const historyList = document.getElementById('chHistoryList');
    const searchInput = document.getElementById('chSearchInput');
    const loadMoreBtn = document.getElementById('chLoadMoreBtn');
    const filterBtns = document.querySelectorAll('.ch-filter-btn');

    let currentFilter = 'all';
    let displayedItems = 10;

    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
        if (diffDays === 0) return 'Today at ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        if (diffDays === 1) return 'Yesterday at ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        if (diffDays < 7) return date.toLocaleDateString([], { weekday: 'long', hour: '2-digit', minute: '2-digit' });
        return date.toLocaleDateString([], { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function renderHistory() {
        let filtered = [...callHistory];
        if (currentFilter !== 'all') filtered = filtered.filter(item => item.type === currentFilter);
        const term = searchInput.value.toLowerCase();
        if (term) filtered = filtered.filter(item => item.contactName.toLowerCase().includes(term));
        filtered.sort((a, b) => b.timestamp - a.timestamp);
        historyList.innerHTML = '';

        if (filtered.length === 0) {
            historyList.innerHTML = '<div class="ch-empty"><i class="fas fa-history"></i><h3>No call history found</h3><p>Your call history will appear here</p></div>';
            loadMoreBtn.style.display = 'none';
            return;
        }

        const itemsToShow = filtered.slice(0, displayedItems);
        itemsToShow.forEach(item => {
            let iconClass = '', iconSymbol = '';
            if (item.type === 'incoming') { iconClass = 'ch-incoming'; iconSymbol = 'fa-phone-alt'; }
            else if (item.type === 'outgoing') { iconClass = 'ch-outgoing'; iconSymbol = 'fa-phone'; }
            else { iconClass = 'ch-missed'; iconSymbol = 'fa-phone-slash'; }

            const div = document.createElement('div');
            div.className = 'ch-history-item';
            div.innerHTML = 
                '<div class="ch-call-icon ' + iconClass + '"><i class="fas ' + iconSymbol + '"></i></div>' +
                '<div class="ch-history-details">' +
                    '<div class="ch-contact-name">' + item.contactName + '</div>' +
                    '<div class="ch-call-info">' +
                        '<span>' + (item.type === 'missed' ? 'Missed ' : '') + item.callType + ' call</span>' +
                        ' <span>•</span> ' +
                        '<span>' + formatDate(item.date) + '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="ch-call-duration">' + item.duration + '</div>';
            historyList.appendChild(div);
        });

        loadMoreBtn.style.display = filtered.length > displayedItems ? 'block' : 'none';
    }

    searchInput.addEventListener('input', renderHistory);
    loadMoreBtn.addEventListener('click', () => { displayedItems += 10; renderHistory(); });

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('ch-active'));
            btn.classList.add('ch-active');
            currentFilter = btn.dataset.filter;
            displayedItems = 10;
            renderHistory();
        });
    });

    renderHistory();
</script>
</body>
</html>