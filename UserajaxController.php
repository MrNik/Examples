<?php

class UserajaxController extends Controller {

    //Обработчик викторины
    public function actionQuiz() {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {

            $lotId = 0;
            $taskUser = TaskUserModel::model();
            $userId = Yii::app()->user->id;
            if (isset($_POST['task_id'], $_POST['question_id'], $_POST['lot'], $_POST['answer_time'])) {
                $user_answer = DataUtils::checkIntVal(Yii::app()->request->getPost('user_answer', 0), 0);
    //            $user_answer = intval($_POST['user_answer']);
                $taskId = DataUtils::checkIntVal(Yii::app()->request->getPost('task_id', 0), 0);
                $questId = DataUtils::checkIntVal(Yii::app()->request->getPost('question_id', 0), 0);
                $lotId = DataUtils::checkIntVal(Yii::app()->request->getPost('lot', 0), 0);
                $answerTime = DataUtils::checkIntVal(Yii::app()->request->getPost('answer_time', 0), 0);
                $start = DataUtils::checkIntVal(Yii::app()->request->getPost('start', 0), 0);

                $taskUser = TaskUserModel::model()->getRecord($taskId, $userId);
                if (!$taskUser) {
                    echo 'error';
                }
                
                $taskModel = TaskModel::model()->getById($taskId);
                
                // проверка того, что данные ответ уже сохранен
                if ($taskUser->answers)
                foreach ($taskUser->answers as /* @var $userAnswer UserAnswerObject  */ $userAnswer) {
                    if ($userAnswer->quest_id == $questId) {
                        $this->showTaskWidget($lotId, $start, $taskUser, TaskModel::TYPE_QUIZ, $taskModel);
                        return;
                    }
                }

                $lot = LotModel::model()->getById($lotId);
                if ($user_answer && ($taskModel->is_training||$taskModel->is_testlot||$this->checkQuest($lot, $taskModel))) {
                    $userAnswerObj = new UserAnswerObject($questId, $user_answer, $answerTime);
                    $taskUser->answers[] = $userAnswerObj;
                    if($taskUser->last_pause != 0) {
                        $taskUser->all_pause_time = $taskUser->all_pause_time + time() - $taskUser->last_pause;
                        $taskUser->last_pause = 0;
                    }
                    $ret = $taskUser->save();
                    if (!$ret) Yii::log('answer with id='.$taskUser->id.' not saved:'.print_r($taskUser->getErrors(), true),'error');
                }
            } elseif (isset($_POST['start'], $_POST['task_id'], $_POST['lot'])) {
                $taskId = DataUtils::checkIntVal(Yii::app()->request->getPost('task_id', 0), 0);
                $lotId = DataUtils::checkIntVal(Yii::app()->request->getPost('lot', 0), 0);
                $taskModel = TaskModel::model()->getById($taskId);
                $this->widget('ext.task.TaskWidget', array(
                  	'lotId' => $lotId,
                   	'start' => $_POST['start'],
                    'type'	=> TaskModel::TYPE_QUIZ,
                    'is_training'=>$taskModel->is_training,
                ));
                return;
            }

            $this->showTaskWidget($lotId, $start, $taskUser, TaskModel::TYPE_QUIZ, $taskModel);
        } else {
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
        }
    }
    
    public function actionQuizanswers() {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {

            $lotId = 0;
            $taskUser = TaskUserModel::model();
            $userId = Yii::app()->user->id;
            if (isset($_POST['questionNumber'], $_POST['lot'])) {
                $taskId = DataUtils::checkIntVal(Yii::app()->request->getPost('task_id', 0), 0);
                $lotId = DataUtils::checkIntVal(Yii::app()->request->getPost('lot', 0), 0);
                $questionNumber = DataUtils::checkIntVal(Yii::app()->request->getPost('questionNumber', 0), 0);

                $taskUser = TaskUserModel::model()->getRecord($taskId, $userId);
                if (!$taskUser) {
                    echo 'error';
                }

                $lot = LotModel::model()->getById($lotId);
            } 

            $this->widget('ext.task.TaskAnswersWidget', array(
                'lotId' => $lotId,
                'lot' => $lot,
                'taskUser' => $taskUser,
                'questionNumber' => $questionNumber,
                'type'	=> TaskModel::TYPE_QUIZ
            ));
        } else {
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
        }
    }
    
