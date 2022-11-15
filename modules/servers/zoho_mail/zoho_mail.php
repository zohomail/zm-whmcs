<?php
header('X-Frame-Options: GOFORIT');
use WHMCS\Database\Capsule;
use WHMCS\Utility\Environment\WebHelper;
use WHMCS\Config\Setting;
use Respect\Validation\Rules\Length;
if (! defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function zoho_mail_MetaData()
{
    try {
        Capsule::schema()->create('zoho_mail', function ($table) {
            $table->string('domain');
            $table->string('zoid')
            ->unique();
            $table->string('superAdmin');
            $table->string('isverified');
            $table->string('url');
        });
    } catch (Exception $e) {}
    return array(
        'DisplayName' => 'Zoho Mail',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '1111',
        'DefaultSSLPort' => '1112',
        'ServiceSingleSignOnLabel' => 'Login to Panel as User',
        'AdminSingleSignOnLabel' => 'Login to Panel as Admin'
    );
}

function zoho_mail_ConfigOptions()
{
    $patharray = array();
    $patharray = explode('/', $_SERVER['REQUEST_URI']);
    $url = Setting::getValue('SystemURL');
    $patharray[1] = $url; // "http://".$_SERVER['HTTP_HOST'].substr(getcwd(),strlen($_SERVER['DOCUMENT_ROOT'])));
    $dir = preg_split("/\//", $_SERVER['PHP_SELF']);
    $config = array(
        'Provide Zoho API credentials' => array(
            'Description' => '<html><script type="text/javascript">
                           var tabval = window.location.hash;
                           document.getElementById("zm_tab_value").value = tabval.toString();
                           </script>
                           <form action=../modules/servers/zoho_mail/zm_oauthgrant.php method=post>
                           <label>Domain</label><br>
                           <select name="zm_dn" required>
                           <option value=".com">.com</option>
                           <option value=".eu">.eu</option>
                           <option value=".com.au">.com.au</option>
                           <option value=".in">.in</option>
                           </select><br><br>
                           <label>Client ID</label><br>
                           <input type="text" size="60" name="zm_ci" required/><br>
                           Generated from <a href="https://accounts.zoho.com/developerconsole" target=_blank>Zoho Developer Console</a><br><br>
                           <label>Client Secret</label><br>
                           <input type="text" size="60" name="zm_cs" required/><br>
                           Generated from <a href="https://accounts.zoho.com/developerconsole" target=_blank>Zoho Developer Console</a><br><br>
                           <label>Redirect URL</label><br>
                           <input type="text" size="60" name="zm_ru" value=' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/' . $dir[1] . '/modules/servers/zoho_mail/zm_oauthgrant.php required readonly/><br>
                           Redirect URL used to generate Client ID and Client Secret.<br><br>
                           <input type="hidden" id="zm_tab_value" name="zm_tab_value" value=""/>
                           <input type="hidden" name="zm_pi" value=' . $_REQUEST['id'] . '>
                           <button name="zm_submit" size="15">Authenticate</button>
                           </form></html>'
        )
    );
    try {
        if (Capsule::schema()->hasTable('zoho_mail_auth_table')) {
            $count = 0;
            $list = 0;
            foreach (Capsule::table('zoho_mail_auth_table')->get() as $client) {
                if (strpos($client->token, 'tab') == false && strlen($client->token) > 1) {
                    $list = $list + 1;
                    $count = 1;
                }
            }
            
            if ($count > 0 && $list > 0) {
                $conn = Capsule::connection()->getPdo();
                get_customer_list(get_access_token(array()));
                
                $sql = "SELECT Profile_Id,Email,Country,Custom_Id,Plan,Paid_Users,Payperiod,Registration_Date,Renewal_Date,ManageUrl,LicenseUrl FROM zoho_mail_customerlist_table ORDER BY Registration_Date DESC";
                $result = $conn->query($sql);
                $tableString = "<label>Status :&nbsp;</label><label style='color:green;'> Authenticated Successfully </label>"."\n";
                $tableString .= "<br><label></label><br><label><b>Customer Listing</b></label><br><TABLE BORDER=.1 >";
                $tableString .= "<TR><TD><b>Profile Id</b></TD>";
                $tableString .= "<TD><b>Email</b></TD>";
                $tableString .= "<TD><b>Country</b></TD>";
                $tableString .= "<TD><b>Custom Id</b></TD>";
                $tableString .= "<TD><b>Plan</b></TD>";
                $tableString .= "<TD><b>Paid Users</b></TD>";
                $tableString .= "<TD><b>Payperiod</b></TD>";
                $tableString .= "<TD><b>Registration Date</b></TD>";
                $tableString .= "<TD><b>Renewal Date</b></TD>";
                $tableString .= "<TD><b>License URL</b></TD>";
                $tableString .= "<TD><b>Mail Control Panel</b></TD></TR>";
                
                while ($currentRow = $result->fetch()) {
                    $tableString .= "<TR><TD>" . $currentRow['Profile_Id'] . "</TD>" . "\n";
                    $tableString .= "<TD>" . $currentRow['Email'] . "</TD>" . "\n";
                    $tableString .= "<TD>" . $currentRow['Country'] . "</TD>" . "\n";
                    $tableString .= "<TD>" . $currentRow['Custom_Id'] . "</TD>" . "\n";
                    $tableString .= "<TD>" . $currentRow['Plan'] . "</TD>" . "\n";
                    $tableString .= "<TD>" . $currentRow['Paid_Users'] . "</TD>" . "\n";
                    $tableString .= "<TD>" . $currentRow['Payperiod'] . "</TD>" . "\n";
                    $tableString .= "<TD>" . $currentRow['Registration_Date'] . "</TD>" . "\n";
                    $tableString .= "<TD>" . $currentRow['Renewal_Date'] . "</TD>" . "\n";
                    $tableString .= "<TD><a href=" . $currentRow['ManageUrl'] . ">Manage</a></TD>" . "\n";
                    $tableString .= "<TD><a href=" . $currentRow['LicenseUrl'] . ">Manage</a></TD></TR>" . "\n";
                }
                $config = array(
                    'Customers Listing' => array(
                        'Description' => $tableString . "</TABLE>"
                    )
                );
            }
        }
    } catch (Exception $e) {
    }
    return $config;
}

function zoho_mail_TestConnection(array $params)
{
    try {
        // Call the service's connection test function.
        
        $success = true;
        $errorMsg = '';
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall('provisioningmodule', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        
        $success = false;
        $errorMsg = $e->getMessage();
    }
    
    return array(
        'success' => $success,
        'error' => $errorMsg
    );
}

function zoho_mail_AdminCustomButtonArray()
{
    return array("Create Account" => "buttonOneFunction");
}

function zoho_mail_buttonOneFunction(array $params)
{
    try {
        return create_child_organization($params, "free", get_access_token($params));
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall('zoho_maill', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        
        return $e->getMessage();
    }
    
    return 'success';
}

function zoho_mail_AdminServicesTabFields(array $params)
{
    try {
        
        $cli = Capsule::table('zoho_mail')->where('domain', $params['domain'])->first();
        $response = array();
        $$authenticateStatus = '<h2 style="color:red;">UnAuthenticated</h2>';
        if (Capsule::schema()->hasTable('zoho_mail_auth_table')) {
            $count = 0;
            $list = 0;
            foreach (Capsule::table('zoho_mail_auth_table')->get() as $client) {
                $list = $list + 1;
                if ($client->token == 'test') {
                    $count = 1;
                }
            }
            if ($count == 0 && $list > 0) {
                $authenticateStatus = '<h2 style="color:green;">Authenticated</h2>';
            }
        }
        
        $verificationStatus;
        if (strcmp("true", $cli->isverified) == 0) {
            $verificationStatus = '<b style=color:green>Verified</b>';
        } else {
            $verificationStatus = '<b style=color:red>Not Verified</b>';
        }
        $planname = get_assigned_plan($params['clientsdetails']['email'], get_access_token(array()));
        if ($cli->superAdmin != null && $cli->zoid != null) {
            $resultArray['Manage Customer'] = '<form></form>
    <html>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
    body {font-family: Arial, Helvetica, sans-serif;}
    * {box-sizing: border-box;}
                
    /* The popup form - hidden by default */
    .form-popup {
        display: none;
        right: 15px;
        border: 3px solid #f1f1f1;
        z-index: 9;
    }
                
    body {font-family: Arial, Helvetica, sans-serif;}
                
/* The Modal (background) */
.modal {
  display: none; /* Hidden by default */
  position: fixed; /* Stay in place */
  z-index: 1; /* Sit on top */
  padding-top: 100px; /* Location of the box */
  left: 0;
  top: 0;
  width: 100%; /* Full width */
  height: 100%; /* Full height */
  overflow: auto; /* Enable scroll if needed */
  background-color: rgb(0,0,0); /* Fallback color */
  background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
}
                
/* Modal Content */
.modal-content {
  position: relative;
  background-color: #fefefe;
  margin: auto;
  padding: 0;
  border: 1px solid #888;
  width: 30%;
  box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19);
  -webkit-animation-name: animatetop;
  -webkit-animation-duration: 0.4s;
  animation-name: animatetop;
  animation-duration: 0.4s
}
                
/* Add Animation */
@-webkit-keyframes animatetop {
  from {top:-300px; opacity:0}
  to {top:0; opacity:1}
}
                
@keyframes animatetop {
  from {top:-300px; opacity:0}
  to {top:0; opacity:1}
}
                
/* The Close Button */
.close {
  color: black;
  float: right;
  font-size: 28px;
  font-weight: bold;
}
                
.close:hover,
.close:focus {
  color: 	#000000;
  text-decoration: none;
  cursor: pointer;
}
                
.modal-header {
                
  padding: 2px 16px;
  background-color: #D3D3D3;
  color: black;
}
.modal-button{margin-bottom: 25px;margin-top: 25px;}
.modal-body {padding: 2px 16px;}
    </style>
    </head>
    <body>
                
<!-- The Modal -->
<div id="myModal" class="modal">
                
  <!-- Modal content -->
  <div class="modal-content">
    <div class="modal-header">
      <span onclick="return closefrm()" class="close">&times;</span>
      <h2><b>Enable License</b></h2>
    </div>
    <div class="modal-body">
      <p><b>Purchase Details</b></p>
      <table id="confrmTable">
        <tbody id="tbodyid1"></tbody>
      </table>
      <center><input  class="modal-button" type="submit" name="cnfrmBtn" id="cnfrmBtn" value="Confirm Purchase"></center>
    </div>
  </div>
</div>
                
<div class="form-popup2" id="extendtrialForm">
<form id="myextendtrialform" action=../modules/servers/zoho_mail/zm_assignplan.php method=post>
<lable>Are you sure you want extend trial for 15 more days for Test?</lable>
<input type="hidden" name="extend" id="extend" value="true">
<br><br><input type="hidden" name="zm_uid" value=' . $_REQUEST['userid'] . '>
<button name="zm_submit3" size="15" id="myButton4" type="button">Extend Trial</button>
<input type="reset" value="Cancel" onclick="closeForm()"/>
</form>
</div>
    
<div class="form-popup1" id="trialAssignForm">
<form id="mytrialform" action=../modules/servers/zoho_mail/zm_assignplan.php method=post>
<lable>Enable your customer a premium plan for 15 day trial!</lable><br><br>
    
<input type="radio" name="JTP" id="JTP" checked="checked" value="Workplace Standard Trial">Workplace Standard Trial<br>
<input type="radio" name="JTP" id="JTP" value="Workplace Professional Trial">Workplace Professional Trial<br>
<input type="radio" name="JTP" id="JTP" value="Mail Premium Trial">Mail Premium Trial<br>
<br><br><input type="hidden" name="zm_uid" value=' . $_REQUEST['userid'] . '>
    
<label>Explore! our premium features.</lable><br><br>
<center><button name="zm_submit2" size="15" id="myButton3" type="button">Start Trial</button>
<input type="reset" value="Back" onclick="closeForm()"/></center>
</form>
</div>
    <div class="button" style="width:100%" id="assignplan">
    <input id="open" type="button" onclick="openForm()" value="Assign Paid Plan"/>
    <input id="openstartTrial" type="button" onclick="openstartTrialForm()" value="StartTrial" />
    <input id="openExtendTrial" type="button" onclick="openExtendTrialForm()" value="ExtendTrial" />
    <input id="downgradetofree" type="button" value="Complete downgrade to free" />
    </div>
    <div class="form-popup" id="planAssignForm">
        <form id="myForm" action=../modules/servers/zoho_mail/zm_assignplan.php method=post>
            <table id = "planTable">
                <tr>
                    <td>
                        <label for="payperiod"><b>Pay period</b></label>
                    </td>
                    <td>
                        <select onchange="onPlanDurationSelect(this)" name="payperiod" id="payperiod">
                            <option value="YEAR">Yearly</option>
                            <option value="MONT">Monthly</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="plan"><b>Plan</b></label>
                    </td>
                    <td>
                        <select onchange="onPayPeriodSelect(this)" name="plan" id="plan">
                        </select>
                    </td>
                </tr>
                <tr class="u0">
                    <td><label><b>Users</b></label>
                    </td>
                    <td><input type="number" placeholder="Enter user count" name="u0" value="0" id="u0" min="1">
                    </td>
                </tr>
                <tr class="mu1" style="display:none">
                    <td><label><b>Standard User</b></label>
                    </td>
                    <td> <input type="number" placeholder="Enter user count" name="mu1" id="mu1" value="0" min="0">
                    </td>
                </tr>
                <tr class="mu2" style="display:none">
                    <td><label><b>Professional User</b></label>
                    </td>
                    <td> <input type="number" placeholder="Enter user count" name="mu2" id="mu2" value="0" min="0">
                    </td>
                </tr>
                <tr class="yu1" style="display:none">
                    <td><label><b>Standard User</b></label>
                    </td>
                    <td> <input type="number" placeholder="Enter user count" name="yu1" id="yu1" value="0" min="0">
                    </td>
                </tr>
                <tr class="yu2" style="display:none">
                    <td><label><b>Professional User</b></label>
                    </td>
                    <td> <input type="number" placeholder="Enter user count" name="yu2" id="yu2" value="0" min="0">
                    </td>
                </tr>
                <tr class="yu3" style="display:none">
                    <td><label><b>Mail Lite User</b></label>
                    </td>
                    <td> <input type="number" placeholder="Enter user count" name="yu3" id="yu3" value="0" min="0">
                    </td>
                </tr>
                <tr class="yu4" style="display:none">
                    <td><label><b>Mail 10GB User</b></label>
                    </td>
                    <td> <input type="number" placeholder="Enter user count" name="yu4" id="yu4" value="0" min="0">
                    </td>
                </tr>
                <tr class="yu5" style="display:none">
                    <td><label><b>Mail Premium User</b></label>
                    </td>
                    <td> <input type="number" placeholder="Enter user count" name="yu5" id="yu5" value="0" min="0">
                    </td>
                </tr>
                <input type="hidden" name="upgrade" id="upgrade" value="false">
                <input type="hidden" name="zm_uid" value=' . $_REQUEST['userid'] . '>
                <tbody id="tbodyid">
                </tbody>
            </table>
            <div class="cancelButton" >
            <button name="zm_submit1" size="15" id="myButton1" onClick="return getData()" type="button">Enable License</button>
            <button name="zm_submit1" size="15" id="myButton2" onClick="return getData()" type="button">Upgrade License</button>
            <input type="reset" value="Close" onclick="closeForm()"/>
        </form>
    </div>
    <div id="message"></div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <script type="text/javascript">
                    
    var isStartTrial = false;
    var isExtendTrial = false;
document.getElementById("trialAssignForm").style.display = "none";
document.getElementById("extendtrialForm").style.display = "none";
document.getElementById("downgradetofree").style.display = "none";
document.getElementById("openstartTrial").style.display = "none";
document.getElementById("openExtendTrial").style.display = "none";
 $(document).ready(function(){
$.ajax({
         type: \'POST\',
         url:  "../modules/servers/zoho_mail/zm_subscriptionplan.php",
        data: {
        userID: ' . $_REQUEST['userid'] .',
        case : "getOrgdetails"
    }
      })
         .done( function (responseText) {
            json = JSON.parse(responseText);
            
if(json.data[0].isTrialAllowed ){
    isStartTrial = true;
    document.getElementById("openstartTrial").style.display = "inline-block";}
if(json.data[0].extendTrial ){
   isExtendTrial = true;
document.getElementById("openExtendTrial").style.display = "inline-block";}
            
if((isStartTrial || isExtendTrial) || json.data[0].basePlan == "Mail Free")
{
    document.getElementById("downgradetofree").style.display = "none";
}
else{
            
document.getElementById("downgradetofree").style.display = "inline-block";
}
            
         $("#myButton3").click(function () {
    $("#mytrialform").submit();
  });
$("#myButton4").click(function () {
    $("#myextendtrialform").submit();
  });
})
         .fail( function (jqXHR, status, error) {
            alert(jqXHR.responseText);
         })
         .always( function() {
         });
}
)
function closefrm(){
    document.getElementById("myModal").style.display = "none";
    return true;
}
function getData(){
            
// Get the modal
var modal = document.getElementById("myModal");
var status = openWindow();
var click = "";
// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];
if(status == true){
  modal.style.display = "block";}
            
$("#cnfrmBtn").click(function () {
    event.preventDefault();
    $("#myForm").submit();
  });
            
// When the user clicks on <span> (x), close the modal
span.onclick = function(e) {
  modal.style.display = "none";
    return false;
}
            
// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
    return false;
  }
}
}
var plan_res = "";
        document.getElementById("planAssignForm").style.display = "none";
var json = `{
        "data": {
            "Monthly": [
            {
                "planname": "Workplace Standard",
                "planId": 10730,
                "addonId": 10779
            },
            {
                "planname": "Workplace Professional",
                "planId": 10731,
                "addonId": 10780
            },
            {
                "planname": "Mail Flexible",
                "planId": 17045
            }
        ],
        "Yearly": [
            {
                "planname": "Workplace Standard",
                "planId": 10730,
                "addonId": 10779
            },
            {
                "planname": "Workplace Professional",
                "planId": 10731,
                "addonId": 10780
            },
            {
                "planname": "Mail lite",
                "planId": 17033,
                "addonId": 17083
            },
            {
                "planname": "Mail 10GB",
                "planId": 17034,
                "addonId": 17084
            },
            {
                "planname": "Mail Premium",
                "planId": 17035,
                "addonId": 10792
            },
            {
                "planname": "Mail Flexible",
                "planId": 17045
            }
        ],
            "ExtraStorage": [
            {
                "planname": "Extra_5_GB",
                "addonId": 191
            },
            {
                "planname": "Extra_25_GB",
                "addonId": 188
            },
            {
                "planname": "Extra_50_GB",
                "addonId": 187
            },
            {
                "planname": "Extra_100_GB",
                "addonId": 186
            },
            {
                "planname": "Extra_200_GB",
                "addonId": 185
            }
        ]
        }
    }`;
var res = "";
var u0 = 0;
var mu1 = 0;
var mu2 = 0;
var yu1 = 0;
var yu2 = 0;
var yu3 = 0;
var yu4 = 0;
var yu5 = 0;
var extra_5GB = 0;
var extra_25GB = 0;
var extra_50GB = 0;
var extra_100GB = 0;
var extra_200GB = 0;
$("#downgradetofree").click(function(e){
    $.post("../modules/servers/zoho_mail/zm_subscriptionplan.php",{
        userID: ' . $_REQUEST['userid'] .',
        case : "downgradetofree",
        comment : "developer"
    })
    .done(function(result, status, xhr){
        if(result == "success"){
            alert("Cancelled and moved to Free Plan");
            location.reload();
        }
else{
    alert(result);
}
     })
    .fail(function(xhr, status, error){
        $("#message").html("Result: " + status + " " + error + " " + xhr.status + " " + xhr.statusText)})
});
            
       var obj = JSON.parse(json);
$("#open").click(function (e) {
    $.post("../modules/servers/zoho_mail/zm_subscriptionplan.php",
    {
        userID: ' . $_REQUEST['userid'] .',
        case : "getSubscription details"
    })
    .done(function (result, status, xhr) {
document.getElementById("openstartTrial").style.display = "none";
document.getElementById("openExtendTrial").style.display = "none";
    var select = document.getElementById("plan");
    $("#message").html("");
$("#plan").val("17034");
for(var i=0;i<obj["data"]["ExtraStorage"].length;i++)
{
    $("#row"+i).remove();
}
for(var i=0;i<obj["data"]["ExtraStorage"].length;i++)
      {
        var markup = "<tr id="+"row"+ i + "><td><label><b>"+obj["data"]["ExtraStorage"][i]["planname"] + "</b></label></td>"+ "<td>"+"<input type=\'number\' placeholder=\'\' name="+obj["data"]["ExtraStorage"][i]["planname"]+" id="+obj["data"]["ExtraStorage"][i]["addonId"] +" value=\'0\' min=\'0\' >"+"</td>"+"</tr>";
        $("#tbodyid").append(markup);
      }
selctplan = document.getElementById("plan");
clearOptions(selctplan);
for(var i=0;i<obj["data"]["Yearly"].length;i++)
      {
        var option = document.createElement("option");
        option.value = obj["data"]["Yearly"][i]["planId"];
        option.text = obj["data"]["Yearly"][i]["planname"];
        selctplan.appendChild(option);
}
            
showNonFlexibleUsersCountUI();
plan_res = result;
if(result == "Mail Free" || result == "Workplace Standard Trial" ||result == "Workplace Professional Trial" ||result == "Mail Premium Trial"){
$("#plan").val($("#plan option:first").val());
document.getElementById("planAssignForm").style.display = "block";
document.getElementById("myButton2").style.display = "none";
}
else{
 res = JSON.parse(result);
            
if(res.result == "success" && res.licensedetails.paiduser){
document.getElementById("myButton1").style.display = "none";
$("#upgrade").val("true");
$("#payperiod").val(res.licensedetails.payperiod);
if(res.licensedetails.payperiod=="MONT")
{
selctplan = document.getElementById("plan");
clearOptions(selctplan);
for(var i=0;i<obj["data"]["Monthly"].length;i++)
      {
        var option = document.createElement("option");
        option.value = obj["data"]["Monthly"][i]["planId"];
        option.text = obj["data"]["Monthly"][i]["planname"];
        selctplan.appendChild(option);
}
showNonFlexibleUsersCountUI();
}
if(res.licensedetails.payperiod == "YEAR")
{
   var selectobject = document.getElementById("payperiod");
for (var i=0; i<selectobject.length; i++) {
    if (selectobject.options[i].value == "MONT")
        selectobject.remove(i);
}
}
$("#plan").val(res.licensedetails.planname);
}
else
{
document.getElementById("myButton2").style.display = "none";
}
            
document.getElementById("planAssignForm").style.display = "block";
if(res.result == "success" && res.licensedetails.paiduser){
$("#plan").val(res.licensedetails.planid);
}
else{
$("#plan").val($("#plan option:first").val());
}
            
const names = res.licensedetails.addondetails;
const obj2 = {};
            
names.forEach((elem, i) => {
  obj2[i] = elem
})
for(var i=0;i<Object.keys(obj2).length;i++){
if(obj2[i]["addonname"] == "User")
{
    document.getElementById("u0").value = obj2[i]["addonvalue"];
    u0 = obj2[i]["addonvalue"];
}
            
if(res.licensedetails.planname == "Mail Flexible Plan")
{
    onPayPeriodSelect1(res.licensedetails.planid);
    if(res.licensedetails.payperiod == "MONT"){
        if(obj2[i]["addonname"] == "Professional User"){
            
            document.getElementById("mu2").value = obj2[i]["addonvalue"];
            mu2 = obj2[i]["addonvalue"];
        }
        if(obj2[i]["addonname"] == "Standard User"){
            
            document.getElementById("mu1").value = obj2[i]["addonvalue"];
            mu1 = obj2[i]["addonvalue"];
        }
    }
    else if(res.licensedetails.payperiod == "YEAR"){
        if(obj2[i]["addonname"] == "Professional User"){
            document.getElementById("yu2").value = obj2[i]["addonvalue"];
            yu2 = obj2[i]["addonvalue"];
        }
        if(obj2[i]["addonname"] == "Standard User"){
            document.getElementById("yu1").value = obj2[i]["addonvalue"];
            yu1 = obj2[i]["addonvalue"];
        }
        if(obj2[i]["addonname"] == "Mail Lite User"){
            document.getElementById("yu3").value = obj2[i]["addonvalue"];
            yu3 = obj2[i]["addonvalue"];
        }
        if(obj2[i]["addonname"] == "Mail 10GB User"){
            document.getElementById("yu4").value = obj2[i]["addonvalue"];
            yu4 = obj2[i]["addonvalue"];
        }
        if(obj2[i]["addonname"] == "Mail Premium User"){
            document.getElementById("yu5").value = obj2[i]["addonvalue"];
            yu5 = obj2[i]["addonvalue"];
        }
    }
}
if(obj2[i]["addonid"] == "191")
{
    document.getElementById(obj2[i]["addonid"]).value = obj2[i]["addonvalue"];
    extra_5GB = obj2[i]["addonvalue"];
}
if(obj2[i]["addonid"] == "188")
{
    document.getElementById(obj2[i]["addonid"]).value = obj2[i]["addonvalue"];
    extra_25GB = obj2[i]["addonvalue"];
            
}
if(obj2[i]["addonid"] == "187")
{
    document.getElementById(obj2[i]["addonid"]).value = obj2[i]["addonvalue"];
    extra_50GB = obj2[i]["addonvalue"];
            
}
if(obj2[i]["addonid"] == "186")
{
    document.getElementById(obj2[i]["addonid"]).value = obj2[i]["addonvalue"];
    extra_100GB = obj2[i]["addonvalue"];
            
}
if( obj2[i]["addonid"] == "185")
{
    document.getElementById(obj2[i]["addonid"]).value = obj2[i]["addonvalue"];
    extra_200GB = obj2[i]["addonvalue"];
            
}
}
}
})
    .fail(function (xhr, status, error) {
        $("#message").html("Result: " + status + " " + error + " " + xhr.status + " " + xhr.statusText)
    });
});
function openWindow() {
    var rowCount = $("#tbodyid1 tr").length;
    for (var i = rowCount-1 ; i > -1; i--) {
        tbodyid1.deleteRow(i);
    }
            
	var purchaseDetails = "";
	if (res != "" && res.result == "success" && res.licensedetails.paiduser) {
		if (document.getElementById("payperiod").value == res.licensedetails.payperiod &&
			document.getElementById("plan").value == res.licensedetails.planid) {
            
			if (u0 == document.getElementById("u0").value &&
				mu1 == document.getElementById("mu1").value &&
				mu2 == document.getElementById("mu2").value &&
				yu1 == document.getElementById("yu1").value &&
				yu2 == document.getElementById("yu2").value &&
				yu3 == document.getElementById("yu3").value &&
				yu4 == document.getElementById("yu4").value &&
				yu5 == document.getElementById("yu5").value &&
				extra_5GB == document.getElementById("191").value &&
				extra_25GB == document.getElementById("188").value &&
				extra_50GB == document.getElementById("187").value &&
				extra_100GB == document.getElementById("186").value &&
				extra_200GB == document.getElementById("185").value) {
				alert("You have not done any changes.!!!!!");
				return false;
			}
		}
	}
    else if(plan_res == "Mail Free" || plan_res == "Workplace Standard Trial" ||plan_res == "Workplace Professional Trial" ||plan_res == "Mail Premium Trial"){
        if (document.getElementById("u0").value == 0 && document.getElementById("plan").value != "17045"){
                alert("Minimum 1 Users is required");
				return false;
        }
        if (u0 == document.getElementById("u0").value &&
				mu1 == document.getElementById("mu1").value &&
				mu2 == document.getElementById("mu2").value &&
				yu1 == document.getElementById("yu1").value &&
				yu2 == document.getElementById("yu2").value &&
				yu3 == document.getElementById("yu3").value &&
				yu4 == document.getElementById("yu4").value &&
				yu5 == document.getElementById("yu5").value &&
				extra_5GB == document.getElementById("191").value &&
				extra_25GB == document.getElementById("188").value &&
				extra_50GB == document.getElementById("187").value &&
				extra_100GB == document.getElementById("186").value &&
				extra_200GB == document.getElementById("185").value) {
				alert("You have not done any changes.!!!!!");
				return false;
			}
    }
	if (document.getElementById("payperiod").value == "MONT") {
            
		let text = "Purchase Details" + "\r\n\r\n";
		let plan = "";
		if (document.getElementById("plan").value == "10730") {
			plan = "Workplace Standard";
		} else if (document.getElementById("plan").value == "10731") {
			plan = "Workplace Professional";
		} else if (document.getElementById("plan").value == "17045") {
			plan = "Mail Flexible";
		}
		purchaseDetails += "<tr><td>Plan</td><td>" + plan + "</td></tr>";
		purchaseDetails += "<tr><td>Payperiod</td><td>" + "Monthly" + "</td></tr>";
		text += "Plan                          : " + plan + "\r\n" + "Payperiod                     : Monthly";
            
		try {
			if (document.getElementById("191").value > 0) {
				purchaseDetails += "<tr><td>Extra_5_GB</td><td>" + document.getElementById("191").value;
				text += "\r\n" + "Extra_5_GB                    : " + document.getElementById("191").value;
				if (document.getElementById("191").value != extra_5GB) {
					if (document.getElementById("191").value > extra_5GB) {
						purchaseDetails += "(Upgrade: " + extra_5GB + " -> " + document.getElementById("191").value + ")";
						text += "(Upgrade: " + extra_5GB + " -> " + document.getElementById("191").value + ")";
					} else {
						purchaseDetails += "(Downgrade: " + extra_5GB + " -> " + document.getElementById("191").value + ")";
						text += "(Downgrade: " + extra_5GB + " -> " + document.getElementById("191").value + ")";
					}
				}
				purchaseDetails += "</td></tr>";
			}
			if (document.getElementById("188").value > 0) {
				purchaseDetails += "<tr><td>Extra_25_GB</td><td>" + document.getElementById("188").value;
				text += "\r\n" + "Extra_25_GB                   : " + document.getElementById("188").value;
				if (document.getElementById("188").value != extra_25GB) {
					if (document.getElementById("188").value > extra_25GB) {
						purchaseDetails += "(Upgrade: " + extra_25GB + " -> " + document.getElementById("188").value + ")";
						text += "(Upgrade: " + extra_25GB + " -> " + document.getElementById("188").value + ")";
					} else {
						purchaseDetails += "(Downgrade: " + extra_25GB + " -> " + document.getElementById("188").value + ")";
						text += "(Downgrade: " + extra_25GB + " -> " + document.getElementById("188").value + ")";
					}
				}
				purchaseDetails += "</td></tr>";
			}
			if (document.getElementById("187").value > 0) {
				purchaseDetails += "<tr><td>Extra_50_GB</td><td>" + document.getElementById("187").value;
				text += "\r\n" + "Extra_50_GB                   : " + document.getElementById("187").value;
				if (document.getElementById("187").value != extra_50GB) {
					if (document.getElementById("187").value > extra_50GB) {
						purchaseDetails += "(Upgrade: " + extra_50GB + " -> " + document.getElementById("187").value + ")";
						text += "(Upgrade: " + extra_50GB + " -> " + document.getElementById("187").value + ")";
					} else {
						purchaseDetails += "(Downgrade: " + extra_50GB + " -> " + document.getElementById("187").value + ")";
						text += "(Downgrade: " + extra_50GB + " -> " + document.getElementById("187").value + ")";
					}
				}
				purchaseDetails += "</td></tr>";
			}
            
			if (document.getElementById("186").value > 0) {
				purchaseDetails += "<tr><td>Extra_100_GB</td><td>" + document.getElementById("186").value;
				text += "\r\n" + "Extra_100_GB                  : " + document.getElementById("186").value;
				if (document.getElementById("186").value != extra_100GB) {
					if (document.getElementById("186").value > extra_100GB) {
						purchaseDetails += "(Upgrade: " + extra_100GB + " -> " + document.getElementById("186").value + ")";
						text += "(Upgrade: " + extra_100GB + " -> " + document.getElementById("186").value + ")";
					} else {
						purchaseDetails += "(Downgrade: " + extra_100GB + " -> " + document.getElementById("186").value + ")";
						text += "(Downgrade: " + extra_100GB + " -> " + document.getElementById("186").value + ")";
					}
				}
				purchaseDetails += "</td></tr>";
			}
			if (document.getElementById("185").value > 0) {
				purchaseDetails += "<tr><td>Extra_200_GB</td><td>" + document.getElementById("185").value;
				text += "\r\n" + "Extra_200_GB                  : " + document.getElementById("185").value;
				if (document.getElementById("185").value != extra_200GB) {
					if (document.getElementById("185").value > extra_200GB) {
						purchaseDetails += "(Upgrade: " + extra_200GB + " -> " + document.getElementById("185").value + ")";
						text += "(Upgrade: " + extra_200GB + " -> " + document.getElementById("185").value + ")";
					} else {
						purchaseDetails += "(Downgrade: " + extra_200GB + " -> " + document.getElementById("185").value + ")";
						text += "(Downgrade: " + extra_200GB + " -> " + document.getElementById("185").value + ")";
					}
				}
				purchaseDetails += "</td></tr>";
            
			}
			if (document.getElementById("plan").value == "17045") {
				if (document.getElementById("mu1").value > 0) {
					purchaseDetails += "<tr><td>Standard User</td><td>" + document.getElementById("mu1").value;
					text += "\r\n" + "Standard User                 : " + document.getElementById("mu1").value;
					if (document.getElementById("mu1").value != mu1) {
						if (document.getElementById("mu1").value > mu1) {
							purchaseDetails += "(Upgrade: " + mu1 + " -> " + document.getElementById("mu1").value + ")";
							text += "(Upgrade: " + mu1 + " -> " + document.getElementById("mu1").value + ")";
						} else {
							purchaseDetails += "(Downgrade: " + mu1 + " -> " + document.getElementById("mu1").value + ")";
							text += "(Downgrade: " + mu1 + " -> " + document.getElementById("mu1").value + ")";
						}
					}
					purchaseDetails += "</td></tr>";
				}
				if (document.getElementById("mu2").value > 0) {
					purchaseDetails += "<tr><td>Professional User</td><td>" + document.getElementById("mu2").value;
					text += "\r\n" + "Professional User             : " + document.getElementById("mu2").value;
					if (document.getElementById("mu2").value != mu2) {
						if (document.getElementById("mu2").value > mu2) {
							purchaseDetails += "(Upgrade: " + mu2 + " -> " + document.getElementById("mu2").value + ")";
							text += "(Upgrade: " + mu2 + " -> " + document.getElementById("mu2").value + ")";
						} else {
							purchaseDetails += "(Downgrade: " + mu2 + " -> " + document.getElementById("mu2").value + ")";
							text += "(Downgrade: " + mu2 + " -> " + document.getElementById("mu2").value + ")";
						}
					}
					purchaseDetails += "</td></tr>";
            
				}
			} else if (document.getElementById("u0").value > 0) {
				purchaseDetails += "<tr><td>Users</td><td>" + document.getElementById("u0").value;
				text += "\r\n" + "Users                         : " + document.getElementById("u0").value;
				if (document.getElementById("u0").value != u0) {
					if (document.getElementById("u0").value > u0) {
						purchaseDetails += "(Upgrade: " + u0 + " -> " + document.getElementById("u0").value + ")";
						text += "(Upgrade: " + u0 + " -> " + document.getElementById("u0").value + ")";
					} else {
						purchaseDetails += "(Downgrade: " + u0 + " -> " + document.getElementById("u0").value + ")";
						text += "(Downgrade: " + u0 + " -> " + document.getElementById("u0").value + ")";
					}
				}
				purchaseDetails += "</td></tr>";
			}
		} catch (err) {
			text += err.message;
		}
		if (purchaseDetails != null) {
			$("#tbodyid1").append(purchaseDetails);
			return true;
		}
		return false;
	}
	else if (document.getElementById("payperiod").value == "YEAR") {
		let text = "Purchase Details" + "\r\n\r\n";
		let plan = "";
		if (document.getElementById("plan").value == "10730") {
			plan = "Workplace Standard";
		} else if (document.getElementById("plan").value == "10731") {
			plan = "Workplace Professional";
		} else if (document.getElementById("plan").value == "17033") {
			plan = "Mail Lite";
		} else if (document.getElementById("plan").value == "17034") {
			plan = "Mail 10GB";
		} else if (document.getElementById("plan").value == "17035") {
			plan = "Mail Premium";
		} else if (document.getElementById("plan").value == "17045") {
			plan = "Mail Flexible";
		}
		purchaseDetails += "<tr><td>Plan</td><td>";
            
		text += "Plan                          : ";
        try{
		if (document.getElementById("plan").value != res.licensedetails.planid) {
			purchaseDetails += res.licensedetails.planname + " -> " + plan;
			text += res.licensedetails.planname + " -> " + plan + "\r\n" + "Payperiod                     : ";
		} else {
			purchaseDetails += plan;
			text += plan + "\r\n" + "Payperiod                     : ";
		}}
        catch(err){
            purchaseDetails += plan;
			text += plan + "\r\n" + "Payperiod                     : ";
        }
		purchaseDetails += "</td></tr><tr><td>payperiod</td><td>";
        try{
		if (document.getElementById("payperiod").value != res.licensedetails.payperiod) {
			purchaseDetails += "Monthly -> Yearly";
			text += "Monthly -> Yearly";
		} else {
			purchaseDetails += "Yearly";
			text += "Yearly";
		}}
        catch(err){purchaseDetails += "Yearly";
			text += "Yearly";}
		purchaseDetails += "</td></tr>";
            
		try {
			if (document.getElementById("191").value > 0) {
				purchaseDetails += "<tr><td>Extra_5_GB</td><td>" + document.getElementById("191").value;
				text += "\r\n" + "Extra_5_GB                    : " + document.getElementById("191").value;
				if (document.getElementById("191").value != extra_5GB) {
					if (document.getElementById("191").value > extra_5GB) {
						purchaseDetails += "(Upgrade: " + extra_5GB + " -> " + document.getElementById("191").value + ")";
						text += "(Upgrade: " + extra_5GB + " -> " + document.getElementById("191").value + ")";
					} else {
						purchaseDetails += "(Downgrade: " + extra_5GB + " -> " + document.getElementById("191").value + ")";
						text += "(Downgrade: " + extra_5GB + " -> " + document.getElementById("191").value + ")";
					}
				}
				purchaseDetails += "</td></tr>";
			}
			if (document.getElementById("188").value > 0) {
				purchaseDetails += "<tr><td>Extra_25_GB</td><td>" + document.getElementById("188").value;
				text += "\r\n" + "Extra_25_GB                   : " + document.getElementById("188").value;
				if (document.getElementById("188").value != extra_25GB) {
					if (document.getElementById("188").value > extra_25GB) {
						purchaseDetails += "(Upgrade: " + extra_25GB + " -> " + document.getElementById("188").value + ")";
						text += "(Upgrade: " + extra_25GB + " -> " + document.getElementById("188").value + ")";
					} else {
						purchaseDetails += "(Downgrade: " + extra_25GB + " -> " + document.getElementById("188").value + ")";
						text += "(Downgrade: " + extra_25GB + " -> " + document.getElementById("188").value + ")";
					}
				}
				purchaseDetails += "</td></tr>";
			}
			if (document.getElementById("187").value > 0) {
				purchaseDetails += "<tr><td>Extra_50_GB</td><td>" + document.getElementById("187").value;
				text += "\r\n" + "Extra_50_GB                   : " + document.getElementById("187").value;
				if (document.getElementById("187").value != extra_50GB) {
					if (document.getElementById("187").value > extra_50GB) {
						purchaseDetails += "(Upgrade: " + extra_50GB + " -> " + document.getElementById("187").value + ")";
						text += "(Upgrade: " + extra_50GB + " -> " + document.getElementById("187").value + ")";
					} else {
						purchaseDetails += "(Downgrade: " + extra_50GB + " -> " + document.getElementById("187").value + ")";
						text += "(Downgrade: " + extra_50GB + " -> " + document.getElementById("187").value + ")";
					}
				}
				purchaseDetails += "</td></tr>";
			}
			if (document.getElementById("186").value > 0) {
				purchaseDetails += "<tr><td>Extra_100_GB</td><td>" + document.getElementById("186").value;
				text += "\r\n" + "Extra_100_GB                  : " + document.getElementById("186").value;
				if (document.getElementById("186").value != extra_100GB) {
					if (document.getElementById("186").value > extra_100GB) {
						purchaseDetails += "(Upgrade: " + extra_100GB + " -> " + document.getElementById("186").value + ")";
						text += "(Upgrade: " + extra_100GB + " -> " + document.getElementById("186").value + ")";
					} else {
						purchaseDetails += "(Downgrade: " + extra_100GB + " -> " + document.getElementById("186").value + ")";
						text += "(Downgrade: " + extra_100GB + " -> " + document.getElementById("186").value + ")";
					}
				}
				purchaseDetails += "</td></tr>";
			}
			if (document.getElementById("185").value > 0) {
				purchaseDetails += "<tr><td>Extra_200_GB</td><td>" + document.getElementById("185").value;
				text += "\r\n" + "Extra_200_GB                  : " + document.getElementById("185").value;
				if (document.getElementById("185").value != extra_200GB) {
					if (document.getElementById("185").value > extra_200GB) {
						purchaseDetails += "(Upgrade: " + extra_200GB + " -> " + document.getElementById("185").value + ")";
						text += "(Upgrade: " + extra_200GB + " -> " + document.getElementById("185").value + ")";
					} else {
						purchaseDetails += "(Downgrade: " + extra_200GB + " -> " + document.getElementById("185").value + ")";
						text += "(Downgrade: " + extra_200GB + " -> " + document.getElementById("185").value + ")";
					}
				}
				purchaseDetails += "</td></tr>";
			}
			if (document.getElementById("plan").value == "17045") {
				if (document.getElementById("yu1").value > 0) {
					purchaseDetails += "<tr><td>Standard User</td><td>" + document.getElementById("yu1").value;
					text += "\r\n" + "Standard User                 : " + document.getElementById("yu1").value;
					if (document.getElementById("yu1").value != yu1) {
						if (document.getElementById("yu1").value > yu1) {
							purchaseDetails += "(Upgrade: " + yu1 + " -> " + document.getElementById("yu1").value + ")";
							text += "(Upgrade: " + yu1 + " -> " + document.getElementById("yu1").value + ")";
						} else {
							purchaseDetails += "(Downgrade: " + yu1 + " -> " + document.getElementById("yu1").value + ")";
							text += "(Downgrade: " + yu1 + " -> " + document.getElementById("yu1").value + ")";
						}
					}
					purchaseDetails += "</td></tr>";
				}
				if (document.getElementById("yu2").value > 0) {
					purchaseDetails += "<tr><td>Professional User</td><td>" + document.getElementById("yu2").value;
					text += "\r\n" + "Professional User             : " + document.getElementById("yu2").value;
					if (document.getElementById("yu2").value != yu2) {
						if (document.getElementById("yu2").value > yu2) {
							purchaseDetails += "(Upgrade: " + yu2 + " -> " + document.getElementById("yu2").value + ")";
							text += "(Upgrade: " + yu2 + " -> " + document.getElementById("yu2").value + ")";
						} else {
							purchaseDetails += "(Downgrade: " + yu2 + " -> " + document.getElementById("yu2").value + ")";
							text += "(Downgrade: " + yu2 + " -> " + document.getElementById("yu2").value + ")";
						}
					}
					purchaseDetails += "</td></tr>";
				}
            
				if (document.getElementById("yu3").value > 0) {
					purchaseDetails += "<tr><td>Mail Lite User</td><td>" + document.getElementById("yu3").value;
					text += "\r\n" + "Mail Lite User                : " + document.getElementById("yu3").value;
					if (document.getElementById("yu3").value != yu3) {
						if (document.getElementById("yu3").value > yu3) {
							purchaseDetails += "(Upgrade: " + yu3 + " -> " + document.getElementById("yu3").value + ")";
							text += "(Upgrade: " + yu3 + " -> " + document.getElementById("yu3").value + ")";
						} else {
							purchaseDetails += "(Downgrade: " + yu3 + " -> " + document.getElementById("yu3").value + ")";
							text += "(Downgrade: " + yu3 + " -> " + document.getElementById("yu3").value + ")";
						}
					}
					purchaseDetails += "</td></tr>";
				}
				if (document.getElementById("yu4").value > 0) {
					purchaseDetails += "<tr><td>Mail 10GB User</td><td>" + document.getElementById("yu4").value;
					text += "\r\n" + "Mail 10GB User                : " + document.getElementById("yu4").value;
					if (document.getElementById("yu4").value != yu4) {
						if (document.getElementById("yu4").value > yu4) {
							purchaseDetails += "(Upgrade: " + yu4 + " -> " + document.getElementById("yu4").value + ")";
							text += "(Upgrade: " + yu4 + " -> " + document.getElementById("yu4").value + ")";
						} else {
							purchaseDetails += "(Downgrade: " + yu4 + " -> " + document.getElementById("yu4").value + ")";
							text += "(Downgrade: " + yu4 + " -> " + document.getElementById("yu4").value + ")";
						}
					}
					purchaseDetails += "</td></tr>";
				}
				if (document.getElementById("yu5").value > 0) {
					purchaseDetails += "<tr><td>Mail Premium User</td><td>" + document.getElementById("yu5").value;
					text += "\r\n" + "Mail Premium User             : " + document.getElementById("yu5").value;
					if (document.getElementById("yu5").value != yu5) {
						if (document.getElementById("yu5").value > yu5) {
							purchaseDetails += "(Upgrade: " + yu5 + " -> " + document.getElementById("yu5").value + ")";
							text += "(Upgrade: " + yu5 + " -> " + document.getElementById("yu5").value + ")";
						} else {
							purchaseDetails += "(Downgrade: " + yu5 + " -> " + document.getElementById("yu5").value + ")";
							text += "(Downgrade: " + yu5 + " -> " + document.getElementById("yu5").value + ")";
						}
					}
					purchaseDetails += "</td></tr>";
				}
			} else if (document.getElementById("u0").value > 0) {
				purchaseDetails += "<tr><td>Users</td><td>" + document.getElementById("u0").value;
				text += "\r\n" + "Users                         : " + document.getElementById("u0").value;
				if (document.getElementById("u0").value != u0) {
					if (document.getElementById("u0").value > u0) {
						purchaseDetails += "(Upgrade: " + u0 + " -> " + document.getElementById("u0").value + ")";
						text += "(Upgrade: " + u0 + " -> " + document.getElementById("u0").value + ")";
					} else {
						purchaseDetails += "(Downgrade: " + u0 + " -> " + document.getElementById("u0").value + ")";
						text += "(Downgrade: " + u0 + " -> " + document.getElementById("u0").value + ")";
					}
				}
				purchaseDetails += "</td></tr>";
			}
		} catch (err) {
			text += err.message;
		}
		if (purchaseDetails != "") {
			$("#tbodyid1").append(purchaseDetails);
			return true;
		} return false;
	}
}
        function openForm() {
            document.getElementById("assignplan").style.display = "none";
            
        }
        function openstartTrialForm(){
            document.getElementById("assignplan").style.display = "none";
            document.getElementById("planAssignForm").style.display = "none";
            document.getElementById("trialAssignForm").style.display = "block";
        }
        function openExtendTrialForm(){
            document.getElementById("extendtrialForm").style.display = "block";
            document.getElementById("assignplan").style.display = "none";
            document.getElementById("planAssignForm").style.display = "none";
            document.getElementById("trialAssignForm").style.display = "none";
            document.getElementById("myextendtrialform").style.display = "block";
        }
        function closeForm() {
            
            document.getElementById("planAssignForm").style.display = "none";
            document.getElementById("assignplan").style.display = "block";
            if(isStartTrial){
                document.getElementById("openstartTrial").style.display = "inline-block";
                document.getElementById("trialAssignForm").style.display = "none";
            }
            else if(isExtendTrial){
                document.getElementById("extendtrialForm").style.display = "none";
                document.getElementById("myextendtrialform").style.display = "none";
                document.getElementById("openExtendTrial").style.display = "inline-block";
                document.getElementById("trialAssignForm").style.display = "none";
            }
        }
        function onPlanDurationSelect (e) {
            const planSelectElement = document.getElementById("plan");
            clearOptions(planSelectElement);
            
            if(e.value == "MONT") {
                for(var i=0;i<obj["data"]["Monthly"].length;i++)
      {
        var option = document.createElement("option");
        option.value = obj["data"]["Monthly"][i]["planId"];
        option.text = obj["data"]["Monthly"][i]["planname"];
        planSelectElement.appendChild(option);
      }
            
                showNonFlexibleUsersCountUI();
            } else {
                for(var i=0;i<obj["data"]["Yearly"].length;i++)
      {
        var option = document.createElement("option");
        option.value = obj["data"]["Yearly"][i]["planId"];
        option.text = obj["data"]["Yearly"][i]["planname"];
        planSelectElement.appendChild(option);
      }
            
                showNonFlexibleUsersCountUI();
            }
        }
function addMonthlyPlanOptions(planSelectElement) {
            clearOptions(planSelectElement);
            monthlyPlanOptions.forEach((plan)=>{
                planSelectElement.add(plan,undefined);
            });
        }
            
        function addYearlyPlanOptions(planSelectElement) {
            clearOptions(planSelectElement);
            yearlyPlanOptions.forEach((plan)=>{
                planSelectElement.add(plan,undefined);
            });
        }
            
        function clearOptions(selectElement) {
            var i, L = selectElement.options.length - 1;
            for(i = L; i >= 0; i--) {
                selectElement.remove(i);
            }
        }
        function onPayPeriodSelect (e) {
            const planSelectElement = document.getElementById("payperiod");
            if (e.value == "17045") {
            
                if(planSelectElement.options[planSelectElement.selectedIndex].value == "MONT") {
                    showMonthlyFlexibleUsersCountUI();
                } else {
                    showYearlyFlexibleUsersCountUI();
                }
            } else {
                showNonFlexibleUsersCountUI();
            }
        }
    function onPayPeriodSelect1 (e) {
            const planSelectElement = document.getElementById("payperiod");
            
            if (e == "17045") {
            
                if(planSelectElement.options[planSelectElement.selectedIndex].value == "MONT") {
                    showMonthlyFlexibleUsersCountUI();
                } else {
                    showYearlyFlexibleUsersCountUI();
                }
            } else {
                showNonFlexibleUsersCountUI();
            }
        }
            
            
        function showMonthlyFlexibleUsersCountUI() {
            $("#u0").attr({
            "min" : 0
            });
            $(".u0").hide();
            $(".yu1").hide();
            $(".yu2").hide();
            $(".yu3").hide();
            $(".yu4").hide();
            $(".yu5").hide();
            
            $(".mu1").show();
            $(".mu2").show();
        }
            
        function showYearlyFlexibleUsersCountUI() {
            $("#u0").attr({
            "min" : 0
            });
            $(".u0").hide();
            $(".mu1").hide();
            $(".mu2").hide();
            
            $(".yu1").show();
            $(".yu2").show();
            $(".yu3").show();
            $(".yu4").show();
            $(".yu5").show();
        }
            
        function showNonFlexibleUsersCountUI() {
            $(".mu1").hide();
            $(".mu2").hide();
            $(".yu1").hide();
            $(".yu2").hide();
            $(".yu3").hide();
            $(".yu4").hide();
            $(".yu5").hide();
            
            $("#u0").attr({
            "min" : 1
            });
            $(".u0").show();
        }
    </script>
    </body>
</html>
<?php
    ';
        } else {
            $resultArray['Manage Customer'] = '<html><form>
<span style="color:red" id="hint" name="hint">Please create account before assign Trial/Paid plans</span><br><input type="button" onclick="notify()" value="Assign Paid Plan" />
</form><script>
</script></html>';
        }
        $resultArray['Customer Plan'] = $planname;
        $resultArray['Client Domain'] = $cli->domain;
        $resultArray['Client Control Panel'] = '<a href="' . $cli->url . '" target=_blank>Click here</a>';
        $resultArray['Super Administrator'] = $cli->superAdmin;
        $resultArray['ZOID'] = $cli->zoid;
        $resultArray['Domain verification status'] = $verificationStatus;
        $resultArray['URL to Manage Customers'] = '<a href="https://mailadmin.zoho.com/cpanel/index.do#managecustomers" target="blank">Click here</a>';
        
        
        return $resultArray;
    } catch (Exception $e) {
        logModuleCall('zoho_mail', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
    }
    return array();
}

function console_log($output, $with_script_tags = true)
{
    $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . ');';
    if ($with_script_tags) {
        $js_code = '<script>' . $js_code . '</script>';
    }
    echo $js_code;
}

function zoho_mail_AdminServicesTabFieldsSave(array $params)
{
    // Fetch form submission variables.
    $originalFieldValue = isset($_REQUEST['zoho_mail_original_uniquefieldname']) ? $_REQUEST['zoho_mail_original_uniquefieldname'] : '';
    $newFieldValue = isset($_REQUEST['zoho_mail_uniquefieldname']) ? $_REQUEST['zoho_mail_uniquefieldname'] : '';
    if ($originalFieldValue != $newFieldValue) {
        try {} catch (Exception $e) {
            logModuleCall('zoho_mail', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        }
    }
}

function zoho_mail_ServiceSingleSignOn(array $params)
{
    try {
        $response = array();
        return array(
            'success' => true,
            'redirectTo' => $response['redirectUrl']
        );
    } catch (Exception $e) {
        logModuleCall('zoho_mail', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return array(
            'success' => false,
            'errorMsg' => $e->getMessage()
        );
    }
}

function zoho_mail_AdminSingleSignOn(array $params)
{
    try {
        // Call the service's single sign-on admin token retrieval function,
        // using the values provided by WHMCS in `$params`.
        $response = array();
        return array(
            'success' => true,
            'redirectTo' => $response['redirectUrl']
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall('zoho_mail', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return array(
            'success' => false,
            'errorMsg' => $e->getMessage()
        );
    }
}

function zoho_mail_ClientArea(array $params)
{
    $serviceAction = 'get_stats';
    $templateFile = 'templates/overview.tpl';
    try {
        $cli = Capsule::table('zoho_mail')->where('domain', $params['domain'])->first();
        $urlToPanel = $cli->url;
        return array(
            'tabOverviewReplacementTemplate' => $templateFile,
            'templateVariables' => array(
                'mailUrl' => 'https://mail.zoho.com',
                'panelUrl' => ''.$urlToPanel
            )
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall('zoho_mail', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        // In an error condition, display an error page.
        return array(
            'tabOverviewReplacementTemplate' => 'error.tpl',
            'templateVariables' => array(
                'usefulErrorHelper' => $e->getMessage()
            )
        );
    }
}

function get_assigned_plan(string $email,$accessToken)
{
    $cli = Capsule::table('zoho_mail_auth_table')->first();
    $cli1 = Capsule::table('zoho_mail')->where('superAdmin',$email)->first();
    $curlOrg1 = curl_init();
    $urlOrg1 = 'https://mail.zoho'.$cli->region.'/api/organization/'.(string)$cli1->zoid;
    $status = "";
    curl_setopt_array($curlOrg1, array(
        CURLOPT_URL => $urlOrg1,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "authorization: Zoho-oauthtoken ".$accessToken
        )
    ));
    $responseOrg3 = curl_exec($curlOrg1);
    $respOrgJson1 = json_decode($responseOrg3);
    $arr = json_decode($responseOrg3,true);
    $getInfo = curl_getinfo($curlOrg1,CURLINFO_HTTP_CODE);
    curl_close($curlOrg1);
    if ( $getInfo == '200')
    {
        return $arr["data"]["basePlan"];
    }
    else{
        $status = '';
    }
    get_customer_list(get_access_token(array()));
    if ($status == '' && Capsule::schema()->hasTable('zoho_mail_customerlist_table')) {
        $conn = Capsule::connection()->getPdo();
        $result = $conn->query("SELECT Plan FROM zoho_mail_customerlist_table WHERE Email='" . $email . "'");
        while ($currentRow = $result->fetch()) {
            return $currentRow['Plan'];
        }
    }
    return '';
}

function get_access_token(array $params)
{
    $curl = curl_init();
    $cli = Capsule::table('zoho_mail_auth_table')->first();
    $urlAT = 'https://accounts.zoho' . $cli->region . '/oauth/v2/token?refresh_token=' . $cli->token . '&grant_type=refresh_token&client_id=' . $cli->clientId . '&client_secret=' . $cli->clientSecret . '&redirect_uri=' . $cli->redirectUrl . '&scope=VirtualOffice.partner.organization.CREATE,VirtualOffice.partner.organization.READ,ZohoPayments.partnersubscription.all,ZohoPayments.fullaccess.READ,ZohoPayments.leads.READ';
    curl_setopt_array($curl, array(
        CURLOPT_URL => $urlAT,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST"
    ));
    
    $response = curl_exec($curl);
    $accessJson = json_decode($response);
    $getInfo = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return $accessJson->access_token;
}

function create_child_organization(array $params, $planName, $accessToken)
{
    $arrClient = $params['clientsdetails'];
    $cli = Capsule::table('zoho_mail_auth_table')->first();
    $bodyArr = array(
        "firstName" => $arrClient['firstname'],
        "lastName" => $arrClient['lastname'],
        "emailId" => $arrClient['email'],
        "domainName" => $params['domain'],
        "planName" => $planName
    );
    $bodyJson = json_encode($bodyArr);
    $curlOrg = curl_init();
    $urlOrg = 'https://mail.zoho' . $cli->region . '/api/organization';
    curl_setopt_array($curlOrg, array(
        CURLOPT_URL => $urlOrg,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $bodyJson,
        CURLOPT_HTTPHEADER => array(
            "authorization: Zoho-oauthtoken " . $accessToken,
            "content-type: application/json"
        )
    ));
    $responseOrg = curl_exec($curlOrg);
    $respOrgJson = json_decode($responseOrg);
    $getInfo = curl_getinfo($curlOrg, CURLINFO_HTTP_CODE);
    curl_close($curlOrg);
    if ($getInfo == '200') {
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();
        
        try {
            $statement = $pdo->prepare('insert into zoho_mail (domain, zoid, isverified, superAdmin, url) values (:domain, :zoid, :isverified, :superAdmin, :url)');
            
            $statement->execute([
                ':domain' => $respOrgJson->data->domainName,
                ':zoid' => $respOrgJson->data->zoid,
                ':isverified' => ($respOrgJson->data->isVerified) ? 'true' : 'false',
                ':superAdmin' => $respOrgJson->data->superAdmin,
                ':url' => get_child_org_url($params, $accessToken, $respOrgJson->data->zoid)
            ]);
            $pdo->commit();
            return array(
                'success' => 'Mailbox has been created.'
            );
        } catch (Exception $e) {
            return "Uh oh! {$e->getMessage()}" . $urlChildPanel;
            $pdo->rollBack();
        }
        return array(
            'success' => 'Mailbox has been created.' 
        );
    } else if ($getInfo == '400') {
        $updatedUserCount = Capsule::table('tblproducts')->where('servertype', 'zoho_mail')->update([
            'configoption5' => ''
        ]);
    } else if ($getInfo == '500') {
        return $respOrgJson->data->moreInfo . '. To map, please send this link to your customer and ask them to tag you using your partner code. "https://store.zoho.com/html/store/tagyourpartner.html"';
    } else {
        return 'Failed -->Description: ' . $respOrgJson->status->description . ' --->More Information:' . $respOrgJson->data->moreInfo ;
    }
}

function get_customer_list($accessToken)
{
    $curlOrg = curl_init();
    $cli = Capsule::table('zoho_mail_auth_table')->first();
    
    $urlOrg = 'https://store.zoho' . $cli->region . '/api/v1/partner/subscriptions';
    
    curl_setopt_array($curlOrg, array(
        CURLOPT_URL => $urlOrg,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "authorization: Zoho-oauthtoken " . $accessToken
        )
    ));
    $responseOrg1 = curl_exec($curlOrg);
    $respOrgJson1 = json_decode($responseOrg1);
    
    $arr1 = json_decode($responseOrg1, true);
    
    $getInfo = curl_getinfo($curlOrg, CURLINFO_HTTP_CODE);
    curl_close($curlOrg);
    
    $curlOrg = curl_init();
    $cli = Capsule::table('zoho_mail_auth_table')->first();
    $urlOrg = 'https://store.zoho' . $cli->region . '/api/v1/partner/leads';
    curl_setopt_array($curlOrg, array(
        CURLOPT_URL => $urlOrg,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "authorization: Zoho-oauthtoken " . $accessToken
        )
    ));
    $responseOrg2 = curl_exec($curlOrg);
    $respOrgJson2 = json_decode($responseOrg2);
    $arr2 = json_decode($responseOrg2, true);
    $getInfo = curl_getinfo($curlOrg, CURLINFO_HTTP_CODE);
    curl_close($curlOrg);
    foreach ($arr2 as $item) {
        $arr1[] = $item;
    }
    
    $conn = Capsule::connection()->getPdo();
    if (! Capsule::schema()->hasTable('zoho_mail_customerlist_table')) {
        Capsule::schema()->create('zoho_mail_customerlist_table', function ($table) {
            $table->string('Profile_Id');
            $table->string('Email');
            $table->string('Country');
            $table->string('Custom_Id');
            $table->string('Plan');
            $table->string('Paid_Users');
            $table->string('Payperiod');
            $table->string('Registration_Date');
            $table->string('Renewal_Date');
            $table->string('ManageUrl');
            $table->string('LicenseUrl');
        });
    }
    $conn->query("DELETE FROM zoho_mail_customerlist_table");
    $statement = $conn->prepare("INSERT INTO zoho_mail_customerlist_table (Profile_Id,Email,Country,Custom_Id,Plan,Paid_Users,Payperiod,Registration_Date,Renewal_Date,ManageUrl,LicenseUrl) VALUES (:ProfileId,:Email,:Country,:CustomId,:Plan,:PaidUsers,:Payperiod,:RegistrationDate,:RenewalDate,:ManageUrl,:LicenseUrl)");
    
    try {
        foreach ($arr1 as $row) {
            
            $time_start = microtime(true);
            
            if (strcmp($row['service_name'], 'Workplace') == 0) {
                
                $newRow = [];
                $newRow["ProfileId"] = $row['profile_id'];
                
                $newRow["Email"] = $row['email_id'];
                $newRow["Country"] = $row['customer_address']['country'];
                $newRow["CustomId"] = $row['org_id'];
                
                if ($row['plan_name'] != null) {
                    $newRow["Plan"] = $row['plan_name'];
                    if($row['plan_name'] == "Mail Flexible Plan"){
                        $newRow["Plan"] = "Mail Flexible";
                    }
                    
                } else {
                    $newRow["Plan"] = 'Free Plan';
                }
                if($newRow["Plan"] == "Free Plan"|| $newRow["Plan"] == "Mail Free" || $newRow["Plan"] == "Workplace Standard Trial"
                    || $newRow["Plan"] == "Workplace Professional Trial" || $newRow["Plan"] == "Mail Premium Trial" )
                {
                    $newRow["PaidUsers"] = 0;
                }
                else {
                    
                    try{
                        $count = 0;
                        foreach ($row['addons'] as $curAddon) {
                            if ($curAddon['addon_name'] == "User") {
                                $newRow["PaidUsers"] = $curAddon['value'];
                                break;
                            }
                            
                        }
                        if($newRow["PaidUsers"] == null){
                            $newRow["PaidUsers"] = 0;
                        }
                    }
                    catch(Exception $e){$newRow["PaidUsers"] = 0;}
                }
                if ($newRow["Plan"] == "Free Plan"|| $newRow["Plan"] == "Mail Free" || $newRow["Plan"] == "Workplace Standard Trial"
                    || $newRow["Plan"] == "Workplace Professional Trial" || $newRow["Plan"] == "Mail Premium Trial" )
                {
                    $newRow["Payperiod"] = 'Monthly';
                }
                else {
                    try{
                        $newRow["Payperiod"] = ucfirst($row['billing_frequency']);
                    }
                    catch(Exception $e){
                        $newRow["Payperiod"] = 'Monthly';
                    }
                }
                
                if ($row['registration_date'] != null) {
                    $newRow["RegistrationDate"] = $row['registration_date'];
                } else if ($row['subscription_start_date'] != null) {
                    $newRow["RegistrationDate"] = $row['subscription_start_date'];
                }
                
                if ($newRow["Payperiod"] == 'Yearly') {
                    $dt = strtotime($newRow["RegistrationDate"]);
                    $newRow["RenewalDate"] = date("Y-m-d", strtotime("+1 year", $dt));
                } else if ($newRow["Payperiod"] == 'Monthly'){
                    $dt = strtotime($newRow["RegistrationDate"]);
                    $newRow["RenewalDate"] = date("Y-m-d", strtotime("+1 month", $dt));
                }
                $newRow["ManageUrl"] = 'https://store.zoho' . $cli->region . '/store/reseller.do?profileId=' . $row['profile_id'];
                
                $domain = explode("@",$newRow["Email"]);
                $isMCPmapped = true;
                
                $data = getOrgDetails($accessToken,$domain[1]);
                //$isMCPmapped = empty($data);
                
                if($isMCPmapped){
                    $newRow["LicenseUrl"] ='https://mailadmin.zoho' . $cli->region . '/cpanel/home.do?zaaid='.$newRow["CustomId"].'#dashboard';
                }
                else{
                    $newRow["LicenseUrl"] = 'Not mapped';
                }
                
                $statement->execute($newRow);
            }
        }
    } catch (Exception $e) {
        echo $e->getMessage();
        $conn->rollback();
        throw $e;
    }
}

function getOrgDetails($accessToken,string $domain)
{
    $curlOrg = curl_init();
    $cli = Capsule::table('zoho_mail_auth_table')->first();
    $urlOrg = 'https://mail.zoho'.$cli->region.'/api/organization?mode=getCustomerOrgDetails&searchKey='.$domain;
    
    curl_setopt_array($curlOrg, array(
        CURLOPT_URL => $urlOrg,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "authorization: Zoho-oauthtoken " . $accessToken
        )
    ));
    $responseOrg = curl_exec($curlOrg);
    return $responseOrg;
    $respOrgJson = json_decode($responseOrg);
    $arr = json_decode($responseOrg,true);
    $getInfo = curl_getinfo($curlOrg,CURLINFO_HTTP_CODE);
    curl_close($curlOrg);
    if ( $getInfo == '200')
    {
        if($respOrgJson->status->description == "success")
        {
            return $respOrgJson->data;
        }
    }
    else
        echo $responseOrg;
}

function get_child_org_url(array $params, $accessToken, $zoid)
{
    $cli = Capsule::table('zoho_mail_auth_table')->first();
    $urlPanel = 'https://mail.zoho' . $cli->region . '/api/organization/' . $zoid . '?fields=encryptedZoid';
    $curlPanel = curl_init();
    curl_setopt_array($curlPanel, array(
        CURLOPT_URL => $urlPanel,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "authorization: Zoho-oauthtoken " . $accessToken
        )
    ));
    $responsePanel = curl_exec($curlPanel);
    $respJsonPanel = json_decode($responsePanel);
    $getPanelInfo = curl_getinfo($curlPanel, CURLINFO_HTTP_CODE);
    curl_close($curlPanel);
    if ($getPanelInfo == '200') {
        $encryptedZoid = $respJsonPanel->data->encryptedZoid;
        return 'https://mail.localzoho' . $cli->region . '/cpanel/index.do?zoid=' . $encryptedZoid . '&dname=' . $params['domain'];
    }
    return null;
}
