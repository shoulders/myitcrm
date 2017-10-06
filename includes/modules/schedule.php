<?php

/*
 * @package   QWcrm
 * @author    Jon Brown https://quantumwarp.com/
 * @copyright Copyright (C) 2016 - 2017 Jon Brown, All rights reserved.
 * @license   GNU/GPLv3 or later; https://www.gnu.org/licenses/gpl.html
 */

/*
 * Mandatory Code - Code that is run upon the file being loaded
 * Display Functions - Code that is used to primarily display records - linked tables
 * New/Insert Functions - Creation of new records
 * Get Functions - Grabs specific records/fields ready for update - no table linking
 * Update Functions - For updating records/fields
 * Close Functions - Closing Work Orders code
 * Delete Functions - Deleting Work Orders
 * iCalendar Functions - code for creating and manipulation iCalendar .ics format
 * Other Functions - All other functions not covered above
 */

defined('_QWEXEC') or die;

/** Mandatory Code **/

// If no schedule year set, use today's year
if (isset($VAR['start_year'])) {
    $start_year = $VAR['start_year'];
} else {
    $start_year = date('Y');
}

// If no schedule month set, use today's month
if (isset($VAR['start_month'])) {
    $start_month = $VAR['start_month'];
} else {
    $start_month = date('m');
}

// If no schedule day set, use today's day
if (isset($VAR['start_day'])) {
    $start_day = $VAR['start_day'];
} else {
    $start_day = date('d');
}

/** Display Functions **/

###############################
# Display Workorder Schedules #
###############################

function display_workorder_schedules($db, $workorder_id)
{
    $sql = "SELECT * FROM ".PRFX."schedule WHERE workorder_id=".$db->qstr($workorder_id);
    
    if (!$rs = $db->Execute($sql)) {
        force_error_page($_GET['page'], 'database', __FILE__, __FUNCTION__, $db->ErrorMsg(), $sql, _gettext("Failed to return the work order schedules."));
        exit;
    } else {
        return $rs->GetArray();
    }
}

/** Insert Functions **/

######################################
#  Insert schedule                   #
######################################

function insert_schedule($db, $start_date, $StartTime, $end_date, $EndTime, $notes, $employee_id, $customer_id, $workorder_id)
{

    // Get Full Timestamps for the schedule item (date/hour/minute/second) - 12 Hour
    //$start_timestamp = datetime_to_timestamp($start_date, $start_time['Time_Hour'], $start_time['Time_Minute'], '0', '12', $start_time['time_meridian']);
    //$end_timestamp   = datetime_to_timestamp($end_date, $end_time['Time_Hour'], $end_time['Time_Minute'], '0', '12', $end_time['time_meridian']);
    
    // Get Full Timestamps for the schedule item (date/hour/minute/second) - 24 Hour
    $start_timestamp = datetime_to_timestamp($start_date, $StartTime['Time_Hour'], $StartTime['Time_Minute'], '0', '24');
    $end_timestamp   = datetime_to_timestamp($end_date, $EndTime['Time_Hour'], $EndTime['Time_Minute'], '0', '24');
    
    // Corrects the extra time segment issue
    $end_timestamp -= 1;
    
    // Validate the submitted dates
    if (!validate_schedule_times($db, $start_date, $start_timestamp, $end_timestamp, $employee_id)) {
        return false;
    }

    // Insert schedule item into the database
    $sql = "INSERT INTO ".PRFX."schedule SET
            employee_id     =". $db->qstr($employee_id).",
            customer_id     =". $db->qstr($customer_id).",   
            workorder_id    =". $db->qstr($workorder_id).",
            start_time      =". $db->qstr($start_timestamp).",
            end_time        =". $db->qstr($end_timestamp).",            
            notes           =". $db->qstr($notes);

    if (!$rs = $db->Execute($sql)) {
        force_error_page($_GET['page'], 'database', __FILE__, __FUNCTION__, $db->ErrorMsg(), $sql, _gettext("Failed to insert the schedule record into the database."));
        exit;
    } else {
        
        // Get the new Workorders ID
        $schedule_id = $db->Insert_ID();
        
        // Assign the workorder to the scheduled employee
        assign_workorder_to_employee($db, $workorder_id, $employee_id);
    
        // Change the Workorders Status
        update_workorder_status($db, $workorder_id, 'scheduled');
        
        // Insert Work Order History Note
        insert_workorder_history_note($db, $workorder_id, _gettext("Schedule").' '.$schedule_id.' '._gettext("was created by").' '.QFactory::getUser()->login_display_name.'.');
        
        // Log activity
        write_record_to_activity_log(_gettext("Schedule").' '.$schedule_id.' '._gettext("has been created and added to work order").' '.$workorder_id.' '._gettext("by").' '.QFactory::getUser()->login_display_name.'.');
        
        // Update last active record
        update_workorder_last_active($db, $workorder_id);
        update_customer_last_active($db, $customer_id);
    
        return true;
    }
}

