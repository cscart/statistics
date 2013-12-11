<div id="visitors_list">

{if $no_sort != "Y"}
    {assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
    {assign var="c_icon" value="<i class=\"exicon-`$search.sort_order_rev`\"></i>"}
    {assign var="c_dummy" value="<i class=\"exicon-dummy\"></i>"}
{/if}

{if $visitors_log}
<table id="visitors_log_table" width="100%" class="table">
<thead>
<tr>
    <th width="15%">
    {if $hide_extra_button != "Y"}
        <span name="plus_minus" id="on_st" alt="{__("expand_collapse_list")}" title="{__("expand_collapse_list")}" class="hand cm-combinations-visitors"><i class="exicon-expand"></i></span>
        <span name="minus_plus" id="off_st" alt="{__("expand_collapse_list")}" title="{__("expand_collapse_list")}" class="hand hidden cm-combinations-visitors"><i class="exicon-collapse"></i></span>
    {/if}
        {if $no_sort != "Y"}<a data-ca-target-id="visitors_list" href="{"`$c_url`&sort_by=date&sort_order=`$search.sort_order_rev`"|fn_url}" class="cm-ajax">{/if}{__("date")} {if $search.sort_by == "date"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}{if $no_sort != "Y"}</a>{/if}</th>
    <th class="center" width="15%">
        {__("pages")}</th>
    <th width="15%">
        {if $no_sort != "Y"}<a data-ca-target-id="visitors_list" href="{"`$c_url`&sort_by=ip&sort_order=`$search.sort_order_rev`"|fn_url}" class="cm-ajax ">{/if}{__("ip")}{if $search.sort_by == "ip"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}{if $no_sort != "Y"}</a>{/if}/ {if $no_sort != "Y"}<a data-ca-target-id="visitors_list" href="{"`$c_url`&sort_by=proxy&sort_order=`$search.sort_order_rev`"|fn_url}" class="cm-ajax">{/if}{__("proxy")}{if $search.sort_by == "proxy"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}{if $no_sort != "Y"}</a>{/if}</th>
    {if $smarty.request.client_type == "B"}
    <th width="20%">
        {if $no_sort != "Y"}<a data-ca-target-id="visitors_list" href="{"`$c_url`&sort_by=robot&sort_order=`$search.sort_order_rev`"|fn_url}" class="cm-ajax ">{/if}{__("robot")}{if $search.sort_by == "robot"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}{if $no_sort != "Y"}</a>{/if}</th>
    {else}
    <th width="20%">
        {if $no_sort != "Y"}<a data-ca-target-id="visitors_list" href="{"`$c_url`&sort_by=os&sort_order=`$search.sort_order_rev`"|fn_url}" class="cm-ajax">{/if}{__("operating_system")}{if $search.sort_by == "os"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}{if $no_sort != "Y"}</a>{/if}</th>
    <th width="20%">
        {if $no_sort != "Y"}<a data-ca-target-id="visitors_list" href="{"`$c_url`&sort_by=browser&sort_order=`$search.sort_order_rev`"|fn_url}" class="cm-ajax ">{/if}{__("browser")}{if $search.sort_by == "browser"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}{if $no_sort != "Y"}</a>{/if}</th>
    {/if}
    <th width="15%">
        {if $no_sort != "Y"}<a data-ca-target-id="visitors_list" href="{"`$c_url`&sort_by=country&sort_order=`$search.sort_order_rev`"|fn_url}" class="cm-ajax ">{/if}{__("country")}{if $search.sort_by == "country"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}{if $no_sort != "Y"}</a>{/if}</th>
</tr>
</thead>
{foreach from=$visitors_log item="visitor" name="visitors"}
<tr>
    <td class="nowrap">
        {if $hide_extra_button != "Y"}
            <span name="plus_minus" id="on_visitors_log_{$smarty.foreach.visitors.iteration}" alt="{__("expand_collapse_list")}" title="{__("expand_collapse_list")}" class="hand cm-combination-visitors"><i class="exicon-expand"></i></span>
            <span name="minus_plus" id="off_visitors_log_{$smarty.foreach.visitors.iteration}" alt="{__("expand_collapse_list")}" title="{__("expand_collapse_list")}" class="hand hidden cm-combination-visitors"><i class="exicon-collapse"></i></span>
        {/if}
        {$visitor.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
    </td>
    <td class="center nowrap">
        {assign var="return_current_url" value=$config.current_url|escape:url}
        {if $runtime.action != "pages"}<a href="{"statistics.visitor_pages?client_type=`$search.client_type`&stat_sess_id=`$visitor.sess_id`&return_url=`$return_current_url`"|fn_url}">{/if}{$visitor.requests_count|default:1}{if $runtime.action != "pages"}</a>{/if}</td>
    <td class="nowrap"><span>{$visitor.host_ip}</span> / {if $visitor.proxy_ip}{$visitor.proxy_ip}{else}-{/if}</td>
    {if $smarty.request.client_type == "B"}
    <td>{if $visitor.robot}{$visitor.robot}{else}{__("undefined")}{/if}</td>
    {else}
    <td class="nowrap">
        {if $visitor.os}
            {if $visitor.os == "Windows"}
                <img src="{$images_dir}/os/os_windows.gif" width="16" height="16" border="0" alt="{$visitor.os}" title="{$visitor.os}" align="top" />
            {elseif $visitor.os == "Mac"}
                <img src="{$images_dir}/os/os_mac.gif" width="16" height="16" border="0" alt="{$visitor.os}" title="{$visitor.os}" align="top" />
            {elseif $visitor.os == "Linux"}
                <img src="{$images_dir}/os/os_linux.gif" width="16" height="16" border="0" alt="{$visitor.os}" title="{$visitor.os}" align="top" />
            {/if}
            {$visitor.os}
        {else}
            {__("undefined")}
        {/if}
        </td>
    <td class="nowrap">
        {if $visitor.browser}
            {assign var="browser" value=$visitor.browser|lower|replace:"internet ":""}
            {if $browser == "explorer" || $browser == "firefox" || $browser == "mozilla" || $browser == "chrome" || $browser == "netscape" || $browser == "safari" || $browser == "opera"}
                <img src="{$images_dir}/browsers/browser_{$browser}.gif" width="16" height="16" border="0" alt="{$visitor.browser}" title="{$visitor.browser}" align="top" />
            {/if}
            {$visitor.browser} {$visitor.browser_version}
        {else}
            {__("undefined")}
        {/if}</td>
    {/if}
    <td><i class="flag flag-{$visitor.country_code|default:"01"|lower}"></i>&nbsp;{if $visitor.country}{$visitor.country}{elseif $visitor.country_code}{$visitor.country_code}{else}{__("undefined")}{/if}</td>
</tr>
<tr id="visitors_log_{$smarty.foreach.visitors.iteration}" {if $hide_extra_button != "Y"}class="hidden"{/if}>
    <td colspan="{if $smarty.request.client_type == "B"}6{else}7{/if}">

        <dl class="dl-horizontal">
            <dt>{__("entry_page")}:</dt>
            <dd>
                {if $visitor.url}<a href="{$visitor.url}" title="{$visitor.url}">{$visitor.url|truncate:110:"..."}</a>{else}{__("undefined")}{/if}
                <p>{__("page_title")}:&nbsp;{if $visitor.title}{$visitor.title}{else}-{/if}</p>
            </dd>

            <dt>{__("current_page")}:</dt>
            <dd>
                {if $visitor.current_url}<a href="{$visitor.current_url}" title="{$visitor.current_url}">{$visitor.current_url|truncate:110:"..."}</a>{else}-{/if}
                <p>
                    {__("page_title")}:&nbsp;{if $visitor.current_title}{$visitor.current_title}{else}-{/if}
                </p>
            </dd>

            <dt>{__("referrer")}:</dt>
            <dd>
                {if $visitor.referrer}<a href="{$visitor.referrer}" title="{$visitor.referrer}">{$visitor.referrer|truncate:110:"..."}</a>{else}-{/if}
                {if $visitor.phrase}<p>{__("phrase")}:&nbsp;{$visitor.phrase nofilter}</p>{/if}
            </dd>

            <dt>{__("user_agent")}:</dt>
            <dd>
                {if $visitor.user_agent}{$visitor.user_agent}{else}-{/if}
            </dd>

            <dt>{__("language")}:</dt>
            <dd>
            {if $visitor.language}
                {$visitor.language}
            {elseif $visitor.client_language}
                {$visitor.client_language}
            {else}
                {__("undefined")}
            {/if}
            </dd>

            {if $smarty.request.client_type != "B"}
                <dt>{__("screen")}:</dt>
                <dd>{$visitor.screen_x|default:0}x{$visitor.screen_y|default:0} ({$visitor.color|default:0})</dd>
            {/if}
        </dl>
    </td>
</tr>
{/foreach}
</table>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

<!--visitors_list--></div>
