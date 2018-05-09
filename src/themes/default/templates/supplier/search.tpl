<!-- search.tpl -->
{*
 * @package   QWcrm
 * @author    Jon Brown https://quantumwarp.com/
 * @copyright Copyright (C) 2016 - 2017 Jon Brown, All rights reserved.
 * @license   GNU/GPLv3 or later; https://www.gnu.org/licenses/gpl.html
*}
<table width="100%" border="0" cellpadding="20" cellspacing="5">
    <tr>
        <td>
            <table width="700" cellpadding="4" cellspacing="0" border="0" >
                <tr>
                    <td class="menuhead2" width="80%">&nbsp;&nbsp;{t}Supplier Search{/t}</td>
                    <td class="menuhead2" width="20%" align="right" valign="middle">
                        <a>
                            <img src="{$theme_images_dir}icons/16x16/help.gif" border="0" onMouseOver="ddrivetip('<div><strong>{t escape=tooltip}SUPPLIER_SEARCH_HELP_TITLE{/t}</strong></div><hr><div>{t escape=tooltip}SUPPLIER_SEARCH_HELP_CONTENT{/t}</div>');" onMouseOut="hideddrivetip();">
                        </a>
                    </td>
                </tr>
                <tr>
                    <td class="menutd2" colspan="2">
                        <table class="olotable" width="100%" border="0" cellpadding="5" cellspacing="0">
                            <tr>
                                <td class="menutd">
                                    <table class="menutable" width="100%" border="0" cellpadding="5" cellspacing="0">
                                        <tr>
                                            <!-- Category Search -->
                                            <td valign="top">
                                                <form method="post" action="index.php?page=supplier:search" name="supplier_search" id="supplier_search" autocomplete="off">                                                        
                                                    <table border="0">
                                                        <tr>
                                                            <td align="left" valign="top"><b>{t}Supplier Search{/t}</b>
                                                                <br />
                                                                <select class="olotd5" id="search_category" name="search_category">
                                                                    <option value="supplier_id"{if $search_category == 'supplier_id'} selected{/if}>{t}Supplier ID{/t}</option>
                                                                    <option value="display_name"{if $search_category == 'display_name'} selected{/if}>{t}Display Name{/t}</option>                                                                    
                                                                    <option value="type"{if $search_category == 'type'} selected{/if}>{t}Type{/t}</option>
                                                                    <option value="zip"{if $search_category == 'zip'} selected{/if}>{t}Zip{/t}</option>
                                                                    <option value="country"{if $search_category == 'country'} selected{/if}>{t}Country{/t}</option>                                                                    
                                                                    <option value="description"{if $search_category == 'description'} selected{/if}>{t}Description{/t}</option>
                                                                    <option value="notes"{if $search_category == 'notes'} selected{/if}>{t}Notes{/t}</option>
                                                                </select>
                                                                <br />
                                                                <b>{t}for{/t}</b>
                                                                <br />
                                                                <input name="search_term" class="olotd4" value="{$search_term}" type="text" maxlength="20" required onkeydown="return onlySearch(event);" />
                                                                <input name="submit" class="olotd4" value="{t}Search{/t}" type="submit" />
                                                                <input type="button" class="olotd4" value="{t}Reset{/t}" onclick="window.location.href='index.php?page=supplier:search';">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td><font color="red">{t}NO special characters like !@#$%^*(){/t}</font></td>
                                                        </tr>
                                                    </table>                                                        
                                                </form>                                          
                                            </td>                                                   

                                            <!-- Navigation -->
                                            <td valign="top" nowrap align="right">
                                                <form id="navigation">                                                    
                                                    <table>
                                                        <tr>
                                                            
                                                            <!-- Left buttons -->
                                                            <td>                                                                
                                                                <a href="index.php?page=supplier:search&search_category={$search_category}&search_term={$search_term}&page_no=1"><img src="{$theme_images_dir}rewnd_24.gif" border="0" alt=""></a>&nbsp;                                                    
                                                                <a href="index.php?page=supplier:search&search_category={$search_category}&search_term={$search_term}&page_no={$previous}"><img src="{$theme_images_dir}back_24.gif" border="0" alt=""></a>&nbsp;
                                                            </td>                                                   
                                                    
                                                            <!-- Dropdown Menu -->
                                                            <td>                                                                    
                                                                <select id="changeThisPage" onChange="changePage();">
                                                                    {section name=page loop=$total_pages start=1}
                                                                        <option value="index.php?page=supplier:search&search_category={$search_category}&search_term={$search_term}&page_no={$smarty.section.page.index}" {if $page_no == $smarty.section.page.index } Selected {/if}>
                                                                            {t}Page{/t} {$smarty.section.page.index} {t}of{/t} {$total_pages} 
                                                                        </option>
                                                                    {/section}
                                                                    <option value="index.php?page=supplier:search&search_category={$search_category}&search_term={$search_term}&page_no={$total_pages}" {if $page_no == $total_pages} selected {/if}>
                                                                        {t}Page{/t} {$total_pages} {t}of{/t} {$total_pages}
                                                                    </option>
                                                                </select>
                                                            </td>
                                                            
                                                            <!-- Right Side Buttons --> 
                                                            <td>
                                                                <a href="index.php?page=supplier:search&search_category={$search_category}&search_term={$search_term}&page_no={$next}"><img src="{$theme_images_dir}forwd_24.gif" border="0" alt=""></a>                                                   
                                                                <a href="index.php?page=supplier:search&search_category={$search_category}&search_term={$search_term}&page_no={$total_pages}"><img src="{$theme_images_dir}fastf_24.gif" border="0" alt=""></a>
                                                            </td>                                                                                             
                                                    
                                                        </tr>
                                                        <tr>

                                                            <!-- Page Number Display -->
                                                            <td></td>
                                                            <td>
                                                                <p style="text-align: center;">{$total_results} {t}records found.{/t}</p>
                                                            </td>
                                                            
                                                        </tr>                                                    
                                                    </table>                                                    
                                                </form>                                                
                                            </td>
                                            
                                        </tr>
                                        <tr>
                                            <!-- Records Table -->
                                            <td valign="top" colspan="2">
                                                {include file='supplier/blocks/display_suppliers_block.tpl' display_suppliers=$display_suppliers block_title=''}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>