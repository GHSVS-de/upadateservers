<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name=robots content="noindex, nofollow"/>
<title>Chengelogs Reader at ghsvs.de</title>
<style>
h4,h5,h6
{
	margin:0.5em 0 1em 0;
}
h4
{
	font-size:1.3em;
}
h5
{
	font-size:1.1em;
}
</style>
</head>
<body>
<?php
/*
 * Call via https://updates.ghsvs.de/changelog.php?file=justtesting&element=whatever
 * file is mandatory and part of the filename "justtesting-changelog.xml".
 * element is optional but a must if <element> tags don't match the file parameter.
 */

//defined('_JEXEC') or die;

//use Joomla\CMS\Factory;

//$input     = Factory::getApplication()->input;
$whichFile = trim($_GET['file']);

if (empty($whichFile))
{
	echo 'Missing file parameter.';
	return;
}

$url = 'https://raw.githubusercontent.com/GHSVS-de/upadateservers/master/' . $whichFile . '-changelog.xml';

// Disable libxml errors and allow to fetch error information as needed
libxml_use_internal_errors(true);

echo '<p><a href="' . $url . '">' . $url . '</a></p>';

@$xml = simplexml_load_file($url, null, LIBXML_NOCDATA);

if ($xml === false)
{
	echo 'Errors with file ' . $url . "\n<br>";

	foreach (libxml_get_errors() as $error)
	{
		echo $error->message . "\n<br>";
	}
	return;
}
elseif (empty($xml))
{
	echo 'Empty result with file ' . $url . "\n<br>";
	return;
}
elseif (!($xml instanceof SimpleXMLElement))
{
	echo 'Errors with file ' . $url . ". Not instencaof SimpleXMLElement.\n<br>";
	return;
}
elseif ($xml->getName() !== 'changelogs')
{
	echo 'Errors with file ' . $url . ". No parent changelogs tag.\n<br>";
	return;
}
elseif (empty($xml->changelog) || !($xml->changelog instanceof SimpleXMLElement))
{
	echo 'Errors with file ' . $url . ". No changelog child tag or child tag not instencaof SimpleXMLElement.\n<br>";
	return;
}

if (!($whichElement = trim($_GET['element'])))
{
  $whichElement = $whichFile;
}

if ($extension = trim($xml->attributes()->extension))
{
	echo '<h4>' . $extension . '</h4';
}

$data = array();

foreach ($xml->changelog as $changelog)
{
	$version = trim($changelog->version);

	if (!$version) continue;

	$element = trim($changelog->element);

	if ($element !== $whichElement) continue;

	$do = array('element', 'folder', 'type');

	$values = array();

	foreach ($do as $key)
	{
		if (isset($changelog->$key))
		{
			$values[] = ucfirst($key) . ': ' . (string) $changelog->$key;
		}
	}

	$data[$version]['headline'] = '<p>' . implode(", ", $values) . '</p>';

	$do = array(
		'security',
		'fix',
		'change',
		'addition',
		'remove',
		'language',
		'note',
		'requirement',
	);

	foreach ($do as $key)
	{
		if (
			!empty($changelog->$key)
			&& ($changelog->$key instanceof SimpleXMLElement)
			&& !empty($changelog->$key->item)
			&& ($changelog->$key->item instanceof SimpleXMLElement)
		){
			$collect = array();

			foreach ($changelog->$key->item as $item)
			{
				$collect[] = trim($item);
			}

			$data[$version][$key] = '<div><h6>' . strtoupper($key) . '</h6><ul><li>' . implode("</li><li>", $collect) . '</li></ul></div>';
		}
	}
}

ksort($data);
$data = array_reverse($data);

foreach ($data as $key => $changelog)
{
	echo '<hr><h5>Version ' . $key . '</h5>';
	echo implode("\n", $changelog) . '<hr>';
}

?>
</body>
</html>