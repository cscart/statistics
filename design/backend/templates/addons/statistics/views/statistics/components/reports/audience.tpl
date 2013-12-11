{capture name="tabsbox"}

<div id="content_{$report_data.report}">
    {if $report_data.data}
    {capture name="table_chart"}
    
    <table cellpadding="2" cellspacing="1" border="0">
    {foreach from=$report_data.data item="row" key="key"}
    <tr>
        <td valign="top">
            {$row.label|default:__("undefined")}
            {if $report_data.report == "page_load_speed"}

                {if $smarty.request.load_speed_details}
                <div id="content_stat_pages_{$key}">

                    {if $row.pages}
                    <table class="table" width="400">
                    {foreach from=$row.pages item="_page"}
                    <tr>
                        <td><div class="no-scroll"><a href="{$_page.url|fn_url}">{$_page.url}</a></div></td>
                        {math equation="loadtime/1000000" assign="time" loadtime=$_page.loadtime}
                        <td align="right">&nbsp;&nbsp;{$time|string_format:"%07.6f"}</td>
                    </tr>
                    {/foreach}
                    </table>
                    {else}
                        <p class="no-items">{__("no_data")}</p>
                    {/if}

                <!--content_stat_pages_{$key}--></div>
                {/if}

                {include file="common/table_tools_list.tpl" prefix=$key tools_list=$smarty.capture.tools_items id="stat_pages_`$key`" text=__("pages") link_text=__("view_pages") act="link" href="statistics.reports?reports_group=audience&report=page_load_speed&load_speed_details=`$key`" link_class="tool-link" popup=true}
            {/if}
            {include file="views/sales_reports/components/graph_bar.tpl" bar_width="800px" value_width=$row.percent|round}
        </td>
        {if $report_data.report == "page_load_speed"}
        <td align="right" width="10px">
            <span class="small-note">+</span>{$row.sum_count}
            <p class="muted">({$row.sum_percent}%)</p></td>
        {/if}
        <td width="10px">&nbsp;</td>
        <td width="10px" class="right">
            {if $report_data.report == "site_attendance" || $report_data.report == "page_load_speed"}
                {if $report_data.report == "site_attendance"}{assign var="object_code" value=$row.hour}{else}{assign var="object_code" value=$row.label}{/if}
                <a href="{"statistics.visitors?section=audience&report=`$report_data.report`&object_code=`$object_code`"|fn_url}">{$row.count}</a>
            {else}
                {$row.count}
            {/if}
            
            {if $report_data.report != "site_attendance"}
                <p class="muted">{$row.percent}%</p>
            {/if}
        </td>
    </tr>
    {/foreach}
    {if $report_data.report == "total_pages_viewed"}
    <tr>
        <td>{__("average_depth")}:&nbsp;</td>
        <td align="right"><span>{$report_data.average_depth}</span></td>
    </tr>
    {elseif $report_data.report == "stat_visit_time"}
    <tr>
        <td>{__("average_duration")}:&nbsp;</td>
        <td align="right"><span>{$report_data.average_duration|date_format:$settings.Appearance.time_format}</span></td>
    </tr>
    {elseif $report_data.report == "site_attendance"}
    <tr>
        <td class="right">{__("total")}:&nbsp;</td>
        <td width="10px">&nbsp;</td>
        <td class="right"><span>{$data.total|default:"0"}</span></td>
    </tr>
    {/if}
    </table>
    
    {/capture}
    {include file="addons/statistics/views/statistics/components/select_charts.tpl" chart_table=$smarty.capture.table_chart chart_type=$chart_type applicable_charts="bar,pie"}
    {else}
        <p class="no-items">{__("no_data")}</p>
    {/if}
<!--content_{$report_data.report}--></div>

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$smarty.request.selected_section|default:$report_data.report track=true}