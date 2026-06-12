<?php
include_once ("z_db.php");
include_once("password_helper.php");
// Inialize session
session_start();
// Check, if username session is NOT set then this page will jump to login page
if (!isset($_SESSION['username'])) {
        print "
				<script language='javascript'>
					window.location = 'index.php';
				</script>
			";
        exit;
}


if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['todo']))
{


// Collect the data from post method of form submission // 
$name=$_POST['fullname'] ?? '';
$address=$_POST['address'] ?? '';
$cont=$_POST['contry'] ?? '';
$p1=$_POST['p1'] ?? '';
$p2=$_POST['p2'] ?? '';
$bankname=$_POST['bankname'] ?? '';
$email=$_POST['email'] ?? '';
$accname=$_POST['accname'] ?? '';
$accno=$_POST['accno'] ?? '';
$ifsccode=$_POST['ifsccode'] ?? '';
$alwdpayment=$_POST['alwdpayment'] ?? '';
$acctype=$_POST['acctype'] ?? '';
$default_leg=$_POST['default_leg'] ?? 'AUTO';
if (!in_array($default_leg, ['L', 'R', 'AUTO'])) $default_leg = 'AUTO';
//collection ends

$check=1;
if($check==1){

$status = "OK";
$msg="";
//validation starts
// if userid is less than 6 char then status is not ok

if ( ($p1 !== "" || $p2 !== "") && strlen($p1) < 8 ){
$msg=$msg."Password Must Be More Than 8 Char Length.<BR>";
$status= "NOTOK";}	

if ( ($p1 !== "" || $p2 !== "") && strlen($p2) < 8 ){
$msg=$msg."Conformation Password Must Be More Than 8 Char Length.<BR>";
$status= "NOTOK";}

if ( ($p1 !== "" || $p2 !== "") && $p2!=$p1 ){
$msg=$msg."Password Does Not Match.<BR>";
$status= "NOTOK";}

if ( strlen($name) < 2 ){
$msg=$msg."Name should contain 2 chars.<BR>";
$status= "NOTOK";}

if ( strlen($address) < 5 ){
$msg=$msg."address should contain 5 chars.<BR>";
$status= "NOTOK";}

if ( strlen($cont) < 1 ){
$msg=$msg."Country should contain 1 char.<BR>";
$status= "NOTOK";}

}

if ($status=="OK") 
{

$sql = "UPDATE affiliateuser SET fname=?, address=?, country=?, bankname=?, accountname=?, accountno=?, accounttype=?, ifsccode=?, email=?, getpayment=?, default_leg=?";
$params = [$name, $address, $cont, $bankname, $accname, $accno, $acctype, $ifsccode, $email, $alwdpayment, $default_leg];

if ($p1 !== "") {
    $hashedPassword = mlmp_hash_password($p1);
    $sql .= ", password=?";
    $params[] = $hashedPassword;
}

$sql .= " WHERE username=?";
$params[] = $_SESSION['username'];

mlmp_pdo_execute($pdo, $sql, $params);

$errormsg= "<script>Swal.fire('Success!', 'Your profile has been updated.', 'success');</script>";



}



else
{ 
$errormsg= "<script>Swal.fire({title: 'Error!', html: 'Please Fix Below Errors: <br>".$msg."', icon: 'error'});</script>";
					
}

}

// Data fetch logic:
$row = mlmp_pdo_fetch($pdo, "SELECT * FROM affiliateuser WHERE username=?", [$_SESSION['username']]);
$getpayment = '';
if($row)
{
	$name=$row['fname'];
	$add=$row['address'];
	$contry=$row['country'];
	$email=$row['email'];
	$bname=$row['bankname'];
	$accnamee=$row['accountname'];
	$accnumber=$row['accountno'];
	$acctyppe=$row['accounttype'];
	$ifsc=$row['ifsccode'];
	$getpayment=$row['getpayment'];
	$default_leg_val=$row['default_leg'] ?? 'AUTO';
}
	
$row121 = mlmp_pdo_fetch($pdo, "SELECT * FROM settings");
if($row121)
{
	$wlink=$row121['wlink'];
}
$referral_url = mlmp_build_referral_url($wlink ?? '', $_SESSION['username']);

