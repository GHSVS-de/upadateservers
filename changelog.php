<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name=robots content="noindex, nofollow"/>
<title>Chengelogs Reader at ghsvs.de</title>
<style>
body
{
	font-family:Arial, Helvetica, sans-serif;
}
code
{
	font-family:"Courier New", Courier, monospace;
}
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
	font-size:1.2em;
}
h6
{
	font-size:1.1em;
	letter-spacing:1px;
}
li
{
	line-height:1.5em;
}
</style>
</head>
<body>
<?php
/*
 * Call via https://updates.ghsvs.de/changelog.php?file=justtesting&element=whatever&version=2019.06.25
 * file is mandatory and part of the filename "justtesting-changelog.xml".
 * element is optional but a must if <element> tags don't match the file parameter.
 * version is optional: Show only changelog of this version.
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

$allLogs = '';

if ($whichVersion = trim($_GET['version']))
{
	if ($whichElement === $whichFile)
	{
		$allLogs = '<p><strong><a href="https://updates.ghsvs.de/changelog.php?file=' . $whichFile . '">
					See changelogs of all versions
				</a></strong>
				</p>';
	}
}

if ($extension = trim($xml->attributes()->extension))
{
	echo '<h4>' . $extension . '</h4>';
}

if ($projecturl = trim($xml->attributes()->projecturl))
{
	echo '<p>Project page: <a href="' . $projecturl . '">' . $projecturl . '</a></p>';
}

echo $allLogs;

$data = array();

foreach ($xml->changelog as $changelog)
{
	$version = trim($changelog->version);
	
	$excludeIfNoVersion = $changelog->attributes()->excludeIfNoVersion;

	if (
		! $version
		|| ($whichVersion && $whichVersion !== $version)
	){
		continue;
	}

	if (
		$excludeIfNoVersion
		&& (! $whichVersion || $whichVersion !== $version)
	){
		continue;
	}

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
		'addition',
		'change',
		'remove',
		'language',
		'note',
		'requirement',
	);

	// Subtag entry instead of item to keep away from Joomla 4 display.
	$ownTags = array(
		'requirement',
	);

	foreach ($do as $key)
	{
		$subTag = in_array($key, $ownTags) ? 'entry' : 'item';

		if (
			!empty($changelog->$key)
			&& ($changelog->$key instanceof SimpleXMLElement)
			&& !empty($changelog->$key->$subTag)
			&& ($changelog->$key->$subTag instanceof SimpleXMLElement)
		){
			$collect = array();
			
			foreach ($changelog->$key->$subTag as $item)
			{
				$collect[] = trim($item);
			}

			$data[$version][$key] = '<div><h6>' . ucfirst($key) . '</h6><ul><li>' . implode("</li><li>", $collect) . '</li></ul></div>';
		}
	}
}

if ($data)
{
	ksort($data);
	$data = array_reverse($data);

	foreach ($data as $key => $changelog)
	{
		echo '<hr><h5>Version ' . $key . '</h5>';
		echo implode("\n", $changelog) . '<hr>';
	}
}
elseif ($allLogs)
{
	echo '<hr><h5>No changelogs found for version ' . $whichVersion . '</h5>' . $allLogs;
}
?>
</body>
</html>