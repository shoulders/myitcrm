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
 * Other Functions - All other functions not covered above
 */

defined('_QWEXEC') or die;

/** Mandatory Code **/

/** Display Functions **/

#########################################
#     Display Gift Certificates         #
#########################################

function display_giftcerts($db, $direction = 'DESC', $use_pages = false, $page_no = '1', $records_per_page = '25', $search_term = null, $search_category = null, $status = null, $is_redeemed = null, $employee_id = null, $customer_id = null, $invoice_id = null)
{
    global $smarty;
    
    /* Filter the Records */
        
    // Default Action
    $whereTheseRecords = "WHERE ".PRFX."giftcert.giftcert_id";
    
    // Restrict results by search category and search term
    if ($search_term != null) {
        $whereTheseRecords .= " AND ".PRFX."giftcert.$search_category LIKE '%$search_term%'";
    }
    
    // Restrict by Status
    if ($status != null) {
        $whereTheseRecords .= " AND ".PRFX."giftcert.active=".$db->qstr($status);
    }
    
    // Restrict by redmption Status
    if ($is_redeemed != null) {
        $whereTheseRecords .= " AND ".PRFX."giftcert.is_redeemed=".$db->qstr($is_redeemed);
    }
    
    // Restrict by Employee
    if ($employee_id != null) {
        $whereTheseRecords .= " AND ".PRFX."giftcert.employee_id=".$db->qstr($employee_id);
    }
    
    // Restrict by Customer
    if ($customer_id != null) {
        $whereTheseRecords .= " AND ".PRFX."giftcert.customer_id=".$db->qstr($customer_id);
    }
    
    // Restrict by Invoice
    if ($invoice_id != null) {
        $whereTheseRecords .= " AND ".PRFX."giftcert.invoice_id=".$db->qstr($invoice_id);
    }
    
    /* The SQL code */
    
    $sql = "SELECT
            ".PRFX."giftcert.*,
            ".PRFX."user.display_name as employee_display_name,
            ".PRFX."customer.display_name as customer_display_name         
            FROM ".PRFX."giftcert
            LEFT JOIN ".PRFX."user ON ".PRFX."giftcert.employee_id = ".PRFX."user.user_id
            LEFT JOIN ".PRFX."customer ON ".PRFX."giftcert.customer_id = ".PRFX."customer.customer_id            
            ".$whereTheseRecords."
            GROUP BY ".PRFX."giftcert.giftcert_id           
            ORDER BY ".PRFX."giftcert.giftcert_id
            ".$direction;

    /* Restrict by pages */
        
    if ($use_pages == true) {
        
        // Get the start Record
        $start_record = (($page_no * $records_per_page) - $records_per_page);
        
        // Figure out the total number of records in the database for the given search
        if (!$rs = $db->Execute($sql)) {
            force_error_page($_GET['page'], 'database', __FILE__, __FUNCTION__, $db->ErrorMsg(), $sql, _gettext("Failed to count the matching Gift Certificate records."));
            exit;
        } else {
            $total_results = $rs->RecordCount();
            $smarty->assign('total_results', $total_results);
        }
        
        // Figure out the total number of pages. Always round up using ceil()
        $total_pages = ceil($total_results / $records_per_page);
        $smarty->assign('total_pages', $total_pages);

        // Assign the Previous page
        if ($page_no > 1) {
            $previous = ($page_no - 1);
        } else {
            $previous = 1;
        }
        $smarty->assign('previous', $previous);
        
        // Assign the next page
        if ($page_no < $total_pages) {
            $next = ($page_no + 1);
        } else {
            $next = $total_pages;
        }
        $smarty->assign('next', $next);
        
       // Only return the given page's records
        $limitTheseRecords = " LIMIT ".$start_record.", ".$records_per_page;
        
        // add the restriction on to the SQL
        $sql .= $limitTheseRecords;
        $rs = '';
    } else {
        
        // This make the drop down menu look correct
        $smarty->assign('total_pages', 1);
    }
    
    /* Return the records */
         
    if (!$rs = $db->Execute($sql)) {
        force_error_page($_GET['page'], 'database', __FILE__, __FUNCTION__, $db->ErrorMsg(), $sql, _gettext("Failed to return the matching Gift Certificate records."));
        exit;
    } else {
        $records = $rs->GetArray();   // If I call this twice for this search, no results are shown on the TPL

        if (empty($records)) {
            return false;
        } else {
            return $records;
        }
    }
}

