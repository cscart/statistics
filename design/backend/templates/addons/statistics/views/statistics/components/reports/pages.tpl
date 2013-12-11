{capture name="tabsbox"}

<div id="content_{$report_data.report}">
    {if $report_data.data}
    {capture name="table_chart"}

    <table cellpadding="2" cellspacing="1" border="0">
    {foreach from=$report_data.data item="row" name="stat"}
    <tr>
        <td><span class="muted">{$smarty.foreach.stat.iteration}</span></td>
        <td>
            <div class="no-scroll">
            {if $row.title}
                <a href="{$row.label}">{$row.label|default:__("undefined")|truncate:115:"...":true}</a>
            {else}
                {$row.label|default:__("undefined")|truncate:115:"...":true}
            {/if}
            {if ($report_data.report == "pages_by_visits" || $report_data.report == "entry_points" || $report_data.report == "exit_points") && $row.title}
                <br />{$row.title}
            {/if}
            {include file="views/sales_reports/components/graph_bar.tpl" bar_width="800px" value_width=$row.percent|round}
            </div>
        </td>
        <td width="10px">&nbsp;</td>
        <td width="100px">
            {assign var="object_code" value=$row.label|escape:url}
            <a href="{"statistics.visitors?section=pages&report=`$report_data.report`&object_code=`$object_code`"|fn_url}">{$row.count}</a>
            <p class="muted">({$row.percent}%)</p>
        </td>
    </tr>
    {/foreach}
    </table>

    {/capture}
    {include file="addons/statistics/views/statistics/components/select_charts.tpl" chart_table=$smarty.capture.table_chart chart_type=$chart_type applicable_charts="bar,pie"}
    {else}
        <p class="no-items">{__("no_data")}</p>
    {/if}
<!--content_{$report_data.report}--></div>

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$smarty.request.selected_section|default:$report_data.report track=true}