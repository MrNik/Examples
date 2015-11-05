<?php

use yii\bootstrap\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

?>
<div class="container" style="width: inherit;">
    <div class="row">
        <?php if(count($comments)):?>
            <div class="detailBox">
                <div class="actionBox">
                    <ul class="commentList">
                        <?php foreach ($comments as $comment): ?>
                            <li>
                                <div class="commentText">
                                    <p class=""><?=$comment->comment;?></p> 
                                    <span class="date sub-text"><?= $comment->user->username;?>, <?=$comment->date;?></span>
                                </div>
                            </li>
                        <?php endforeach;?>
                    </ul>
                </div>
            </div>
        <?php endif;?>
    </div>
    <hr>
    <div class="row">
        <?php $form = ActiveForm::begin([
            'options' => [
                'id' => 'create-comment-form'
            ],
        ]) ?>

        <?php if($commentForm->id) :?>
            <p class="success"><?= 'Комментарий сохранен';?></p>
        <?php elseif($error): ?>
            <p class="danger"><?= $error;?></p>
        <?php endif; ?>

        <?= $form->field($commentForm, 'comment')
            ->textarea(['placeholder' => $commentForm->getAttributeLabel('comment')])
            ->label(false) ?>

        <?= Html::button('Добавить', ['class' => 'btn btn-primary', 'id' => 'comment-form-submit']) ?>

        <?php ActiveForm::end(); ?>
    </div>
</div>