/** Get Functions **/

################################
#  Get Schedule Details        #
################################

function get_schedule_details($db, $schedule_id, $item = null)
{
    $sql = "SELECT * FROM ".PRFX."schedule WHERE schedule_id=".$db->qstr($schedule_id);
    
    if (!$rs = $db->Execute($sql)) {
        force_error_page($_GET['page'], 'database', __FILE__, __FUNCTION__, $db->ErrorMsg(), $sql, _gettext("Failed to get the schedule details."));
        exit;
    } else {
        if ($item === null) {
            return $rs->GetRowAssoc();
        } else {
            return $rs->fields[$item];
        }
    }
}

##########################################################
#    Get all schedule IDs for an employee for a date     #
##########################################################

function get_schedule_ids_for_employee_on_date($db, $employee_id, $start_year, $start_month, $start_day)
{
    
    // Get the start and end time of the calendar schedule to be displayed, Office hours only - (unix timestamp)
    $company_day_start = mktime(get_company_details($db, 'opening_hour'), get_company_details($db, 'opening_minute'), 0, $start_month, $start_day, $start_year);
    $company_day_end   = mktime(get_company_details($db, 'closing_hour'), get_company_details($db, 'closing_minute'), 59, $start_month, $start_day, $start_year);
      
    // Look in the database for a scheduled events for the current schedule day (within business hours)
    $sql = "SELECT schedule_id
            FROM ".PRFX."schedule       
            WHERE start_time >= ".$company_day_start." AND start_time <= ".$company_day_end."
            AND employee_id =".$db->qstr($employee_id)."
            ORDER BY start_time
            ASC";
    
    if (!$rs = $db->Execute($sql)) {
        force_error_page($_GET['page'], 'database', __FILE__, __FUNCTION__, $db->ErrorMsg(), $sql, _gettext("Failed to get all schedule IDs belonging to an employee."));
        exit;
    } else {
        return $rs->GetArray();
    }
}

/** Update Functions **/

######################################
#      Update Schedule               #
######################################

function update_schedule($db, $start_date, $StartTime, $end_date, $EndTime, $notes, $schedule_id, $employee_id, $customer_id, $workorder_id)
{
    
    // Get Full Timestamps for the schedule item (date/hour/minute/second) - 12 Hour
    //$start_timestamp = datetime_to_timestamp($start_date, $start_time['Time_Hour'], $start_time['Time_Minute'], '0', '12', $start_time['time_meridian']);
    //$end_timestamp   = datetime_to_timestamp($end_date, $end_time['Time_Hour'], $end_time['Time_Minute'], '0', '12', $end_time['time_meridian']);
    
    // Get Full Timestamps for the schedule item (date/hour/minute/second) - 24 Hour
    $start_timestamp = datetime_to_timestamp($start_date, $StartTime['Time_Hour'], $StartTime['Time_Minute'], '0', '24');
    $end_timestamp   = datetime_to_timestamp($end_date, $EndTime['Time_Hour'], $EndTime['Time_Minute'], '0', '24');
    
    // Corrects the extra time segment issue
    $end_timestamp -= 1;
    
    // Validate the submitted dates
    if (!validate_schedule_times($db, $start_date, $start_timestamp, $end_timestamp, $employee_id, $schedule_id)) {
        return false;
    }
    
    $sql = "UPDATE ".PRFX."schedule SET
        schedule_id         =". $db->qstr($schedule_id).",
        employee_id         =". $db->qstr($employee_id).",
        customer_id         =". $db->qstr($customer_id).",
        workorder_id        =". $db->qstr($workorder_id).",   
        start_time          =". $db->qstr($start_timestamp).",
        end_time            =". $db->qstr($end_timestamp).",                
        notes               =". $db->qstr($notes)."
        WHERE schedule_id   =". $db->qstr($schedule_id);
   
    if (!$rs = $db->Execute($sql)) {
        force_error_page($_GET['page'], 'database', __FILE__, __FUNCTION__, $db->ErrorMsg(), $sql, _gettext("Failed to update a schedule record."));
        exit;
    } else {
         
        // Insert Work Order History Note
        insert_workorder_history_note($db, $workorder_id, _gettext("Schedule").' '.$schedule_id.' '._gettext("was updated by").' '.QFactory::getUser()->login_display_name.'.');
        
        // Log activity
        write_record_to_activity_log(_gettext("Schedule").' '.$schedule_id.' '._gettext("was updated by").' '.QFactory::getUser()->login_display_name.'.');
        
        // Update last active record
        update_workorder_last_active($db, $workorder_id);
        update_customer_last_active($db, $customer_id);
        
        return true;
    }
}

