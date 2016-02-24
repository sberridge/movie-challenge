<?php
	/**
	 * class containing some useful string functions
	 * code for the plural and singular functions taken from the Akelos Inflector class
	 * found here: http://phpsnips.com/567/Pluralize-and-Singularize-English-Nouns#.U774BvldWSo
	 */
	class Str {
		public static function snake_case($str) {
			$str = lcfirst(str_replace(" ", "_", $str));
			$str = str_replace("-", "_", $str);
			while(preg_match("/[A-Z]/",$str)) {
				if(!preg_match("/[a-z0-9][A-Z]/",$str)) break;
				$str = preg_replace_callback("/([a-z0-9])([A-Z])/", function($matches) {
					return ($matches[1] === "_" ? "" : $matches[1])."_".strtolower($matches[2]);
				}, $str);	
			}
			$str = strtolower($str);
			return $str;
		}
		public static function camel_case($str) {
			$str = lcfirst(preg_replace_callback("/([ _-])(.)/", function($matches) {
				return strtoupper($matches[2]);
			}, $str));
			return $str;
		}
		public static function studly_case($str) {
			$str = ucwords(self::camel_case($str));
			return $str;
		}
		public static function separate($str) {
			$str = str_replace("_"," ",$str);
			$str = str_replace("-"," ",$str);
			$str = lcfirst($str);
			while(preg_match("/[a-z][A-Z]/",$str)) {
				$str = preg_replace_callback("/([a-z0-9])([A-Z])/", function($matches) {
					return $matches[1]." ".strtolower($matches[2]);
				}, $str);
			}
			return $str;
		}
		public static function slugify($str) {
			$str = str_replace('/', '', $str);
			$str = str_replace('\\', '', $str);
			return str_replace('%','',strtolower(urlencode(str_replace(' ','-',$str))));
		}
		public static function random($length=10) {
			$characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890/.";
	        $string = "";
	        for($i = 0; $i < $length; $i++) {
	            $string .= $characters[mt_rand(0,strlen($characters)-1)];
	        }
	        return $string;
		}
		public static function plural($word){ 
	        $plural = array( 
	            '/(quiz)$/i'               => '\1zes', 
	            '/^(ox)$/i'                => '\1en', 
	            '/([m|l])ouse$/i'          => '\1ice', 
	            '/(matr|vert|ind)ix|ex$/i' => '\1ices', 
	            '/(x|ch|ss|sh)$/i'         => '\1es', 
	            '/([^aeiouy]|qu)ies$/i'    => '\1y', 
	            '/([^aeiouy]|qu)y$/i'      => '\1ies', 
	            '/(hive)$/i'               => '\1s', 
	            '/(?:([^f])fe|([lr])f)$/i' => '\1\2ves', 
	            '/sis$/i'                  => 'ses', 
	            '/([ti])um$/i'             => '\1a', 
	            '/(buffal|tomat)o$/i'      => '\1oes', 
	            '/(bu)s$/i'                => '\1ses', 
	            '/(alias|status)/i'        => '\1es', 
	            '/(octop|vir)us$/i'        => '\1i', 
	            '/(ax|test)is$/i'          => '\1es', 
	            '/s$/i'                    => 's', 
	            '/$/'                      => 's'); 

	        $uncountable = array('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep'); 

	        $irregular = array( 
	            'person' => 'people', 
	            'man'    => 'men', 
	            'child'  => 'children', 
	            'sex'    => 'sexes', 
	            'move'   => 'moves'); 

	        $lowercased_word = strtolower($word); 

	        foreach($uncountable as $_uncountable){ 
	            if(substr($lowercased_word, (-1 * strlen($_uncountable))) == $_uncountable){ 
	                return $word; 
	            } 
	        } 

	        foreach($irregular as $_plural => $_singular){ 
	            if(preg_match('/(' . $_plural . ')$/i', $word, $arr)){ 
	                return preg_replace('/(' . $_plural . ')$/i', substr($arr[0], 0, 1) . substr($_singular, 1), $word); 
	            } 
	        } 

	        foreach($plural as $rule => $replacement){ 
	            if(preg_match($rule, $word)){ 
	                return preg_replace($rule, $replacement, $word); 
	            } 
	        } 
	        return false; 
	    }

	    public static function singular($word) {
	    	$singular = array( 
	            '/(quiz)zes$/i'                                                    => '\1', 
	            '/(matr)ices$/i'                                                   => '\1ix', 
	            '/(vert|ind)ices$/i'                                               => '\1ex', 
	            '/^(ox)en/i'                                                       => '\1', 
	            '/(alias|status)es$/i'                                             => '\1', 
	            '/([octop|vir])i$/i'                                               => '\1us', 
	            '/(cris|ax|test)es$/i'                                             => '\1is', 
	            '/(shoe)s$/i'                                                      => '\1', 
	            '/(o)es$/i'                                                        => '\1', 
	            '/(bus)es$/i'                                                      => '\1', 
	            '/([m|l])ice$/i'                                                   => '\1ouse', 
	            '/(x|ch|ss|sh)es$/i'                                               => '\1', 
	            '/(m)ovies$/i'                                                     => '\1ovie', 
	            '/(s)eries$/i'                                                     => '\1eries', 
	            '/([^aeiouy]|qu)ies$/i'                                            => '\1y', 
	            '/([lr])ves$/i'                                                    => '\1f', 
	            '/(tive)s$/i'                                                      => '\1', 
	            '/(hive)s$/i'                                                      => '\1', 
	            '/([^f])ves$/i'                                                    => '\1fe', 
	            '/(^analy)ses$/i'                                                  => '\1sis', 
	            '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis', 
	            '/([ti])a$/i'                                                      => '\1um', 
	            '/(n)ews$/i'                                                       => '\1ews', 
	            '/s$/i'                                                            => '', 
	        ); 

	        $uncountable = array('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep'); 

	        $irregular = array( 
	            'person' => 'people', 
	            'man'    => 'men', 
	            'child'  => 'children', 
	            'sex'    => 'sexes', 
	            'move'   => 'moves'); 

	        $lowercased_word = strtolower($word); 

	        foreach($uncountable as $_uncountable){ 
	            if(substr($lowercased_word, (-1 * strlen($_uncountable))) == $_uncountable){ 
	                return $word; 
	            } 
	        } 

	        foreach($irregular as $_plural => $_singular){ 
	            if(preg_match('/(' . $_singular . ')$/i', $word, $arr)){ 
	                return preg_replace('/(' . $_singular . ')$/i', substr($arr[0], 0, 1) . substr($_plural, 1), $word); 
	            } 
	        } 

	        foreach($singular as $rule => $replacement){ 
	            if(preg_match($rule, $word)){ 
	                return preg_replace($rule, $replacement, $word); 
	            } 
	        } 

	        return $word; 
	    }
	}