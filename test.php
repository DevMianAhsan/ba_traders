<?php //date_default_timezone_set('Europe/London');
//$sTime = date("H:i");  
//echo date('Y-m-d H:i:s',strtotime("$sTime"));
//print 'The time is: ' . $sTime; 

//date_default_timezone_set('');
date_default_timezone_set("Asia/Karachi");
//date_default_timezone_set('US/Central');
$datetime = new DateTime();
echo $datetime->format('Y-m-d H:i:s') . "\n";
$la_time = new DateTimeZone('Asia/Kashgar');
$datetime->setTimezone($la_time);
echo $datetime->format('Y-m-d H:i:s');

$time="10:09";
$time = date('H:i', strtotime($time.'+1 hour'));
echo $time;
?>
<!-- /usr/local/bin/php /home1/nkrowdco/public_html/admin/record_fixtures.php
/usr/local/bin/php /home1/nkrowdco/public_html/admin/action_get_points.php
/usr/local/bin/php /home1/nkrowdco/public_html/admin/record_standings.php
/usr/local/bin/php /home1/nkrowdco/public_html/admin/record_results.php -->
ALTER TABLE `vouchers` ADD `td_check_no` TEXT NULL AFTER `voucher_group`, ADD `voucher_bank_name` VARCHAR(255) NULL AFTER `td_check_no`, ADD `td_check_date` VARCHAR(100) NULL AFTER `voucher_bank_name`;

ALTER TABLE `product` ADD `inventory` INT NOT NULL DEFAULT '0' AFTER `adddatetime`;

ALTER TABLE `vouchers` ADD `check_type` VARCHAR(150) NULL AFTER `td_check_date`;