/** Close Functions **/

/** Delete Functions **/

##################################
#        Delete Schedule         #
##################################

function delete_schedule($db, $schedule_id)
{
    
    // Get schedule details before deleting
    $schedule_details = get_schedule_details($db, $schedule_id);
    
    $sql = "DELETE FROM ".PRFX."schedule WHERE schedule_id =".$db->qstr($schedule_id);

    if (!$rs = $db->Execute($sql)) {
        force_error_page($_GET['page'], 'database', __FILE__, __FUNCTION__, $db->ErrorMsg(), $sql, _gettext("Failed to delete a schedule record."));
        exit;
    } else {
        
        // If there are no schedules left for this workorder
        if (!count_workorder_schedule_items($db, $schedule_details['workorder_id'])) {
            
            // if the workorder status is 'scheduled', change the status to 'assigned'
            if (get_workorder_details($db, $schedule_details['workorder_id'], 'status') == 'scheduled') {
                update_workorder_status($db, $schedule_details['workorder_id'], 'assigned');
            }
        }
        
        // Create a Workorder History Note
        insert_workorder_history_note($db, $schedule_details['workorder_id'], _gettext("Schedule").' '.$schedule_id.' '._gettext("was deleted by").' '.QFactory::getUser()->login_display_name.'.');
        
        // Log activity
        write_record_to_activity_log(_gettext("Schedule").' '.$schedule_id.' '._gettext("for Work Order").' '.$schedule_details['workorder_id'].' '._gettext("was deleted by").' '.QFactory::getUser()->login_display_name.'.');
        
        // Update last active record
        update_workorder_last_active($db, $schedule_details['workorder_id']);
        update_customer_last_active($db, $schedule_details['customer_id']);
        
        return true;
    }
}

/** iCalendar Functions **/

#####################################################
#     .ics header settings                          #
#####################################################

function ics_header_settings()
{
    $ics_header_settings =
        'BEGIN:VCALENDAR'."\r\n".
        'VERSION:2.0'."\r\n".
        'PRODID:-//QuantumWarp//QWcrm//EN'."\r\n".
        'CALSCALE:GREGORIAN'."\r\n".
        'METHOD:PUBLISH'."\r\n";        // does this force events to be added rather than create a new calendar
    
    return $ics_header_settings;
}

#####################################################
#        This is the schedule .ics builder          #
#####################################################

function build_single_schedule_ics($db, $schedule_id, $ics_type = 'single')
{
    
    // Get the schedule information
    $single_schedule    = get_schedule_details($db, $schedule_id);
    $workorder          = get_workorder_details($db, $single_schedule['workorder_id']);
    $customer           = get_customer_details($db, $workorder['customer_id']);
    
    $start_datetime     = timestamp_to_ics_datetime($single_schedule['start_time']);
    $end_datetime       = timestamp_to_ics_datetime($single_schedule['end_time']);
    $current_datetime   = timestamp_to_ics_datetime(time());

    $summary            = prepare_ics_strings('SUMMARY', $customer['display_name'].' - Workorder '.$single_schedule['workorder_id'].' - Schedule '.$schedule_id);
    $description        = prepare_ics_strings('DESCRIPTION', build_ics_description('textarea', $single_schedule, $customer, $workorder));
    $x_alt_desc         = prepare_ics_strings('X-ALT-DESC;FMTTYPE=text/html', build_ics_description('html', $single_schedule, $customer, $workorder));
    
    $location           = prepare_ics_strings('LOCATION', build_single_line_address($customer['address'], $customer['city'], $customer['state'], $customer['zip']));
    $uniqid             = 'QWcrm-'.$single_schedule['schedule_id'].'-'.$single_schedule['start_time'];
  
    // Build the Schedule .ics content
    
    $single_schedule_ics = '';
   
    if ($ics_type == 'single') {
        $single_schedule_ics .= ics_header_settings();
    }
    
    $single_schedule_ics .=
        'BEGIN:VEVENT'."\r\n".
        'DTSTART:'.$start_datetime."\r\n".
        'DTEND:'.$end_datetime."\r\n".
        'DTSTAMP:'.$current_datetime."\r\n".
        'LOCATION:'.$location."\r\n".
        'SUMMARY:'.$summary."\r\n".
        'DESCRIPTION:'.$description."\r\n".
        'X-ALT-DESC;FMTTYPE=text/html:'.$x_alt_desc."\r\n".
        'UID:'.$uniqid."\r\n".
        'END:VEVENT'."\r\n";

    if ($ics_type == 'single') {
        $single_schedule_ics .= 'END:VCALENDAR'."\r\n";
    }

    // Return the .ics content
    return $single_schedule_ics;
}