    public function actionMemory() {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {

            $lotId = 0;
            $taskUser = TaskUserModel::model();
            $userId = Yii::app()->user->id;
            if (isset($_POST['user_answer'], $_POST['task_id'], $_POST['question_id'], $_POST['lot'], $_POST['answer_time'], $_POST['real_img'])) {

                $user_answer = '';
                
                //Без понятия с регуляркой!!
                if(preg_match("/^[,0-9]+$/i", Yii::app()->request->getPost('user_answer')))
                        $user_answer = preg_replace('/,+/', ',', Yii::app()->request->getPost('user_answer'));
                
                $taskId = DataUtils::checkIntVal(Yii::app()->request->getPost('task_id', 0), 0);
                $questId = DataUtils::checkIntVal(Yii::app()->request->getPost('question_id', 0), 0);
                $lotId = DataUtils::checkIntVal(Yii::app()->request->getPost('lot', 0), 0);
                $answerTime = DataUtils::checkIntVal(Yii::app()->request->getPost('answer_time', 0), 0);
                $start = DataUtils::checkIntVal(Yii::app()->request->getPost('start', 0), 0);
                
                $real_img = Yii::app()->request->getPost('real_img');
                $taskUser = TaskUserModel::model()->getRecord($taskId, $userId);
                if (!$taskUser) {
                    echo 'error';
                }
                $taskModel = TaskModel::model()->getById($taskId);
                $lot = LotModel::model()->getById($lotId);
                if ($user_answer && ($taskModel->is_training||$this->checkQuest($lot, $taskModel))) {
                    $userAnswerObj = new UserAnswerObject($questId, $user_answer, $answerTime);
                    $userAnswerObj->model_answer = $real_img;
                    $taskUser->answers[] = $userAnswerObj;
                    $ret = $taskUser->save();
                    if (!$ret) Yii::log('answer with id='.$taskUser->id.' not saved:'.print_r($taskUser->getErrors(), true),'error');
                }
            } elseif (isset($_POST['start'], $_POST['task_id'], $_POST['lot'])) {
                $taskId = DataUtils::checkIntVal(Yii::app()->request->getPost('task_id', 0), 0);
                $lotId = DataUtils::checkIntVal(Yii::app()->request->getPost('lot', 0), 0);
                $taskModel = TaskModel::model()->getById($taskId);
                $this->widget('ext.task.TaskWidget', array(
                    'lotId' => $lotId,
                    'start' => $_POST['start'],
                    'type'	=> TaskModel::TYPE_MEMORY,
                    'is_training'=>$taskModel->is_training,
                ));
                return;
            }

            $this->widget('ext.task.TaskWidget', array(
                'lotId' => $lotId,
                'start' => $start,
                'user' => $taskUser,
                'type'	=> TaskModel::TYPE_MEMORY,
                'is_training'=>$taskModel->is_training,
            ));
        } else {
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
        }
    }
    
    
    public function actionLogic() {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {

            $lotId = 0;
            $taskUser = TaskUserModel::model();
            $userId = Yii::app()->user->id;
            if (isset($_POST['answer'], $_POST['task_id'], $_POST['question_id'], $_POST['lot'], $_POST['answer_time'])) {              
                $answers = Yii::app()->request->getPost('answer');
                $user_answer = array();
                for ($i=0; $i < count($answers); $i++) { 
                    if($answers[$i] == substr(md5('0'.Yii::app()->user->id.$_POST['task_id']),0,10)) $user_answer[$i] = 0;
                    elseif($answers[$i] == substr(md5('1'.Yii::app()->user->id.$_POST['task_id']),0,10)) $user_answer[$i] = 1;
                    elseif($answers[$i] == substr(md5('2'.Yii::app()->user->id.$_POST['task_id']),0,10)) $user_answer[$i] = 2;
                    elseif($answers[$i] == substr(md5('3'.Yii::app()->user->id.$_POST['task_id']),0,10)) $user_answer[$i] = 3;
                }
                //$user_answer = implode(',',Yii::app()->request->getPost('answer'));
                $user_answer = implode(',',$user_answer);
                $taskId = DataUtils::checkIntVal(Yii::app()->request->getPost('task_id', 0), 0);
                $questId = DataUtils::checkIntVal(Yii::app()->request->getPost('question_id', 0), 0);
                $lotId = DataUtils::checkIntVal(Yii::app()->request->getPost('lot', 0), 0);
                $answerTime = DataUtils::checkIntVal(Yii::app()->request->getPost('answer_time', 0), 0);
                $start = DataUtils::checkIntVal(Yii::app()->request->getPost('start', 0), 0);
                
                $taskUser = TaskUserModel::model()->getRecord($taskId, $userId);
                if (!$taskUser) {
                    echo 'error';
                }
                $taskModel = TaskModel::model()->getById($taskId);
                
                // проверка того, что данные ответ уже сохранен
                if ($taskUser->answers)
                foreach ($taskUser->answers as /* @var $userAnswer UserAnswerObject  */ $userAnswer) {
                    if ($userAnswer->quest_id == $questId) {
                        $this->showTaskWidget($lotId, $start, $taskUser, TaskModel::TYPE_LOGIC, $taskModel);
                        return;
                    }
                }
                
                $lot = LotModel::model()->getById($lotId);
                if ($user_answer && ($taskModel->is_training||$this->checkQuest($lot, $taskModel))) {
                    $userAnswerObj = new UserAnswerObject($questId, $user_answer, $answerTime);
                    $taskUser->answers[] = $userAnswerObj;
                    if($taskUser->last_pause != 0) {
                        $taskUser->all_pause_time = $taskUser->all_pause_time + time() - $taskUser->last_pause;
                        $taskUser->last_pause = 0;
                    }
                    $ret = $taskUser->save();
                    if (!$ret) Yii::log('answer with id='.$taskUser->id.' not saved:'.print_r($taskUser->getErrors(), true),'error');
                }
            } elseif (isset($_POST['start'], $_POST['task_id'], $_POST['lot'])) {
                $taskId = DataUtils::checkIntVal(Yii::app()->request->getPost('task_id', 0), 0);
                $lotId = DataUtils::checkIntVal(Yii::app()->request->getPost('lot', 0), 0);
                $taskModel = TaskModel::model()->getById($taskId);
                $this->widget('ext.task.TaskWidget', array(
                    'lotId' => $lotId,
                    'start' => $_POST['start'],
                    'type'	=> TaskModel::TYPE_LOGIC,
                    'is_training'=>$taskModel->is_training,
                ));
                return;
            }

            $this->showTaskWidget($lotId, $start, $taskUser, TaskModel::TYPE_LOGIC, $taskModel);
        } else {
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
        }
    }
    
