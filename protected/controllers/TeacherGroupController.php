<?php

class TeacherGroupController extends Controller
{

    public $layout = '//layouts/column2';

    public function actionIndex()
    {
        $dataProvider = new CActiveDataProvider('TeacherGroup');
        $this->render('index', array(
            'dataProvider' => $dataProvider
        ));
    }

    public function accessRules()
    {
        return array(
            array(
                'allow', // allow all users to perform 'index' and 'view' actions
                'actions' => array(
                    'index',
                    'view'
                ),
                'users' => array(
                    '*'
                )
            ),
            array(
                'allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions' => array(
                    'create',
                    'update'
                ),
                'users' => array(
                    '@'
                )
            ),
            array(
                'allow', // allow admin user to perform 'admin' and 'delete' actions
                'actions' => array(
                    'admin',
                    'delete'
                ),
                'users' => array(
                    'admin'
                )
            ),
            array(
                'deny', // deny all users
                'users' => array(
                    '*'
                )
            )
        );
    }

    public function actionCreate()
    {
        $model = new TeacherGroup();
        
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'client-account-create-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
        
        if (isset($_POST['TeacherGroup'])) {
            $model->attributes = $_POST['TeacherGroup'];
            if ($model->validate()) {
                $this->saveModel($model);
                $this->redirect(array(
                    'view',
                    'teacher_id' => $model->teacher_id,
                    'group_id' => $model->group_id
                ));
            }
        }
        $this->render('create', array(
            'model' => $model
        ));
    }

    public function actionDelete($teacher_id, $group_id)
    {
        if (Yii::app()->request->isPostRequest) {
            try {
                // we only allow deletion via POST request
                $this->loadModel($teacher_id, $group_id)->delete();
            } catch (Exception $e) {
                $this->showError($e);
            }
            
            // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
            if (! isset($_GET['ajax']))
                $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array(
                    'index'
                ));
        } else
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
    }

    public function actionUpdate($teacher_id, $group_id)
    {
        $model = $this->loadModel($teacher_id, $group_id);
        
        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);
        
        if (isset($_POST['TeacherGroup'])) {
            $model->attributes = $_POST['TeacherGroup'];
            $this->saveModel($model);
            $this->redirect(array(
                'view',
                'teacher_id' => $model->teacher_id,
                'group_id' => $model->group_id
            ));
        }
        
        $this->render('update', array(
            'model' => $model
        ));
    }

    public function actionAdmin()
    {
        $model = new TeacherGroup('search');
        $model->unsetAttributes(); // clear any default values
        if (isset($_GET['TeacherGroup']))
            $model->attributes = $_GET['TeacherGroup'];
        
        $this->render('admin', array(
            'model' => $model
        ));
    }

    public function actionView($teacher_id, $group_id)
    {
        $model = $this->loadModel($teacher_id, $group_id);
        $this->render('view', array(
            'model' => $model
        ));
    }

    public function loadModel($teacher_id, $group_id)
    {
        $model = TeacherGroup::model()->findByPk(array(
            'teacher_id' => $teacher_id,
            'group_id' => $group_id
        ));
        if ($model == null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    public function saveModel($model)
    {
        try {
            $model->save();
        } catch (Exception $e) {
            $this->showError($e);
        }
    }

    function showError(Exception $e)
    {
        if ($e->getCode() == 23000)
            $message = "This operation is not permitted due to an existing foreign key reference.";
        else
            $message = "Invalid operation.";
        throw new CHttpException($e->getCode(), $message);
    }
}