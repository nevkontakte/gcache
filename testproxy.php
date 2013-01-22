<pre>
<?php
$PROXY='proxy.txt';
$proxy = file($PROXY);
$proxy = array_map('trim', $proxy);
function get($url, $proxy)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)");
	curl_setopt($ch, CURLOPT_PROXY, $proxy);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

$good = array();
$bad = array();
foreach($proxy as $p) {
	$robots = get('http://www.google.com/robots.txt', $p);
	if(strpos($robots, 'Sitemap: http://www.google.com') !== false) {
		$search = get('http://www.google.com/search?q=site:google.com', $p);
		if(strpos($search, '/sorry/image?id=') !== false)
		{
			$bad[] = $p;
		}
		else
		{
			$good[] = $p;
		}
	}
	else {
		$bad[] = $p." (response: ".htmlspecialchars(str_replace(array("\n", "\r"), '', substr($robots, 0, 60))).")";
	}
}

echo "Good proxies:\n";
foreach($good as $p) {
	echo $p."\n";
}
echo "Bad proxies:\n";
foreach($bad as $p) {
	echo $p."\n";
}
?>
