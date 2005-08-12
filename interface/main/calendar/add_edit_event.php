<?
 // Copyright (C) 2005 Rod Roark <rod@sunsetsystems.com>
 //
 // This program is free software; you can redistribute it and/or
 // modify it under the terms of the GNU General Public License
 // as published by the Free Software Foundation; either version 2
 // of the License, or (at your option) any later version.

 // The event editor looks something like this:

 //------------------------------------------------------------//
 // Category __________________V   O All day event             //
 // Date     _____________ [?]     O Date     ___:___ __V      //
 // Title    ___________________     Duration ____ minutes     //
 // Provider __________________V   X Repeats  _____________V   //
 // Patient  ___________________     Until    ___________ [?]  //
 // Comments ________________________________________________  //
 //                                                            //
 //       [Save]  [Find Available]  [Delete]  [Cancel]         //
 //------------------------------------------------------------//

 include_once("../../globals.php");
 include_once("$srcdir/patient.inc");

 // Things that might be passed by our opener.
 //
 $eid        = $_GET['eid'];         // only for existing events
 $date       = $_GET['date'];        // this and below only for new events
 $userid     = $_GET['userid'];
 //
 if ($date)
  $date = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6);
 else
  $date = date("Y-m-d");
 //
 $starttimem = '00';
 if (isset($_GET['starttimem']))
  $starttimem = substr('00' . $_GET['starttimem'], -2);
 //
 if (isset($_GET['starttimeh'])) {
  $starttimeh = $_GET['starttimeh'];
  if (isset($_GET['startampm'])) {
   if ($_GET['startampm'] == '2' && $starttimeh < 12)
    $starttimeh += 12;
  }
 } else {
  $starttimeh = date("G");
 }
 $startampm = '';

 $info_msg = "";

 // If we are saving, then save and close the window.
 //
 if ($_POST['form_save']) {

  // Compute start and end time strings to be saved.
  if ($_POST['form_allday']) {
   $tmph = 0;
   $tmpm = 0;
   $duration = 24 * 60;
  } else {
   $tmph = $_POST['form_hour'] + 0;
   $tmpm = $_POST['form_minute'] + 0;
   if ($_POST['form_ampm'] == '2' && $tmph < 12) $tmph += 12;
   $duration = $_POST['form_duration'];
  }
  $starttime = "$tmph:$tmpm:00";
  //
  $tmpm += $duration;
  while ($tmpm >= 60) {
   $tmpm -= 60;
   ++$tmph;
  }
  $endtime = "$tmph:$tmpm:00";

  // Useless garbage that we must save.
  $locationspec = 'a:6:{s:14:"event_location";N;s:13:"event_street1";N;' .
   's:13:"event_street2";N;s:10:"event_city";N;s:11:"event_state";N;s:12:"event_postal";N;}';

  // More garbage, but this time 1 character of it is used to save the
  // repeat type.
  if ($_POST['form_repeat']) {
   $recurrspec = 'a:5:{s:17:"event_repeat_freq";s:1:"1";' .
    's:22:"event_repeat_freq_type";s:1:"' . $_POST['form_repeat_type'] . '";' .
    's:19:"event_repeat_on_num";s:1:"1";s:19:"event_repeat_on_day";s:1:"0";' .
    's:20:"event_repeat_on_freq";s:1:"0";}';
  } else {
   $recurrspec = 'a:5:{s:17:"event_repeat_freq";N;s:22:"event_repeat_freq_type";s:1:"0";' .
    's:19:"event_repeat_on_num";s:1:"1";s:19:"event_repeat_on_day";s:1:"0";' .
    's:20:"event_repeat_on_freq";s:1:"1";}';
  }

  if ($eid) {
   sqlStatement("UPDATE openemr_postcalendar_events SET " .
    "pc_catid = '"       . $_POST['form_category']             . "', " .
    "pc_aid = '"         . $_POST['form_provider']             . "', " .
    "pc_pid = '"         . $_POST['form_pid']                  . "', " .
    "pc_title = '"       . $_POST['form_title']                . "', " .
    "pc_time = NOW(), "                                                .
    "pc_hometext = '"    . $_POST['form_comments']             . "', " .
    "pc_informant = '"   . $_SESSION['authUserID']             . "', " .
    "pc_eventDate = '"   . fixDate($_POST['form_date'])        . "', " .
    "pc_endDate = '"     . fixDate($_POST['form_enddate'])     . "', " .
    "pc_duration = '"    . ($duration * 60)                    . "', " .
    "pc_recurrtype = '"  . ($_POST['form_repeat'] ? '1' : '0') . "', " .
    "pc_recurrspec = '$recurrspec', "                                  .
    "pc_startTime = '$starttime', "                                    .
    "pc_endTime = '$endtime', "                                        .
    "pc_alldayevent = '" . $_POST['form_allday']               . "', " .
    "pc_apptstatus = '"  . $_POST['form_apptstatus']           . "' "  .
    "WHERE pc_eid = '$eid'");
  } else {
   sqlInsert("INSERT INTO openemr_postcalendar_events ( " .
    "pc_catid, pc_aid, pc_pid, pc_title, pc_time, pc_hometext, " .
    "pc_informant, pc_eventDate, pc_endDate, pc_duration, pc_recurrtype, " .
    "pc_recurrspec, pc_startTime, pc_endTime, pc_alldayevent, " .
    "pc_apptstatus, pc_location, pc_eventstatus, pc_sharing " .
    ") VALUES ( " .
    "'" . $_POST['form_category']             . "', " .
    "'" . $_POST['form_provider']             . "', " .
    "'" . $_POST['form_pid']                  . "', " .
    "'" . $_POST['form_title']                . "', " .
    "NOW(), "                                         .
    "'" . $_POST['form_comments']             . "', " .
    "'" . $_SESSION['authUserID']             . "', " .
    "'" . fixDate($_POST['form_date'])        . "', " .
    "'" . fixDate($_POST['form_enddate'])     . "', " .
    "'" . ($duration * 60)                    . "', " .
    "'" . ($_POST['form_repeat'] ? '1' : '0') . "', " .
    "'$recurrspec', "                                 .
    "'$starttime', "                                  .
    "'$endtime', "                                    .
    "'" . $_POST['form_allday']               . "', " .
    "'" . $_POST['form_apptstatus']           . "', " .
    "'$locationspec', "                               .
    "1, " .
    "1 )");
  }
 }
 else if ($_POST['form_delete']) {
  sqlStatement("DELETE FROM openemr_postcalendar_events WHERE " .
   "pc_eid = '$eid'");
 }

 if ($_POST['form_save'] || $_POST['form_delete']) {
  // Close this window and refresh the calendar display.
  echo "<html>\n<body>\n<script language='JavaScript'>\n";
  if ($info_msg) echo " alert('$info_msg');\n";
  echo " if (!opener.closed && opener.refreshme) opener.refreshme();\n";
  echo " window.close();\n";
  echo "</script>\n</body>\n</html>\n";
  exit();
 }

 // If we get this far then we are displaying the form.

 $statuses = array(
  '-' => '',
  '*' => '* Reminder done',
  '+' => '+ Chart pulled',
  '?' => '? No show',
  '@' => '@ Arrived',
  '~' => '~ Arrived late',
  '!' => '! Left w/o visit',
  '#' => '# Ins/fin issue',
  '<' => '< In exam room',
  '>' => '> Checked out',
  '$' => '$ Coding done',
 );

 $repeats = 0; // if the event repeats
 $repeattype = '0';
 $patientid = '';
 if ($_REQUEST['patientid']) $patientid = $_REQUEST['patientid'];
 $patientname = " (Click to select)";
 $patienttitle = "";
 $hometext = "";
 $row = array();

 // If we are editing an existing event, then get its data.
 if ($eid) {
  $row = sqlQuery("SELECT * FROM openemr_postcalendar_events WHERE pc_eid = $eid");
  $date = $row['pc_eventDate'];
  $userid = $row['pc_aid'];
  $patientid = $row['pc_pid'];
  $starttimeh = substr($row['pc_startTime'], 0, 2) + 0;
  $starttimem = substr($row['pc_startTime'], 3, 2);
  $repeats = $row['pc_recurrtype'];
  if (preg_match('/"event_repeat_freq_type";s:1:"(\d)"/', $row['pc_recurrspec'], $matches)) {
   $repeattype = $matches[1];
  }
  $hometext = $row['pc_hometext'];
  if (substr($hometext, 0, 6) == ':text:') $hometext = substr($hometext, 6);
 }

 // If we have a patient ID, get the name and phone numbers to display.
 if ($patientid) {
  $prow = sqlQuery("SELECT lname, fname, phone_home, phone_biz " .
   "FROM patient_data WHERE pid = '" . $patientid . "'");
  $patientname = $prow['lname'] . ", " . $prow['fname'];
  if ($prow['phone_home']) $patienttitle .= " H=" . $prow['phone_home'];
  if ($prow['phone_biz']) $patienttitle  .= " W=" . $prow['phone_biz'];
 }

 // Get the providers list.
 $ures = sqlStatement("SELECT id, username, fname, lname FROM users WHERE " .
  "authorized != 0 ORDER BY lname, fname");

 // Get event categories.
 $cres = sqlStatement("SELECT pc_catid, pc_catname, pc_recurrtype, pc_duration, pc_end_all_day " .
  "FROM openemr_postcalendar_categories ORDER BY pc_catname");

 // Fix up the time format for AM/PM.
 $startampm = '1';
 if ($starttimeh >= 12) { // p.m. starts at noon and not 12:01
  $startampm = '2';
  if ($starttimeh > 12) $starttimeh -= 12;
 }
