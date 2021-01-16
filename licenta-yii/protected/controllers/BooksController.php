<?php

require 'protected/vendor/autoload.php';
use Aws\S3\S3Client;

class BooksController extends Controller
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

    protected function configureS3Client() {
        $access_key = getenv('AWS_ACCESS_KEY');
        $secret_access_key = getenv('AWS_SECRET_ACCESS_KEY');
        return new Aws\S3\S3Client([
            'version' => 'latest',
            'region' => 'eu-central-1',
            'credentials' => new Aws\Credentials\Credentials($access_key, $secret_access_key)
        ]);
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
				'actions'=>array('index','view','visits', 'indexmostpopular', 'addtoshelf', 'listshelf', 'newbook', 'removefromshelf', 'getbooksignedurl'),
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
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$model=new Books;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Books']))
		{
			$model->attributes=$_POST['Books'];
			if($model->save())
				$this->redirect(array('view','id'=>$model->id));
		}

		$this->render('create',array(
			'model'=>$model,
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

		if(isset($_POST['Books']))
		{
			$model->attributes=$_POST['Books'];
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
	 * Returnează informațiile cărților din tabela books.
     * Se efectuează interogarea tabelei books.
     * Dacă a fost furnizat id-ul cărții, va fi returnată înregistrarea respectivei cărți.
     * Dacă nu a fost furnizat niciun id, se vor returna toate înregistrările din tabela books.
	 */
	public function actionIndex() {
        $books = Yii::app()->db->createCommand('select * from books')->queryAll();
        if (isset($_GET['id'])) $books = Yii::app()->db->createCommand("select * from books where id=" . $_GET['id'])->queryAll();
        echo json_encode($books);
	}

    /**
     * Incrementează numărul de vizite ale unei cărți.
     * Este furnizat id-ul cărții pentru interogarea tabelei și obținerea numărului curent de vizite.
     * Se modifică numărul de vizite cu numărul curent incrementat cu o unitate.
     */
	public function actionVisits() {
        $data = json_decode(Yii::app()->request->getRawBody(), true);
        $book_id = $data['id'];
        $book = Books::model()->findByPk($book_id);
        $current_visits = $book->visits;
        $next_visits = $current_visits + 1;
        Books::model()->updateByPk($book_id, array('visits' => $next_visits));
        $this->renderJSON('ok', 'Field updated successfully');
    }

    /**
     * Returnează cele mai pupulare cărți ale aplicației (cele mai populare 2 cărți
     * din fiecare categorie existentă).
     * Se realizează interogări ale tabelei books în funcție de categoriile existente
     * și se efectuează unificarea acestora.
     */
    public function actionIndexMostPopular() {
        $books = Yii::app()->db->createCommand("
            (select * from books where genre='fantasy' order by visits desc limit 2)
            UNION
            (select * from books where genre='fiction' order by visits desc limit 2)
            UNION
            (select * from books where genre='horror' order by visits desc limit 2)
            UNION
            (select * from books where genre='classics' order by visits desc limit 2)
            UNION
            (select * from books where genre='mistery' order by visits desc limit 2)
            UNION
            (select * from books where genre='comics' order by visits desc limit 2)
            UNION
            (select * from books where genre='dystopia' order by visits desc limit 2)
        ")->queryAll();
        echo json_encode($books);
    }

    /**
     * Adăugarea informațiilor unei noi cărți în tabela books.
     * Se preiau datele introduse de utilizator și se escapează.
     * Se verifică faptul că toate câmpurile au fost completate,
     * apoi se inserează o nouă înregistrare în tabela books.
     */
    public function actionNewbook() {
	    $data = json_decode(Yii::app()->request->getRawBody(), true);
        if (isset($data)) {
            $book_name   = addslashes($data['book_name']);
            $author      = addslashes($data['author']);
            $file_name   = addslashes($data['file_name']);
            $image_name  = addslashes($data['image_name']);
            $s3_folder   = addslashes($data['s3_folder']);
            $genre       = addslashes($data['genre']);
            $description = addslashes($data['description']);
        }
        if (isset($book_name) && isset($author) && isset($file_name) && isset($image_name) && isset($s3_folder) && isset($genre) && isset($description)) {
            Yii::app()->db->createCommand("insert into books(book_name, author, file_name, image_name, s3_folder, genre, stars, description) values
                                          ('$book_name', '$author', '$file_name', '$image_name', '$s3_folder', '$genre', 5, '$description');")->execute();
            $this->renderJSON('ok', 'Book added successfully');
        } else {
            $this->renderJSON('error', 'Something went wrong');
        }
	}

    /**
     * Adăugarea unei cărți la raftul virtual al utilizatorului.
     * Sunt furnizate id-ul utilizatorului și id-ul cărții.
     * Acestea sunt inserate în tabela booksperuser ce conține rafturile
     * tuturor utilizatorilor.
     */
    public function actionAddtoshelf() {
        $data = json_decode(Yii::app()->request->getRawBody(), true);
        $id_book = $data['id_book'];
        $id_user = $data['id_user'];
        try {
            Yii::app()->db->createCommand("insert into booksperuser(id_user, id_book) values (" . $id_user . ", " . $id_book . ");")->execute();
        } catch (CDbException $e) {
            $this->renderJSON('error', $e);
        }
        $this->renderJSON('ok', 'Book added successfully');
    }

    /**
     * Eliminarea unei cărți din raftul virtual al utilizatorului.
     * Sunt furnizate id-ul utilizatorului și id-ul cărții.
     * Se va șterge înregistrarea ce conține aceste id-uri.
     */
    public function actionRemovefromshelf() {
        $data = json_decode(Yii::app()->request->getRawBody(), true);
        $id_book = $data['id_book'];
        $id_user = $data['id_user'];
        try {
            Yii::app()->db->createCommand("delete from booksperuser where id_book = '$id_book' and id_user = '$id_user';")->execute();
        } catch (CDbException $e) {
            $this->renderJSON('error', $e);
        }
        $this->renderJSON('ok', 'Book removed successfully');
    }

    /**
     * Returnează cărțile salvate în raftul virtual al unui utilizator.
     * Este furnizat id-ul utilizatorului al cărui raft trebuie returnat.
     * Se realizează o joncțiune între tabelele books, booksperuser, users
     * pentru obținerea înregistrărilor căutate.
     */
    public function actionListshelf() {
        $id_user = (isset($_GET['id'])) ? $_GET['id'] : null;
        if ($id_user) {
            $result = Yii::app()->db->createCommand("select books.id, books.book_name, books.author, books.file_name, 
                                                     books.image_name, books.s3_folder, books.genre, books.stars,
                                                     books.description, books.visits
                                                     from books inner join booksperuser on (books.id = booksperuser.id_book)
                                                                inner join users        on (users.id = booksperuser.id_user)
                                                     where users.id=" . $id_user . ";")->queryAll();
            echo json_encode($result);
        }
    }

    public function actionGetBookSignedUrl()
    {
        $s3 = $this->configureS3Client();

        // type image or book
        $resource_type = isset($_GET['type']) ? $_GET['type'] : null;
        $book_name = isset($_GET['book_name']) ? $_GET['book_name'] : null;
        $image_name = isset($_GET['image_name']) ? $_GET['image_name'] : null;

        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => 'licentas3',
            'Key' => $resource_type == 'book' ? 'fiction_books/' . $book_name : 'images_books/' . $image_name
        ]);

        $request = $s3->createPresignedRequest($cmd, '+20 minutes');
        $presigned_url = (string)$request->getUri();
        echo json_encode($presigned_url);
    }

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model=new Books('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['Books']))
			$model->attributes=$_GET['Books'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Books the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
		$model=Books::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param Books $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='books-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
