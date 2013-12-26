<?php
$this->breadcrumbs = array(
    $this->module->id,
);
?>

<h1 class="yellow_bg h1_box">Мои привилегии</h1>

<div class="boxer">
    <div class="allboxer">
        <table class="list_skills">
            <tr>
                <td class="first"><strong>Звание :</strong></td>
                <td class="last"><?php echo $rank->title; ?></td>
            </tr>
            <tr>
                <td class="first">- присвоено:</td>
                <td class="last"><?php echo DataUtils::getDateFromTime($model->rank->time, 'dd MMMM yyyy'); ?></td>
            </tr>
            <tr>
                <td class="first"><strong>Доступ к «Бронзовым» лотам:</strong></td>
                <td class="last">
                    <?php if($model->rank->lot_0 > 0) {
                        echo $model->rank->lot_0;
                        echo ($model->rank->lot_0 == 1) ? ' лот' : (($model->rank->lot_0 == 2 || $model->rank->lot_0 == 3 || $model->rank->lot_0 == 4) ? ' лота' : ' лотов');
                    } elseif($model->rank->lot_0 == -1) echo 'все лоты';
                    else echo 'Нет'; ?>
                </td>
            </tr>
            <tr>
                <td class="first"><strong>Доступ к «Серебряным» лотам:</strong></td>
                <td class="last">
                    <?php if($model->rank->lot_1 > 0) {
                        echo $model->rank->lot_1;
                        echo ($model->rank->lot_1 == 1) ? ' лот' : (($model->rank->lot_1 == 2 || $model->rank->lot_1 == 3 || $model->rank->lot_1 == 4) ? ' лота' : ' лотов');
                    } elseif($model->rank->lot_1 == -1) echo 'все лоты';
                    else echo 'Нет'; ?>
                </td>
            </tr>
            <tr>
                <td class="first"><strong>Доступ к «Золотым» лотам:</strong></td>
                <td class="last">
                    <?php if($model->rank->lot_2 > 0) {
                        echo $model->rank->lot_2;
                        echo ($model->rank->lot_2 == 1) ? ' лот' : (($model->rank->lot_2 == 2 || $model->rank->lot_2 == 3 || $model->rank->lot_2 == 4) ? ' лота' : ' лотов');
                    } elseif($model->rank->lot_2 == -1) echo 'все лоты';
                    else echo 'Нет'; ?>
                </td>
            </tr>
        </table>
        <div class="ava">
            <a href="#"><img src="/images/avatars/<?php echo $rank->id;?>.png" alt="" title="" /></a>
        </div>
    </div>
</div>

