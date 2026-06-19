$ErrorActionPreference = "Stop"

$base = "http://127.0.0.1:8080"
$root = "C:\Users\HP\Desktop\MLMP"
$artifacts = Join-Path $root "flow-smoke-artifacts"
$adminCookie = Join-Path $artifacts "admin.cookies.txt"
$userCookie = Join-Path $artifacts "user.cookies.txt"

if (Test-Path $artifacts) {
    Remove-Item $artifacts -Recurse -Force
}
New-Item -Path $artifacts -ItemType Directory | Out-Null

$errors = @()

function Add-Error {
    param([string]$message)
    $script:errors += $message
}

function Save-Response {
    param(
        [string]$url,
        [string]$outputPath,
        [string]$cookieFile = "",
        [string]$postData = ""
    )

    if ($postData -ne "") {
        if ($cookieFile -ne "") {
            & curl.exe -s -i -b $cookieFile -c $cookieFile -d $postData $url -o $outputPath
        } else {
            & curl.exe -s -i -d $postData $url -o $outputPath
        }
    } else {
        if ($cookieFile -ne "") {
            & curl.exe -s -i -b $cookieFile -c $cookieFile $url -o $outputPath
        } else {
            & curl.exe -s -i $url -o $outputPath
        }
    }
}

function Assert-Contains {
    param([string]$file, [string]$pattern, [string]$label)
    if (-not (Select-String -Path $file -SimpleMatch $pattern -Quiet)) {
        Add-Error "$label (pattern not found: $pattern)"
    }
}

function Assert-NotContains {
    param([string]$file, [string]$pattern, [string]$label)
    if (Select-String -Path $file -SimpleMatch $pattern -Quiet) {
        Add-Error "$label (unexpected pattern found: $pattern)"
    }
}

function Assert-NoPhpRuntimeErrors {
    param([string]$file, [string]$label)
    $patterns = @("Fatal error", "Parse error", "Uncaught", "Warning:", "Notice:")
    foreach ($p in $patterns) {
        if (Select-String -Path $file -Pattern $p -Quiet) {
            Add-Error "$label (php runtime output contains: $p)"
            break
        }
    }
}

# 1) Admin login flow
$adminLoginOut = Join-Path $artifacts "admin_login.html"
Save-Response -url "$base/admin/loginproc.php" -outputPath $adminLoginOut -cookieFile $adminCookie -postData "username=adminadmin&password=123123123"
Assert-Contains -file $adminLoginOut -pattern "dashboard.php?page=dashboard%location=index.php" -label "Admin login"
Assert-NoPhpRuntimeErrors -file $adminLoginOut -label "Admin login response"

# 2) Admin protected pages
$adminPages = @(
    "admin/dashboard.php",
    "admin/users.php",
    "admin/profile.php",
    "admin/notifications.php",
    "admin/payments.php",
    "admin/paymentscod.php",
    "admin/pacsettings.php"
)

foreach ($p in $adminPages) {
    $out = Join-Path $artifacts ("admin_" + ($p -replace "[/\.]", "_") + ".html")
    Save-Response -url "$base/$p" -outputPath $out -cookieFile $adminCookie
    Assert-NotContains -file $out -pattern "window.location = 'index.php'" -label "Admin page auth check: $p"
    Assert-NoPhpRuntimeErrors -file $out -label "Admin page response: $p"
}

# 3) Signup flow (new QA user)
$stamp = Get-Date -Format "MMddHHmmss"
$newUser = "qa$stamp"
$newPass = "Qa12345678"
$mobile = -join ((1..10) | ForEach-Object { Get-Random -Minimum 0 -Maximum 10 })
$email = "$newUser@example.com"

$signupData = @(
    "todo=post",
    "username=$newUser",
    "fname=QA+$stamp",
    "password=$newPass",
    "password2=$newPass",
    "email=$email",
    "mobile=$mobile",
    "address=QA+Address",
    "country=India",
    "package=1",
    "referral=adminadmin",
    "check=on"
) -join "&"

$signupOut = Join-Path $artifacts "user_signup.html"
Save-Response -url "$base/signup.php" -outputPath $signupOut -cookieFile $userCookie -postData $signupData
Assert-Contains -file $signupOut -pattern "Location: select_product.php" -label "User signup redirect"

