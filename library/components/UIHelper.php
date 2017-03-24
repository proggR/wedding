<?php

class UIHelper{    

    function __construct(){
        $this->template = STANDARD_LAYOUT;
        $this->page = STANDARD_LAYOUT_PAGE;
        $this->modules = array();
        $this->scripts = array();
        //$this->default_styles = array();
        $this->default_styles = array('templates/styles/style.css','templates/styles/bootstrap-theme.css','templates/styles/bootstrap.css','templates/styles/css-reset.css','templates/styles/font.css');
        $this->styles = array();
        $this->js = array();
        $this->url = false;
        if(!$views = Cache::get('views')){
            $views = array();
            if ($handle = opendir(VIEW_PATH)) {
                while (false !== ($file = readdir($handle))) {
                    if($file == "." || $file == ".."){
                        continue;
                    }elseif(@is_dir(VIEW_PATH."/{$file}")){
                        if ($inner_handle = opendir(VIEW_PATH."/{$file}")) {
                            while (false !== ($mv = readdir($inner_handle))) {
                                $split = explode('.',$mv);
                                if(isset($split[0]) && $split[0])
                                    $views["{$file}:{$split[0]}"] = array("class"=>"{$file}_{$split[0]}","path"=>"{$file}/{$split[0]}.php");        
                            }
                            closedir($inner_handle);
                        }
                    }else{
                        $split = explode('.',$file);
                        $views[$split[0]] =  array("class"=>"{$split[0]}","path"=>"{$split[0]}.php");        
                    }                    
                }
                closedir($handle);
            }
            Cache::set('views',$views);
        }
        if(!$pages = Cache::get('pages')){
            $pages = array();
            if ($handle = opendir(PAGE_PATH)) {
                while (false !== ($file = readdir($handle))) {
                    $split = explode('.',$file);
                    $pages[] = $split[0];
                }
                closedir($handle);
            }
            Cache::set('pages',$pages);
        }
        $this->views = $views;
        $this->pages = $pages;
        $this->custom_title = false;
    }

    public function show($return = false){
        $this->loadSuccesses();
        $this->loadErrors();

        if($return){
            /**
            * @Todo make support for PageViews so this isn't so crappy
            */
            foreach($this->default_styles as $style){
                $this->addStyle($style,true);
            }
        }

        require_once $this->template;
        $r = new ReflectionClass($this->page);
        $page = $r->newInstanceArgs(array($this->modules,$this->scripts,$this->styles,$this->js,$this->url));
        if($return){
            ob_start();
        }         
        $page->current_route = isset($this->current_route)?$this->current_route:'/';
        if($this->custom_title) $page->page_title = $this->custom_title;
        $page->show();
        session_write_close();
        if($return){
            return ob_get_clean();
        }
    }

    private function loadSuccesses(){
        $successes = MessageHandler::getSuccesses(true);
        if($successes && count($successes)){
            $suc_arr = array();
            foreach($successes as $success){
                if($success['display']){
                    $suc_arr[] = '<span class="achievement-msg">'.$success['message'].'</span>';
                }
            }
            $sucs = implode('<br/>',$suc_arr);
            $this->addView('utility:success',$sucs,SUCCESS_CONTAINER);
        }        
    }

    private function loadErrors(){
        $errors = MessageHandler::getErrors(true);
        if($errors && count($errors)){
            $err_arr = array();
            foreach($errors as $error){
                if($error['display']){
                    $err_arr[] = '<span class="error_msg">'.$error['message'].'</span>';
                }
            }
            $errs = implode('<br/>',$err_arr);
            $this->addView('utility:error',$errs,ERROR_CONTAINER);
        }        
    }


    /**
    * Instantiates view and readies any CSS or scripts required by that view
    * @param $viewname: view name you want to use, colon delimited for 'model views'
    * @param $params: scalar or array representation of data to be bound to view
    * @param $options: array of options to load view with, each becomes instance variable of view
    */ 
    protected function retrieveView($viewname,$params = null,$options = false){
        if(!array_key_exists($viewname,$this->views)) {return false;}
        require_once VIEW_PATH."/{$this->views[$viewname]["path"]}";
        $ref = new ReflectionClass($this->views[$viewname]["class"]);
        if(!$ref) return false;
        //if(!is_array($params)){
            $params = array($params);

        //}
        if($options){
            if(isset($options['id'])){
                $params[] = $options['id'];
            }else{
                $params[] = $this->incrementModule($viewname);
            }
            $params[] = $options;
        }else{
            $params[] = $this->incrementModule($viewname);            
        }
        $view = $ref->newInstanceArgs($params);
        if(!$view) return false;
        if(isset($view->scripts)){
            foreach($view->scripts as $script){
                $this->addScript($script);
            }
        }
        if(isset($view->js)){
            foreach($view->js as $js){
                $this->addJS($js);
            }
        }

        if(isset($view->styles)){
            foreach($view->styles as $style){
                $this->addStyle($style);
            }
        }
        return $view;        
    }
    /**
    * Attaches view instance to list of views that will be displayed when page is built after all processes have finished
    * @param $module: view instance to attach
    * @param $key: instructions for the UIHelper dictating which part of the page layout to display view in
    */ 
    public function attachView($module,$key=CONTENT){
        if(!$key) $key = 0;
        if(!isset($this->modules[$key])) $this->modules[$key] = array();
        if(is_array($module)){
            $this->modules[$key] = array_merge($this->modules[$key],$module);
        }
        else{
            $this->modules[$key][] = $module;
        }
    }
    /**
    * Instantiates and eturns a view object
    * @param $viewname: view name you want to use, colon delimited for 'model views'
    * @param $params: scalar or array representation of data to be bound to view
    * @param $options: array of options to load view with, each becomes instance variable of view
    * @param $iterate: boolean saying whether array of params should be iterated through, each being data for a view, or not
    */     
    public function getView($viewname,$params = null,$options = false, $iterate = false){
        if(is_array($params) && $iterate){
            $vs = array();
            foreach($params as $param){
                $vs[] = $this->retrieveView($viewname,$param,$options);
            }
            return $vs;
        }else return $this->retrieveView($viewname,$params,$options);
    }
    /**
    * Instantiates and attaches view to list of views that will be displayed when page is built after all processes have finished
    * @param $viewname: view name you want to use, colon delimited for 'model views'
    * @param $params: scalar or array representation of data to be bound to view
    * @param $key: instructions for the UIHelper dictating which part of the page layout to display view in
    * @param $options: array of options to load view with, each becomes instance variable of view
    * @param $iterate: boolean saying whether array of params should be iterated through, each being data for a view, or not
    */    
    public function addView($viewname,$params = null,$key = CONTENT, $options = false, $iterate = false){        
        if(is_array($params) && $iterate){
            $vs = array();
            foreach($params as $param){
                $vs[] = $this->attachView($this->retrieveView($viewname, $param,$options),$key);                
            }
            return $vs;
        }else return $this->attachView($this->retrieveView($viewname, $params,$options),$key);
    }

