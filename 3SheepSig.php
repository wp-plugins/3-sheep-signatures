<?php
/*
Plugin Name: 3 Sheep Signatures
Plugin URI: http://3sheep.co.uk
Description: Add a custom signature to the bottom of posts with the author's information. Based on http://www.dagondesign.com/articles/add-signature-plugin-for-wordpress
Author: 3 Sheep Ltd
Version: 1.00
Author URI: http://3sheep.co.uk
*/

$tssig_version = '1.00';


// Setup defaults if options do not exist

add_option('tssig_count', 1); // The number of signaures
add_option('tssig_data0', 'Written by %FIRST% %LAST% - <a href="%URL%">Visit Website</a>');	// Signature data

function tssig_add_option_pages() {
	if (function_exists('add_options_page')) {
		add_options_page("3 Sheep Signatures", '3 Sheep Signatures', '', __FILE__, 'tssig_options_page');
	}		
}

function tssig_trim_sig($sig) {
	return trim($sig, "*");
}

function tssig_alterCount($oldCount, $newCount) {
	if ($oldCount < $newCount) {
		for ($pos = $oldCount; $pos < $newCount; ++$pos) {
			add_option('tssig_data' . $pos ,'Written by %FIRST% %LAST% - <a href="%URL%">Visit Website</a>');	// Signature data
			update_option('tssig_data' . $pos ,'Written by %FIRST% %LAST% - <a href="%URL%">Visit Website</a>');	// Signature data
		}
	} else if ($oldCount > $newCount) {
		for ($pos = $newCount; $pos < $oldCount; ++$pos) {
			delete_option('tssig_data' . $pos);
		}
	}
}

function tssig_options_page() {

	global $tssig_version;
	$oldSigCount = get_option('tssig_count');

	if (isset($_POST['set_defaults'])) {
		echo '<div id="message" class="updated fade"><p><strong>';

		update_option('tssig_count', 1);
		update_option('tssig_data0', 'Written by %FIRST% %LAST% - <a href="%URL%">Visit Website</a>');	// Signature data

		echo 'Default Options Loaded!';
		echo '</strong></p></div>';

	} else if (isset($_POST['info_update'])) {
		$sig_count = (int)$_POST["tssig_count"];
		echo '<div id="message" class="updated fade"><p><strong>';

		update_option('tssig_count', $sig_count);

		update_option('tssig_data0', '*' . (string)$_POST["tssig_data0"] . '*');
		for ($pos = 1; $pos < $sig_count; ++$pos) {
			update_option('tssig_data' . $pos, '*' . (string)$_POST["tssig_data" . $pos] . '*');
		}
		echo 'Configuration Updated!';
		echo '</strong></p></div>';

	} 
	tssig_alterCount($oldSigCount, get_option('tssig_count'));
	?>

	<div class=wrap>

	<h2>3 Sheep Signatures v<?php echo $tssig_version; ?></h2>

	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	<input type="hidden" name="info_update" id="info_update" value="true" />

	<label for="tssign_count">Number of Signatures</label>
	<input type="text" name="tssig_count" value="<? echo get_option('tssig_count'); ?>" />
	<h3>Signatures</h3>
	<div style="padding: 0 0 0 11px;">
		<p><strong>Primary Signature</strong> - Trigger with [tssig] <br />
		<textarea name="tssig_data0" cols="80" rows="4"><?php echo htmlspecialchars(stripslashes(tssig_trim_sig(get_option('tssig_data0')))) ?></textarea>
		</p>
		
		<?
		$itemCount = get_option('tssig_count');
		for ($pos = 1; $pos < $itemCount; ++$pos) {
			?>
			<p><strong>Signature <? echo $pos + 1; ?></strong> - Trigger with [tssig id="<? echo $pos + 1; ?>"]<br />
			<textarea name="tssig_data<? echo $pos; ?>" cols="80" rows="4"><?php echo htmlspecialchars(stripslashes(tssig_trim_sig(get_option('tssig_data' . $pos)))) ?></textarea>
			</p>
			<?
		}
		?>

		<strong>Notes:</strong>
		<p>- HTML is allowed<br />
		- <strong>All</strong> newlines will be turned into line breaks<br />
		- CSS can be added to customize the look</p>
		
		<strong>You can use the following variables to display author information:</strong>
		<p>- %LOGIN% - Login name<br />
		- %FIRST% - First name<br />
		- %LAST% - Last name<br />
		- %NICK% - Nickname<br />
		- %EMAIL% - Email address<br />
		- %URL% - Website<br />
		- %DESC% - Description/Bio</p>
	</div>

	<h3>Usage</h3>
	<ul>
	<li>You can add a signature to your post or page by inserting the trigger text given above.</li>
	</ul>

	<div class="submit">
		<input type="submit" name="set_defaults" value="<?php _e('Load Default Options'); ?> &raquo;" />
		<input type="submit" name="info_update" value="<?php _e('Update options'); ?> &raquo;" />
	</div>

	</form>
	</div><?php
}

function tssig_generate($content) {
	global $wpdb, $id, $authordata;
	$the_sig = '';
	extract( shortcode_atts( array('id' => '1'), $content));

	if ($id >= 1 && $id <= get_option('tssig_count')) {
		// Load options
		$tssig_data = get_option('tssig_data' . ($id - 1));
	
		// Get author information
		$a_login = get_the_author_login();		// %LOGIN%
		$a_first = get_the_author_firstname();	// %FIRST% 
		$a_last = get_the_author_lastname();	// %LAST%
		$a_nick = get_the_author_nickname(); 	// %NICK%
		$a_email = get_the_author_email(); 		// %EMAIL%
		$a_url = get_the_author_url();			// %URL%
		$a_desc = get_the_author_description();	// %DESC%
	
		// Process signature
		$the_sig = stripslashes(nl2br(tssig_trim_sig($tssig_data)));
		$the_sig = str_replace("%LOGIN%", $a_login, $the_sig);
		$the_sig = str_replace("%FIRST%", $a_first, $the_sig);
		$the_sig = str_replace("%LAST%", $a_last, $the_sig);
		$the_sig = str_replace("%NICK%", $a_nick, $the_sig);
		$the_sig = str_replace("%EMAIL%", $a_email, $the_sig);
		$the_sig = str_replace("%URL%", $a_url, $the_sig);
		$the_sig = str_replace("%DESC%", $a_desc, $the_sig);
			
		$the_sig = '<div class="tssig_wrap">' . do_shortcode($the_sig) . '</div>';
	}
	
	return $the_sig;
}


add_action('admin_menu', 'tssig_add_option_pages');
add_shortcode( 'tssig', 'tssig_generate');

?>
