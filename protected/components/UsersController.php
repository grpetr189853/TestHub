<?php

class UsersController extends Controller
{
    public $userModel;
    public $index;
    public $layout = '//layouts/column2';

    public function filters()
    {
        return array(
            'accessControl', // perform access control for CRUD operations
            'postOnly + delete' // we only allow deletion via POST request
        );
    }
    
    public function accessRules()
    {
        return array(
            array(
                'allow',
                'actions' => array(
                    'index',
                    'view',
                    'registration',
                    'login',
                    'tests',
                    'socialregistration'
                ),
                'users' => array(
                    '*'
                )
            ),
            array(
                'allow',
                'actions' => array(
                    'logout'
                ),
                'users' => array(
                    '@'
                )
            ),
            array(
                'allow',
                'actions' => array(
                    'admin',
                    'delete',
                    'update'
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
    
    public function actionIndex()
    {
        $dataProvider = new CActiveDataProvider($this->index);
        $this->render('index', array(
            'dataProvider' => $dataProvider
        ));
    }
    
    public function actionAdmin()
    {
        $this->userModel->unsetAttributes(); // clear any default values
        if (isset($_GET[$this->index]))
            $this->userModel->attributes = $_GET[$this->index];
        
        $this->render('admin', array(
            'model' => $this->userModel
        ));
    }
    
    public function actionRegistration()
    {
        $model = $this->userModel;
        
        $this->performAjaxValidation($model);
        
        if (isset($_POST[$this->index])) {
            $post = $_POST[$this->index];
            
            $model->attributes = $post;
            
            if ($model->validate()) {
            	
                $model->save(false);
                
                $confirmModel = new AccountInteraction();
                
                $confirmModel->saveAndSend($model, 'confirm');
                
                if (Yii::app()->session && isset(Yii::app()->session['captchaCash'])) {
                	unset(Yii::app()->session['captchaCash']);
                }
                
                if (Yii::app()->session) {
                	Yii::app()->session['regModel'] = $model;
                }
                
                Users::model()->deleteNotActivated();
                
                $this->redirect(array(
                    'accountInteraction/confirmNotification'
                ));
            }
        }
        
        $this->render('//users/registration', array(
            'model' => $model
        ));
    }
    
    public function actionLogin()
    {
        $service = Yii::app()->request->getQuery('service');
        
        if (isset($service)) {
        	
        	/**
        	 *Так как провайдеры отдают значение гендерной принадлежности пользователя каждый по разному,
        	 *необходимо передать расширению значения, которые допустимы для нашей базы.
        	 *По умолчанию $genderArray = array('female' => 1,'male' => 2,'undefined' => 3);
        	 */
        	
        	$genderArray = array(
        		'female' => 'female',
        		'male' => 'male',
        		'undefined' => 'undefined'
        	);
        	
        	$serviceClass     = Yii::app()->soauth->getClass($service, $genderArray);
        	
        	if($serviceClass->authenticate()) {
        		$socialAttributes = $serviceClass->socialAttributes();
        		
        		$socialModel             = SocialAccounts::model();
        		$socialModel->attributes = $socialAttributes;
        		
        		$oauthModel = $serviceClass->validateSocialModel($socialModel);
        		
        		if (!empty($oauthModel)) {
        			Yii::app()->session['oauth_model'] = $oauthModel;
        			$this->redirect(array(
        					'socialRegistration'
        			));
        		}
        		
        		$this->redirect(array(
        				'site/index'
        		));
        	}
        }
        
        $model            = new LoginForm;
        $model->userClass = $this->userModel;
        
        $this->performAjaxValidation($model);
        
        if (isset($_POST['LoginForm'])) {
            $model->attributes = $_POST['LoginForm'];
            if ($model->validate() && $model->login())
                $this->redirect(array(
                    'site/index'
                ));
        }
        $this->render('//users/login', array(
            'model' => $model
        ));
    }
    
    public function actionDelete()
    {
        $this->loadModel()->delete();
        
        if (!Yii::app()->request->isAjaxRequest) {
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array(
                'admin'
            ));
        }
    }
    
    public function actionUpdate()
    {
        $model = $this->loadModel();
        
        if (isset($_POST[$this->index])) {
            $model->attributes = $_POST[$this->index];
            if ($model->save())
                $this->redirect(array(
                    'view',
                    'id' => $model->id
                ));
        }
        
        $this->render('update', array(
            'model' => $model
        ));
    }
    
    public function actionView()
    {
        $this->render('view', array(
            'model' => $this->loadModel()
        ));
    }
    
    public function loadModel()
    {
        
        if (isset($_GET['id'])) {
            
            $model = $this->userModel->findByPk($_GET['id']);
        }
        
        if ($model === null) {
            throw new CHttpException(404, 'The requested page does not exist.');
        }
        
        return $model;
    }
    
    public function actionSocialRegistration()
    {
        $userModel              = $this->userModel;
        $userModel->scenario    = 'oauth';
        $userModel->isNewRecord = true;
        $oauthModel             = Yii::app()->session['oauth_model'];
        
        $userAttributes        = json_decode($oauthModel->info);
        $userModel->attributes = array(
            'name' => $userAttributes->name,
            'surname' => $userAttributes->surname,
            'gender' => $userAttributes->gender,
            'avatar' => $userAttributes->photo,
        	'active' => 1
        );
        
        $this->performAjaxValidation($userModel);
        
        if (isset($_POST[$this->index])) {
            $post                  = $_POST[$this->index];
            $userModel->attributes = $post;
            
            if ($userModel->validate()) {
                
                $userModel->save(false);
                Users::model()->deleteNotActivated();
                
                $oauthModel->user_id = $userModel->id;
                $oauthModel->save(false);
                
                $identity = UserIdentity::forceLogin($userModel);
                Yii::app()->user->login($identity);
                unset(Yii::app()->session['oauth_model']);
                
                $this->redirect(array(
                    'site/index'
                ));
            }
        }
        
        $this->render('//users/registration', array(
            'model' => $userModel
        ));
    }
}