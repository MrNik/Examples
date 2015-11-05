<?php

class LotController extends Controller {

    public function beforeAction($action) {
        if (Yii::app()->user->isGuest && 
            Yii::app()->controller->action->id != 'show' && 
            Yii::app()->controller->action->id != 'send' &&
            Yii::app()->controller->action->id != 'ajaxCommentPages' &&
            Yii::app()->controller->action->id != 'rules' 
        ) {
            $this->redirect('/');
            return false;
        }
        $this->layout='//layouts/column_lot';
        return true;
    }


    public function actionIndex() {
        $this->layout='//layouts/column2';
        $this->pageTitle = Yii::app()->name. ". Текущие лоты";
        $userId = Yii::app()->user->getId();
        $lots = LotModel::model()->getMoreRegistered();
        $this->render('index', array('lots'=>$lots));
    }

    public function actionPast() {
        $this->layout='//layouts/column2';
        $this->pageTitle = Yii::app()->name. ". Прошедшие лоты";
        $userId = Yii::app()->user->getId();
        $lots = LotModel::model()->getPast();
        $widgetLinkModel = new WidgetLinkModel();
        $widgetList = $widgetLinkModel->getList($widgetLinkModel->getCriteriaByParams(WidgetLinkModel::LINK_ENDED_LOT));
        Yii::app()->container->setWidgets($widgetList);
        $this->render('index', array('lots'=>$lots, 'past'=>true));
    }

    public function actionShow() {
        if (Yii::app()->user->isGuest) {
            $this->layout='//layouts/column_lot_guest';
            $id = DataUtils::checkIntVal(Yii::app()->request->getQuery('id', 0), 0);
            $lotModel = new LotModel();
            $lotModel = $lotModel->getById($id);
            $task = TaskModel::model()->getTaskRelation($lotModel->id);
            $this->pageTitle = Yii::app()->name." / ".$lotModel->title;
            if (!$lotModel || $lotModel->status == LotModel::LOT_STATUS_HIDEN || $lotModel->status == LotModel::LOT_STATUS_DRAFT) throw new CHttpException(404, 'Запрошенный лот не существует');
             
            $widgetLinkModel = new WidgetLinkModel();
            $widgetList = $widgetLinkModel->getList($widgetLinkModel->getCriteriaByParams(
                    ($lotModel->status == LotModel::LOT_STATUS_FINISHED?
                        WidgetLinkModel::LINK_ENDED_LOT:WidgetLinkModel::LINK_ACTIVE_LOT)
                ));
            $widgetListOneLot = $widgetLinkModel->getList($widgetLinkModel->getCriteriaByParams(
                    WidgetLinkModel::LINK_ONE_LOT, null, $id
                ));
            $widgetList = array_merge($widgetList, $widgetListOneLot);
            Yii::app()->container->addSponsor($lotModel->sponsor);
            if ($lotModel->interview) 
                Yii::app()->container->addInterview($lotModel->interview);
            Yii::app()->container->setWidgets($widgetList);
            Yii::app()->container->lot = $lotModel;
            Yii::app()->container->task = $task;
            $this->render('show_guest', array('model'=>$lotModel));
        } else {
            $userId = Yii::app()->user->getId();
            $user = UserModel::model()->getById($userId);
            $id = DataUtils::checkIntVal(Yii::app()->request->getQuery('id', 0), 0);
            $userRank = UsersRanks::model()->getByUserId($userId);
            $currentRank = Ranks::model()->getCurrentRank($userRank->spent);
            $discount = Ranks::model()->getDiscount(UsersRanks::model()->getByUserId($userId)->score, $userId);
            $lotModel = new LotModel();
            $lotModel = $lotModel->getById($id);
            $this->pageTitle = Yii::app()->name." / ".$lotModel->title;
            if (!$lotModel || $lotModel->status == LotModel::LOT_STATUS_HIDEN || ($lotModel->status == LotModel::LOT_STATUS_DRAFT && $user->role != UserModel::ROLE_ADMIN)) throw new CHttpException(404, 'Запрошенный лот не существует');
            $accountModel = new AccountModel();
            $account = $accountModel->getByUserId($userId);
            $financialResponsibility = $accountModel->financialResponsibility($account, $lotModel->member_price, $userId);
            $accountActiveModel = new AccountActivityModel();
            $isAble = $accountActiveModel->isSubscribe($userId, $lotModel->id);
            $task = TaskModel::model()->getTaskRelation($lotModel->id);
            $taskUser = ($task)?TaskUserModel::model()->getRecord($task->id, $userId):null;
            
            $widgetLinkModel = new WidgetLinkModel();
            $widgetList = $widgetLinkModel->getList($widgetLinkModel->getCriteriaByParams(
                    ($lotModel->status == LotModel::LOT_STATUS_FINISHED?
                        WidgetLinkModel::LINK_ENDED_LOT:WidgetLinkModel::LINK_ACTIVE_LOT)
                ));
            $widgetListOneLot = $widgetLinkModel->getList($widgetLinkModel->getCriteriaByParams(
                    WidgetLinkModel::LINK_ONE_LOT, null, $id
                ));
            $widgetList = array_merge($widgetList, $widgetListOneLot);
            Yii::app()->container->addSponsor($lotModel->sponsor);
            if ($lotModel->interview) 
                Yii::app()->container->addInterview($lotModel->interview);
            Yii::app()->container->setWidgets($widgetList);
            Yii::app()->container->lot = $lotModel;
            Yii::app()->container->task = $task;
            Yii::app()->container->taskUser = $taskUser;
            Yii::app()->container->account = $account;
            Yii::app()->container->financialResponsibility = $financialResponsibility;
            Yii::app()->container->isAble = $isAble;
            Yii::app()->container->currentRank = $currentRank;
            $this->render('show', array('model'=>$lotModel, 'task'=>$task,
            						'taskUser'=>$taskUser, 'account'=>$account,
            						'financialResponsibility'=>$financialResponsibility,
            						'isAble'=>$isAble, 'discount'=>$discount)
                    );
        }
    }

