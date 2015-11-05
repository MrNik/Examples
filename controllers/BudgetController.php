<?php

namespace finsysend\controllers;

use Yii;
use common\models\Account;
use common\models\Budget;
use common\models\BudgetComment;
use common\models\CurrencyRateHistory;
use common\models\UserTransactionRelation;
use common\models\Employee;
use common\models\Finances;
use common\models\Transaction;
use common\models\TransactionCategory;
use common\models\TransactionCategoryGroup;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ArrayDataProvider;
use yii\web\Response;
use common\helpers\PriceHelper;
use yii\db\Query;

class BudgetController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex() {
        $groups = TransactionCategoryGroup::find()->where(['is_archive'=>false])->all();
        $months = isset($_GET['months']) ? $_GET['months'] : [date('Y-m')];
        $group_ids = isset($_GET['group_ids']) ? $_GET['group_ids'] : [];

        $data = [];
        $month_data = [];

        foreach ($groups as $group) {
            if($group->defaultTransactionCategory) {
                $category = $group->defaultTransactionCategory;
                if($group->code == TransactionCategoryGroup::CODE_SALARY)
                    $data = array_merge($data, $this->fillSalaryData($category, $months));
                else $data[] = $this->fillData($category, $months);
            }
        }
        if(count($group_ids) > 0) {
            foreach ($group_ids as $group_id) {
                $group = TransactionCategoryGroup::findOne($group_id);
                if($group) {
                    foreach ($group->transactionCategories as $category) {
                        if($group->code == TransactionCategoryGroup::CODE_SALARY)
                            $data = array_merge($data, $this->fillSalaryData($category, $months));
                        else $data[] = $this->fillData($category, $months);
                    } 
                }
            }
        }

        foreach ($months as $m) {
            $total_budget_eur = 0;
            $real_cost_eur = 0;
            $revenue = 0;

            $min_date = new \DateTime($m.'-01');
            $max_date = new \DateTime($m.'-01');
            $max_date->modify('+1 month');

            $query = Transaction::find();
            $query->select('sum(t.amount_eur_credit)')
            ->from(['t' => Transaction::tableName()])
            ->innerJoinWith(['groupList' => function($query) {
                $query->from(['tcg' => TransactionCategoryGroup::tableName()]);
                $query->onCondition(['tcg.code'=>TransactionCategoryGroup::CODE_REVENUE]);
            }])
            ->andWhere(['>=', 'operation_at', $min_date->format('Y-m-d')])
            ->andWhere(['<', 'operation_at', $max_date->format('Y-m-d')])
            ->all();
            
            $revenue = $query->createCommand()->queryAll()[0]['sum'];

            $cost_query = Transaction::find();
            $cost_query->select('sum(t.amount_eur_debit)')
            ->from(['t' => Transaction::tableName()])
            ->innerJoinWith(['groupList' => function($cost_query) {
                $cost_query->from(['tcg' => TransactionCategoryGroup::tableName()]);
                $cost_query->onCondition(['not in','tcg.code',[
                    TransactionCategoryGroup::CODE_TRANSFER, 
                    TransactionCategoryGroup::CODE_CAR_COST,
                    TransactionCategoryGroup::CODE_COSTS_ORDERS,
                    TransactionCategoryGroup::CODE_OTHERS,
                ]]);
            }])
            ->andWhere(['>=', 'operation_at', $min_date->format('Y-m-d')])
            ->andWhere(['<', 'operation_at', $max_date->format('Y-m-d')])
            ->all();

            $cost = $cost_query->createCommand()->queryAll()[0]['sum'];

            $used_data = [];
            foreach ($data as $d) {
                if(!in_array($d['category_name'], $used_data)) {
                    $total_budget_eur += $d['budget'][$m]['total_budget_eur'];
                    $real_cost_eur += $d['budget'][$m]['real_cost_eur'];
                    $used_data[] = $d['category_name'];
                }
            }
            $month_data[$m]['total_budget_eur'] = $total_budget_eur;
            $month_data[$m]['real_cost_eur'] = $real_cost_eur;
            $month_data[$m]['revenue'] = $revenue ? $revenue : 0;
            $month_data[$m]['cost'] = $cost != '' ? $cost : 0;
        }

