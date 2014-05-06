<?php

$plugin['name'] = 'yab_cf_article_list';
$plugin['allow_html_help'] = 0;
$plugin['version'] = '0.1';
$plugin['author'] = 'Tommy Schmucker';
$plugin['author_uri'] = 'http://www.yablo.de/';
$plugin['description'] = 'List a custom_field in admin article list';
$plugin['order'] = '5';
$plugin['type'] = '3';

if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001);
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002);

$plugin['flags'] = '';

if (!defined('txpinterface'))
{
	@include_once('zem_tpl.php');
}

# --- BEGIN PLUGIN CODE ---
/**
 * Config function holder to avoid some globals
 * Can later be changed to receive config from database id needed
 *
 * @param  string $name name of the config
 * @return string
 */
function yab_cfal_config($name)
{
	global $prefs;

	// config begin
	$config = array(
		'custom_field'  => '1', // number of the custom field
		'name_for_list' => '' // Title in table head and search field, default custom_field name
	);
	// config end

	// get default name of custom field
	if (!$config['name_for_list'])
	{
		$config['name_for_list'] = @$prefs['custom_1_set'];
	}

	return $config[$name];
}

// admin callbacks
if (@txpinterface == 'admin')
{
	register_callback(
		'yab_cfal_js',
		'admin_side',
		'body_end'
	);

	register_callback(
		'yab_cfal_search',
		'admin_criteria',
		'list_list'
	);

	// our AJAX endpoint
	$ajax = gps('yab_cfal_ajax');

	// is AJAX endpoint is called
	if ($ajax)
	{
		// echo our stuff
		echo yab_cfal_ajax(ps('ids'));
		// but no textpattern stuff
		exit();
	}
}


/**
 * Search by our custom field
 * Adminside Textpattern callback function
 * Hooked in the list_list search and extends the search $criteria
 *
 * @return void
 */
function yab_cfal_search()
{
	$cf = 'custom_'.yab_cfal_config('custom_field');

	$method = gps('search_method');
	$crit   = gps('crit');

	if ($method === $cf and $crit != '')
	{
		$verbatim = preg_match('/^"(.*)"$/', $crit, $m);
		$crit_escaped = doSlash($verbatim ? $m[1] : str_replace(array('\\','%','_','\''), array('\\\\','\\%','\\_', '\\\''), $crit));
		return " AND $cf LIKE '%$crit_escaped%'";
	}
}

/**
 * Echo the JavaScript
 * Adminside Textpattern callback function
 * Fired at body_end in ui
 *
 * @return void
 */
function yab_cfal_js()
{
	global $event;

	$ajax_uri = hu.'textpattern/?yab_cfal_ajax=1';
	$name     = yab_cfal_config('name_for_list');
	$cf       = yab_cfal_config('custom_field');
	$method   = gps('search_method');
	$crit     = gps('crit');
	$verbatim = preg_match('/^"(.*)"$/', $crit, $m);
	$crit_escaped = doSlash($verbatim ? $m[1] : str_replace(array('\\','%','_','\''), array('\\\\','\\%','\\_', '\\\''), $crit));

	// be sure we are article list area
	if ($event != 'list')
	{
		return false;
	}

	$thead = yab_cfal_config('name_for_list');
	$js     = <<<EOF
<script>
(function() {

	var method = '$method';
	var selected = '';
	var crit     = '$crit_escaped';

	if (method == 'custom_$cf')
	{
		selected = ' selected="selected"';
		$('option:selected', '#list-search').removeAttr('selected');
		$('.input-medium').val(crit);
	}

	var option = '<option value="custom_$cf"' + selected + '>$name</option>';
	$('option:first-child', '#list-search').after(option);

	var th = '<th class="custom_field">$thead</th>';
	$('th.id').after(th);

	// get ids
	var ids = [];
	var tdid = $('td.id', '.txp-list tr');

	tdid.each(function() {
		var article_id = $(this).children('a').text();
		ids.push(article_id);
	});

		$.ajax({
			type:   'POST',
			url:    '$ajax_uri',
			cache:  false,
			data :  {'ids':ids}, // POST json
			success: function(result) {
				result = result ? result : '';
				data = JSON.parse(result);

				tdid.each(function() {
					var that = this;
					var this_id = $(this).children('a').text();
					$.each(data, function(i) {
						if (data[i].id == this_id) {
							var this_cf = data[i].cf;
							$(that).after('<td class="custom_field">' + this_cf + '</td>');
						}
					});
				});
			}
		});

})();
</script>
EOF;

	echo $js;
}

/**
 * Return ID and custom_field values as JSON
 *
 * @param  array  $ids POSTed array of Textpattern IDs
 * @return string JSON string
 */
function yab_cfal_ajax($ids)
{
	$json = '';
	$ids = array_map('intval', $ids);
	$ids = implode(',', $ids);
	$ids = doSlash($ids);
	$cf = yab_cfal_config('custom_field');

	$rs = safe_rows("ID as id, custom_$cf as cf", 'textpattern', "ID IN ($ids)");

	if ($rs)
	{
		$json .= json_encode($rs);
	}

	return $json;
}

# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
h1. yab_cf_article_list

p. List a custom_field in admin article list and search article list for this custom field.

p. *Version:* 0.1

h2. Table of contents

# "Plugin requirements":#help-section02
# "Configuration":#help-config03
# "Changelog":#help-section10
# "License":#help-section11
# "Author contact":#help-section12

h2(#help-section02). Plugin requirements

Minimum requirements:

* Textpattern 4.4.x

h2(#help-config03). Configuration

Install and activate the plugin.

The function yab_cfal_config() in the plugin code contains an array with some config values:

@'custom_field'@: Number of the custom field
Default: 1

@'name_for_list'@: Name of the custom field in the table head and in article search.
Default: empty (The given name for this custom field will be shown.)

h2(#help-section10). Changelog

* v0.1: 2014-05-06
** initial release

h2(#help-section11). Licence

This plugin is released under the GNU General Public License Version 2 and above
* Version 2: "http://www.gnu.org/licenses/gpl-2.0.html":http://www.gnu.org/licenses/gpl-2.0.html
* Version 3: "http://www.gnu.org/licenses/gpl-3.0.html":http://www.gnu.org/licenses/gpl-3.0.html

h2(#help-section12). Author contact

* "Plugin on author's site":http://www.yablo.de/
* "Plugin on GitHub":https://github.com/trenc/yab_cf_article_list
* "Plugin on textpattern forum":http://forum.textpattern.com/
* "Plugin on textpattern.org":http://textpattern.org/
# --- END PLUGIN HELP ---
-->
<?php
}
?>