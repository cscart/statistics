{capture name="tabsbox"}

{capture name="mainbox"}
<div id="content_visitors_log">
{capture name="extra"}
<input type="hidden" name="client_type" value="{$search.client_type}" />
<input type="hidden" name="section" value="{$smarty.request.section}" />
{/capture}

{if $text_conditions}
    <h4>{__("conditions")}</h4>
<div class="form-horizontal">
    {foreach from=$text_conditions key="lang_var" item="cond"}
        <div class="control-group">
            <label class="control-label">{__($lang_var)}</label>
            <div class="controls">
            {if $clear_conditions.$lang_var}
                {assign var="clear_url" value=$config.current_url|fn_query_remove:$clear_conditions.$lang_var}
            {else}
                {assign var="clear_url" value=$config.current_url|fn_query_remove:"report":"object_code"}
                {foreach from=$clear_conditions item="cond_sign"}
                    {assign var="clear_url" value=$clear_url|fn_query_remove:$cond_sign}
                {/foreach}
            {/if}
            <p class="shift-top">{$cond nofilter} <a href="{$clear_url}"><i alt="{__("remove_this_item")}" title="{__("remove_this_item")}" class="icon-trash hand"></i></a></p>
            </div>
        </div>
    {/foreach}
</div>
{/if}

{include file="addons/statistics/views/statistics/components/visitors.tpl" visitors_log=$statistics_data.visitors_log}
<!--content_visitors_log--></div>
{/capture}
{capture name="title"}{if $search.client_type == "B"}{__("robots_log")}{else}{__("visitors_log")}{/if}{/capture}

{capture name="sidebar"}
    {include file="common/saved_search.tpl" dispatch="statistics.visitors" view_type="statistics"}
    {include file="addons/statistics/views/statistics/components/search_form.tpl" key="visitors" extra=$smarty.capture.extra report_data=$statistics_data dispatch="statistics.visitors"}
{/capture}

{include file="common/mainbox.tpl" title=$smarty.capture.title content=$smarty.capture.mainbox sidebar=$smarty.capture.sidebar}

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox}