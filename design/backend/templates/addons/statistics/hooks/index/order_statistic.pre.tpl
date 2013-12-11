{if "statistics.reports"|fn_check_view_permissions:"GET"}
    <div class="dashboard-table dashboard-table-top-search">
        <h4>{__("stat_top_search_terms")}</h4>
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th width="50%">{__("stat_search_term")}</th>
                    <th width="20%" class="center">{__("qty")}</th>
                    <th width="30%" class="center">{__("stat_results")}</th>
                </tr>
                </thead>
            </table>
            <div class="scrollable-table">
            <table class="table table-striped">
                <tbody>
                    {foreach from=$search_terms item="term"}
                        {$query = http_build_query($term.search_string)}
                        {$url = "products.search?`$query`"|fn_url:'C'}
                        <tr>
                            <td width="50%"><a href="{$url}" target="_blank">{if $term.search_string.q}{$term.search_string.q}{else}-&nbsp;{__("empty")}&nbsp;-{/if}</a></td>
                            <td width="20%" class="center">{$term.count}</td>
                            <td width="30%" class="center"><a href="{$url}" target="_blank">{$term.quantity}</a></td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
            </div>
        </div>
    </div>
{/if}