    public function actionRules() {
        $id = DataUtils::checkIntVal(Yii::app()->request->getQuery('id', 0), 0);
        $lotModel = LotModel::model()->getById($id);
        if (!$lotModel) throw new CHttpException(404, 'Запрошенный лот не существует');
        if (Yii::app()->user->isGuest) {
            $this->layout='//layouts/column_lot_guest';
        }
        $widgetLinkModel = new WidgetLinkModel();
        $widgetList = $widgetLinkModel->getList($widgetLinkModel->getCriteriaByParams(
                ($lotModel->status == LotModel::LOT_STATUS_FINISHED?
                    WidgetLinkModel::LINK_ENDED_LOT:WidgetLinkModel::LINK_ACTIVE_LOT)
            ));
        Yii::app()->container->addSponsor($lotModel->sponsor);
        if ($lotModel->interview) 
            Yii::app()->container->addInterview($lotModel->interview);
        Yii::app()->container->setWidgets($widgetList);
        $this->render('rules', array('model'=>$lotModel));
    }
    
    public function actionShowresult() {
        $userId = Yii::app()->user->getId();
        $id = DataUtils::checkIntVal(Yii::app()->request->getQuery('id', 0), 0);
        $lotModel = new LotModel();
        $lotModel = $lotModel->getById($id);
        if (!$lotModel) throw new CHttpException(404, 'Запрошенный лот не существует');
        if ($lotModel->status != LotModel::LOT_STATUS_FINISHED && $lotModel->title !=='Тестовый лот') throw new CHttpException(204, 'Страница результатов недоступна');
        $task = TaskModel::model()->getTaskRelation($lotModel->id);
        
        Yii::app()->container->lot = $lotModel;
        Yii::app()->container->task = $task;
        
        $widgetLinkModel = new WidgetLinkModel();
        $widgetList = $widgetLinkModel->getList($widgetLinkModel->getCriteriaByParams(
                ($lotModel->status == LotModel::LOT_STATUS_FINISHED?
                    false:WidgetLinkModel::LINK_ACTIVE_LOT)
            ));
        Yii::app()->container->setWidgets($widgetList);
        $this->render('result', array('model'=>$lotModel));
    }

    public function actionSend() {
        $model = new LotCommentModel();
        if ($_POST) {
            $model->lot_id = $_POST['lot_id'];
            $model->msg = $_POST['msg'];
            $model->parent_id = $_POST['parent_id'];
            $offset = $_POST['offset'];
            $lotModel = LotModel::model()->getById($model->lot_id);
            if (Yii::app()->user->getIsGuest()) {
                $model->user_id = -1;
                if ($model->validate()) {
                    $model->save();
                }
            } else {
                $model->user_id = Yii::app()->user->getId();
                if ($model->validate()) {
                    $model->save();
                    if ($model->parent_id) {
                        $cronEmail = new CronEmailModel();
                        $cronEmail->createCronByCommentReply($lotModel, $model->parent->user);
                    }
                }
            }
            if ($lotModel)
                $this->renderPartial('commentlist', array('lotModel'=>$lotModel, 'offset'=>$offset));
        }
    }