?>
<html>
<head>
<title><? echo $eid ? "Edit" : "Add New" ?> Event</title>
<link rel=stylesheet href='<? echo $css_header ?>' type='text/css'>

<style>
td { font-size:10pt; }
</style>

<script type="text/javascript" src="../../../library/topdialog.js"></script>
<script type="text/javascript" src="../../../library/dialog.js"></script>
<script type="text/javascript" src="../../../library/overlib_mini.js"></script>
<script type="text/javascript" src="../../../library/calendar.js"></script>
<script type="text/javascript" src="../../../library/textformat.js"></script>

<script language="JavaScript">

 var mypcc = '<? echo $GLOBALS['phone_country_code'] ?>';

 var durations = new Array();
 // var rectypes  = new Array();
<?
 // Read the event categories, generate their options list, and get
 // the default event duration from them if this is a new event.
 $catoptions = "";
 $thisduration = 0;
 if ($eid) {
  $thisduration = $row['pc_alldayevent'] ? 1440 : round($row['pc_duration'] / 60);
 }
 while ($crow = sqlFetchArray($cres)) {
  $duration = round($crow['pc_duration'] / 60);
  if ($crow['pc_end_all_day']) $duration = 1440;
  echo " durations[" . $crow['pc_catid'] . "] = $duration\n";
  // echo " rectypes[" . $crow['pc_catid'] . "] = " . $crow['pc_recurrtype'] . "\n";
  $catoptions .= "    <option value='" . $crow['pc_catid'] . "'";
  if ($eid) {
   if ($crow['pc_catid'] == $row['pc_catid']) $catoptions .= " selected";
  } else {
   if ($crow['pc_catid'] == '5') { // office visit
    $catoptions .= " selected";
    $thisduration = $duration;
   }
  }
  $catoptions .= ">" . $crow['pc_catname'] . "</option>\n";
 }
