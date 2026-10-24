<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['username'])) {
    echo "<script>window.location='index.php';</script>"; exit;
}
ensure_binary_schema($pdo);

// Fetch user's package & currency info
$urow = mlmp_pdo_fetch($pdo, "SELECT pcktaken FROM affiliateuser WHERE username=?", [$_SESSION['username']]);
$pcktaken = $urow['pcktaken'] ?? 0;
$pckcur = '';
$l1=$l2=$l3=$l4=$l5=$l6=$l7=$l8=$l9=$l10 = 0;
if ($pcktaken) {
    $r = mlmp_pdo_fetch($pdo, "SELECT * FROM packages WHERE id=?", [(int)$pcktaken]);
    if ($r) {
        $pckcur = $r['currency'];
        $l1=$r['level1']; $l2=$r['level2']; $l3=$r['level3'];  $l4=$r['level4'];
        $l5=$r['level5']; $l6=$r['level6']; $l7=$r['level7'];  $l8=$r['level8'];
        $l9=$r['level9']; $l10=$r['level10'];
    }
}

if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="my_network_downline_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['User ID', 'Username', 'Full Name', 'Email', 'Sponsor', 'Position', 'Left Count', 'Right Count', 'Paid Pairs', 'Wallet Balance (INR)', 'Package', 'Status', 'Joining Date']);

    $sessionUser = $_SESSION['username'];
    $members = mlmp_pdo_fetch_all($pdo, "
        SELECT u.Id, u.username, u.fname, u.email, u.referedby, u.position, u.left_count, u.right_count, u.paid_pairs, u.tamount, u.active, u.doj, p.name as package_name 
        FROM affiliateuser u
        LEFT JOIN packages p ON u.pcktaken = p.id
        WHERE u.referedby = ? OR u.parent_id IN (SELECT Id FROM affiliateuser WHERE username = ? OR referedby = ?)
        ORDER BY u.Id ASC
    ", [$sessionUser, $sessionUser, $sessionUser]);

    foreach ($members as $m) {
        fputcsv($output, [
            $m['Id'],
            $m['username'],
            $m['fname'],
            $m['email'],
            $m['referedby'],
            $m['position'] ?: 'Root',
            $m['left_count'],
            $m['right_count'],
            $m['paid_pairs'],
            number_format((float)$m['tamount'], 2),
            $m['package_name'] ?: 'Starter Plan',
            $m['active'] == 1 ? 'Active' : 'Inactive',
            $m['doj']
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "My Network";
$active_nav = "downline";
$extra_head = '
<style>
/* ULTRA-PREMIUM MODERN CSS FOR BINARY TREE VIEW */
@keyframes pulseGlow {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.tree-container {
    overflow: auto;
    padding: 60px 40px;
    background-color: #f8fafc;
    background-image: radial-gradient(#cbd5e1 1.2px, transparent 1.2px);
    background-size: 24px 24px;
    border-radius: 20px;
    position: relative;
    min-height: 560px;
    max-height: 760px;
    cursor: grab;
    user-select: none;
    border: 1px solid #e2e8f0;
    box-shadow: inset 0 2px 10px rgba(15, 23, 42, 0.03);
}
.tree-container.grabbing {
    cursor: grabbing;
}
.tree-container::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}
.tree-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}
.tree-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.tree-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
.tree-container:fullscreen {
    max-height: 100vh !important;
    height: 100vh !important;
    border-radius: 0 !important;
    padding: 60px 40px !important;
    background-color: #f8fafc !important;
}
.tree-wrapper {
    display: inline-block;
    transform-origin: top center;
    transition: transform 0.15s ease-out;
    min-width: 100%;
    text-align: center;
}
.tree {
    display: inline-block;
    margin: 0 auto;
    text-align: center;
    padding-bottom: 40px;
}
.tree ul {
    padding-top: 28px; 
    position: relative;
    transition: all 0.3s ease;
    display: flex;
    justify-content: center;
    padding-left: 0;
    margin: 0 auto;
    width: max-content;
}
.tree li {
    float: left; text-align: center;
    list-style-type: none;
    position: relative;
    padding: 28px 12px 0 12px;
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
    align-items: center;
}
/* Modern Crisp Connectors */
.tree li::before, .tree li::after {
    content: "";
    position: absolute; top: 0; right: 50%;
    border-top: 2px solid #94a3b8;
    width: 50%; height: 28px;
}
.tree li::after {
    right: auto; left: 50%;
    border-left: 2px solid #94a3b8;
}
.tree li:only-child::after, .tree li:only-child::before {
    display: none;
}
.tree li:only-child { padding-top: 0; }
.tree li:first-child::before, .tree li:last-child::after {
    border: 0 none;
}
.tree li:last-child::before {
    border-right: 2px solid #94a3b8;
    border-radius: 0 10px 0 0;
}
.tree li:first-child::after {
    border-radius: 10px 0 0 0;
}
.tree ul ul::before {
    content: "";
    position: absolute; top: 0; left: 50%;
    border-left: 2px solid #94a3b8;
    width: 0; height: 28px;
    transform: translateX(-50%);
}

/* Modern Card Geometry */
.tree-node {
    text-decoration: none;
    color: #0f172a;
    display: inline-block;
    border-radius: 16px;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    background: #ffffff;
    width: 175px;
    border: 1.5px solid #e2e8f0;
    box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.06), 0 2px 4px -1px rgba(15, 23, 42, 0.04);
    position: relative;
    z-index: 1;
    overflow: visible;
}
.tree-node:hover {
    box-shadow: 0 16px 32px -4px rgba(99, 102, 241, 0.22);
    border-color: #818cf8;
    transform: translateY(-5px);
}

/* Node Header Styles */
.node-header {
    padding: 7px 12px;
    font-weight: 700;
    font-size: 11px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top-left-radius: 14px;
    border-top-right-radius: 14px;
}
.node-header.pos-root {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    color: #92400e;
    border-bottom: 1px solid #fde68a;
}
.node-header.pos-left {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    color: #1e40af;
    border-bottom: 1px solid #bfdbfe;
}
.node-header.pos-right {
    background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
    color: #6b21a8;
    border-bottom: 1px solid #e9d5ff;
}
.pos-tag {
    font-size: 10.5px;
    font-weight: 800;
    letter-spacing: -0.01em;
    display: flex;
    align-items: center;
    gap: 4px;
}
.status-pill {
    display: flex;
    align-items: center;
    justify-content: center;
}
.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10b981;
}
.status-pill.active .status-dot {
    background: #10b981;
    animation: pulseGlow 2s infinite;
}
.status-pill.inactive .status-dot {
    background: #ef4444;
}

/* Node Body Styles */
.node-body {
    padding: 12px 10px 10px 10px;
    text-align: center;
    background: #ffffff;
    border-bottom-left-radius: 14px;
    border-bottom-right-radius: 14px;
}
.avatar-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 6px;
}
.node-img {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    border: 2px solid #ffffff;
    outline: 2px solid var(--ring-color, #6366f1);
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    object-fit: cover;
}
.node-name {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 145px;
    margin: 0 auto;
    letter-spacing: -0.01em;
}
.node-user {
    display: inline-block;
    background: #f1f5f9;
    color: #475569;
    font-size: 10.5px;
    font-weight: 600;
    padding: 1px 8px;
    border-radius: 9999px;
    margin-top: 3px;
    max-width: 145px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Dual Metric Chips (L / R) */
.node-metrics {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
    margin-top: 9px;
}
.metric-chip {
    padding: 4px 6px;
    border-radius: 9px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.metric-chip.left {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1d4ed8;
}
.metric-chip.right {
    background: #faf5ff;
    border: 1px solid #e9d5ff;
    color: #7e22ce;
}
.metric-chip .lbl {
    font-size: 9.5px;
    font-weight: 800;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    gap: 2px;
}
.metric-chip .val {
    font-size: 14px;
    font-weight: 800;
    margin-top: 1px;
    line-height: 1.1;
}

/* Matched Pairs Badge */
.node-pair-badge {
    margin-top: 7px;
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border: 1px solid #a7f3d0;
    color: #065f46;
    font-size: 10px;
    font-weight: 800;
    padding: 2.5px 6px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

/* Toggle Expand Button */
.toggle-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: white;
    border-radius: 50%;
    border: 2.5px solid #ffffff;
    font-weight: 800;
    font-size: 11px;
    cursor: pointer;
    position: absolute;
    bottom: -12px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
    box-shadow: 0 3px 8px rgba(79, 70, 229, 0.35);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    text-decoration: none;
    outline: none;
}
.toggle-btn:hover {
    background: linear-gradient(135deg, #4f46e5, #4338ca);
    transform: translateX(-50%) scale(1.2);
    box-shadow: 0 5px 12px rgba(79, 70, 229, 0.5);
}

/* Empty Node Slot */
.tree-node.empty {
    border: 2px dashed #cbd5e1;
    background: rgba(248, 250, 252, 0.75);
    box-shadow: none;
    color: #94a3b8;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px 10px;
    width: 175px;
    min-height: 130px;
    border-radius: 16px;
    transition: all 0.2s;
}
.tree-node.empty:hover {
    background: #eef2ff;
    border-color: #818cf8;
    color: #4f46e5;
    transform: translateY(-3px);
}
.empty-icon {
    font-size: 22px;
    margin-bottom: 6px;
    opacity: 0.7;
}
.empty-label {
    font-size: 11.5px;
    font-weight: 700;
}
.empty-sub {
    font-size: 10px;
    opacity: 0.7;
    margin-top: 2px;
}
</style>

';
include 'layout_header.php';

// Helper to render member card
function render_member($fname, $username, $email, $doj, $active, $referred_by = '') {
    $dot_color  = ($active == 1) ? '#34d399' : '#f87171';
    $badge_text = ($active == 1) ? 'Active' : 'Pending';
    $badge_bg   = ($active == 1) ? 'bg-emerald-500/10' : 'bg-red-500/10';
    $badge_bc   = ($active == 1) ? 'border-emerald-500/20'  : 'border-red-500/20';
    $badge_col  = ($active == 1) ? 'text-emerald-600' : 'text-red-600';
    $ref_html   = $referred_by ? "<span class='text-[10px] text-slate-600 ml-1'>via " . htmlspecialchars($referred_by, ENT_QUOTES, 'UTF-8') . "</span>" : '';
    echo "
    <div class='flex items-start gap-3 p-3.5 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors mb-2 shadow-sm'>
      <div class='w-2 h-2 rounded-full mt-1.5 shrink-0 shadow-[0_0_8px_currentColor]' style='color:{$dot_color}; background-color:{$dot_color}'></div>
      <div class='flex-1 min-w-0'>
        <div class='text-[13px] font-bold text-slate-900 truncate'>" . htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') . " <span class='text-[10px] font-bold text-white bg-indigo-500 px-1.5 py-0.5 rounded ml-1.5 align-middle'>@" . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . "</span></div>
        <div class='text-[11px] text-slate-600 mt-1 truncate'>" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</div>
        <div class='text-[10px] text-slate-600 mt-0.5'>" . htmlspecialchars($doj, ENT_QUOTES, 'UTF-8') . " {$ref_html}</div>
      </div>
      <span class='text-[10px] font-bold px-2 py-0.5 rounded-full {$badge_bg} {$badge_col} border {$badge_bc} shrink-0'>{$badge_text}</span>
    </div>";
}

// ---- Build levels ----
$levels = [];

// Cache packages lookup table once to avoid thousands of N+1 database queries
$all_packages = [];
$pkg_rows = mlmp_pdo_fetch_all($pdo, "SELECT * FROM packages");
foreach ($pkg_rows as $pr) {
    $all_packages[(int)$pr['id']] = $pr;
}

function get_level_members_pdo($pdo, $user, $depth, $level_num, array $all_packages) {
    $results = []; $earnings = 0;
    $stack = [['user'=>$user,'depth'=>0,'ref'=>'']];
    $visited = [];
    while (!empty($stack)) {
        $item = array_shift($stack);
        if ($item['depth'] == $depth - 1) {
            $stmt = $pdo->prepare("SELECT fname,email,doj,active,username,pcktaken FROM affiliateuser WHERE referedby=?");
            $stmt->execute([$item['user']]);
            while ($r = $stmt->fetch()) {
                $ac = $r['active']; $ll = 0;
                $pkgId = (int)$r['pcktaken'];
                if (isset($all_packages[$pkgId]["level{$level_num}"])) {
                    $ll = (float)$all_packages[$pkgId]["level{$level_num}"];
                }
                if ($ac == 1) $earnings += $ll;
                $results[] = ['fname'=>$r['fname'],'username'=>$r['username'],'email'=>$r['email'],'doj'=>$r['doj'],'active'=>$ac,'ref'=>$item['user']];
            }
        } else {
            $stmt = $pdo->prepare("SELECT username, fname FROM affiliateuser WHERE referedby=?");
            $stmt->execute([$item['user']]);
            while ($r = $stmt->fetch()) {
                if (!isset($visited[$r['username']])) {
                    $visited[$r['username']] = true;
                    // Provide the fname as ref for the next depth if needed (matches original logic for L2/L3)
                    $refName = ($item['depth'] == 0) ? $r['fname'] : $r['username']; 
                    $stack[] = ['user'=>$r['username'],'depth'=>$item['depth']+1,'ref'=>$refName];
                }
            }
        }
    }
    return ['members'=>$results,'earn'=>$earnings];
}

for ($i = 1; $i <= 10; $i++) {
    $levels[$i] = get_level_members_pdo($pdo, $_SESSION['username'], $i, $i, $all_packages);
}

$total_network_earnings = 0;
foreach ($levels as $l) {
    $total_network_earnings += $l['earn'];
}

// Tree logic
function is_user_in_binary_network(PDO $pdo, string $candidateUsername, string $rootUsername): bool {
    if ($candidateUsername === $rootUsername) {
        return true;
    }

    // Collect all board IDs belonging to root (including _bc_ boards)
    $boards = mlmp_pdo_fetch_all($pdo, "SELECT Id FROM affiliateuser WHERE username = ? OR username LIKE ? ORDER BY Id ASC", [$rootUsername, $rootUsername . '_bc_%']);
    $rootIds = array_map(fn($b) => (int)$b['Id'], $boards);

    $candidate = mlmp_pdo_fetch($pdo, "SELECT Id, parent_id FROM affiliateuser WHERE username = ? LIMIT 1", [$candidateUsername]);
    if (!$candidate) {
        return false;
    }

    $parentId = (int)($candidate['parent_id'] ?? 0);
    $visited = [];

    while ($parentId > 0 && !isset($visited[$parentId])) {
        if (in_array($parentId, $rootIds)) {
            return true;
        }
        $visited[$parentId] = true;
        $parent = mlmp_pdo_fetch($pdo, "SELECT parent_id FROM affiliateuser WHERE Id = ? LIMIT 1", [$parentId]);
        if (!$parent) {
            break;
        }
        $parentId = (int)($parent['parent_id'] ?? 0);
    }

    return false;
}

$requested_user = trim($_GET['user'] ?? $_SESSION['username']);
$root_user = is_user_in_binary_network($pdo, $requested_user, $_SESSION['username']) ? $requested_user : $_SESSION['username'];
$tree_access_denied = ($requested_user !== $root_user);
$selected_board = $_GET['board'] ?? '1';

// Check if user has multiple boards (e.g. admin or power member with virtual boards)
$allBoards = mlmp_pdo_fetch_all($pdo, "SELECT Id, username FROM affiliateuser WHERE username = ? OR username LIKE ? ORDER BY Id ASC", [$root_user, $root_user . '_bc_%']);
$has_multiple_boards = (count($allBoards) > 1);

if ($selected_board !== 'all' && is_numeric($selected_board)) {
    $b_num = (int)$selected_board;
    if ($b_num > 1) {
        $board_candidate = "{$root_user}_bc_{$b_num}";
        $b_exists = mlmp_pdo_fetch($pdo, "SELECT username FROM affiliateuser WHERE username = ? LIMIT 1", [$board_candidate]);
        if ($b_exists) {
            $root_user = $board_candidate;
        }
    }
}

function getTreeData($pdo, $username, $current_depth, $max_depth, $session_username = '', $board_mode = '1') {
    if ($current_depth > $max_depth) return null;
    $user_data = mlmp_pdo_fetch($pdo, "SELECT Id, fname, username, email, mobile, referedby, active, left_count, right_count, paid_pairs, tamount, position, doj, pcktaken FROM affiliateuser WHERE username=?", [$username]);
    if (!$user_data) return null;
    $node = [
        'id'               => $user_data['Id'],
        'fname'            => $user_data['fname'],
        'username'         => $user_data['username'],
        'email'            => $user_data['email'] ?? '',
        'mobile'           => $user_data['mobile'] ?? '',
        'referedby'        => $user_data['referedby'] ?? '',
        'active'           => $user_data['active'],
        'left_count'       => $user_data['left_count'],
        'right_count'      => $user_data['right_count'],
        'paid_pairs'       => $user_data['paid_pairs'] ?? 0,
        'tamount'          => $user_data['tamount'] ?? 0,
        'position'         => $user_data['position'] ?? '',
        'doj'              => $user_data['doj'] ?? '',
        'pcktaken'         => $user_data['pcktaken'] ?? 1,
        'left'             => null,
        'right'            => null,
        'unilevel_children'=> []
    ];
    if ($current_depth < $max_depth) {
        if ($board_mode === 'all' && $current_depth == 1) {
            $user_boards = mlmp_pdo_fetch_all($pdo, "SELECT Id FROM affiliateuser WHERE username = ? OR username LIKE ? ORDER BY Id ASC", [$username, $username . '_bc_%']);
            if (count($user_boards) > 1) {
                $boardIds = array_map(fn($b) => (int)$b['Id'], $user_boards);
                $in_clause = implode(',', $boardIds);
                $children = mlmp_pdo_fetch_all($pdo, "SELECT username, position FROM affiliateuser WHERE parent_id IN ($in_clause) ORDER BY parent_id ASC, position ASC");
                foreach ($children as $child) {
                    $child_node = getTreeData($pdo, $child['username'], $current_depth + 1, $max_depth, $session_username, 'normal');
                    if ($child_node) {
                        $node['unilevel_children'][] = $child_node;
                    }
                }
                return $node;
            }
        }
        
        // Standard Pure Binary Node (L & R children only)
        $children = mlmp_pdo_fetch_all($pdo, "SELECT username, position FROM affiliateuser WHERE parent_id=? ORDER BY position ASC", [(int)$user_data['Id']]);
        foreach ($children as $child) {
            if ($child['position'] == 'L') {
                $node['left'] = getTreeData($pdo, $child['username'], $current_depth + 1, $max_depth, $session_username, 'normal');
            } elseif ($child['position'] == 'R') {
                $node['right'] = getTreeData($pdo, $child['username'], $current_depth + 1, $max_depth, $session_username, 'normal');
            }
        }
    }
    return $node;
}

$max_depth = isset($_GET['depth']) ? min(12, max(2, (int)$_GET['depth'])) : 10;
$tree_data = getTreeData($pdo, $root_user, 1, $max_depth, $_SESSION['username'], $selected_board);

function renderNodeHTML($node, $pos_label = '', $has_children = false, $is_collapsed = false) {
    if (!$node) {
        $slotText = ($pos_label === 'L') ? 'Left Leg Slot' : (($pos_label === 'R') ? 'Right Leg Slot' : 'Available Slot');
        return "
        <div class='tree-node empty' style='margin-bottom: 25px;'>
            <div class='empty-icon'><i class='fa-solid fa-user-plus'></i></div>
            <div class='empty-label'>{$slotText}</div>
            <div class='empty-sub'>Empty Position</div>
        </div>";
    }

    $isActive = ((int)$node['active'] === 1);
    $statusClass = $isActive ? 'active' : 'inactive';
    $statusText = $isActive ? 'Active Member' : 'Inactive / Pending';
    
    // Position Header Styling
    $posBadgeClass = 'pos-root';
    $posDisplay = '<i class="fa-solid fa-crown text-amber-500"></i> Root';
    $ringColor = '#f59e0b';
    if ($node['position'] === 'L') {
        $posBadgeClass = 'pos-left';
        $posDisplay = '<i class="fa-solid fa-arrow-left"></i> Left Leg';
        $ringColor = '#3b82f6';
    } elseif ($node['position'] === 'R') {
        $posBadgeClass = 'pos-right';
        $posDisplay = 'Right Leg <i class="fa-solid fa-arrow-right"></i>';
        $ringColor = '#8b5cf6';
    }

    $toggle_html = "";
    if ($has_children) {
        $icon = $is_collapsed ? '<i class="fa-solid fa-plus"></i>' : '<i class="fa-solid fa-minus"></i>';
        $toggle_html = "<button class='toggle-btn' onclick='toggleChildren(event, this)' title='Toggle Sub-Tree'>{$icon}</button>";
    }

    $json_data = htmlspecialchars(json_encode([
        'id' => $node['id'],
        'username' => $node['username'],
        'fname' => $node['fname'],
        'email' => $node['email'] ?? '',
        'mobile' => $node['mobile'] ?? '',
        'sponsor' => $node['referedby'] ?: 'None',
        'active' => (int)$node['active'],
        'position' => $node['position'] ? ($node['position'] == 'L' ? 'Left Leg' : 'Right Leg') : 'Root Node',
        'left_count' => (int)$node['left_count'],
        'right_count' => (int)$node['right_count'],
        'paid_pairs' => (int)($node['paid_pairs'] ?? 0),
        'tamount' => number_format((float)($node['tamount'] ?? 0), 2),
        'doj' => !empty($node['doj']) ? date('d M Y', strtotime($node['doj'])) : '-',
    ]), ENT_QUOTES, 'UTF-8');

    $paidPairs = (int)($node['paid_pairs'] ?? 0);
    $pairBadgeHtml = "<div class='node-pair-badge'><i class='fa-solid fa-bullseye'></i> <span>{$paidPairs} Pairs</span></div>";

    return "
    <div style='position:relative; display:inline-block; margin-bottom: 25px;'>
        <div class='tree-node' onclick='openMemberModal(event, {$json_data})' style='cursor: pointer;' title='Click to view member profile'>
            <div class='node-header {$posBadgeClass}'>
                <span class='pos-tag'>{$posDisplay}</span>
                <span class='status-pill {$statusClass}' title='{$statusText}'>
                    <span class='status-dot'></span>
                </span>
            </div>
            <div class='node-body'>
                <div class='avatar-wrapper' style='--ring-color: {$ringColor};'>
                    <img src='images/a0.jpg' class='node-img' onerror=\"this.src='https://ui-avatars.com/api/?name=".urlencode($node['fname'])."&background=random&color=fff&size=100'\" alt='User'>
                </div>
                <div class='node-name' title='".htmlspecialchars($node['fname'], ENT_QUOTES, 'UTF-8')."'>".htmlspecialchars($node['fname'], ENT_QUOTES, 'UTF-8')."</div>
                <div class='node-user'>@".htmlspecialchars($node['username'], ENT_QUOTES, 'UTF-8')."</div>
                
                <div class='node-metrics'>
                    <div class='metric-chip left'>
                        <span class='lbl'><i class='fa-solid fa-arrow-left'></i> L</span>
                        <span class='val'>".$node['left_count']."</span>
                    </div>
                    <div class='metric-chip right'>
                        <span class='lbl'>R <i class='fa-solid fa-arrow-right'></i></span>
                        <span class='val'>".$node['right_count']."</span>
                    </div>
                </div>
                {$pairBadgeHtml}
            </div>
        </div>
        {$toggle_html}
    </div>";
}

function renderTreeHTML($node, $depth = 1) {
    echo "<li>";
    $has_children = ($node && (isset($node['left']) || isset($node['right']) || !empty($node['unilevel_children'])));
    $is_collapsed = ($depth >= 2 && $has_children); // Level 1 is expanded, level 2+ collapsed

    echo renderNodeHTML($node, $node ? ($node['position'] ?: 'Root') : '', $has_children, $is_collapsed);

    if ($node && $has_children) {
        $display = $is_collapsed ? 'style="display:none;"' : '';
        echo "<ul class='nested-tree' {$display}>";
        if (!empty($node['unilevel_children'])) {
            // Render Multi-Board Unilevel Overview Children
            foreach ($node['unilevel_children'] as $uchild) {
                renderTreeHTML($uchild, $depth + 1);
            }
        } else {
            // Render Pure 2-Leg Binary Children
            renderTreeHTML($node['left'] ?? null, $depth + 1);
            renderTreeHTML($node['right'] ?? null, $depth + 1);
        }
        echo "</ul>";
    }
    echo "</li>";
}
?>

<div style="display:flex;gap:10px;margin-bottom:20px;justify-content:center;">
  <button id="tab-list-view" onclick="switchMainView('list')" style="padding:10px 20px;border-radius:8px;background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:none;cursor:pointer;font-weight:600;font-size:14px;transition:all 0.3s;"><i class="fa-solid fa-list"></i> List View (Unilevel)</button>
  <button id="tab-tree-view" onclick="switchMainView('tree')" style="padding:10px 20px;border-radius:8px;background:#f8fafc;color:#64748b;border:1px solid #e2e8f0;cursor:pointer;font-weight:600;font-size:14px;transition:all 0.3s;"><i class="fa-solid fa-sitemap"></i> Tree View (Binary)</button>
</div>

<!-- TREE VIEW SECTION -->
<div id="main-tree-view" style="display:none;">
    <?php if (!empty($tree_access_denied)): ?>
        <div class="bg-amber-50 border border-amber-200 text-amber-700 rounded-xl p-4 mb-4 text-sm font-semibold flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation"></i>
            You can only view members inside your own binary network.
        </div>
    <?php endif; ?>
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col mb-6">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-3 bg-slate-50 rounded-t-2xl">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0"><i class="fa-solid fa-sitemap"></i></span>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 m-0 leading-tight">Binary Network Tree</h3>
                    <span class="text-[11px] text-slate-500 font-medium">Click & drag to pan • Up to Level 10 enabled</span>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">

                <!-- Zoom Controls -->
                <div class="inline-flex items-center bg-white border border-slate-200 rounded-full p-0.5 shadow-sm">
                    <button type="button" onclick="zoomTree(-0.15)" class="w-7 h-7 flex items-center justify-center text-slate-600 hover:text-indigo-600 hover:bg-slate-100 rounded-full transition-colors" title="Zoom Out"><i class="fa-solid fa-minus text-xs"></i></button>
                    <span id="zoomPercent" class="text-[11px] font-bold text-slate-700 px-2 select-none">100%</span>
                    <button type="button" onclick="zoomTree(0.15)" class="w-7 h-7 flex items-center justify-center text-slate-600 hover:text-indigo-600 hover:bg-slate-100 rounded-full transition-colors" title="Zoom In"><i class="fa-solid fa-plus text-xs"></i></button>
                    <button type="button" onclick="zoomTree(0)" class="text-[11px] font-semibold text-slate-500 hover:text-slate-800 px-2 py-0.5 hover:bg-slate-100 rounded-full transition-colors border-l border-slate-200" title="Reset Zoom">100%</button>
                    <button type="button" onclick="fitTree()" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 px-2 py-0.5 hover:bg-indigo-50 rounded-full transition-colors border-l border-slate-200" title="Fit Tree View">Fit</button>
                </div>

                <!-- Tree Expand / Collapse -->
                <button onclick="expandAllTree()" type="button" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-600 border border-indigo-200 px-3 py-1.5 rounded-full text-xs font-bold transition-colors whitespace-nowrap flex items-center gap-1.5" title="Expand all branches"><i class="fa-solid fa-expand"></i> Expand All</button>
                <button onclick="collapseAllTree()" type="button" class="bg-red-500/10 hover:bg-red-500/20 text-red-600 border border-red-500/20 px-3 py-1.5 rounded-full text-xs font-bold transition-colors whitespace-nowrap flex items-center gap-1.5" title="Collapse nested branches"><i class="fa-solid fa-compress"></i> Collapse All</button>

                <!-- Fullscreen -->
                <button onclick="toggleTreeFullscreen()" type="button" class="bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 px-3 py-1.5 rounded-full text-xs font-bold transition-colors whitespace-nowrap flex items-center gap-1.5" title="View Fullscreen"><i class="fa-solid fa-maximize"></i> Fullscreen</button>

                <!-- Search -->
                <form method="GET" action="downline.php" class="flex items-center bg-white border border-slate-200 rounded-full px-3 py-1 focus-within:border-indigo-500 shadow-sm max-w-[210px]">
                    <input type="hidden" name="view" value="tree">
                    <i class="fa-solid fa-search text-slate-400 text-xs mr-2"></i>
                    <input type="text" name="user" placeholder="Search user..." class="bg-transparent border-none outline-none text-slate-900 text-xs flex-1 min-w-0 placeholder:text-slate-400">
                    <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-800 text-[11px] font-bold py-0.5 px-2 rounded-full transition-colors ml-1">Go</button>
                </form>

                <!-- Export CSV -->
                <a href="downline.php?export=csv" class="bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 border border-emerald-500/20 px-3 py-1.5 rounded-full text-xs font-bold transition-colors whitespace-nowrap flex items-center gap-1.5 no-underline" title="Download Excel/CSV Report"><i class="fa-solid fa-file-excel"></i> Export CSV</a>
                <?php if ($root_user !== $_SESSION['username']): ?>
                    <a href="downline.php?view=tree" class="bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 px-3 py-1.5 rounded-full text-xs font-bold transition-colors whitespace-nowrap flex items-center gap-1.5 no-underline"><i class="fa-solid fa-arrow-left"></i> Root</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="p-3 sm:p-5">
            <!-- Board Switcher & Visual Legend Bar -->
            <div class="flex items-center justify-between flex-wrap gap-3 mb-4 px-1">
                <?php if ($has_multiple_boards): ?>
                    <div class="flex items-center gap-1.5 flex-wrap bg-slate-100/90 p-1.5 rounded-xl border border-slate-200 shadow-sm">
                        <span class="text-xs font-bold text-slate-500 px-2 flex items-center gap-1.5"><i class="fa-solid fa-layer-group text-indigo-500"></i> Board:</span>
                        <?php foreach ($allBoards as $idx => $bNode): 
                            $bNum = $idx + 1;
                            $bActive = ($selected_board == (string)$bNum) ? 'active' : '';
                            $bLabel = ($bNum === 1) ? '👑 Board 1 (Main)' : "⚡ Board {$bNum}";
                        ?>
                            <a href="downline.php?view=tree&board=<?= $bNum ?><?= ($requested_user !== $_SESSION['username']) ? '&user=' . urlencode($requested_user) : '' ?>" class="board-chip <?= $bActive ?>"><?= $bLabel ?></a>
                        <?php endforeach; ?>
                        <a href="downline.php?view=tree&board=all<?= ($requested_user !== $_SESSION['username']) ? '&user=' . urlencode($requested_user) : '' ?>" class="board-chip <?= ($selected_board == 'all') ? 'active' : '' ?>"><i class="fa-solid fa-table-cells"></i> View All Directs</a>
                    </div>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>

                <!-- Modern Visual Legend -->
                <div class="flex items-center gap-3.5 text-xs font-semibold text-slate-600 bg-white px-3.5 py-1.5 rounded-xl border border-slate-200 shadow-sm flex-wrap">
                    <span class="flex items-center gap-1.5 text-blue-600"><span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block shadow-sm"></span> Left Leg</span>
                    <span class="flex items-center gap-1.5 text-purple-600"><span class="w-2.5 h-2.5 rounded-full bg-purple-500 inline-block shadow-sm"></span> Right Leg</span>
                    <span class="flex items-center gap-1.5 text-emerald-600"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block shadow-sm"></span> Active</span>
                    <span class="flex items-center gap-1.5 text-amber-600"><span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block shadow-sm"></span> Root</span>
                </div>
            </div>

            <?php if ($tree_data): ?>
                <div class="tree-container" id="treeContainer">
                    <div class="tree-wrapper" id="treeWrapper">
                        <div class="tree">
                            <ul>
                                <?php renderTreeHTML($tree_data, 1); ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div style="padding: 40px; text-align: center; color: #64748b;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 32px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                    User not found or no tree data available.
                </div>
            <?php endif; ?>
        </div>
        
        <div class="px-5 py-4 border-t border-slate-100 bg-slate-50 rounded-b-2xl">
            <div style="display:flex; gap: 20px; font-size: 12px; color: #64748b; justify-content: center; flex-wrap: wrap;">
                <div style="display:flex; align-items:center; gap: 6px;">
                    <i class="fa-solid fa-arrow-pointer" style="color: #6366f1;"></i> Click on any card to view detailed performance profile
                </div>
                <div style="display:flex; align-items:center; gap: 6px;">
                    <i class="fa-solid fa-hand" style="color: #64748b;"></i> Click & drag to pan around tree canvas
                </div>
                <div style="display:flex; align-items:center; gap: 6px;">
                    <i class="fa-solid fa-circle-nodes" style="color: #059669;"></i> Real-time synchronized binary pair balances
                </div>
            </div>
        </div>
    </div>
</div>

<!-- LIST VIEW SECTION -->
<div id="main-list-view">
    <?php
    $member_stat = mlmp_pdo_fetch($pdo, "SELECT Id, tamount, left_count, right_count, paid_pairs FROM affiliateuser WHERE username = ?", [$_SESSION['username']]);
    $member_wallet = (float)($member_stat['tamount'] ?? 0);
    $member_left = (int)($member_stat['left_count'] ?? 0);
    $member_right = (int)($member_stat['right_count'] ?? 0);
    $member_paid_pairs = (int)($member_stat['paid_pairs'] ?? 0);

    $stmt_mpair = $pdo->prepare("SELECT COALESCE(SUM(net_amount), 0) as pair_net FROM pairing_transactions WHERE user_id = ?");
    $stmt_mpair->execute([(int)$member_stat['Id']]);
    $member_pair_net = (float)($stmt_mpair->fetch()['pair_net'] ?? 0);
    $member_bonus_net = max(0.0, $member_wallet - $member_pair_net);
    ?>

    <!-- Financial Clarity & Earnings Breakdown Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- 1. Total Wallet Balance -->
        <div class="bg-gradient-to-br from-indigo-600 to-purple-700 text-white rounded-2xl p-5 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-indigo-100">Total Wallet Balance</span>
                <i class="fa-solid fa-wallet text-xl opacity-40"></i>
            </div>
            <div class="text-2xl font-black tracking-tight">₹<?php echo number_format($member_wallet, 2); ?></div>
            <div class="mt-2 text-[11px] text-indigo-200 flex items-center gap-1">
                <i class="fa-solid fa-circle-check text-emerald-300"></i> Actual Credited & Withdrawable
            </div>
        </div>

        <!-- 2. Binary Pair Earnings -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Binary Matching Pairs</span>
                <span class="text-[10px] font-bold bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-full border border-indigo-100"><?php echo $member_paid_pairs; ?> Pairs</span>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">₹<?php echo number_format($member_pair_net, 2); ?></div>
            <div class="mt-2 text-[11px] text-slate-500 flex items-center justify-between">
                <span>Left: <b class="text-slate-800"><?php echo $member_left; ?></b> | Right: <b class="text-slate-800"><?php echo $member_right; ?></b></span>
                <span class="text-emerald-600 font-medium">Daily Limit: 10</span>
            </div>
        </div>

        <!-- 3. Direct Referral Bonus -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Direct Sponsor Bonus</span>
                <i class="fa-solid fa-gift text-slate-400"></i>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">₹<?php echo number_format($member_bonus_net, 2); ?></div>
            <div class="mt-2 text-[11px] text-slate-500">
                ₹50 per direct sponsored member
            </div>
        </div>

        <!-- 4. Unilevel Team Volume -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Unilevel Team Volume</span>
                <span class="text-[10px] font-bold bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded-full border border-emerald-100">10 Levels</span>
            </div>
            <div class="text-2xl font-black text-emerald-600 tracking-tight">₹<?php echo number_format($total_network_earnings, 2); ?></div>
            <div class="mt-2 text-[11px] text-slate-500">
                Calculated package level commission
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div style="display:flex;gap:16px;align-items:center;margin-bottom:20px;font-size:12px;font-weight:500;color:#64748b;">
      <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#22c55e;margin-right:5px;"></span>Active / Paid</span>
      <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#ef4444;margin-right:5px;"></span>Pending / Unpaid</span>
      <span style="margin-left:auto;font-size:11px;color:#334155;">Currency: <strong style="color:#a78bfa;"><?php echo htmlspecialchars($pckcur, ENT_QUOTES, 'UTF-8'); ?></strong></span>
    </div>

    <!-- Tabs for levels -->
    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px;" id="levelTabs">
      <?php for ($i = 1; $i <= 10; $i++):
        $cnt = count($levels[$i]['members']);
        $is_active = ($i === 1);
      ?>
      <button onclick="showLevel(<?php echo $i; ?>)"
              id="tab<?php echo $i; ?>"
              style="background:<?php echo $is_active ? 'linear-gradient(135deg,#7c3aed,#5b21b6)' : '#f8fafc'; ?>;
                     color:<?php echo $is_active ? '#fff' : '#64748b'; ?>;
                     border:1px solid <?php echo $is_active ? 'transparent' : '#e2e8f0'; ?>;
                     border-radius:8px;padding:7px 14px;font-size:12px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;
                     display:flex;align-items:center;gap:6px;transition:all 0.2s;">
        Level <?php echo $i; ?>
        <span style="background:<?php echo $cnt>0 ? 'rgba(124,58,237,0.15)' : '#e2e8f0'; ?>;
                     color:<?php echo $cnt>0 ? '#6d28d9' : '#64748b'; ?>;
                     border-radius:20px;padding:1px 7px;font-size:10px;"><?php echo $cnt; ?></span>
      </button>
      <?php endfor; ?>
    </div>

    <!-- Level panels -->
    <?php for ($i = 1; $i <= 10; $i++):
      $ldata = $levels[$i];
      $members = $ldata['members'];
      $earn    = $ldata['earn'];
    ?>
    <div id="level<?php echo $i; ?>" style="display:<?php echo $i === 1 ? 'block' : 'none'; ?>;">
      <div class="bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col mb-6">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-4">
          <div class="text-sm font-bold text-slate-900 flex items-center gap-2.5">
            <span class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center text-[13px]"><i class="fa-solid fa-layer-group"></i></span>
            Level <?php echo $i; ?> Downline
          </div>
          <div class="flex items-center gap-2.5">
            <span class="text-xs text-slate-700 font-medium bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-md"><?php echo count($members); ?> members</span>
            <span class="text-xs text-emerald-600 font-bold bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-200">+ <?php echo htmlspecialchars($pckcur, ENT_QUOTES, 'UTF-8'); ?> <?php echo number_format($earn, 2); ?></span>
          </div>
        </div>
        <div class="p-5 flex flex-col gap-1.5">
          <?php if (empty($members)): ?>
            <div style="text-align:center;padding:40px 20px;color:#334155;">
              <i class="fa-solid fa-users-slash" style="font-size:36px;display:block;margin-bottom:12px;color:#1e293b;"></i>
              No members at Level <?php echo $i; ?> yet.
            </div>
          <?php else:
            foreach ($members as $m):
              render_member($m['fname'], $m['username'], $m['email'], $m['doj'], $m['active'], $m['ref']);
            endforeach;
          endif; ?>
        </div>
      </div>
    </div>
    <?php endfor; ?>
</div>

<script>
function showLevel(n) {
    for (var i = 1; i <= 10; i++) {
        var panel = document.getElementById('level' + i);
        var tab   = document.getElementById('tab' + i);
        if (i === n) {
            panel.style.display = 'block';
            tab.style.background = 'linear-gradient(135deg,#7c3aed,#5b21b6)';
            tab.style.color = '#fff';
            tab.style.borderColor = 'transparent';
        } else {
            panel.style.display = 'none';
            tab.style.background = '#f8fafc';
            tab.style.color = '#64748b';
            tab.style.borderColor = '#e2e8f0';
        }
    }
}

function switchMainView(view) {
    var list = document.getElementById("main-list-view");
    var tree = document.getElementById("main-tree-view");
    var btnList = document.getElementById("tab-list-view");
    var btnTree = document.getElementById("tab-tree-view");
    
    const url = new URL(window.location);
    
    if(view === "list") {
        list.style.display = "block";
        tree.style.display = "none";
        btnList.style.background = "linear-gradient(135deg,#7c3aed,#5b21b6)"; btnList.style.color = "#fff"; btnList.style.border = "none";
        btnTree.style.background = "#f8fafc"; btnTree.style.color = "#64748b"; btnTree.style.border = "1px solid #e2e8f0";
        url.searchParams.delete('view');
    } else {
        list.style.display = "none";
        tree.style.display = "block";
        btnTree.style.background = "linear-gradient(135deg,#7c3aed,#5b21b6)"; btnTree.style.color = "#fff"; btnTree.style.border = "none";
        btnList.style.background = "#f8fafc"; btnList.style.color = "#64748b"; btnList.style.border = "1px solid #e2e8f0";
        url.searchParams.set('view', 'tree');
    }
    
    window.history.replaceState({}, '', url);
}

// Auto-switch to tree view if the URL has ?view=tree
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('view') === 'tree') {
    switchMainView('tree');
}

// Handle Click-to-Expand functionality
function toggleChildren(e, btn) {
    e.preventDefault();
    e.stopPropagation();
    
    // Find the next sibling <ul> element inside the parent <li>
    const li = btn.closest('li');
    const ul = li.querySelector(':scope > ul');
    
    if (ul) {
        if (ul.style.display === 'none') {
            ul.style.display = 'flex';
            btn.innerHTML = '-';
        } else {
            ul.style.display = 'none';
            btn.innerHTML = '+';
        }
    }
}

function collapseAllTree() {
    const treeContainer = document.getElementById('main-tree-view');
    if (!treeContainer) return;
    
    // Hide all nested lists
    const nestedLists = treeContainer.querySelectorAll('ul.nested-tree');
    nestedLists.forEach(ul => {
        ul.style.display = 'none';
    });
    
    // Reset all toggle buttons to '+'
    const toggleBtns = treeContainer.querySelectorAll('.toggle-btn');
    toggleBtns.forEach(btn => {
        btn.innerHTML = '+';
    });
}

function expandAllTree() {
    const treeContainer = document.getElementById('main-tree-view');
    if (!treeContainer) return;
    
    const nestedLists = treeContainer.querySelectorAll('ul.nested-tree');
    nestedLists.forEach(ul => {
        ul.style.display = 'flex';
    });
    
    const toggleBtns = treeContainer.querySelectorAll('.toggle-btn');
    toggleBtns.forEach(btn => {
        btn.innerHTML = '-';
    });
}

// Tree Zoom & Pan System
let currentZoom = 1.0;
const minZoom = 0.35;
const maxZoom = 1.8;

function applyZoom() {
    const wrapper = document.getElementById('treeWrapper');
    const zoomText = document.getElementById('zoomPercent');
    if (wrapper) {
        wrapper.style.transform = 'scale(' + currentZoom + ')';
    }
    if (zoomText) {
        zoomText.innerText = Math.round(currentZoom * 100) + '%';
    }
}

function zoomTree(delta) {
    if (delta === 0) {
        currentZoom = 1.0;
    } else {
        currentZoom = Math.min(maxZoom, Math.max(minZoom, parseFloat((currentZoom + delta).toFixed(2))));
    }
    applyZoom();
}

function fitTree() {
    const container = document.getElementById('treeContainer');
    const wrapper = document.getElementById('treeWrapper');
    if (container && wrapper) {
        const treeElem = wrapper.querySelector('.tree');
        if (treeElem) {
            const treeW = treeElem.scrollWidth || treeElem.offsetWidth;
            const contW = container.clientWidth - 40;
            if (treeW > 0 && contW > 0) {
                currentZoom = Math.min(1.0, Math.max(minZoom, parseFloat((contW / treeW).toFixed(2))));
                applyZoom();
                centerTree();
                return;
            }
        }
    }
    currentZoom = 0.55;
    applyZoom();
    centerTree();
}

function centerTree() {
    const container = document.getElementById('treeContainer');
    if (container) {
        setTimeout(function() {
            container.scrollLeft = (container.scrollWidth - container.clientWidth) / 2;
        }, 120);
    }
}

function toggleTreeFullscreen() {
    const elem = document.getElementById('treeContainer');
    if (!elem) return;
    if (!document.fullscreenElement) {
        elem.requestFullscreen().catch(err => {
            console.warn('Fullscreen error:', err);
        });
    } else {
        document.exitFullscreen();
    }
}

// Mouse drag-to-pan
(function initTreePan() {
    window.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('treeContainer');
        if (!container) return;

        let isDown = false;
        let startX, startY, scrollLeft, scrollTop;

        container.addEventListener('mousedown', function(e) {
            if (e.target.closest('.toggle-btn') || e.target.closest('.tree-node') || e.target.closest('button') || e.target.closest('a')) {
                return;
            }
            isDown = true;
            container.classList.add('grabbing');
            startX = e.pageX - container.offsetLeft;
            startY = e.pageY - container.offsetTop;
            scrollLeft = container.scrollLeft;
            scrollTop = container.scrollTop;
        });

        window.addEventListener('mouseup', function() {
            isDown = false;
            if (container) container.classList.remove('grabbing');
        });

        container.addEventListener('mousemove', function(e) {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - container.offsetLeft;
            const y = e.pageY - container.offsetTop;
            const walkX = (x - startX) * 1.3;
            const walkY = (y - startY) * 1.3;
            container.scrollLeft = scrollLeft - walkX;
            container.scrollTop = scrollTop - walkY;
        });

        centerTree();
    });
})();

// Member Quick Profile Modal Logic
function openMemberModal(e, data) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    if (!data) return;

    document.getElementById('modalAvatar').src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(data.fname) + '&background=random&color=fff&size=120';
    document.getElementById('modalFname').innerText = data.fname;
    document.getElementById('modalUsername').innerText = '@' + data.username;
    document.getElementById('modalPositionBadge').innerText = data.position;
    
    // Status
    const statusDot = document.getElementById('modalStatusBadge');
    if (data.active === 1) {
        statusDot.style.background = '#10b981';
        statusDot.title = 'Active Member';
    } else {
        statusDot.style.background = '#ef4444';
        statusDot.title = 'Inactive Member';
    }

    document.getElementById('modalLeftCount').innerText = data.left_count;
    document.getElementById('modalRightCount').innerText = data.right_count;
    document.getElementById('modalPaidPairs').innerText = data.paid_pairs;
    document.getElementById('modalSponsor').innerText = '@' + data.sponsor;
    document.getElementById('modalEarnings').innerText = '₹' + data.tamount;
    document.getElementById('modalDoj').innerText = data.doj;
    document.getElementById('modalEmail').innerText = data.email || 'Not Provided';
    document.getElementById('modalDrillBtn').href = 'downline.php?user=' + encodeURIComponent(data.username) + '&view=tree';

    const modal = document.getElementById('memberDetailsModal');
    modal.style.display = 'flex';
}

function closeMemberModal() {
    const modal = document.getElementById('memberDetailsModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal on Escape key or outside click
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeMemberModal();
});
</script>

<!-- MEMBER DETAILS MODAL -->
<div id="memberDetailsModal" onclick="if(event.target===this) closeMemberModal();" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(15, 23, 42, 0.65); backdrop-filter:blur(6px); align-items:center; justify-content:center; padding:20px;">
    <div style="background:#ffffff; border-radius:24px; max-width:480px; width:100%; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); overflow:hidden; border:1px solid #e2e8f0;">
        <!-- Modal Header -->
        <div style="background:linear-gradient(135deg, #1e1b4b, #312e81); padding:24px; color:#fff; position:relative;">
            <button onclick="closeMemberModal()" style="position:absolute; top:18px; right:18px; background:rgba(255,255,255,0.15); border:none; color:#fff; width:32px; height:32px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:14px; transition:background 0.2s;">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div style="display:flex; align-items:center; gap:16px;">
                <div style="position:relative;">
                    <img id="modalAvatar" src="" style="width:60px; height:60px; border-radius:50%; border:3px solid #fff; box-shadow:0 4px 10px rgba(0,0,0,0.2); object-fit:cover;">
                    <span id="modalStatusBadge" style="position:absolute; bottom:0; right:0; width:16px; height:16px; border-radius:50%; border:2px solid #fff; background:#10b981;"></span>
                </div>
                <div>
                    <h3 id="modalFname" style="margin:0; font-size:18px; font-weight:800; color:#fff;"></h3>
                    <div id="modalUsername" style="font-size:13px; color:#a5b4fc; font-weight:600; margin-top:2px;"></div>
                    <span id="modalPositionBadge" style="display:inline-block; margin-top:6px; font-size:11px; font-weight:700; background:rgba(255,255,255,0.2); padding:2px 8px; border-radius:9999px;"></span>
                </div>
            </div>
        </div>

        <!-- Modal Body -->
        <div style="padding:24px;">
            <!-- Performance Stats Grid -->
            <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; margin-bottom:20px;">
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:12px; text-align:center;">
                    <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Left Leg</div>
                    <div id="modalLeftCount" style="font-size:20px; font-weight:800; color:#0f172a; margin-top:4px;">0</div>
                </div>
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:12px; text-align:center;">
                    <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Right Leg</div>
                    <div id="modalRightCount" style="font-size:20px; font-weight:800; color:#0f172a; margin-top:4px;">0</div>
                </div>
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:12px; text-align:center;">
                    <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Paid Pairs</div>
                    <div id="modalPaidPairs" style="font-size:20px; font-weight:800; color:#7c3aed; margin-top:4px;">0</div>
                </div>
            </div>

            <!-- Detail List -->
            <div style="background:#f1f5f9; border-radius:14px; padding:14px; font-size:13px; color:#334155; display:flex; flex-direction:column; gap:8px; margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:#64748b; font-weight:600;"><i class="fa-solid fa-user-tag" style="width:16px;"></i> Sponsor:</span>
                    <strong id="modalSponsor" style="color:#1e293b;"></strong>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:#64748b; font-weight:600;"><i class="fa-solid fa-box-open" style="width:16px;"></i> Package:</span>
                    <strong style="color:#1e293b;">Starter Plan (₹1,000)</strong>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:#64748b; font-weight:600;"><i class="fa-solid fa-wallet" style="width:16px;"></i> Total Earnings:</span>
                    <strong id="modalEarnings" style="color:#059669; font-weight:800;"></strong>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:#64748b; font-weight:600;"><i class="fa-solid fa-calendar" style="width:16px;"></i> Joined On:</span>
                    <strong id="modalDoj" style="color:#1e293b;"></strong>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:#64748b; font-weight:600;"><i class="fa-solid fa-envelope" style="width:16px;"></i> Email:</span>
                    <span id="modalEmail" style="color:#1e293b; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></span>
                </div>
            </div>

            <!-- Actions -->
            <div style="display:flex; gap:10px;">
                <a id="modalDrillBtn" href="#" style="flex:1; background:linear-gradient(135deg,#7c3aed,#5b21b6); color:#fff; font-weight:700; font-size:13px; padding:12px; border-radius:12px; text-align:center; text-decoration:none; box-shadow:0 4px 12px rgba(124,58,237,0.3); transition:transform 0.2s;">
                    <i class="fa-solid fa-sitemap mr-1"></i> View Tree as Root
                </a>
                <button onclick="closeMemberModal()" style="background:#e2e8f0; color:#334155; font-weight:700; font-size:13px; padding:12px 20px; border-radius:12px; border:none; cursor:pointer;">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'layout_footer.php'; ?>
