<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['adminidusername'])) {
    echo "<script>window.location='index.php';</script>"; exit;
}

// Fetch user's package & currency info
$urow = mysqli_fetch_array(mysqli_query($con, "SELECT pcktaken FROM affiliateuser WHERE username='" . $_SESSION['adminidusername'] . "'"));
$pcktaken = $urow['pcktaken'] ?? 0;
$pckcur = '';
$l1=$l2=$l3=$l4=$l5=$l6=$l7=$l8=$l9=$l10 = 0;
if ($pcktaken) {
    $re = mysqli_query($con, "SELECT * FROM packages WHERE id=$pcktaken");
    $r  = mysqli_fetch_array($re);
    if ($r) {
        $pckcur = $r['currency'];
        $l1=$r['level1']; $l2=$r['level2']; $l3=$r['level3'];  $l4=$r['level4'];
        $l5=$r['level5']; $l6=$r['level6']; $l7=$r['level7'];  $l8=$r['level8'];
        $l9=$r['level9']; $l10=$r['level10'];
    }
}

$page_title = "My Network";
$active_nav = "downline";
$extra_head = '
<style>
/* PREMIUM CSS for Binary Tree View */
.tree-container {
    overflow-x: auto;
    padding: 40px 20px;
    text-align: center;
    background: #f8fafc;
    border-radius: 12px;
}
.toggle-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: #6366f1;
    color: white;
    border-radius: 50%;
    border: 2px solid #fff;
    font-weight: bold;
    cursor: pointer;
    position: absolute;
    bottom: -12px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.2s;
    text-decoration: none;
}
.toggle-btn:hover {
    background: #4f46e5;
    transform: translateX(-50%) scale(1.1);
}
}
.tree {
    display: inline-flex;
    justify-content: center;
}
.tree ul {
    padding-top: 25px; position: relative;
    transition: all 0.5s;
    display: flex;
    justify-content: center;
    padding-left: 0;
    margin: 0;
}
.tree li {
    float: left; text-align: center;
    list-style-type: none;
    position: relative;
    padding: 25px 8px 0 8px;
    transition: all 0.5s;
    display: flex;
    flex-direction: column;
    align-items: center;
}
/* Connectors */
.tree li::before, .tree li::after {
    content: "";
    position: absolute; top: 0; right: 50%;
    border-top: 2px solid #cbd5e1;
    width: 50%; height: 25px;
}
.tree li::after {
    right: auto; left: 50%;
    border-left: 2px solid #cbd5e1;
}
.tree li:only-child::after, .tree li:only-child::before {
    display: none;
}
.tree li:only-child { padding-top: 0; }
.tree li:first-child::before, .tree li:last-child::after {
    border: 0 none;
}
.tree li:last-child::before {
    border-right: 2px solid #cbd5e1;
    border-radius: 0 6px 0 0;
}
.tree li:first-child::after {
    border-radius: 6px 0 0 0;
}
.tree ul ul::before {
    content: "";
    position: absolute; top: 0; left: 50%;
    border-left: 2px solid #cbd5e1;
    width: 0; height: 25px;
    transform: translateX(-50%);
}
.tree-node {
    text-decoration: none;
    color: #1e293b;
    font-size: 12px;
    display: inline-block;
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: #ffffff;
    min-width: 140px;
    border: 1px solid rgba(226, 232, 240, 0.8);
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
    position: relative;
    z-index: 1;
    overflow: visible; /* changed from hidden to let toggle button overflow */
}
.tree-node:hover {
    box-shadow: 0 10px 25px -5px rgba(124, 58, 237, 0.25);
    border-color: #a78bfa;
    transform: translateY(-4px) scale(1.02);
}
.node-header {
    padding: 8px 12px;
    font-weight: 700;
    font-size: 11px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
.node-header.active {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.15));
    color: #059669;
}
.node-header.inactive {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.15));
    color: #dc2626;
}
.node-body {
    padding: 12px;
    text-align: center;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}
