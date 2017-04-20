<!-- details.tpl -->
<table width="100%">
       <tr>
        <td>
            <div id="tabs_container">
                <ul class="tabs">
                    <li class="active"><a href="#" rel="#tab_1_contents" class="tab"><img src="{$theme_images_dir}icons/customers.gif" alt="" border="0" height="14" width="14" />&nbsp;Customer Details</a></li>
                    <li><a href="#" rel="#tab_2_contents" class="tab"><img src="{$theme_images_dir}icons/workorders.gif" alt="" border="0" height="14" width="14" />&nbsp;Works Orders</a></li>
                    <li><a href="#" rel="#tab_3_contents" class="tab"><img src="{$theme_images_dir}icons/invoice.png" alt="" border="0" height="14" width="14" />&nbsp;Invoices</a></li>
                    <li><a href="#" rel="#tab_4_contents" class="tab">Gift Certificates</a></li>
                    <li><a href="#" rel="#tab_5_contents" class="tab">Notes</a></li>                    
                </ul>

                <!-- This is used so the contents don't appear to the right of the tabs -->
                <div class="clear"></div>

                <!-- This is a div that hold all the tabbed contents -->
                <div class="tab_contents_container">

                    <!-- Tab 1 Contents (Customer Details) -->
                    <div id="tab_1_contents" class="tab_contents tab_contents_active">
                        {include file='customer/blocks/details_customer_details_block.tpl'}
                    </div>

                    <!-- Tab 2 Contents (Work Orders) -->
                    <div id="tab_2_contents" class="tab_contents">
                        {include file='customer/blocks/details_workorder_block.tpl'}
                    </div>

                    <!-- Tab 3 Contents (Invoices) -->
                    <div id="tab_3_contents" class="tab_contents">
                        {include file='customer/blocks/details_invoice_block.tpl'}
                    </div>

                    <!-- Tab 4 Contents (Gift Certificates) -->
                    <div id="tab_4_contents" class="tab_contents">
                        {include file='customer/blocks/details_giftcert_block.tpl'}
                    </div>

                    <!-- Tab 5 Contents (Customer Notes) -->
                    <div id="tab_5_contents" class="tab_contents">                        
                        {include file='customer/blocks/details_note_block.tpl'}   
                    </div>
                    
                </div>
            </div>
        </td>
    </tr>
</table>