<div class="lotItem">
<?php Yii::app()->getClientScript()->registerScriptFile(Yii::app()->request->baseUrl.'/js/galleria_lot/galleria-1.2.6.min.js'); ?>
<script type="text/javascript">
jQuery(document).ready(function(){
	$("a[rel='all_photoes']").click(function() {
		$('#big_photo').attr('src', $(this).attr('href'));
		return false;
	});
    //$(".galleria-thumbnails").find('img').css({'border':'1px solid #000','border-radius':'4px'});
});
</script>
<?php $class = LotModel::model()->getLotType($model->lottype);?>
<div class="lotHeader <?php echo $class;?>">
    <div class="lotType" <?php if($class =='gold') echo "style='color: #000;'";?>><?php echo mb_strtolower(Yii::app()->params['lotTypeTitleArr'][$model->lottype], 'UTF-8');?></div>
    <div class="lotName"><?php echo $model->title; ?></div>
    <div class="lotPrice" <?php if($class =='silver') echo 'style="color: #222222"';?>>за <?php echo number_format($model->our_price, 0, ',', ' ');?> руб.</div>
</div>
<div class="lotBody2">
    <div class="" id="gallery">
        <?php if ($model->all_photoes):?>
        <h2 class="normal marginT2"></h2>
        <?php foreach ($model->all_photoes as $photo): ?>
        <a href="<?php echo Yii::app()->fileservice->getFileUrl($photo->getFileName(LotPhotoModel::MEDIUM_PHOTO)); ?>" rel="all_photoes">
        <img alt="<?php echo $photo->name; ?>" src="<?php echo Yii::app()->fileservice->getFileUrl($photo->getFileName(LotPhotoModel::SMALL_PHOTO)); ?>"/>
        </a>
        <?php endforeach;?>
        <script>
            Galleria.loadTheme('/js/galleria_lot/themes/classic/galleria.classic.min.js');
            $("#gallery").galleria({
                debug: false,
                width: 640,
                height: 420,
                fullscreenDoubleTap:true,
                transition:'fadeslide',
                imagePan:true,
                showInfo:false,
                showCounter:false
            });
        </script>
        <div class="line3"></div>
        <?php endif;?>
    </div>
    <div style="padding: 10px 0 10px 0;"><?php $this->widget('ext.socialbuttons.SocialButtonsWidget', array('layout'=>'lot', 'lot_model'=>$model)); ?></div>
    <div class="white_bg desc">
        <a href="#" onclick="showDescr(this);return false;">
            <div class="title normal yellow_bg" style="display: none;">
                <h2 class="left"><?php echo ($model->descr_title) ? $model->descr_title : "Описание";?></h2>
                <span class="blockButton right" style="margin-top: 20px; margin-right: 30px;"></span>
            </div>
        </a>
        <div class="text normal gradient" style="display:block;">
            <h2 class="normal" style="float:left;"><?php echo ($model->descr_title) ? $model->descr_title : "Описание";?>:</h2>
            <a href="#" class="showButton right" style="margin-top: 20px; margin-right: 5px;" onclick="hideDescr(this);return false;"></a>
            <p class="normal" style="clear:both;"><?php echo $model->getDescr(); ?></p>
        </div>
    </div>
    <div class="white_bg desc">
        <a href="#" onclick="showDescr(this);return false;">
            <div class="title normal yellow_bg">
                <h2 class="left"><?php echo ($model->properties_title) ? $model->properties_title : "Характеристики";?></h2>
                <span class="blockButton right" style="margin-top: 20px; margin-right: 30px;"></span>
            </div>
        </a>
        <div class="text normal gradient" style="display:none;">
            <h2 class="normal" style="float:left;"><?php echo ($model->properties_title) ? $model->properties_title : "Характеристики";?>:</h2>
            <a href="#" class="showButton right" style="margin-top: 20px; margin-right: 5px;" onclick="hideDescr(this);return false;"></a>
            <p class="normal" style="clear:both;"><?php echo $model->properties; ?></p>
        </div>
    </div>
    <div class="white_bg desc">
        <a href="#" onclick="showDescr(this);return false;">
            <div class="title normal yellow_bg">
                <h2 class="left"><?php echo ($model->history_title) ? $model->history_title : "История";?></h2>
                <span class="blockButton right" style="margin-top: 20px; margin-right: 30px;"></span>
            </div>
        </a>
        <div class="text normal gradient" style="display:none;">
            <h2 class="normal" style="float:left;"><?php echo ($model->history_title) ? $model->history_title : "История";?>:</h2>
            <a href="#" class="showButton right" style="margin-top: 20px; margin-right: 5px;" onclick="hideDescr(this);return false;"></a>
            <p class="normal" style="clear:both;"><?php echo $model->history; ?></p>
        </div>
    </div>
    <div id="commentlist" class="comments">
    <?php $this->widget('ext.showlots.LotCommentWidget', array('lotModel'=>$model)); ?>
    </div>

</div>
</div>