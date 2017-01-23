<?php


use bff\utils\Files;

class config {

	static public $data = array();

	static private $cacheKey = "data";


	static private function db() {
		return bff::database();
	}
	
	static private function cache() {
		static $cache;
		if (empty($cache)) {
			$cache = Cache::singleton("config", "file");
		}
		return $cache;
	}

	static public function load() {
		$var9nj44gZLiFFN = static::cache();
		$var3svR7O34lr0N = static::db();
		do {
			if (($var26lm2I6QlwIm = $var9nj44gZLiFFN->get(static::$cacheKey)) !== false) {
				if ($var3svR7O34lr0N->isConnected()) {
					$var1KqXZramQRAA = $var3svR7O34lr0N->select("SELECT config_name as n, config_value as v FROM " . TABLE_CONFIG . " WHERE is_dynamic = 1 ");
					foreach ($var1KqXZramQRAA as $var58qJdGahLfOx) {
						$var26lm2I6QlwIm[$var58qJdGahLfOx["n"]] = $var58qJdGahLfOx["v"];
					}
				}
				break;
			}
			if ($var3svR7O34lr0N->isConnected()) {
				$var26lm2I6QlwIm = $var37oiRXkkFiHL = array();
				$var1KqXZramQRAA = $var3svR7O34lr0N->select("SELECT config_name as n, config_value as v, is_dynamic as d FROM " . TABLE_CONFIG);
				foreach ($var1KqXZramQRAA as $var58qJdGahLfOx) {
					if (!$var58qJdGahLfOx["d"]) {
						$var37oiRXkkFiHL[$var58qJdGahLfOx["n"]] = $var58qJdGahLfOx["v"];
					}
					$var26lm2I6QlwIm[$var58qJdGahLfOx["n"]] = $var58qJdGahLfOx["v"];
				}
				$var9nj44gZLiFFN->set(static::$cacheKey, $var37oiRXkkFiHL);
				break;
			}
			$var26lm2I6QlwIm = static::file("site");
		}
		while (false);
		return static::$data = $var26lm2I6QlwIm;
	}


	static public function save($va061ea4d9e7e82e57, $G90738ee4a37 = false, $b48fd5e1eecbdb2 = false) {
		$var4P0xEGRqE4Nx = static::db();
		if ($var4P0xEGRqE4Nx->isConnected()) {
			if (is_array($va061ea4d9e7e82e57)) {
				static::saveMany($va061ea4d9e7e82e57, $b48fd5e1eecbdb2);
				return null;
			}
			$var3w1JBY086HJA = $var4P0xEGRqE4Nx->exec("UPDATE " . TABLE_CONFIG . " SET config_value = :val WHERE config_name = :name", array(":val" => $G90738ee4a37, ":name" => $va061ea4d9e7e82e57));
			if (!$var3w1JBY086HJA && !(isset(static::$data[$va061ea4d9e7e82e57]))) {
				$var4P0xEGRqE4Nx->insert(TABLE_CONFIG, array("config_name" => $va061ea4d9e7e82e57, "config_value" => $G90738ee4a37, " is_dynamic " => ($b48fd5e1eecbdb2 ? 1 : 0)), false);
			}
			if (!$b48fd5e1eecbdb2) {
				static::cache()->delete(static::$cacheKey);
			}
			static::$data[$va061ea4d9e7e82e57] = $G90738ee4a37;
		}
		else {
			if (is_array($va061ea4d9e7e82e57)) {
				foreach ($va061ea4d9e7e82e57 as $var3p6jbIceI8gW => $var4WpOnSHv14Z5) {
					static::$data[$var3p6jbIceI8gW] = $var4WpOnSHv14Z5;
				}
			}
			else {
				static::$data[$va061ea4d9e7e82e57] = $G90738ee4a37;
			}
		}
		static::saveToFile(static::$data, false);
	}


