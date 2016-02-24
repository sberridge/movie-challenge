<?php
	namespace lib\schema;
	class Column extends \Sql {
		public $name;
		public $type;
		public $nullable = false;
		public $increments = false;
		public $primary = false;
		public $index = false;
		public $length;
		public $table;

		public function __construct($name,$type=null,$length=null) {
			$this->name = $name;
			$this->type = $type;
			$this->length = $length;
		}

		public function nullable() {
			$this->nullable = true;
			return $this;
		}

		public function increments() {
			$this->increments = true;
			return $this;
		}

		public function primary() {
			$this->primary = true;
			return $this;
		}

		public function index() {
			$this->index = true;
			return $this;
		}

		public function fullText() {
			$this->index = 'FULLTEXT';
		}

		public function drop() {
			$sql = "ALTER TABLE ".self::quote($this->table)." DROP COLUMN ".self::quote($this->name);
			self::raw($sql)->run();
		}
	}