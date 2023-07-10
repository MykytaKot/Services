<?php

namespace Admin\Controller;

//use Realsoft\Model\GlobalSettings\GlobalSettings;
use Laminas\Mvc\Controller\AbstractActionController;

use Laminas\View\Model\ViewModel;


use Admin\Model\AuthService;
use Admin\Model\NotificationSevice;

use Laminas\Http\Request;
use Laminas\View\Model\JsonModel;
use Laminas\View\View;

class NotificationController extends AbstractActionController {
   
    private $authService;
    private $notificationService;
    public function __construct(AuthService $authService ,NotificationSevice $notificationService ) {
        
        $this->authService = $authService;
        $this->notificationService = $notificationService;
    }
    public function indexAction()
    {
        $this->layout('admin/layout/layout');
        if(!$this->authService->logincheck()){
            return $this->redirect()->toRoute('login');
        }
        $all = $this->notificationService->getAll();
       
        $view = new ViewModel(['functions'=>$all]);
        $view->setTemplate('admin/index/notifications');
      
       
        return $view;
    }
    public function createAction()
    {
        $this->layout('admin/layout/layout');
        if(!$this->authService->logincheck()){
            return $this->redirect()->toRoute('login');
        }
        $all = $this->notificationService->getAll();
       
        $view = new ViewModel(['notifications'=>$all]);
        $view->setTemplate('admin/index/notificationscreate');
      
       
        return $view;
    }
    public function addAction()
    {
        if(!$this->authService->logincheck()){
            return $this->redirect()->toRoute('login');
        }
        $postedData = $this->params()->fromPost();
        $title =  $postedData['title'];
        $intro = $postedData['intro'];
        $content = $postedData['editordata'];
        $sendto = $postedData['sendto'];
        $type = $postedData['type'];
        if($title != '' && $intro !="" && $content != ''){
            $data = ['title'=>$title , 'intro' => $intro , 'content' => $content, 'sendto' => $sendto, 'type' => $type];
            $this->notificationService->createNewItem($data);
            
        }
            
        
        
        return $this->redirect()->toRoute('notifications');
    }

    public function deleteAction()
    {
        if(!$this->authService->logincheck()){
            return $this->redirect()->toRoute('login');
        }
        $postedData = $this->params()->fromPost();
        $id = $postedData['id'];

        if($id && $id != ''){
            $this->notificationService->delete($id);
        }
        return $this->redirect()->toRoute('notifications');
    }
}