$page_title = "My Profile";
$active_nav = "profile";
$extra_head = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

include 'layout_header.php';
?>

<?php 
if($_SERVER['REQUEST_METHOD'] == 'POST' && ($status!=""))
{
    print $errormsg;
}
?>

<div class="bg-indigo-600/10 border border-indigo-500/20 rounded-xl p-4 mb-6 flex gap-3 items-start">
  <i class="fa-solid fa-circle-info text-indigo-600 mt-0.5"></i>
  <div class="text-sm text-indigo-200">
    <strong class="text-white">Important Instructions:</strong> All fields are mandatory. Please submit your bank details accurately. Incorrect details may lead to payment rejection.
  </div>
</div>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, "utf-8"); ?>" method="post">
  <input type="hidden" name="todo" value="post">
  <input type="hidden" value="" name="sno">

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Left Column: Personal Profile & Security -->
    <div class="flex flex-col gap-6">
      
      <!-- General Settings Panel -->
      <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center gap-3 bg-slate-50">
          <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-600 flex items-center justify-center"><i class="fa-solid fa-user-gear"></i></div>
          <h3 class="text-sm font-bold text-slate-900">General Settings</h3>
        </div>
        <div class="p-6 flex flex-col gap-4">
          
          <!-- Invite URL -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">My Personal Invite URL</label>
            <div class="flex">
              <input type="text" id="refUrl" value="<?php print mlmp_escape($referral_url) ?>" readonly
                     class="flex-1 bg-black/20 border border-slate-200 border-r-0 rounded-l-lg px-4 py-2.5 text-slate-700 text-sm focus:outline-none"
                     placeholder="Your Invite URL">
              <button type="button" onclick="copyRefUrl()"
                      class="bg-indigo-600 hover:bg-indigo-500 text-white rounded-r-lg px-5 py-2.5 text-xs font-semibold transition-colors flex items-center gap-2">
                <i class="fa-solid fa-copy"></i> Copy
              </button>
            </div>
            <div id="copySuccess" class="text-[11px] text-emerald-600 mt-1.5 hidden font-semibold flex items-center gap-1.5">
              <i class="fa-solid fa-circle-check"></i> Copied to clipboard!
            </div>
          </div>

          <!-- Full Name -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Full Name</label>
            <input type="text" name="fullname" value="<?php print mlmp_escape($name) ?>" required
                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                   placeholder="Full Name">
          </div>

          <!-- Address -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Address</label>
            <input type="text" name="address" value="<?php print mlmp_escape($add) ?>" required
                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                   placeholder="Full Address">
          </div>

          <!-- E-Mail -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">E-Mail</label>
            <input type="email" name="email" value="<?php print mlmp_escape($email) ?>" required
                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                   placeholder="E-Mail Address">
          </div>

          <!-- Default Placement Leg -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Default Placement Leg</label>
            <select name="default_leg" required
                    class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-[42px]">
              <option value="AUTO" <?php echo (!isset($default_leg_val) || $default_leg_val == 'AUTO') ? 'selected' : ''; ?>>Auto (Weaker Leg)</option>
              <option value="L" <?php echo (isset($default_leg_val) && $default_leg_val == 'L') ? 'selected' : ''; ?>>Extreme Left</option>
              <option value="R" <?php echo (isset($default_leg_val) && $default_leg_val == 'R') ? 'selected' : ''; ?>>Extreme Right</option>
            </select>
          </div>

          <!-- Country Selection Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
            <div>
              <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Current Country</label>
              <input type="text" value="<?php print mlmp_escape($contry) ?>" disabled
                     class="w-full bg-black/20 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-600 text-sm cursor-not-allowed">
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Update Country</label>
              <select name="contry" required
                      class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-[42px]">
                <option value="">Country...</option>
                <option value="Afganistan">Afghanistan</option>
                <option value="Albania">Albania</option>
                <option value="Algeria">Algeria</option>
                <option value="American Samoa">American Samoa</option>
                <option value="Andorra">Andorra</option>
                <option value="Angola">Angola</option>
                <option value="Anguilla">Anguilla</option>
                <option value="Antigua &amp; Barbuda">Antigua &amp; Barbuda</option>
                <option value="Argentina">Argentina</option>
                <option value="Armenia">Armenia</option>
                <option value="Aruba">Aruba</option>
                <option value="Australia">Australia</option>
                <option value="Austria">Austria</option>
                <option value="Azerbaijan">Azerbaijan</option>
                <option value="Bahamas">Bahamas</option>
                <option value="Bahrain">Bahrain</option>
                <option value="Bangladesh">Bangladesh</option>
                <option value="Barbados">Barbados</option>
                <option value="Belarus">Belarus</option>
                <option value="Belgium">Belgium</option>
                <option value="Belize">Belize</option>
                <option value="Benin">Benin</option>
                <option value="Bermuda">Bermuda</option>
                <option value="Bhutan">Bhutan</option>
                <option value="Bolivia">Bolivia</option>
                <option value="Bonaire">Bonaire</option>
                <option value="Bosnia &amp; Herzegovina">Bosnia &amp; Herzegovina</option>
                <option value="Botswana">Botswana</option>
                <option value="Brazil">Brazil</option>
                <option value="British Indian Ocean Ter">British Indian Ocean Ter</option>
                <option value="Brunei">Brunei</option>
                <option value="Bulgaria">Bulgaria</option>
                <option value="Burkina Faso">Burkina Faso</option>
                <option value="Burundi">Burundi</option>
                <option value="Cambodia">Cambodia</option>
                <option value="Cameroon">Cameroon</option>
                <option value="Canada">Canada</option>
                <option value="Canary Islands">Canary Islands</option>
                <option value="Cape Verde">Cape Verde</option>
                <option value="Cayman Islands">Cayman Islands</option>
                <option value="Central African Republic">Central African Republic</option>
                <option value="Chad">Chad</option>
                <option value="Channel Islands">Channel Islands</option>
                <option value="Chile">Chile</option>
                <option value="China">China</option>
                <option value="Christmas Island">Christmas Island</option>
                <option value="Cocos Island">Cocos Island</option>
                <option value="Colombia">Colombia</option>
                <option value="Comoros">Comoros</option>
                <option value="Congo">Congo</option>
                <option value="Cook Islands">Cook Islands</option>
                <option value="Costa Rica">Costa Rica</option>
                <option value="Cote DIvoire">Cote D'Ivoire</option>
                <option value="Croatia">Croatia</option>
                <option value="Cuba">Cuba</option>
                <option value="Curaco">Curacao</option>
                <option value="Cyprus">Cyprus</option>
                <option value="Czech Republic">Czech Republic</option>
                <option value="Denmark">Denmark</option>
                <option value="Djibouti">Djibouti</option>
                <option value="Dominica">Dominica</option>
                <option value="Dominican Republic">Dominican Republic</option>
                <option value="East Timor">East Timor</option>
                <option value="Ecuador">Ecuador</option>
                <option value="Egypt">Egypt</option>
                <option value="El Salvador">El Salvador</option>
                <option value="Equatorial Guinea">Equatorial Guinea</option>
                <option value="Eritrea">Eritrea</option>
                <option value="Estonia">Estonia</option>
                <option value="Ethiopia">Ethiopia</option>
                <option value="Falkland Islands">Falkland Islands</option>
                <option value="Faroe Islands">Faroe Islands</option>
                <option value="Fiji">Fiji</option>
                <option value="Finland">Finland</option>
                <option value="France">France</option>
                <option value="French Guiana">French Guiana</option>
                <option value="French Polynesia">French Polynesia</option>
                <option value="French Southern Ter">French Southern Ter</option>
                <option value="Gabon">Gabon</option>
                <option value="Gambia">Gambia</option>
                <option value="Georgia">Georgia</option>
                <option value="Germany">Germany</option>
                <option value="Ghana">Ghana</option>
                <option value="Gibraltar">Gibraltar</option>
                <option value="Great Britain">Great Britain</option>
                <option value="Greece">Greece</option>
                <option value="Greenland">Greenland</option>
                <option value="Grenada">Grenada</option>
                <option value="Guadeloupe">Guadeloupe</option>
                <option value="Guam">Guam</option>
                <option value="Guatemala">Guatemala</option>
                <option value="Guinea">Guinea</option>
                <option value="Guyana">Guyana</option>
                <option value="Haiti">Haiti</option>
                <option value="Hawaii">Hawaii</option>
                <option value="Honduras">Honduras</option>
                <option value="Hong Kong">Hong Kong</option>
                <option value="Hungary">Hungary</option>
                <option value="Iceland">Iceland</option>
                <option value="India">India</option>
                <option value="Indonesia">Indonesia</option>
                <option value="Iran">Iran</option>
                <option value="Iraq">Iraq</option>
                <option value="Ireland">Ireland</option>
                <option value="Isle of Man">Isle of Man</option>
                <option value="Israel">Israel</option>
                <option value="Italy">Italy</option>
                <option value="Jamaica">Jamaica</option>
                <option value="Japan">Japan</option>
                <option value="Jordan">Jordan</option>
                <option value="Kazakhstan">Kazakhstan</option>
                <option value="Kenya">Kenya</option>
                <option value="Kiribati">Kiribati</option>
                <option value="Korea North">Korea North</option>
                <option value="Korea Sout">Korea South</option>
                <option value="Kuwait">Kuwait</option>
                <option value="Kyrgyzstan">Kyrgyzstan</option>
                <option value="Laos">Laos</option>
                <option value="Latvia">Latvia</option>
                <option value="Lebanon">Lebanon</option>
                <option value="Lesotho">Lesotho</option>
                <option value="Liberia">Liberia</option>
                <option value="Libya">Libya</option>
                <option value="Liechtenstein">Liechtenstein</option>
                <option value="Lithuania">Lithuania</option>
                <option value="Luxembourg">Luxembourg</option>
                <option value="Macau">Macau</option>
                <option value="Macedonia">Macedonia</option>
                <option value="Madagascar">Madagascar</option>
                <option value="Malaysia">Malaysia</option>
                <option value="Malawi">Malawi</option>
                <option value="Maldives">Maldives</option>
                <option value="Mali">Mali</option>
                <option value="Malta">Malta</option>
                <option value="Marshall Islands">Marshall Islands</option>
                <option value="Martinique">Martinique</option>
                <option value="Mauritania">Mauritania</option>
                <option value="Mauritius">Mauritius</option>
                <option value="Mayotte">Mayotte</option>
                <option value="Mexico">Mexico</option>
                <option value="Midway Islands">Midway Islands</option>
                <option value="Moldova">Moldova</option>
                <option value="Monaco">Monaco</option>
                <option value="Mongolia">Mongolia</option>
                <option value="Montserrat">Montserrat</option>
                <option value="Morocco">Morocco</option>
                <option value="Mozambique">Mozambique</option>
                <option value="Myanmar">Myanmar</option>
                <option value="Nambia">Nambia</option>
                <option value="Nauru">Nauru</option>
                <option value="Nepal">Nepal</option>
                <option value="Netherland Antilles">Netherland Antilles</option>
                <option value="Netherlands">Netherlands (Holland, Europe)</option>
                <option value="Nevis">Nevis</option>
                <option value="New Caledonia">New Caledonia</option>
                <option value="New Zealand">New Zealand</option>
                <option value="Nicaragua">Nicaragua</option>
                <option value="Niger">Niger</option>
                <option value="Nigeria">Nigeria</option>
                <option value="Niue">Niue</option>
                <option value="Norfolk Island">Norfolk Island</option>
                <option value="Norway">Norway</option>
                <option value="Oman">Oman</option>
                <option value="Pakistan">Pakistan</option>
                <option value="Palau Island">Palau Island</option>
                <option value="Palestine">Palestine</option>
                <option value="Panama">Panama</option>
                <option value="Papua New Guinea">Papua New Guinea</option>
                <option value="Paraguay">Paraguay</option>
                <option value="Peru">Peru</option>
                <option value="Phillipines">Philippines</option>
                <option value="Pitcairn Island">Pitcairn Island</option>
                <option value="Poland">Poland</option>
                <option value="Portugal">Portugal</option>
                <option value="Puerto Rico">Puerto Rico</option>
                <option value="Qatar">Qatar</option>
                <option value="Republic of Montenegro">Republic of Montenegro</option>
                <option value="Republic of Serbia">Republic of Serbia</option>
                <option value="Reunion">Reunion</option>
                <option value="Romania">Romania</option>
                <option value="Russia">Russia</option>
                <option value="Rwanda">Rwanda</option>
                <option value="St Barthelemy">St Barthelemy</option>
                <option value="St Eustatius">St Eustatius</option>
                <option value="St Helena">St Helena</option>
                <option value="St Kitts-Nevis">St Kitts-Nevis</option>
                <option value="St Lucia">St Lucia</option>
                <option value="St Maarten">St Maarten</option>
                <option value="St Pierre &amp; Miquelon">St Pierre &amp; Miquelon</option>
                <option value="St Vincent &amp; Grenadines">St Vincent &amp; Grenadines</option>
                <option value="Saipan">Saipan</option>
                <option value="Samoa">Samoa</option>
                <option value="Samoa American">Samoa American</option>
                <option value="San Marino">San Marino</option>
                <option value="Sao Tome &amp; Principe">Sao Tome &amp; Principe</option>
                <option value="Saudi Arabia">Saudi Arabia</option>
                <option value="Senegal">Senegal</option>
                <option value="Serbia">Serbia</option>
                <option value="Seychelles">Seychelles</option>
                <option value="Sierra Leone">Sierra Leone</option>
                <option value="Singapore">Singapore</option>
                <option value="Slovakia">Slovakia</option>
                <option value="Slovenia">Slovenia</option>
                <option value="Solomon Islands">Solomon Islands</option>
                <option value="Somalia">Somalia</option>
                <option value="South Africa">South Africa</option>
                <option value="Spain">Spain</option>
                <option value="Sri Lanka">Sri Lanka</option>
                <option value="Sudan">Sudan</option>
                <option value="Suriname">Suriname</option>
                <option value="Swaziland">Swaziland</option>
                <option value="Sweden">Sweden</option>
                <option value="Switzerland">Switzerland</option>
                <option value="Syria">Syria</option>
                <option value="Tahiti">Tahiti</option>
                <option value="Taiwan">Taiwan</option>
                <option value="Tajikistan">Tajikistan</option>
                <option value="Tanzania">Tanzania</option>
                <option value="Thailand">Thailand</option>
                <option value="Togo">Togo</option>
                <option value="Tokelau">Tokelau</option>
                <option value="Tonga">Tonga</option>
                <option value="Trinidad &amp; Tobago">Trinidad &amp; Tobago</option>
                <option value="Tunisia">Tunisia</option>
                <option value="Turkey">Turkey</option>
                <option value="Turkmenistan">Turkmenistan</option>
                <option value="Turks &amp; Caicos Is">Turks &amp; Caicos Is</option>
                <option value="Tuvalu">Tuvalu</option>
                <option value="Uganda">Uganda</option>
                <option value="Ukraine">Ukraine</option>
                <option value="United Arab Erimates">United Arab Emirates</option>
                <option value="United Kingdom">United Kingdom</option>
                <option value="United States of America">United States of America</option>
                <option value="Uraguay">Uruguay</option>
                <option value="Uzbekistan">Uzbekistan</option>
                <option value="Vanuatu">Vanuatu</option>
                <option value="Vatican City State">Vatican City State</option>
                <option value="Venezuela">Venezuela</option>
                <option value="Vietnam">Vietnam</option>
                <option value="Virgin Islands (Brit)">Virgin Islands (Brit)</option>
                <option value="Virgin Islands (USA)">Virgin Islands (USA)</option>
                <option value="Wake Island">Wake Island</option>
                <option value="Wallis &amp; Futana Is">Wallis &amp; Futana Is</option>
                <option value="Yemen">Yemen</option>
                <option value="Zaire">Zaire</option>
                <option value="Zambia">Zambia</option>
                <option value="Zimbabwe">Zimbabwe</option>
              </select>
            </div>
          </div>

        </div>
      </div>

      <!-- Security Settings Panel -->
      <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center gap-3 bg-slate-50">
          <div class="w-8 h-8 rounded-lg bg-red-500/20 text-red-600 flex items-center justify-center"><i class="fa-solid fa-shield-halved"></i></div>
          <h3 class="text-sm font-bold text-slate-900">Security &amp; Password</h3>
        </div>
        <div class="p-6 flex flex-col gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">New Password</label>
            <input type="password" name="p1" value=""
                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                   placeholder="Leave blank to keep current password">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Confirm New Password</label>
            <input type="password" name="p2" value=""
                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                   placeholder="Repeat only if changing password">
          </div>
        </div>
      </div>

    </div>

    <!-- Right Column: Banking & Payouts -->
    <div class="flex flex-col gap-6">
      
      <!-- Banking Panel -->
      <div class="bg-white border border-slate-200 shadow-sm rounded-2xl shadow-xl flex flex-col overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center gap-3 bg-slate-50">
          <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-600 flex items-center justify-center"><i class="fa-solid fa-wallet"></i></div>
          <h3 class="text-sm font-bold text-slate-900">Payout &amp; Banking Details</h3>
        </div>
        <div class="p-6 flex flex-col gap-4">
          
          <!-- Get Payment Via -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Get Payment Via</label>
            <select name="alwdpayment"
                    class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-[42px]">
              <option value="2" selected>Your Bank Account</option>
            </select>
          </div>

          <div class="text-xs text-slate-600 font-bold uppercase tracking-wider mt-4 pb-2 border-b border-slate-200">
            Bank Account Info
          </div>

          <!-- Account Type -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Account Type</label>
            <select name="acctype" required
                    class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-[42px]">
              <option value='0' <?php echo ($acctyppe == '0') ? 'selected' : ''; ?>>Select Type</option>	  
              <option value='1' <?php echo ($acctyppe == '1') ? 'selected' : ''; ?>>Current</option>
              <option value='2' <?php echo ($acctyppe == '2') ? 'selected' : ''; ?>>Savings</option>
            </select>
          </div>

          <!-- Bank Name -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Bank Name</label>
            <input type="text" name="bankname" value="<?php print mlmp_escape($bname) ?>"
                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                   placeholder="Bank Name">
          </div>

          <!-- Account Name -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Account Holder Name</label>
            <input type="text" name="accname" value="<?php print mlmp_escape($accnamee) ?>"
                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                   placeholder="Account Holder Name">
          </div>

          <!-- Account Number -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Account Number</label>
            <input type="text" name="accno" value="<?php print mlmp_escape($accnumber) ?>"
                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                   placeholder="Bank Account Number">
          </div>

          <!-- IFSC Code -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">IFSC Code</label>
            <input type="text" name="ifsccode" value="<?php print mlmp_escape($ifsc) ?>"
                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                   placeholder="IFSC Code">
          </div>

        </div>
      </div>

    </div>
  </div>

  <!-- Submit Button block -->
  <div class="mt-6 flex justify-center mb-8">
    <button type="submit"
            class="w-full max-w-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-4 px-6 rounded-xl transition-all shadow-lg shadow-indigo-500/30 hover:-translate-y-0.5 flex items-center justify-center gap-2">
      <i class="fa-solid fa-circle-check"></i> Save &amp; Update Profile Details
    </button>
  </div>

</form>

<script>
// Copy referral URL
function copyRefUrl() {
    var inp = document.getElementById('refUrl');
    inp.select(); inp.setSelectionRange(0, 99999);
    navigator.clipboard ? navigator.clipboard.writeText(inp.value) : document.execCommand('copy');
    var suc = document.getElementById('copySuccess');
    suc.style.display = 'block';
    setTimeout(function(){ suc.style.display = 'none'; }, 2500);
}
</script>

<?php include 'layout_footer.php'; ?>
