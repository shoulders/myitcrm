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
 * Other Functions - All other public functions not covered above
 * Login
 * Reset Password
 */

defined('_QWEXEC') or die;

class User extends Components {

    /** Insert Functions **/

    #####################################
    #    Insert new user                #
    #####################################

    public function insertRecord($qform) {

        $sql = "INSERT INTO ".PRFX."user_records SET
                client_id           =". $this->app->db->qstr( $qform['client_id']                            ).", 
                username            =". $this->app->db->qstr( $qform['username']                             ).",
                password            =". $this->app->db->qstr( \Joomla\CMS\User\UserHelper::hashPassword($qform['password'])  ).",
                email               =". $this->app->db->qstr( $qform['email']                                ).",
                usergroup           =". $this->app->db->qstr( $qform['usergroup']                            ).",
                active              =". $this->app->db->qstr( $qform['active']                               ).",
                register_date       =". $this->app->db->qstr( $this->app->system->general->mysqlDatetime()  ).",  
                last_active         =". $this->app->db->qstr( $this->app->system->general->mysql_datetime()  ).",
                last_reset_time     =". $this->app->db->qstr( $this->app->system->general->mysql_datetime()  ).",
                require_reset       =". $this->app->db->qstr( $qform['require_reset']                        ).",
                is_employee         =". $this->app->db->qstr( $qform['is_employee']                          ).", 
                first_name          =". $this->app->db->qstr( $qform['first_name']                           ).",
                last_name           =". $this->app->db->qstr( $qform['last_name']                            ).",
                work_primary_phone  =". $this->app->db->qstr( $qform['work_primary_phone']                   ).",
                work_mobile_phone   =". $this->app->db->qstr( $qform['work_mobile_phone']                    ).",
                work_fax            =". $this->app->db->qstr( $qform['work_fax']                             ).",                    
                home_primary_phone  =". $this->app->db->qstr( $qform['home_primary_phone']                   ).",
                home_mobile_phone   =". $this->app->db->qstr( $qform['home_mobile_phone']                    ).",
                home_email          =". $this->app->db->qstr( $qform['home_email']                           ).",
                home_address        =". $this->app->db->qstr( $qform['home_address']                         ).",
                home_city           =". $this->app->db->qstr( $qform['home_city']                            ).",  
                home_state          =". $this->app->db->qstr( $qform['home_state']                           ).",
                home_zip            =". $this->app->db->qstr( $qform['home_zip']                             ).",
                home_country        =". $this->app->db->qstr( $qform['home_country']                         ).", 
                based               =". $this->app->db->qstr( $qform['based']                                ).",  
                note                =". $this->app->db->qstr( $qform['note']                                 );                     

        if(!$this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        // Get user_id
        $user_id = $this->app->db->Insert_ID();

        // Update last active record        
        $this->app->components->client->updateLastActive($qform['client_id']);        

        // Log activity
        if($qform['client_id']) {
            $user_type = _gettext("Client");
        } else {
            $user_type = _gettext("Employee");
        }        
        $record = _gettext("User Account").' '.$user_id.' ('.$user_type.') '.'for'.' '.$this->getRecord($user_id, 'display_name').' '._gettext("created").'.';
        $this->app->system->general->writeRecordToActivityLog($record, $user_id);

        return $user_id; 

    }
    
    /** Get Functions **/

    #####################################
    #    Display Users                  #  // 'display_name' and 'full_name' are the same. This is usability issues.
    #####################################

    public function getRecords($order_by, $direction, $records_per_page = 0, $use_pages = false, $page_no = null, $search_category = 'user_id', $search_term = null, $usergroup = null, $usertype = null, $status = null) {

        // This is needed because of how page numbering works
        $page_no = $page_no ?: 1;      

        // Default Action
        $whereTheseRecords = "WHERE ".PRFX."user_records.user_id\n";
        $havingTheseRecords = '';

        // Search category (display) and search term
        if($search_category == 'display_name') {$havingTheseRecords .= " HAVING display_name LIKE ".$this->app->db->qstr('%'.$search_term.'%');}

        // Restrict results by search category and search term
        elseif($search_term) {$whereTheseRecords .= " AND ".PRFX."user_records.$search_category LIKE ".$this->app->db->qstr('%'.$search_term.'%');}

        // Restrict results by usergroup
        if($usergroup) {$whereTheseRecords .= " AND ".PRFX."user_records.usergroup =".$this->app->db->qstr($usergroup);}

        // Restrict results by user type
        if($usertype && !$usergroup) {

            if($usertype == 'client') { 
                $whereTheseRecords .= " AND ".PRFX."user_records.usergroup =".$this->app->db->qstr(7);}            

            if($usertype == 'employee') {

                $whereTheseRecords .= " AND ".PRFX."user_records.usergroup =".$this->app->db->qstr(1);
                $whereTheseRecords .= " OR ".PRFX."user_records.usergroup =".$this->app->db->qstr(2);
                $whereTheseRecords .= " OR ".PRFX."user_records.usergroup =".$this->app->db->qstr(3);
                $whereTheseRecords .= " OR ".PRFX."user_records.usergroup =".$this->app->db->qstr(4);
                $whereTheseRecords .= " OR ".PRFX."user_records.usergroup =".$this->app->db->qstr(5);
                $whereTheseRecords .= " OR ".PRFX."user_records.usergroup =".$this->app->db->qstr(6);

            }

        }

        // Restrict by Status (is null because using boolean/integer)
        if(!is_null($status)) {$whereTheseRecords .= " AND ".PRFX."user_records.active=".$this->app->db->qstr($status);}  

        // The SQL code
        $sql = "SELECT
                ".PRFX."user_records.*,
                CONCAT(".PRFX."user_records.first_name, ' ', ".PRFX."user_records.last_name) AS display_name,
                CONCAT(".PRFX."user_records.first_name, ' ', ".PRFX."user_records.last_name) AS full_name,
                ".PRFX."user_usergroups.display_name

                FROM ".PRFX."user_records
                LEFT JOIN ".PRFX."user_usergroups ON (".PRFX."user_records.usergroup = ".PRFX."user_usergroups.usergroup_id)
                ".$whereTheseRecords."
                GROUP BY ".PRFX."user_records.".$order_by."
                ".$havingTheseRecords."
                ORDER BY ".PRFX."user_records.".$order_by."
                ".$direction; 

       // Get the total number of records in the database for the given search        
        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}       
        $total_results = $rs->RecordCount();        
            
        // Restrict by pages
        if($use_pages) {

            // Get Start Record
            $start_record = (($page_no * $records_per_page) - $records_per_page);

            // Figure out the total number of pages. Always round up using ceil()
            $total_pages = ceil($total_results / $records_per_page);            

            // Assign the Previous page        
            $previous_page_no = ($page_no - 1);                    

            // Assign the next page        
            if($page_no == $total_pages) {$next_page_no = 0;}
            elseif($page_no < $total_pages) {$next_page_no = ($page_no + 1);}
            else {$next_page_no = $total_pages;}            
            
            // Only return the given page's records
            $sql .= " LIMIT ".$start_record.", ".$records_per_page;

        // Restrict by number of records   
        } elseif($records_per_page) {

            // Only return the first x number of records
            $sql .= " LIMIT 0, ".$records_per_page;

            // Show restricted records message if required
            $restricted_records = $total_results > $records_per_page ? true : false;

        }       

        // Get the records        
        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        // Return the data        
        return array(
                'records' => $rs->GetArray(),
                'total_results' => $total_results,
                'total_pages' => $total_pages ?? 1,             // This make the drop down menu look correct on search tpl with use_pages off
                'page_no' => $page_no,
                'previous_page_no' => $previous_page_no ?? null,
                'next_page_no' => $next_page_no ?? null,                    
                'restricted_records' => $restricted_records ?? false,
                );

    } 