	static public function saveMany($c2cad3ff60fc1eddc0, $I4ef8cbfd4 = false) {
		static::saveToFile($c2cad3ff60fc1eddc0, true);
		$var33IdlGBV0QZW = static::db();
		if ($var33IdlGBV0QZW->isConnected()) {
			$c2cad3ff60fc1eddc0 = array_chunk($c2cad3ff60fc1eddc0, 15, true);
			$var3blshYM3sxCL = $var33IdlGBV0QZW->select_one_column(" SELECT config_name FROM " . TABLE_CONFIG . ( ! $I4ef8cbfd4 ? " WHERE is_dynamic = 0 " : ""));
			$var7BKjAVYGaLvy = array();
			foreach ($c2cad3ff60fc1eddc0 as $var5WRlQBBjQr3J) {
				if (empty($var5WRlQBBjQr3J)) {
					continue;
				}
				$varfn7DQ6JojQZ = array();
				foreach ($var5WRlQBBjQr3J as $var4LTX0Y9xxizL => $var69rVlywcNbv9) {
					if (in_array($var4LTX0Y9xxizL, $var3blshYM3sxCL)) {
						$varfn7DQ6JojQZ[] = " WHEN " . $var33IdlGBV0QZW->str2sql($var4LTX0Y9xxizL) . " THEN " . $var33IdlGBV0QZW->str2sql($var69rVlywcNbv9);
						continue;
					}
					$var7BKjAVYGaLvy[] = "(" . $var33IdlGBV0QZW->str2sql($var4LTX0Y9xxizL) . "," . $var33IdlGBV0QZW->str2sql($var69rVlywcNbv9) . ",0)";
				}
				if (empty($varfn7DQ6JojQZ)) {
					continue;
				}
				$var33IdlGBV0QZW->exec(" UPDATE " . TABLE_CONFIG . " SET config_value = CASE config_name " . join(" ", $varfn7DQ6JojQZ) . " ELSE config_value END WHERE is_dynamic = 0");
			}
			if (!(empty($var7BKjAVYGaLvy))) {
				$var33IdlGBV0QZW->exec(" INSERT INTO " . TABLE_CONFIG . " (config_name, config_value, is_dynamic) VALUES " . join(",", $var7BKjAVYGaLvy));
			}
			static::cache()->delete(static::$cacheKey);
		}
	}


	static private function saveToFile($hb82026, $P9634e9d = true) {
		if ($P9634e9d) {
			$hb82026 = array_merge(static::$data, $hb82026);
		}
		return Files::putFileContent(static::file("site", true), "<?php " . PHP_EOL . PHP_EOL . " return " . var_export($hb82026, true) . ";" . PHP_EOL . PHP_EOL);
	}


	static public function saveCount($d6f9cb89, $zac0ab6d2a7207cbc, $cae9394cc = false) {
		$var9wAKqbY4CEhk = static::db();
		switch ($var9wAKqbY4CEhk->getDriverName()) {
			case "pgsql":
				$var9oNvc9RcBNyg = " config_value = int4(config_value) + " . (int)$zac0ab6d2a7207cbc;
				break;
			
			default:
				$var9oNvc9RcBNyg = " config_value = config_value + " . (int)$zac0ab6d2a7207cbc;
				break;
			
		}
		$var1oJ8dSoit7gL = $var9wAKqbY4CEhk->update(TABLE_CONFIG, array($var9oNvc9RcBNyg), array("config_name" => $d6f9cb89));
		if (!$var1oJ8dSoit7gL) {
			$var9wAKqbY4CEhk->insert(TABLE_CONFIG, array("config_name" => $d6f9cb89, "config_value" => 0 < $zac0ab6d2a7207cbc ? $zac0ab6d2a7207cbc : 0, "is_dynamic" => $cae9394cc ? 1 : 0), false);
		}
		if (!$cae9394cc) {
			static::cache()->delete(static::$cacheKey);
		}
	}


	static public function resetCounters() {
		$var3IA1Z5pABhEm = static::db();
		$var3IA1Z5pABhEm->update(TABLE_CONFIG, array("config_value" => 0), array("is_dynamic" => 1));
		$var2VWAhpTzLv3G = $var3IA1Z5pABhEm->select_one_column("SELECT config_name FROM " . \TABLE_CONFIG . " WHERE is_dynamic = 1");
		$var7QmOPqp9Fefw = array();
		foreach ($var2VWAhpTzLv3G as $var2ZvhFdK4F4Kx) {
			$var7QmOPqp9Fefw[$var2ZvhFdK4F4Kx] = 0;
		}
		static::saveToFile($var7QmOPqp9Fefw, true);
		static::cache()->delete(static::$cacheKey);
	}


	static public function set($ccada674fab30, $ec0920589746 = false, $X341e5ddefd814 = false) {
		$var6j9D3V6dqy0O = $X341e5ddefd814 !== false ? $X341e5ddefd814 : "";
		if (is_scalar($ccada674fab30)) {
			static::$data[$var6j9D3V6dqy0O . $ccada674fab30] = $ec0920589746;
			return null;
		}
		if (is_array($ccada674fab30)) {
			foreach ($ccada674fab30 as $var5PaISK6VEoTs => $var2H0wIArbhmVi) {
				static::$data[$var6j9D3V6dqy0O . $var5PaISK6VEoTs] = $var2H0wIArbhmVi;
			}
		}
	}


	static public function get($Y97001321, $q6b2976 = false, $l1d2196e5548812300a = false) {
		$var5fkP6absQ53M = $l1d2196e5548812300a !== false ? $l1d2196e5548812300a : "";
		if (is_array($Y97001321)) {
			$var8JMyxpC41T2J = array();
			foreach ($Y97001321 as $var3TXaBHVv6f4n) {
				if (!(isset(static::$data[$var5fkP6absQ53M . $var3TXaBHVv6f4n]))) {
					continue;
				}
				$var8JMyxpC41T2J[$var3TXaBHVv6f4n] = static::$data[$var5fkP6absQ53M . $var3TXaBHVv6f4n];
			}
			return $var8JMyxpC41T2J;
		}
		return isset(static::$data[$var5fkP6absQ53M . $Y97001321]) ? static::$data[$var5fkP6absQ53M . $Y97001321] : $q6b2976;
	}