#########################################################################
#    Build a multi .ics - the employees schedule items for that day     #
#########################################################################

function build_ics_schedule_day($db, $employee_id, $start_year, $start_month, $start_day)
{
    
    // fetch all schdule items for this setup
    $schedule_multi_ics = ics_header_settings();
    
    $schedule_multi_id = get_schedule_ids_for_employee_on_date($db, $employee_id, $start_year, $start_month, $start_day);
    
    foreach ($schedule_multi_id as $schedule_id) {
        $schedule_multi_ics .= build_single_schedule_ics($db, $schedule_id['schedule_id'], $type = 'multi');
    }
   
    $schedule_multi_ics .= 'END:VCALENDAR'."\r\n";
    
    return $schedule_multi_ics;
}

#########################################################
# Build single line address (suitable for .ics location #
#########################################################

function build_single_line_address($address, $city, $state, $postcode)
{
       
    // Replace real newlines with comma and space, build address using commans
    return preg_replace("/(\r\n|\r|\n)/", ', ', $address).', '.$city.', '.$state.', '.$postcode;
}

#####################################
#     build adddress html style     #
#####################################

// build adddress html style
function build_html_adddress($address, $city, $state, $postcode)
{
       
    // Open address block
    $html_address = '<address>';
    
    // Replace real newlines with comma and space, build address using commas
    $html_address .= preg_replace("/(\r\n|\r|\n)/", '<br>', $address).'<br>'.$city.'<br>'.$state.'<br>'.$postcode;
    
    // Close address block
    $html_address .= '</address>';
    
    // Return the built address block
    return $html_address;
}

##################################################
#    Build description for ics                   #
##################################################

function build_ics_description($type, $single_schedule, $customer, $workorder)
{
    if ($type == 'textarea') {

        // Workorder and Schedule Information
        $description =  _gettext("Scope").': \n\n'.
                        $workorder['scope'].'\n\n'.
                        _gettext("Description").': \n\n'.
                        html_to_textarea($workorder['description']).'\n\n'.
                        _gettext("Schedule Notes").': \n\n'.
                        html_to_textarea($single_schedule['notes']);

        // Contact Information
        $description .= _gettext("Contact Information")  .''.'\n\n'.
                        _gettext("Company")              .': '   .$customer['display_name'].'\n\n'.
                        _gettext("Contact")              .': '   .$customer['first_name'].' '.$customer['last_name'].'\n\n'.
                        _gettext("Phone")                .': '   .$customer['primary_phone'].'\n\n'.
                        _gettext("Mobile")               .': '   .$customer['mobile_phone'].'\n\n'.
                        _gettext("Website")              .': '   .$customer['website'].'\n\n'.
                        _gettext("Email")                .': '   .$customer['email'].'\n\n'.
                        _gettext("Address")              .': '   .build_single_line_address($customer['address'], $customer['city'], $customer['state'], $customer['zip']).'\n\n';
    }
    
    if ($type == 'html') {
        
        // Open HTML Wrapper
        $description .= '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2//EN">\n'.
                        '<HTML>\n'.
                        '<HEAD>\n'.
                        '<META NAME="Generator" CONTENT="QuantumWarp - QWcrm">\n'.
                        '<TITLE></TITLE>\n'.
                        '</HEAD>\n'.
                        '<BODY>\n';
    
        // Workorder and Schedule Information
        $description .= '<p><strong>'._gettext("Scope").': </strong></p>'.
                        '<p>'.$workorder['scope'].'</p>'.
                        '<p><strong>'._gettext("Description").': </strong></p>'.
                        '<div>'.$workorder['description'].'</div>'.
                        '<p><strong>'._gettext("Schedule Notes").': </strong></p>'.
                        '<div>'.$single_schedule['notes'].'</div>';

        // Contact Information
        $description .= '<p><strong>'._gettext("Contact Information").'</strong></p>'.
                        '<p>'.
                        '<strong>'._gettext("Company")   .':</strong> '  .$customer['display_name'].'<br>'.
                        '<strong>'._gettext("Contact")   .':</strong> '  .$customer['first_name'].' '.$customer['last_name'].'<br>'.
                        '<strong>'._gettext("Phone")     .':</strong> '  .$customer['primary_phone'].'<br>'.
                        '<strong>'._gettext("Mobile")    .':</strong> '  .$customer['mobile_phone'].'<br>'.
                        '<strong>'._gettext("Website")   .':</strong> '  .$customer['website'].
                        '<strong>'._gettext("Email")     .':</strong> '  .$customer['email'].'<br>'.
                        '</p>'.
                        '<p><strong>'._gettext("Contact Information").'Address: </strong></p>'.
                        build_html_adddress($customer['address'], $customer['city'], $customer['state'], $customer['zip']);
        
        // Close HTML Wrapper
        $description .= '</BODY>\n'.
                        '</HTML>';
    }
    
    return $description;
}