    public function actionSubscribe(){
        $userId = Yii::app()->user->getId();
        $lotId = DataUtils::checkIntVal(Yii::app()->request->getQuery('id', 0), 0);
        $for_price = Yii::app()->request->getQuery('for_price');
        $lotModel = new LotModel();
        $lot = $lotModel->getById($lotId);
        if (!$lot) {
            Yii::log('Лот не найден, id='.$lotId);
            throw new CHttpException(404, 'Лот не найден, id='.$lotId);
            return false;
        }

        if ($lot->member_count >= $lot->max_member_count) {
            Yii::log('Лот достиг максимума участников, id='.$lotId);
            return false;
        }
        if ($lot->status != LotModel::LOT_STATUS_REGISTER || ($lot->dateEndInt < time() && $lot->dateStartInt > time())) {
            Yii::log('Регистрация закрыта на лот, id='.$lotId);
            return false;
        }

        /*
         * Необходимо проверить, подписан или нет данный пользователь на лот.
         */
        $accountActiveModel = new AccountActivityModel();
        $a = $accountActiveModel->isSubscribe($userId, $lotId);
        if ($a) {
            Yii::log('Пользователь = '.$userId.' уже подписан на лот ='.$lotId);
            throw new CHttpException(404, 'Пользователь = '.$userId.' уже подписан на лот ='.$lotId);
            return false;
        }

        $accountModel = new AccountModel();
        $userModel = Yii::app()->user->getModel();
        $score = $userModel->rank->score;
        $discount = Ranks::model()->getDiscount($score, $userId);
        $lot->member_price = $lot->member_price - $lot->member_price * $discount;
        $f = $accountModel->financialResponsibility($userId, $lot->member_price);
        
        if($lot->lottype != 3) {
            $good = Goods::model()->getById('lot_'.$lot->lottype);
            $userRank = UsersRanks::model()->getByUserId($userId);
            if($for_price) {
                if (!$f) {
                    Yii::log('Не хватает денег на счету пользователя, id='.$userId);
                    throw new CHttpException(404, 'На Вашем счету не достаточно денег');
                    return false;
                }
                $account = $accountModel->updateBalance($userId, $lot->member_price, '-');
                if (!$account) {
                    Yii::log('Не найден счет пользователя, id='.$userId);
                    throw new CHttpException(404, 'Не найден счет пользователя, id='.$userId);
                    return false;
                }
                $payment_type = 1;
            } elseif($userRank['lot_'.$lot->lottype] == 0){
                if($score >= $good->price){
                    GoodsActions::model()->addAction($userId, $good->id, $good->price);
                    $userRank = $userRank->updateSpent($userId, $good->id, $good->price);
                    $payment_type = 2;
                } else {
                    throw new CHttpException(404, 'Не достаточно баллов');
                    return false;
                }
            } elseif ($userRank['lot_'.$lot->lottype] > 0) {
                $userRank->updateLotCount($userId, $lot->lottype);
                $payment_type = 2;
            }
        } elseif($lot->lottype == 3) {
            if (!$f) {
                Yii::log('Не хватает денег на счету пользователя, id='.$userId);
                throw new CHttpException(404, 'Не хватает денег на счету пользователя, id='.$userId);
                return false;
            }
            $account = $accountModel->updateBalance($userId, $lot->member_price, '-');
            if (!$account) {
                Yii::log('Не найден счет пользователя, id='.$userId);
                throw new CHttpException(404, 'Не найден счет пользователя, id='.$userId);
                return false;
            }   //баллы за участие
            $action = 'prem_lot';
            $motiv = RanksMotivations::model()->getQuantityById ($action);
            RanksActions::model()->addAction ($motiv->quantity, $userId, $action);
            $action = 'invite_pay'; //баллы тому, кто пригласил пользователя
            $a = RanksActions::model()->getUserAction($userModel->ref_id, $action, $userModel->email);
            if ($userModel->ref_id != null && $a === false) {
                $motiv = RanksMotivations::model()->getQuantityById ($action);
                RanksActions::model()->addAction ($motiv->quantity, $userModel->ref_id, $action, '+', $userModel->email);
            }
            $payment_type = 1;
        }
        Yii::log('Сумма = '.$lot->member_price.' пользователя = '.$userId.' списана за лот ='.$lotId);


        $lotModel->updateMemberCount($lot);
        Yii::log('Подписка на лот ='.$lotId.' пользователя = '.$userId);

        $accountActiveModel->addLotSubscribe($userId, $account->id, $lot, $payment_type);

        if ($lot->sponsor) {
            $bonusAccount = new BonusAccountsModel();
            $bonus_plus = Ranks::model()->getBonusPlus($score);
            $lot->member_price = $lot->member_price + $lot->member_price * $bonus_plus;
            if ($lot->bonus_index != 0) $lot->member_price = $lot->member_price * $lot->bonus_index;
            $bonusAccount->makeAccount($userId, $lot->sponsor_id, $lot->member_price, $lot->sponsor->action_time);
        }

        $user = Yii::app()->user->getModel();
        Yii::app()->sendMail->subscribeToLot($user, $this, $lot);

        $this->redirect('/lot/show/id/'.$lotId);
        return true;
    }



