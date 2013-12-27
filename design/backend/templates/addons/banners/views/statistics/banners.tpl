{capture name="mainbox"}
    {capture name="table_chart"}
    {if $banners}
    <table width="100%" class="table">
    <thead>
        <tr>
            <th>{__("banner")}</th>
            <th>{__("clicks")}</th>
            <th>{__("views")}</th>
            <th>{__("conversion")}</th>
            <th>&nbsp;</th>
        </tr>
    </thead>
    {foreach from=$banners item="banner"}
    <tr>
        <td>{$banner.banner}</td>
        <td>{$banners_statistics[$banner.banner_id].C.number|default:"0"}</td>
        <td>{$banners_statistics[$banner.banner_id].V.number|default:"0"}</td>
        <td>{$banners_statistics[$banner.banner_id].conversion|default:0}%</td>
        <td>
            <div class="hidden-tools">
                {capture name="tools_items"}
                    <li>{btn type="dialog" text=__("details") title=__("statistics") href="statistics.banner_stats?banner_id=`$banner.banner_id`&time_from=`$search.time_from`&time_to=`$search.time_to`" target_id="banner_stats_`$banner.banner_id`"}</li>
                {/capture}
                {dropdown content=$smarty.capture.tools_items}
            </div>
        </td>
    </tr>
    {/foreach}
    </table>
    {else}
        <p class="no-items">{__("no_data")}</p>
    {/if}

    {/capture}
    {include file="addons/statistics/views/statistics/components/select_charts.tpl" chart_table=$smarty.capture.table_chart chart_type=$chart_type applicable_charts=""}
{/capture}

{capture name="sidebar"}
    {include file="addons/statistics/views/statistics/components/search_form.tpl" key=$runtime.action dispatch="statistics.banners" hide_advanced=true}
{/capture}

{include file="common/mainbox.tpl" title="{__("statistics")}: {__("banners")}" content=$smarty.capture.mainbox sidebar=$smarty.capture.sidebar select_languages=true}