/* Custom Search Bar - Google Chrome Style */
.ts-form {
    display: flex;
    align-items: center;
    background: #ffffff;
    border: 1px solid #dfe1e5;
    border-radius: 9999px;
    padding: 4px 4px 4px 14px;
    width: 250px;
    max-width: 100%;
    box-shadow: 0 1px 3px rgba(32, 33, 36, 0.08);
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}
.ts-form:hover {
    box-shadow: 0 2px 5px rgba(32, 33, 36, 0.15);
}
.ts-form:focus-within {
    border-color: #d2e3fc;
    box-shadow: 0 1px 4px rgba(32, 33, 36, 0.2);
}
.ts-icon {
    color: #5f6368;
    font-size: 14px;
    margin-right: 8px;
}
.ts-input {
    border: none !important;
    background: transparent !important;
    outline: none !important;
    box-shadow: none !important;
    flex: 1;
    min-width: 0;
    font-size: 13px;
    color: #202124;
    font-family: "Inter", sans-serif;
    padding: 2px 0;
    height: auto;
}
.ts-input::placeholder {
    color: #5f6368;
    opacity: 0.8;
}
.ts-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #f1f3f4;
    color: #202124;
    border: none;
    border-radius: 9999px;
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
    white-space: nowrap;
}
.ts-btn:hover {
    background: #e8eaed;
}
.dash-panel-header-tree {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
}
.node-img {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    margin-bottom: 8px;
    border: 2px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    object-fit: cover;
}
.node-name {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 120px;
    margin: 0 auto;
}
.node-user {
    color: #64748b;
    font-size: 11px;
    margin-top: 2px;
    font-weight: 500;
}
.node-stats {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-top: 10px;
    padding-top: 8px;
    border-top: 1px solid rgba(226, 232, 240, 0.6);
}
.stat-box {
    font-size: 10px;
    color: #64748b;
    font-weight: 600;
}
.stat-box span { 
    font-weight: 800; 
    color: #1e293b; 
    display: block; 
    font-size: 14px;
    margin-top: 2px;
}
.tree-node.empty {
    border: 2px dashed #cbd5e1;
    background: transparent;
    box-shadow: none;
    color: #94a3b8;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    min-height: 120px;
    border-radius: 12px;
}
.tree-node.empty:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
</style>
';
include 'layout_header.php';

// Helper to render member card
function render_member($fname, $username, $email, $doj, $active, $referred_by = '') {
    $dot_color  = ($active == 1) ? '#22c55e' : '#ef4444';
    $badge_text = ($active == 1) ? '✅ Active' : '⏳ Pending';
    $badge_bg   = ($active == 1) ? 'rgba(5,150,105,0.15)' : 'rgba(239,68,68,0.15)';
    $badge_bc   = ($active == 1) ? 'rgba(5,150,105,0.3)'  : 'rgba(239,68,68,0.3)';
    $badge_col  = ($active == 1) ? '#34d399' : '#f87171';
    $ref_html   = $referred_by ? "<span style='font-size:10px;color:#475569;'>via " . htmlspecialchars($referred_by, ENT_QUOTES, 'UTF-8') . "</span>" : '';
    echo "
    <div style='display:flex;align-items:flex-start;gap:12px;padding:12px 14px;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:8px;transition:background 0.15s;box-shadow:0 1px 2px rgba(0,0,0,0.05);'
         onmouseover=\"this.style.background='#f8fafc'\" onmouseout=\"this.style.background='#ffffff'\">
      <div style='width:8px;height:8px;border-radius:50%;background:{$dot_color};margin-top:5px;flex-shrink:0;box-shadow:0 0 6px {$dot_color};'></div>
      <div style='flex:1;min-width:0;'>
        <div style='font-size:13px;font-weight:600;color:#102a43;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;'>" . htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') . " <span style='font-size:10px;font-weight:700;color:#fff;background:#6366f1;padding:2px 6px;border-radius:4px;margin-left:6px;vertical-align:middle;'>@" . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . "</span></div>
        <div style='font-size:11px;color:#475569;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;'>" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</div>
        <div style='font-size:10px;color:#64748b;margin-top:2px;'>" . htmlspecialchars($doj, ENT_QUOTES, 'UTF-8') . " {$ref_html}</div>
      </div>
      <span style='font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;background:{$badge_bg};color:{$badge_col};border:1px solid {$badge_bc};flex-shrink:0;'>{$badge_text}</span>
    </div>";
}

