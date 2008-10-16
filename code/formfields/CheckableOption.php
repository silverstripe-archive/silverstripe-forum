<?php

class CheckableOption extends CompositeField {
	protected $childField, $checkbox;
		
	function __construct($checkName, $childField, $value = "", $readonly = false) {
		$this->checkbox = new CheckboxField($checkName, "", $value);
		if($readonly) $this->checkbox->setDisabled(true);
				
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