<?php

// Load the Supplier classes
require_once('include.php');

// Load PHP Language Translations
$langvals = gateway_xml2php('supplier');

$last_record_id = last_record_id_lookup($db);
$new_record_id = $last_record_id + 1;

// If details submitted insert record, if non submitted load new.tpl and populate values
    if((isset($VAR['submit'])) || (isset($VAR['submitandnew']))) {
        
                    if($run != insert_new_supplier($db,$VAR)){
                            $smarty->assign('error_msg', 'Falied to insert Supplier');
                            $BuildPage .= $smarty->fetch('core'.SEP.'error.tpl');
                            echo "supplier insert error";

                            } else {

                                   if (isset($VAR['submitandnew'])){

                                                // Submit New Supplier and reload page
                                                force_page('supplier', 'new');
                                                exit;

                                                }

                                                        else {

                                                            // Submit and load Supplier View Details
                                                            force_page('supplier', 'details', 'supplier_id='.$new_record_id.'&page_title='.$langvals['supplier_details_title']);
                                                            exit;

                                                }
                                 }

} else {
            
            $smarty->assign('new_record_id', $new_record_id);
            $smarty->assign('tax_rate', tax_rate($db)); // this function needs to be put in include.php frome xpense/refund and rename company_tax_rate if not amalgamangted
            $BuildPage .= $smarty->fetch('supplier'.SEP.'new.tpl');

       }