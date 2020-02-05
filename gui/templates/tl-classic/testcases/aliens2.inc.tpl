{* 
TestLink Open Source Project - http://testlink.sourceforge.net/
@filesource aliens2.inc.tpl
@used_by 
*}

{lang_get var='alien_labels' 
          s='btn_add,img_title_remove_alien,warning,
             select_aliens,createAlien,btn_create_and_link'}

{lang_get s='remove_alien_msgbox_msg' var='remove_alien_msgbox_msg'}
{lang_get s='remove_alien_msgbox_title' var='remove_alien_msgbox_title'}

<script type="text/javascript">
var alert_box_title = "{$alien_labels.warning|escape:'javascript'}";
var remove_alien_msgbox_msg = '{$remove_alien_msgbox_msg|escape:'javascript'}';
var remove_alien_msgbox_title = '{$remove_alien_msgbox_title|escape:'javascript'}';



/**
 * 
 *
 */
function alien_remove_confirmation(item_id, tcalien_link_id, alien, title, msg, pFunction) 
{
  var my_msg = msg.replace('%i',alien);
  var safe_title = escapeHTML(title);
  Ext.Msg.confirm(safe_title, my_msg,
                  function(btn, text) { 
                    pFunction(btn,text,item_id, tcalien_link_id);
                  });
}


/**
 * 
 *
 */
function remove_alien(btn, text, item_id, tcalien_link_id) 
{
  var my_url = "{$gui->delTCVAlienURL}";
  var dummy = my_url.replace('%1',item_id);
  var my_action = dummy.replace('%2',tcalien_link_id);


  if( btn == 'yes' ) {
    window.location=my_action;
  }
}

var pF_remove_alien = remove_alien;

</script>

<form method="post" action="{$basehref}lib/testcases/tcEdit.php">
  <input type="hidden" id="alf_doAction" name="doAction"
    value="removeAlien" />
      
  <input type="hidden" name="tcase_id" id="tcase_id" 
    value="{$args_tcase_id}" />

  <input type="hidden" name="tcversion_id" id="tcversion_id"
    value="{$args_tcversion_id}" />

  <input type="hidden" name="tproject_id" id="tproject_id"
    value="{$gui->tproject_id}" />

  {if property_exists($gui,'tplan_id') } 
    <input type="hidden" name="tplan_id" value="{$gui->tplan_id}" />
  {/if}

  {if property_exists($gui,'show_mode') } 
    <input type="hidden" name="show_mode" value="{$gui->show_mode}" />
  {/if}

  {$canWork=1}
  <table class="simple" id="relations">
    {if $args_edit_enabled}
      <br>
      {if $canWork}
        <tr><th colspan="7">{$tcView_viewer_labels.aliens} 
        </th></tr>

  		  {if $args_frozen_version == "no"}
            <tr>
              {$addEnabled = $args_edit_enabled}
              {if $addEnabled && null != $gui->currentVersionFreeAliens} 
                <td>
                  <select id="free_aliens" name="free_aliens[]"
                    data-placeholder="{$alien_labels.select_aliens}"
                    class="chosen-add-aliens" multiple="multiple">
                    {html_options options = $gui->currentVersionFreeAliens}
                  </select>
                  <input type="submit" value="{$alien_labels.btn_add}"
                    onclick="doAction.value='addAlien'">
                </td>  
                <script>
                  jQuery( document ).ready(
                    function() { 
                      jQuery(".chosen-add-aliens").chosen({ width: "75%", allow_single_deselect: true }); 
                    }
                  );
                </script>  
              {/if}
            </tr>
  		  {/if}

      {/if}
    {/if} {* Item can be managed *}


    {* Display Existent Items *}
    {$removeEnabled = $args_edit_enabled 
                      && $gui->assign_aliens 
                      && $args_frozen_version == "no"}


    {if 1}
      <tr>
        <th><nobr>{$rel_labels.relation_id} / {$rel_labels.relation_type}</nobr></th>
        <th colspan="1">{$rel_labels.test_case}</th>
        <th><nobr>{$rel_labels.relation_set_by}</nobr></th>
        <th><nobr>&nbsp;</nobr></th>
      </tr>
      <tr>
        <td style="vertical-align:top;">
          {foreach item=tcalien_link_item from=$args_aliens_map}
            {if $removeEnabled}
              <a href="javascript:alien_remove_confirmation(
                         {$gui->tcase_id},
                         {$tcalien_link_item.tcalien_link},
                         '{$tcalien_link_item.name|escape:'javascript'}',
                         remove_alien_msgbox_title, remove_alien_msgbox_msg, 
                         pF_remove_alien);">
              <img src="{$tlImages.delete}"
                title="{$alien_labels.img_title_remove_alien}" 
                style="border:none" /></a>
            {/if}
            {debug}
            &nbsp;&nbsp;
            {$tcalien_link_item.name|escape}
            &nbsp;
            {$tcalien_link_item.blob->summaryHTMLString|escape}
            <br />
          {/foreach}
        </td>      
      </tr>  

    {/if}
    </table>
    </form>
