<?php

	//that scripts is for usual host
	ini_set('memory_limit', "128M");
    ini_set('max_execution_time', 60);
	ini_set('session.gc_maxlifetime', 604800);	//3600*24*7
	//stops script if usert aborts
    ignore_user_abort(false);
    date_default_timezone_set('Europe/Kiev');
    header('Content-Type: text/html; charset=UTF-8');
    mb_internal_encoding("UTF-8");
	//dispaly errors in debug mode
	if (isset($_GET["debug"])) {
		ini_set("display_errors", "on");
		error_reporting(E_ALL);		
		define("DEBUG_MODE", "ON");
	} else {
		ini_set("display_errors", "off");
		error_reporting(0);		
		define("DEBUG_MODE", "OFF");
	}
	//fill this
	define("DB_HOST", "localhost");
	define("DB_USER", "root");
	define("DB_PASS", "");
	define("DB_NAME", "freelancehunt");
	
	define("WEB", "http://localhost/freelancehunt/");
	
	
	$mysqli	= new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$mysqli->query("SET NAMES utf8");
	$mysqli->query("SET CHARACTER SET utf8");
	$mysqli->set_charset('utf8');	
	
	if (isset($_GET["destroy"])) {
		$q = "TRUNCATE `freelancehunt`.`users`";
		if (!$mysqli->query($q)) _logMysqlError(1, $q); else {if (DEBUG_MODE == "ON") echo "Truncated<br />-----<br /><br />";}
		$q = "DROP TABLE `freelancehunt`.`users`";
		if (!$mysqli->query($q)) _logMysqlError(2, $q); else {if (DEBUG_MODE == "ON") echo "Deleted<br />-----<br /><br />";}
		$mysqli->close();
		exit();
	}
	
	if (isset($_GET["install"])) {
		//creating a table in database
		$qs = [
			"CREATE TABLE `users` (
			  `id` int(10) UNSIGNED NOT NULL,
			  `name` char(255) NOT NULL,
			  `checkin_date` date NOT NULL,
			  `ip` char(255) NOT NULL,
			  `rating` int(11) NOT NULL,
			  `home_country` char(255) NOT NULL,
			  `is_active` int(11) NOT NULL,
			  `checkin_country` char(255) NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;",			
			"ALTER TABLE `users` ADD PRIMARY KEY (`id`);",			
			"ALTER TABLE `users` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;",
			"ALTER TABLE `users` CHANGE `is_active` `is_active` INT(11) NULL;"
		];
		foreach ($qs as $q) if (!$mysqli->query($q)) {
			_logMysqlError(3, $q); 
			if (DEBUG_MODE == "ON") echo "Haven't made that query: <b>$q</b><br />-----<br /><br />";
		} else {
			if (DEBUG_MODE == "ON") echo "Query executed: <b>$q</b><br />-----<br />";
		}
		//filling table in with provided data
		unset($q);
		$file = dirname(__FILE__) . "/test-data.csv";
		$rows = explode(PHP_EOL, _read($file));
		$n = count($rows);
		if (DEBUG_MODE == "ON") echo "Rows total: <b>" . $n . "</b><br />-----<br />";
		if ($n > 0) {
			foreach ($rows as $row) {
				echo "$row<br />";
				$data = _prepareToInsert($row);
				if (is_array($data)) {
					if (!isset($q)) $q = " ('" . implode("', '", $data) . "')";
					else $q.= ", ('" . implode("', '", $data) . "')";
				}
			}
			if (isset($q)) {
				//, `checkin_country`
				$q = "InSeRt InTo `users` (`id`, `name`, `checkin_date`, `ip`, `rating`, `home_country`, `is_active`) VaLuEs " . $q;
				if (!$mysqli->query($q)) _logMysqlError(4, $q);
			}
		} else {
			if (DEBUG_MODE == "ON") echo "Please copy test data to <b>$file</b>!<br />-----<br />";
		}
		$mysqli->close();
		exit();
	}

	if (isset($_GET["country_check"])) {
		if (file_exists(__DIR__ . "/sypexgeonet/SxGeo.php")) {
			require_once(__DIR__ . "/sypexgeonet/SxGeo.php");
			$SxGeo = new SxGeo(__DIR__ . '/sypexgeonet/SxGeoCity.dat');
		} else {
			if (DEBUG_MODE == "ON") echo "Please install sypexgeonet!<br />-----<br />";
			$mysqli->close();
			exit();
		}
		$q = "SELECT `id`, `ip` FROM `users` WHERE `checkin_country`='' LIMIT 10;";
		if ($result = $mysqli->query($q)) {
			while ($row = $result->fetch_assoc()) {
				$id = $row['id'];
				$ip = $row['ip'];		
				$geo = _getCountry($ip);
				$country = $mysqli->real_escape_string($geo["country"]);
				$q = "UpDaTe `users` SeT `checkin_country`= '$country' WHERE `id` = " . $row['id'];
				if (!$mysqli->query($q)) _logMysqlError(5, $q); else {if (DEBUG_MODE == "ON") echo "Updated $id!<br />-----<br />";}
			}
			if ($result->num_rows > 0) echo '<meta http-equiv="refresh" content="0; url=' . WEB . '?country_check&debug">';
			else {if (DEBUG_MODE == "ON") echo "All countries are now defined!<br />-----<br />";}
		} else _logMysqlError(6, $q);
		
		$mysqli->close();
		exit();
	}
		
