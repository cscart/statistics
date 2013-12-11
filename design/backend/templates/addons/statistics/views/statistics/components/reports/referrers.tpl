{capture name="tabsbox"}

<div id="content_{$report_data.report}">
    {if $report_data.data}
    {capture name="table_chart"}

    <table cellpadding="2" cellspacing="1" border="0">
    {foreach from=$report_data.data item="row" name="stat"}
    <tr>
        <td><span class="muted">{$smarty.foreach.stat.iteration}</span></td>
        {if $report_data.report == "came_to" || $report_data.report == "came_from"}
        <td>
            <a href="{$search_engines[$row.engine].url}" target="_blank">{$row.engine}</a>
        </td>
        {/if}
        <td>
            <div class="no-scroll">
                {if $report_data.report == "all_referrers" || $report_data.report == "by_domain"}
                    <a href="{$row.label}" target="_blank">{$row.label}</a>
                {elseif $report_data.report == "by_search_engine"}
                    {assign var="field_value" value=$row.label}
                    <a href="{$search_engines[$row.label].url}" target="_blank">{$row.label}</a>
                {else}
                    {$row.label nofilter}
                {/if}
                {if $report_data.report == "all_referrers" && $row.phrase}
                <br>
                    <span>{__("phrase")}: {$row.phrase nofilter}</span>
                {/if}
            {include file="views/sales_reports/components/graph_bar.tpl" bar_width="800px" value_width=$row.percent|round}
            </div>
        </td>
        <td width="10px">&nbsp;</td>
        <td width="100px">
            {if $report_data.report == "all_referrers" || $report_data.report == "by_domain"}
                {assign var="object_code" value=$row.label|escape:url}
            {elseif $report_data.report == "by_search_engine"}
                {assign var="object_code" value=$row.engine_id}
            {elseif $report_data.report == "search_words"}
                {assign var="object_code" value=$row.phrase_id}
            {elseif $report_data.report == "came_to"}
                {assign var="object_code" value=$row.label|escape:url}
                {assign var="object_code" value="`$object_code`&engine_id=`$row.engine_id`"}
            {elseif $report_data.report == "came_from"}
                {assign var="object_code" value="`$row.phrase_id`&engine_id=`$row.engine_id`"}
            {/if}
            <a href="{"statistics.visitors?reports_group=referrers&report=`$report_data.report`"|fn_url}&object_code={$object_code}">{$row.count}</a>

            <span>({$row.percent}%)</span></td>
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