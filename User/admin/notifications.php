<?php
include_once ("z_db.php");
// Inialize session
session_start();
// Check, if username session is NOT set then this page will jump to login page
if (!isset($_SESSION['adminidusername'])) {
        print "
				<script language='javascript'>
					window.location = 'index.php';
				</script>
			";
        exit;
}

$page_title = 'Broadcast & Announcements';
$active_nav = 'notifications';
include("layout_header.php");

// Fetch all notifications for the grid (both valid and invalid if needed, but let's just fetch all to allow toggling or just show active ones)
// In deletenoti.php, it sets valid=0. So we should fetch all to allow admin to see what's posted, or just valid ones.
// Let's fetch all and show status like we did for packages.
$notifications = mlmp_pdo_fetch_all($pdo, "SELECT * FROM notifications ORDER BY id DESC");
?>

<div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
    <p class="text-sm text-blue-700"><strong>Important Instructions:</strong> Use the Manage Notifications tab to view or delete announcements. Create new notifications under the Post Notification tab. Notifications appear on the user dashboard.</p>
</div>

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col overflow-hidden mb-6">
    <div class="px-5 py-0 border-b border-slate-200 bg-slate-50">
        <ul class="flex overflow-x-auto gap-2 no-scrollbar nav-tabs">
            <li class="active"><a href="#manage" class="block px-4 py-4 text-sm font-bold text-slate-700 hover:text-indigo-600 border-b-2 border-transparent transition-all whitespace-nowrap"><i class="fa-solid fa-layer-group mr-1.5"></i> Manage Notifications</a></li>
            <li><a href="#create" class="block px-4 py-4 text-sm font-bold text-slate-700 hover:text-indigo-600 border-b-2 border-transparent transition-all whitespace-nowrap"><i class="fa-solid fa-plus mr-1.5"></i> Post Notification</a></li>
        </ul>
    </div>
    <div class="p-6 bg-slate-50">
        <div class="tab-content">
            
            <!-- Manage Notifications Tab -->
            <div class="tab-pane active" id="manage">
                <?php if (count($notifications) === 0): ?>
                    <div class="bg-white border border-slate-200 rounded-xl p-8 text-center shadow-sm">
                        <i class="fa-solid fa-bell-slash text-4xl text-slate-400 mb-3"></i>
                        <h3 class="text-lg font-bold text-slate-800 mb-1">No Notifications Found</h3>
                        <p class="text-sm text-slate-500">You haven't posted any announcements yet. Go to the Post Notification tab to get started.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach($notifications as $noti): 
                            $id = $noti['id'];
                            $subject = $noti['subject'];
                            $body = $noti['body'];
                            $date = $noti['posteddate'];
                            $valid = $noti['valid'];
                        ?>
                            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm hover:shadow-md transition-shadow flex flex-col overflow-hidden relative">
                                
                                <div class="p-5 border-b border-slate-100 flex-1 flex flex-col">
                                    <div class="flex items-start gap-3 mb-4 pr-6">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-lg shadow-sm shrink-0">
                                            <i class="fa-solid fa-bullhorn"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-slate-900 text-base m-0 leading-tight"><?php echo mlmp_escape($subject); ?></h3>
                                            <div class="text-[11px] font-semibold text-slate-500 mt-1"><i class="fa-regular fa-clock mr-1"></i> <?php echo mlmp_escape($date); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-slate-50 rounded-xl p-4 flex-1 border border-slate-100 overflow-y-auto max-h-[150px]">
                                        <p class="text-sm text-slate-700 m-0 leading-relaxed whitespace-pre-wrap"><?php echo mlmp_escape($body); ?></p>
                                    </div>
                                </div>
                                
                                <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-2">
                                        <form action="deletenoti.php" method="post" class="w-full">
                                            <input type="hidden" name="notisub" value="<?php echo $id; ?>">
                                            <button type="submit" class="w-full bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 py-2.5 text-center text-xs font-bold rounded-lg transition-colors shadow-sm" onclick="return confirm('Are you sure you want to completely delete this notification?');">
                                                <i class="fa-solid fa-trash-can mr-1"></i> Delete Notification
                                            </button>
                                        </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Create Notification Tab -->
            <div class="tab-pane" id="create">
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 max-w-2xl mx-auto">
                    <form action="postnoti.php" method="post" class="flex flex-col gap-5">
                        <div class="mb-2 border-b border-slate-100 pb-2">
                            <h4 class="text-sm font-bold text-slate-900 m-0"><i class="fa-solid fa-pen-nib text-indigo-500 mr-1.5"></i> Compose Announcement</h4>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Notification Heading/Subject</label>
                            <input type="text" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-900 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="Maximum 20 Words" name="notihead" required>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Notification Body</label>
                            <textarea class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-900 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all resize-y" rows="6" name="notibody" placeholder="Details of the notification..." required></textarea>
                        </div>
                        
                        <div class="border-t border-slate-200 mt-2 pt-4">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white border-none py-3 px-8 text-sm font-bold rounded-xl shadow-md transition-all w-full md:w-auto md:float-right">
                                <i class="fa-solid fa-paper-plane mr-1.5"></i> Post to All Users
                            </button>
                            <div class="clear-both"></div>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</div>

<style>
.tab-pane { display: none; }
.tab-pane.active { display: block; animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
.nav-tabs li { cursor: pointer; list-style: none; }
.nav-tabs li.active a { color: #4f46e5; border-bottom-color: #4f46e5; background: #f8fafc; }
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const tabs = document.querySelectorAll(".nav-tabs a");
    const panes = document.querySelectorAll(".tab-pane");

    tabs.forEach(tab => {
        tab.addEventListener("click", function(e) {
            e.preventDefault();
            tabs.forEach(t => t.parentElement.classList.remove("active"));
            panes.forEach(p => p.classList.remove("active"));

            this.parentElement.classList.add("active");
            const targetId = this.getAttribute("href");
            const targetPane = document.querySelector(targetId);
            if (targetPane) targetPane.classList.add("active");
        });
    });
});
</script>

<?php include("layout_footer.php"); ?>
