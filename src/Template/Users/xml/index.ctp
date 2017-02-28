// src/Template/Recipes/xml/index.ctp
// Do some formatting and manipulation on
// the $users array.
$xml = Xml::fromArray(['response' => $users]);
echo $xml->asXML();