?>

<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>

    <!-- Bootstrap CSS
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous"/>
	 -->
	<link rel="stylesheet" href="<?=WEB;?>css/bootstrap9.css" />
	
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.9/css/all.css" />
	
    <title>freelancehunt.com</title>
	<link rel="apple-touch-icon" sizes="57x57" href="/apple-icon-57x57.png" />
	<link rel="apple-touch-icon" sizes="60x60" href="/apple-icon-60x60.png" />
	<link rel="apple-touch-icon" sizes="72x72" href="/apple-icon-72x72.png" />
	<link rel="apple-touch-icon" sizes="76x76" href="/apple-icon-76x76.png" />
	<link rel="apple-touch-icon" sizes="114x114" href="/apple-icon-114x114.png" />
	<link rel="apple-touch-icon" sizes="120x120" href="/apple-icon-120x120.png" />
	<link rel="apple-touch-icon" sizes="144x144" href="/apple-icon-144x144.png" />
	<link rel="apple-touch-icon" sizes="152x152" href="/apple-icon-152x152.png" />
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-icon-180x180.png" />
	<link rel="icon" type="image/png" sizes="192x192"  href="/android-icon-192x192.png" />
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png" />
	<link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png" />
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png" />
	<link rel="manifest" href="/manifest.json" />
	<meta name="msapplication-TileColor" content="#ffb31b" />
	<meta name="msapplication-TileImage" content="/ms-icon-144x144.png" /> 
	<meta name="theme-color" content="#ffb31b" />		
	<style>
table td,table th{padding-bottom:0!important;padding-top:0!important}._main{margin-top:80px!important;margin-bottom:60px!important}.not_active{background-color:rgba(0,0,0,.075)!important}.logo{width:28px}#average_rating,#bestrated{text-transform:uppercase;font-weight:700}.flag{height:20px;width:20px}.custom-control-label{font-size:11px}.green{color:green}
	</style>
  </head>
  <body>
  <?php 
		$contents = "";
		$navbar_bottom = "";
		$navbar_top = "";
		
		$table = _getTable();
		if ($table[1] == "no rows") $contents = '<div class="alert alert-warning" role="alert">Sorry, no rows available. Try to <a href="/?install" class="alert-link">Insert Test Data</a></div>';
		elseif ($table[1] == "error") $contents = '<div class="alert alert-danger" role="alert">Sorry, an error occured. Please check your DB config</div>';
		elseif ($table[1] == "success") {
			$contents = $table[0];
			$navbar_bottom = '<nav class="navbar fixed-bottom navbar-light bg-light">' . $table[2] . '</nav>';
		}
		$navbar_top = '<nav class="navbar fixed-top navbar-light bg-light">' . 
			'<div class="container">' . 
				'<a class="navbar-brand" href="freelancehunt.com"><img src="' . WEB . 'img/logo.png"  class="logo"/></a>' .
				'<div class="float-left" id="average_rating"></div>' .
				'<div class="float-left" id="bestrated"></div>' .
				'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="active" /><label class="custom-control-label" for="active">Select just active</label></div>' .
			'</div>
		</nav>';
		
		echo $navbar_top;
		echo $navbar_bottom;
	?>
	<div class="container _main">
		<div class="row">
			<div class="col">
				<?=$contents?>
			</div>
		</div>
	</div>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>
	
	<script>
	!function(l){function t(){for(var e=l("tr:visible").length,t=0,r=0,i={},a=0;a<e;a++){t+=1*l("tr:visible:eq("+a+")").find("td:eq(3)").text(),r+=1;var s=l("tr:visible:eq("+a+")").find("td:eq(0)").text(),n=1*l("tr:visible:eq("+a+")").find("td:eq(3)").text();0<n&&(i[s]?i[s]+=n:i[s]=n)}if(0!=r&&0!=e){var c=(t/r).toFixed(2);l("#average_rating").html('<i class="fas fa-calculator"></i> '+c);var o=Object.keys(i).map(function(e){return{user:e,rate:i[e]}});o.sort(function(e,t){return t.rate-e.rate}),console.log(o);var v=o[0].user;l("#bestrated").html('<i class="far fa-star"></i> '+v);for(e=l("tr:visible").length,a=0;a<e;a++){v==l("tr:visible:eq("+a+")").find("td:eq(0)").text()?l("tr:visible:eq("+a+")").addClass("green"):l("tr:visible:eq("+a+")").removeClass("green")}}else l("#average_rating").text(""),l("#bestrated").text(""),l("tr").removeClass("green")}l("#active").change(function(){l(this).is(":checked")?(l("tr.active").show(1e3),l("tr.not_active").hide(1e3)):(l("tr.active").show(1e3),l("tr.not_active").show(1e3)),t()}),l("._country").change(function(){var e=l(this).attr("data-country");l(this).is(":checked")?l("tr."+e).show(1e3):l("tr."+e).hide(1e3),t()})}(jQuery);
	</script>

  </body>
