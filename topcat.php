<?php
/*
Plugin Name: Top Cat
Plugin URI: http://www.thunderguy.com/semicolon/wordpress/top-cat-wordpress-plugin/
Description: Specify a principal category for posts.
Version: 1.0.2
Author: Bennett McElwee
Author URI: http://www.thunderguy.com/semicolon/

$Revision: 1953 $

Copyright (C) 2005 Bennett McElwee

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the
Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

The GNU General Public License is also available at
http://www.gnu.org/copyleft/gpl.html

Bennett McElwee
bennett at thunderguy dotcom
*/

/*
DEVELOPMENT NOTES

All template tags begin with "topcat_"
All internal globals begin with "tguy_tc_" (for Thunderguy Top Cat)
Tested with PHP 4.3.8, WordPress 1.5.
*/

/*	==================================================
	Template functions
	Displaying the main category of posts.
	All functions can be called in The Loop with no arguments, or anywhere
	with a post ID as argument.
*/

function topcat_get_the_main_category_id($post_id = 0) {
/*	Return ID of the given post's main category.
*/
	global $post, $wpdb;
	if (0 != $post_id) {
		$category_id = $wpdb->get_row("SELECT post_category FROM $wpdb->posts WHERE ID = $post_id");
	} else {
		$category_id = $post->post_category;
	}
	return $category_id;
}

function topcat_get_the_main_category($post_id = 0) {
/*	Return the given post's main category name.
*/
	return get_the_category_by_ID(topcat_get_the_main_category_id($post_id));
}

function topcat_the_main_category_id($post_id = 0, $before = '', $after = '') {
/*	Write the ID of the given post's main category if there is one, otherwise nothing.
*/
	$cat_id = topcat_get_the_main_category_id($post_id);
	if (0 < $cat_id) {
		echo $before . $cat_id . $after;
	}
}

function topcat_the_main_category($post_id = 0, $before = '', $after = '') {
/*	Write the name of the given post's main category if there is one, otherwise nothing.
*/
	$cat = topcat_get_the_main_category($post_id);
	if ('' != $cat) {
		echo $before . $cat . $after;
	}
}


/*	==================================================
	Machinery to set and display the main category of posts in the
	admin interface.
*/

// Add the radio buttons to the edit form (using DOM). This goes in the
// footer but might be better off in the edit form actions.
add_filter('admin_footer', 'tguy_tc_add_radios');

// Add the old main category to the edit form
add_action('simple_edit_form',   'tguy_tc_add_hidden_field');
add_action('edit_form_advanced', 'tguy_tc_add_hidden_field');

// Save the main category when saving a new post or editing an old one
add_action('save_post', 'tguy_tc_save_main_category');
add_action('edit_post', 'tguy_tc_save_main_category');

function tguy_tc_add_radios() {
/*	If the current page is post.php, add radio buttons for all
	categories in addition to the checkboxes. Select one of the
	radio buttons based on the value of the 'old_main_category'
	hidden form field.
*/
	if(strpos($_SERVER['REQUEST_URI'], 'post.php'))
	{
	// TODO Move the useful createNamedElement function to a library
?>
<script language="JavaScript" type="text/javascript"><!--

function createNamedElement(type, name) {
	var element = null;
	// First try the IE way; if this fails then use the standard way
	try {
		element = document.createElement('<'+type+' name="'+name+'">');
	} catch (e) {
	}
	if (!element) {
		element = document.createElement(type);
		element.name = name;
	}
	return element;
}

function addRadio(catBox) {
// Add a "main category" radio button before the given checkbox
// (which sits inside a label). Return true on success, else false.
	var cat = catBox.value;
	var catLabel = catBox.parentNode;
	var theButton = createNamedElement('input', 'main_category');
	if (!theButton) {
		return false;
	}
	theButton.type = 'radio';
	theButton.value = cat;
	theButton.id = "main_category-"+cat;
	//theButton.onclick = if radio is now checked, check the corresponding checkbox
	//theButton.title = help text? does this work?

	// insert radio button before label
	catLabel.style.display = 'inline';
	catLabel.parentNode.insertBefore(theButton, catLabel);

	return true;
}

// Get all the checkboxes into a separate array now, since the getElementsByTagName()
// return value will be mutated when we start adding the radio buttons.
var catBoxes = new Array();
var categoryDiv = document.getElementById("categorydiv");
if (categoryDiv) {
	var inputs = categoryDiv.getElementsByTagName("input");
	for (var i = 0; i < inputs.length; ++i) {
		var input = inputs[i];
		// Make sure it really is a category checkbox
		if (input.type == "checkbox" && input.id == "category-"+input.value) {
			catBoxes[catBoxes.length] = input;
		}
	}
}

// Add a radio button before each checkbox
for (var i = 0; i < catBoxes.length; ++i) {
	addRadio(catBoxes[i]);
}

// Select the main category's radio button
var topCatElement = document.getElementById("old_main_category");
if (topCatElement) {
	var topCat = topCatElement.value;
	var topCatRadio = document.getElementById("main_category-"+topCat);
	if (topCatRadio) {
		topCatRadio.checked = true;
	}
}

//--></script>
<?php
	}
}

/*	Add a hidden field with id="old_main_category" and value read from
	the database to the current page.
*/
function tguy_tc_add_hidden_field() {
	global $wpdb, $post_ID;
	$topCat = $wpdb->get_var("SELECT post_category FROM $wpdb->posts WHERE ID='$post_ID'");
	echo '<input type="hidden" name="old_main_category" id="old_main_category" value="'.$topCat.'" />';
}

/*	Save the POSTed value of the main category to the given post ID.
*/
function tguy_tc_save_main_category($post_ID) {
	global $wpdb;
	if (isset($_POST['main_category'])) {
		$main_category = (int) $_POST['main_category'];
		if (0 < $main_category) {
			$wpdb->query("UPDATE $wpdb->posts SET post_category = '" . $main_category . "' WHERE ID = '$post_ID'");
		}
	}
}

?>