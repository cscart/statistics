{if !$smarty.capture.chart_js}

<script type="text/javascript">
//<![CDATA[
    {literal}
    function fn_switch_stat_graphics(url, rep)
    {
        Tygh.$.ceAjax('request', 
            url,
            {result_ids: 'chart_contents_' + rep}
        );
    }
    {/literal}
//]]>
</script>
{capture name="chart_js"}Y{/capture}
{/if}

<div class="right">
    <span>{__("type")}:</span>&nbsp;
    <select onchange="fn_switch_stat_graphics('{$config.current_url|fn_query_remove:"chart_type"|fn_url:'A':'rel' nofilter}&chart_type=' + this.value, '{$report_data.report}');">
        <option value="table" {if $chart_type == "table"}selected="selected"{/if}>{__("table")}</option>
        {if $applicable_charts|strpos:"bar" !== false}
        <option value="bar" {if $chart_type == "bar"}selected="selected"{/if}>{__("graphic")} [{__("bar")}]</option>
        {/if}
        {if $applicable_charts|strpos:"pie" !== false}
        <option value="pie" {if $chart_type == "pie"}selected="selected"{/if}>{__("graphic")} [{__("pie")}]</option>
        {/if}
        {if $applicable_charts|strpos:"line" !== false}
        <option value="line" {if $chart_type == "line"}selected="selected"{/if}>{__("graphic")} [{__("line")}]</option>
        {/if}
    </select>
</div>

<div id="chart_contents_{$report_data.report}">
    {if $chart_type == "table"}
        {$chart_table nofilter}
    {elseif $chart_type == "bar"}
        {include file="views/sales_reports/components/amchart.tpl" type="column" set_type="bar" chart_data=$chart_data chart_id="`$report_data.report`_``$chart_type`" chart_title=$chart_title chart_height=$column_height}
    {elseif $chart_type == "pie"}
        {include file="views/sales_reports/components/amchart.tpl" type="pie" chart_data=$chart_data chart_id="`$report_data.report`_``$chart_type`" chart_title=$chart_title chart_height=$pie_height}
    {elseif $chart_type == "line"}
        {include file="views/sales_reports/components/amchart.tpl" type="line" chart_data=$chart_data chart_id="`$report_data.report`_``$chart_type`" chart_title=$chart_title chart_height=$line_height}
    {/if}
<!--chart_contents_{$report_data.report}--></div>