    /**
    * Immedieately creates and displays a view
    * @param $viewname: view name you want to use, colon delimited for 'model views'
    * @param $params: scalar or array representation of data to be bound to view
    * @param $options: array of options to load view with, each becomes instance variable of view
    * @param $iterate: boolean saying whether array of params should be iterated through, each being data for a view, or not
    * @param $exit: boolean deciding if script should exit immediately after showing view(s) [good for AJAX calls that return markup]
    */
    public function showView($viewname,$params = null, $options = false, $iterate = false, $exit = false, $return = false){
        if($return){
            ob_start();
        }          
        if(is_array($params) && $iterate){
            foreach($params as $param){
                $v = $this->retrieveView($viewname, $param,$options);
                $v->show();                
            }
        }else{
            $v = $this->retrieveView($viewname, $params,$options);
            $v->show();
        }
        if($exit)exit;

        if($return){
            return ob_get_clean();
        }  
        return '';
    }    

    public function clearModulesForContainer($container = false){
        if($container){
            unset($this->modules[$container]);
        }
    }
    public function clearModules(){
        $this->modules = array();
    }

    public function redirect($url){
        $this->url = $url;
    }

    public function addScript($script){
        if(!in_array($script,$this->scripts)){
            $this->scripts[] = $script;
        }
    }
    
    public function addStyle($style,$prepend = false){
        if(!in_array($style,$this->styles)){
            if($prepend){
                array_unshift($this->styles, $style);
            }else{
                $this->styles[] = $style;
            }
        }
    }
    public function addJS($js){
        if(!in_array($js,$this->js)){
            if($prepend){
                array_unshift($this->js, $js);
            }else{
                $this->js[] = $js;
            }
        }
    }
    public function getCSS(){
        $css = array();
        if(isset($this->styles) && is_array($this->styles)){
            $path = dirname(__FILE__)."/../../";
            foreach($this->styles as $style){                
                $css[] = (string) @file_get_contents($path.$style);
            }
        }
        $css = implode("\n",$css);
        return $css;
    }
    
    function incrementModule($type){
        if(isset($this->genModules[$type])){
            $this->genModules[$type]++;
        }
        else{
            $this->genModules[$type] = 1;
        }
        return $this->genModules[$type];
    }

    public function setPage($page){
        if(in_array($page,$this->pages)){
            $this->page = $page;
            $this->template = PAGE_PATH."/{$page}.php";
        }
        return false;
    }

    public function json($data, $echo = false, $exit = false,$mimetype = 'application/json'){
        if($echo && $exit){
            header('Content-Type: '.$mimetype);
        }
        if(!$data){
            $data = array();//array("error"=>"Invalid response. Please try again.");
        }
        $encoded_data =  htmlspecialchars(json_encode($data), ENT_NOQUOTES);
        if($echo){
            echo $encoded_data;
        }
        if($exit){
            @session_write_close();
            exit;
        }
        return $encoded_data;
    }

    public function markdown($data,$encode = true){
        $text = $encode?$this->encode($data):$data;
        $matches = array();
        $tagged_users = array();
        preg_match_all('/(^|\W)@(\w+)/', $text, $matches);        
        if($matches){
            $u = ModelFactory::getModel('user');            
            foreach($matches[2] as $match){
                if($u->userWithUserName($match)){
                    $link = "[@{$match}](".SITE."/u/{$match})";
                    $name = '@'.$match;
                    $text = str_replace($name, $link, $text);
                }
            }     
        }
        $text = str_replace("_", ":under:", $text);
        $out = Michelf\Markdown::defaultTransform($text);
        echo str_replace(":under:", "_", $out);
    }
    
    public function display($data,$encode = true){
        echo $encode?$this->encode($data):$data;
    }    

    public function encode($data){
        return htmlentities($data,ENT_QUOTES,'UTF-8');
    }

    public function title($title){
        $this->custom_title = $title;
    }
    
}
