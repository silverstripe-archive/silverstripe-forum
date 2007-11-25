<?php

class ForumAdmin extends LeftAndMain{

	static $tree_class = 'Post';

	public function init() {
		parent::init();
	}

	public function Link($action=null) {
		return "admin/forum/$action";
	}


	/**
	 * Return the entire site tree as a nested set of ULs
	*/
	public function SiteTreeAsUL() {
		$link = $this->Link();
		$forums = DataObject::get("Forum");
		if($forums&&$forums->count()){
			$ret .= "<ul>";
			foreach($forums as $forum){
				$ret .= <<<HTML
				<li id="record-$forum->ID" class="Forum closed">Forum: $forum->Title
HTML;
				$ret .= "<ul>";

				$topics = $forum->getTopicsByStatus('Moderated');
				if($topics&&$topics->count()){
					$ret .= "<li>Moderated Topics";
					$ret .= "<ul>";
					foreach($topics as $topic){
						$ret .= <<<HTML
						<li id="record-$topic->ID" class="Post unexpanded closed">
						<a class="contents" href="$link
HTML;
						$ret .= <<<HTML
						show/$topic->ID">Topic: $topic->Title</a>
						</li>
HTML;
					}
					$ret .= "</ul>";
					$ret .= "</li>";
				}

				$topics = $forum->getTopicsByStatus('Awaiting');
				if($topics&&$topics->count()){
					$ret .= "<li>Awaiting Topics";
					$ret .= "<ul>";
					foreach($topics as $topic){
						$ret .= <<<HTML
						<li id="record-$topic->ID" class="Post unexpanded closed">
						<a class="contents" href="$link
HTML;
						$ret .= <<<HTML
						show/$topic->ID">Topic: $topic->Title</a>
						</li>
HTML;
					}
					$ret .= "</ul>";
					$ret .= "</li>";
				}

				$topics = $forum->getTopicsByStatus('Rejected');
				if($topics&&$topics->count()){
					$ret .= "<li>Rejected Topics";
					$ret .= "<ul>";
					foreach($topics as $topic){
						$ret .= <<<HTML
						<li id="record-$topic->ID" class="Post unexpanded closed">
						<a class="contents" href="$link
HTML;
						$ret .= <<<HTML
						show/$topic->ID">Topic: $topic->Title</a>
						</li>
HTML;
					}
					$ret .= "</ul>";
					$ret .= "</li>";
				}

				$topics = $forum->getTopicsByStatus('Archived');
				if($topics&&$topics->count()){
					$ret .= "<li>Archived Topics";
					$ret .= "<ul>";
					foreach($topics as $topic){
						$ret .= <<<HTML
						<li id="record-$topic->ID" class="Post unexpanded closed">
						<a class="contents" href="$link
HTML;
						$ret .= <<<HTML
						show/$topic->ID">Topic: $topic->Title</a>
						</li>
HTML;
					}
					$ret .= "</ul>";
					$ret .= "</li>";
				}

				$ret .="</ul>";
				$ret .= "</li>";
			}
			$ret .= "</ul>";
		}

		return $ret;
	}

	function getEditForm($id) {
		if(!is_numeric($id))
			return;

		$topic = DataObject::get_by_id("Post", $id);

		$fields = (method_exists($topic, 'getCMSFields'))
			? $topic->getCMSFields()
			: new FieldSet();

		if(!$fields->dataFieldByName('ID')) {

			$fields->push($idField = new HiddenField("ID","ID", $id));
			$idField->setValue($id);
		}

		$actions = $topic->getCMSActions();
		$form = new Form($this, "EditForm", $fields, $actions);

		$form->loadDataFrom($topic);

		return $form;
	}

	function save($urlParams, $form){
		if(is_numeric($urlParams['ID'])) {
			$post = DataObject::get_by_id("Post", $urlParams['ID']);
			$post->update($_POST);
			$post->write();

			FormResponse::status_message("Saved", "good");
			return FormResponse::respond();
		}
	}

	function archive($urlParams, $form){
		if(is_numeric($urlParams['ID'])) {
			$post = DataObject::get_by_id("Post", $urlParams[ID]);
			$post->update($_POST);
			$post->Status = 'Archived';
			$post->write();

			FormResponse::status_message("Archived", "good");
			return FormResponse::respond();
		}
	}
}

?>