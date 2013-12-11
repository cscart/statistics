{script src="js/lib/amcharts/swfobject.js"}


{capture name="mainbox"}
    <div id="content_{$reports_group}">
        {include file="addons/statistics/views/statistics/components/reports/`$reports_group`.tpl" report_data=$statistics_data}
    <!--content_{$reports_group}--></div>
{/capture}

{capture name="sidebar"}
    {include file="common/saved_search.tpl" dispatch="statistics.reports" view_type="statistics"}
    {include file="addons/statistics/views/statistics/components/search_form.tpl" key=$runtime.action dispatch="statistics.reports"}
{/capture}

{capture name="buttons"}
    {capture name="tools_list"}
        <li>{btn type="list" text=__("users_online") href="statistics.visitors?section=general&report=online"}</li>
        <li>{btn type="list" text=__("all_users") href="statistics.visitors?section=general&client_type=U"}</li>
        <li class="divider"></li>
        <li>{btn type="list" class="cm-confirm" text=__("remove_statistics") href="statistics.delete"}</li>
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
{/capture}

{include file="common/mainbox.tpl" title="{__("statistics")}: {__($reports_group)}" content=$smarty.capture.mainbox sidebar=$smarty.capture.sidebar buttons=$smarty.capture.buttons}