// ---- Build levels ----
$levels = [];

// Level 1
$lv1 = []; $totalrefear1 = 0;
$r1 = mysqli_query($con, "SELECT fname,email,doj,active,username,pcktaken FROM affiliateuser WHERE referedby='".$_SESSION['adminidusername']."'");
while ($row = mysqli_fetch_array($r1)) {
    $ac = $row['active']; $ll = 0;
    $rp = mysqli_fetch_array(mysqli_query($con, "SELECT level1 FROM packages WHERE id=" . (int)$row['pcktaken']));
    if ($rp) $ll = $rp['level1'];
    if ($ac == 1) $totalrefear1 += $ll;
    $lv1[] = ['fname'=>$row['fname'],'username'=>$row['username'],'email'=>$row['email'],'doj'=>$row['doj'],'active'=>$ac,'ref'=>''];
}
$levels[1] = ['members'=>$lv1,'earn'=>$totalrefear1];

// Level 2
$lv2 = []; $totalrefear2 = 0;
$r1 = mysqli_query($con, "SELECT username,fname FROM affiliateuser WHERE referedby='".$_SESSION['adminidusername']."'");
while ($row = mysqli_fetch_array($r1)) {
    $r2 = mysqli_query($con, "SELECT fname,email,doj,active,username,pcktaken FROM affiliateuser WHERE referedby='".$row['username']."'");
    while ($row2 = mysqli_fetch_array($r2)) {
        $ac2 = $row2['active']; $ll2 = 0;
        $rp = mysqli_fetch_array(mysqli_query($con, "SELECT level2 FROM packages WHERE id=".(int)$row2['pcktaken']));
        if ($rp) $ll2 = $rp['level2'];
        if ($ac2 == 1) $totalrefear2 += $ll2;
        $lv2[] = ['fname'=>$row2['fname'],'username'=>$row2['username'],'email'=>$row2['email'],'doj'=>$row2['doj'],'active'=>$ac2,'ref'=>$row['fname']];
    }
}
$levels[2] = ['members'=>$lv2,'earn'=>$totalrefear2];

// Level 3
$lv3 = []; $totalrefear3 = 0;
$r1 = mysqli_query($con, "SELECT username FROM affiliateuser WHERE referedby='".$_SESSION['adminidusername']."'");
while ($row = mysqli_fetch_array($r1)) {
    $r2 = mysqli_query($con, "SELECT username,fname FROM affiliateuser WHERE referedby='".$row['username']."'");
    while ($row2 = mysqli_fetch_array($r2)) {
        $r3 = mysqli_query($con, "SELECT fname,email,doj,active,username,pcktaken FROM affiliateuser WHERE referedby='".$row2['username']."'");
        while ($row3 = mysqli_fetch_array($r3)) {
            $ac3=$row3['active']; $ll3=0;
            $rp=mysqli_fetch_array(mysqli_query($con,"SELECT level3 FROM packages WHERE id=".(int)$row3['pcktaken']));
            if($rp) $ll3=$rp['level3'];
            if($ac3==1) $totalrefear3+=$ll3;
            $lv3[] = ['fname'=>$row3['fname'],'username'=>$row3['username'],'email'=>$row3['email'],'doj'=>$row3['doj'],'active'=>$ac3,'ref'=>$row2['fname']];
        }
    }
}
$levels[3] = ['members'=>$lv3,'earn'=>$totalrefear3];