    public function actionLogicanswers() {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {

            $lotId = 0;
            $taskUser = TaskUserModel::model();
            $userId = Yii::app()->user->id;
            if (isset($_POST['questionNumber'], $_POST['lot'])) {
                $taskId = DataUtils::checkIntVal(Yii::app()->request->getPost('task_id', 0), 0);
                $lotId = DataUtils::checkIntVal(Yii::app()->request->getPost('lot', 0), 0);
                $questionNumber = DataUtils::checkIntVal(Yii::app()->request->getPost('questionNumber', 0), 0);

                $taskUser = TaskUserModel::model()->getRecord($taskId, $userId);
                if (!$taskUser) {
                    echo 'error';
                }

                $lot = LotModel::model()->getById($lotId);
            } 

            $this->widget('ext.task.TaskAnswersWidget', array(
                'lotId' => $lotId,
                'lot' => $lot,
                'taskUser' => $taskUser,
                'questionNumber' => $questionNumber,
                'type'	=> TaskModel::TYPE_LOGIC
            ));
        } else {
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
        }
    }
    
    public function actionIngenuity() {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {

            $lotId = 0;
            $taskUser = TaskUserModel::model();
            $userId = Yii::app()->user->id;
            if (isset($_POST['answer'], $_POST['task_id'], $_POST['question_id'], $_POST['lot'], $_POST['answer_time'])) {
                $answers = Yii::app()->request->getPost('answer');
                $user_answer = array();
                for ($i=0; $i < count($answers); $i++) { 
                    for ($k=0; $k < count($answers); $k++) {
                        if ($answers[$i] == substr(md5((string)$k.$userId.$_POST['task_id']),0,10))
                            $user_answer[$i] = $k;
                    }
                }
                //$user_answer = implode(',',Yii::app()->request->getPost('answer'));
                $user_answer = implode(',',$user_answer);
                $taskId = DataUtils::checkIntVal(Yii::app()->request->getPost('task_id', 0), 0);
                $questId = DataUtils::checkIntVal(Yii::app()->request->getPost('question_id', 0), 0);
                $lotId = DataUtils::checkIntVal(Yii::app()->request->getPost('lot', 0), 0);
                $question = QuestionModel::model()->getById($questId);
                $real_ans = array();
                foreach($question->ans as $k=>$v) {
                    $real_ans[] = $k;
                }
                $real_img = implode(':',$real_ans);
                $answerTime = DataUtils::checkIntVal(Yii::app()->request->getPost('answer_time', 0), 0);
                $start = DataUtils::checkIntVal(Yii::app()->request->getPost('start', 0), 0);
                
                $taskUser = TaskUserModel::model()->getRecord($taskId, $userId);
                if (!$taskUser) {
                    echo 'error';
                }
                $taskModel = TaskModel::model()->getById($taskId);

                // проверка того, что данные ответ уже сохранен
                if ($taskUser->answers)
                    foreach ($taskUser->answers as /* @var $userAnswer UserAnswerObject  */ $userAnswer) {
                        if ($userAnswer->quest_id == $questId) {
                            $this->showTaskWidget($lotId, $start, $taskUser, TaskModel::TYPE_INGENUITY, $taskModel);
                            return;
                        }
                    }

                $lot = LotModel::model()->getById($lotId);
                if ($user_answer && ($taskModel->is_training||$this->checkQuest($lot, $taskModel))) {
                    $userAnswerObj = new UserAnswerObject($questId, $user_answer, $answerTime);
                    $userAnswerObj->model_answer = $real_img;
                    $taskUser->answers[] = $userAnswerObj;
                    if($taskUser->last_pause != 0) {
                        $taskUser->all_pause_time = $taskUser->all_pause_time + time() - $taskUser->last_pause;
                        $taskUser->last_pause = 0;
                    }
                    $ret = $taskUser->save();
                    if (!$ret) Yii::log('answer with id='.$taskUser->id.' not saved:'.print_r($taskUser->getErrors(), true),'error');
                }
            } elseif (isset($_POST['start'], $_POST['task_id'], $_POST['lot'])) {
                $taskId = DataUtils::checkIntVal(Yii::app()->request->getPost('task_id', 0), 0);
                $lotId = DataUtils::checkIntVal(Yii::app()->request->getPost('lot', 0), 0);
                $taskModel = TaskModel::model()->getById($taskId);
                $this->widget('ext.task.TaskWidget', array(
                    'lotId' => $lotId,
                    'start' => $_POST['start'],
                    'type'	=> TaskModel::TYPE_INGENUITY,
                    'is_training'=>$taskModel->is_training,
                ));
                return;
            }

            $this->widget('ext.task.TaskWidget', array(
                'lotId' => $lotId,
                'start' => $start,
                'user' => $taskUser,
                'type'	=> TaskModel::TYPE_INGENUITY,
                'is_training'=>$taskModel->is_training,
            ));
        } else {
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
        }
    }