        $provider = new ArrayDataProvider([
            'allModels' => $data,
            'key' => 'category_id',
            'sort' => [
                'attributes' => ['category_id', 'category_name'],
            ],
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        return $this->render('index', [
            'groups' => $groups,
            'provider' => $provider,
            'group_ids' => $group_ids,
            'months' => array_reverse($months),
            'month_data' => $month_data,
        ]);
    }

    public function actionUpdate() {
        if (Yii::$app->request->post('hasEditable')) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            if (!Yii::$app->user->can('budgetEditPlanned')) {
                throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
            }

            if(isset($_GET['category_id']) && $_GET['category_id'] != '' && isset($_GET['month']) && $_GET['month'] != '') {
                $category_id = $_GET['category_id'];
                $month = $_GET['month'];
                $employee_id = $_GET['employee_id'] != '' ? $_GET['employee_id'] : null;
                if(Budget::findByCategoryAndMonth($category_id, $month, $employee_id) != null) {
                    $budget = Budget::findByCategoryAndMonth($category_id, $month, $employee_id);
                } else {
                    $budget = new Budget;
                    $budget->month = $month . '-01';
                    $budget->category_id = $category_id;
                    $budget->employee_id = $employee_id;
                }
                if(isset($_POST['amount_planned_rub'])) {
                    $budget->amount_planned_rub = preg_replace("/[^0-9]/", '', $_POST['amount_planned_rub']);
                    $output = PriceHelper::asCurrency(PriceHelper::integerToDouble($budget->amount_planned_rub), 'RUR');
                } elseif(isset($_POST['amount_planned_pln'])) {
                    $budget->amount_planned_pln = preg_replace("/[^0-9]/", '', $_POST['amount_planned_pln']);
                    $output = PriceHelper::asCurrency(PriceHelper::integerToDouble($budget->amount_planned_pln), 'PLN');
                }
                if($budget->validate() && $budget->save()) {
                    return ['output' => $output];
                } else {
                    return ['message' => implode('<br>', $budget->getFirstErrors())];
                }
            }
        }
    }

    public function actionComments() {
        $commentForm = new BudgetComment;
        if(isset($_GET['category_id']) && $_GET['category_id'] != '' && isset($_GET['month']) && $_GET['month'] != '') {
            $error = false;
            $category_id = $_GET['category_id'];
            $month = $_GET['month'];
            $employee_id = $_GET['employee_id'] != '' ? $_GET['employee_id'] : null;
            if(Budget::findByCategoryAndMonth($category_id, $month, $employee_id) != null) {
                $budget = Budget::findByCategoryAndMonth($category_id, $month, $employee_id);
            } else {
                $budget = new Budget;
                $budget->month = $month.'-01';
                $budget->category_id = $category_id;
                $budget->employee_id = $employee_id;
                $budget->save();
            }

            if(Yii::$app->request->isPost && Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                $commentForm->budget_id = $budget->id;
                $commentForm->user_id = Yii::$app->user->id;
                $commentForm->comment = $_POST['BudgetComment']['comment'];
                if(!$commentForm->validate()) {
                } elseif(!Yii::$app->user->can('budgetRead')) {
                    $error = 'У вас недостаточно прав для этого действия';
                } else {
                    $commentForm->save();
                }
                $commentForm->comment = '';
            } 
            $comments = $budget->budgetCommentList;

            return $this->renderAjax('comments', [
                'comments' => $comments,
                'commentForm' => $commentForm,
                'error' => $error,
            ]);
        }
    }

    private function fillData($category, $months) {
        $budget = [];

        foreach ($months as $m) {
            $real_cost = Transaction::countAmountForCategoryAndMonth($category->id, $m);
            $budget[$m]['real_cost_pln'] = $real_cost['amount_pln'];
            $budget[$m]['real_cost_rur'] = $real_cost['amount_rur'];
            $budget[$m]['real_cost_eur'] = $real_cost['amount_eur'];
            $budget[$m]['total_budget_eur'] = 0;
            $budget[$m]['last_comment'] = false;

            $budgetModel = Budget::findByCategoryAndMonth($category->id, $m);
            
            if($budgetModel != null) {
                $budget[$m]['amount_planned_rub'] = $budgetModel->amount_planned_rub;
                $budget[$m]['amount_planned_pln'] = $budgetModel->amount_planned_pln;
                if($budgetModel->amount_planned_rub != 0) {
                    $rate = Yii::$app->cache->get('currencyRateRubEur');
                    if ($rate === false) {
                        $rate = CurrencyRateHistory::getRateByDate('RUB', 'EUR', $m.'-1');
                        Yii::$app->cache->set('currencyRateRubEur', $rate, 24 * 3600);
                    }
                    $budget[$m]['total_budget_eur'] += $budgetModel->amount_planned_rub * $rate;
                }
                if($budgetModel->amount_planned_pln != 0) {
                    $rate = Yii::$app->cache->get('currencyRatePlnEur');
                    if ($rate === false) {
                        $rate = CurrencyRateHistory::getRateByDate('PLN', 'EUR', $m.'-1');
                        Yii::$app->cache->set('currencyRatePlnEur', $rate, 24 * 3600);
                    }
                    $budget[$m]['total_budget_eur'] += $budgetModel->amount_planned_pln * $rate;
                }
                if(isset($budgetModel->lastComment)) $budget[$m]['last_comment'] = $budgetModel->lastComment;
            } else {
                $budget[$m]['amount_planned_rub'] = 0;
                $budget[$m]['amount_planned_pln'] = 0;
            }
        }

        return [
            'category_id' => $category->id, 
            'category_name' => $category->name, 
            'budget' => $budget, 
        ];
    }