// Level 4
$lv4=[]; $te4=0;
$r1=mysqli_query($con,"SELECT username FROM affiliateuser WHERE referedby='".$_SESSION['adminidusername']."'");
while($row=mysqli_fetch_array($r1)){
 $r2=mysqli_query($con,"SELECT username FROM affiliateuser WHERE referedby='".$row['username']."'");
 while($row2=mysqli_fetch_array($r2)){
  $r3=mysqli_query($con,"SELECT username FROM affiliateuser WHERE referedby='".$row2['username']."'");
  while($row3=mysqli_fetch_array($r3)){
   $r4=mysqli_query($con,"SELECT fname,email,doj,active,username,pcktaken FROM affiliateuser WHERE referedby='".$row3['username']."'");
   while($row4=mysqli_fetch_array($r4)){
    $ac=$row4['active'];$ll=0;
    $rp=mysqli_fetch_array(mysqli_query($con,"SELECT level4 FROM packages WHERE id=".(int)$row4['pcktaken']));
    if($rp)$ll=$rp['level4']; if($ac==1)$te4+=$ll;
    $lv4[]=['fname'=>$row4['fname'],'username'=>$row4['username'],'email'=>$row4['email'],'doj'=>$row4['doj'],'active'=>$ac,'ref'=>$row3['username']];
   }
  }
 }
}
$levels[4]=['members'=>$lv4,'earn'=>$te4];

// Level 5
$lv5=[]; $te5=0;
$r1=mysqli_query($con,"SELECT username FROM affiliateuser WHERE referedby='".$_SESSION['adminidusername']."'");
while($row=mysqli_fetch_array($r1)){
 $r2=mysqli_query($con,"SELECT username FROM affiliateuser WHERE referedby='".$row['username']."'");
 while($row2=mysqli_fetch_array($r2)){
  $r3=mysqli_query($con,"SELECT username FROM affiliateuser WHERE referedby='".$row2['username']."'");
  while($row3=mysqli_fetch_array($r3)){
   $r4=mysqli_query($con,"SELECT username FROM affiliateuser WHERE referedby='".$row3['username']."'");
   while($row4=mysqli_fetch_array($r4)){
    $r5=mysqli_query($con,"SELECT fname,email,doj,active,pcktaken FROM affiliateuser WHERE referedby='".$row4['username']."'");
    while($row5=mysqli_fetch_array($r5)){
     $ac=$row5['active'];$ll=0;
     $rp=mysqli_fetch_array(mysqli_query($con,"SELECT level5 FROM packages WHERE id=".(int)$row5['pcktaken']));
     if($rp)$ll=$rp['level5']; if($ac==1)$te5+=$ll;
     $lv5[]=['fname'=>$row5['fname'],'username'=>$row5['username'],'email'=>$row5['email'],'doj'=>$row5['doj'],'active'=>$ac,'ref'=>$row4['username']];
    }
   }
  }
 }
}
$levels[5]=['members'=>$lv5,'earn'=>$te5];

function get_level_members($con, $user, $depth, $level_num) {
    $results = []; $earnings = 0;
    $stack = [['user'=>$user,'depth'=>0,'ref'=>'']];
    $visited = [];
    while (!empty($stack)) {
        $item = array_shift($stack);
        if ($item['depth'] == $depth - 1) {
            $q = mysqli_query($con, "SELECT fname,email,doj,active,username,pcktaken FROM affiliateuser WHERE referedby='".mysqli_real_escape_string($con,$item['user'])."'");
            while ($r = mysqli_fetch_array($q)) {
                $ac = $r['active']; $ll = 0;
                $rp = mysqli_fetch_array(mysqli_query($con,"SELECT level{$level_num} FROM packages WHERE id=".(int)$r['pcktaken']));
                if ($rp) $ll = $rp["level{$level_num}"];
                if ($ac == 1) $earnings += $ll;
                $results[] = ['fname'=>$r['fname'],'username'=>$r['username'],'email'=>$r['email'],'doj'=>$r['doj'],'active'=>$ac,'ref'=>$item['user']];
            }
        } else {
            $q = mysqli_query($con, "SELECT username FROM affiliateuser WHERE referedby='".mysqli_real_escape_string($con,$item['user'])."'");
            while ($r = mysqli_fetch_array($q)) {
                if (!isset($visited[$r['username']])) {
                    $visited[$r['username']] = true;
                    $stack[] = ['user'=>$r['username'],'depth'=>$item['depth']+1,'ref'=>$r['username']];
                }
            }
        }
    }
    return ['members'=>$results,'earn'=>$earnings];
}

foreach ([6,7,8,9,10] as $lnum) {
    $levels[$lnum] = get_level_members($con, $_SESSION['adminidusername'], $lnum, $lnum);
}