?>

 // This is for callback by the find-patient popup.
 function setpatient(pid, lname, fname) {
  var f = document.forms[0];
  f.form_patient.value = lname + ', ' + fname;
  f.form_pid.value = pid;
 }

 // This invokes the find-patient popup.
 function sel_patient() {
  dlgopen('find_patient_popup.php', '_blank', 500, 400);
 }

 // Do whatever is needed when a new event category is selected.
 // For now this means changing the event title and duration.
 function set_category() {
  var f = document.forms[0];
  var s = f.form_category;
  if (s.selectedIndex >= 0) {
   f.form_title.value = s.options[s.selectedIndex].text;
   f.form_duration.value = durations[s.options[s.selectedIndex].value];
  }
 }

 // Modify some visual attributes when the all-day or timed-event
 // radio buttons are clicked.
 function set_allday() {
  var f = document.forms[0];
  var color1 = '#777777';
  var color2 = '#777777';
  var disabled2 = true;
  if (document.getElementById('rballday1').checked) {
   color1 = '#000000';
  }
  if (document.getElementById('rballday2').checked) {
   color2 = '#000000';
   disabled2 = false;
  }
  document.getElementById('tdallday1').style.color = color1;
  document.getElementById('tdallday2').style.color = color2;
  document.getElementById('tdallday3').style.color = color2;
  document.getElementById('tdallday4').style.color = color2;
  document.getElementById('tdallday5').style.color = color2;
  f.form_hour.disabled     = disabled2;
  f.form_minute.disabled   = disabled2;
  f.form_ampm.disabled     = disabled2;
  f.form_duration.disabled = disabled2;
 }

 // Modify some visual attributes when the Repeat checkbox is clicked.
 function set_repeat() {
  var f = document.forms[0];
  var isdisabled = true;
  var mycolor = '#777777';
  var myvisibility = 'hidden';
  if (f.form_repeat.checked) {
   isdisabled = false;
   mycolor = '#000000';
   myvisibility = 'visible';
  }
  f.form_repeat_type.disabled = isdisabled;
  f.form_enddate.disabled = isdisabled;
  document.getElementById('tdrepeat1').style.color = mycolor;
  document.getElementById('tdrepeat2').style.color = mycolor;
  document.getElementById('imgrepeat').style.visibility = myvisibility;
 }

 // This is for callback by the find-available popup.
 function setappt(year,mon,mday,hours,minutes) {
  var f = document.forms[0];
  f.form_date.value = '' + year + '-' +
   ('' + (mon  + 100)).substring(1) + '-' +
   ('' + (mday + 100)).substring(1);
  f.form_ampm.selectedIndex = (hours >= 12) ? 1 : 0;
  f.form_hour.value = (hours > 12) ? hours - 12 : hours;
  f.form_minute.value = ('' + (minutes + 100)).substring(1);
 }

 // Invoke the find-available popup.
 function find_available() {
  var s = document.forms[0].form_provider;
  dlgopen('find_appt_popup.php?providerid=' + s.options[s.selectedIndex].value,
   '_blank', 500, 400);
 }

