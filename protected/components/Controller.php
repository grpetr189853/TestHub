<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class Controller extends CController
{
	/**
	 * @var string the default layout for the controller view. Defaults to '//layouts/column1',
	 * meaning using a single column layout. See 'protected/views/layouts/column1.php'.
	 */
	public $layout='//layouts/column1';
	/**
	 * @var array context menu items. This property will be assigned to {@link CMenu::items}.
	 */
	public $menu=array();
	
	protected function performAjaxValidation($model)
	{
		if (isset($_POST['ajax']) && ($_POST['ajax'] === 'register-form' || $_POST['ajax'] === 'change-pass-form')) {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}