$total_network_earnings = 0;
foreach ($levels as $l) {
    $total_network_earnings += $l['earn'];
}

// Tree logic
$root_user = $_GET['user'] ?? $_SESSION['adminidusername'];
$root_user_esc = mysqli_real_escape_string($con, $root_user);

function getTreeData($con, $username, $current_depth, $max_depth) {
    if ($current_depth > $max_depth) return null;
    $user_query = mysqli_query($con, "SELECT Id, fname, username, active, left_count, right_count, position FROM affiliateuser WHERE username='$username'");
    $user_data = mysqli_fetch_assoc($user_query);
    if (!$user_data) return null;
    $node = [
        'id' => $user_data['Id'],
        'fname' => $user_data['fname'],
        'username' => $user_data['username'],
        'active' => $user_data['active'],
        'left_count' => $user_data['left_count'],
        'right_count' => $user_data['right_count'],
        'position' => $user_data['position'],
        'left' => null,
        'right' => null
    ];
    if ($current_depth < $max_depth) {
        $children_query = mysqli_query($con, "SELECT username, position FROM affiliateuser WHERE parent_id=" . (int)$user_data['Id']);
        while ($child = mysqli_fetch_assoc($children_query)) {
            if ($child['position'] == 'L') {
                $node['left'] = getTreeData($con, $child['username'], $current_depth + 1, $max_depth);
            } elseif ($child['position'] == 'R') {
                $node['right'] = getTreeData($con, $child['username'], $current_depth + 1, $max_depth);
            }
        }
    }
    return $node;
}

$tree_data = getTreeData($con, $root_user_esc, 1, 4);

function renderNodeHTML($node, $pos_label = '', $has_children = false, $is_collapsed = false) {
    if (!$node) {
        return "
        <div class='tree-node empty' style='margin-bottom: 25px;'>
            <div><i class='fa-solid fa-user-plus' style='font-size: 20px; margin-bottom: 5px;'></i><br>Empty</div>
        </div>";
    }
    $status_class = ($node['active'] == 1) ? 'active' : 'inactive';
    $status_icon = ($node['active'] == 1) ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-solid fa-circle-xmark"></i>';
    
    $toggle_html = "";
    if ($has_children) {
        $icon = $is_collapsed ? '+' : '-';
        $toggle_html = "<span class='toggle-btn' onclick='toggleChildren(event, this)'>{$icon}</span>";
    }

    return "
    <div style='position:relative; display:inline-block; margin-bottom: 25px;'>
        <a href='downline.php?user=" . urlencode($node['username']) . "&view=tree' class='tree-node'>
            <div class='node-header {$status_class}'>
                <span>{$pos_label}</span>
                <span title='".($node['active'] == 1 ? 'Active' : 'Inactive')."'>{$status_icon}</span>
            </div>
            <div class='node-body'>
                <img src='images/a0.jpg' class='node-img' onerror=\"this.src='https://ui-avatars.com/api/?name=".urlencode($node['fname'])."&background=random&color=fff'\" alt='User'>
                <div class='node-name'>".htmlspecialchars($node['fname'], ENT_QUOTES, 'UTF-8')."</div>
                <div class='node-user'>@".htmlspecialchars($node['username'], ENT_QUOTES, 'UTF-8')."</div>
                <div class='node-stats'>
                    <div class='stat-box'>L <span>".$node['left_count']."</span></div>
                    <div class='stat-box'>R <span>".$node['right_count']."</span></div>
                </div>
            </div>
        </a>
        {$toggle_html}
    </div>";
}

