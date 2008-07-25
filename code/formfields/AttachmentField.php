<?php
/**
 * Allows you to attach files to a DataObject. Uploads via an inline iframe.
 *
 * BIG DUMB NOTE: If you use this field, and want it to work at all well,
 * you need to set the ID of the page/DataObject this attachment is associated with.
 * If you're only just creating it now, you're pretty much SOL - create it BEFORE
 * you call a new AttachmentField, then DataObject::write() it, then call
 * $this->setExtraData(array("PostID" => $newPost->ID)). This means you can upload
 * attachments before you've officially created the associated DataObject
 * for the first time...
 *
 * Of course, this should be re-factored so that it doesn't require you to jump through
 * the aforementioned hoops to make it work
 */
class AttachmentField extends FormField {
	protected $destObj;
	protected $extraData;

	/**
	 * Constructor, @see FormField
	 *
	 * @param string $destObj The object we create to store the file. Should be a sub-class of File.
	 */
	function __construct($name, $title = null, $destObj) {
		$this->destObj = $destObj;

		parent::__construct($name, $title);
	}

	/**
	 * Extra data to be passed to the iframe in the query-string
	 *
	 * @param Array $array The array of extra data to be set on every $this->destObj.
	 *
	 * TODO Add to the array so we can call this method multiple times if necessary
	 */
	function setExtraData($array) {
		if(is_array($array)) $this->extraData = $array;
	}

	/**
	 * The <iframe> field
	 */
	function Field() {
		// Add extra data into the iframe src tag if required
		$this->extraData["DestObj"] = $this->destObj;

		if($this->extraData) {
			foreach($this->extraData as $key => $val) {
				$array[] = "$key=$val";
			}
			$extraData = implode("&amp;", $array); 
		}
return <<<HTML
	<iframe name="AttachmentField_upload" src="AttachmentField_Uploader/uploadiframe/?$extraData" id="{$this->form->FormName()}_AttachmentField_{$this->name}" border="0"></iframe>
HTML;
	}
}