    #####################################
    #     Get User Details              #  // 'display_name' and 'full_name' are the same. This is usability issues.
    #####################################

    public function getRecord($user_id = null, $item = null) {

        // This allows for workorder:status to work
        if(!$user_id){
            return;        
        }

        $sql = "SELECT * FROM ".PRFX."user_records WHERE user_id =".$this->app->db->qstr($user_id);

        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        if($item === null) {

            $results = $rs->GetRowAssoc();

            // Add these dynamically created fields           
            $results['display_name'] = $results['first_name'].' '.$results['last_name'];
            $results['full_name'] = $results['first_name'].' '.$results['last_name'];

            return $results;

        } else {

            // Return the dynamically created 'display_name'
            if($item == 'display_name') {
                $results = $rs->GetRowAssoc();
                return $results['first_name'].' '.$results['last_name'];
            }

            // Return the dynamically created 'full_name'
            if($item == 'full_name') {
                $results = $rs->GetRowAssoc();
                return $results['first_name'].' '.$results['last_name'];
            }

            return $rs->fields[$item];   

        } 
        
    }

    #########################################
    # Get User ID by username               # // moved from core
    #########################################

    /* 
     * Not used in core anywhere
     * it was used for getting user specific stats in theme_header_block.php
     * $user->login_user_id is not set via the auth session
     * i will leave this here just for now
     * no longer needed as I stored the id in the session
     * 
     */

