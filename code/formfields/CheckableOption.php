<?php

class CheckableOption extends CompositeField {
	protected $childField, $checkbox;
		
	function __construct($checkName, $childField, $value = "", $readonly = false) {
		$this->name = $checkName;
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
	
	function Name() {
		return $this->childField->Name();
	}
	
	function Message() {
		return $this->childField->Message();
	}
	
	function MessageType() {
		return $this->childField->MessageType();
	}
	
	function Title() {
		return $this->childField->Title();
	}
	
	function Field() {
		return $this->childField->Field() . ' ' . $this->checkbox->Field();
	}
}

?>