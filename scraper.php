<?php
#
#	Google Scraper V 1.0
#	François Grante 
#	http://francoisgrante.com/
#

include('simple_html_dom/simple_html_dom.php');

function strip_tags_content($text, $tags = '', $invert = FALSE) {

	$text = str_ireplace("<br>", "", $text);

	preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
	$tags = array_unique($tags[1]);

	if(is_array($tags) AND count($tags) > 0) {
		if($invert == FALSE) {
			return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
		} else {
			return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text);
		}
	} elseif($invert == FALSE) {
		return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
	}

	return $text;
}

function extract_url_from_redirect_link($url) {

	$q = parse_url($url)['query'];
	parse_str($q, $url_params)['q']['q'];

	if (isset($url_params['q']) AND (strpos($url_params['q'],'https://') !== false OR strpos($url_params['q'],'http://') !== false))
		return $url_params['q'];
	else
		return false;

}

function get_content($url) {

	$data = file_get_html($url);

	#
	#	Possible également avec CURL
	#

	return $data;
}

function scrap_to_csv($links) {

    $fp = fopen('scrap.csv', 'w');

	foreach ($links as $link) {
		$line = array();
		$line['link'] = $link;
		fputcsv($fp, $line);
	}

    fclose($fp);

}

$result = array();

if (isset($_POST['footprint']))
{
	$footprint = $_POST['footprint'];

	$q = urlencode(str_replace(' ', '+', $footprint));

	#
	#	Paramètres :
	#
	#	hl : la langue
	#	q : la requête
	#	num : nombre de résultats par page
	#	filter : permet d'afficher aussi les réultats ignorés
	#

	$data = get_content('https://www.google.com/search?hl=en&q='.$q.'&num=100&filter=0');

	$html = str_get_html($data);

	foreach($html->find('li.g') as $g)
	{

		$h3 = $g->find('h3.r', 0);
		$s = $g->find('span.st', 0);
		$a = $h3->find('a', 0);
		$url = $a->getAttribute('href');

		$link = extract_url_from_redirect_link($url);
		if (extract_url_from_redirect_link($url))
		{
			$result[] = array(
				'title' => strip_tags($a->innertext),
				'link' => extract_url_from_redirect_link($url),
				'description' => strip_tags_content($s->innertext));
		}
	}

	$links = array();
	foreach ($result as $line) {
		$links[] = $line['link'];
	}

	scrap_to_csv($links);
}
else
{
	$footprint ='';
}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Google Scraper</title>
	</head>
	<body>
		<h1>Google scraper</h1>
		<form method="post" action="index.php">
			<input type="text" name="footprint" placeholder="Search" style="width: 30%;" value="<?php echo $footprint; ?>" />
			<input type="submit" value="Scrap!"/>
		</form>
		<?php
		if (!empty($result)) {
			echo '<p><a href="scrap.csv">Download CSV</a></p>';
		}
		?>
		<br/>
		<?php
		foreach ($result as $line) {
			echo $line['link'].'<br>';
		}
		?>
	</body>
</html>