class AttachmentField_Uploader extends Controller {
	function UploadForm() {
		$fields = new FieldSet(
			new FileField("Files[0]" , ""),
			new HiddenField("action_doUpload", "", "1"),
			new LiteralField('UploadButton',"
				 <input type='submit' value='"._t('AttachmentField.UPLOADBUTTON','Upload')."' name='action_upload' id='Form_UploadForm_action_upload' class='action' /> 
			"),
			new LiteralField('MultifileCode',"
				<div id='Form_UploadForm_FilesList'></div>
				<script>
					var multi_selector = new MultiSelector($('Form_UploadForm_FilesList'), null, $('Form_UploadForm_action_upload'));
					multi_selector.addElement($('Form_UploadForm_Files-0'));
				</script>
			")
		);

		$actions = new FieldSet();

		// Add any $extraData as HiddenField's
		$extraData = $this->getExtraData();
		if(isset($extraData) && sizeof($extraData)>0) {
			foreach($extraData as $key => $val) {
				$fields->push(new HiddenField(Convert::raw2js($key), Convert::raw2js($key), Convert::raw2js($val)));
			}
		}

//		Debug::show($fields);

		return new Form($this, "UploadForm", $fields, $actions);
	}

	function uploadiframe() {
		Requirements::clear();

		Requirements::javascript("jsparty/prototype.js");
		Requirements::javascript("jsparty/behaviour.js");
		Requirements::javascript("jsparty/prototype_improvements.js");
		// TODO Fix this (merge back to jsparty/multifile/multifile.js maybe? I only changed one line (the last one :P)...)
		Requirements::javascript("forum/javascript/multifile.js");
		Requirements::css("jsparty/multifile/multifile.css");

		Requirements::css("forum/css/AttachmentField.css");

		return array();
	}

	function doUpload($data, $form) {
		// Ensure we have an object to create
		if(!$_GET['DestObj']) {
			user_error(_t('AttachmentField.NODESTOBJ','A usable destination object could not be found. Check that you\'ve passed argument 3 to AttachmentField::__construct()'), E_USER_ERROR); 
		}

		// Ensure the destination object extends File
		$SAFE_objText = Convert::raw2sql($_GET['DestObj']);
		if(!is_subclass_of($SAFE_objText, 'File')) {
			user_error(sprintf(_t('AttachmentField.NOTSUBCLASS',"'%s'  doesn't sub-class File."),$SAFE_objText), E_USER_ERROR);
		}

		if($data['Files']) {
			foreach($data['Files'] as $param => $files) {
				foreach($files as $key => $val)
				$processedFiles[$key][$param] = $val;
			}

			// Get any passed-through extra data
			$extraData = $this->getExtraData();
			$validFile = false;

			foreach($processedFiles as $file) {
				// Ensure we have a valid filename (Sometimes the multifile javascript will let a blank field through)
				if($file['tmp_name']) {

					// Check that the file can be uploaded
					$extensionIndex = strripos($file['name'], '.');
					$extension = strtolower(substr($file['name'], $extensionIndex + 1));

					if($extensionIndex !== FALSE) list($maxSize, $warnSize) = File::getMaxFileSize($extension);
					else list($maxSize, $warnSize) = File::getMaxFileSize();

					// Check that the file is not too large
					if(File::allowedFileType($extension) && $file['size'] < $maxSize) {
						$validFile = true;
						$obj = new $SAFE_objText();

						if($extraData) {
							foreach($extraData as $key => $val) {
								$SAFE_key = Convert::raw2sql($key);
								$SAFE_val = Convert::raw2sql($val);
								$obj->$SAFE_key = $SAFE_val;
							}
						}

						// Create the folder if it doesn't exist
						// TODO @Sam Is there any reason why Folder::findOrMake doesn't create the physical directory structure as well as the database structure?
						$parentFolder = Folder::findOrMake("Attachments");
						$base = dirname(dirname($_SERVER[SCRIPT_FILENAME]));
						if(!file_exists("$base/assets/Attachments")){
							mkdir("$base/assets/Attachments", 02775);
						}

						// Generate default filename
						$fileName = str_replace(' ', '-',$file['name']);
						$fileName = ereg_replace('[^A-Za-z0-9+.-]+','',$fileName);
						$fileName = ereg_replace('-+', '-',$fileName);
						$fileName = basename($fileName);

						$fileName = "assets/Attachments/$fileName";

						while(file_exists("$base/$fileName")) {
							$i = $i ? ($i+1) : 2;
							$oldFile = $fileName;
							$fileName = ereg_replace('[0-9]*(\.[^.]+$)',$i . '\\1', $fileName);
							if($oldFile == $fileName && $i > 2) user_error("Couldn't fix $fileName with $i", E_USER_ERROR);
						}

						if(file_exists($file['tmp_name']) && copy($file['tmp_name'], "$base/$fileName")) {
							$obj->record['Name'] = null;
							$obj->ParentID = $parentFolder->ID;
							$obj->Name = basename($fileName);
							$obj->write();
						}
					}
				}
			}

			// If no files were successfully uploaded, show an error message
			if(!$validFile) {
				$form->addErrorMessage('UploadButton', _t('AttachmentField.ERRORPROBABLYSIZE','No files were uploaded, probably because of there sizes.'), 'bad'); 
			}
		}

		Director::redirect($this->Link());
	}

	/**
	 * This lets you see a list of all files that have been attached so far.
	 */
	function Attachments() {
		// Create the filter from $this->getExtraData()
		if($extraData = $this->getExtraData()) {
			foreach($extraData as $key => $val) {
				if($key == "DestObj") continue;
				$SQL_key = Convert::raw2sql($key);
				$SQL_val = Convert::raw2sql($val);

				$filter[] = "`$SQL_key` = '$SQL_val'";
			}
		} else {
			$filter = "";
		}

		$SAFE_objText = Convert::raw2sql($_GET['DestObj']);
		if(is_subclass_of($SAFE_objText, 'File')) {
			$doSet = DataObject::get($SAFE_objText, implode(" AND ", $filter));

			if($doSet) return $doSet;
		}

		return null;
	}

	function getExtraData() {
		//Debug::show($_GET);
		if(isset($_GET) && sizeof($_GET)>0) {
			foreach($_GET as $key => $val) {
				if(!in_array($key, array('flush', 'showtemplate', 'buildmanifest', 'executeForm', 'url'))) {
					$return[Convert::raw2js($key)] = Convert::raw2js($val);
				}

			}
		}
		return $return;
	}

	function Link() {
		$extraData = $this->getExtraData();
		if(isset($extraData) && sizeof($extraData)>0) {
			foreach($extraData as $key => $val) {
					$array[] = "$key=$val";
			}
		}
		$extraData = implode("&", $array);

		return "$this->class/uploadiframe/?$extraData";
	}

	function FormObjectLink($name) {
		return $this->Link()."&executeForm=".$name;
	}
}

?>