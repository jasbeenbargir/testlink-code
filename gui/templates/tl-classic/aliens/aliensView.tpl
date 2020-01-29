{*
TestLink Open Source Project - http://testlink.sourceforge.net/
@filesource: aliensView.tpl
 smarty template - View all aliens 
*}

{$cfg_section=$smarty.template|basename|replace:".tpl":"" }
{config_load file="input_dimensions.conf" section=$cfg_section}

{include file="inc_head.tpl" jsValidate="yes" openHead="yes" enableTableSorting="yes"}
{include file="inc_del_onclick.tpl"}

{lang_get var='labels'
          s='th_notes,th_alien,th_delete,btn_import,btn_export,
             btn_assign_alien_to_tc,btn_create_alien,
             menu_manage_aliens,alt_delete_alien,
             tcvqty_with_alien,link_type,realtime,snapshot,
             change_to_realtime,change_to_snapshot'}

{lang_get s='warning_delete_alien' var="warning_msg" }
{lang_get s='delete' var="del_msgbox_title" }

<script type="text/javascript">
/* All this stuff is needed for logic contained in inc_del_onclick.tpl */
var del_action = fRoot+'lib/aliens/aliensEdit.php'+
                 '?tproject_id={$gui->tproject_id}&doAction=do_delete'+
                 '&openByOther={$gui->openByOther}&id=';
</script>

{if $gui->bodyOnLoad != ''}
  <script language="JavaScript">
  var {$gui->dialogName} = new std_dialog();
  </script>  
{/if}


{include file="bootstrap.inc.tpl"} 
</head>
<body onLoad="{$gui->bodyOnLoad}" onUnload="{$gui->bodyOnUnload}"
      class="testlink">

<h1 class="title">{$labels.menu_manage_aliens}</h1>

<div class="page-content">
  {if $gui->aliens != ''}
  <table class="table table-bordered sortable">
    <thead class="thead-dark">
      <tr>
        <th width="30%">{$tlImages.sort_hint}{$labels.th_alien}</th>
        <th>{$tlImages.sort_hint}{$labels.th_notes}</th>
        <th>{$tlImages.sort_hint}{$labels.link_type}</th>
        {if $gui->canManage != ""}
          <th style="min-width:70px">{$tlImages.sort_hint}{$labels.th_delete}</th>
        {/if}
      </tr>
    </thead>

    <tbody>
    {section name=kwx loop=$gui->aliens}
      {$itemID=$gui->aliens[kwx]['id']}
      {$item=$gui->aliens[kwx]}
    <tr>
      <td>
        {if $gui->canManage != ""}
          <a href="{$gui->editUrl}&doAction=edit&id={$item['id']}&openByOther={$gui->openByOther}">
        {/if}
        {$item['name']|escape}

        {if $gui->canManage != ""}
          </a>
        {/if}
        <span title="{$labels.tcvqty_with_alien}">({$gui->alienOnTCV[$itemID]['tcv_qty']})</span>
      </td>
      <td>{$item['notes']|escape:htmlall|nl2br}</td>

      <td class="clickable_icon">
        {if $item['link_type']==1} 
          <input type="image" style="border:none"
                 id="disableDesign_{$oplat.id}"
                 name="disableDesign"
                 title="{$labels.change_to_snapshot}"
                 alt="{$labels.change_to_snapshot}" 
                 onClick = "platform_id.value={$itemID};doAction.value='disableDesign';"
                 src="{$tlImages.realtime}"/>
        {/if}
        {if $item['link_type']==2} 
          <input type="image" style="border:none"
                 id="enableDesign_{$oplat.id}"
                 name="enableDesign"
                 title="{$labels.change_to_realtime}" 
                 alt="{$labels.change_to_realtime}" 
                 onClick = "doAction.value='linkTypeSnapshot';alien_id.value={$itemID};"
                 src="{$tlImages.snapshot}"/>
        {/if}
      </td>




      {if $gui->canManage != ""}
        {$yesDel = 1}
        <td class="clickable_icon">
            {if $gui->alienExecStatus != '' && 
                isset($gui->alienExecStatus[$itemID]) &&
                $gui->alienExecStatus[$itemID]['exec_or_not'] == 'EXECUTED'}
                {$yesDel = 0}
            {/if}

            {if $gui->alienFreshStatus != '' && 
                isset($gui->alienFreshStatus[$itemID]) && 
                $gui->alienFreshStatus[$itemID]['fresh_or_frozen'] == 'FROZEN'}
                {$yesDel = 0}
            {/if}

            {if $yesDel == 1}
            <img style="border:none;cursor: pointer;"
                alt="{$labels.alt_delete_alien}" title="{$labels.alt_delete_alien}"   
                src="{$tlImages.delete}"           
               onclick="delete_confirmation({$item['id']},
                      '{$item['name']|escape:'javascript'|escape}',
                      '{$del_msgbox_title}','{$warning_msg}');" />
            {/if}          
        </td>
      {/if}
    </tr>
    {/section}
   </tbody>
  </table>
  {/if}
  

  <div class="page-content">  
      <form name="alien_view" id="alien_view" method="post" action="lib/aliens/aliensEdit.php"> 
        <input type="hidden" name="doAction" value="" />
        <input type="hidden" name="tproject_id" value="{$gui->tproject_id}" />
        <input type="hidden" name="openByOther" value="{$gui->openByOther}" />

    {if $gui->canManage != ""}
        <input type="submit" id="create_alien" name="create_alien" 
               value="{$labels.btn_create_alien}" 
               onclick="doAction.value='create'"/>
    {/if}
    {if $gui->aliens != '' && $gui->canAssign!=''}
        <input type="button" id="alien_assign" name="alien_assign" 
            value="{$labels.btn_assign_alien_to_tc}" 
              onclick="location.href=fRoot+'lib/general/frmWorkArea.php?feature=aliensAssign&tproject_id={$gui->tproject_id}';"/>
    {/if}    
    
    {if $gui->canManage != ""}
      <input type="button" name="do_import" value="{$labels.btn_import}" 
        onclick="location='{$basehref}/lib/aliens/aliensImport.php?tproject_id={$gui->tproject_id}'" />
    {/if}
  
      {if $gui->aliens != ''}
      <input type="button" name="do_export" value="{$labels.btn_export}" 
        onclick="location='{$basehref}/lib/aliens/aliensExport.php?doAction=export&tproject_id={$gui->tproject_id}'" />
      {/if}
      </form>
  </div>
</div>

</body>
</html>