<?php

$d = DIRECTORY_SEPARATOR;
$project_path = implode($d, array_slice(explode($d, getcwd()), 0, -1));
$library_path = $project_path . $d . 'library';
$html_path = $project_path . $d . 'html';
set_include_path(get_include_path() . PATH_SEPARATOR . $library_path);

/**
 * Sass_Parser
 */
require_once 'Sass/Parser.php';

$css_path = $html_path . $d . 'css';
$sass_path = $css_path . $d . 'sass';
$css_file = $css_path . $d . basename($_SERVER['REQUEST_URI']);
$sass_file = $sass_path . $d . preg_replace('/\.css$/', '.sass', basename($_SERVER['REQUEST_URI']));
$cache_file = preg_replace('/\.css$/', '.csscache', $css_file);
if(file_exists($cache_file) && file_exists($sass_file)) {
  if(filemtime($cache_file) > filemtime($sass_file)) $css = file_get_contents($cache_file);
  else {
    $parser = new Sass_Parser(file_get_contents($sass_file));
    $css = $parser->render();
    file_put_contents($cache_file, $css);
  }
}
elseif(file_exists($sass_file)) {
  $parser = new Sass_Parser(file_get_contents($sass_file));
  $css = $parser->render();
  file_put_contents($cache_file, $css);
}
elseif(file_exists($css_file)) {
  $css = file_get_contents($css_file);
}
else {
  throw new Exception('No matching SASS or CSS file was found!');
}

header('Content-type: text/css');
echo $css;