    public function getIdByUsername($username) {

        $sql = "SELECT user_id FROM ".PRFX."user_records WHERE username =".$this->app->db->qstr($username);

        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        return $rs->fields['user_id'];
        
    }

    #########################################
    # Get User ID by username               #
    #########################################

    public function getIdByEmail($email) {

        $sql = "SELECT user_id FROM ".PRFX."user_records WHERE email =".$this->app->db->qstr($email);

        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        $result_count = $rs->RecordCount();

        if($result_count != 1) {

            return false;

        } else {

            return $rs->fields['user_id'];

        }  

    }

    ##################################
    # Get the usergroups             #
    ##################################

    public function getUsergroups($user_type = null) {

        $sql = "SELECT * FROM ".PRFX."user_usergroups";

        // Filter the results by user type client/employee
        if($user_type === 'employees') {$sql .= " WHERE user_type='1'";}
        if($user_type === 'clients')   {$sql .= " WHERE user_type='2'";}    
        if($user_type === 'other')     {$sql .= " WHERE user_type='3'";}

        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        return $rs->GetArray();

    }

    ##################################################
    # Get all active users display name and ID       #
    ##################################################

    public function getActiveUsers($user_type = null) {  

        $sql = "SELECT        
                user_id,
                CONCAT(first_name, ' ', last_name) AS display_name

                FROM ".PRFX."user_records
                WHERE active='1'";

        // Filter the results by user type client/employee
        if($user_type === 'clients')   {$sql .= " AND is_employee='0'";}
        if($user_type === 'employees') {$sql .= " AND is_employee='1'";}

        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        return $rs->GetArray();

    }

    ##################################################
    # Get all active users display name and ID       #
    ##################################################

    public function getLocations() {  

        $sql = "SELECT * FROM ".PRFX."user_locations";

        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}   