function renderTreeHTML($node, $depth = 1) {
    echo "<li>";
    $has_children = ($node && (isset($node['left']) || isset($node['right'])));
    $is_collapsed = ($depth >= 2 && $has_children); // Level 1 (depth=1) is expanded, level 2 is collapsed
    
    echo renderNodeHTML($node, $node ? ($node['position'] ?: 'Root') : '', $has_children, $is_collapsed);
    
    if ($node && $has_children) {
        $display = $is_collapsed ? 'style="display:none;"' : '';
        echo "<ul class='nested-tree' {$display}>";
        renderTreeHTML($node['left'] ?? null, $depth + 1);
        renderTreeHTML($node['right'] ?? null, $depth + 1);
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
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col mb-6">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-4 bg-slate-50 rounded-t-2xl">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center"><i class="fa-solid fa-sitemap"></i></div>
                <h3 class="text-sm font-bold text-slate-900 m-0">Binary Network Tree</h3>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <form method="GET" action="downline.php" class="flex items-center bg-white border border-slate-200 rounded-full px-3 py-1.5 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 transition-all shadow-inner max-w-[250px]">
                    <input type="hidden" name="view" value="tree">
                    <i class="fa-solid fa-search text-slate-400 text-xs mr-2"></i>
                    <input type="text" name="user" placeholder="Search Username..." class="bg-transparent border-none outline-none text-slate-900 text-sm flex-1 min-w-0 placeholder:text-slate-400">
                    <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold py-1 px-3 rounded-full transition-colors ml-2">Find</button>
                </form>
                <button onclick="collapseAllTree()" type="button" class="bg-red-500/10 hover:bg-red-500/20 text-red-600 border border-red-500/20 px-3 py-1.5 rounded-full text-xs font-bold transition-colors whitespace-nowrap flex items-center gap-1.5"><i class="fa-solid fa-compress"></i> Collapse All</button>
                <?php if ($root_user !== $_SESSION['adminidusername']): ?>
                    <a href="downline.php?view=tree" class="bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-600 border border-indigo-500/20 px-3 py-1.5 rounded-full text-xs font-bold transition-colors whitespace-nowrap flex items-center gap-1.5 no-underline"><i class="fa-solid fa-arrow-left"></i> Back to Root</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="p-6 overflow-x-auto">
            <?php if ($tree_data): ?>
                <div class="tree-container">
                    <div class="tree">
                        <ul>
                            <?php renderTreeHTML($tree_data, 1); ?>
                        </ul>
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
            <div class="flex gap-5 justify-center text-xs font-semibold text-slate-600 flex-wrap">
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-emerald-100 border border-emerald-200 shadow-[0_0_8px_rgba(16,185,129,0.1)]"></span> Active Node</div>
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-red-100 border border-red-200 shadow-[0_0_8px_rgba(239,68,68,0.1)]"></span> Inactive Node</div>
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-white border border-dashed border-slate-300"></span> Empty Slot</div>
                <div class="flex items-center gap-2 text-indigo-600"><i class="fa-solid fa-mouse-pointer"></i> Click on any user to drill down</div>
            </div>
        </div>
    </div>
</div>

<!-- LIST VIEW SECTION -->
<div id="main-list-view">
    <!-- Total Network Earnings Banner -->
    <div style="background: linear-gradient(135deg, #7c3aed, #5b21b6); color: #fff; padding: 20px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(124, 58, 237, 0.2);">
      <div>
        <div style="font-size: 14px; font-weight: 500; opacity: 0.9; margin-bottom: 4px;">My Total Network Earnings</div>
        <div style="font-size: 24px; font-weight: 700;"><?php echo htmlspecialchars($pckcur, ENT_QUOTES, 'UTF-8'); ?> <?php echo number_format($total_network_earnings, 2); ?></div>
      </div>
      <div style="font-size: 32px; opacity: 0.2;">
        <i class="fa-solid fa-coins"></i>
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
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-4 bg-slate-50 rounded-t-2xl">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center"><i class="fa-solid fa-layer-group"></i></div>
            <h3 class="text-sm font-bold text-slate-900 m-0">Level <?php echo $i; ?> Downline</h3>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-xs font-semibold text-slate-700 bg-slate-100 border border-slate-200 px-2 py-1 rounded"><?php echo count($members); ?> members</span>
            <span class="text-xs font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 px-2 py-1 rounded shadow-none">+ <?php echo htmlspecialchars($pckcur, ENT_QUOTES, 'UTF-8'); ?> <?php echo number_format($earn, 2); ?></span>
          </div>
        </div>
        <div class="p-6">
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
</script>

<?php include 'layout_footer.php'; ?>
