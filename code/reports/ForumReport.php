<?php
/**
 * Forum Reports.
 * These are some basic reporting tools which sit in the CMS for the user to view.
 * No fancy graphing tools or anything just some simple querys and numbers
 * 
 * @package forum
 */

/**
 * Member Signups Report.
 * Lists the Number of people who have signed up in the past months categorized 
 * by month.
 */
class ForumReport_MemberSignups extends SS_Report {

	public function title() {
		return _t('Forum.FORUMSIGNUPS',"Forum Signups by Month");
	}

	public function columns() {

		$fields = array(
			'Month' => array(
				'title' => 'Month'
			),
			'Signups' => array(
				'title' => 'Signups'
			)
		);
		return $fields;
	}

	public function sourceRecords($params, $sort, $limit) {

		$returnSet = new ArrayList();
		$members = DB::query("
			SELECT DATE_FORMAT(\"Created\", '%Y-%m \(%M\)') AS \"Month\", COUNT(\"Created\") AS \"NumberJoined\"
			FROM \"Member\"
			GROUP BY DATE_FORMAT(\"Created\", '%M %Y')
			ORDER BY \"Created\" DESC
		");

		foreach($members->map() as $record => $value) {
			$do = new DataObject();
			$do->Month = $record;
			$do->Signups = $value;
			$returnSet->push($do);
			unset($do);
		}
		return $returnSet;
	}

	/**
	 * Update the report field export button so that correct columns are included.
	 * 
	 * @return GridField
	 */
	public function getReportField() {

		$field = parent::getReportField();

		$config = $field->getConfig();
		$config->removeComponentsByType('GridFieldExportButton');
		$config->addComponent(
			new GridFieldExportButton(
				'after',
				array_combine(array_keys($this->columns()), array_keys($this->columns()))
			)
		);
		$config->removeComponentsByType('GridFieldPrintButton');
		$config->addComponent(
			new GridFieldPrintButton(
				'after',
				array_combine(array_keys($this->columns()), array_keys($this->columns()))
			)
		);

		$field->setConfig($config);
		return $field;
	}

}

/**
 * Member Posts Report.
 * Lists the Number of Posts made in the forums in the past months categorized 
 * by month.
 */
class ForumReport_MonthlyPosts extends SS_Report {

	public function title() {
		return _t('Forum.FORUMMONTHLYPOSTS',"Forum Posts by Month");
	}

	public function columns() {

		$fields = array(
			'Month' => array(
				'title' => 'Month'
			),
			'Posts' => array(
				'title' => 'Posts'
			)
		);
		return $fields;
	}

	public function sourceRecords($params, $sort, $limit) {

		$returnSet = new ArrayList();
		$members = DB::query("
			SELECT DATE_FORMAT(\"Created\", '%Y-%m \(%M\)') AS \"Month\", COUNT(\"Created\") AS \"PostsTotal\"
			FROM \"Post\"
			GROUP BY DATE_FORMAT(\"Created\", '%M %Y')
			ORDER BY \"Created\" DESC
		");
		foreach($members->map() as $record => $value) {
			$do = new DataObject();
			$do->Month = $record;
			$do->Posts = $value;
			$returnSet->push($do);
			unset($do);
		}
		return $returnSet;
	}

	/**
	 * Update the report field export button so that correct columns are included.
	 * 
	 * @return GridField
	 */
	public function getReportField() {

		$field = parent::getReportField();

		$config = $field->getConfig();
		$config->removeComponentsByType('GridFieldExportButton');
		$config->addComponent(
			new GridFieldExportButton(
				'after',
				array_combine(array_keys($this->columns()), array_keys($this->columns()))
			)
		);
		$config->removeComponentsByType('GridFieldPrintButton');
		$config->addComponent(
			new GridFieldPrintButton(
				'after',
				array_combine(array_keys($this->columns()), array_keys($this->columns()))
			)
		);

		$field->setConfig($config);
		return $field;
	}

}