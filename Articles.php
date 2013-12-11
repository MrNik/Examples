<?php

class Articles extends CActiveRecord {

	public $createDateInt;
	public $editDateInt;
	public $pubDateInt;

	public function tableName() {
		return 'articles';
	}

	public function rules() {
		return array(
			array('title, create_date', 'required'),
			array('published, user_id, pub_date, create_date, cat_id, edit_date', 'numerical', 'integerOnly'=>true),
			array('title, seolink', 'length', 'max'=>255),
			array('body, meta_desc, meta_keys', 'safe'),
			array('id, title, body, meta_desc, meta_keys, seolink, published, user_id, pub_date, cat_id, edit_date', 'safe', 'on'=>'search'),
		);
	}

	public function behaviors() {
		$cat = Category::model()->findByPk($this->id);
		if($cat && $cat->parent()->find()->id) {
			$parent_cat = $cat->parent()->find();
			$root_link = $parent_cat->seolink;
		} 
		$parent_link = $cat->seolink;
	    return array(
	        array(
	            'class'=>'ext.seo.components.SeoRecordBehavior',
	            'route'=>'articles/view',
	            'params'=>array('seolink'=>$this->seolink, 'root_link'=>$root_link, 'parent_link'=>$parent_link),
	        ),
	    );
	}

	public function beforeSave() {
		if($this->cat_id == false) $this->published = 0;
		return true;
	}

	public function afterFind() {
		$this->createDateInt = DataUtils::getDateFromTime($this->create_date, DataUtils::DATE_FORMAT);
		$this->editDateInt = DataUtils::getDateFromTime($this->edit_date, DataUtils::DATE_FORMAT);
		$this->pubDateInt = DataUtils::getDateFromTime($this->pub_date, DataUtils::DATE_FORMAT);
	}

	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'title' => 'Заголовок',
			'body' => 'Текст',
			'meta_desc' => 'Описание',
			'meta_keys' => 'Ключевые слова',
			'seolink' => 'Ссылка',
			'published' => 'Публикация',
			'user_id' => 'Автор',
			'pub_date' => 'Дата публикации',
			'cat_id' => 'Категория',
			'edit_date' => 'Дата редактирования',
			'create_date' => 'Дата создания'
		);
	}

	public function search() {
		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('body',$this->body,true);
		$criteria->compare('meta_desc',$this->meta_desc,true);
		$criteria->compare('meta_keys',$this->meta_keys,true);
		$criteria->compare('seolink',$this->seolink,true);
		$criteria->compare('published',$this->published);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('pub_date',$this->pub_date);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function getByCat($cat_id, $onlyPublished=false) {
		$criteria=new CDbCriteria;
		$criteria->addCondition('cat_id='.$cat_id);
		if($onlyPublished) $criteria->addCondition('published=1');
		return $this->findAll($criteria);
	}

	public function getBySeolink($seolink) {
		$criteria=new CDbCriteria;
		$criteria->addCondition('seolink=:seolink');
		$criteria->params[':seolink']=$seolink;
		return $this->find($criteria);
	}

	public function getFullUrl($id=false) {
		if($id) {
			$article = $this->findByPk($id);
		} else {
			$article = $this;
		}
		$cat = Category::model()->findByPk($article->cat_id);
		if($cat && $cat->parent()->find()->id) {
			$root_link = $cat->parent()->find()->seolink;
		} 
		$parent_link = $cat->seolink;
		$fullUrl = ($root_link) ? 
					$root_link.'/'.$parent_link.'/'.$article->seolink.'.html' : 
					$parent_link.'/'.$article->seolink.'.html';
		
		return $fullUrl;
	}

	public function getList() {
		$criteria=new CDbCriteria;
		return $this->findAll($criteria);
	}
}
