<?php

namespace Admin\Controller;

//use Realsoft\Model\GlobalSettings\GlobalSettings;
use Laminas\Mvc\Controller\AbstractActionController;

use Laminas\View\Model\ViewModel;
use Admin\Model\AdminService;

use Admin\Model\AuthService;
use Admin\Model\NotificationSevice;

use Laminas\Http\Request;
use Laminas\View\Model\JsonModel;
use Laminas\View\View;

class IndexController extends AbstractActionController {
    private $adminService;
    private $authService;
    private $notificationService;
    public function __construct(AdminService $adminService ,AuthService $authService ,NotificationSevice $notificationService ) {
        $this->adminService = $adminService;
        $this->authService = $authService;
        $this->notificationService = $notificationService;
    }
  
    public function indexAction()
    {
        $this->layout('admin/layout/layout');
        if(!$this->authService->logincheck()){
            return $this->redirect()->toRoute('login');
        }
        $all = $this->adminService->getAll();
       
        $view = new ViewModel(['functions'=>$all]);
        $view->setTemplate('admin/index/index');
      
       
        return $view;
    }

    public function homeAction()
    {
        $this->layout('admin/layout/layout');
        if(!$this->authService->logincheck()){
            return $this->redirect()->toRoute('login');
        }
        
       
        $view = new ViewModel();
        $view->setTemplate('admin/index/home');
      
       
        return $view;
    }


    

    public function addAction()
    {
        if(!$this->authService->logincheck()){
            return $this->redirect()->toRoute('login');
        }
        $postedData = $this->params()->fromPost();
        $name =  $postedData['name'];
        $key = $postedData['key'];
        $desc = $postedData['desc'];
        if($name != '' && $key !="" && $desc != ''){
            $data = ['name'=>$name , 'key' => $key , 'desc' => $desc];
            $this->adminService->createNewItem($data);
            
        }
            
        
        
        return $this->redirect()->toRoute('functions');
    }

    public function deleteAction()
    {
        if(!$this->authService->logincheck()){
            return $this->redirect()->toRoute('login');
        }
        $postedData = $this->params()->fromPost();
        $id = $postedData['id'];

        if($id && $id != ''){
            $this->adminService->delete($id);
        }
        return $this->redirect()->toRoute('functions');
    }


    public function editAction(){
        if(!$this->authService->logincheck()){
            return $this->redirect()->toRoute('login');
        }
        $postedData = $this->params()->fromPost();
        $name =  $postedData['name'];
        $key = $postedData['key'];
        $desc = $postedData['desc'];
        $id = $postedData['id'];
        if($name != '' && $key !="" && $desc != ''){
            $data = ['name'=>$name , 'key' => $key , 'desc' => $desc];
            $this->adminService->update($id,$data);
            
        }
        return $this->redirect()->toRoute('functions');
    }
   
    public function jsonAction(){
        $all = $this->adminService->getAll();
       
        return new JsonModel($all);

    }

    public function loginAction(){
       
        $this->layout('admin/layout/layout');
        if($this->authService->logincheck()){
            return $this->redirect()->toRoute('admin');
        }
        $postedData = $this->params()->fromPost();
        $message = false;
        if($postedData['username'] && $postedData['password']){
            $name = $postedData['username'];
            $password = $postedData['password'];
            if($name != '' && $password != ''){
               
                if($this->authService->login($name,$password)){
                   
                    return $this->redirect()->toRoute('admin');
                }else{
                    $message = true;
                }
            }
        }

        $view = new ViewModel(['message' => $message]);
        $view->setTemplate('admin/index/login');
        return $view;
    }

    public function logoutAction(){
        if (isset($_COOKIE['login'])) {
            unset($_COOKIE['login']); 
            setcookie('login', null, -1, '/'); 
           
        }  
        if (isset($_COOKIE['pass'])) {
            unset($_COOKIE['pass']); 
            setcookie('pass', null, -1, '/'); 
           
        }  
        return $this->redirect()->toRoute('login');
    } 
}

