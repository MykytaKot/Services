<?php

namespace Admin\Model;

class AuthService {
    protected $password = 'testservice';
    protected $login = 'testservice';
    protected $cypher = 'AES-128-CTR';
    protected $key = 'realvia';


    public function login($name,$password){
       
        if($password == $this->password && $name == $this->login ){
            setcookie("login",openssl_encrypt($name, $this->cypher, $this->key)  , time()+3600);
            setcookie("pass",openssl_encrypt($password, $this->cypher, $this->key) , time()+3600);
           
            
            return true;
        }else{
            return false;
        }
       
    }

    public function logincheck(){
       
        if(openssl_decrypt($_COOKIE['pass'], $this->cypher, $this->key) == $this->password && openssl_decrypt($_COOKIE['login'], $this->cypher, $this->key) == $this->login ){
           
            return true;
        }else{
            return false;
        }
    }

}