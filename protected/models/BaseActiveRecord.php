<?php
/**
 * OpenEyes
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2012
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2008-2011, Moorfields Eye Hospital NHS Foundation Trust
 * @copyright Copyright (c) 2011-2012, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */

/**
 * A class that all OpenEyes active record classes should extend.
 *
 * Currently its only purpose is to remove all html tags to
 * prevent XSS.
 */
abstract class BaseActiveRecord extends CActiveRecord implements AuthorisationProvider {

	/**
	 * How long (in seconds) before cached PAS details are considered stale
	 */
	const PAS_CACHE_TIME = 300;

	/**
	 * Audit log
	 */
	public function behaviors() {
		$behaviors = array();
		if(Yii::app()->params['audit_trail']) {
			$behaviors['LoggableBehavior'] = array(
				'class' => 'application.behaviors.LoggableBehavior',
			);
		}
		return $behaviors;
	}
	
	/**
	 * Strips all html tags out of attributes to be saved.
	 * @return boolean
	 */
	protected function beforeSave() {
		$primaryKey = $this->tableSchema->primaryKey;
		foreach ($this->attributes as $name => $value) {
			// The '!empty' check is to prevent it populating NULL values, e.g. episode.end_date was changing from NULL to 0000-00-00 00:00:00.
			if(!empty($value) && ($primaryKey !== $name || (is_array($primaryKey) && !in_array($name, $primaryKey)))) {
				$this->$name = strip_tags($value);
			}
		}
		return parent::beforeSave();
	}
	
	/**
	 * Authorisation name
	 * @return string
	 */
	public static function get_auth_name() {
		return __CLASS__;
	}
	
	/**
	 * Defined Operation items
	 * @return array
	 */
	public static function defined_operations() {
		return array(
			'OprnView'.self::get_auth_name() => 'View '.self::get_auth_name(),
			'OprnEdit'.self::get_auth_name() => 'Edit '.self::get_auth_name(),
			'OprnDelete'.self::get_auth_name() => 'Delete '.self::get_auth_name(),
		);
	}
	
	/**
	 * Defined Task items
	 * @return array
	 */
	public static function defined_tasks() {
		return array();
	}
	
	/**
	 * Is user authorised to view record
	 * @param integer $user User ID
	 * @return boolean
	 */
	public function canView($user_id = null) {
		if(is_integer($user_id)) {
			$user = User::model()->findByPk($user_id);
			if(!$user) {
				throw CException('Invalid user ID:'.$user_id);
			}
		} else {
			$user = Yii::app()->user;
		}
		return $user->checkAccess('OprnView'.self::get_auth_name());
	}
	
	/**
	 * Is user authorised to edit record
	 * @param integer $user User ID
	 * @return boolean
	 */
	public function canEdit($user_id = null) {
		if(is_integer($user_id)) {
			$user = User::model()->findByPk($user_id);
			if(!$user) {
				throw CException('Invalid user ID:'.$user_id);
			}
		} else {
			$user = Yii::app()->user;
		}
		return $user->checkAccess('OprnView'.self::get_auth_name());
	}
	
	/**
	 * Is user authorised to delete record
	 * @param integer $user User ID
	 * @return boolean
	 */
	public function canDelete($user_id = null) {
		if(is_integer($user_id)) {
			$user = User::model()->findByPk($user_id);
			if(!$user) {
				throw CException('Invalid user ID:'.$user_id);
			}
		} else {
			$user = Yii::app()->user;
		}
		return $user->checkAccess('OprnView'.self::get_auth_name());
	}
	
	/**
	 * @param boolean $runValidation
	 * @param array $attributes
	 * @param boolean $allow_overriding - if true allows created/modified user/date to be set and saved via the model (otherwise gets overriden)
	 * @return boolean
	 */
	public function save($runValidation=true, $attributes=null, $allow_overriding=false)
	{
		$user_id = null;

		try {
			if (isset(Yii::app()->user)) {
				$user_id = Yii::app()->user->id;
			}
		} catch (Exception $e) {
		}

		if ($this->getIsNewRecord() || !isset($this->id)) {
			if (!$allow_overriding || $this->created_user_id == 1) {
				// Set creation properties
				if ($user_id === NULL) {
					// Revert to the admin user
					$this->created_user_id = 1;
				} else {
					$this->created_user_id = $user_id;
				}
			}
			if (!$allow_overriding || $this->created_date == "1900-01-01 00:00:00") {
				$this->created_date = date('Y-m-d H:i:s');
			}
		}

		try {
			if (!$allow_overriding || $this->last_modified_user_id == 1) {
				// Set the last_modified_user_id and last_modified_date fields
				if ($user_id === NULL) {
					// Revert to the admin user
					// need this try/catch block here to make older migrations pass with this hook in place
					$this->last_modified_user_id = 1;
				} else {
					$this->last_modified_user_id = $user_id;
				}
			}
			if (!$allow_overriding || $this->last_modified_date == "1900-01-01 00:00:00") {
				$this->last_modified_date = date('Y-m-d H:i:s');
			}
		} catch (Exception $e) {
		}

		return parent::save($runValidation, $attributes);
	}

	/**
	 * Returns a date field in NHS format
	 * @param string $attribute
	 * @return string
	 */
	public function NHSDate($attribute, $empty_string = '-') {
		if($value = $this->getAttribute($attribute)) {
			return Helper::convertMySQL2NHS($value, $empty_string);
		}
	}

	public function NHSDateAsHTML($attribute, $empty_string = '-') {
		if($value = $this->getAttribute($attribute)) {
			return Helper::convertMySQL2HTML($value, $empty_string);
		}
	}
}
