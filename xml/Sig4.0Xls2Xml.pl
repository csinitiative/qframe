#!/usr/bin/perl

use strict;
use Spreadsheet::ParseExcel;
use Spreadsheet::ParseExcel::FmtUnicode;
use Spreadsheet::ParseExcel::FmtDefault;
use Data::Dumper;
use Encode;
binmode STDOUT, ":utf8";

my $excel = Spreadsheet::ParseExcel::Workbook->Parse('SIGv4_Final.xls');

my $first = qq[<?xml version="1.0" encoding="UTF-8"?>
<csi:questionnaire xmlns:csi="http://www.csinitiative.com/ns/csi-qframe" questionnaireName="CSI SIG" questionnaireVersion="4.0" revision="1" targetQFrameVersion="1.0">
  <csi:pages>
];

my $added_question = 0;
my $questionSeqNumber = 1;
my $sectionSeqNumber = 1;
my $pageSeqNumber = 1;
my $sectionGUID = 1;
my $pageGUID = 1;
my %virtual;
my %real;
my %realDisable;
my %lastQueueDepths;

foreach my $sheet (@{$excel->{Worksheet}}) {
  $sheet->{MaxRow} ||= $sheet->{MinRow};
  $sheet->{Name} =~ s/\_/ /g;
  $first .= qq[    <csi:page>
      <csi:pageHeader>] . encode_entities($sheet->{Name}) . qq[</csi:pageHeader>
      <csi:pageGUID>] . $pageGUID++ . qq[</csi:pageGUID>
      <csi:seqNumber>] . $pageSeqNumber++ . qq[</csi:seqNumber>
      <csi:sections>
        <csi:section>
          <csi:sectionHeader></csi:sectionHeader>
          <csi:sectionGUID>] . $sectionGUID++ . qq[</csi:sectionGUID>
          <csi:seqNumber>] . $sectionSeqNumber++ . qq[</csi:seqNumber>
          <csi:questions>\n];
  my $in_question_group;
  foreach my $row ($sheet->{MinRow} .. $sheet->{MaxRow}) {
    
    if ($sheet->{Name} =~ /^Business Info/) {
      my $questionGUID = ($sheet->{Cells}[$row][0]->{Val});
      my $qText = ($sheet->{Cells}[$row][1]->{Val});
      $qText = Encode::decode('UCS2', $qText) if $sheet->{Cells}[$row][1]->{Code} eq 'ucs2';

      if ($questionGUID =~ /\d/) {
        $first .= qq[            <csi:question>\n];
        $first .= qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
        $first .= qq[              <csi:questionGUID>] . $questionGUID . qq[</csi:questionGUID>\n];
        $first .= qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
        $first .= qq[              <csi:questionType>T</csi:questionType>\n];
        $first .= qq[            </csi:question>\n];
      }
    }
    elsif ($sheet->{Name} =~ /^Documentation/) {
      my $questionGUID = ($sheet->{Cells}[$row][0]->{Val});
      my $qText = ($sheet->{Cells}[$row][1]->{Val});
      $qText = Encode::decode('UCS2', $qText) if $sheet->{Cells}[$row][1]->{Code} eq 'ucs2';

      if ($questionGUID =~ /\d/) {
        $first .= qq[            <csi:question>\n];
        $first .= qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
        $first .= qq[              <csi:questionGUID>] . $questionGUID . qq[</csi:questionGUID>\n];
        $first .= qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
        $first .= qq[              <csi:questionType>T</csi:questionType>\n];
        $first .= qq[            </csi:question>\n];
      }
    }
    elsif ($sheet->{Name} =~ /Lv\d /) {
      my $questionNumberRef = ($sheet->{Cells}[$row][0]->{Val});
      my $questionNumber = ($sheet->{Cells}[$row][1]->{Val});
      my $qText = ($sheet->{Cells}[$row][2]->{Val});
      $qText = Encode::decode('UCS2', $qText) if $sheet->{Cells}[$row][2]->{Code} eq 'ucs2';
      next unless $questionNumberRef =~ /\d\s*$/;
      next unless $questionNumber =~ /\d\s*$/;

      if (defined($virtual{$questionNumberRef})) {
        $first .= qq[            <csi:question>\n];
        $first .= qq[              <csi:questionGUID>UNKNOWN-$questionNumberRef</csi:questionGUID>\n];
        $first .= qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
        $first .= qq[              <csi:questionNumber>] . $questionNumber . qq[</csi:questionNumber>\n];
        $first .= qq[              <csi:questionType>V</csi:questionType>\n];
        $first .= qq[            </csi:question>\n];
      }
      else {
        $first .= qq[            <csi:question>\n];
        $first .= qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
        $first .= qq[              <csi:questionGUID>UNKNOWN-$questionNumberRef</csi:questionGUID>\n];
        $first .= qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
        $first .= qq[              <csi:questionNumber>] . $questionNumber . qq[</csi:questionNumber>\n];
        $first .= qq[              <csi:questionType>S</csi:questionType>\n];
        $first .= qq[              <csi:questionPrompt>\n];
        $first .= qq[                <csi:promptText>Yes</csi:promptText>\n];
        $first .= qq[              </csi:questionPrompt>\n];
        $first .= qq[              <csi:questionPrompt>\n];
        $first .= qq[                <csi:promptText>No</csi:promptText>\n];
        $first .= qq[              </csi:questionPrompt>\n];
        $first .= qq[              <csi:questionPrompt>\n];
        $first .= qq[                <csi:promptText>N/A</csi:promptText>\n];
        $first .= qq[                <csi:requireAdditionalInfo>1</csi:requireAdditionalInfo>\n];
        $first .= qq[              </csi:questionPrompt>\n];
        $first .= qq[            </csi:question>\n];
        $virtual{$questionNumberRef}{found} = 1;
      }
    }
    elsif ($sheet->{Name} =~ /^\w{1,2}\./) {
      my $questionGUID = ($sheet->{Cells}[$row][0]->{Val});
      my $questionNumber = ($sheet->{Cells}[$row][1]->{Val});
      my $qText = ($sheet->{Cells}[$row][2]->{Val});
      my $aup = ($sheet->{Cells}[$row][5]->{Val});
      my $iso = ($sheet->{Cells}[$row][6]->{Val});
      my $iso_name = ($sheet->{Cells}[$row][7]->{Val});
      $qText = Encode::decode('UCS2', $qText) if $sheet->{Cells}[$row][2]->{Code} eq 'ucs2';
      $aup = Encode::decode('UCS2', $aup) if $sheet->{Cells}[$row][5]->{Code} eq 'ucs2';
      $iso = Encode::decode('UCS2', $iso) if $sheet->{Cells}[$row][6]->{Code} eq 'ucs2';
      $iso_name = Encode::decode('UCS2', $iso_name) if $sheet->{Cells}[$row][7]->{Code} eq 'ucs2';
      next unless $qText && $questionGUID =~ /\d/;

      my $queueDepth = ($sheet->{Cells}[$row][8]->{Val});
      foreach my $q (keys %lastQueueDepths) {
        if ($lastQueueDepths{$q} >= $queueDepth) {
          delete $lastQueueDepths{$q};
        }
      }
      foreach my $q (keys %lastQueueDepths) {
        push @{$realDisable{$q}{disable}}, $questionGUID;
      }
       
      if ($qText =~ /:\s*$/ && $questionNumber !~ /^(F\.1\.9\.20\.1|E\.3\.8\.1|E\.3\.1|E\.2\.1\.4|B\.3\.1\.1)$/) {
        if (defined($virtual{$questionNumber})) {
          die ('There should not be a questionGroup for a virtual question:' . $questionNumber);
        }
        if ($in_question_group) {
          if (!$added_question ) {
            print STDERR "Did not add a question for questionGroup before question: $qText\n";
          }
          $first .= qq[            </csi:questionGroup>\n];
          $in_question_group = 0;
          $added_question = 0;
        }

        $first .= qq[            <csi:questionGroup>\n];
        $first .= qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
        $first .= qq[              <csi:questionGUID>] . $questionGUID . qq[</csi:questionGUID>\n];
        $first .= qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
        $first .= qq[              <csi:groupQuestionNumber>] . $questionNumber . qq[</csi:groupQuestionNumber>\n];
        if ($aup || ($iso && $iso ne 'N/A')) {
          $first .= qq[              <csi:groupQuestionReferences>\n];
          if ($aup) {
            $first .= qq[                <csi:reference>\n];
            $first .= qq[                  <csi:shortName>AUP</csi:shortName>\n];
            $first .= qq[                  <csi:referenceName>Agreed Upon Procedures</csi:referenceName>\n];
            $first .= qq[                  <csi:item>] . encode_entities($aup) . qq[</csi:item>\n];
            $first .= qq[                </csi:reference>\n];
          }
          if ($iso && $iso ne 'N/A') {
            $first .= qq[                <csi:reference>\n];
            $first .= qq[                  <csi:shortName>ISO</csi:shortName>\n];
            $first .= qq[                  <csi:referenceName>ISO 17799:2005</csi:referenceName>\n];
            $first .= qq[                  <csi:item>] . encode_entities("$iso $iso_name") . qq[</csi:item>\n];
            $first .= qq[                </csi:reference>\n];
          }
          $first .= qq[              </csi:groupQuestionReferences>\n];
        }
        $in_question_group = $questionNumber;
      }
      elsif ($qText) {
        my $padding = '';
        if ($in_question_group) {
          if ($questionNumber =~ /^$in_question_group\./) {
            $padding = '  ';
          }
          else {
            if (!$added_question ) {
              print STDERR "Did not add a question for questionGroup before question: $qText\n";
            }
            $first .= qq[            </csi:questionGroup>\n];
            $in_question_group = 0;
          }
        }
        
        $added_question = 1;
        if (defined($virtual{$questionNumber})) {
          $real{$questionNumber}{guid} = $questionGUID;
          $lastQueueDepths{$questionGUID} = $queueDepth;
          $virtual{$questionNumber}{guid} = $questionGUID;
          $first .= qq[$padding            <csi:question>\n];
          $first .= qq[$padding              <csi:questionGUID>] . $questionGUID . qq[</csi:questionGUID>\n];
          $first .= qq[$padding              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
          $first .= qq[$padding              <csi:questionNumber>] . $questionNumber . qq[</csi:questionNumber>\n];
          $first .= qq[$padding              <csi:questionType>V</csi:questionType>\n];
          $first .= qq[$padding            </csi:question>\n];
        }
        else {
          $first .= qq[$padding            <csi:question>\n];
          $first .= qq[$padding              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
          $first .= qq[$padding              <csi:questionGUID>] . $questionGUID . qq[</csi:questionGUID>\n];
          $first .= qq[$padding              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
          $first .= qq[$padding              <csi:questionNumber>] . $questionNumber . qq[</csi:questionNumber>\n];
          if ($aup || ($iso && $iso ne 'N/A')) {
            $first .= qq[$padding              <csi:questionReferences>\n];
            if ($aup) {
              $first .= qq[$padding                <csi:reference>\n];
              $first .= qq[$padding                  <csi:shortName>AUP</csi:shortName>\n];
              $first .= qq[$padding                  <csi:referenceName>Agreed Upon Procedures</csi:referenceName>\n];
              $first .= qq[$padding                  <csi:item>] . encode_entities($aup) . qq[</csi:item>\n];
              $first .= qq[$padding                </csi:reference>\n];
            }
            if ($iso && $iso ne 'N/A') {
              $first .= qq[$padding                <csi:reference>\n];
              $first .= qq[$padding                  <csi:shortName>ISO</csi:shortName>\n];
              $first .= qq[$padding                  <csi:referenceName>ISO 17799:2005</csi:referenceName>\n];
              $first .= qq[$padding                  <csi:item>] . encode_entities("$iso $iso_name") . qq[</csi:item>\n];
              $first .= qq[$padding                </csi:reference>\n];
            }
            $first .= qq[$padding              </csi:questionReferences>\n];
          }
          $first .= qq[$padding              <csi:questionType>S</csi:questionType>\n];
          $first .= qq[$padding              <csi:questionPrompt>\n];
          $first .= qq[$padding                <csi:promptText>Yes</csi:promptText>\n];
          $first .= qq[$padding              </csi:questionPrompt>\n];
          $first .= qq[$padding              <csi:questionPrompt>\n];
          $first .= qq[$padding                <csi:promptText>No</csi:promptText>\n];
          $first .= qq[$padding              </csi:questionPrompt>\n];
          $first .= qq[$padding              <csi:questionPrompt>\n];
          $first .= qq[$padding                <csi:promptText>N/A</csi:promptText>\n];
          $first .= qq[$padding                <csi:requireAdditionalInfo>1</csi:requireAdditionalInfo>\n];
          $first .= qq[$padding              </csi:questionPrompt>\n];
          $first .= qq[$padding            </csi:question>\n];
        }
      }
    }
  }
  if ($in_question_group) {
    if (!$added_question ) {
      print STDERR "Did not add a question for questionGroup before question: END\n";
    }
    $first .= qq[            </csi:questionGroup>\n];
    $added_question = 0;
  }
  $first .= qq[          </csi:questions>\n];
  $first .= qq[        </csi:section>\n];
  $first .= qq[      </csi:sections>\n];
  $first .= qq[    </csi:page>\n];
}

$first .= qq[  </csi:pages>\n];
$first .= qq[</csi:questionnaire>];

my $second = '';
my @lines = split(/\n/, $first);
for (my $i = 0; $i < @lines; $i++) {
  my $line = $lines[$i];
  if ($line =~ /UNKNOWN-(.+?)<\/csi:questionGUID/) {
    my $questionNumber = $1;
    my $guid = $real{$questionNumber}{guid} || die ("Could not find GUID for questionNumber: $questionNumber");
    $line =~ s/UNKNOWN-(.+?)<\/csi:questionGUID/$guid<\/csi:questionGUID/;
    if (defined($realDisable{$guid}{disable}) && $lines[$i+8] =~ /^(.+)<csi:promptText.+No/) {
      my $padding = $1;
      foreach my $guid (sort {$a <=> $b} @{$realDisable{$guid}{disable}}) {
        $lines[$i+8] .= "\n$padding<csi:disableQuestion>$guid</csi:disableQuestion>";
      }
    }
  }
  $second .= "$line\n";
}

print $second;

sub encode_entities {
  my $string = shift;
  $string =~ s/\x{2018}/'/g; # apostophre
  $string =~ s/\x{2019}/'/g; # apostophre
  $string =~ s/\x{201C}/"/g; # left double quote
  $string =~ s/\x{201D}/"/g; # right double quote
  $string =~ s/\x{2013}/-/g; # dash
  $string =~ s/‘//g;
  $string =~ s/’//g;
  $string =~ s/\s+$//;
  $string =~ s/^\s+//;
  $string =~ s/&/&amp;/g;
  $string =~ s/</&lt;/g;
  $string =~ s/>/&gt;/g;
  $string =~ s/'/&apos;/g;
  $string =~ s/"/&quot;/g;
  return $string;
}
