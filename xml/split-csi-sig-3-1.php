#!/usr/bin/php
<?php

$file = $_SERVER['argv'][1];
if (!file_exists($file)) {
  throw new Exception('File was not supplied or invalid location');
}

$xml = file_get_contents($file);
$dom = new DOMDocument();
$dom->loadXML($xml);
$file = preg_replace('/\.xml$/', '', $file);
$filename1 = $file . '-part1.xml';
$filename2 = $file . '-part2.xml';
$filename3 = $file . '-part3.xml';

$errors = libxml_get_errors();
foreach ($errors as $error) {
  error_log("XML error on line {$error->line} of {$error->file}: {$error->message}");
}
if(count($errors) > 0) throw new Exception('XML Exception');

$questionnaire = $dom->getElementsByTagName('questionnaire')->item(0);
$questionnaireName = $questionnaire->getAttribute('questionnaireName');
$questionnaireVersion = $questionnaire->getAttribute('questionnaireVersion');
$revision = $questionnaire->getAttribute('revision');

$file1 = '<?xml version="1.0" encoding="UTF-8"?>
<csi:questionnaire xmlns:csi="http://www.csinitiative.com/ns/csi-qframe" questionnaireName="CSI SIG Part 1/3" questionnaireVersion="3.1" revision="' . $revision . '" targetQFrameVersion="1.0">
<csi:pages>';
$file2 = '<?xml version="1.0" encoding="UTF-8"?>
<csi:questionnaire xmlns:csi="http://www.csinitiative.com/ns/csi-qframe" questionnaireName="CSI SIG Part 2/3" questionnaireVersion="3.1" revision="' . $revision . '" targetQFrameVersion="1.0">
<csi:pages>';
$file3 = '<?xml version="1.0" encoding="UTF-8"?>
<csi:questionnaire xmlns:csi="http://www.csinitiative.com/ns/csi-qframe" questionnaireName="CSI SIG Part 3/3" questionnaireVersion="3.1" revision="' . $revision . '" targetQFrameVersion="1.0">
<csi:pages>';

if ($questionnaireName != 'CSI SIG' || $questionnaireVersion != '3.1') {
  throw new Exception('This script only works with CSI SIG 3.1');
}

$pages = $questionnaire->getElementsByTagName('page');
for ($p = 0; $p < $pages->length; $p++) {
  $page = $pages->item($p);
  $pageHeader = $page->getElementsByTagName('pageHeader')->item(0)->nodeValue;
  $pageGUID = $page->getElementsByTagName('pageGUID')->item(0)->nodeValue;
  $seqNumber = $page->getElementsByTagName('seqNumber')->item(0)->nodeValue;
  $description = $page->getElementsByTagName('description')->item(0)->nodeValue;
  $headerText = $page->getElementsByTagName('headerText')->item(0)->nodeValue;
  $footerText = $page->getElementsByTagName('footerText')->item(0)->nodeValue;
  $cloneable = $page->getElementsByTagName('cloneable')->item(0)->nodeValue;
  if (preg_match('/High Level/', $pageHeader)) {
    $file1 .= "<csi:page>
          <csi:pageHeader>$pageHeader</csi:pageHeader>
          <csi:pageGUID>$pageGUID</csi:pageGUID>
          <csi:seqNumber>$seqNumber</csi:seqNumber>
          <csi:description>$description</csi:description>
          <csi:headerText>$headerText</csi:headerText>
          <csi:footerText>$footerText</csi:footerText>
          <csi:cloneable>$cloneable</csi:cloneable>
          <csi:sections>";
    $file2 .= "<csi:page>
          <csi:pageHeader>$pageHeader</csi:pageHeader>
          <csi:pageGUID>$pageGUID</csi:pageGUID>
          <csi:seqNumber>$seqNumber</csi:seqNumber>
          <csi:description>$description</csi:description>
          <csi:headerText>$headerText</csi:headerText>
          <csi:footerText>$footerText</csi:footerText>
          <csi:cloneable>$cloneable</csi:cloneable>
          <csi:sections>";
    $file3 .= "<csi:page>
          <csi:pageHeader>$pageHeader</csi:pageHeader>
          <csi:pageGUID>$pageGUID</csi:pageGUID>
          <csi:seqNumber>$seqNumber</csi:seqNumber>
          <csi:description>$description</csi:description>
          <csi:headerText>$headerText</csi:headerText>
          <csi:footerText>$footerText</csi:footerText>
          <csi:cloneable>$cloneable</csi:cloneable>
          <csi:sections>";
    $sections = $page->getElementsByTagName('section');
    for ($s = 0; $s < $sections->length; $s++) {
      $section = $sections->item($s);
      $sectionHeader = $section->getElementsByTagName('sectionHeader')->item(0)->nodeValue;
      if (preg_match('/^[A-F]\./', $sectionHeader)) {
        $file1 .= $dom->saveXML($section);
      }
      elseif (preg_match('/^[G]\./', $sectionHeader)) {
        $file2 .= $dom->saveXML($section);
      }
      elseif (preg_match('/^[H-M]\./', $sectionHeader)) {
        $file3 .= $dom->saveXML($section);
      }
    }
    $file1 .= "</csi:sections></csi:page>";
    $file2 .= "</csi:sections></csi:page>";
    $file3 .= "</csi:sections></csi:page>";
  }
  elseif (preg_match('/^([A-F]\.|Business Info|Documentation)/', $pageHeader)) {
    $file1 .= $dom->saveXML($page);
  }
  elseif (preg_match('/^[G]\./', $pageHeader)) {
    $file2 .= $dom->saveXML($page);
  }
  elseif (preg_match('/^[H-M]\./', $pageHeader)) {
    $file3 .= $dom->saveXML($page);
  }
  else {
    $file1 .= $dom->saveXML($page);
    $file2 .= $dom->saveXML($page);
    $file3 .= $dom->saveXML($page);
  }
}

$file1 .= '</csi:pages>
</csi:questionnaire>';
$file2 .= '</csi:pages>
</csi:questionnaire>';
$file3 .= '</csi:pages>
</csi:questionnaire>';

file_put_contents($filename1, $file1);
file_put_contents($filename2, $file2);
file_put_contents($filename3, $file3);
