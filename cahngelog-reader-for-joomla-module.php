<?php
/*
 * Call via example.org?file=justesting&element=whatever
 * file is mandatory and part of the filename "justtesting-changelog.xml".
 * element is optional but a must if <element> tags don't match the file parameter.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

$input     = Factory::getApplication()->input;
$whichFile = trim($input->get('file'));

if (empty($whichFile))
{
	echo 'Missing file parameter.';
	return;
}

$url = 'https://raw.githubusercontent.com/GHSVS-de/upadateservers/master/' . $whichFile . '-changelog.xml';

@$xml = simplexml_load_file($url, null, LIBXML_NOCDATA);

if (
	empty($xml)
	|| !($xml instanceof SimpleXMLElement)
	|| $xml->getName() !== 'changelogs'
	|| empty($xml->changelog) || !($xml->changelog instanceof SimpleXMLElement)
){
	echo 'No XML file found.';
	return;
}

if (!($whichElement = trim($input->get('element'))))
{
  $whichElement = $whichFile;
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
			$values[] = str_replace('Element', 'Extension',
				ucfirst($key) . ': ' . (string) $changelog->$key);
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

			$data[$version][$key] = '<div><h4>' . strtoupper($key) . '</h4><ul><li>' . implode("</li><li>", $collect) . '</li></ul></div>';
		}
	}
}

$data = array_reverse($data);

foreach ($data as $key => $changelog)
{
	echo '<hr><h3>Version ' . $key . '</h3>';
	echo implode("\n", $changelog) . '<hr>';
}
return;
