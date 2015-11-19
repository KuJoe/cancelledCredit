<?php
/**
Add Credit On Immediate Cancellation Hook for WHMCS

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
**/

function cancelledCredit($vars) {
	$minpay = '0.00'; //Set minimum payment amount to receive credit here
	$desc = 'Credit for cancelling service early'; //Edit the description to whatever you'd like
	$adminuser = "ADMINUSER"; //Edit the admin user to which ever you like, make sure it's valid in WHMCS since this is required
	
	if($vars['type'] == "Immediate") {
		$sid = $vars['relid'];
		$num = '0';
		$pastinv = 'false';
		while($pastinv == 'false'){
			$result = select_query('tblinvoiceitems','',array("relid" => $sid),'id','DESC',$num.',1');
			$data = mysql_fetch_assoc($result);
			$date = new DateTime($data['duedate']);
			$now = new DateTime();
			if($date < $now) {
				$invoice = $data['invoiceid'];
				$state = mysql_result(select_query('tblinvoices','status',array("id" => $invoice),'id','DESC','1'),0);
				if($state == 'Paid') {
					$pastin = 'true';
				} else {
					$pastin = 'false';
				}
			}
		}
		$cycle = mysql_result(select_query('tblhosting','billingcycle',array("id" => $sid),'id','DESC','1'),0);
		$amountpaid = $data['amount'];
		$invoice = $data['invoiceid'];
		if($cycle == 'Monthly') {
			$amount = ($row['amount']/30);
			$amount = round($amount,2);
		} elseif($cycle == 'Quarterly') {
			$amount = ($row['amount']/90);
			$amount = round($amount,2);
		} elseif($cycle == 'Semi-Annually') {
			$amount = ($row['amount']/180);
			$amount = round($amount,2);
		} elseif($cycle == 'Annually') {
			$amount = ($row['amount']/365);
			$amount = round($amount,2);
		} elseif($cycle == 'Biennially') {
			$amount = ($row['amount']/730);
			$amount = round($amount,2);
		} elseif($cycle == 'Triennially') {
			$amount = ($row['amount']/1095);
			$amount = round($amount,2);
		}
		$now = time();
		$your_date = strtotime("2016-02-21");
		$datediff = $your_date - $now;
		$days = floor($datediff/(60*60*24));
		$amount = ($days * $amount);
		if(isset($amount) AND $amount > $minpay) {
			$command = "addcredit";
			$values = array( 'clientid' => $vars['userid'], 'description' => $desc, 'amount' => $amount  );
			$results = localAPI($command, $values, $adminuser);
			if ($results['result'] != "success") {
				logActivity('An Error Occurred: '.$results['result']);
			}
		}
	}
}

add_hook("CancellationRequest",0,"cancelledCredit");
?>