<?php

/**
 * A Forum Category is applied to each forum page in 
 * a has one relation. These will be editable via the
 * {@link ComplexTableField} on the Forum object 
 *
 * @package forum
 */

class ForumCategory extends DataObject {
	
	static $db = array(
		'Title' => 'Varchar(100)'
	);
	
	static $has_many = array(
		'Forums' => 'Forum'
	);
	
	/**
	 * Get the fields for the category edit/ add
	 * in the complex table field popup window. 
	 * 
	 * @see Forum
	 * @return FieldSet
	 */
	function getCMSFields_forPopup() {
		return new FieldSet(
			new TextField('Title')
		);
	}
}
?>