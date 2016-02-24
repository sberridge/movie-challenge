<?php
	class Paginator {

		private static $registered = array();

		private $perPage;
		public $totalPages;
		private $page;
		private $template;
		private $pageName;

		public static function register($pageName,$totalPages,$page) {
			self::$registered[$pageName] = array(
				"total"=>$totalPages,
				"page"=>$page
			);
		}

		public function __construct($pageName,$perPage,$totalPages,$page) {
			$this->perPage = $perPage;
			$this->pageName = $pageName;
			if(isset(self::$registered[$pageName])) {
				$this->totalPages = self::$registered[$pageName]['total'];
				$this->page = self::$registered[$pageName]['page'];
			} else {
				$this->totalPages = $totalPages;
				$this->page = $page;	
			}			
		}

		public static function make($pageName,$perPage=null,$totalPages=null,$page=1) {
			$paginator = new Paginator($pageName,$perPage,$totalPages,$page);
			return $paginator;
		}

		public function setTemplate($template) {
			$this->template = $template;
			return $this;
		}

		public function renderLinks($activeClass="active") {
			$links = array();
			$url = Url::current();
			$queryString = $_SERVER['QUERY_STRING'];
			if(strlen($queryString) > 0) {
				$queryString = '?'.preg_replace('/\&?'.$this->pageName.'\=[0-9]+/', '', $queryString);
			} else {
				$queryString = '?';
			}
			for($i = 1; $i <= $this->totalPages; $i++) {
				$links[$i] = $url.$queryString.(strlen($queryString) > 1 ? '&' : '').$this->pageName.'='.$i;
			}
			if(count($links) > 1) {
				if($this->template) {
					$temp = View::make($this->template,array('links'=>$links,'active'=>$this->page,'class'=>$activeClass,'name'=>$this->pageName));
				} else {
					$temp = View::make('pagination/default',array('links'=>$links,'active'=>$this->page,'class'=>$activeClass,'name'=>$this->pageName));
				}
				
				$temp->render();
			}
			
		}
	}