##################################################
# Convert Timestamp into .ics compatible  format #
##################################################

// Converts a unix timestamp to an ics-friendly format
// NOTE: "Z" means that this timestamp is a UTC timestamp. If you need
// to set a locale, remove the "\Z" and modify DTEND, DTSTAMP and DTSTART
// with TZID properties (see RFC 5545 section 3.3.5 for info)
//
// Also note that we are using "H" instead of "g" because iCalendar's Time format
// requires 24-hour time (see RFC 5545 section 3.3.12 for info).
function timestamp_to_ics_datetime($timestamp)
{
    return date('Ymd\THis\Z', $timestamp);
}

##################################################
#      Convert HTML into Textarea                #
##################################################

function html_to_textarea($content)
{
    
    // Remove real newlines
    $content = preg_replace("/(\r|\n)/", '', $content);
        
    // Replace <br> and variants with newline
    $content = preg_replace('/<br ?\/?>/', '\n', $content);
    
    // Remove <p>
    $content = preg_replace('/<p>/', '', $content);
    
    // Replace </p> with newline
    $content = preg_replace('/<\/p>/', '\n', $content);
    
    return strip_tags($content);
}

##################################################
#      Prepare the text strings for .ics         #
##################################################

// prepare the text strings
function prepare_ics_strings($ics_keyname, $ics_string)
{
    
    // Remove whitespace at the beginning and end of the string
    $ics_string = trim($ics_string);
    
    // Replace real newlines with escaped character (i dont think this is needed)
    $ics_string = preg_replace("/(\r|\n)/", '', $ics_string);
    
    // Replace combined escaped newlines to escaped unix style newlines
    $ics_string = preg_replace('/(\r\n)/', '\n', $ics_string);
    
    // Escape some characters .ics does not like
    $ics_string = preg_replace('/([\,;])/', '\\\$1', $ics_string);
    
    // Break into octets with 75 character line limit (as per spec)
    $ics_string = ics_string_octet_split($ics_keyname, $ics_string);
    
    return $ics_string;
}

##################################################
#     split ics content into 75-octet line       #
##################################################

// Original script from https://gist.github.com/hugowetterberg/81747

function ics_string_octet_split($ics_keyname, $ics_string)
{

    // Get the ics_key length (after correction)
    $ics_keyname        .= ':';
    $ics_keyname_len    = strlen($ics_keyname);
    
    // Get the Key by Regex if full string supplied
    //preg_match('/^.*\:/U', $ics_string, $ics_keyname);
    
    $lines = array();
    
    // Loop out the chopped lines to the array
    while (strlen($ics_string) > (75 - $ics_keyname_len)) {
        $space  = (75 - $ics_keyname_len);
        $mbcc   = $space;
        
        while ($mbcc) {
            $line = mb_substr($ics_string, 0, $mbcc);
            $oct = strlen($line);
            
            if ($oct > $space) {
                $mbcc -= $oct - $space;
            } else {
                $lines[] = $line;
                $ics_keyname_len = 1; // Still take the tab into account
                $ics_string = mb_substr($ics_string, $mbcc);
                break;
            }
        }
    }
    
    if (!empty($ics_string)) {
        $lines[] = $ics_string;
    }
    
    // Join the lines and return the result
    return join($lines, "\r\n\t");
}

