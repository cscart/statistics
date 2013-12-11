{if "statistics.reports"|fn_check_view_permissions:"GET"}
	<td>
	    <div class="dashboard-card">
	        <div class="dashboard-card-title">{__("visits")}</div>
	        <div class="dashboard-card-content">
	            <h3>{$visitors.total}</h3>{$visitors.prev_total}, {if $visitors.total > $visitors.prev_total}+{/if}{$visitors.diff nofilter}%
	        </div>
	    </div>
	</td>
{/if}