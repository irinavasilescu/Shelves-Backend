<?php

class UsersController extends Controller
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column2';

    /**
     * Utilizată pentru returnarea răspunsurilor.
     * Se precizează statusul cererii ("ok" sau "error")
     * mesajul (acțiunea efectuată sau eșuată, după caz)
     * date (dacă este cazul)
     * @param $status
     * @param $message
     * @param string $data
     */
    protected function renderJSON($status, $message, $data='') {
        header('Content-type: application/json');
        $data = json_decode(json_encode($data), true);
        $response['status'] = $status;
        $response['message'] = $message;
        if ($data !== '') $response['data'] = $data;
        echo CJSON::encode($response);
        Yii::app()->end();
    }

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
			'postOnly + delete', // we only allow deletion via POST request
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('index','view','login','signup', 'checklogin', 'logout'),
				'users'=>array('*'),
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('create','update'),
				'users'=>array('@'),
			),
			array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions'=>array('admin','delete'),
				'users'=>array('admin'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model=$this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Users']))
		{
			$model->attributes=$_POST['Users'];
			if($model->save())
				$this->redirect(array('view','id'=>$model->id));
		}

		$this->render('update',array(
			'model'=>$model,
		));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		$this->loadModel($id)->delete();

		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
	}

	/**
	 * Returnează informațiile unui utilizator (cu excepția parolei).
     * Se furnizează id-ul utilizatorului.
	 */
	public function actionIndex()
    {
        if (isset($_GET['id'])) {
            $user_by_id = Users::model()->findByPk($_GET['id']);
            $user = [
                'id' => $user_by_id->id,
                'f_name' => $user_by_id->f_name,
                'l_name' => $user_by_id->l_name,
                'email' => $user_by_id->email,
                'username' => $user_by_id->username
            ];
            echo json_encode($user);
        }
    }

    /**
     * Realizează autentificarea utilizatorului.
     * Se furnizează numele de utilizator și parola.
     */
    public function actionLogin() {
        $model = new LoginForm();
        $req = json_decode(Yii::app()->request->getRawBody(), true);

        if (isset($req['username']) && !(isset($req['pass']))) {
            $this->renderJSON('ok', 'Password not provided');
        }

        $model->username = $req['username'];
        $model->password = hash('sha256', $req['pass']);

        if ($model->login()) {
            $this->renderJSON('ok', "Logged in successfully", Yii::app()->user->getId());
        } else {
            $this->renderJSON('ok', 'Wrong username or password');
        }
        Yii::app()->end(1);
    }

    /**
     * Verifică daca utilizatorul este autentificat sau este
     * doar un vizitator (nu este autentificat).
     */
    public function actionCheckLogin() {
        $this->renderJSON('ok', !Yii::app()->user->isGuest);
    }

    /**
     * Realizează delogarea utilizatorului
     */
    public function actionLogout() {
        Yii::app()->user->logout();
    }

    /**
     * Efectuează crearea unui nou utilizator.
     * Se furnizează informațiile, de către utilizator, în formularul de înregistrare,
     * date care mai apoi se escapează.
     * Se verifică existența unui alt utilizator cu același nume de utilizator sau
     * email ca cele furnizate.
     * În cazul în care există deja, nu se va realiza înregistrarea.
     * Daca numele de utilizator și emailul sunt noi, se va crea un nou cont.
     */
    public function actionSignup()
    {
        $req = json_decode(Yii::app()->request->getRawBody(), true);
        $user = new Users;
        $user->username = addslashes($req['username']);
        $user->pass = addslashes(hash('sha256', $req['pass']));
        $user->f_name = addslashes($req['f_name']);
        $user->l_name = addslashes($req['l_name']);
        $user->email = addslashes($req['email']);

        $queryByUsername = Users::model()->findByAttributes(array('username' => $user->username));
        $queryByEmail = Users::model()->findByAttributes(array('email' => $user->email));

        if (!empty($queryByUsername)) {
            $this->renderJSON('error', 'User already exists');
            return;
        }
        if (!empty($queryByEmail)) {
            $this->renderJSON('error', 'Email already used');
            return;
        }
        if (empty($queryByUsername) && empty($queryByEmail)) {
            if (!filter_var($req['email'], FILTER_VALIDATE_EMAIL)) {
                $this->renderJSON('error', 'Invalid email format');
            }
            $user->save();
            $this->renderJSON('ok', 'User created succesfully');
        }
    }

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model=new Users('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['Users']))
			$model->attributes=$_GET['Users'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Users the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
		$model=Users::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param Users $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='users-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
