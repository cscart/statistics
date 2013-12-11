<div class="sidebar-row">
    <h6>{__("search")}</h6>
<form action="{""|fn_url}" name="{$key}_filter_form" method="get">
{capture name="simple_search"}
<input type="hidden" name="report" value="{$report_data.report}" />
<input type="hidden" name="reports_group" value="{$reports_group}" />
<input type="hidden" name="selected_section" value="{$report_data.report}" />
{$extra nofilter}

{include file="common/period_selector.tpl" period=$search.period extra="" display="form"}
    <div class="sidebar-field">
        <label for="filter_search_phrase">{__("search_phrase")}:</label>
        <input type="text" name="search_phrase" id="filter_search_phrase" value="{$search.search_phrase}" size="10"/>
    </div>
{/capture}

{capture name="advanced_search"}
    <div class="row-fluid">
        <div class="span6 group form-horizontal">
            <div class="control-group">
                <label class="control-label" for="filter_referrer_url">{__("referrer_url")}:</label>
                <div class="controls">
                    <input type="text" name="referrer_url" id="filter_referrer_url" value="{$search.referrer_url}" size="10"/>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="filter_url">{__("url")}:</label>
                <div class="controls">
                    <input type="text" name="url" id="filter_url" value="{$search.url}" size="10" />
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="filter_page_title">{__("page_title")}:</label>
                <div class="controls">
                    <input type="text" name="page_title" id="filter_page_title" value="{$search.page_title}" size="10"/>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="filter_ip_address">{__("ip_address")}:</label>
                <div class="controls">
                    <input type="text" name="ip_address" id="filter_ip_address" value="{$search.ip_address}" size="10"/>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="filter_browser_name">{__("browser_name")}:</label>
                <div class="controls">
                    <input type="text" name="browser_name" id="filter_browser_name" value="{$search.browser_name}" size="10"/>
                </div>
            </div>
        </div>

        <div class="span6 group form-horizontal">
            <div class="control-group">
                <label class="control-label" for="filter_browser_version">{__("browser_version")}:</label>
                <div class="controls">
                    <input type="text" name="browser_version" id="filter_browser_version" value="{$search.browser_version}" size="10" />
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="filter_operating_system">{__("operating_system")}:</label>
                <div class="controls">
                    <input type="text" name="operating_system" id="filter_operating_system" value="{$search.operating_system}" size="10" />
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="filter_language">{__("language")}:</label>
                <div class="controls">
                    <input type="text" name="language" id="filter_language" value="{$search.language}" size="10" />
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="filter_country">{__("country")}:</label>
                <div class="controls">
                    <input type="text" name="country" id="filter_country" value="{$search.country}" size="10"/>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="filter_exclude_condition">{__("exclude")}:</label>
                <div class="controls">
                    <input type="checkbox" name="exclude_condition" id="filter_exclude_condition" value="Y" {if $search.exclude_condition == "Y"}checked="checked"{/if}/>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="filter_limit">{__("limit")}:</label>
                <div class="controls">
                    <input type="text" name="limit" id="filter_limit" value="{$search.limit}" class="cm-value-integer" />
                </div>
            </div>

        </div>
    </div>
{/capture}

{include file="common/advanced_search.tpl" simple_search=$smarty.capture.simple_search advanced_search=$smarty.capture.advanced_search dispatch=$dispatch view_type="statistics"}

</form>
</div>
