<?php
/**
* @author		Daeho Lee
* @datetime		December 14 2009
* @comment		Educational purpose only.
*/
if (!count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))) {
	echo "This file cannot be used itself."; exit();
}

function stdinfunc($mode, $message) {
	echo $message.": ";
	$handle = fopen("php://stdin","r");
	$line = fgets($handle);

	switch ($mode) {
		case "login":
			if (trim($line) != "hard_coded_password") {
				echo "Failed authentication. Exiting process...";
				exit();
			}
			break;
		case "input":
			$inputvar = trim($line);
			return $inputvar;
		case "exit":
			exit();
	}
	fclose($handle);
} # end of stdinfunc

function initialize() {
	stdinfunc("login","\nPlease enter your password to continue");
	echo "Login Successful.\n";
} # end of initialize

function login_register() {
	global $target_section, $user_id, $pw, $cookie_nm;

	$login_url = "https://yourdomain.edu/login?service=https%3A%2F%2Fyourdomain.edu%2Fwebreg%2Fcas_security_check";
	$login_data = "username=".$user_id."&password=".$pw."&authenticationType=Kerberos&_currentStateId=&_eventId=submit";
	
	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL,$login_url);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt ($ch, CURLOPT_SSLVERSION,1);
	curl_setopt ($ch, CURLOPT_HEADER, 1);
	curl_setopt ($ch, CURLOPT_POST, 1);
	curl_setopt ($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
	curl_setopt ($ch, CURLOPT_REFERER, "https://yourdomain.edu/login?service=https%3A%2F%2Fyourdomain.edu%2Fwebreg%2Fcas_security_check");
	curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie_nm);
	curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookie_nm);
	curl_setopt ($ch, CURLOPT_POSTFIELDS, $login_data);
	curl_setopt ($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec ($ch);
	curl_close ($ch);

	// Get Location header

	$result = explode("Location: ",$result);
	$result = explode("\r\n",$result[1]);
	$url = $result[0];

	/*  PATTERN
		cas_security_check?ticket= (SOME STRING HERE)
	*/

	if (ereg("ticket=",$url)) {
		echo "Login Successful. Proof: ".$url."\n";
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL,$url);
		curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie_nm); 
		curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookie_nm); 
		$result = curl_exec ($ch); // just need to visit that webpage to get updated cookie
		curl_close ($ch);
	} else {
		echo "Login Failed.\n";
		$f = fopen("loginfail.html", "w");
		fwrite($f, $result);
		fclose($f);
		unset($result);
		echo "Check loginfail.html";
		exit();
	}

	unset($url, $result);

	/*
	example
	$baseurl = "https://yourdomain.edu/webreg/editSchedule.htm";
	$postfields = "login=cas&semesterSelection=12010&indexList=12345,67890,23456,34567,45678,56789";
	$url = $baseurl.$postfields;
	*/

	$url = "https://yourdomain.edu/webreg/editSchedule.htm?login=cas&semesterSelection=12010&indexList=".$target_section;

	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL,$url);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt ($ch, CURLOPT_SSLVERSION,1);
	curl_setopt ($ch, CURLOPT_HEADER, 1);
	curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie_nm);
	curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookie_nm);
	$result = curl_exec ($ch); 
	curl_close ($ch);

	// Now handle Webreg Queue
	$waiting_queue = TRUE;
	for ($i=0; $i<100; $i++) {
		if ($waiting_queue) {
			$url = "https://yourdomain.edu/webreg/refresh.htm";
			$ch = curl_init();
			curl_setopt ($ch, CURLOPT_URL,$url);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt ($ch, CURLOPT_SSLVERSION,1);
			curl_setopt ($ch, CURLOPT_HEADER, 1);
			curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie_nm);
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookie_nm);
			$result = curl_exec ($ch);
			curl_close ($ch);
			
			if (ereg("<meta http-equiv=\"refresh\" content=\"(.+);url=refresh.htm\" />",$result)) {
				$waiting_queue = TRUE;
			} else {
				$waiting_queue = FALSE;
			}
			echo "Stuck in webreg queue. Refreshed ".($i+1)."times. Retrying in 5 seconds.\n";
			sleep(5);
		} else {
			echo "Queue solved. Now adding courses and finalizing registration.\n";
		}
	}

	// Handling Exception: ineligible to register on current date.
	if (ereg("You may not register or change registration today.", $result)) {
		echo "You may not register or change registration today.\nCheck your registration date.\n";
		exit();
	}

	// Adding Courses

	/*
	example
	$coursedata = "coursesToAdd[0].courseIndex=12345&coursesToAdd[1].courseIndex=67890";
	usually get 1 input for target_section, but also can handle multiple sections
	*/

	if (ereg(",",$target_section)) {
		$tmp_var = explode(",",$target_section);
		$coursedata = "";
		for ($i=0; $i < sizeof($tmp_var); $i++) {
			$coursedata .= "coursesToAdd[".$i."].courseIndex=".$tmp_var[$i]."&";
		}
	} else {
		$coursedata = "coursesToAdd[0].courseIndex=".$target_section;
	}

	$url = "https://yourdomain.edu/webreg/addCourses.htm";

	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL,$url);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt ($ch, CURLOPT_SSLVERSION,1);
	curl_setopt ($ch, CURLOPT_HEADER, 1);
	curl_setopt ($ch, CURLOPT_POST, 1);
	curl_setopt ($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
	curl_setopt ($ch, CURLOPT_REFERER, "https://yourdomain.edu/webreg/refresh.htm");
	curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie_nm);
	curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookie_nm);
	curl_setopt ($ch, CURLOPT_POSTFIELDS, $coursedata);
	curl_setopt ($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec ($ch);
	curl_close ($ch);

	if(ereg("Registration successful",$result)) {
		echo "Registration Successful!!\n";
	} elseif(ereg("special permission",$result)) {
		echo "You need to get a special permission number to register for that course.\n";
	} else {
		$f = fopen("exception.html", "w");
		fwrite($f, $result);
		fclose($f);
		unset($result);
		echo "Unexpected Exception. Check exception.html";
	}
	exit();
} # end of login_register
?>