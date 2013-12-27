<div id="content_banner_stats_{$smarty.request.banner_id}">

{if $stat}
<table width="100%" class="table">
<thead>
    <tr>
        <th>{__("date")}</th>
        <th>{__("clicks")}</th>
        <th>{__("views")}</th>
        <th>{__("conversion")}</th>
    </tr>
</thead>
{foreach from=$stat item="s" key="t"}
<tr>
    <td>
    {if $period == "year"}
        {$t|date_format:"%Y"}
    {elseif $period == "month"}
        {$t|date_format:"%Y, %B"}
    {elseif $period == "day"}
        {$t|date_format:"%B, %d"}
    {elseif $period == "hour"}
        {$t|date_format:"%A, %H:%M"}
    {/if}
    </td>
    <td>{$s.C.number|default:"0"}</td>
    <td>{$s.V.number|default:"0"}</td>
    <td>{$s.conversion|default:"0"}%</td>
</tr>
{/foreach}
</table>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

<!--content_banner_stats_{$smarty.request.banner_id}--></div>