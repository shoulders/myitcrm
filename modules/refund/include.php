<?php

##########################################
#      MyITCRM TAX Rate Call             #
##########################################

function tax_rate($db){

$q = 'SELECT * FROM '.PRFX.'SETUP';
    if(!$rs = $db->execute($q)) {
        force_page('core', 'error&error_msg=MySQL Error: '.$db->ErrorMsg().'&menu=1&type=database');
        exit;
    } else {
        $tax_rate = $rs->fields['INVOICE_TAX'];
                return $tax_rate;
        }
}



##########################################
#      Last Record Look Up               #
##########################################

function last_record_id_lookup($db){

$q = 'SELECT * FROM '.PRFX.'TABLE_REFUND ORDER BY REFUND_ID DESC LIMIT 1';
    if(!$rs = $db->execute($q)) {
        force_page('core', 'error&error_msg=MySQL Error: '.$db->ErrorMsg().'&menu=1&type=database');
        exit;
    } else {
        $last_record_id = $rs->fields['REFUND_ID'];
                return $last_record_id;
        }
}

##########################################
#      Insert New Record                 #
##########################################

function insert_new_refund($db,$VAR) {

    $checked_date = date_to_timestamp($VAR['refundDate']);

//Remove Extra Slashes caused by Magic Quotes
$refundNotes_string = $VAR['refundNotes'];
$refundNotes_string = stripslashes($refundNotes_string);

$refundItems_string = $VAR['refundItems'];
$refundItems_string = stripslashes($refundItems_string);

    $sql = "INSERT INTO ".PRFX."TABLE_REFUND SET

            REFUND_ID            = ". $db->qstr( $VAR['refund_id']           ).",
            REFUND_PAYEE            = ". $db->qstr( $VAR['refundPayee']        ).",
            REFUND_DATE            = ". $db->qstr( $checked_date               ).",
            REFUND_TYPE            = ". $db->qstr( $VAR['refundType']         ).",
            REFUND_PAYMENT_METHOD          = ". $db->qstr( $VAR['refundPaymentMethod']).",
            REFUND_NET_AMOUNT        = ". $db->qstr( $VAR['refundNetAmount']    ).",
                        REFUND_TAX_RATE                = ". $db->qstr( $VAR['refundTaxRate']      ).",
                        REFUND_TAX_AMOUNT              = ". $db->qstr( $VAR['refundTaxAmount']    ).",
                        REFUND_GROSS_AMOUNT            = ". $db->qstr( $VAR['refundGrossAmount']  ).",
                        REFUND_NOTES                   = ". $db->qstr( $refundNotes_string        ).",
                        REFUND_ITEMS                   = ". $db->qstr( $refundItems_string        );

    if(!$result = $db->Execute($sql)) {
        force_page('core', 'error&error_msg=MySQL Error: '.$db->ErrorMsg().'&menu=1&type=database');
        exit;
    } /*else {
        return true;
    } */
    
} 

#####################################
#     Edit - Load Record            #
#####################################

function edit_info($db, $refund_id){
    $sql = "SELECT * FROM ".PRFX."TABLE_REFUND WHERE REFUND_ID=".$db->qstr($refund_id);
    
    if(!$result = $db->Execute($sql)) {
        force_page('core', 'error&error_msg=MySQL Error: '.$db->ErrorMsg().'&menu=1&type=database');
        exit;
    } else {
        $row = $result->FetchRow();
        return $row;
    }
}

#####################################
#     Update Record              #
#####################################