/** Other **/

#####################################################
#        Build Calendar Matrix                      #
#####################################################

function build_calendar_matrix($db, $start_year, $start_month, $start_day, $employee_id, $workorder_id = null)
{
    
    // Get the start and end time of the calendar schedule to be displayed, Office hours only - (unix timestamp)
    $company_day_start = mktime(get_company_details($db, 'opening_hour'), get_company_details($db, 'opening_minute'), 0, $start_month, $start_day, $start_year);
    $company_day_end   = mktime(get_company_details($db, 'closing_hour'), get_company_details($db, 'closing_minute'), 59, $start_month, $start_day, $start_year);
    /* Same as above but my code - Get the start and end time of the calendar schedule to be displayed, Office hours only - (unix timestamp)
    $company_day_start = datetime_to_timestamp($current_schedule_date, get_company_details($db, 'opening_hour'), 0, 0, $clock = '24');
    $company_day_end   = datetime_to_timestamp($current_schedule_date, get_company_details($db, 'closing_hour'), 59, 0, $clock = '24');*/
      
    // Look in the database for a scheduled events for the current schedule day (within business hours)
    $sql ="SELECT 
        ".PRFX."schedule.*,
        ".PRFX."customer.display_name AS customer_display_name
        FROM ".PRFX."schedule
        INNER JOIN ".PRFX."workorder
        ON ".PRFX."schedule.workorder_id = ".PRFX."workorder.workorder_id
        INNER JOIN ".PRFX."customer
        ON ".PRFX."workorder.customer_id = ".PRFX."customer.customer_id
        WHERE ".PRFX."schedule.start_time >= ".$company_day_start."
        AND ".PRFX."schedule.start_time <= ".$company_day_end."
        AND ".PRFX."schedule.employee_id =".$db->qstr($employee_id)."
        ORDER BY ".PRFX."schedule.start_time
        ASC";
    
    if (!$rs = $db->Execute($sql)) {
        force_error_page($_GET['page'], 'database', __FILE__, __FUNCTION__, $db->ErrorMsg(), $sql, _gettext("Failed to return the selected schedules."));
        exit;
    }

    // Add any scheduled events found into the $scheduleObject for any employee
    $scheduleObject = array();
    while (!$rs->EOF) {
        array_push($scheduleObject, array(
            'schedule_id'           => $rs->fields['schedule_id'],
            'customer_display_name' => $rs->fields['customer_display_name'],
            'workorder_id'          => $rs->fields['workorder_id'],
            'start_time'            => $rs->fields['start_time'],
            'end_time'              => $rs->fields['end_time'],
            'notes'                 => $rs->fields['notes']
            ));
        $rs->MoveNext();
    }
    
    /* Build the Calendar Matrix Table Content */

    // Open the Calendar Matrix Table - Blue Header Bar
    $calendar .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"olotable\">\n
        <tr>\n
            <td class=\"olohead\" width=\"75\">&nbsp;</td>\n
            <td class=\"olohead\" width=\"600\">&nbsp;</td>\n
        </tr>\n";
    
    // Set the Schedule item array counter
    $i = 0;
    
    // Length of calendar times slots/segments
    $time_slot_length   = 900;
    
    // Needed for the loop advancement
    $matrixStartTime    = $company_day_start;
    
    // Cycle through the Business day in 15 minute segments (set at the bottom) - you take of the $time_slot_length to prevent an additional slot at the end
    while ($matrixStartTime <= $company_day_end - $time_slot_length) {

        /*
         * There are 2 segment/row types: Whole Hour, Hour With minutes
         * Both have different Styles
         * Left Cells = Time
         * Right Cells = Blank || Clickable Links || Schedule Item
         * each ROW is assigned a date and are seperated by 15 minutes
         */
        
        /* Start ROW */
        $calendar .= "<tr>\n";

        /* Schedule Block ROW */
        
        // If the ROW is within the time range of the schedule item
        if ($matrixStartTime >= $scheduleObject[$i]['start_time'] && $matrixStartTime <= $scheduleObject[$i]['end_time']) {
            
            /* LEFT CELL*/
            
            // Make the left column blank when there is a schedule item
            $calendar .= "<td></td>\n";

            /* RIGHT CELL */
            
            // Build the Schedule Block (If the ROW is the same as the schedule item's start time)
            if ($matrixStartTime == $scheduleObject[$i]['start_time']) {

                // Open CELL and add clickable link (to workorder) for CELL
                $calendar .= "<td class=\"menutd2\" align=\"center\" >\n";

                // Schedule Item Title
                $calendar .= "<b><font color=\"red\">"._gettext("Work Order")." ".$scheduleObject[$i]['workorder_id']." "._gettext("for")." ". $scheduleObject[$i]['customer_display_name']."</font></b><br>\n";

                // Time period of schedule
                $calendar .= "<b><font color=\"red\">".date("H:i", $scheduleObject[$i]['start_time'])." - ".date("H:i", $scheduleObject[$i]['end_time'])."</font></b><br>\n";

                // Schedule Notes
                $calendar .= "<div style=\"color: blue; font-weight: bold;\">"._gettext("Notes").":  ".$scheduleObject[$i]['notes']."</div><br>\n";

                // Links for schedule
                $calendar .= "<b><a href=\"index.php?page=workorder:details&workorder_id=".$scheduleObject[$i]['workorder_id']."\">"._gettext("Work Order")."</a> - </b>";
                $calendar .= "<b><a href=\"index.php?page=schedule:details&schedule_id=".$scheduleObject[$i]['schedule_id']."\">"._gettext("Details")."</a></b>";
                if (!get_workorder_details($db, $scheduleObject[$i]['workorder_id'], 'is_closed')) {
                    $calendar .= " - <b><a href=\"index.php?page=schedule:edit&schedule_id=".$scheduleObject[$i]['schedule_id']."\">"._gettext("Edit")."</a></b> - ".
                                    "<b><a href=\"index.php?page=schedule:icalendar&schedule_id=".$scheduleObject[$i]['schedule_id']."&theme=print\">"._gettext("iCalendar")."</a></b> - ".
                                    "<b><a href=\"index.php?page=schedule:delete&schedule_id=".$scheduleObject[$i]['schedule_id']."\" onclick=\"return confirmChoice('"._gettext("Are you sure you want to delete this schedule?")."');\">"._gettext("Delete")."</a></b>\n";
                }

                // Close CELL
                $calendar .= "</td>\n";
            }
            
        /* Empty ROW */
        } else {
            
            // If just viewing/no workorder_id -  no clickable links to create schedule items
            if (!$workorder_id) {
                if (date('i', $matrixStartTime) == 0) {
                    $calendar .= "<td class=\"olotd\"><b>&nbsp;".date("H:i", $matrixStartTime)."</b></td>\n";
                    $calendar .= "<td class=\"olotd\"></td>\n";
                } else {
                    $calendar .= "<td class=\"olotd4\">&nbsp;".date("H:i", $matrixStartTime)."</td>\n";
                    $calendar .= "<td class=\"olotd4\"></td>\n";
                }
            
            // If workorder_id is present enable clickable links
            } else {
                if (date('i', $matrixStartTime) == 0) {
                    $calendar .= "<td class=\"olotd\" onClick=\"window.location='index.php?page=schedule:new&start_year={$start_year}&start_month={$start_month}&start_day={$start_day}&start_time=".date("H:i", $matrixStartTime)."&employee_id=".$employee_id."&workorder_id=".$workorder_id."'\"><b>&nbsp;".date("H:i", $matrixStartTime)."</b></td>\n";
                    $calendar .= "<td class=\"olotd\" onClick=\"window.location='index.php?page=schedule:new&start_year={$start_year}&start_month={$start_month}&start_day={$start_day}&start_time=".date("H:i", $matrixStartTime)."&employee_id=".$employee_id."&workorder_id=".$workorder_id."'\"></td>\n";
                } else {
                    $calendar .= "<td class=\"olotd4\" onClick=\"window.location='index.php?page=schedule:new&start_year={$start_year}&start_month={$start_month}&start_day={$start_day}&start_time=".date("H:i", $matrixStartTime)."&employee_id=".$employee_id."&workorder_id=".$workorder_id."'\">&nbsp;".date("H:i", $matrixStartTime)."</td>\n";
                    $calendar .= "<td class=\"olotd4\" onClick=\"window.location='index.php?page=schedule:new&start_year={$start_year}&start_month={$start_month}&start_day={$start_day}&start_time=".date("H:i", $matrixStartTime)."&employee_id=".$employee_id."&workorder_id=".$workorder_id."'\"></td>\n";
                }
            }
        }

        /* Close ROW */
        $calendar .= "</tr>\n";
        
        /* Loop Advancement */
        
        // Advance the schedule counter to the next item
        if ($matrixStartTime >= $scheduleObject[$i]['end_time']) {
            $i++;
        }

        // Advance matrixStartTime by 15 minutes before restarting loop to create 15 minute segements
        $matrixStartTime += $time_slot_length;
    }

    // Close the Calendar Matrix Table
    $calendar .= "</table>\n";
    
    // Return Calender HTML Matrix
    return $calendar;
}

