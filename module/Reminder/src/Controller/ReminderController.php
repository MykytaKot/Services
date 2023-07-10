<?php
namespace Reminder\Controller;
use Laminas\Mail;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;

use Laminas\Mvc\Controller\AbstractActionController;
use Application\Core\CollectionMap;
use MongoDB\Database;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;
use Laminas\View\Model\JsonModel;


class ReminderController extends AbstractActionController{
    protected $reminderCollection;
    protected $reminderLogCollection;
    protected $settings;
    protected $locations_collection;

    protected $email_limit = 20;
    protected $name_of_sender  = 'Realvia';
    protected $email_of_sender = 'devs@realvia.sk';

    protected $DEBUG_FUNCTIONS = true;

    public function __construct($reminderCollection, $reminderLogCollection, $settings,  $locations_collection) {
        $this->reminderCollection = $reminderCollection;
        $this->reminderLogCollection = $reminderLogCollection;
        $this->settings = $settings;
        $this->locations_collection = $locations_collection;
    }
    public function indexAction(){
        echo "index";
    }
    public function sendAction(){
        $mail = new Mail\Message();
        $transport = new SmtpTransport();
        $transport->setOptions(new SmtpOptions($this->settings->email));
        $reminders = $this->reminderCollection->find([], ['limit' => $this->email_limit,'sort'=>['date_to_send'=>-1]]);
        $i=0;
        foreach($reminders as $reminder){
            if(strtotime($reminder['date_to_send']) < strtotime("now")) {
                $clientHtml = '';
                $clientName = (isset($reminder['clientName']) && !empty($reminder['clientName'])) ? $reminder['clientName'] : null;
                $clientUrl  = (isset($reminder['clientUrl']) && !empty($reminder['clientUrl'])) ? $reminder['clientUrl'] : null;
                if(!empty($clientName) && !empty($clientUrl)) {
                    $clientHtml .= '<p>Klient URL: <a href="'.$reminder['clientUrl'].'" target="_blank">'.$reminder['clientName'].'</a></p>';
                }
                $messageText = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
                <html xmlns=\"http://www.w3.org/1999/xhtml\">
                <head>
                <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
                <title>Reminder</title>
                <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"/>
                </head>";
                $messageText .= "<body style=\"margin: 0; padding: 1em;\">
                <h1 style=\"background-color:#00c07f;color:white;margin:0px;text-align:center;width:90%;padding:0.5em\">". $this->name_of_sender ."</h1><br>
                <h3 style=\"margin:0px;\">". $reminder['subject'] ."</h3><br>
                <p>". $reminder['text'] ."</p>";
                $messageText .= $clientHtml;
                $messageText .= "</body>
                </html>";
                /*$messageText = "This is reminder from " . $this->name_of_sender . "\n"
                ."Text of your message : \n" . $reminder['text'];
                ;*/
                $mail->getHeaders()->addHeaderLine('MIME-Version', '1.0');
                $mail->getHeaders()->addHeaderLine('Content-type', 'text/html; charset=utf-8');
                $mail->setBody($messageText);
                $mail->setFrom($this->email_of_sender, $this->name_of_sender);
                $mail->setTo($reminder['to'], $reminder['to']);
                $mail->setSubject($reminder['subject']);
                $mail->setEncoding('UTF-8');
                $transport->send($mail);
                $this->reminderLogCollection->insertOne([
                    'email'=> $reminder['to'],
                    'data' => $messageText,
                    'action'=> 'Successful',
                ]);
                $this->reminderCollection->deleteOne(['_id' => $reminder['_id']]);
            }
            $i++;
            if($i> $this->email_limit){
                return;
            }
        }
    }
    public function saveAction(){
        $request = $this->getRequest();
        $type = $this->params()->fromPost('type', null);
        if($type == "emailReminder"){
            $data = $this->params()->fromPost('data', null);
            if($data == null){
                $this->reminderLogCollection->insertOne([ 
                    'email'=> 'No email',
                    'action'=> 'Reminder request without data',
                ]);
                echo 'data null';
                die();
            }
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            echo $data['subject'];
            $this->reminderCollection->insertOne([
                'clientName'   => (isset($data['clientName']) && !empty($data['clientName'])) ? $data['clientName'] : '',
                'clientUrl'    => (isset($data['clientUrl']) && !empty($data['clientUrl'])) ? $data['clientUrl'] : '',
                'created'      => date("d.m.Y"),
                'date_to_send' => $data['dateToSend'],
                'fromUrl'      => $ip,
                'subject'      => $data['subject'],
                'text'         => $data['text'],
                'to'           => $data['to'],
                'type'         => $type,
            ]);
            $this->reminderLogCollection->insertOne([
                'action'       => 'Reminder request',
                'clientName'   => (isset($data['clientName']) && !empty($data['clientName'])) ? $data['clientName'] : '',
                'clientUrl'    => (isset($data['clientUrl']) && !empty($data['clientUrl'])) ? $data['clientUrl'] : '',
                'created'      => date("d.m.Y"),
                'data'         => $data,
                'date_to_send' => $data['dateToSend'],
                'email'        => $data['to'],
                'fromUrl'      => $ip,
                'subject'      => $data['subject'],
            ]);
            echo 'success';
        }
        else{
            $this->reminderLogCollection->insertOne([ 
                'email'=> "No email",
                'action'=> 'Not a reminder request. type: ' . $type ,
            ]);
            echo 'not a reminder request';
        }
    }
    public function listAction(){
        if($this->DEBUG_FUNCTIONS){
            $reminders = $this->reminderCollection->find([], ['limit' => $this->email_limit,'sort'=>['date_to_send'=>-1]]);
            //var_dump($reminders);
            foreach($reminders as $reminder){
                var_dump($reminder);
            }
        }else{echo 'no debug';}
    }
    /*public function deleteAllAction(){
        if($this->DEBUG_FUNCTIONS){
            $reminders = $this->reminderCollection->find([], ['limit' => $this->email_limit,'sort'=>['date_to_send'=>-1]]);
            //var_dump($reminders);
            foreach($reminders as $reminder){
                $this->reminderCollection->deleteOne(['_id' => $reminder['_id']]);
            }   
        }
    }*/

    /*public function importCountriesAction(){
        if(DEBUG_FUNCTIONS){
            if (($handle = fopen("obce.csv", "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    list($obecAreality,$okresAreality,$nazov)=explode(';',$data[0]);
                    
                    $edited = $this->locations_collection->updateMany(array('name'=> $nazov,'type'=>'obec'), array('$set' => array('arealitySk' => $obecAreality)));
                    if(!$edited->modifiedCount > 0){
                     echo $nazov.' obec<br>';
                     $nazov = str_replace(' - ','-',$nazov);
                     $nazov = str_replace('mestská časť ','',$nazov);
                     $edited = $this->locations_collection->updateMany(array('name'=> $nazov,'type'=>'obec'), array('$set' => array('arealitySk' => $obecAreality)), ['multi' => true]);
                    if($edited->modifiedCount > 0){
                     echo $nazov.' OK obec<br>';
                        }
                    }
                }
                fclose($handle);
            }
            if (($handle = fopen("okresy.csv", "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        list($okresAreality,$krajAreality,$nazov)=explode(';',$data[0]);
                        $edited = $this->locations_collection->updateMany(array('name'=>$nazov,'type'=>'okres'), array('$set' => array('arealitySk' => $okresAreality)), ['multi' => true]);
                        if(!$edited->modifiedCount > 0){
                             echo $nazov.' okres<br>';
                        }else{
                            echo $nazov.' OK okres<br>';
                        }
                }
                fclose($handle);
            }
        }
    }*/
}