<h1 class="yellow_bg h1_box">Мои баллы</h1>
<div class="boxer">
    <div class="allboxer">
        <span class="button_history" id="charge_off_button" onclick="if($('#charge').is(':visible')) {$('#charge').hide(); $('#charge_off').show(); $('#charge_off_button').attr('class', 'button_history_active');$('#charge_button').attr('class', 'button_history');}">Списание</span>
        <span class="button_history_active" id="charge_button" onclick="if($('#charge_off').is(':visible')) {$('#charge').show(); $('#charge_off').hide(); $('#charge_button').attr('class', 'button_history_active'); $('#charge_off_button').attr('class', 'button_history');}">Начисление</span>
        <div id="charge">
            <?php if(count($history) > 0) { ?>
            <table style="margin:0; width: 100%;">
                <tr>
                    <th>Дата</th>
                    <th>Действие</th>
                    <th>Баллы</th>
                </tr>
                <?php //foreach ($history as $value) { 
                    for ($i = 0; $i<count($history); $i++) {?>
                <?php if ($i%2 == 0) echo '<tr class="color"'; else echo '<tr'; if($i>4) echo ' style="display:none;"'; echo '>';?>
                    <td><?php echo DataUtils::getDateFromTime($history[$i]->time, 'dd MMMM yyyy'); ?></td>
                    <td><?php 
                            if($history[$i]->action_id == 'invite_use'){
                                if($history[$i]->page_id !=''){
                                    echo str_replace('%name%', ' ('.$history[$i]->page_id.') ', $actionsTitles[$history[$i]->action_id]);
                                }else{
                                    echo str_replace('%name%', ' ', $actionsTitles[$history[$i]->action_id]);
                                }
                            }elseif($history[$i]->action_id == 'invite_reg' || $history[$i]->action_id == 'invite_pay') {
                                if($history[$i]->page_id !=''){
                                    $user = UserModel::model()->getByEmail($history[$i]->page_id);
                                    if($user && ($user->role == 5 || $user->role == 2))
                                        $name = '<a href="'.$user->getUserHref().'">'.$user->getCaption().'</a>';
                                        echo str_replace('%name%', ', '.$name.', ', $actionsTitles[$history[$i]->action_id]);
                                }else{
                                    echo str_replace('%name%', ' ', $actionsTitles[$history[$i]->action_id]);
                                }
                            }else{
                                echo $actionsTitles[$history[$i]->action_id];
                            } ?>
                    </td>
                    <td><?php echo $history[$i]->quantity; ?></td>
                </tr>
                <?php }
                ?>
                <tr class="box_cost">
                    <td colspan="2" class="second"><span>Текущий баланс:</span></td>
                    <td><?php echo $model->rank->score; ?></td>
                </tr>
                <tr id="all_history">
                    <td colspan=3 align="center"><a href="javascript:void(0);" style="border-bottom: 1px dashed black;text-decoration:none;text-align:center;" onclick="$('#charge').find('tr').show(); $('#all_history').hide();">открыть всю историю</a></td>
                </tr>
            </table>
            <?php } else { ?>
            <span>У Вас еще нет заработанных баллов. Участвуйте в премиальных лотах и <a href="/profile/contacts/invite">приглашайте друзей</a> чтобы получать баллы</span>
            <?php } ?>
        </div>
        <div  id="charge_off" style="display:none;">
            <table style="margin:0;width: 100%;">
                <tr>
                    <th>Дата</th>
                    <th>Действие</th>
                    <th>Баллы</th>
                </tr>
                <?php 
                    for ($i = 0; $i<count($charge_offHistory); $i++) {?>
                <?php if ($i%2 == 0) echo '<tr class="color"'; else echo '<tr'; if($i>4) echo ' style="display:none;"'; echo '>';?>
                    <td><?php echo DataUtils::getDateFromTime($charge_offHistory[$i]->time, 'dd MMMM yyyy'); ?></td>
                    <td><?php echo $charge_offTitles[$charge_offHistory[$i]->good_id]; ?>
                    </td>
                    <td><?php echo $charge_offHistory[$i]->price; ?></td>
                </tr>
                <?php }
                ?>
                <tr class="box_cost">
                    <td colspan="2" class="second"><span>Всего потрачено:</span></td>
                    <td><?php echo $model->rank->spent; ?></td>
                </tr>
                <tr id="all_history_off">
                    <td colspan=3 align="center"><a href="javascript:void(0);" style="border-bottom: 1px dashed black;text-decoration:none;text-align:center;" onclick="$('#charge_off').find('tr').show(); $('#all_history_off').hide();">открыть всю историю</a></td>
                </tr>
            </table>
        </div>
    </div>
    <div class="bottom">&nbsp;</div>
</div>

<?php $this->widget('ext.shop.ShopWidget', array()); ?>

<script type="text/javascript">
    function getLast(){
        jQuery.ajax({
                        'type':'POST','url':'/profile/privileges/getLast',
                        'cache':false,
                        'data':"action=spent",
                        'success':function(data){
                            var data = eval("(" + data + ")");
                            $('#charge_off tr:first').after('<tr><td>'+data.date+'</td><td>'+data.title+'</td><td>'+data.price+'</td></tr>');
                            if($('#charge_off tr:eq(2)').attr('class') != 'color') $('#charge_off tr:eq(1)').attr('class','color');
                        }
                    });
    };
</script>