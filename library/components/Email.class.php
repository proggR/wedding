<?php

require_once dirname(__FILE__)."/../CssToInlineStyles/CssToInlineStyles.php";
require_once dirname(__FILE__)."/../PHPMailer/class.phpmailer.php";
require_once UI_CONTROLLER;
class Email{
    private static $templates;
    public function __construct(){
        $this->sender_email = SYSTEM_EMAIL;
        $this->sender_display = SYSTEM_DISPLAY;
        $this->subject = '';
        $this->recipients = array();
        $this->search = array(':site');
        $this->replace = array(SITE);
    }

    /**
     * Accepts array of values, must contain an index called 'name' and another called 'email'.
     */
    public function addRecipient($recipient){
        if(array_key_exists('name', $recipient) && array_key_exists('email', $recipient)){
            $this->recipients[] = $recipient;
            return true;
        }
        return false;
    }
    
    public function addKeyValue($key,$value){
        $this->search[] = $key;
        $this->replace[] = $value;
    }
    
    public function loadTemplate($type,$var=false){
        $dir = dirname(__FILE__).'/email_templates/';
        if(!self::$templates){
            if(!$templates = Cache::get('email_templates')){
                $templates = array();
                if ($handle = opendir($dir)) {
                    while (false !== ($file = readdir($handle))) {
                            $split = explode('.',$file);
                            $templates[strtolower($split[0])] = array('file'=>$file,'class'=>$split[0]);
                    }
                    closedir($handle);
                }
                Cache::set('email_templates',$templates);
            }
            self::$templates = $templates;
        }
        if($type){
            if(array_key_exists($type, self::$templates)){
                if (@$template =  file($dir.self::$templates[$type]['file'])){
                    $this->subject = $template[0];
                    array_shift($template);
                    $this->template = implode("\n",$template);
                }
            }
        }
        return false;
    }

    public function send($html = false, $fake = false){
        $e = new PHPMailer();
        $ui = new UIHelper();
        $ui->template = EMAIL_LAYOUT;
        $ui->page = EMAIL_LAYOUT_PAGE;            
        $e->SetFrom($this->sender_email,$this->sender_display);
        $e->addReplyTo($this->sender_email, $this->sender_display);        
        $e->isSMTP();
        $e->SMTPDebug = 0;  // debugging: 1 = errors and messages, 2 = messages only
        $e->SMTPAuth = true;  // authentication enabled
        $e->SMTPSecure = MAILER_SECURE; // secure transfer enabled REQUIRED for GMail
        $e->Host = MAILER_HOST;
        $e->Port = MAILER_PORT; 
        $e->Username = MAILER_USER;  
        $e->Password = MAILER_PASS;           
        $e->Subject = $this->subject;        
        if($this->recipients){
            foreach($this->recipients as $recipient){                
                $search = $this->search;
                $replace = $this->replace;
                foreach($recipient as $key=>$value){
                    $search[] = ':'.$key;
                    $replace[] = $value;
                }
                $message = str_replace($search, $replace, $this->template);
                $ui->addView('email:content',$message,CONTENT);
                $html = $ui->show(true);
                $ui->addStyle('templates/styles/email.css');
                $css = $ui->getCSS();
                $ui->clearModules();

                $ctis = new CssToInlineStyles($html,$css);
                $ctis->setExcludeMediaQueries();
                $ctis->setStripOriginalStyleTags();
                $markup = $ctis->convert();
                $html = $markup;            

                $e->addAddress($recipient["email"],$recipient['name']);
                $e->AltBody = $message;
                $e->Body = $html;
                $e->isHTML(true);
                if(ENV == 'prod' || !$fake)
                    $e->send();
                else{
                    file_put_contents(DEV_MOCK_EMAIL, $html);
                }
                $e->ClearAllRecipients();

                //mail($recipient["email"], $this->subject, $html, $headers);
            }
        }
    }

    public function sendLater($html = false){
        $ui = new UIHelper();
        $ui->template = EMAIL_LAYOUT;
        $ui->page = EMAIL_LAYOUT_PAGE;       
        $q = Model::get('queuedemail');
        if($this->recipients){
            $stmt = DataMapper::getStatement();
            $stmt->query = 'emails';
            $stmt->type = 'insert';
            foreach($this->recipients as $recipient){                
                $search = $this->search;
                $replace = $this->replace;
                foreach($recipient as $key=>$value){
                    $search[] = ':'.$key;
                    $replace[] = $value;
                }
                $message = str_replace($search, $replace, $this->template);
                $ui->addView('email:content',$message,CONTENT);
                $html = $ui->show(true);
                $css = $ui->getCSS();
                $ui->clearModules();

                $ctis = new CssToInlineStyles($html,$css);
                $ctis->setExcludeMediaQueries();
                $ctis->setStripOriginalStyleTags();
                $markup = $ctis->convert();
                $html = $markup;            

                $stmt->bind('email',$recipient['email'],'string');
                $stmt->bind('name',$recipient['name'],'string');
                $stmt->bind('alt',$message,'html');
                $stmt->bind('body',$html,'html');
                $stmt->bind('subject',$this->subject,'string');

                DataMapper::mapResults($q,$stmt);
            }
        }
    }
}