function update_refund($db,$VAR) {

        $checked_date = date_to_timestamp($VAR['refundDate']);

//Remove Extra Slashes caused by Magic Quotes
$refundNotes_string = $VAR['refundNotes'];
$refundNotes_string = stripslashes($refundNotes_string);

$refundItems_string = $VAR['refundItems'];
$refundItems_string = stripslashes($refundItems_string);

    $sql = "UPDATE ".PRFX."TABLE_REFUND SET

            REFUND_PAYEE            = ". $db->qstr( $VAR['refundPayee']        ).",
            REFUND_DATE            = ". $db->qstr( $checked_date               ).",
            REFUND_TYPE            = ". $db->qstr( $VAR['refundType']         ).",
            REFUND_PAYMENT_METHOD          = ". $db->qstr( $VAR['refundPaymentMethod']).",
            REFUND_NET_AMOUNT        = ". $db->qstr( $VAR['refundNetAmount']    ).",
                        REFUND_TAX_RATE                = ". $db->qstr( $VAR['refundTaxRate']      ).",
                        REFUND_TAX_AMOUNT              = ". $db->qstr( $VAR['refundTaxAmount']    ).",
                        REFUND_GROSS_AMOUNT            = ". $db->qstr( $VAR['refundGrossAmount']  ).",
                        REFUND_NOTES                   = ". $db->qstr( $refundNotes_string        ).",
                        REFUND_ITEMS                   = ". $db->qstr( $refundItems_string        )."
                        WHERE REFUND_ID        = ". $db->qstr( $VAR['refund_id']           );
                        
            
    if(!$result = $db->Execute($sql)) {
        force_page('core', 'error&error_msg=MySQL Error: '.$db->ErrorMsg().'&menu=1&type=database');
        exit;
    } else {
      return true;
    }
    
} 

#####################################
#    Delete Record               #
#####################################

function delete_refund($db, $refund_id){
    $sql = "DELETE FROM ".PRFX."TABLE_REFUND WHERE REFUND_ID=".$db->qstr($refund_id);
    
    if(!$rs = $db->Execute($sql)) {
        force_page('core', 'error&error_msg=MySQL Error: '.$db->ErrorMsg().'&menu=1&type=database');
        exit;    
    } else {
        return true;
    }    
}

#####################################
#     Display Single Record         #
#####################################

function display_refund_info($db, $refund_id){

    $sql = "SELECT * FROM ".PRFX."TABLE_REFUND WHERE REFUND_ID=".$db->qstr($refund_id);

    if(!$result = $db->Execute($sql)) {
        force_page('core', 'error&error_msg=MySQL Error: '.$db->ErrorMsg().'&menu=1&type=database');
        exit;
    } else {
        $refund_array = array();
    }

    while($row = $result->FetchRow()){
         array_push($refund_array, $row);
    }

    return $refund_array;
}

##########################################
#          xml2php Gateway               #
# Loads language file up as a php array  #
##########################################

function gateway_xml2php($module) {
    global $smarty;

   $file = QWCRM_PHYSICAL_PATH.LANGUAGE_DIR.THEME_LANGUAGE;

   $xml_parser = xml_parser_create();
   if (!($fp = fopen($file, 'r'))) {
       die('unable to open XML');
   }
   $contents = fread($fp, filesize($file));
   fclose($fp);
   xml_parse_into_struct($xml_parser, $contents, $arr_vals);
   xml_parser_free($xml_parser);

   $xmlarray = array();

  foreach($arr_vals as $things){
        if($things['tag'] != 'TRANSLATE' && $things['value'] != "" ){

                    $ttag = strtolower($things['tag']);
                    $tvalue = $things['value'];

                    $xmlarray[$ttag]= $tvalue;
                      
        }
    }
 
    return $xmlarray;
}

######################################################
#                REFUND GATEWAY                      #
#      Manipulates search data for server submission #
######################################################

