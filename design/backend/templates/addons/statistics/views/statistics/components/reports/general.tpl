{capture name="table_chart"}

{include file="common/pagination.tpl" div_id="general_pagination_content"}

{if $report_data.data}
<table width="100%" class="table">
<thead>
    <tr>
        <th>{__("date")}</th>
        <th class="right">{__("total")}</th>
        <th class="right">{__("robots")}</th>
        <th class="right">{__("visitors")}</th>
        <th class="right">{__("visitor_hosts")}</th>
    </tr>
</thead>
{foreach from=$report_data.data key="date" item="stat"}
<tr>
    <td>
        {if $statistic_period == $smarty.const.STAT_PERIOD_DAY}
            {$stat.time_from|date_format:$settings.Appearance.date_format}
        {elseif $statistic_period == $smarty.const.STAT_PERIOD_HOUR}
            {$stat.time_from|date_format:"`$settings.Appearance.time_format`, `$settings.Appearance.date_format`"}
        {/if}
    </td>
    <td class="right">{$stat.total}</td>
    <td class="right">{if $stat.robots}<a href="{"statistics.visitors?section=general&report=general&time_from=`$stat.time_from`&period=`$statistic_period`&client_type=B"|fn_url}">{/if}{$stat.robots}{if $stat.robots}</a>{/if}</td>
    <td class="right">{if $stat.visitors}<a href="{"statistics.visitors?section=general&report=general&time_from=`$stat.time_from`&period=`$statistic_period`&client_type=U"|fn_url}">{/if}{$stat.visitors}{if $stat.visitors}</a>{/if}</td>
    <td class="right">{$stat.hosts}</td>
</tr>
{/foreach}
</table>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl" div_id="general_pagination_content"}

{/capture}
{include file="addons/statistics/views/statistics/components/select_charts.tpl" chart_table=$smarty.capture.table_chart chart_type=$chart_type applicable_charts="line"}