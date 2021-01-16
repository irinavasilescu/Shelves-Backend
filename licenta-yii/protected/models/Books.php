<?php

/**
 * This is the model class for table "books".
 *
 * The followings are the available columns in table 'books':
 * @property string $id
 * @property string $book_name
 * @property string $file_name
 * @property string $image_name
 * @property string $author
 * @property string $s3_folder
 * @property string $category
 * @property double $stars
 * @property double $visits
 */
class Books extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'books';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('stars', 'visits', 'numerical'),
			array('book_name, file_name, image_name, author', 'length', 'max'=>200),
			array('s3_folder, category', 'length', 'max'=>100),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, book_name, file_name, image_name, author, s3_folder, category, stars, visits', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'book_name' => 'Book Name',
			'file_name' => 'File Name',
			'image_name' => 'Image Name',
			'author' => 'Author',
			's3_folder' => 'S3 Folder',
			'category' => 'Category',
			'stars' => 'Stars',
            'visits' => 'Visits'
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id,true);
		$criteria->compare('book_name',$this->book_name,true);
		$criteria->compare('file_name',$this->file_name,true);
		$criteria->compare('image_name',$this->image_name,true);
		$criteria->compare('author',$this->author,true);
		$criteria->compare('s3_folder',$this->s3_folder,true);
		$criteria->compare('category',$this->category,true);
		$criteria->compare('stars',$this->stars);
		$criteria->compare('visits',$this->visits);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Books the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