    private function fillSalaryData($category, $months) {
        $data = [];

        foreach ($months as $m) {
            $min_date = new \DateTime($m.'-01');
            $max_date = new \DateTime($m.'-01');
            $max_date->modify('+1 month');            

            $query = Employee::find();
            $query->select(['e.id', 'e.first_name', 'e.middle_name', 'e.last_name', 't_rub.real_cost_rub', 't_rub.real_eur_cost_rub', 't_pln.real_cost_pln', 't_pln.real_eur_cost_pln']);
            $query->from(['e' => Employee::tableName()])
            ->leftJoin(['b' => Budget::tableName()], ['b.month' => $m.'-01', 'b.category_id' => $category->id, 'b.employee_id' => new yii\db\Expression('"e"."id"')])
            ->leftJoin(['t_rub' => $this->queryTransactions('RUB', $category->id, $min_date, $max_date)], ['t_rub.user_id' =>  new yii\db\Expression('"e"."id"')])
            ->leftJoin(['t_pln' => $this->queryTransactions('PLN', $category->id, $min_date, $max_date)], ['t_pln.user_id' =>  new yii\db\Expression('"e"."id"')])
            ->all();

            $employees = $query->createCommand()->queryAll();

            $key = 0;
            foreach ($employees as $e) {
                $budgetModel = Budget::findSalary($e['id'], $m);
                
                if($budgetModel != null) {
                    $amount_planned_rub = $budgetModel->amount_planned_rub;
                    $amount_planned_pln = $budgetModel->amount_planned_pln;
                    if($budgetModel->amount_planned_rub != 0) {
                        $rate = Yii::$app->cache->get('currencyRateRubEur');
                        if ($rate === false) {
                            $rate = CurrencyRateHistory::getRateByDate('RUB', 'EUR', $m.'-1');
                            Yii::$app->cache->set('currencyRateRubEur', $rate, 24 * 3600);
                        }
                        $total_budget_eur = $budgetModel->amount_planned_rub * $rate;
                    }
                    if($budgetModel->amount_planned_pln != 0) {
                        $rate = Yii::$app->cache->get('currencyRatePlnEur');
                        if ($rate === false) {
                            $rate = CurrencyRateHistory::getRateByDate('PLN', 'EUR', $m.'-1');
                            Yii::$app->cache->set('currencyRatePlnEur', $rate, 24 * 3600);
                        }
                        $total_budget_eur = $budgetModel->amount_planned_pln * $rate;
                    }
                    $last_comment = isset($budgetModel->lastComment) ? $budgetModel->lastComment : false;
                } else {
                    $amount_planned_rub = 0;
                    $amount_planned_pln = 0;
                    $total_budget_eur = 0;
                    $last_comment = false;
                }
                $names = [
                    'category_id' => $category->id,
                    'category_name' => $category->name.': '.$e['last_name'].' '.$e['first_name'].' '.$e['middle_name'].' ',
                    'employee_id' => $e['id'],
                    'budget' => [],
                ];

                $month_budget = [
                    'real_cost_rur' => $e['real_cost_rub'] ? $e['real_cost_rub'] : 0,
                    'real_cost_pln' => $e['real_cost_pln'] ? $e['real_cost_pln'] : 0,
                    'real_cost_eur' => $e['real_eur_cost_rub'] + $e['real_eur_cost_pln'],
                    'amount_planned_rub' => $amount_planned_rub,
                    'amount_planned_pln' => $amount_planned_pln,
                    'total_budget_eur' => $total_budget_eur,
                    'last_comment' => $last_comment,
                ];

                if(isset($data[$key]['employee_id']) && $data[$key]['employee_id'] == $e['id']) {
                    $data[$key]['budget'][$m] = $month_budget;
                } else {
                    $names['budget'][$m] = $month_budget;
                    $data[] = $names;
                }
                $key++;
            }
        }
        return $data;
    }

    private function queryTransactions($currency, $category_id, $min_date, $max_date) {
        $query = Transaction::find();
        if($currency == 'RUB') {
            $query->select([
                'userTransactionRelation.user_id as user_id',
                'SUM(t.amount_debit) as real_cost_rub',
                'SUM(t.amount_eur_debit) as real_eur_cost_rub',
            ]);
        } elseif($currency == 'PLN') {
            $query->select([
                'userTransactionRelation.user_id as user_id',
                'SUM(t.amount_debit) as real_cost_pln',
                'SUM(t.amount_eur_debit) as real_eur_cost_pln',
            ]);
        }
        $query->from(['t' => Transaction::tableName()]);
        $query->joinWith([
            'userTransactionRelation' => function($query) {
                $query->from(['userTransactionRelation' => UserTransactionRelation::tableName()]);
            },
            'account' => function($query) {
                $query->from(['account' => Account::tableName()]);
            },
        ]);
        $query->andWhere(['>=', 'operation_at', $min_date->format('Y-m-d')]);
        $query->andWhere(['<', 'operation_at', $max_date->format('Y-m-d')]);
        $query->andWhere(['not', ['userTransactionRelation.user_id'=>null]]);
        $query->andWhere(['not', ['t.amount_debit'=>null]]);
        $query->andWhere(['t.category_id' => $category_id]);
        $query->andWhere(['account.currency' => $currency]);
        $query->groupBy(['userTransactionRelation.user_id']);

        return $query;
    }
}