function refund_search_gateway($db, $refund_search_category, $refund_search_term) {
    // global $smarty;

           $langvals = gateway_xml2php('refund');

            switch ($refund_search_category) {

                   case "DATE": {
                   $refund_gateway_search_term = date_to_timestamp($refund_search_term);
                   return $refund_gateway_search_term;
                   break;
                   }

                   case "TYPE": {
                           switch ($refund_search_term) {

                             case ($langvals['refund_type_1']):
                                 $refund_gateway_search_term = "1";
                                 return $refund_gateway_search_term;
                                 break;

                             case ($langvals['refund_type_2']):
                                 $refund_gateway_search_term = "2";
                                 return $refund_gateway_search_term;
                                 break;

                             case ($langvals['refund_type_3']):
                                 $refund_gateway_search_term = "3";
                                 return $refund_gateway_search_term;
                                 break;

                             case ($langvals['refund_type_4']):
                                 $refund_gateway_search_term = "4";
                                 return $refund_gateway_search_term;
                                 break;

                             case ($langvals['refund_type_5']):
                                 $refund_gateway_search_term = "5";
                                 return $refund_gateway_search_term;
                                 break;

                           default:
                                  $refund_search_gateway = $refund_search_term;
                                  return $refund_search_gateway;
                                  break;
                                }
                             }

                  case "PAYMENT_METHOD": {
                           switch ($refund_search_term) {

                             case ($langvals['refund_payment_method_1']):
                                 $refund_gateway_search_term = "1";
                                 return $refund_gateway_search_term;
                                 break;

                             case ($langvals['refund_payment_method_2']):
                                 $refund_gateway_search_term = "2";
                                 return $refund_gateway_search_term;
                                 break;

                             case ($langvals['refund_payment_method_3']):
                                 $refund_gateway_search_term = "3";
                                 return $refund_gateway_search_term;
                                 break;

                             case ($langvals['refund_payment_method_4']):
                                 $refund_gateway_search_term = "4";
                                 return $refund_gateway_search_term;
                                 break;

                             case ($langvals['refund_payment_method_5']):
                                 $refund_gateway_search_term = "5";
                                 return $refund_gateway_search_term;
                                 break;

                             case ($langvals['refund_payment_method_6']):
                                 $refund_gateway_search_term = "6";
                                 return $refund_gateway_search_term;
                                 break;

                             case ($langvals['refund_payment_method_7']):
                                 $refund_gateway_search_term = "7";
                                 return $refund_gateway_search_term;
                                 break;

                             case ($langvals['refund_payment_method_8']):
                                 $refund_gateway_search_term = "8";
                                 return $refund_gateway_search_term;
                                 break;

                             case ($langvals['refund_payment_method_9']):
                                 $refund_gateway_search_term = "9";
                                 return $refund_gateway_search_term;
                                 break;

                             case ($langvals['refund_payment_method_10']):
                                 $refund_gateway_search_term = "10";
                                 return $refund_gateway_search_term;
                                 break;

                             case ($langvals['refund_payment_method_11']):
                                 $refund_gateway_search_term = "11";
                                 return $refund_gateway_search_term;
                                 break;

                                }
                             }

                  default:
                      $refund_gateway_search_term = "%".$refund_search_term."%";
                      return $refund_gateway_search_term;
                      break;

               }
    }

######################################################
# Search - Also returns records for intial view page #
######################################################

function display_refund_search($db, $refund_search_category, $refund_search_term, $page_no, $smarty) {
    global $smarty;

    // Define the number of results per page
    $max_results = 25;

    // Figure out the limit for the Execute based
    // on the current page number.
    $from = (($page_no * $max_results) - $max_results);

    $sql = "SELECT * FROM ".PRFX."TABLE_REFUND WHERE REFUND_$refund_search_category LIKE '$refund_search_term' ORDER BY REFUND_ID DESC LIMIT $from, $max_results";

    //print $sql;

    if(!$result = $db->Execute($sql)) {
        force_page('core', 'error&error_msg=MySQL Error: '.$db->ErrorMsg().'&menu=1&type=database');
        exit;
    } else {
        $refund_search_result = array();
    }

    while($row = $result->FetchRow()){
         array_push($refund_search_result, $row);
    }

    // Figure out the total number of results in DB:
    $results = $db->Execute("SELECT COUNT(*) as Num FROM ".PRFX."TABLE_REFUND WHERE REFUND_$refund_search_category LIKE ".$db->qstr("$refund_search_term") );

    if(!$total_results = $results->FetchRow()) {
        force_page('core', 'error&error_msg=MySQL Error: '.$db->ErrorMsg().'&menu=1&type=database');
        exit;
    } else {
        $smarty->assign('total_results', $total_results['Num']);
    }

    // Figure out the total number of pages. Always round up using ceil()
    $total_pages = ceil($total_results["Num"] / $max_results);
    $smarty->assign('total_pages', $total_pages);

    // Assign the first page
    if($page_no > 1) {
        $prev = ($page_no - 1);
    }

    // Build Next Link
    if($page_no < $total_pages){
        $next = ($page_no + 1);
    }

    $smarty->assign('items', $items);
    $smarty->assign('page_no', $page_no);
    $smarty->assign('previous', $prev);
    $smarty->assign('next', $next);
        $smarty->assign('refund_search_category', $refund_search_category);
        $smarty->assign('refund_search_term', $refund_search_term);

    return $refund_search_result;
}