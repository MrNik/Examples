<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "fs_budget".
 *
 * @property integer $id
 * @property string $month
 * @property integer $category_id
 * @property integer $amount_planned_rub
 * @property integer $amount_planned_pln
 *
 * @property TransactionCategory $category
 * @property BudgetComment[] $budgetComments
 */
class Budget extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%budget}}';
    }

    public function rules()
    {
        return [
            [['category_id'], 'required'],
            [['category_id', 'amount_planned_rub', 'amount_planned_pln'], 'integer'],
            [['month'], 'string', 'max' => 20],
        ];
    }
    
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'month' => 'Month',
            'category_id' => 'Category ID',
            'amount_planned_rub' => 'Planned Rur',
            'amount_planned_pln' => 'Planned Pln',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(TransactionCategory::className(), ['id' => 'category_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBudgetCommentList()
    {
        return $this->hasMany(BudgetComment::className(), ['budget_id' => 'id']);
    }

    public function findByCategoryAndMonth($category_id, $month, $employee_id=null) {
        return Budget::find()
            ->where(['category_id' => $category_id])
            ->andWhere(['month' => $month . '-01'])
            ->andWhere(['employee_id' => $employee_id])
            ->one();
    }

    public function findSalary($employee_id, $month) {
        return Budget::find()
            ->where(['employee_id' => $employee_id])
            ->andWhere(['month' => $month . '-01'])
            ->one();
    }

    public function getLastComment() {
        return BudgetComment::find()->where(['budget_id' => $this->id])->orderBy('id desc')->one();
    }
}