/** Insert Functions **/

#################################
#   insert Gift Certificate     #
#################################

function insert_giftcert($db, $customer_id, $date_expires, $amount, $active, $notes)
{
    $sql = "INSERT INTO ".PRFX."giftcert SET 
            giftcert_code   =". $db->qstr(generate_giftcert_code()).",  
            employee_id     =". $db->qstr(QFactory::getUser()->login_user_id).",
            customer_id     =". $db->qstr($customer_id).",                        
            date_created    =". $db->qstr(time()).",
            date_expires    =". $db->qstr($date_expires).",                                     
            amount          =". $db->qstr($amount).",
            active          =". $db->qstr($active).",                
            notes           =". $db->qstr($notes);

    if (!$db->execute($sql)) {
        force_error_page($_GET['page'], 'database', __FILE__, __FUNCTION__, $db->ErrorMsg(), $sql, _gettext("Failed to insert the Gift Certificate into the database."));
        exit;
    } else {
        $giftcert_id = $db->Insert_ID();

        // Log activity
        write_record_to_activity_log(_gettext("Gift Certificate").' '.$giftcert_id.' '._gettext("was created by").' '.QFactory::getUser()->login_display_name.'.');

        // Update last active record
        update_customer_last_active($db, $customer_id);
        
        return $giftcert_id ;
    }
}

/** Get Functions **/

##########################
#  Get giftcert details  #
##########################

function get_giftcert_details($db, $giftcert_id, $item = null)
{
    $sql = "SELECT * FROM ".PRFX."giftcert WHERE giftcert_id=".$db->qstr($giftcert_id);
    
    if (!$rs = $db->execute($sql)) {
        force_error_page($_GET['page'], 'database', __FILE__, __FUNCTION__, $db->ErrorMsg(), $sql, _gettext("Failed to get the Gift Certificate details."));
        exit;
    } else {
        if ($item === null) {
            return $rs->GetRowAssoc();
        } else {
            return $rs->fields[$item];
        }
    }
}

#########################################
#   get giftcert_id by giftcert_code    #
#########################################

function get_giftcert_id_by_gifcert_code($db, $giftcert_code)
{
    $sql = "SELECT * FROM ".PRFX."giftcert WHERE giftcert_code=".$db->qstr($giftcert_code);

    if (!$rs = $db->execute($sql)) {
        force_error_page($_GET['page'], 'database', __FILE__, __FUNCTION__, $db->ErrorMsg(), $sql, _gettext("Failed to get the Gift Certificate ID by the Gift Certificate code."));
        exit;
    }
    
    if ($rs->fields['giftcert_id'] != '') {
        return $rs->fields['giftcert_id'];
    } else {
        return false;
    }
}

/** Update Functions **/

#################################
#   Update Gift Certificate     #
#################################

function update_giftcert($db, $giftcert_id, $date_expires, $amount, $active, $notes)
{
    $sql = "UPDATE ".PRFX."giftcert SET            
            date_expires    =". $db->qstr($date_expires).",
            amount          =". $db->qstr($amount).",
            active          =". $db->qstr($active).",                
            notes           =". $db->qstr($notes)."
            WHERE giftcert_id =".$giftcert_id;

    if (!$db->execute($sql)) {
        force_error_page($_GET['page'], 'database', __FILE__, __FUNCTION__, $db->ErrorMsg(), $sql, _gettext("Failed to update the Gift Certificate record in the database."));
        exit;
    } else {
        
        // Log activity
        write_record_to_activity_log(_gettext("Gift Certificate").' '.$giftcert_id.' '._gettext("was updated by").' '.QFactory::getUser()->login_display_name.'.');

        // Update last active record
        update_customer_last_active($db, get_giftcert_details($db, $giftcert_id, 'customer_id'));

        return;
    }
}

