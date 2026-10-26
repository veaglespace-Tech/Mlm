<?php
include_once("z_db.php");
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$page_title = 'KYC Verification';
$active_nav = 'kyc';
include("layout_header.php");
ensure_kyc_schema($pdo);

$username = $_SESSION['username'];
$user = mlmp_pdo_fetch($pdo, "SELECT Id, fname, mobile, email, kyc_status FROM affiliateuser WHERE username = ?", [$username]);
$user_id = $user['Id'] ?? 0;
$kyc_status = $user['kyc_status'] ?? 'not_submitted';

$msg = '';

// Check if user already has an active submission
$existing_kyc = mlmp_pdo_fetch($pdo, "SELECT * FROM kyc_documents WHERE user_id = ? ORDER BY id DESC LIMIT 1", [$user_id]);
if ($existing_kyc) {
    $kyc_status = $existing_kyc['status'];
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_kyc'])) {
    if ($kyc_status === 'approved') {
        $msg = "<div class='bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-check'></i> Your KYC is already verified. No further action needed.</div>";
    } elseif ($kyc_status === 'pending') {
        $msg = "<div class='bg-amber-500/10 border border-amber-500/20 text-amber-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-clock'></i> Your KYC is already under review by the admin team.</div>";
    } else {
        $full_name     = trim($_POST['full_name'] ?? '');
        $aadhar_number = preg_replace('/\s+/', '', trim($_POST['aadhar_number'] ?? ''));
        $pan_number    = strtoupper(trim($_POST['pan_number'] ?? ''));

        $errors = [];

        if (empty($full_name) || strlen($full_name) < 3) {
            $errors[] = "Please enter your full legal name as per government ID.";
        }

        if (!preg_match('/^[0-9]{12}$/', $aadhar_number)) {
            $errors[] = "Aadhaar number must be exactly 12 digits.";
        }

        if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan_number)) {
            $errors[] = "PAN number format is invalid (e.g. ABCDE1234F).";
        }

        // Upload directory
        $upload_dir = __DIR__ . '/uploads/kyc';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB

        $aadhar_front_path = $existing_kyc['aadhar_front'] ?? '';
        $aadhar_back_path  = $existing_kyc['aadhar_back'] ?? '';
        $pan_card_path     = $existing_kyc['pan_card'] ?? '';

        // Helper to process uploaded file
        $handle_upload = function($file_key, $prefix) use ($upload_dir, $allowed_exts, $max_size, $user_id, &$errors) {
            if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] === UPLOAD_ERR_NO_FILE) {
                return null;
            }
            if ($_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Error uploading file for $prefix.";
                return null;
            }
            if ($_FILES[$file_key]['size'] > $max_size) {
                $errors[] = "File size for $prefix exceeds maximum 5MB limit.";
                return null;
            }
            $ext = strtolower(pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_exts)) {
                $errors[] = "Invalid file type for $prefix. Allowed: JPG, PNG, WEBP, PDF.";
                return null;
            }

            $new_name = $prefix . '_' . $user_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $target = $upload_dir . '/' . $new_name;
            if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target)) {
                return 'uploads/kyc/' . $new_name;
            } else {
                $errors[] = "Failed to save file for $prefix.";
                return null;
            }
        };

        $up_af = $handle_upload('aadhar_front', 'aadhar_front');
        if ($up_af) $aadhar_front_path = $up_af;

        $up_ab = $handle_upload('aadhar_back', 'aadhar_back');
        if ($up_ab) $aadhar_back_path = $up_ab;

        $up_pan = $handle_upload('pan_card', 'pan_card');
        if ($up_pan) $pan_card_path = $up_pan;

        // Require files for first submission
        if (!$existing_kyc) {
            if (!$aadhar_front_path) $errors[] = "Aadhaar Card Front photo is required.";
            if (!$aadhar_back_path)  $errors[] = "Aadhaar Card Back photo is required.";
            if (!$pan_card_path)     $errors[] = "PAN Card photo is required.";
        }

        if (!empty($errors)) {
            $msg = "<div class='bg-red-500/10 border border-red-500/20 text-red-600 p-4 rounded-xl flex flex-col gap-1'>" .
                   "<div class='font-semibold flex items-center gap-2'><i class='fa-solid fa-triangle-exclamation'></i> Please fix the following errors:</div><ul class='list-disc list-inside text-sm mt-1'>";
            foreach ($errors as $err) {
                $msg .= "<li>" . htmlspecialchars($err) . "</li>";
            }
            $msg .= "</ul></div>";
        } else {
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("INSERT INTO kyc_documents 
                    (user_id, username, full_name, aadhar_number, pan_number, aadhar_front, aadhar_back, pan_card, status, submitted_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
                $stmt->execute([
                    $user_id,
                    $username,
                    $full_name,
                    $aadhar_number,
                    $pan_number,
                    $aadhar_front_path,
                    $aadhar_back_path,
                    $pan_card_path
                ]);

                $stmt2 = $pdo->prepare("UPDATE affiliateuser SET kyc_status = 'pending' WHERE Id = ?");
                $stmt2->execute([$user_id]);

                $pdo->commit();

                $kyc_status = 'pending';
                $existing_kyc = mlmp_pdo_fetch($pdo, "SELECT * FROM kyc_documents WHERE user_id = ? ORDER BY id DESC LIMIT 1", [$user_id]);
                $msg = "<div class='bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-check'></i> KYC documents submitted successfully! Admin will review and verify shortly.</div>";
            } catch (Exception $e) {
                $pdo->rollBack();
                $msg = "<div class='bg-red-500/10 border border-red-500/20 text-red-600 p-4 rounded-xl flex items-center gap-3'><i class='fa-solid fa-circle-exclamation'></i> Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
}
?>

<div class="dash-content space-y-6">
  <!-- Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2.5">
        <i class="fa-solid fa-id-card text-indigo-600"></i> KYC / eKYC Verification
      </h1>
      <p class="text-sm text-slate-500 mt-0.5">Verify your Aadhaar and PAN documents to unlock unrestricted withdrawals and full benefits.</p>
    </div>

    <!-- Status Badge -->
    <div>
      <?php if ($kyc_status === 'approved'): ?>
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200 shadow-sm">
          <i class="fa-solid fa-circle-check text-emerald-600"></i> KYC Verified
        </span>
      <?php elseif ($kyc_status === 'pending'): ?>
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-amber-100 text-amber-800 border border-amber-200 shadow-sm animate-pulse">
          <i class="fa-solid fa-clock text-amber-600"></i> Under Review
        </span>
      <?php elseif ($kyc_status === 'rejected'): ?>
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-red-100 text-red-800 border border-red-200 shadow-sm">
          <i class="fa-solid fa-circle-xmark text-red-600"></i> Verification Rejected
        </span>
      <?php else: ?>
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-slate-100 text-slate-700 border border-slate-200">
          <i class="fa-solid fa-circle-info text-slate-500"></i> Not Submitted
        </span>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($msg) echo $msg; ?>

  <!-- Status Alert Card -->
  <?php if ($kyc_status === 'approved'): ?>
    <div class="p-6 rounded-2xl bg-gradient-to-r from-emerald-500/10 via-teal-500/10 to-indigo-500/10 border border-emerald-500/20 flex flex-col md:flex-row items-center gap-5">
      <div class="w-14 h-14 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-2xl shadow-lg shadow-emerald-500/20 shrink-0">
        <i class="fa-solid fa-shield-halved"></i>
      </div>
      <div class="flex-1 text-center md:text-left">
        <h3 class="text-lg font-bold text-slate-800">Your Identity is 100% Verified!</h3>
        <p class="text-sm text-slate-600 mt-1">
          Your KYC verification is complete. You have full access to seamless payout withdrawals, commissions, and all platform features.
        </p>
        <div class="flex flex-wrap gap-4 mt-3 text-xs text-slate-500">
          <div><span class="font-semibold text-slate-700">Aadhaar:</span> XXXX-XXXX-<?php echo substr($existing_kyc['aadhar_number'] ?? '0000', -4); ?></div>
          <div><span class="font-semibold text-slate-700">PAN:</span> <?php echo substr($existing_kyc['pan_number'] ?? 'XXXXX0000X', 0, 2) . '******' . substr($existing_kyc['pan_number'] ?? '', -2); ?></div>
          <div><span class="font-semibold text-slate-700">Approved On:</span> <?php echo date('d M Y, h:i A', strtotime($existing_kyc['reviewed_at'] ?? 'now')); ?></div>
        </div>
      </div>
    </div>
  <?php elseif ($kyc_status === 'pending'): ?>
    <div class="p-6 rounded-2xl bg-amber-50 border border-amber-200 flex flex-col md:flex-row items-center gap-5">
      <div class="w-14 h-14 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-2xl shadow-lg shadow-amber-500/20 shrink-0">
        <i class="fa-solid fa-hourglass-half"></i>
      </div>
      <div class="flex-1 text-center md:text-left">
        <h3 class="text-lg font-bold text-amber-900">Documents Under Admin Review</h3>
        <p class="text-sm text-amber-800 mt-1">
          We have received your KYC submission (submitted on <?php echo date('d M Y, h:i A', strtotime($existing_kyc['submitted_at'])); ?>).
          Our compliance team reviews documents within 24-48 business hours. You'll be notified once approved.
        </p>
      </div>
    </div>
  <?php elseif ($kyc_status === 'rejected'): ?>
    <div class="p-6 rounded-2xl bg-red-50 border border-red-200 flex flex-col md:flex-row items-start gap-5">
      <div class="w-14 h-14 rounded-2xl bg-red-500 text-white flex items-center justify-center text-2xl shadow-lg shadow-red-500/20 shrink-0 mt-0.5">
        <i class="fa-solid fa-circle-exclamation"></i>
      </div>
      <div class="flex-1">
        <h3 class="text-lg font-bold text-red-900">KYC Verification Rejected</h3>
        <p class="text-sm text-red-800 mt-1">
          Reason: <strong class="underline"><?php echo htmlspecialchars($existing_kyc['reject_reason'] ?? 'Documents unclear or did not match registered details.'); ?></strong>
        </p>
        <p class="text-sm text-red-700 mt-2">
          Please review the details, attach clear and readable document photos, and submit the form again below.
        </p>
      </div>
    </div>
  <?php endif; ?>

  <!-- Submission Form (Shown if not approved or if rejected) -->
  <?php if ($kyc_status !== 'approved'): ?>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
      <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
            <i class="fa-solid fa-file-shield"></i>
          </div>
          <div>
            <h2 class="font-bold text-slate-800 text-base">
              <?php echo ($kyc_status === 'rejected') ? 'Resubmit KYC Documents' : 'Submit KYC Documents'; ?>
            </h2>
            <p class="text-xs text-slate-500">Accepted formats: JPG, PNG, WEBP, PDF (Max 5MB each)</p>
          </div>
        </div>
      </div>

      <form action="kyc.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
        <!-- Personal Information -->
        <div class="space-y-4">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400">1. Identity Information</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Full Name (As per Aadhaar/PAN) *</label>
              <input type="text" name="full_name" required
                     placeholder="e.g. Rahul Ramesh Sharma" 
                     value="<?php echo htmlspecialchars($existing_kyc['full_name'] ?? $user['fname'] ?? ''); ?>"
                     class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all font-medium">
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Aadhaar Card Number *</label>
              <input type="text" name="aadhar_number" required maxlength="14"
                     placeholder="12-digit number (e.g. 1234 5678 9012)" 
                     value="<?php echo htmlspecialchars($existing_kyc['aadhar_number'] ?? ''); ?>"
                     class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all font-medium tracking-wide">
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">PAN Card Number *</label>
              <input type="text" name="pan_number" required maxlength="10"
                     placeholder="10-digit PAN (e.g. ABCDE1234F)" 
                     value="<?php echo htmlspecialchars($existing_kyc['pan_number'] ?? ''); ?>"
                     style="text-transform: uppercase;"
                     class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all font-medium tracking-wider">
            </div>
          </div>
        </div>

        <hr class="border-slate-100">

        <!-- Document File Uploads -->
        <div class="space-y-4">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400">2. Document Photos / Scans</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Aadhaar Front -->
            <div class="border border-dashed border-slate-300 rounded-2xl p-4 text-center hover:border-indigo-400 transition-colors bg-slate-50/30">
              <div class="w-12 h-12 mx-auto rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl mb-3">
                <i class="fa-solid fa-address-card"></i>
              </div>
              <div class="font-semibold text-sm text-slate-800">Aadhaar Front Side *</div>
              <p class="text-xs text-slate-400 mt-0.5 mb-3">Clear photo showing name & photo</p>
              
              <?php if (!empty($existing_kyc['aadhar_front'])): ?>
                <div class="mb-3 text-xs text-indigo-600 font-medium truncate">
                  Current: <a href="<?php echo htmlspecialchars($existing_kyc['aadhar_front']); ?>" target="_blank" class="underline">View Attached File</a>
                </div>
              <?php endif; ?>

              <input type="file" name="aadhar_front" accept="image/*,.pdf" <?php echo empty($existing_kyc['aadhar_front']) ? 'required' : ''; ?>
                     class="block w-full text-xs text-slate-500 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
            </div>

            <!-- Aadhaar Back -->
            <div class="border border-dashed border-slate-300 rounded-2xl p-4 text-center hover:border-indigo-400 transition-colors bg-slate-50/30">
              <div class="w-12 h-12 mx-auto rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl mb-3">
                <i class="fa-solid fa-map-location-dot"></i>
              </div>
              <div class="font-semibold text-sm text-slate-800">Aadhaar Back Side *</div>
              <p class="text-xs text-slate-400 mt-0.5 mb-3">Showing registered address</p>
              
              <?php if (!empty($existing_kyc['aadhar_back'])): ?>
                <div class="mb-3 text-xs text-indigo-600 font-medium truncate">
                  Current: <a href="<?php echo htmlspecialchars($existing_kyc['aadhar_back']); ?>" target="_blank" class="underline">View Attached File</a>
                </div>
              <?php endif; ?>

              <input type="file" name="aadhar_back" accept="image/*,.pdf" <?php echo empty($existing_kyc['aadhar_back']) ? 'required' : ''; ?>
                     class="block w-full text-xs text-slate-500 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
            </div>

            <!-- PAN Card -->
            <div class="border border-dashed border-slate-300 rounded-2xl p-4 text-center hover:border-indigo-400 transition-colors bg-slate-50/30">
              <div class="w-12 h-12 mx-auto rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl mb-3">
                <i class="fa-solid fa-credit-card"></i>
              </div>
              <div class="font-semibold text-sm text-slate-800">PAN Card Front *</div>
              <p class="text-xs text-slate-400 mt-0.5 mb-3">Showing signature & PAN number</p>
              
              <?php if (!empty($existing_kyc['pan_card'])): ?>
                <div class="mb-3 text-xs text-indigo-600 font-medium truncate">
                  Current: <a href="<?php echo htmlspecialchars($existing_kyc['pan_card']); ?>" target="_blank" class="underline">View Attached File</a>
                </div>
              <?php endif; ?>

              <input type="file" name="pan_card" accept="image/*,.pdf" <?php echo empty($existing_kyc['pan_card']) ? 'required' : ''; ?>
                     class="block w-full text-xs text-slate-500 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
            </div>
          </div>
        </div>

        <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-100">
          <p class="text-xs text-slate-500">
            <i class="fa-solid fa-lock text-slate-400 mr-1"></i>
            Your documents are strictly encrypted and securely stored for compliance only.
          </p>

          <button type="submit" name="submit_kyc"
                  class="w-full sm:w-auto px-7 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold text-sm shadow-md shadow-indigo-500/25 flex items-center justify-center gap-2 transition-all">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <?php echo ($kyc_status === 'rejected') ? 'Resubmit Verification' : 'Submit for Verification'; ?>
          </button>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <!-- Help & Guidelines Box -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-start gap-3.5">
      <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shrink-0">
        <i class="fa-solid fa-camera"></i>
      </div>
      <div>
        <h4 class="font-bold text-sm text-slate-800">Clear Visibility</h4>
        <p class="text-xs text-slate-500 mt-1">Make sure all 4 corners of documents are clearly visible without flash glare or blur.</p>
      </div>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-start gap-3.5">
      <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shrink-0">
        <i class="fa-solid fa-user-check"></i>
      </div>
      <div>
        <h4 class="font-bold text-sm text-slate-800">Name Match</h4>
        <p class="text-xs text-slate-500 mt-1">Your registered profile name must match the name written on your Aadhaar & PAN card.</p>
      </div>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-start gap-3.5">
      <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shrink-0">
        <i class="fa-solid fa-bolt"></i>
      </div>
      <div>
        <h4 class="font-bold text-sm text-slate-800">Fast Approvals</h4>
        <p class="text-xs text-slate-500 mt-1">Proper documents are reviewed and approved within minutes during business hours.</p>
      </div>
    </div>
  </div>
</div>

<?php include("layout_footer.php"); ?>
