<?php

class CheckableOption extends CompositeField {
	protected $childField, $checkbox;
		
	function __construct($checkName, $childField, $value = "", $readonly = false) {
		if( $readonly )
			$this->checkbox = new CheckboxFieldDisabled($checkName, "", $value);
		else
			$this->checkbox = new CheckboxField($checkName, "", $value);
				
		$this->childField = $childField;
		
		$this->children = new FieldSet(
			$this->childField,
			$this->checkbox
		);
	}
	
	function FieldHolder() {
		return FormField::FieldHolder();
	}
	
	function Title() {
		return $this->childField->Title();
	}
	
	function Field() {
		return $this->childField->Field() . ' ' . $this->checkbox->Field();
	}
}

?>