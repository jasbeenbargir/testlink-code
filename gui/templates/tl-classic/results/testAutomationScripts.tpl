{* 
TestLink Open Source Project - http://testlink.sourceforge.net/ 

@filesource testAutomationScripts.tpl
*}

{lang_get var="labels" 
  s="numOfScripts,report_test_automation_scripts,totalNumberOfScripts"}

{include file="inc_head.tpl" openHead="yes"}
</head>

<body>
<form id='testAutomationScripts' name='testAutomationScripts' method='post'>
	<h1 class="title">{$gui->title|escape}</h1>
  {if count($gui->topContainers) > 0}
    {$labels.totalNumberOfScripts} {$gui->totalScripts}
    <div class="workBack">
	    {foreach from=$gui->topContainers item=name key=topName}
	        <div style="margin-left:0px; border:1;">
            <br />
              <table cellspacing="0" style="font-size:small;" width="100%" 
                     class="tableruler">
                <caption 
                  style="text-align:left;font-size:x-small;background-color:#059; font-weight:bold; color:white">{$name|escape} ({$labels.numOfScripts}={$gui->scriptQty[$name]})</caption>
                <tbody>  
                  {foreach from=$gui->scripts[$name] item=script}
                    <tr>
                      <td>{$script|escape}</td>
                    </tr>
                  {/foreach}   
                </tbody>
              </table>
          </div>
	    {/foreach}
	  </div>
  {/if} 
</form>
</body>
</html>