        return $rs->GetArray();    

    }

    /** Update Functions **/

    #########################
    #   Update Employee     #
    #########################

    public function updateRecord($qform) {

        $sql = "UPDATE ".PRFX."user_records SET        
                username            =". $this->app->db->qstr( $qform['username']                             ).",
                email               =". $this->app->db->qstr( $qform['email']                                ).",
                usergroup           =". $this->app->db->qstr( $qform['usergroup']                            ).",
                active              =". $this->app->db->qstr( $qform['active']                               ).",                    
                require_reset       =". $this->app->db->qstr( $qform['require_reset']                        ).",               
                first_name          =". $this->app->db->qstr( $qform['first_name']                           ).",
                last_name           =". $this->app->db->qstr( $qform['last_name']                            ).",
                work_primary_phone  =". $this->app->db->qstr( $qform['work_primary_phone']                   ).",
                work_mobile_phone   =". $this->app->db->qstr( $qform['work_mobile_phone']                    ).",
                work_fax            =". $this->app->db->qstr( $qform['work_fax']                             ).",                    
                home_primary_phone  =". $this->app->db->qstr( $qform['home_primary_phone']                   ).",
                home_mobile_phone   =". $this->app->db->qstr( $qform['home_mobile_phone']                    ).",
                home_email          =". $this->app->db->qstr( $qform['home_email']                           ).",
                home_address        =". $this->app->db->qstr( $qform['home_address']                         ).",
                home_city           =". $this->app->db->qstr( $qform['home_city']                            ).",  
                home_state          =". $this->app->db->qstr( $qform['home_state']                           ).",
                home_zip            =". $this->app->db->qstr( $qform['home_zip']                             ).",
                home_country        =". $this->app->db->qstr( $qform['home_country']                         ).",
                based               =". $this->app->db->qstr( $qform['based']                                ).",  
                note                =". $this->app->db->qstr( $qform['note']                                 )."
                WHERE user_id= ".$this->app->db->qstr($qform['user_id']);

        if(!$this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        // Reset user password if required
        if($qform['password']) {
            $this->resetPassword($qform['user_id'], $qform['password']);
        }

        // Update last active record
        $this->updateLastActive($qform['user_id']);
        $this->app->components->client->updateLastActive($this->getRecord($qform['user_id'], 'client_id'));        

        // Log activity        
        $record = _gettext("User Account").' '.$qform['user_id'].' ('.$this->getRecord($qform['user_id'], 'display_name').') '._gettext("updated.");
        $this->app->system->general->writeRecordToActivityLog($record, $qform['user_id']);

        return true; 

    }

    #######################################
    #    Update User's Last Active Date   #
    #######################################

    public function updateLastActive($user_id = null) {

        // compensate for some operations not having a user_id
        if(!$user_id) { return; }        

        $sql = "UPDATE ".PRFX."user_records SET last_active=".$this->app->db->qstr( $this->app->system->general->mysqlDatetime() )." WHERE user_id=".$this->app->db->qstr($user_id);

        if(!$this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

    }
    
    /** Close Functions **/

    /** Delete Functions **/

    #####################################
    #    Delete User                    #
    #####################################

    public function deleteRecord($user_id) {

        // get user details before deleting
        $user_details = $this->getRecord($user_id);

        // Make sure the client can be deleted 
        if(!$this->checkRecordAllowsDelete($user_id)) {        
            return false;
        }

        /* we can now delete the user */

        // Delete User account
        $sql = "DELETE FROM ".PRFX."user_records WHERE user_id=".$this->app->db->qstr($user_id);    
        if(!$this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        // Log activity        
        $record = _gettext("User Account").' '.$user_id.' ('.$user_details['display_name'].') '._gettext("deleted.");
        $this->app->system->general->writeRecordToActivityLog($record, $user_id);

        // Update last active record
        $this->app->components->client->updateLastActive($user_details['client_id']);    

        return true;

    }
    
    /** Check Functions **/
    
    #################################################
    #    Check if username already exists           #
    #################################################

    public function checkUsernameExists($username, $current_username = null) {

        // This prevents self-checking of the current username of the record being edited
        if ($current_username != null && $username === $current_username) {return false;}

        $sql = "SELECT username FROM ".PRFX."user_records WHERE username =". $this->app->db->qstr($username);

        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        $result_count = $rs->RecordCount();

        if($result_count >= 1) {

            $this->app->system->variables->systemMessagesWrite('danger', _gettext("The Username")." '".$username."' "._gettext("already exists! Please use a different one."));

            return true;

        } else {

            return false;

        }        

    }

    ######################################################
    #  Check if an email address has already been used   #
    ######################################################

    public function checkEmailExists($email, $current_email = null) {

        // This prevents self-checking of the current username of the record being edited
        if ($current_email != null && $email === $current_email) {return false;}

        $sql = "SELECT email FROM ".PRFX."user_records WHERE email =". $this->app->db->qstr($email);

        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        $result_count = $rs->RecordCount();

        if($result_count >= 1) {

            $this->app->system->variables->systemMessagesWrite('danger', _gettext("The email address has already been used. Please use a different one."));

            return true;

        } else {

            return false;

        } 

    }

    #################################################
    #    Check if user already has login            #
    #################################################

    public function checkClientLoginExists($client_id) {

        $sql = "SELECT user_id FROM ".PRFX."user_records WHERE client_id =". $this->app->db->qstr($client_id);

        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        $result_count = $rs->RecordCount();

        if($result_count >= 1) {

            $this->app->system->variables->systemMessagesWrite('danger', _gettext("The client already has a login."));           

            return true;

        } else {

            return false;

        }    

    }    

    ###############################################################
    #   Check to see if the user can be deleted                   #
    ###############################################################

    public function checkRecordAllowsDelete($user_id) {

        $state_flag = true;

        // Get the user details
        $user_details = $this->getRecord($user_id);

        // User cannot delete their own account
        if($user_id == $this->app->user->login_user_id) {
            $this->app->system->variables->systemMessagesWrite('danger', _gettext("You can not delete your own account."));        
            $state_flag = false;
        }

        // Cannot delete this account if it is the last administrator account
        if($user_details['usergroup'] == '1') {

            $sql = "SELECT count(*) as count FROM ".PRFX."user_records WHERE usergroup = '1'";    
            if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}  
            if($rs->fields['count'] <= 1 ) {
                $this->app->system->variables->systemMessagesWrite('danger', _gettext("You can not delete the last administrator user account."));        
                $state_flag = false;
            }        
        }

        // Check if user has created any workorders
        $sql = "SELECT count(*) as count FROM ".PRFX."workorder_records WHERE created_by=".$this->app->db->qstr($user_id);    
        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}  
        if($rs->fields['count'] > 0 ) {
            $this->app->system->variables->systemMessagesWrite('danger', _gettext("You can not delete a user who has created work orders."));        
            $state_flag = false;
        }

        // Check if user has any assigned workorders
        $sql = "SELECT count(*) as count FROM ".PRFX."workorder_records WHERE employee_id=".$this->app->db->qstr($user_id);    
        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}  
        if($rs->fields['count'] > 0 ) {
            $this->app->system->variables->systemMessagesWrite('danger', _gettext("You can not delete a user who has assigned work orders."));
            $state_flag = false;
        }

        // Check if user has any invoices
        $sql = "SELECT count(*) as count FROM ".PRFX."invoice_records WHERE employee_id=".$this->app->db->qstr($user_id);    
        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}    
        if($rs->fields['count'] > 0 ) {
            $this->app->system->variables->systemMessagesWrite('danger', _gettext("You can not delete a user who has invoices."));
            $state_flag = false;
        }    

        // Check if user is assigned to any Vouchers
        $sql = "SELECT count(*) as count FROM ".PRFX."voucher_records WHERE employee_id=".$this->app->db->qstr($user_id);
        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}
        if($rs->fields['count'] > 0 ) {
            $this->app->system->variables->systemMessagesWrite('danger', _gettext("You can not delete a user who has Vouchers."));
            $state_flag = false;
        }

        return $state_flag;

    }    

    /** Other Functions **/

    ##############################################
    #   Build an active employee <option> list   #  // Not currently used keep for reference
    ##############################################

    /*
     * This utilises the ADODB PHP Framework for building a <option> list from the supplied data set.
     * 
     * Build <option></option> list for a <form></form> to select employee for 'Assign To' feature
     * GetMenu2('assign_employee_val, null, false') will turn off the blank option
     * GetMenu2('dataset values', 'default option', 'blank 1st record' )
     * 
     * The assigned employee is the default option selected
     * 
     * I will use Smarty for this feature
     * 
     */

    public function buildActiveEmployeeFormOptionList($assigned_user_id) {

        // select all employees and return their display name and ID as an array
        $sql = "SELECT
                CONCAT(".PRFX."first_name, ' ', ".PRFX."last_name) AS display_name,
                user_id

                FROM ".PRFX."user_records
                WHERE active=1 AND is_employee=1";

        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        // Get ADODB to build the form using the loaded dataset
        return $rs->GetMenu2('assign_user', $assigned_user_id, false);

    }

    #####################################
    #    Reset a user's password        #    
    #####################################

    public function resetPassword($user_id, $password = null) { 

        // if no password supplied generate a random one
        if($password == null) { $password = \Joomla\CMS\User\UserHelper::genRandomPassword(16); }

        $sql = "UPDATE ".PRFX."user_records SET
                password        =". $this->app->db->qstr( \Joomla\CMS\User\UserHelper::hashPassword($password) ).",
                require_reset   =". $this->app->db->qstr( 0                                    ).",   
                last_reset_time =". $this->app->db->qstr( $this->app->system->general->mysqlDatetime()                     ).",
                reset_count     =". $this->app->db->qstr( 0                                    )."
                WHERE user_id   =". $this->app->db->qstr( $user_id                             );

        if(!$this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        // Log activity        
        $record = _gettext("User Account").' '.$user_id.' ('.$this->getRecord($user_id, 'display_name').') '._gettext("password has been reset.");
        $this->app->system->general->writeRecordToActivityLog($record, $user_id);

        // Update last active record
        $this->updateLastActive($user_id);
        $this->app->components->client->updateLastActive($this->getRecord($user_id, 'client_id'));

        return;         

    }


    /* Login */

    ####################################
    #  Login authentication public function   #
    ####################################

    public function login($qform, $credentials, $options = array())
    {   
        $this->smarty = \Factory::getSmarty();   

        // If username or password is missing
        if (!isset($credentials['username']) || $credentials['username'] == '' || !isset($credentials['password']) || $credentials['password'] == '') {

            // Set error message
            $this->app->system->variables->systemMessagesWrite('danger', _gettext("Username or Password Missing."));

            return false;

        } 

        // Does the account require the password to be reset, if so force it
        if($this->getRecord($this->getIdByUsername($qform['login_username']), 'require_reset')) {

            // Set error message
            $this->app->system->variables->systemMessagesWrite('danger', _gettext("You must reset your password before you are allowed to login."));

            return false;

        }

        // If user is blocked - QFramework returns True for a blocked user, but does blocks it.
        if($this->getRecord($this->getIdByUsername($qform['login_username']), 'active') === '0') {  

            // Set error message
            $this->app->system->variables->systemMessagesWrite('danger', _gettext("Login denied! Your account has either been blocked or you have not activated it yet."));

            // Log activity       
            $this->app->system->general->writeRecordToActivityLog(_gettext("Login denied for").' '.$qform['login_username'].'.');

            return false;

        }

        if(\Factory::getAuth()->login($credentials, $options)) {

            /* Login Successful */

            // Wipe the current user details (probably guest) is this needed?
            //$this->app->user = null; \Factory::$user = null;
            
            // Get the new login details
            $user = \Factory::getUser();       

            // Log activity       
            $record = _gettext("Login successful for").' '.$user->login_username.'.';
            $this->app->system->general->writeRecordToActivityLog($record, $user->login_user_id);        

            // Update last active record        
            $this->app->components->client->updateLastActive($user->login_client_id);        

            // set success message to survice the login event
            $this->app->system->variables->systemMessagesWrite('success', _gettext("Login successful."));

            return true;

        } else {

            /* Login failed */

            // Log activity       
            $this->app->system->general->writeRecordToActivityLog(_gettext("Login unsuccessful for").' '.$credentials['username'].'.');

            $this->app->system->variables->systemMessagesWrite('danger', _gettext("Login Failed. Check you username and password."));

            return false;

        }
    }

    ###########################
    #  Login authentication   #
    ###########################

    public function logout($silent = null)        
    {   
        // Build logout message (while user details exist)
        $record = _gettext("Logout successful for").' '.$this->app->user->login_username.'.';

        // Logout
        \Factory::getAuth()->logout();    

        // Log activity       
        $this->app->system->general->writeRecordToActivityLog($record, $this->app->user->login_user_id);

        // Update last active record 
        $this->updateLastActive($this->app->user->login_user_id);
        $this->app->components->client->updateLastActive($this->app->user->login_client_id);

        // Action after logout
        if($silent) {

            // No message or redirect

            return;        

        } else {

            // Reload Homepage with message (default)

            // only $_GET will work because the session store is destroyed (this is good behaviour)
            //$this->app->system->page->forcePage('index.php', null, 'msg_success='._gettext("Logout successful."), 'get');
            $this->app->system->page->forcePage('index.php');

        }

    } 

    ####################################
    #  Logout all online users         #  // This terminates sessions fo those currently connected (Logged in and Guests). This does not handle users with 'remember me' enabled. 
    ####################################

    public function logoutAllUsers($except_me = false) {

        //truncate something like `#__user_keys` destroys the remember_me link, the session kills the imediate session

        // Logout all users
        if(!$except_me) {

            // Sessions
            $sql = "TRUNCATE ".PRFX."session";
            if(!$this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

            // Remember Me
            $sql = "TRUNCATE ".PRFX."user_keys";
            if(!$this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        // Delete all sessions except the currently logged in user 
        } else {

            $sql = "DELETE FROM ".PRFX."session WHERE userid <> ".$this->app->db->qstr($this->app->user->login_user_id);
            if(!$this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

            $sql = "DELETE FROM ".PRFX."user_keys WHERE userid <> ".$this->app->db->qstr($this->app->user->login_user_id);
            if(!$this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        }

        return;

    }

    /* Reset Password */

    #####################################
    #    Verify submitted reCAPTCHA     #    
    #####################################

    public function authenticateRecaptcha($recaptcha_secret_key, $recaptcha_response) {

        // Load ReCaptcha library       
        $recaptcha = new \ReCaptcha\ReCaptcha($recaptcha_secret_key);

        // Get response from Google
        $response = $recaptcha->verify($recaptcha_response, $_SERVER['REMOTE_ADDR']);

        //  and if successfull authenticate
        if ($response->isSuccess()) {

            // Success
            return true;

        } else {

            /* If it's not successful, then one or more error codes will be returned.      
            $error_msg .= '<h2>Something went wrong</h2>';
            $error_msg .= '<p>The following error was returned:';
                foreach ($response->getErrorCodes() as $error_code) {
                    $error_msg .= '<kbd>'.$error_code.'</kbd> ';
                }
            $error_msg .= '</p>';
            $error_msg .= '<p>Check the error code reference at <kbd><a href="https://developers.google.com/recaptcha/docs/verify#error-code-reference">https://developers.google.com/recaptcha/docs/verify#error-code-reference</a></kbd>.';
            $error_msg .= '<p><strong>Note:</strong> Error code <kbd>missing-input-response</kbd> may mean the user just didn\'t complete the reCAPTCHA.</p>';
            $error_msg .= '<p><a href="/">Try again</a></p>';*/        

            $this->app->system->variables->systemMessagesWrite('danger', _gettext("Google reCAPTCHA Verification Failed."));
            return false;

        }  

    }

    ######################################################################################
    #    Validate that the email submitted belongs to a valid account and can be reset   #    
    ######################################################################################

    public function validateResetEmail($email) {

        // get the user_id if the user exists
        if(!$user_id = $this->getIdByEmail($email)) {
            return false;        
        }

        // is the user active
        if(!$this->getRecord($user_id, 'active')) {
            return false;
        }

        return $user_id;

    }

    #####################################
    #    Build and send a reset email   #    
    #####################################

    public function sendResetEmail($user_id) {

        // Get recipient email
        $recipient_email = $this->getRecord($user_id, 'email');

        // Set subject  
        $subject = _gettext("Your QWcrm password reset request");    

        // Create Token
        $token = $this->createResetToken($user_id);

        /* Build Email body
        $body = '';

        $body .= _gettext("Hello").','."\r\n\r\n";

        $body .= _gettext("A request has been made to reset your QWcrm account password.").' ';
        $body .= _gettext("To reset your password, you will need to submit this verification code in order to verify that the request was legitimate.")."\r\n\r\n";

        $body .= _gettext("The verification code is").' '.$token."\r\n\r\n";

        $body .= _gettext("Select the URL below and proceed with resetting your password.")."\r\n\r\n";

        $body .= QWCRM_PROTOCOL. QWCRM_DOMAIN . QWCRM_BASE_PATH."index.php?component=user&page_tpl=reset&token=".$token."\r\n\r\n";

        $body .= _gettext("Thank you.");*/


        // Build Email body
        $body = '';

        $body .= '<p>'._gettext("Hello").','.'</p>';

        $body .= '<p>'._gettext("A request has been made to reset your QWcrm account password.").' ';
        $body .= _gettext("To reset your password, you will need to submit this verification code in order to verify that the request was legitimate.").'</p>';

        $body .= '<p>'._gettext("The verification code is").' '.$token.'</p>';

        $body .= '<p>'._gettext("Select the URL below and proceed with resetting your password.").'</p>';

        $body .= '<p>'. QWCRM_PROTOCOL . QWCRM_DOMAIN . QWCRM_BASE_PATH ."index.php?component=user&page_tpl=reset&token=".$token.'</p>';  

        $body .= '<p>'._gettext("Thank you.").'</p>';    

        // Send Reset Email (no onscreen notifications to prevent headers already sent error)
        $this->app->system->email->send($recipient_email, $subject, $body, null, null, null, null, null, null, true);

        // Log activity        
        $record = _gettext("User Account").' '.$user_id.' ('.$this->getRecord($user_id, 'display_name').') '._gettext("reset email has been sent.");
        $this->app->system->general->writeRecordToActivityLog($record, $user_id);

        return;

    }

    ###################################################################################
    #   Set time limited reset code to allow new passwords to be submitted securely   #
    ###################################################################################

    public function authorisePasswordReset($token) {

        $reset_code = \Joomla\CMS\User\UserHelper::genRandomPassword(64);   // 64 character token
        $reset_code_expiry_time = time() + (60 * 5);                        // sets a 5 minute expiry time

        $sql = "UPDATE ".PRFX."user_reset
                SET
                reset_code              =". $this->app->db->qstr( $reset_code              ).",
                reset_code_expiry_time  =". $this->app->db->qstr( $reset_code_expiry_time  )."            
                WHERE token             =". $this->app->db->qstr( $token                   );

        if(!$this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        return $reset_code;

    }

    #####################################
    #    create a reset user token      #    
    #####################################

    public function createResetToken($user_id) {

        // check for previous tokens for this user and delete them
        $sql = "SELECT * FROM ".PRFX."user_reset WHERE user_id=".$this->app->db->qstr($user_id);

        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}       
        
        $result_count = $rs->RecordCount();       
         
        // Delete any reset tokens for this user
        if($result_count >= 1) {

            $this->deleteResetCode($user_id);

        }

        // Insert a new token
        $expiry_time = time() + (60 * 15);              // 15 minute expiry time
        $token = \Joomla\CMS\User\UserHelper::genRandomPassword(64);    // 64 character token

        $sql = "INSERT INTO ".PRFX."user_reset SET              
                user_id         =". $this->app->db->qstr( $user_id     ).", 
                expiry_time     =". $this->app->db->qstr( $expiry_time ).",   
                token           =". $this->app->db->qstr( $token       );                     

        if(!$this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        // Return the token
        return $token;    

    }

    #########################################
    # Get User ID by reset code             #
    #########################################

    public function getIdByResetCode($reset_code) {

        $sql = "SELECT user_id FROM ".PRFX."user_reset WHERE reset_code =".$this->app->db->qstr($reset_code);

        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        return $rs->fields['user_id'];

    }

    ##############################################
    #    validate the reset token can be used    #    
    ##############################################

    public function validateResetToken($token) {

        // check for previous tokens for this user and delete them
        $sql = "SELECT * FROM ".PRFX."user_reset WHERE token =".$this->app->db->qstr($token);

        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        // Check there is only 1 record
        if($rs->RecordCount() != 1) {
            $this->app->system->variables->systemMessagesWrite('danger', _gettext("The reset token does not exist."));
            return false;
        }

        // check if user is blocked        
        if(!$this->getRecord($rs->fields['user_id'], 'active')){
            $this->app->system->variables->systemMessagesWrite('danger', _gettext("The user is blocked."));
            return false;
        }

        // Check not expired
        if($rs->fields['expiry_time'] < time()){
            $this->app->system->variables->systemMessagesWrite('danger', _gettext("The reset token has expired."));
            return false;
        }

        // All checked passed
        $this->app->system->variables->systemMessagesWrite('success', _gettext("Token accepted."));
        return true;     

    }

    #########################################################
    #   validate reset code - submitted with password form  #
    #########################################################

    public function validateResetCode($reset_code) {

       // Check for previous tokens for this user and delete them
        $sql = "SELECT * FROM ".PRFX."user_reset WHERE reset_code =".$this->app->db->qstr($reset_code);

        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        // Check there is only 1 record
        if($rs->RecordCount() != 1) {            
            $this->app->system->variables->systemMessagesWrite('danger', 'The reset code does not exist.');
            return false;
        }

        // Check not expired
        if($rs->fields['reset_code_expiry_time'] < time()){
            $this->app->system->variables->systemMessagesWrite('danger', 'The reset code has expired.');
            return false;
        }

        // All checked passed
        $this->app->system->variables->systemMessagesWrite('success', _gettext("Reset code accepted."));        
        return true; 

    }

    ##########################################
    #    Delete user reset codes             #
    ##########################################

    public function deleteResetCode($user_id) {  

        $sql = "DELETE FROM ".PRFX."user_reset WHERE user_id = ".$this->app->db->qstr($user_id);

        if(!$this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

    }

    ##########################################
    #    Delete all expired reset codes      #
    ##########################################

    public function deleteExpiredResetCodes() {   

        $sql = "DELETE FROM ".PRFX."user_reset WHERE expiry_time < ".$this->app->db->qstr( time() );

        if(!$this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

    }


    #####################################
    #    Update users reset count       #    
    #####################################

     public function updateResetCount($user_id) {

        $sql = "UPDATE ".PRFX."user_records SET       
                reset_count     = reset_count + 1
                WHERE user_id   =". $this->app->db->qstr($user_id);

        if(!$this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

     }
     
     
    #####################################   // Not Currently used
    #    Reset all user's passwords     #   // used for migrations or security
    #####################################

    public function resetAllPasswords() { 

        $sql = "SELECT user_id FROM ".PRFX."user_records";

        if(!$rs = $this->app->db->execute($sql)) {$this->app->system->page->forceErrorPage('database', __FILE__, __FUNCTION__, $this->app->db->ErrorMsg(), $sql);}

        // Loop through all users
        while(!$rs->EOF) { 

            // Reset User's password
            $this->resetPassword($rs->fields['user_id']);

            // Advance the INSERT loop to the next record            
            $rs->MoveNext();            

        }

        // Log activity        
        $this->app->system->general->writeRecordToActivityLog(_gettext("All User Account passwords have been reset."));

        return;

    }

}
