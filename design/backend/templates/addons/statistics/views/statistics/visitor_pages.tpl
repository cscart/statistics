{capture name="tabsbox"}

{capture name="mainbox"}
<div id="content_visitors_pages">
{include file="addons/statistics/views/statistics/components/visitors.tpl" no_sort="Y" no_paginate=true}

{include file="common/subheader.tpl" title=__("route") target="#acc_route"}

<div id="acc_route" class="collapse in">
{include file="common/pagination.tpl" div_id="stat_requests"}

{if $requests}
<table width="100%" class="table">
<thead>
<tr>
    <th width="10%">{__("date")}</th>
    <th>{__("page")}</th>
</tr>
</thead>
{foreach from=$requests item="req"}
<tr>
    <td class="nowrap">{$req.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>
    <td>
        <div><a href="{$req.storefront_url}" target="_blank">{$req.url}</a></div>
        {$req.title}</td>
</tr>
{/foreach}
</table>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}
{include file="common/pagination.tpl" div_id="stat_requests"}
</div>
<!--content_visitors_pages--></div>
{/capture}

{capture name="title"}{if $smarty.request.client_type == "B"}{__("robot_path")}{else}{__("visitor_path")}{/if}{/capture}
{include file="common/mainbox.tpl" title=$smarty.capture.title content=$smarty.capture.mainbox}



{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox}

{*/if*}