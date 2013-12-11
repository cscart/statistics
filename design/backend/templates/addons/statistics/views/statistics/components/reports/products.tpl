<div id="content_{$report_data.report}">

    {if $report_data.data}
    <table width="100%" class="table">
    <thead>
    <tr>
        <th width="55%">{__("search_conditions")}</th>
        <th width="15%">{__("date")}</th>
        <th width="15%" class="right">{__("visitors")}</th>
        <th width="15%" class="right">{__("found_products")}</th>
    </tr>
    </thead>
    {foreach from=$report_data.data item="row" key="k"}
    <tr>
        <td>
            {strip}
            <div class="clear">
                <p class="pull-right">&nbsp;<a name="details_{$k}" class="hand" onclick="Tygh.$('#stat_product_search_{$k}').toggle();">{__("details")}&nbsp;&raquo;</a></p>
                <span>{if $row.label.q}{$row.label.q}{else}-&nbsp;{__("empty")}&nbsp;-{/if}</span>
                <p class="text-success">
                    [{if $row.label.match == "exact"}{__("exact_phrase")}{elseif $row.label.match == "all"}{__("all_words")}{else}{__("any_words")}{/if}]
                </p>
             </div>
            
            <div id="stat_product_search_{$k}" class="well well-small hidden">
            {if $row.label.pname || $row.label.pshort || $row.label.pfull || $row.label.pkeywords}
            <p><span>{__("search_in")}:</span>&nbsp;
                {assign var="comma" value=""}
                {if $row.label.pname}
                    {__("product_name")}
                    {assign var="comma" value=",&nbsp;"}
                {/if}
                {if $row.label.pshort}
                    {$comma nofilter}{__("short_description")}
                    {assign var="comma" value=",&nbsp;"}
                {/if}
                {if $row.label.pfull}
                    {$comma nofilter}{__("full_description")}
                    {assign var="comma" value=",&nbsp;"}
                {/if}
                {if $row.label.pkeywords}
                    {$comma nofilter}{__("keywords")}
                {/if}</p>
            {/if}
                
            {if $row.label.feature}
            <p><span>{__("search_by_product_features")}:</span>&nbsp;
                {assign var="comma" value=""}
                {foreach from=$row.label.feature item="feature_id"}
                    {if $product_features.$feature_id.description}
                        {$comma nofilter}{$product_features.$feature_id.description}
                        {assign var="comma" value=",&nbsp;"}
                    {/if}
                {/foreach}</p>
            {/if}
            
            {if $row.label.category}
                <p><span>{__("search_in_category")}:</span>&nbsp;
                {$row.label.category}
                {if $row.label.subcats}&nbsp;[{__("search_in_subcategories")}]{/if}</p>
            {/if}
            
            {if $row.label.pcode}
                <p><span>{__("search_by_sku")}:</span>&nbsp;{$row.label.pcode}</p>
            {/if}
            
            {if $row.label.price_from || $row.label.price_to}
                <p><span>{__("search_by_price")}:</span>&nbsp;{$row.label.price_from|format_price:$currencies.$primary_currency:"price_from_$k"}&nbsp;-&nbsp;{$row.label.price_to|format_price:$currencies.$primary_currency:"price_to_$k"}</p>
            {/if}
            
            {if $row.label.weight_from || $row.label.weight_to}
                <p><span>{__("search_by_weight")}&nbsp;({$settings.General.weight_symbol}):</span>&nbsp;{$row.label.weight_from|default:0}&nbsp;-&nbsp;{$row.label.weight_to|default:0}</p>
            {/if}
            </div>
            
            {/strip}
        </td>
        <td>{$row.date|date_format:$settings.Appearance.date_format}</td>
        <td class="right">
            <a href="{"statistics.visitors?section=products&report=`$report_data.report`&object_code=`$row.md5`"|fn_url}">{$row.count}</a>
        </td>
        <td class="right">
            {if $row.quantity}<a href="{$row.storefront_url}" target="_blank">{/if}{$row.quantity|string_format:"%d"}{if $row.quantity}</a>{/if}</td>
    </tr>
    {/foreach}
    </table>
    {else}
        <p class="no-items">{__("no_data")}</p>
    {/if}

<!--content_{$report_data.report}--></div>