    public function actionTestLot() {
        $userId = Yii::app()->user->getId();
        $id = DataUtils::checkIntVal(Yii::app()->request->getQuery('id', 0), 0);
        $userRank = UsersRanks::model()->getByUserId($userId);
        $currentRank = Ranks::model()->getCurrentRank($userRank->spent);
        $accountModel = new AccountModel();
        $account = $accountModel->getByUserId($userId);
        $financialResponsibility = $accountModel->financialResponsibility($account, $lotModel->member_price);
        $accountActiveModel = new AccountActivityModel();
        $task = TaskModel::model()->getTestLotTask();
        $taskUser = ($task)?TaskUserModel::model()->getRecord($task->id, $userId):null;
        if ($taskUser->test_lot_place == 1) $this->redirect('/profile/lots/trains/');
        $lotModel = LotModel::model()->getTestLot();
        $widgetLinkModel = new WidgetLinkModel();
        $widgetList = $widgetLinkModel->getList($widgetLinkModel->getCriteriaByParams(
                ($lotModel->status == LotModel::LOT_STATUS_FINISHED?
                    WidgetLinkModel::LINK_ENDED_LOT:WidgetLinkModel::LINK_ACTIVE_LOT)
            ));
        $isAble = $accountActiveModel->isSubscribe($userId, $lotModel->id);
        Yii::app()->container->addSponsor($lotModel->sponsor);
        if ($lotModel->interview) 
            Yii::app()->container->addInterview($lotModel->interview);
        Yii::app()->container->setWidgets($widgetList);
        Yii::app()->container->lot = $lotModel;
        Yii::app()->container->task = $task;
        Yii::app()->container->taskUser = $taskUser;
        Yii::app()->container->account = $account;
        Yii::app()->container->financialResponsibility = $financialResponsibility;
        Yii::app()->container->isAble = $isAble;
        Yii::app()->container->currentRank = $currentRank;
        $this->render('testlot', array('task'=>$task, 'model'=>$lotModel,
                                'taskUser'=>$taskUser,
                                'isAble'=>$isAble,'account'=>$account)
                );
    }
    
    public function actionShowTestLotResult() {
        $userId = Yii::app()->user->getId();
        $lotModel = LotModel::model()->getTestLot();
        if (!$lotModel) throw new CHttpException(404, 'Запрошенный лот не существует');
        $task = TaskModel::model()->getTaskRelation($lotModel->id);
        $taskUser = ($task)?TaskUserModel::model()->getRecord($task->id, $userId):null;
        if(!$taskUser) $this->redirect('/lot/testlot/');
        if ($taskUser->test_lot_place == 1) $this->redirect('/profile/lots/trains/');
        Yii::app()->container->lot = $lotModel;
        Yii::app()->container->task = $task;
        Yii::app()->container->taskUser = $taskUser;
        
        $widgetLinkModel = new WidgetLinkModel();
        $widgetList = $widgetLinkModel->getList($widgetLinkModel->getCriteriaByParams(
                ($lotModel->status == WidgetLinkModel::LINK_ENDED_LOT)
            ));
        Yii::app()->container->setWidgets($widgetList);
        $this->render('result', array('model'=>$lotModel, 'is_testlot'=>1));
    }

    public function actionAjaxCommentPages() {
        if(Yii::app()->request->isAjaxRequest && isset($_POST['lot_id'], $_POST['comment'])) {
            $lotModel = new LotModel();
            $lotModel = $lotModel->getById($_POST['lot_id']);
            return $this->widget('ext.showlots.LotCommentWidget', array('lotModel'=>$lotModel, 'offset'=>$_POST['comment']));
        }
    }

}