	static public function getWithPrefix($E8b360012367) {
		$var74FyatEURZiP = array();
		$var8dLCZgWJLEV7 = strlen($E8b360012367);
		foreach (static::$data as $var8vSamXTdnzIN => $var2XeKuVRY2eyM) {
			if (!(strpos($var8vSamXTdnzIN, $E8b360012367) === 0)) {
				continue;
			}
			$var74FyatEURZiP[substr($var8vSamXTdnzIN, $var8dLCZgWJLEV7)] = $var2XeKuVRY2eyM;
		}
		return $var74FyatEURZiP;
	}


	static public function sys($e5c94c1e99fc, $c42afe113a57a1e9537 = "", $w370f257e8 = "", $t65d168560fc6fa0 = false) {
		static $bfb4e6b841111;
		if (isset($bfb4e6b841111)) {
			if (!(empty($w370f257e8))) {
				$w370f257e8 = $w370f257e8 . ".";
			}
			if (is_string($e5c94c1e99fc)) {
				return isset($bfb4e6b841111[$w370f257e8 . $e5c94c1e99fc]) ? $bfb4e6b841111[$w370f257e8 . $e5c94c1e99fc] : $c42afe113a57a1e9537;
			}
			if (is_array($e5c94c1e99fc)) {
				if (!(empty($e5c94c1e99fc))) {
					$var6GmGsxzAzfi8 = array();
					foreach ($e5c94c1e99fc as $var79FrSpJ3BrhQ) {
						if (!(isset($bfb4e6b841111[$w370f257e8 . $var79FrSpJ3BrhQ]))) {
							continue;
						}
						$var6GmGsxzAzfi8[$t65d168560fc6fa0 ? "" : $w370f257e8 . $var79FrSpJ3BrhQ] = $bfb4e6b841111[$w370f257e8 . $var79FrSpJ3BrhQ];
					}
				}
				else {
					if (empty($w370f257e8)) {
						return $bfb4e6b841111;
					}
					$var6GmGsxzAzfi8 = array();
					$var2gD1Z1q2JXSZ = strlen($w370f257e8);
					foreach ($bfb4e6b841111 as $var79FrSpJ3BrhQ => $var4MDVbJ2LEFDV) {
						if (!(strpos($var79FrSpJ3BrhQ, $w370f257e8) === 0)) {
							continue;
						}
						$var6GmGsxzAzfi8[$t65d168560fc6fa0 ? substr($var79FrSpJ3BrhQ, $var2gD1Z1q2JXSZ) : $var79FrSpJ3BrhQ] = $var4MDVbJ2LEFDV;
					}
				}
				return $var6GmGsxzAzfi8;
			}
		}
		$bfb4e6b841111 = static::file("sys");
		if ($e5c94c1e99fc !== false) {
			return static::sys($e5c94c1e99fc, $c42afe113a57a1e9537, $w370f257e8, $t65d168560fc6fa0);
		}
	}


	static public function instruction($a6383865bbad3918a = false) {
		static $e0692d9a3a9314;
		if (isset($e0692d9a3a9314)) {
			if (is_string($a6383865bbad3918a)) {
				return isset($e0692d9a3a9314[$a6383865bbad3918a]) ? $e0692d9a3a9314[$a6383865bbad3918a] : "";
			}
			if (is_array($a6383865bbad3918a) && !(empty($a6383865bbad3918a))) {
				$var9Ehepj7unf2O = array();
				foreach ($a6383865bbad3918a as $var8La1dAyzMDyj) {
					$var9Ehepj7unf2O[$var8La1dAyzMDyj] = isset($e0692d9a3a9314[$var8La1dAyzMDyj]) ? $e0692d9a3a9314[$var8La1dAyzMDyj] : "";
				}
				return $var9Ehepj7unf2O;
			}
			return $e0692d9a3a9314;
		}
		$e0692d9a3a9314 = static::file("instructions");
		return static::instruction($a6383865bbad3918a);
	}


	static public function instructionSave($data, $zb105cf1504c4e241 = true) {
		if ($zb105cf1504c4e241) {
			$data = array_merge(static::instruction(false), $data);
		}
		return Files::putFileContent(static::file("instructions", true), "<?php " . PHP_EOL . PHP_EOL . " return " . var_export($data, true) . ";" . PHP_EOL . PHP_EOL);
	}


	static public function file($Ia02ce, $bd1ee74f = false) {
		$var2DnVqxWsAKyK = PATH_BASE . "config" . DIRECTORY_SEPARATOR . $Ia02ce . ".php";
		return $bd1ee74f ? $var2DnVqxWsAKyK : require($var2DnVqxWsAKyK);
	}


	static public function update($W452c3e4d31551 = "") {
		return true;
	}


	
}

?>