</script>

</head>

<body <?echo $top_bg_line;?> onunload='imclosing()'>

<!-- Required for the popup date selectors -->
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>

<form method='post' name='theform' action='add_edit_event.php?eid=<? echo $eid ?>'>
<center>

<table border='0' width='100%'>

 <tr>
  <td width='1%' nowrap>
   <b>Category:</b>
  </td>
  <td nowrap>
   <select name='form_category' onchange='set_category()' style='width:100%'>
<? echo $catoptions ?>
   </select>
  </td>
  <td width='1%' nowrap>
   &nbsp;&nbsp;
   <input type='radio' name='form_allday' onclick='set_allday()' value='1' id='rballday1'
    <? if ($thisduration == 1440) echo "checked " ?>/>
  </td>
  <td colspan='2' nowrap id='tdallday1'>
   All day event
  </td>
 </tr>

 <tr>
  <td nowrap>
   <b>Date:</b>
  </td>
  <td nowrap>
   <input type='text' size='10' name='form_date'
    value='<? echo $eid ? $row['pc_eventDate'] : $date ?>'
    title='yyyy-mm-dd event date or starting date'
    onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)' />
   <a href="javascript:show_calendar('theform.form_date')"
    title="Click here to choose a date"
    ><img src='../../pic/show_calendar.gif' align='absbottom' width='24' height='22' border='0' alt='[?]'></a>
  </td>
  <td nowrap>
   &nbsp;&nbsp;
   <input type='radio' name='form_allday' onclick='set_allday()' value='0' id='rballday2'
    <? if ($thisduration != 1440) echo "checked " ?>/>
  </td>
  <td width='1%' nowrap id='tdallday2'>
   Time
  </td>
  <td width='1%' nowrap id='tdallday3'>
   <input type='text' size='2' name='form_hour'
    value='<? echo $starttimeh ?>'
    title='Event start time' /> :
   <input type='text' size='2' name='form_minute'
    value='<? echo $starttimem ?>'
    title='Event start time' />&nbsp;
   <select name='form_ampm' title='Note: 12:00 noon is PM, not AM'>
    <option value='1'>AM</option>
    <option value='2'<? if ($startampm == '2') echo " selected" ?>>PM</option>
   </select>
  </td>
 </tr>

 <tr>
  <td nowrap>
   <b>Title:</b>
  </td>
  <td nowrap>
   <input type='text' size='10' name='form_title'
    value='<? echo addslashes($row['pc_title']) ?>'
    style='width:100%'
    title='Event title' />
  </td>
  <td nowrap>
   &nbsp;
  </td>
  <td nowrap id='tdallday4'>
   duration
  </td>
  <td nowrap id='tdallday5'>
   <input type='text' size='4' name='form_duration' value='<? echo $thisduration ?>'
    title='Event duration in minutes' /> minutes
  </td>
 </tr>

 <tr>
  <td nowrap>
   <b>Patient:</b>
  </td>
  <td nowrap>
   <input type='text' size='10' name='form_patient' style='width:100%'
    value='<? echo $patientname ?>' onclick='sel_patient()'
    title='Click to select patient' readonly />
   <input type='hidden' name='form_pid' value='<? echo $patientid ?>' />
  </td>
  <td colspan='3' nowrap style='font-size:8pt'>
   &nbsp;<? echo $patienttitle ?>
  </td>
 </tr>

 <tr>
  <td nowrap>
   <b>Provider:</b>
  </td>
  <td nowrap>
   <select name='form_provider' style='width:100%'>