############################################
#   validate schedule start and end time   #
############################################

function validate_schedule_times($db, $start_date, $start_timestamp, $end_timestamp, $employee_id, $schedule_id = null)
{
    global $smarty;
    
    $company_day_start = datetime_to_timestamp($start_date, get_company_details($db, 'opening_hour'), get_company_details($db, 'opening_minute'), '0', '24');
    $company_day_end   = datetime_to_timestamp($start_date, get_company_details($db, 'closing_hour'), get_company_details($db, 'closing_minute'), '0', '24');
    
    // Add the second I removed to correct extra segment issue
    $end_timestamp += 1;
     
    // If start time is after end time show message and stop further processing
    if ($start_timestamp > $end_timestamp) {
        $smarty->assign('warning_msg', _gettext("Schedule ends before it starts."));
        return false;
    }

    // If the start time is the same as the end time show message and stop further processing
    if ($start_timestamp == $end_timestamp) {
        $smarty->assign('warning_msg', _gettext("Start Time and End Time are the Same."));
        return false;
    }

    // Check the schedule is within Company Hours
    if ($start_timestamp < $company_day_start || $end_timestamp > $company_day_end) {
        $smarty->assign('warning_msg', _gettext("You cannot book work outside of company hours"));
        return false;
    }

    // Load all schedule items from the database for the supplied employee for the specified day (this currently ignores company hours)
    $sql = "SELECT
             schedule_id, start_time, end_time
            FROM ".PRFX."schedule
            WHERE start_time >= ".$company_day_start."
            AND end_time <=".$company_day_end."
            AND employee_id ='".$employee_id."'
            ORDER BY start_time
            ASC";
    
    if (!$rs = $db->Execute($sql)) {
        force_error_page($_GET['page'], 'database', __FILE__, __FUNCTION__, $db->ErrorMsg(), $sql, _gettext("Failed to return the selected schedules."));
        exit;
    }
    
    // Loop through all schedule items in the database (for the selected day and employee) and validate that schedule item can be inserted with no conflict.
    while (!$rs->EOF) {
        
        // Check the schedule is not getting updated
        if ($schedule_id != $rs->fields['schedule_id']) {

            // Check if this schedule item ends after another item has started
            if ($start_timestamp <= $rs->fields['start_time'] && $end_timestamp >= $rs->fields['start_time']) {
                $smarty->assign('warning_msg', _gettext("Schedule conflict - This schedule item ends after another schedule has started."));
                return false;
            }

            // Check if this schedule item starts before another item has finished
            if ($start_timestamp >= $rs->fields['start_time'] && $start_timestamp <= $rs->fields['end_time']) {
                $smarty->assign('warning_msg', _gettext("Schedule conflict - This schedule item starts before another schedule ends."));
                return false;
            }
        }

        $rs->MoveNext();
    }
    
    return true;
}

############################################
#   count schedule items for a workorder   #
############################################

function count_workorder_schedule_items($db, $workorder_id)
{
    $sql = "SELECT COUNT(*) AS count
            FROM ".PRFX."schedule
            WHERE workorder_id=".$db->qstr($workorder_id);
            
    if (!$rs = $db->Execute($sql)) {
        force_error_page($_GET['page'], 'database', __FILE__, __FUNCTION__, $db->ErrorMsg(), $sql, _gettext("Could not count schedule items for the specified Work Order."));
        exit;
    } else {
        return  $rs->fields['count'];
    }
}
