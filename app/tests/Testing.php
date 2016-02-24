<?php
	abstract class Testing extends lib\testing\Tester {

		protected function assertTrue($val) {
			echo "Asserting value is true: ".($val === true ? "Pass" : "Fail")."\n";
		}

		protected function assertFalse($val) {
			echo "Asserting value is false: ".($val === false ? "Pass" : "Fail")."\n";	
		}

		protected function assertSessionHas($key) {
			echo "Asserting session has key '".$key."': ".(Session::has($key) ? "Pass" : "Fail")."\n";
		}

		protected function assertInArray($key,$array) {
			echo "Asserting array has: ".(in_array($key,$array) ? "Pass" : "Fail")."\n";
		}

		protected function assertEquals($var,$val) {
			echo "Asserting equals: ".($var === $val ? "Pass" : "Fail")."\n";
		}
	}