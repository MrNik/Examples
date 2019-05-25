<?php

namespace backend\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\widgets\ActiveForm;
use yii\imagine\Image;
use yii\web\UploadedFile;

use common\models\Event;
use common\models\search\EventSearch;
use common\models\EventBlock;
use backend\models\EditorModel;
use common\models\blocks\items\BlockGalleryImage;
use common\models\blocks\items\BlockFactItem;
use common\models\blocks\items\BlockCardItem;
use backend\models\forms\CropForm;
/**
 * EventController implements the CRUD actions for Event model.
 */
class EventController extends CController
{  

    public function actionIndex()
    {
        $searchModel = new EventSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        //$dataProvider->pagination->pageSize = 50;
        $dataProvider->sort = [
            'defaultOrder' => ['id' => SORT_DESC]
        ];

        $editorModels = EditorModel::find()->where(['<', 'updated_at', time() - 120])->all();
        foreach ($editorModels as $editorModel) {
            $editorModel->delete();
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new Event model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Event();
        $model->loadDefaultValues();
        $blockModelsArray = [];
        $cropFormArray = $this->getCropForms();

        $post = Yii::$app->request->post();

        if ($model->load($post)) {
            $transaction = Yii::$app->db->beginTransaction();

            $loadBlockModels = $this->loadBlockModels($post);
            $blockModelsArray = $loadBlockModels['models'];

            $this->loadCroppedImages($cropFormArray);

            $validationArr = ArrayHelper::merge(
                ActiveForm::validateMultiple($blockModelsArray),
                ActiveForm::validate($model)
            );

            if(empty($validationArr)) {
                try  {
                    $success = $model->save();
                    foreach ($blockModelsArray as $key => $blockModel) {
                        if($success) {
                            $blockModel->imageNamePrefix = $model->id;
                            $success = $blockModel->save();

                            $eventBlock = new EventBlock;
                            $eventBlock->event_id = $model->id;
                            $eventBlock->model = $blockModel->formName();
                            $eventBlock->block_id = $blockModel->id;
                            $eventBlock->order = $blockModel->order;
                            $eventBlock->anchor = $blockModel->anchor;

                            $eventBlock->save();
                        }
                    }

                    if($success) {
                        $transaction->commit();

                        $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
                        if($model->imageFile) {
                            $root = __DIR__ . '/../../frontend/web';
                            $path = '/uploads/';
                            if(!file_exists($root.$path)) {
                                mkdir($root.$path, 0775, true);
                            }

                            $model->origin_image = $path.$model->id.'_origin'.'.'.$model->imageFile->extension;
                            $model->save(false, ['origin_image']);

                            $model->imageFile->saveAs($root.$model->origin_image);

                            if(isset(Yii::$app->webdavFs)) {                    
                                $content = file_get_contents($root.$model->origin_image);

                                Yii::$app->webdavFs->put('events/'.$model->origin_image, $content);
                            }
                        }

                        foreach ($cropFormArray as $key => $cropForm) {
                            $this->saveCropForm($cropForm, $key, $model);
                        }

                        Yii::$app->session->setFlash("success", 'Данные успешно обновлены');

                        return $this->redirect(['update', 'id' => $model->id]);
                    } else {
                        $transaction->rollBack();
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                }
            }
        } 

        return $this->render('create', [
            'model' => $model,
            'blockModelsArray' => $blockModelsArray,
            'cropFormArray' => $cropFormArray,
        ]);
    }

    /**
     * Updates an existing Event model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id) {
        $model = $this->findModel($id);
        $blockIDsOld = [];
        $blockModelsArray = [];
        $cropFormArray = $this->getCropForms($model);

        foreach ($model->eventBlocks as $eventBlock) {
            $blockIDsOld[$eventBlock->model][] = $eventBlock->block_id;
            $block = $eventBlock->block;
            $block->order = $eventBlock->order;
            $block->anchor = $eventBlock->anchor;
            $blockModelsArray[] = $block;
        }

        $post = Yii::$app->request->post();

        if ($model->load($post)) {
            $transaction = Yii::$app->db->beginTransaction();

            $loadBlockModels = $this->loadBlockModels($post);
            $blockModelsArray = $loadBlockModels['models'];
            $blockIDs = $loadBlockModels['blockIDs'];

            $this->loadCroppedImages($cropFormArray);
            //\yii\base\Model::loadMultiple($cropFormArray, $post);

            $validationErrors = ArrayHelper::merge(
                ActiveForm::validateMultiple($blockModelsArray),
                ActiveForm::validate($model)
            );

            if(empty($validationErrors)) {
                try  {
                    $success = $model->save();
                    foreach ($blockModelsArray as $key => $blockModel) {
                        if($success) {
                            $success = $blockModel->save();

                            if($blockModel->eventBlock === null) {
                                $eventBlock = new EventBlock;
                                $eventBlock->event_id = $model->id;
                                $eventBlock->model = $blockModel->formName();
                                $eventBlock->block_id = $blockModel->id;
                            } else {
                                $eventBlock = $blockModel->eventBlock;
                            }

                            $eventBlock->order = $blockModel->order;
                            $eventBlock->anchor = $blockModel->anchor;

                            $eventBlock->save();
                        }
                    }

                    foreach ($this->check_diff_multi($blockIDsOld, $blockIDs) as $key => $ids) {
                        foreach ($ids as $id) {
                            $class = 'common\models\blocks\\'.$key;
                            $class::findOne($id)->delete();
                        }
                    }

                    if($success) {
                        $transaction->commit();

                        $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
                        if($model->imageFile) {
                            $root = __DIR__ . '/../../frontend/web';
                            $path = '/uploads/';
                            if(!file_exists($root.$path)) {
                                mkdir($root.$path, 0775, true);
                            }

                            $oldFile = $model->origin_image;
                            $model->origin_image = $path.$model->id.'_origin'.'.'.$model->imageFile->extension;
                            $model->save(false, ['origin_image']);

                            $model->imageFile->saveAs($root.$model->origin_image);

                            if(isset(Yii::$app->webdavFs)) {                    
                                $content = file_get_contents($root.$model->origin_image);

                                Yii::$app->webdavFs->put('events/'.$model->origin_image, $content);
                            }
                        }

                        foreach ($cropFormArray as $key => $cropForm) {
                            $this->saveCropForm($cropForm, $key, $model);
                        }

                        Yii::$app->session->setFlash("success", 'Данные успешно обновлены');

                        return $this->redirect(['update', 'id' => $model->id]);
                    } else {
                        $transaction->rollBack();
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                }
            }
        }

        $editorModel = EditorModel::find()->where(['model' => 'Event', 'model_id' => $id])->andWhere(['not', ['editor_id' => Yii::$app->user->id]])->orderBy('updated_at DESC')->one();
        if($editorModel !== null) {
            if($editorModel->updated_at + 120 < time()) {
                $editorModel->delete();
                $editorModel = null;
            } else {
                echo '<h2>Внимание! Событие в данный момент редактируется другим пользователем ('.$editorModel->editor->name.')!</h2>';
                exit;
            }
        } 

        return $this->render('update', [
            'model' => $model,
            'blockModelsArray' => $blockModelsArray,
            'editorModel' => $editorModel,
            'cropFormArray' => $cropFormArray,
        ]);
    }

    public function loadCroppedImages($cropFormArray) 
    {
        $post = Yii::$app->request->post();

        foreach ($cropFormArray as $cropForm) {
            $cropForm->attributes = $post['CropForm']['Event'][$cropForm->attribute];
        }

        return $cropFormArray;
    }

    protected function saveCropForm($cropForm, $key, $event) 
    {
        $root = __DIR__ . '/../../frontend/web';
        $path = '/uploads/';
        if(!file_exists($root.$path)) {
            mkdir($root.$path, 0775, true);
        }

        $cropForm->imageFile = UploadedFile::getInstanceByName("CropForm[Event][$cropForm->attribute][imageFile]");
        if($cropForm->imageFile) {
            $originFileName = $path.$event->id.'_origin_'.$cropForm->attribute.'.'.$cropForm->imageFile->extension;
            $cropForm->imageFile->saveAs($root.$originFileName, false);
        } else {
            $cropForm->imageFile = UploadedFile::getInstance($event, 'imageFile');
            $originFileName = $event->origin_image;
        }

        if($cropForm->imageFile) {
            $attribute = $cropForm->attribute;
            $fileName = $path.$event->id.'_'.$attribute.'_'.$cropForm->imageWidth.'x'.$cropForm->imageHeight.'_'.date('d_m_Y_H:i:s', time()).'.'.$cropForm->imageFile->extension;
            $event->$attribute = $fileName;
            $event->save(false, [$attribute]);

            copy($root.$originFileName, $root.$fileName);

            if($cropForm->imageFile->type !== 'image/svg+xml') {
                $imgWidth = getimagesize($root.$fileName)[0];
                
                if($imgWidth < $cropForm->imageWidth) {
                    Image::getImagine()->open($root.$originFileName)->resize(new \Imagine\Image\Box($cropForm->imageWidth, $cropForm->imageHeight))->save($root.$fileName);
                } else {
                    Image::crop($root.$fileName, $cropForm->width, $cropForm->height, [$cropForm->x, $cropForm->y])
                        ->save($root.$fileName);

                    Image::thumbnail($root.$fileName, $cropForm->imageWidth, $cropForm->imageHeight)
                        ->save($root.$fileName);
                }

                if($event->watermarkParams($cropForm) && in_array($attribute, ['socials_image_url', 'socials_image_url_fb', 'socials_image_url_tw'])) {
                    Yii::$app->image->drawWatermarks($root.$fileName, $event->watermarkParams($cropForm));
                }
            }

            if(isset(Yii::$app->webdavFs)) {
                $content = file_get_contents($root.$fileName);
                unlink($root.$fileName);
                
                Yii::$app->webdavFs->put('events/'.$fileName, $content);
            }
        }
    }

    public function getCropForms($event = null)
    {
        $cropForms = [];

        $sizes = [];
        if($event) {
            $sizes['main_page_image_url'] = ['header' => 'Главная (десктоп)', 'attribute' => 'main_page_image_url', 'w' => $event->mainPageImageSizes[$event->size][0], 'h' => $event->mainPageImageSizes[$event->size][1], 'sizeRelated' => true];
        } else {
            $sizes['main_page_image_url'] = ['header' => 'Главная (десктоп)', 'attribute' => 'main_page_image_url', 'w' => (new Event)->mainPageImageSizes[1][0], 'h' => (new Event)->mainPageImageSizes[1][1], 'sizeRelated' => true];          
        }
        
        $sizes['main_page_mobile_image_url'] = ['header' => 'Главная (мобилка)', 'attribute' => 'main_page_mobile_image_url', 'w' => 290, 'h' => 190];
                
        $sizes['mobile_image_url'] = ['header' => 'Страница события (мобилка)', 'attribute' => 'mobile_image_url', 'w' =>  1000, 'h' => 550];
        $sizes['image_url'] = ['header' => 'Страница события (десктоп)', 'attribute' => 'image_url', 'w' => 1600, 'h' => 600];
        
        $sizes['small_image_url'] = ['header' => 'Блок связанных', 'attribute' => 'small_image_url', 'w' => 250, 'h' => 140];     
        
        $sizes['socials_image_url'] = ['header' => 'Cоц.сети', 'attribute' => 'socials_image_url', 'w' => 968, 'h' => 504];
        $sizes['socials_image_url_fb'] = ['header' => 'Поделиться Facebook', 'attribute' => 'socials_image_url_fb', 'w' => 1200, 'h' => 628];
        $sizes['socials_image_url_tw'] = ['header' => 'Поделиться Twitter', 'attribute' => 'socials_image_url_tw', 'w' => 1074, 'h' => 480];

        foreach ($sizes as $s) {
            $cropForm = new CropForm;
            $cropForm->imageWidth = $s['w'];
            $cropForm->imageHeight = $s['h'];
            $cropForm->attribute = $s['attribute'];
            $cropForm->header = $s['header'];
            $cropForm->sizeRelated = isset($s['sizeRelated']) ? true : false;
            $cropForms[] = $cropForm;
        }

        return $cropForms;
    }
    
    /**
     * Deletes an existing Event model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        $redirect = Yii::$app->request->referrer ? Yii::$app->request->referrer : ['/event'];
        return $this->redirect($redirect);
    }

    public function actionAddBlock($blockClass, $i) {
        if(Yii::$app->request->isAjax) {
            $model = new $blockClass;

            return $this->renderAjax('_blocks/template', [
                'model' => $model,
                'view' => $model->view,
                'i' => $i,
            ]);
        }
    }

    public function actionAddGalleryImage($i, $key) {
        if(Yii::$app->request->isAjax) {
            return $this->renderAjax('_blocks/_block_gallery_image', [
                'model' => new BlockGalleryImage,
                'i' => $i,
                'key' => $key,
            ]);
        }
    }

    public function actionAddFactItem($i, $key) {
        if(Yii::$app->request->isAjax) {
            return $this->renderAjax('_blocks/_block_fact_item', [
                'model' => new BlockFactItem,
                'i' => $i,
                'key' => $key,
            ]);
        }
    }

    public function actionAddCardItem($i, $key) {
        if(Yii::$app->request->isAjax) {
            return $this->renderAjax('_blocks/_block_card_item', [
                'model' => new BlockCardItem,
                'i' => $i,
                'key' => $key,
            ]);
        }
    }

    protected function loadBlockModels($post) {
        $blockIDs = [];
        $blockModelsArray = [];
        $keyCount = 0;

        foreach ($post as $key => $blockDataArray) {
            $class = 'common\models\blocks\\'.$key;
            if(class_exists($class)) {
                foreach ($blockDataArray as $i => $blockData) {
                    if(isset($blockData['id'])) {
                        $blockIDs[$key][] = $blockData['id'];
                        $blockModel = $class::findOne($blockData['id']);
                    } else {
                        $blockModel = new $class;
                    }

                    $blockModel->attributes = $blockData;

                    if(isset($post['CropForm'][$key][$i])) {
                        $blockModel->cropImage = $post['CropForm'][$key][$i];
                    }
                    $blockModel->key = $keyCount;

                    if(isset($blockModel->itemsModelName)) {
                        $blockModel->loadItems($post[$blockModel->itemsModelName][$i]);
                    }

                    $blockModelsArray[$i] = $blockModel;
                    $blockModel = null;

                    $keyCount++;
                }
            }
        }

        return ['models' => $blockModelsArray, 'blockIDs' => $blockIDs];
    }

    public function actionOrder() {
        $post = Yii::$app->request->post();
        if(Yii::$app->request->isAjax && isset($post) && !empty($post['EventBlock'])) {
            foreach ($post['EventBlock'] as $id => $eventBlock) {
                $eventBlock = EventBlock::findOne($id);
                $eventBlock->order = $post['EventBlock'][$id]['order'];
                $eventBlock->save(false, ['order']);
            }

            return 'success';
        }
    }

    public function actionEditorModel($id) {
        if(Yii::$app->request->isAjax) {
            $em = EditorModel::find()->where(['model' => 'Event', 'model_id' => $id, 'editor_id' => Yii::$app->user->id])->one();
            if($em === null) {
                $em = new EditorModel;
                $em->editor_id = Yii::$app->user->id;
                $em->model_id = $id;
                $em->model = 'Event';
            }
            $em->updated_at = time();

            $em->save();
            return $em->updated_at;
        }
    }

    public function actionCheckEdited()
    {   
        if(Yii::$app->request->isAjax) {
            $eventIds = EditorModel::find()->select('model_id')->where(['>', 'updated_at', time() - 120])->andWhere(['not', ['editor_id' => Yii::$app->user->id]])->column();
            
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ['ids' => $eventIds];
        }
    }

    /**
     * Finds the Event model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Event the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Event::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    protected function check_diff_multi($array1, $array2) {
        $result = [];

        foreach($array1 as $key => $val) {
            if(is_array($val)) {
                if(isset($array2[$key])) {
                    $result[$key] = $this->check_diff_multi($val, $array2[$key]);
                } else {
                    foreach ($val as $k => $v) {
                        echo $v;
                        $result[$key][] = $v;
                    }
                }
            } else {
                if(!in_array($val, $array2)) {
                    $result[] = $val;
                }
            }
        }

        return $result;
    }
}