</html>
<?php	
	$mysqli->close();
	exit();
	
	function _prepareToInsert($row) {
		global $mysqli;
		
		$columns = explode(";", $row);
		$data = [];
		$data["id"] = "NULL";
		$data["name"] = !empty($columns[0]) ? $columns[0] : null;
		$data["checkin_date"] = !empty($columns[1]) ? $columns[1] : null;
		if (mb_strpos($data["checkin_date"], ".") !== false) {
			$data["checkin_date"] = explode(".", $data["checkin_date"]);
			$data["checkin_date"] = $data["checkin_date"][2] . "-" . $data["checkin_date"][1] . "-" . $data["checkin_date"][0] . "-";
		}
		$data["ip"] = !empty($columns[2]) ? $columns[2] : null;
		$data["rating"] = !empty($columns[3]) ? $columns[3] : null;
		$data["home_country"] = !empty($columns[4]) ? $columns[4] : null;
		$data["is_active"] = !empty($columns[5]) ? $columns[5] : null;
	
		$clean = [];
		foreach ($data as $n => $v) {
			_clear($v);
			if ($v != "") $clean[$n] = $mysqli->real_escape_string($v);
		}
		if (isset($clean["is_active"])) {
			if ($clean["is_active"] == "Нет") $clean["is_active"] = 0; else $clean["is_active"] = 1;
		}
		if (isset($clean["name"]) && isset($clean["home_country"])) return $clean;
		return "";
	}
	
	function _clear(&$str) {
		$str = strip_tags($str);
		$str = str_replace(array("\n","\r\n","\r","\t","&nbsp;","  ")," ",$str);
		$str = preg_replace('/\s\s+/', ' ', $str);
		$str = trim(str_replace("  "," ",$str));
	}
	
	function _logMysqlError($place, $q) {
		global $mysqli;
		$dir = dirname(__FILE__) . "/mysqli-log";
		@mkdir($dir);
		$info = [
			"q: " . $q,
			"error: " . $mysqli->error,
			"errno: " . $mysqli->errno,
			"ip: " . $_SERVER['REMOTE_ADDR'],
			"ua: " . $_SERVER['HTTP_USER_AGENT']
		];
		_save(implode(PHP_EOL, $info) . PHP_EOL . "=========" . PHP_EOL, "a",  $dir . "/" . date("YmdHis") . ".log", "nope");
		if (DEBUG_MODE == "ON") echo implode("<br />", $info) . "<br />-----<br />";
	}
	
	function _save($contents, $mode, $filename, $trim = "yes") {	
		_enc($contents);
		if ($trim == "yes") $contents = trim($contents);	
		$h = fopen($filename, $mode); 
		fwrite($h, $contents);
		fclose($h);	
		/*
		clearstatcache();		
		if (filesize($filename) > 2000000) {
			$ext = explode(".", $filename);
			$ext = array_pop($ext);
			$ext = "." . $ext;
			$new_filename = str_replace($ext, "-" . date("YmdHis") . $ext, $filename);
			rename($filename, $new_filename);
		}
		*/
	}

	function _read($filename) {
		$contents = "";
		if (file_exists($filename) && filesize($filename)>0) {
			$h = fopen($filename, "r");
			$contents = fread($h, filesize($filename));
			fclose($h);
		}
		_enc($contents);
		return $contents;
	}

	function _enc(&$contents) {
		$enc = mb_detect_encoding($contents);
		if ($enc != "UTF-8") {
			$contents = mb_convert_encoding($contents, "UTF-8", $enc);
		}
	}
	
	function _getCountry($ip) {
		global $SxGeo;
		$to_return = [
						"country" => "undefined",
						"city" => "undefined",
						"latitude" => "undefined",
						"longitude" => "undefined"
					];
		if (isset($ip) && mb_strlen($ip) > 0 && isset($SxGeo)) {
			$temp = $SxGeo->getCityFull($ip);
			$to_return["country"] = $temp["country"]['name_en'];
			$to_return["city"] = $temp["city"]['name_en'];
			$to_return["latitude"] = $temp["city"]['lat'];
			$to_return["longitude"] = $temp["city"]['lon'];
		}
		return $to_return;
	}
	
	function _getTable() {
		global $mysqli;
		$status = "success";
		$table = "";
		$countries_block = "";
		$q = "SELECT * FROM `users` ORDER BY `rating` DESC LIMIT 1000;";
		if ($result = $mysqli->query($q)) {
			if ($result->num_rows > 0) {
				$countries = [];
				
				$table = '<table class="table table-hover"><thead><tr> <th scope="col">ID</th><th scope="col">Name</th> <th scope="col">Check In</th> <th scope="col">IP</th><th scope="col">Rating</th> <th scope="col">Homeland</th><th scope="col">Check In Country</th></tr></thead><tbody>';
				while ($row = $result->fetch_assoc()) {
					if ($row['is_active'] == 0) $active = " not_active"; else $active = " active";
					$countries[]= $row['checkin_country'];
					$table .= '<tr class="' . $active . ' ' . str_replace(" ", "-", $row['checkin_country']) . '">' . 
					  '<th scope="row">' . $row['id'] . '</th>' . 
					  '<td>' . $row['name'] . '</td>' . 
					  '<td>' . $row['checkin_date'] . '</td>' . 
					  '<td>' . $row['ip'] . '</td>' . 
					  '<td>' . $row['rating'] . '</td>' . 
					  '<td>' . $row['home_country'] . '</td>' .
					  '<td>' . _flag($row['checkin_country']) . $row['checkin_country'] . '</td>' . 
					'</tr>';
				}
			} else $status = "no rows";
			$table .= '</tbody></table>';
			  
			$countries = array_count_values($countries);
			arsort($countries);
			$c = 1;
			foreach ($countries as $country => $n) {
				$countries_block.= '<div class="custom-control custom-checkbox"><input type="checkbox" checked="checked" class="custom-control-input _country" data-country="' . str_replace(" ", "-", $country) . '" id="country' . $c . '" /><label class="custom-control-label" for="country' . $c . '">' . _flag($country) . ' [' . $n . ']</label></div>';
				$c++;
			}
		} else {
			_logMysqlError(7, $q);
			$status = "error";
		}
		return [$table, $status, $countries_block];
	}
	
	function _flag($country) {
		$flag = "";
		//as is
		$file = "/img/flags16/" . str_replace(" ", "-", $country) . ".png";		
		if (file_exists(dirname(__FILE__) . $file)) $flag = "<div style=' background: url(" . WEB . "$file) transparent no-repeat 0px 1px;' class='flag float-left'></div>";
		
		//MB_CASE_TITLE
		else {			
			$file = mb_convert_case($country, MB_CASE_TITLE, 'UTF-8');
			$file = "/images/flags16/" . str_replace(" ", "-", $file) . ".png";
			if (file_exists(dirname(__FILE__) . $file)) $flag = "<div style=' background: url(" . WEB . "$file) transparent no-repeat 0px 1px;' class='flag float-left'></div>";
		}
		return $flag;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	