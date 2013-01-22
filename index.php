<?php error_reporting(E_ALL & ~E_NOTICE); header('Content-Type: text/html; charset=utf-8'); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Google Cache Dumper</title>
	<meta http-equiv="content-type" />
	<style type="text/css">
	body {
		background-color: #DDDDDD;
		font-family: "Trebuchet MS", Arial, sans-serif;
	}
	h1, h2 {
		font-family: serif;
		text-align: center;
	}
	#page {
		background-color: #FFFFFF;
		border: 2px solid #AAAAAA;
		margin: 0 auto;
		padding: 20px;
		width: 80%;
	}
	span.help {
		cursor: help;
	}
	div.error {
		border: 1px solid #AA0000;
		background-color: #FFAAAA;
		padding: 5px;
	}
	.google {
		font-size: 1.2em;
	}
	.copy {
		text-align: center;
		color: #525252;
	}
	.copy a {
		color: #52728C;
	}
	</style>
</head>
<body>
<div id="page">
<h1><span class="google"><span style="color:#184DC6">G</span><span style="color:#C61800">o</span><span style="color:#EFBA00">o</span><span style="color:#184DC6">g</span><span style="color:#31B639">l</span><span style="color:#C61800">e</span></span> Cache Dumper</h1>
<form action="<?=basename(__FILE__)?>">
<table align="center" cellpadding="4">
	<tr>
		<td>
			<span class="help" title="Сайт, который будем скачивать">Домен</span>
		</td>
		<td>
			<input type="text" name="domain" value="<?=$_GET['domain']?>">
		</td>
	</tr>
	<tr>
		<td>
			<span class="help" title="Количество страниц выдачи, которые будут распарсены. 0 - парсить все.">Количество страниц</span>
		</td>
		<td>
			<input type="text" name="p" value="<?=isset($_GET['p'])?$_GET['p']:2?>">
		</td>
	</tr>
	<tr>
		<td>
			<span class="help" title="Дата, начиная с которой требуемые страницы были проиндексированы. Пустое - нет ограничения.">Начальная дата</span>
		</td>
		<td>
			<input type="text" name="date_start" value="<?=isset($_GET['date_start'])?$_GET['date_start']:''?>">
		</td>
	</tr>
	<tr>
		<td>
			<span class="help" title="Дата, заканчивая которой требуемые страницы были проиндексированы. Пустое - нет ограничения.">Конечная дата</span>
		</td>
		<td>
			<input type="text" name="date_end" value="<?=isset($_GET['date_end'])?$_GET['date_end']:''?>"><br />
			M/D/Y
		</td>
	</tr>
	<tr>
		<td>
			<span class="help" title="Задержка между запросами, секунды">Задержка</span>
		</td>
		<td>
			<input type="text" name="sleep" value="<?=isset($_GET['sleep'])?$_GET['sleep']:3?>"> сек.
		</td>
	</tr>
	<tr>
		<td align="center" colspan="2">
<?php
if(is_dir('./out/') && is_writeable('./out/')) {
?>
			<input type="submit" name="submit" value="Начать">
<?php
} else {
unset($_GET['submit']);
?>
			<div class="error">Папка ./out/ должна существовать и быть доступной для записи!</div>
<?php
}
?>
		</td>
	</tr>
</table>
</form>
<?php
$PROXY='proxy.txt';

set_time_limit(0);
ini_set('pcre.backtrack_limit', 10000000);

function dbg($var)
{
	echo '<pre>';
	ob_start();
	echo var_dump($var);
	$c = ob_get_contents();
	ob_end_clean();
	echo htmlspecialchars($c);
	echo '</pre>';
	flush();
}

function say($str, $js = true)
{
	static $n = 0;
	echo "Msg #$n: $str<br>";
	if($js)
		echo "<a name=\"prgrs-$n\"></a><script>document.location.hash='prgrs-$n';</script>";
	flush();
	$n++;
}

function get($url, $proxy)
{
	static $useragents = array(
		'Mozilla/5.0 (Windows NT 6.2) AppleWebKit/536.6 (KHTML, like Gecko) Chrome/20.0.1090.0 Safari/536.6',
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_0) AppleWebKit/536.3 (KHTML, like Gecko) Chrome/19.0.1063.0 Safari/536.3',
		'Mozilla/5.0 (Windows NT 6.1; de;rv:12.0) Gecko/20120403211507 Firefox/12.0',
		'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:12.0) Gecko/20100101 Firefox/12.0',
		'Opera/9.80 (Windows NT 6.1; U; es-ES) Presto/2.9.181 Version/12.00',
		'Opera/9.80 (Windows NT 5.1; U; en) Presto/2.9.168 Version/11.51',
		);
	static $i = 0;
	$tries = 0;
	do {
		$useragent = $useragents[array_rand($useragents)];
		say($useragent);
		$i = $i % sizeof($proxy);
		$tries ++;
		say('Trying proxy: '.$proxy[$i], true);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
		curl_setopt($ch, CURLOPT_PROXY, $proxy[$i]);
		$result = curl_exec($ch);
		//dbg($result);
		curl_close($ch);
		$cond = $result == '' || strstr($result, 'but your computer or network may be sending automated') || strstr($result, 'but it appears your computer is sending automated requests') || (strstr($result, '/sorry/image?id=') && strstr($result, '302 Moved')) || strstr($result, 'detected unusual traffic from your computer network');
		
		if($result == '')
		{
			say('Proxy '.$proxy[$i]. ' doesn\'t work');
		}
		else if($cond)
		{
			say('Proxy '.$proxy[$i]. ' is banned by google');
		}
		
		$i ++;
		if($cond && $tries <= sizeof($proxy))
		{
			sleep(1);
		}
	} while($cond && $tries <= sizeof($proxy));
	if($tries > sizeof($proxy))
	{
		say('No luck, no proxy gave good result.');
	}
	return $result;
}