    public function actionIngenuityanswers() {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {

            $lotId = 0;
            $taskUser = TaskUserModel::model();
            $userId = Yii::app()->user->id;
            if (isset($_POST['questionNumber'], $_POST['lot'])) {
                $taskId = DataUtils::checkIntVal(Yii::app()->request->getPost('task_id', 0), 0);
                $lotId = DataUtils::checkIntVal(Yii::app()->request->getPost('lot', 0), 0);
                $questionNumber = DataUtils::checkIntVal(Yii::app()->request->getPost('questionNumber', 0), 0);

                $taskUser = TaskUserModel::model()->getRecord($taskId, $userId);
                if (!$taskUser) {
                    echo 'error';
                }

                $lot = LotModel::model()->getById($lotId);
            } 

            $this->widget('ext.task.TaskAnswersWidget', array(
                'lotId' => $lotId,
                'lot' => $lot,
                'taskUser' => $taskUser,
                'questionNumber' => $questionNumber,
                'type'	=> TaskModel::TYPE_INGENUITY
            ));
        } else {
            throw new CHttpException(400);
        }
    }
    
    public function actionInterview() {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest && isset($_POST['interview_answer'], $_POST['interview_id'])) {
            $model = new InterviewVoteModel;
            $model->interview_id=DataUtils::checkIntVal($_POST['interview_id'], 0);
            $model->answer_id=DataUtils::checkIntVal($_POST['interview_answer'], 0);
            $model->user_id = Yii::app()->user->id;
            $model->save();
            $modelInterview = InterviewModel::model()->with('answers')->findByPk($model->interview_id);
            $this->widget('ext.interview.InterviewWidget', array('model'=>$modelInterview));
        }
        
    }

    
    private function showTaskWidget($lotId, $start, $taskUser, $type, $taskModel) {
        $this->widget('ext.task.TaskWidget', array(
            'lotId' => $lotId,
            'start' => $start,
            'user' => $taskUser,
            'type'	=> $type,
            'is_training'=>$taskModel->is_training,
        ));
    }


    private function checkQuest($lot, $task) {
        if($lot->status == LotModel::LOT_STATUS_QUEST &&
                $task->timeStartInt <= time() && 
                $task->timeEndInt >= time()) {
            return true;
        }
        return false;
    }


    public function actionLike() {
        if (Yii::app()->request->isAjaxRequest && isset($_POST['page_id'], $_POST['action_id'])) {
           // print_r ($_POST);
            $model = new RanksActions;
            $model->page_id = $_POST['page_id'];
            $model->action_id = $_POST['action_id'];
            $model->soc_net = $_POST['soc_net'];
            $model->button = $_POST['button'];
            $model->time = time();
            $model->user_id = Yii::app()->user->id;
            $quantity = RanksMotivations::model()->getQuantityById ($model->action_id)->quantity;
            if ($model->getUserAction($model->user_id, $model->action_id, $model->page_id, $model->soc_net) === false && $model->button ==="like") {
                $currentRank = UsersRanks::model()->getCurrentRank(Yii::app()->user->getModel()->rank->score + $quantity);
                $model->addAction ($quantity, $model->user_id, $model->action_id,'+', $model->page_id, $model->soc_net);
                echo json_encode(array('rc'=>0, 'count'=>$quantity, 'score'=>Yii::app()->user->getModel()->rank->score + $quantity, 'score_start'=>$currentRank->score_start, 'score_end'=>$currentRank->score_end));
                return;
            }
            else {
                $currentRank = UsersRanks::model()->getCurrentRank(Yii::app()->user->getModel()->rank->score - $quantity);
                RanksActions::model()->delAction ($quantity, $model->user_id, $model->action_id,'-', $model->page_id, $model->soc_net);
                //echo json_encode($quantity);
                echo json_encode(array('rc'=>1, 'count'=>$quantity, 'score'=>Yii::app()->user->getModel()->rank->score - $quantity, 'score_start'=>$currentRank->score_start, 'score_end'=>$currentRank->score_end));
                return;
            }
        }
        
    }

    public function actionCommentVoice () {
        if (Yii::app()->request->isAjaxRequest && isset($_POST['comment_id'], $_POST['user_id'])) {
            LotCommentVoice::model()->addVoice($_POST['user_id'], $_POST['comment_id'], $_POST['mark']);
        }
    }

    public function actionLotStartNotif () {
        if (Yii::app()->request->isAjaxRequest && isset($_POST['lot_id'], $_POST['user_id'])) {
            UsersNotifications::model()->unsetLotStartNotif($_POST['user_id'], $_POST['lot_id']);
        }
    }

    public function actionLotResultNotif () {
        if (Yii::app()->request->isAjaxRequest && isset($_POST['lot_id'], $_POST['user_id'])) {
            UsersNotifications::model()->unsetLotResultNotif($_POST['user_id'], $_POST['lot_id']);
        }
    }

    public function actionTestLotNotif () {
        if (Yii::app()->request->isAjaxRequest && isset($_POST['lot_id'], $_POST['user_id'])) {
            $model = UsersNotifications::model()->getByUserId($_POST['user_id']);
            $model->test_lot = null;
            $model->update('test_lot');
        }
    }

    public function actionHideWrongAnswers () {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {
            $userId = Yii::app()->user->id;
            if (isset($_POST['question_id'], $_POST['count'])) {
                $count = $_POST['count'];
                $userRank = UsersRanks::model()->getByUserId($userId);
                if(($count == 1 && $userRank->hide_wrong_1 > 0) || ($count == 2 && $userRank->hide_wrong_2 > 0)) {
                    $questionModel = QuestionModel::model()->getById($_POST['question_id']);
                    $taskUser = TaskUserModel::model()->getRecord($_POST['task_id'], $userId);
                    if($questionModel) {
                        $i = 0;
                        $res = array();
                        foreach ($questionModel->ans as $ans) {
                            if ($i < $count){
                                if (!$ans->is_answer) {
                                    $res[] = $ans->id;
                                    $i++;
                                }
                            }
                        }
                    }
                    if($count == 1){
                        $userRank->hide_wrong_1 = $userRank->hide_wrong_1 - 1;
                        $userRank->update('hide_wrong_1');
                        $taskUser->hide_wrong_1 = $taskUser->hide_wrong_1 + 1;
                        $taskUser->update('hide_wrong_1');
                    } elseif($count == 2) {
                        $userRank->hide_wrong_2 = $userRank->hide_wrong_2 - 1;
                        $userRank->update('hide_wrong_2');
                        $taskUser->hide_wrong_2 = $taskUser->hide_wrong_2 + 1;
                        $taskUser->update('hide_wrong_2');
                    }
                    echo json_encode($res);
                } else {
                    $res['error'] = 'Что-то неправильно';
                    echo json_encode($res);
                }   
            }
        }

    }

    public function actionTestLotHideWrongAnswers () {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {
            $userId = Yii::app()->user->id;
            if (isset($_POST['question_id'], $_POST['count'])) {
                $count = $_POST['count'];
                $clue = 'hide_wrong_'.$count;
                $questionModel = QuestionModel::model()->getById($_POST['question_id']);
                $temporary = TemporaryModel::model()->findByPk($userId);
                $temporary->$clue = 0;
                $temporary->update($clue);
                if($questionModel) {
                    $i = 0;
                    $res = array();
                    foreach ($questionModel->ans as $ans) {
                        if ($i < $count){
                            if (!$ans->is_answer) {
                                $res[] = $ans->id;
                                $i++;
                            }
                        }
                    }
                }
                echo json_encode($res); 
            }
        }

    }

    public function actionTimePause () {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {
            $userId = Yii::app()->user->id;
            if (isset($_POST['question_id']) && isset($_POST['task_id'])) {
                $userRank = UsersRanks::model()->getByUserId($userId);
                if($userRank->task_pause > 0) {
                    $taskUser = TaskUserModel::model()->getRecord($_POST['task_id'], $userId);
                    if($taskUser->times_paused < TaskUserModel::TIMES_PAUSED) { 
                        $taskUser->times_paused = $taskUser->times_paused + 1;
                        $taskUser->last_pause = time();
                        $taskUser->update('times_paused', 'last_pause');
                        $userRank->task_pause = $userRank->task_pause - 1;
                        $userRank->update('task_pause');
                        $res['success'] = 'Успех';
                        echo json_encode($res);
                    } else {
                        $res['error'] = 'Превышено максимальное количество подсказок в задании';
                        echo json_encode($res);
                    }
                } else {
                    $res['error'] = 'Что-то неправильно';
                    echo json_encode($res);
                }   
            }
        }
    }

    public function actionTestLotTimePause () {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {
            $userId = Yii::app()->user->id;
            if (isset($_POST['question_id'])) {
                $temporary = TemporaryModel::model()->findByPk($userId);
                if($temporary->task_pause > 0) {
                    $temporary->task_pause = $temporary->task_pause - 1;
                    $temporary->update('task_pause');
                    $res['success'] = 'Успех';
                    echo json_encode($res);
                } else {
                    $res['error'] = 'Что-то неправильно';
                    echo json_encode($res);
                }   
            }
        }
    }

    public function actionQuestionsThemeClue() {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {
            $userId = Yii::app()->user->id;
            if (isset($_POST['lot_id'])) {
                $userRank = UsersRanks::model()->getByUserId($userId);
                $task = TaskModel::model()->getTaskIdByLot($_POST['lot_id']);
                $res['theme'] = $task->theme;
                if(isset($_COOKIE['lotThemeClue']) && $_COOKIE['lotThemeClue'] == $_POST['lot_id']) {
                    echo json_encode($res);
                } elseif($userRank->questions_theme > 0) {
                    $userRank->questions_theme = $userRank->questions_theme - 1;
                    $userRank->update('questions_theme');
                    echo json_encode($res);
                } else {
                    $res['error'] = 'Ошибка';
                    echo json_encode($res);
                }
            }
        }
    }

    public function actionQuestionsCountClue() {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {
            $userId = Yii::app()->user->id;
            if (isset($_POST['lot_id'])) {
                $userRank = UsersRanks::model()->getByUserId($userId);
                $task = TaskModel::model()->getTaskIdByLot($_POST['lot_id']);
                $t = explode('/',$task->proportion);
                $count = 0;
                foreach ($t as $k) {
                    $count = $count + $k;
                }
                $res['count'] = $count;
                if(isset($_COOKIE['lotCountClue']) && $_COOKIE['lotCountClue'] == $_POST['lot_id']) {
                    echo json_encode($res);
                } elseif($userRank->questions_count > 0) {
                    $userRank->questions_count = $userRank->questions_count - 1;
                    $userRank->update('questions_count');
                    echo json_encode($res);
                } else {
                    $res['error'] = 'Ошибка';
                    $res['count'] = false;
                    echo json_encode($res);
                }
            }
        }
    }

    public function actionSecondAttempt() {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {
            $userId = Yii::app()->user->id;
            if (isset($_POST['task_id'])) {
                $userRank = UsersRanks::model()->getByUserId($userId);
                if($userRank->second_attempt > 0) {
                    $userRank->second_attempt = $userRank->second_attempt - 1;
                    $userRank->update('second_attempt');
                    TaskUserModel::model()->getRecord($_POST['task_id'], $userId)->delete();
                    echo json_encode('ok');
                }
            }
        }
    }

    public function actionTestLotSecondAttempt() {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {
            $userId = Yii::app()->user->id;
            if (isset($_POST['task_id'])) {
                $temporary = TemporaryModel::model()->findByPk($userId);
                if($temporary->second_attempt > 0) {
                    $temporary->second_attempt = $temporary->second_attempt - 1;
                    $temporary->update('second_attempt');
                    TaskUserModel::model()->getRecord($_POST['task_id'], $userId)->delete();
                    //TemporaryModel::model()->findByPk($userId)->delete();
                    echo json_encode('ok');
                }
            }
        }
    }

    public function actionFbInvite() {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest && isset($_POST['ids'])) {
            $userId = Yii::app()->user->id;
            $user = UserModel::model()->getById($userId);
            $ids = explode(",", $_POST['ids']);
            $arr = array();
            $error = false;
            $score = false;
            foreach ($ids as $id) {
                $fb = new FbIdsModel();
                $fb->id = $id;
                $fb->invite_id = $userId;
                $fb->date = time();
                if($fb->save()) $arr[] = $id;
            }
            if(count($arr) > 0) {
                $user->updateCountInvited($userId, count($arr));
                $action = 'invite_use';
                $motiv = RanksMotivations::model()->getQuantityById($action);
                if($motiv->quantity > 0) {
                    $quantity = $motiv->quantity * count($arr);
                    RanksActions::model()->addAction($quantity, $user->id, $action,'+');
                    $score = $user->rank->score;
                }
            } else {
                $error = 'Ваши друзья уже приглашены';
            }
            echo json_encode(array('error'=>$error, 'score'=>$score));
        }
    }


    public function actionBuyGood() {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {
            $userId = Yii::app()->user->id;
            $userRank = UsersRanks::model()->getByUserId($userId);
            if (isset($_POST['clue'])) {
                $clue = $_POST['clue'];
                $good = Goods::model()->findByPk($clue);
                if($clue == 'lot_0' || $clue == 'lot_1' || $clue == 'lot_2') {
                    if($userRank->$clue == '-1') {
                        echo json_encode(array('error'=>'Эти лоты уже доступны для Вас'));
                        return;
                    }
                }
                if($userRank->score >= $good->price){
                    GoodsActions::model()->addAction($userId, $good->id, $good->price);
                    $userRank = $userRank->updateSpent($userId, $good->id, $good->price);
                    if($clue == 'all_lot_0' || $clue == 'all_lot_1' || $clue == 'all_lot_2') {
                        $number = preg_replace("/[^0-9]/", '', $clue);
                        $clue = 'lot_'.$number;
                    }
                    echo json_encode(array('score'=>$userRank->score, 'spent'=>$userRank->spent, 'clue'=>$clue, 'res'=>$userRank[$clue]));
                } else {
                    echo json_encode(array('error'=>'У вас недостаточно баллов'));
                }
            }
        }
    }

    public function actionOpeninviter() {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {
            $userId = Yii::app()->user->id;
            $error = null;
            $validator = new CEmailValidator;
            if (isset($_POST['password_box']) && isset($_POST['email_box']) && $validator->validateValue($_POST['email_box'])) {
                $inviter = Yii::app()->openinviter;
                $user_domain = explode('@',$_POST['email_box']);
                if (isset($user_domain[1])) {
                    $user_domain = $user_domain[1];
                    $availablePlugins = InvitesModel::model()->availablePlugins;
                    foreach ($availablePlugins as $plugin => $value) {
                        if(is_array($value)) foreach ($value as $p) {
                            if (preg_match('/'.$p.'/',$user_domain)) {
                                $provider = $plugin;
                                break;
                            }
                        } elseif (preg_match('/'.$value.'/',$user_domain)) {
                            $provider = $value;
                            break;
                        }
                    }
                }

                if($provider == 'yandex') $_POST['email_box'] == $user_domain[0];

                $inviter->startPlugin($provider);
                $internal=$inviter->getInternalError();
                if($provider == 'gmail' && $internal) {
                    $error='Для приглашения друзей с Gmail воспользуйтесь кнопкой слева';
                } elseif($internal)
                    $error='Пока Вы не можете использовать данный почтовый сервис.';
                elseif (!$inviter->login($_POST['email_box'],$_POST['password_box'])) {
                    $internal=$inviter->getInternalError();
                    $error=($internal?$internal:"Логин или пароль введены неверно.");
                } elseif (false===$contacts=$inviter->getMyContacts())
                    $error="Ваш список контактов недоступен, сообщите нам об этом.";
                elseif (count($contacts) == 0) $error = "Ваш список контактов пустой.";
                else {
                    $arr = array();
                    foreach ($contacts as $email => $value) {
                        if(!InvitesModel::model()->getByEmail($email)) {
                            $arr[$email] = $value;
                        }
                    }
                    $contacts = $arr;
                    if(count($contacts) == 0) $error = "Все контакты приглашены. Попробуйте другой e-mail или социальную сеть";
                }
                echo json_encode(array('error'=>$error, 'contacts'=>$contacts));
            } else echo json_encode(array('error'=>'Логин или пароль введены неверно.', 'contacts'=>false));
        }
    }

    public function actionInvite() {
        if (Yii::app()->request->isAjaxRequest && !Yii::app()->user->isGuest) {
            $userId = Yii::app()->user->id;
            $user = UserModel::model()->getById($userId);
            $score = false;
            if (isset($_POST['email'])) {
                $success = array();
                foreach ($_POST['email'] as $key => $email) {
                    $email = htmlspecialchars($email);
                    $validator = new CEmailValidator;
                    if($validator->validateValue($email)) {
                        if(!InvitesModel::model()->getByEmail($email)) {
                            Yii::app()->sendMail->sendUserInvitation($email, $this, array('whoDid'=>$user));
                            $invites = new InvitesModel();
                            $invites->email = $email;
                            $invites->date = time();
                            $invites->user_id = $userId;
                            $invites->send_again = $_POST['send_again'];
                            $invites->save();
                            $success[] = $email;
                        }
                    }
                }
                if(count($success) > 0) {
                    $user->updateCountInvited($userId, count($success));
                    $action = 'invite_use';
                    $motiv = RanksMotivations::model()->getQuantityById($action);
                    if($motiv->quantity > 0) {
                        $quantity = $motiv->quantity * count($success);
                        RanksActions::model()->addAction ($quantity, $user->id, $action,'+');
                        $score = $user->rank->score;
                    }
                    if (isset($_POST['send_again'])) {
                        $cronEmail = new CronEmailModel();
                        $cronEmail->createCronByUserInvite($success, $user, 0, 5);
                    }
                    echo json_encode(array('success'=>$success, 'error'=>false, 'score'=>$score));
                } else echo json_encode(array('success'=>false, 'error'=>'Приглашения не были отправлены. Вы не выбрали ни одного email-а или ваших друзей уже пригласили.', 'score'=>$score));
            } else echo json_encode(array('success'=>false, 'error'=>'Выберите хотя бы один email для отправки приглашения.', 'score'=>$score));
        }
    }

    public function actionInviteWithEmail() {
        $user = Yii::app()->user->getModel();
        $msg = '';
        $rc = 1;
        $score = false;
        if (isset($_POST['email'])) {
            $email = htmlspecialchars($_POST['email']);
            $add_message = htmlspecialchars($_POST['add_message']);
            $validator = new CEmailValidator;
            if(!$validator->validateValue($email)) {
                $msg = 'Введите правильный e-mail';
            } elseif(InvitesModel::model()->getByEmail($email)) {
                $msg = 'Вашего друга уже пригласили';
            } elseif(!Yii::app()->emailCheck->check($email)) {
                $msg = 'Введённый e-mail не существует';
            } else {
                Yii::app()->sendMail->sendUserInvitation($email, $this, array('whoDid'=>$user, 'add_message'=>$add_message));
                $invites = new InvitesModel();
                $invites->email = $email;
                $invites->date = time();
                $invites->user_id = $user->id;
                if ($invites->save()) {
                    $user->updateCountInvited();
                    //Начисление очков пользователю за приглашение
                    $action = 'invite_use';
                    $motiv = RanksMotivations::model()->getQuantityById ($action);
                    RanksActions::model()->addAction($motiv->quantity, $user->id, $action,'+', $email);
                    //
                    $score = $user->rank->score;
                    $msg = 'Приглашение отправлено';
                    $rc = 0;
                }
            }
            echo json_encode(array('rc'=>$rc, 'data'=>$msg, 'score'=>$score));
        }
    }
}

?>
