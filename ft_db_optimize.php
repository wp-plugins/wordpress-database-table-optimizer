<?php
/*
Plugin Name: WordPress Database Table Optimizer - Figment Thinking 
Plugin URI: http://www.figmentthinking.com/wordpress-database-table-optimizer/
Description: The WordPress Database Table Optimizer plugin will automatically make sure that your WordPress MySQL database tables are always optimized.  Activate the plugin and it will do the rest.
Version: 1.0
Author: Marion Dorsett
Author URI: http://www.FigmentThinking.com/
*/

function figment_thinking_mysql_optimize()
{
	global $wpdb;
	
	# Assign MySQL Tables to new array so we can manipulate them for this plugin.
	$_array = $wpdb->tables;
	
	# Were going to generate a string to list all fo the MySQL tables
	$mysql_tables = '';
	
	# If our $_array var_type isn't an array assign it to an array.
	if(!is_array($_array)) { $_array = array($_array); } // end if
	
	# Loop through the array and create the string we need.  Be sure to ad the dynamic prefix to the table names
	if(is_array($_array))  { foreach($_array as $mysql_table) { $mysql_tables .= "`" . $wpdb->prefix . $mysql_table . "`,"; } /* end foreach */ } // end if
	
	# We don't need the trailing comma so we can remove it.
	$mysql_tables = rtrim($mysql_tables, ',');
	
	# Execute query to optimize the MySQL tables.
	$wpdb->query("OPTIMIZE TABLE " . mysql_real_escape_string($mysql_tables));
	
	# All done.
	return;
} // end figment_thinking_mysql_optimize

# Set Action to execute plugin
add_action('init', 'figment_thinking_mysql_optimize');

# Define Admin page for plugin
add_action('admin_menu', 'figment_thinking_mysql_optimize_pages');
function figment_thinking_mysql_optimize_pages() 
{
    # Add a new submenu under Manage:
    add_management_page('Optimize Database', 'Optimize Database', 10, 'ftoptimize', 'figment_thinking_mysql_optimize_manage_page');
} // end figment_thinking_mysql_optimize_pages

# Data Sizes
function figment_thinking_size_readable($size, $unit = null, $retstring = null, $si = true)
{
	/**
	 * Return human readable sizes
	 *
	 * @author      Aidan Lister <aidan@php.net>
	 * @version     1.1.0
	 * @link        http://aidanlister.com/repos/v/function.size_readable.php
	 * @param       int    $size        Size
	 * @param       int    $unit        The maximum unit
	 * @param       int    $retstring   The return string format
	 * @param       int    $si          Whether to use SI prefixes
	 */
    // Units
    if ($si === true) {
        $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
        $mod   = 1000;
    } else {
        $sizes = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
        $mod   = 1024;
    }
    $ii = count($sizes) - 1;
 
    // Max unit
    $unit = array_search((string) $unit, $sizes);
    if ($unit === null || $unit === false) {
        $unit = $ii;
    }
 
    // Return string
    if ($retstring === null) {
        $retstring = '%01.2f %s';
    }
 
    // Loop
    $i = 0;
    while ($unit != $i && $size >= 1024 && $i < $ii) {
        $size /= $mod;
        $i++;
    }
 
    return sprintf($retstring, $size, $sizes[$i]);
}

# figment_thinking_mysql_optimize_manage_page() displays the page content for the Optimize Database submenu
function figment_thinking_mysql_optimize_manage_page() 
{
	global $wpdb;
	
	# Is there any overhead?
	$overhead = 0;
?>
<div class="wrap"> 
    <h2>Database Table Optimizer</h2>
<?php
	if(is_array($wpdb->tables))
	{
		foreach($wpdb->tables as $ft_table_name)
		{
			$_temp = $wpdb->get_row("SHOW TABLE STATUS WHERE `Name` LIKE '" . mysql_real_escape_string($wpdb->prefix . $ft_table_name) . "'");
			$overhead = $overhead + (int)$_temp->Data_free;
			
			$_data[$ft_table_name] = $_temp;
		} // end foreach
		
		asort($_data);
		
		if($overhead)
		{
?>
			<div style="color: #ffffff; background-color:#FF6600; height: 20px; line-height: 1.5em; padding: 8px; border: solid 1px #990000; margin: 10px 0px;">Oh no! Some of your database tables aren't optimized.</div>
<?php	
		} // end if
		else
		{
?>
			<div style="color: #ffffff; background-color:#66CC33; height: 20px; line-height: 1.5em; padding: 8px; border: solid 1px #009900; margin-bottom: 10px; margin: 10px 0px;">All of your database tables are optimized.</div>
<?php
		} // end else
?>
	<h3>Database Tables</h3>
	<table class="widefat">
	<thead>
	<tr>
		<th scope="col">Table</th>
		<th scope="col" nowrap="nowrap" style="width: 1%;">Rows</th>
		<th scope="col" nowrap="nowrap" style="width: 1%;">Size</th>
		<th scope="col" nowrap="nowrap" style="width: 1%;">Overhead</th>
	</tr>
	</thead>
	<tbody>
<?php
		if(is_array($_data))
		{
			foreach($_data as $ft_table_name => $_temp)
			{
				// $_temp = $wpdb->get_row("SHOW TABLE STATUS WHERE `Name` LIKE '" . mysql_real_escape_string($wpdb->prefix . $ft_table_name) . "'");
?>
		<tr>
			<td><?php echo $ft_table_name; ?></td>
			<td align="right" nowrap="nowrap"><?php echo (int)$_temp->Rows; ?></td>
			<td align="right" nowrap="nowrap"><?php echo figment_thinking_size_readable(($_temp->Data_length + $_temp->Index_length), null, null, false); ?></td>
			<td align="right" nowrap="nowrap"><?php echo figment_thinking_size_readable($_temp->Data_free, null, null, false); ?></td>
		</tr>
<?php
			} // end foreach
?>
	</tbody>
	</table>
<?php
		} // end if
	} // end if
?>
</div>
<?php
} // end figment_thinking_mysql_optimize_manage_page
?>