/** Close Functions **/

/** Delete Functions **/

##############################
#  Delete Gift Certificate   #
##############################

function delete_giftcert($db, $giftcert_id)
{
    
    // update and set non-active as you cannot really delete an issues gift certificate
    
    $sql = "UPDATE ".PRFX."giftcert SET active='0' WHERE giftcert_id=".$db->qstr($giftcert_id);

    if (!$db->execute($sql)) {
        force_error_page($_GET['page'], 'database', __FILE__, __FUNCTION__, $db->ErrorMsg(), $sql, _gettext("Failed to delete the Gift Certificate."));
        exit;
    } else {
        
        // Log activity
        write_record_to_activity_log(_gettext("Gift Certificate").' '.$giftcert_id.' '.' '._gettext("was deleted by").' '.QFactory::getUser()->login_display_name.'.');
        
        // Update last active record
        update_customer_last_active($db, get_giftcert_details($db, $giftcert_id, 'customer_id'));
        
        return;
    }
}

/** Other Functions **/

##############################################################
#  Validate the Gift Certificate can be used for a payemnt   #
##############################################################

function validate_giftcert_for_payment($db, $giftcert_id)
{

    // check is active
    if (get_giftcert_details($db, $giftcert_id, 'active') != 1) {
        //force_page('core','error', 'error_msg='._gettext("This gift certificate is not active"));
        //exit;
        return false;
    }

    // check if expired
    if (get_giftcert_details($db, $giftcert_id, 'date_expires') < time()) {
        //force_page('core', 'error', 'error_msg='._gettext("This gift certificate is expired."));
        //exit;
        return false;
    }
    
    return true;
}

############################################
#  Check if the giftcert is redeemed       #
############################################

function check_giftcert_redeemed($db, $giftcert_id)
{

    // check if redeemed
    if (get_giftcert_details($db, $giftcert_id, 'is_redeemed') == 1) {
        //force_page('core','error', 'error_msg=This gift certificate has been redeemed');
        //exit;
        return true;
    }
    
    return false;
}

############################################
#  Generate Random Gift Certificate code   #
############################################

function generate_giftcert_code()
{
    
    // generate a random string for the gift certificate
    
    $acceptedChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $max_offset = strlen($acceptedChars)-1;
    $giftcert_code = '';
    
    for ($i=0; $i < 16; $i++) {
        $giftcert_code .= $acceptedChars{mt_rand(0, $max_offset)};
    }
    
    return $giftcert_code;
}

######################################################
#   redeem the gift certificate against an invoice   #
######################################################

function update_giftcert_as_redeemed($db, $giftcert_id, $invoice_id)
{
    $sql = "UPDATE ".PRFX."giftcert SET
            invoice_id          =". $db->qstr($invoice_id).",
            date_redeemed       =". $db->qstr(time()).",
            is_redeemed         =". $db->qstr(1).",            
            active              =". $db->qstr(0)."
            WHERE giftcert_id   =". $db->qstr($giftcert_id);
    
    if (!$rs = $db->execute($sql)) {
        force_error_page($_GET['page'], 'database', __FILE__, __FUNCTION__, $db->ErrorMsg(), $sql, _gettext("Failed to update the Gift Certificate as redeemed."));
        exit;
    } else {
        $customer_details = get_customer_details($db, get_giftcert_details($db, $giftcert_id, 'customer_id'));
        
        // Log activity
        write_record_to_activity_log(_gettext("Gift Certificate").' '.$giftcert_id.' '._gettext("was redeemed by").' '.$customer_details['display_name'].'.', null, $customer_details['customer_id']);
    }
}
