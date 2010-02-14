<?php

/**
 * Extension to has-one CTF that lets you specify additional values to populate into created records.
 * This is not ideal, it would be much better if this were done lower down, perhaps in ComplexTableField,
 * but we're not living in utopia yet.
 *
 * @author mstephens
 */
class HasOneCTFWithDefaults extends HasOneComplexTableField {
	/**
	 * The same as the parent, except takes an associative array of defaults right at the end.
	 */
	function __construct($controller, $name, $sourceClass, $fieldList, $detailFormFields = null, $sourceFilter = "", $sourceSort = "", $sourceJoin = "", $defaults = null) {
		parent::__construct($controller, $name, $sourceClass, $fieldList, $detailFormFields, $sourceFilter, $sourceSort, $sourceJoin);

		$this->defaultValues = $defaults;
	}

	/**
	 * Very ugly copy of the same method in ComplexTableField, but need a way to inject the extra data into created
	 * objects prior to writing them.
	 */
	function saveComplexTableField($data, $form, $params) {
		$className = $this->sourceClass();
		$childData = new $className();
		$form->saveInto($childData);

		// Populate in the defaults as well.
		foreach ($this->defaultValues as $key => $value)
			$childData->$key = $value;
		$childData->write();

		// Save the many many relationship if it's available
		if(isset($data['ctf']['manyManyRelation'])) {
			$parentRecord = DataObject::get_by_id($data['ctf']['parentClass'], (int) $data['ctf']['sourceID']);
			$relationName = $data['ctf']['manyManyRelation'];
			$componentSet = $parentRecord->getManyManyComponents($relationName);
			$componentSet->add($childData);
		}
		
		if(isset($data['ctf']['hasManyRelation'])) {
			$parentRecord = DataObject::get_by_id($data['ctf']['parentClass'], (int) $data['ctf']['sourceID']);
			$relationName = $data['ctf']['hasManyRelation'];
			
			$componentSet = $parentRecord->getComponents($relationName);
			$componentSet->add($childData);
		}
		
		$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
		
		$closeLink = sprintf(
			'<small><a href="%s" onclick="javascript:window.top.GB_hide(); return false;">(%s)</a></small>',
			$referrer,
			_t('ComplexTableField.CLOSEPOPUP', 'Close Popup')
		);
		
		$message = sprintf(
			_t('ComplexTableField.SUCCESSADD', 'Added %s %s %s'),
			$childData->singular_name(),
			'<a href="' . $this->Link() . '/item/' . $childData->ID . '/edit">' . $childData->Title . '</a>',
			$closeLink
		);
		
		$form->sessionMessage($message, 'good');

		Director::redirectBack();
	}
	
//	function saveInto(DataObject $record) {
//		var_dump($record);
//		die("");
//		foreach ($this->defaultValues as $key => $value)
//			$record->$key = $value;
//		parent::saveInto($record);
//	}
}