<?
 while ($urow = sqlFetchArray($ures)) {
  echo "    <option value='" . $urow['id'] . "'";
  if ($userid) {
   if ($urow['id'] == $userid) echo " selected";
  } else {
   if ($urow['id'] == $_SESSION['authUserID']) echo " selected";
  }
  echo ">" . $urow['lname'];
  if ($urow['fname']) echo ", " . $urow['fname'];
  echo "</option>\n";
 }
?>
   </select>
  </td>
  <td nowrap>
   &nbsp;&nbsp;
   <input type='checkbox' name='form_repeat' onclick='set_repeat(this)'
    value='1'<? if ($repeats) echo " checked" ?>/>
  </td>
  <td nowrap id='tdrepeat1'>
   Repeats
  </td>
  <td nowrap>
   <select name='form_repeat_type'>
<?
 // See common.api.php for these:
 foreach (array(0 => 'day', 4 => 'work day', 1 => 'week', 2 => 'month', 3 => 'year')
  as $key => $value)
 {
  echo "    <option value='$key'";
  if ($key == $repeattype) echo " selected";
  echo ">every $value</option>\n";
 }
?>
   </select>
  </td>
 </tr>

 <tr>
  <td nowrap>
   <b>Status:</b>
  </td>
  <td nowrap>
   <select name='form_apptstatus' style='width:100%' title='Appointment status'>
<?
 foreach ($statuses as $key => $value) {
  echo "    <option value='$key'";
  if ($key == $row['pc_apptstatus']) echo " selected";
  echo ">" . htmlspecialchars($value) . "</option>\n";
 }
?>
   </select>
  </td>
  <td nowrap>
   &nbsp;
  </td>
  <td nowrap id='tdrepeat2'>
   until
  </td>
  <td nowrap>
   <input type='text' size='10' name='form_enddate' value='<? echo $row['pc_endDate'] ?>'
    onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)'
    title='yyyy-mm-dd last date of this event' />
   <a href="javascript:show_calendar('theform.form_enddate')"
    title="Click here to choose a date"
    ><img src='../../pic/show_calendar.gif' align='absbottom' width='24' height='22'
      border='0' alt='[?]' id='imgrepeat' /></a>
  </td>
 </tr>

 <tr>
  <td nowrap>
   <b>Comments:</b>
  </td>
  <td colspan='4' nowrap>
   <input type='text' size='40' name='form_comments' style='width:100%'
    value='<? echo $hometext ?>'
    title='Optional information about this event' />
  </td>
 </tr>

</table>

<p>
<input type='submit' name='form_save' value='Save' />
&nbsp;
<input type='button' value='Find Available' onclick='find_available()' />
&nbsp;
<input type='submit' name='form_delete' value='Delete'<? if (!$eid) echo " disabled" ?> />
&nbsp;
<input type='button' value='Cancel' onclick='window.close()' />
</p>
</center>
</form>
<script language='JavaScript'>
<? if (! $eid) { ?>
 set_category();
<? } ?>
 set_allday();
 set_repeat();
</script>
</body>
</html>
