<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['username'])) {
    echo "<script>window.location='index.php';</script>"; exit;
}

// Fetch user basic info
$u = mlmp_pdo_fetch($pdo, "SELECT fname, email FROM affiliateuser WHERE username = ?", [$_SESSION['username']]);
$fname = $u['fname'] ?? '';
$uemail = $u['email'] ?? '';

$errormsg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $email   = trim($_POST['email']   ?? '');

    $status = "OK"; $msg = "";
    if (strlen($subject) < 2) { $msg .= "Enter Subject.<br>"; $status = "NOTOK"; }
    if (strlen($message) < 2) { $msg .= "Enter Message.<br>"; $status = "NOTOK"; }
    if (strlen($email)   < 2) { $msg .= "Enter Email.<br>";   $status = "NOTOK"; }

    if ($status == "OK") {
        $to_row = mlmp_pdo_fetch($pdo, "SELECT email FROM settings WHERE sno = 0 LIMIT 1");
        $to = $to_row['email'] ?? '';
        $headers  = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: <" . $email . ">\r\n";
        mail($to, $subject, $message, $headers);
        $errormsg = "<div class='dash-alert success'><span>✅</span> Your message has been sent! Our team will get back to you shortly.</div>";
    } else {
        $errormsg = "<div class='dash-alert danger'><span>⚠️</span> " . $msg . "</div>";
    }
}

$page_title = "Contact / Help";
$active_nav = "contact";
include 'layout_header.php';
?>

<?php if ($errormsg) echo $errormsg; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

  <!-- Contact form -->
  <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col">
    <div class="px-5 py-4 border-b border-slate-200 flex items-center gap-3 bg-slate-50">
      <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-600 flex items-center justify-center"><i class="fa-solid fa-envelope"></i></div>
      <h3 class="text-sm font-bold text-slate-900">Send Us a Message</h3>
    </div>
    <div class="p-6">
      <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'utf-8'); ?>" method="post">
        <div class="mb-4">
          <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Your Email</label>
          <input type="email" name="email" value="<?php echo mlmp_escape($uemail); ?>" required
                 class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                 placeholder="your@email.com">
        </div>
        <div class="mb-4">
          <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Subject</label>
          <input type="text" name="subject" required minlength="2"
                 class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                 placeholder="Message subject...">
        </div>
        <div class="mb-5">
          <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Message</label>
          <textarea name="message" required minlength="2" rows="6"
                    class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all resize-y"
                    placeholder="Describe your issue or question..."></textarea>
        </div>
        <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 px-6 rounded-lg transition-all shadow-lg shadow-indigo-500/30 flex items-center gap-2">
          <i class="fa-solid fa-paper-plane"></i> Send Message
        </button>
      </form>
    </div>
  </div>

  <!-- Info panel -->
  <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col">
    <div class="px-5 py-4 border-b border-slate-200 flex items-center gap-3 bg-slate-50">
      <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-600 flex items-center justify-center"><i class="fa-solid fa-circle-info"></i></div>
      <h3 class="text-sm font-bold text-slate-900">Support Info</h3>
    </div>
    <div class="p-6">
      <div class="flex flex-col gap-4">
        <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-xl p-4">
          <div class="text-sm font-bold text-indigo-600 mb-1.5">
            <i class="fa-solid fa-clock mr-1.5"></i>Support Hours
          </div>
          <div class="text-sm text-slate-600">Available 24×7 — We'll respond within 24 hours.</div>
        </div>
        <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4">
          <div class="text-sm font-bold text-emerald-600 mb-1.5">
            <i class="fa-solid fa-user mr-1.5"></i>Hi, <?php echo mlmp_escape($fname); ?>!
          </div>
          <div class="text-sm text-slate-600">We have your account details on file. Just describe your issue and we'll help you out.</div>
        </div>
        <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4">
          <div class="text-sm font-bold text-blue-600 mb-2.5">
            <i class="fa-solid fa-link mr-1.5"></i>Quick Links
          </div>
          <div class="flex flex-col gap-2">
            <a href="notifications.php" class="text-sm text-slate-600 hover:text-slate-800 transition-colors flex items-center gap-2"><i class="fa-solid fa-bell text-indigo-600 w-4"></i>Check Notifications</a>
            <a href="paymentshistory.php" class="text-sm text-slate-600 hover:text-slate-800 transition-colors flex items-center gap-2"><i class="fa-solid fa-receipt text-emerald-600 w-4"></i>Payment History</a>
            <a href="profile.php" class="text-sm text-slate-600 hover:text-slate-800 transition-colors flex items-center gap-2"><i class="fa-solid fa-circle-user text-blue-600 w-4"></i>Update Profile</a>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<?php include 'layout_footer.php'; ?>
