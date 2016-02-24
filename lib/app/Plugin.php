<?php
    namespace lib\app;
    
    class Plugin {
        protected static $_registeredPlugins = Array();        

        /**
         * Clears all the plugins and loads them from the config
         */
        public static function registerAll() {
            self::$_registeredPlugins = Array();
            
            $plugins = \Config::get('plugins');
            foreach($plugins as $plugin) {
                Plugin::register($plugin);
            }

            $addons = \Config::get('addons');
            foreach($addons as $addon) {
                $path = realpath(ROOT.'/app/addons/'.$addon.'/plugins');
                if($path !== false) {
                    $ns = 'app\addons\\'.$addon.'\plugins\\';
                    $dir = opendir($path);
                    while($f = readdir($dir)) {
                        if(in_array($f,array('.','..'))) {
                            continue;
                        }
                        include($path.'/'.$f);
                        $class = str_replace('.php','',$f);
                        $class = $ns.$class;
                        Plugin::register($class);
                    }
                }
                
            }
            
        }
        
        public static function register($plugin) {
            if(is_string($plugin)) {

                if(class_exists($plugin)) {
                    if(is_subclass_of($plugin, 'Plugin'));
                    $plugin = new $plugin();
                }
            }
            if($plugin instanceof Plugin) {
                self::$_registeredPlugins[] = $plugin;
            }
        }
        
        public static function unregister($name) {
            if(array_key_exists($name, self::$_registeredPlugins)) {
                unset(self::$_registeredPlugins[$name]);
            }
        }
        
        /**
         * Executes all the methods in the added plugins
         * @param type $method
         * @param type $params
         */
        public static function execute($method,$params) {
            $params = (array)$params;
            
            foreach(self::$_registeredPlugins as $name=>$plugin) {
                if(method_exists($plugin, $method)) {
                    call_user_func_array(Array($plugin,$method), $params);
                }
            }
        }
                
        
        protected $_name;

        public function getName() {
            return $this->_name;
        }
    }