if(empty($_GET['domain']))
	unset($_GET['submit']);

if(isset($_GET['submit']))
{
	say('Parsing page list');
	$domain = $_GET['domain'];
	$sleep = $_GET['sleep'];
	$p = $_GET['p'];
	$date_start = $_GET['date_start'];
	$date_end = $_GET['date_end'];
	
	// Start parsing
	$pages = array();
	$parse = true;
	
	// Load Datacenter list
	$dc = file('dc.txt');
	$dc = array_map('trim', $dc);
	
	$proxy = file($PROXY);
	$proxy = array_map('trim', $proxy);
	$i = 0;
	
	$url = 'http://www.google.com/search?q=site%3A'.urlencode($domain).'&hl=en&num=50';
	if(!empty($date_start) || !empty($date_end))
	{
		$url .= '&tbs='.urlencode("cdr:1,cd_min:$date_start,cd_max:$date_end");
	}
	
	for($i = 0; $url && ($i < $p || $p == 0); $i++)
	{
		if($i%sizeof($proxy) == 0)
		{
			// Load proxy list
			say('Proxy list updated');
			$proxy = file($PROXY);
			$proxy = array_map('trim', $proxy);
		}
		$page = get($url, $proxy);
		say("<b>$url</b>");
		$parse = preg_match_all('#<h3 class="r"><a href="([^"]*)"#U', $page, $matches);
		
		foreach($matches[1] as $link)
		{
			$pages[] = $link;
			say('Page found: '.$link, false);
		}
		sleep($sleep+1);
		
		$parse = preg_match('#<td class="b navend"><a href="([^"]+)" class="pn" id="pnnext"#U', $page, $matches);
		$url = empty($matches[1])?false:str_replace('&amp;', '&', "http://google.com$matches[1]");
	}
	say('<b>Parsing finished!</b>');
	$pages = array_unique($pages);
	
	$dir = 'out/'.$domain;
	@mkdir($dir, 0777);
	chmod($dir, 0777);
	
	$fp = fopen($dir.'/zero.txt', 'a');
	
	say('<b>Starting downloading cache...</b>');
	$i = 0;
	foreach($pages as $page)
	{
		if($i%sizeof($proxy) == 0)
		{
			// Load proxy list
			say('Proxy list updated');
			$proxy = file($PROXY);
			$proxy = array_map('trim', $proxy);
		}
		$part = parse_url($page);
		if($part['path'][strlen($part['path'])-1] == '/')
		{
			$container = $dir.'/'.$part['host'].$part['path'];
			$file = $container . '/index' . ((empty($part['query']))?'':'_'.urlencode($part['query'])).'.html';
		}
		else
		{
			$container = $dir.'/'.$part['host'].dirname($part['path']);
			$file = $container.'/'.((dirname($part['path']) == $part['path'])?'index':basename($part['path'])).((empty($part['query']))?'':'_'.urlencode($part['query']).'.html');
		}
		//say($file);
		
		is_dir($container) or mkdir($container, 0777, true);
		chmod($container, 0777);
		
		say("Requesting $page");
		$cache = get('http://'.$dc[$i%sizeof($dc)].'/search?q=cache:'.urlencode($page), $proxy);
		say('http://'.$dc[$i%sizeof($dc)].'/search?q=cache:'.urlencode($page));
		$cache = preg_replace('#^.*<div style="position:relative">#misU', '', $cache);
		file_put_contents($file, $cache);
		chmod($file, 0777);
		
		say("Page $page saved to $file (".strlen($cache)." bytes)");
		
		if(strlen($cache) == 0)
		{
			fwrite($fp, "$page\n");
			fflush($fp);
		}
		sleep($sleep);
		$i++;
	}
	
	fclose($fp);
	
	say("<b>Dumping finished!</b>");
}
?>
</div>
<div class="copy">&copy; Alek$, <a href="http://nevkontakte.org.ru">http://nevkontakte.org.ru</a></div>
</body>
</html>