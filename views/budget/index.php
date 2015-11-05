<?php

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;
use yii\bootstrap\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\widgets\Select2;
use yii\data\ArrayDataProvider;
use kartik\editable\Editable;
use kartik\grid\EditableColumn;
use common\helpers\PriceHelper;
use yii\bootstrap\Modal;

$this->title = 'Бюджет';
$this->params['breadcrumbs'][] = $this->title;

$month_list = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
$date_start = new DateTime("2014-10-01");
$date_now = new DateTime('NOW');
$interval = $date_now->diff($date_start);
$all_months = [];
for ($i=0; $i < $interval->m; $i++) {
    $date_start->add(new DateInterval('P1M'));

    $all_months[$date_start->format('Y-m')] = $month_list[(int)$date_start->format('m') - 1].', '.$date_start->format('Y');
}
$all_months = array_reverse($all_months);

?>

<div id="budget">
    <div class="">
        <?php $form = ActiveForm::begin([
            'method' => 'get',
            'action' => Url::to(['/budget']),
        ]); ?>

        <div class="form-group">
            <?php echo '<label class="control-label">Группы:</label>';
            echo Select2::widget([
                'name' => 'group_ids',
                'value' => $group_ids,
                'data' => ArrayHelper::map($groups, 'id', 'name'),
                'options' => [
                    'placeholder' => 'Выберите группы ...',
                    'multiple' => true
                ],
            ]);?>
        </div>

        <div class="form-group">
            <?php echo '<label class="control-label">Месяцы:</label>';
            echo Select2::widget([
                'name' => 'months',
                'value' => $months,
                'data' => $all_months,
                'options' => [
                    'placeholder' => 'Выберите месяцы ...',
                    'multiple' => true
                ],
            ]);?>
        </div>
        <div class="form-group">
            <?= Html::submitButton('Применить', ['class' => 'btn btn-success']) ?>
        </div>

        <?php ActiveForm::end(); ?>
        <div class="clearfix"></div>
    
        <?php $columns = [
            [
                'attribute' => 'category_id',
                'label' => 'ID',
            ],
            [
                'attribute' => 'category_name',
                'label' => 'Название'
            ]
        ];
        $beforeHeader = [
            [
                'columns'=>[
                    ['content'=>'Категории', 'options'=>['colspan'=>2, 'class'=>'text-center warning']], 
                ],
            ]
        ];
        $beforeHeader[1] = 
            [
                'columns'=>[
                    ['content'=>'', 'options'=>['colspan'=>2, 'class'=>'']], 
                ],
            ];
        if(count($months) > 0) {
            foreach ($months as $m) {
                $month_name = $month_list[(int)explode('-', $m)[1] - 1].', '.explode('-', $m)[0];
                $beforeHeader[0]['columns'][] = [
                    'content'=> $month_name, 
                    'options'=>['colspan'=>9, 'class'=>'text-center warning']
                ];
                $beforeHeader[1]['columns'][] = [
                    'content'=> 'Запланированный бюджет EUR: '.PriceHelper::asCurrency(PriceHelper::integerToDouble($month_data[$m]['total_budget_eur']), 'EUR'),
                    'options'=>['colspan'=>3, 'class'=>'text-center info'],
                ];
                $beforeHeader[1]['columns'][] = [
                    'content'=> 'Реально потрачено EUR: '.PriceHelper::asCurrency(PriceHelper::integerToDouble($month_data[$m]['real_cost_eur']), 'EUR'),
                    'options'=>['colspan'=>2, 'class'=>'text-center info'],
                ];
                $beforeHeader[1]['columns'][] = [
                    'content'=> 'Выручка EUR: '.PriceHelper::asCurrency(PriceHelper::integerToDouble($month_data[$m]['revenue']), 'EUR'),
                    'options'=>['colspan'=>2, 'class'=>'text-center info'],
                ];
                $beforeHeader[1]['columns'][] = [
                    'content'=> 'Потрачено EUR: '.PriceHelper::asCurrency(PriceHelper::integerToDouble($month_data[$m]['cost']), 'EUR'),
                    'options'=>['colspan'=>2, 'class'=>'text-center info'],
                ];
                $columns[] = [
                    'class' => 'kartik\grid\EditableColumn',
                    'attribute' => 'amount_planned_rub',
                    'label' => 'Запланировано RUR',
                    'editableOptions' => function ($data, $key, $index, $widget) use ($m) {
                        return [
                            'showButtonLabels' => true,
                            'format' => Editable::FORMAT_LINK,
                            'inputType' => Editable::INPUT_MONEY,
                            'name'=>'amount_planned_rub',
                            'formOptions' => [
                                'action' => Url::to(['budget/update', 'category_id' => $data['category_id'], 'month' => $m, 'employee_id' => isset($data['employee_id']) ? $data['employee_id'] : '']),
                            ],
                            'pluginEvents' => [
                                "editableSuccess"=>"function(event, val, form, data) { location.reload(); }",
                            ], 
                        ];
                    },
                    'value' => function ($data) use($m) {
                        return isset($data['budget'][$m]['amount_planned_rub']) ?
                        PriceHelper::asCurrency(PriceHelper::integerToDouble($data['budget'][$m]['amount_planned_rub']), 'RUR') :
                        PriceHelper::asCurrency(0, 'RUR');
                    },
                ];
                $columns[] = [
                        'label' => 'Реально потрачено RUR',
                        'value' => function ($data) use($m) {
                            return $data['budget'][$m]['real_cost_rur'] != 0 ? PriceHelper::asCurrency(PriceHelper::integerToDouble($data['budget'][$m]['real_cost_rur']), 'RUR') : '';
                        }
                    ];
                $columns[] = [
                    'label' => 'Запланировано PLN',
                    'class' => 'kartik\grid\EditableColumn',
                    'attribute' => 'amount_planned_pln',
                    'editableOptions' => function ($data, $key, $index, $widget) use ($m) {
                        return [
                            'showButtonLabels' => true,
                            'format' => Editable::FORMAT_LINK,
                            'inputType' => Editable::INPUT_MONEY,
                            'name'=>'amount_planned_pln',
                            'formOptions' => [
                                'action' => Url::to(['budget/update', 'category_id' => $data['category_id'], 'month' => $m, 'employee_id' => isset($data['employee_id']) ? $data['employee_id'] : '']),
                            ],
                        ];
                    },
                    'value' => function ($data) use($m) {
                        return isset($data['budget'][$m]['amount_planned_pln']) ?
                        PriceHelper::asCurrency(PriceHelper::integerToDouble($data['budget'][$m]['amount_planned_pln']), 'PLN') :
                        PriceHelper::asCurrency(0, 'PLN');
                    },
                ];
                $columns[] = [
                    'label' => 'Реально потрачено PLN',
                    'value' => function ($data) use($m) {
                        return $data['budget'][$m]['real_cost_pln'] != 0 ? PriceHelper::asCurrency(PriceHelper::integerToDouble($data['budget'][$m]['real_cost_pln']), 'PLN') : '';
                    }
                ];
                $columns[] = [
                    'label' => 'Совокупный бюджет EUR',
                    'value' => function ($data) use($m) {
                        return $data['budget'][$m]['total_budget_eur'] != 0 ? PriceHelper::asCurrency(PriceHelper::integerToDouble($data['budget'][$m]['total_budget_eur']), 'EUR') : '';
                    }
                ];
                $columns[] = [
                    'label' => 'Реально потрачено EUR',
                    'value' => function ($data) use($m) {
                        return $data['budget'][$m]['real_cost_eur'] != 0 ? PriceHelper::asCurrency(PriceHelper::integerToDouble($data['budget'][$m]['real_cost_eur']), 'EUR') : '';
                    }
                ];
                $columns[] = [
                    'label' => 'Остаток EUR',
                    'value' => function ($data) use($m) {
                        if(($data['budget'][$m]['total_budget_eur'] - $data['budget'][$m]['real_cost_eur']) <= 0) {
                            return 0;
                        } else {
                            return PriceHelper::asCurrency(PriceHelper::integerToDouble($data['budget'][$m]['total_budget_eur'] - $data['budget'][$m]['real_cost_eur']), 'EUR');
                        }
                    }
                ];
                $columns[] = [
                    'label' => 'Перерасход EUR',
                    'value' => function ($data) use($m) {
                        if(($data['budget'][$m]['total_budget_eur'] - $data['budget'][$m]['real_cost_eur']) < 0) {
                            return PriceHelper::asCurrency(PriceHelper::integerToDouble(($data['budget'][$m]['total_budget_eur'] - $data['budget'][$m]['real_cost_eur']) * (-1)), 'EUR');
                        } else {
                            return 0;
                        }
                    }
                ];
                $columns[] = [
                    'label' => 'Комментарий',
                    'format' => 'raw',
                    'value' => function ($data) use($m, $month_name) {
                        $html = Html::button('<span class="glyphicon glyphicon-comment"></span>', [
                            'value' => Url::to(['budget/comments', 'category_id' => $data['category_id'], 'month' => $m, 'employee_id' => isset($data['employee_id']) ? $data['employee_id'] : '']),
                            'class' => 'btn btn-primary show-comments-modal',
                            'title' => 'Комментарии: '.$data['category_name'].', '.$month_name,
                        ]);
                        if($data['budget'][$m]['last_comment']) {
                            $comment = $data['budget'][$m]['last_comment'];
                            $html .= '<div class="detailBox">
                                        <div class="actionBox">
                                            <ul class="commentList">
                                                <li>
                                                    <div class="commentText">
                                                        <p class="">'.$comment->comment.'</p> 
                                                        <span class="date sub-text">'.$comment->user->username.', '.$comment->date.'</span>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>';
                        }
                        return $html;
                    }
                ];
            }
        }
    ?>

        <?= GridView::widget([
            'dataProvider' => $provider,
            'headerRowOptions'=>['style'=>'width: 150px;'],
            'columns' => $columns,
            'beforeHeader'=>$beforeHeader,
            'responsive'=>false,
        ]); ?>
    </div>
</div>

<?php Modal::begin([
    'headerOptions' => ['id' => 'modalHeader'],
    'header' => '<h4>Комментарии</h4>',
    'options' => [
        'id' => 'comments-modal',
    ],
]); ?>

<?php Modal::end(); ?>

<?php $this->registerJs("
    $(function(){
        $(document).on('click', '.show-comments-modal', function(){
            $('#comments-modal').modal('show').find('.modal-body').load($(this).attr('value'));
            $('#modalHeader h4').html($(this).attr('title'));
        });

        $(document).on('click', '#comment-form-submit', function(){
            jQuery.ajax({
                url: $('#create-comment-form').attr('action'),
                type: 'POST',
                dataType: 'json',
                data: $('#create-comment-form').serialize(),
                success: function(response) {
                    $('#comments-modal').modal('show').find('.modal-body').html(response);
                },
            });
            return false;
        });
    });
");
?>