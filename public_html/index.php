<?php
if (!empty($_GET['bff'])){
    $sBFF = $_GET['bff'];
    //echo $sBFF;
} elseif(!empty($_POST['bff'])){
    $sBFF = $_POST['bff'];
    echo $sBFF;
} else $sBFF = "";
$sTemplateDir = __DIR__ . "/../bff.php";
if (empty($sBFF)) {
	require_once ($sTemplateDir);
    require("index.responsive.php");

}
else {
	switch ($sBFF) {
	case "ajax":
		require_once ($sTemplateDir);

		try {
			if (!empty(bff::$class)) {
				$oModule = bff::module(bff::$class);
				$sMethod = (!empty(bff::$event) ? bff::$event : "ajax");

				if (method_exists($oModule, $sMethod)) {
					$oModule->{$sMethod}();
				}
			}
		}
		catch (Exception $e) {
			Errors::i()->set($e->getMessage() . ", " . $e->getFile() . " [" . $e->getCode() . "]", true);
		}

		echo Errors::IMPOSSIBLE;
		break;

	case "device":
		define("BFF_SESSION_START", 0);
		require_once ($sTemplateDir);
		bff::device(isset($_GET["type"]) ? $_GET["type"] : 0, true);
		header("HTTP/1.1 204 No Content");
		break;

	case "errors":
		define("BFF_SESSION_START", 0);
		require_once ($sTemplateDir);

		if (!isset($errno)) {
			$errno = (!empty($_GET["errno"]) ? (int) $_GET["errno"] : 0);
		}

		Errors::i()->errorHttp($errno);
		break;

	case "errors-js":
        define("BFF_SESSION_START", 0);
		require_once ($sTemplateDir);
		$aParams = bff::input()->postm(array("e" => TYPE_STR, "f" => TYPE_STR, "l" => TYPE_STR));
		bff::log(join("; ", array_values($aParams)), "js.log");
		echo 1;
		break;

	case "cron":
		$q93e3919 = (!empty($_GET["s"]) ? $_GET["s"] : false);
		$R082e34ab = (empty($q93e3919) && !empty($_GET["f"]) ? $_GET["f"] : false);
		if (empty($q93e3919) && empty($R082e34ab)) {
			echo "wrong crontab action";
			break;
		}

		define("BFF_SESSION_START", 0);
		define("BFF_CRON", 1);
		ignore_user_abort(true);
		require_once ($sTemplateDir);
        $sMethod = (!empty(bff::$event) ? bff::$event : "cron");
		if (!empty(bff::$class)) {
			$oModule = bff::module(bff::$class);

			if (method_exists($oModule, $sMethod)) {
				$oModule->{$sMethod}();
			}
		}
		else if (!empty($R082e34ab)) {
			require_once (PATH_BASE . "cron" . DS . $R082e34ab . ".php");
		}

		$ba5448800 = Errors::i();

		if (!$ba5448800->no()) {
			$sdce7b1 = print_r($ba5448800->get(true, false), true);

			if (!empty(bff::$class)) {
				bff::log(bff::$class . "::" . $sMethod . ": " . $sdce7b1);
			}
			else if (!empty($R082e34ab)) {
				bff::log($R082e34ab . ": " . $sdce7b1);
			}

			echo "<pre>";
			echo $sdce7b1;
			echo "</pre>";
		}

		break;
	}
}

bff::shutdown();


