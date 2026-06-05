<?php
include_once ("z_db.php");
// Initialize session
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

$errormsg = "";
$successmsg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'update') {
        $id = (int)$_POST['id'];
        $code = mysqli_real_escape_string($con, $_POST['code']);
        $etext = mysqli_real_escape_string($con, $_POST['etext']);
        
        if (empty($code) || empty($etext)) {
            $errormsg = "Code and Email Content cannot be empty.";
        } else {
            mysqli_query($con, "UPDATE emailtext SET code='$code', etext='$etext' WHERE id=$id");
            $successmsg = "Template '$code' updated successfully.";
        }
    } 
    elseif ($action == 'delete') {
        $id = (int)$_POST['id'];
        mysqli_query($con, "DELETE FROM emailtext WHERE id=$id");
        $successmsg = "Template deleted successfully.";
    }
    elseif ($action == 'create') {
        $code = mysqli_real_escape_string($con, $_POST['new_code']);
        $etext = mysqli_real_escape_string($con, $_POST['new_etext']);
        
        if (empty($code) || empty($etext)) {
            $errormsg = "Code and Email Content cannot be empty.";
        } else {
            mysqli_query($con, "INSERT INTO emailtext (code, etext, emailactive) VALUES ('$code', '$etext', 1)");
            $successmsg = "New template '$code' created successfully.";
        }
    }
}

$page_title = 'Email Configuration';
$active_nav = 'emailsettings';
$extra_head = '';
include("layout_header.php");

// Fetch all templates, grouping by ID to ensure we get all unique ones
$templates_query = mysqli_query($con, "SELECT * FROM emailtext ORDER BY id ASC");
?>

<div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 mb-6">
    <div class="flex justify-between items-center flex-wrap gap-4">
        <p class="m-0 text-sm text-blue-700"><strong>Email Templates Manager:</strong> Create, update, or delete auto-responder email templates used throughout the platform.</p>
        <button class="bg-indigo-600 hover:bg-indigo-500 text-white border-none py-2 px-4 text-sm font-bold rounded-lg shadow-lg shadow-indigo-500/20 transition-all flex items-center gap-2" type="button" onclick="document.getElementById('newTemplateForm').style.display='block'; this.style.display='none';">
            <i class="fa-solid fa-plus"></i> Add New Template
        </button>
    </div>
</div>

<?php if ($errormsg): ?>
    <div class="bg-red-500/10 border border-red-500/20 text-red-600 rounded-xl p-4 mb-6 font-semibold flex items-center gap-2">
        <i class="fa-solid fa-triangle-exclamation"></i> <?php echo mlmp_escape($errormsg); ?>
    </div>
<?php endif; ?>

<?php if ($successmsg): ?>
    <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 rounded-xl p-4 mb-6 font-semibold flex items-center gap-2">
        <i class="fa-solid fa-circle-check"></i> <?php echo mlmp_escape($successmsg); ?>
    </div>
<?php endif; ?>

<!-- Create New Template Form (Hidden by default) -->
<div class="bg-indigo-50 border border-indigo-200 shadow-sm border-dashed rounded-2xl shadow-xl flex flex-col overflow-hidden mb-6" id="newTemplateForm" style="display: none;">
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <input type="hidden" name="action" value="create">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
            <h4 class="text-sm font-bold text-indigo-600 m-0 flex items-center gap-2"><i class="fa-solid fa-envelope-open-text"></i> Create New Template</h4>
            <button type="button" class="bg-slate-100 hover:bg-slate-200 text-slate-800 border-none py-1.5 px-3 text-xs font-bold rounded-lg transition-colors" onclick="document.getElementById('newTemplateForm').style.display='none'; document.querySelector('.instruction-banner button').style.display='inline-flex';">Cancel</button>
        </div>
        <div class="p-6 flex flex-col gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Template Code (e.g., WELCOME, PAYMENT_SUCCESS)</label>
                <input type="text" class="w-full sm:max-w-xs bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all font-bold uppercase" name="new_code" placeholder="ENTER_CODE" required>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Email Body / Content</label>
                <textarea class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-4 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all font-mono min-h-[250px] resize-y" name="new_etext" placeholder="Type the email content here..." required></textarea>
            </div>
            <div class="mt-2">
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white border-none py-2.5 px-5 text-sm font-bold rounded-lg shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-2"><i class="fa-solid fa-save"></i> Save New Template</button>
            </div>
        </div>
    </form>
</div>

<!-- List Existing Templates -->
<?php while($row = mysqli_fetch_assoc($templates_query)): ?>
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden mb-6">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
            
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center flex-wrap gap-4">
                <div class="flex items-center gap-3 flex-1 min-w-[200px]">
                    <strong class="text-sm font-semibold text-slate-700">Template Code:</strong>
                    <input type="text" class="bg-black/20 border border-slate-200 rounded-lg px-3 py-1.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 transition-all font-bold uppercase w-full sm:max-w-[200px]" name="code" value="<?php echo mlmp_escape($row['code']); ?>" required>
                </div>
                <div class="flex items-center gap-3">
                    <span class="bg-emerald-500/20 text-emerald-600 border border-emerald-500/20 px-2 py-1 rounded text-xs font-semibold">Active</span>
                    <!-- Delete Button Form -->
                    <button type="submit" name="action" value="delete" class="bg-red-500/10 hover:bg-red-500/20 text-red-600 border border-red-500/20 py-1.5 px-3 text-xs font-bold rounded-lg transition-colors flex items-center gap-1.5" onclick="return confirm('Are you sure you want to delete this email template?');">
                        <i class="fa-solid fa-trash-can"></i> Delete
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Email Body Content</label>
                    <textarea class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-4 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all font-mono min-h-[250px] resize-y" name="etext" required><?php echo mlmp_escape($row['etext']); ?></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white border-none py-2.5 px-5 text-sm font-bold rounded-lg shadow-lg shadow-indigo-500/20 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Update Template
                    </button>
                </div>
            </div>
        </form>
    </div>
<?php endwhile; ?>

<?php include("layout_footer.php"); ?>