# Mail server warnings are non-fatal in local CLI setups; do not fail flow on that.
$hasFatal = Select-String -Path $signupOut -SimpleMatch "Fatal error" -Quiet
$hasParse = Select-String -Path $signupOut -SimpleMatch "Parse error" -Quiet
$hasUncaught = Select-String -Path $signupOut -SimpleMatch "Uncaught" -Quiet
if ($hasFatal -or $hasParse -or $hasUncaught) {
    Add-Error "User signup response contains fatal runtime output"
}

# Select product package
$selectProductOut = Join-Path $artifacts "user_select_product.html"
Save-Response -url "$base/select_product.php" -outputPath $selectProductOut -cookieFile $userCookie -postData "selected_package=1"
Assert-Contains -file $selectProductOut -pattern "Location: payu_payment.php" -label "User select product redirect"

# Request checkout page to trigger entry in pending_registrations
$checkoutOut = Join-Path $artifacts "user_checkout.html"
Save-Response -url "$base/payu_payment.php" -outputPath $checkoutOut -cookieFile $userCookie
Assert-Contains -file $checkoutOut -pattern "Pending Admin Approval" -label "User checkout page pending status"

# 4) Admin approves user
$approveOut = Join-Path $artifacts "admin_approve_user.html"
Save-Response -url "$base/admin/approve_user.php" -outputPath $approveOut -cookieFile $adminCookie -postData "action=approve&username=$newUser"
Assert-Contains -file $approveOut -pattern "Location: approvals.php" -label "Admin approve user redirect"

# 5) Simulate payment completion
$successOut = Join-Path $artifacts "user_payu_success.html"
Save-Response -url "$base/payu_success.php" -outputPath $successOut -cookieFile $userCookie -postData "username=$newUser"
Assert-Contains -file $successOut -pattern "Payment Successful!" -label "User payment simulation"

# Verify User login
$userLoginOut = Join-Path $artifacts "user_login.html"
Save-Response -url "$base/index.php" -outputPath $userLoginOut -cookieFile $userCookie -postData "username=$newUser&password=$newPass"
Assert-Contains -file $userLoginOut -pattern "dashboard.php?page=dashboard%location=index.php" -label "User login"
Assert-NoPhpRuntimeErrors -file $userLoginOut -label "User login response"

$userPages = @(
    "dashboard.php",
    "profile.php",
    "downline.php",
    "paymentshistory.php",
    "contact.php"
)

foreach ($p in $userPages) {
    $out = Join-Path $artifacts ("user_" + ($p -replace "[/\.]", "_") + ".html")
    Save-Response -url "$base/$p" -outputPath $out -cookieFile $userCookie
    Assert-NotContains -file $out -pattern "window.location = 'index.php'" -label "User page auth check: $p"
    Assert-NoPhpRuntimeErrors -file $out -label "User page response: $p"
}

# 6) Critical admin action endpoints with admin session
$rejectOut = Join-Path $artifacts "admin_rejectrenew_testuser.html"
Save-Response -url "$base/admin/rejectrenew.php?username=$newUser" -outputPath $rejectOut -cookieFile $adminCookie
Assert-Contains -file $rejectOut -pattern "Location: users.php" -label "Admin reject renew redirect"

$expiryOut = Join-Path $artifacts "admin_updateexpiry_testuser.html"
Save-Response -url "$base/admin/updateexpiry.php?username=$newUser" -outputPath $expiryOut -cookieFile $adminCookie
Assert-Contains -file $expiryOut -pattern "Location: users.php" -label "Admin update expiry redirect"

$notiOut = Join-Path $artifacts "admin_postnoti.html"
Save-Response -url "$base/admin/postnoti.php" -outputPath $notiOut -cookieFile $adminCookie -postData "notihead=QA+Smoke&notibody=Flow+Smoke+Test+Notification"
Assert-Contains -file $notiOut -pattern "Notification Posted...!!!" -label "Admin post notification"
Assert-NoPhpRuntimeErrors -file $notiOut -label "Admin post notification response"

# 7) Cleanup test user
$deleteOut = Join-Path $artifacts "admin_delete_test_user.html"
Save-Response -url "$base/admin/deleteuser.php?username=$newUser" -outputPath $deleteOut -cookieFile $adminCookie
Assert-NoPhpRuntimeErrors -file $deleteOut -label "Delete test user response"

Write-Output "SMOKE_USER=$newUser"
Write-Output "ARTIFACTS=$artifacts"
if ($errors.Count -eq 0) {
    Write-Output "FLOW_STATUS=PASS"
    exit 0
}

Write-Output "FLOW_STATUS=FAIL"
foreach ($e in $errors) {
    Write-Output "ERROR=$e"
}
exit 1
