<?php
/**
* @author		Daeho Lee
* @datetime		December 14 2009
* @comment		Educational purpose only.
*/
set_time_limit(0);
require_once("function.php");

initialize();

$target_section = stdinfunc("input", "Please enter target section. ex) 12345");
$department = stdinfunc("input", "Please enter department. ex) 001");
$total_attempt = stdinfunc("input", "Number of try? ex) 1000");
$user_id = stdinfunc("input", "Userid");
$pw = stdinfunc("input", "Password");

$sec = 15;
$cookie_nm = "./register_cookie.txt";

echo "\nWebreg Auto v1.0 Initialized\n";
echo "Department: ".$department."\n";
echo "Course Index Number: ".$target_section."\n";
echo "Attempting.. ".$total_attempt." times every ".$sec." seconds.\n";

/*
First, look up course on website A and check whether a section is open or closed.
If open		-> proceed to course register (login_register)
If closed	-> stay in the loop until course opens up or $number reaches $total_attempt

Website Pattern
- Course Open
"<TD ALIGN=\"CENTER\" BGCOLOR=\"#00AE22\"><FONT SIZE=\"2\">&nbsp;<B>".$target_section."</TD>"

- Course Closed
"<TD ALIGN=\"CENTER\" BGCOLOR=\"#FF1111\"><FONT SIZE=\"2\">&nbsp;<B>".$target_section."</TD>"

Need to include whole line to distinguish specific $target_section status from others.
*/

    for ($number = 0; $number < $total_attempt; $number++) {
		$post_data = "p_yearterm=12010&p_campus=SS&p_level=U&p_source=DRILL&p_ss_campus=empty&p_time=empty&p_course_no=&p_subj_cd=".$department."&p_ss_campus=2&p_course_range=0&p_time=M&p_ss_campus=1&p_time=A&p_ss_campus=4&p_time=E&p_ss_campus=5&p_mtg_day=W&p_ss_campus=3&x=".rand(1,15)."&y=".rand(1,15);
		$url = "http://yourdomain.edu/courses/display.select_courses";

		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt ($ch, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
		curl_setopt ($ch, CURLOPT_REFERER, "http://yourdomain.edu/courses/display.select_courses");
		curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie_nm);
		curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookie_nm);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt ($ch, CURLOPT_TIMEOUT, 3);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close ($ch);

		if (ereg("<TD ALIGN=\"CENTER\" BGCOLOR=\"#FF1111\"><FONT SIZE=\"2\">&nbsp;<B>".$target_section."</TD>",$result)){
			echo $target_section." is Closed... (".($number+1)."/".$total_attempt.")\n";
			unset($result);
		} elseif (ereg("<TD ALIGN=\"CENTER\" BGCOLOR=\"#00AE22\"><FONT SIZE=\"2\">&nbsp;<B>".$target_section."</TD>",$result)){
			echo $target_section." is OPEN! Attempting to register...\n";
			login_register();
			unset($result);
			exit();
		} else {
			$f = fopen("error.html", "w");
			fwrite($f, $result);
			fclose($f);
			unset($result);
			echo "Unknown Error. Check error.html";
			exit();
		}
	    sleep($sec);
    } 
?>