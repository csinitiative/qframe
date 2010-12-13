#!/usr/bin/perl

use strict;
use Spreadsheet::ParseExcel;
use Spreadsheet::ParseExcel::FmtUnicode;
use Spreadsheet::ParseExcel::FmtDefault;
use Data::Dumper;
use Encode;
binmode STDOUT, ":utf8";

my $excel = Spreadsheet::ParseExcel::Workbook->Parse('SIG_SIGv6_unprotected.xls');

my $xml = qq[<?xml version="1.0" encoding="UTF-8"?>
<csi:questionnaire xmlns:csi="http://www.csinitiative.com/ns/csi-qframe" questionnaireName="CSI SIG" questionnaireVersion="6.0" revision="1" targetQFrameVersion="1.0">
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
my %questions;

foreach my $sheet (@{$excel->{Worksheet}}) {
  $sheet->{MaxRow} ||= $sheet->{MinRow};
  $sheet->{Name} =~ s/\_/ /g;

  # Skip these
  next if ($sheet->{Name} =~ /Version History|Formula Notes|Full/);

  $xml .= qq[    <csi:page>
      <csi:pageHeader>] . encode_entities($sheet->{Name}) . qq[</csi:pageHeader>
      <csi:pageGUID>] . $pageGUID++ . qq[</csi:pageGUID>
      <csi:seqNumber>] . $pageSeqNumber++ . qq[</csi:seqNumber>\n];
  if ($sheet->{Name} =~ /^Copyright/) {
    $xml .= qq[      <csi:headerText>];
    foreach my $row ($sheet->{MinRow} .. $sheet->{MaxRow}) {
      next unless $row >= 2;
      my $line = ($sheet->{Cells}[$row][1]->{Val});
      $line = Encode::decode('UCS2', $line) if $sheet->{Cells}[$row][1]->{Code} eq 'ucs2';
      $xml .= qq[$line\n];
    }
    $xml .= qq[      </csi:headerText>\n];
  }
  elsif ($sheet->{Name} =~ /^Cover Page/) {
    $xml .= qq[      <csi:headerText>];
    foreach my $row ($sheet->{MinRow} .. $sheet->{MaxRow}) {
      next unless $row >= 10;
      my $line = ($sheet->{Cells}[$row][0]->{Val});
      $line = Encode::decode('UCS2', $line) if $sheet->{Cells}[$row][0]->{Code} eq 'ucs2';
      $xml .= qq[$line\n];
    }
    $xml .= qq[      </csi:headerText>\n];
  }
  elsif ($sheet->{Name} =~ /^Terms of Use/) {
    $xml .= qq[      <csi:headerText>];
    foreach my $row ($sheet->{MinRow} .. $sheet->{MaxRow}) {
      my $line = ($sheet->{Cells}[$row][0]->{Val});
      $line = Encode::decode('UCS2', $line) if $sheet->{Cells}[$row][0]->{Code} eq 'ucs2';
      $xml .= qq[$line\n];
    }
    $xml .= qq[      </csi:headerText>\n];
  }
  elsif ($sheet->{Name} =~ /^Glossary/) {
    $xml .= qq[      <csi:headerText>];
    foreach my $row ($sheet->{MinRow} .. $sheet->{MaxRow}) {
      next unless $row >= 2;
      my $cell1 = ($sheet->{Cells}[$row][0]->{Val});
      my $cell2 = ($sheet->{Cells}[$row][1]->{Val});
      $cell1 = Encode::decode('UCS2', $cell1) if $sheet->{Cells}[$row][0]->{Code} eq 'ucs2';
      $cell2 = Encode::decode('UCS2', $cell2) if $sheet->{Cells}[$row][1]->{Code} eq 'ucs2';
      $xml .= qq[**$cell1:** $cell2\n\n];
    }
    $xml .= qq[      </csi:headerText>\n];
  }
  $xml .= qq[      <csi:sections>
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
      $qText =~ s/Minimum password length at least eight characters:/Minimum password length at least eight characters?/;
      next if $qText =~ /Question\/Request/;

      if ($questionGUID =~ /\d/) {
        if ($qText =~ /Date of Response/) {
          $xml .= qq[            <csi:question>\n];
          $xml .= qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
          $xml .= qq[              <csi:questionGUID>] . $questionGUID . qq[</csi:questionGUID>\n];
          $xml .= qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
          $xml .= qq[              <csi:questionType>D</csi:questionType>\n];
          $xml .= qq[            </csi:question>\n];
        }
        elsif ($qText =~ /Publicly or privately held company/) {
          $xml .= qq[            <csi:question>\n];
          $xml .= qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
          $xml .= qq[              <csi:questionGUID>] . $questionGUID . qq[</csi:questionGUID>\n];
          $xml .= qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
          $xml .= qq[              <csi:questionType>S</csi:questionType>\n];
          $xml .= qq[              <csi:questionPrompt>\n];
          $xml .= qq[                <csi:promptText>Publicly held</csi:promptText>\n];
          $xml .= qq[              </csi:questionPrompt>\n];
          $xml .= qq[              <csi:questionPrompt>\n];
          $xml .= qq[                <csi:promptText>Privately held</csi:promptText>\n];
          $xml .= qq[              </csi:questionPrompt>\n];
          $xml .= qq[            </csi:question>\n];
        }
        elsif ($qText =~ /Are there any material claims or judgments against the company/) {
          $xml .= qq[            <csi:question>\n];
          $xml .= qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
          $xml .= qq[              <csi:questionGUID>] . $questionGUID . qq[</csi:questionGUID>\n];
          $xml .= qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
          $xml .= qq[              <csi:questionType>S</csi:questionType>\n];
          $xml .= qq[              <csi:questionPrompt>\n];
          $xml .= qq[                <csi:promptText>Yes</csi:promptText>\n];
          $xml .= qq[              </csi:questionPrompt>\n];
          $xml .= qq[              <csi:questionPrompt>\n];
          $xml .= qq[                <csi:promptText>No</csi:promptText>\n];
          $xml .= qq[              </csi:questionPrompt>\n];
          $xml .= qq[            </csi:question>\n];
        }
        elsif ($qText =~ /Any additional locations where Scoped Systems and Data is stored/) {
          $xml .= qq[            <csi:question>\n];
          $xml .= qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
          $xml .= qq[              <csi:questionGUID>] . $questionGUID . qq[</csi:questionGUID>\n];
          $xml .= qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
          $xml .= qq[              <csi:questionType>S</csi:questionType>\n];
          $xml .= qq[              <csi:questionPrompt>\n];
          $xml .= qq[                <csi:promptText>Yes</csi:promptText>\n];
          $xml .= qq[              </csi:questionPrompt>\n];
          $xml .= qq[              <csi:questionPrompt>\n];
          $xml .= qq[                <csi:promptText>No</csi:promptText>\n];
          $xml .= qq[              </csi:questionPrompt>\n];
          $xml .= qq[            </csi:question>\n];
        }
        elsif ($qText =~ /Type of service provided:/) {
          $xml .= qq[            <csi:questionGroup>\n];
          $xml .= qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
          $xml .= qq[              <csi:questionGUID>] . $questionGUID . qq[</csi:questionGUID>\n];
          $xml .= qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
        }
        elsif ($qText =~ /- Shared \(provided to multiple clients\)/) {
          $xml .= qq[              <csi:question>\n];
          $xml .= qq[                <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
          $xml .= qq[                <csi:questionGUID>] . $questionGUID . qq[</csi:questionGUID>\n];
          $xml .= qq[                <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
          $xml .= qq[                <csi:questionType>S</csi:questionType>\n];
          $xml .= qq[                <csi:questionPrompt>\n];
          $xml .= qq[                  <csi:promptText>Yes</csi:promptText>\n];
          $xml .= qq[                </csi:questionPrompt>\n];
          $xml .= qq[                <csi:questionPrompt>\n];
          $xml .= qq[                  <csi:promptText>No</csi:promptText>\n];
          $xml .= qq[                </csi:questionPrompt>\n];
          $xml .= qq[              </csi:question>\n];
        }
        elsif ($qText =~ /- Dedicated \(provided to one client\)/) {
          $xml .= qq[              <csi:question>\n];
          $xml .= qq[                <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
          $xml .= qq[                <csi:questionGUID>] . $questionGUID . qq[</csi:questionGUID>\n];
          $xml .= qq[                <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
          $xml .= qq[                <csi:questionType>S</csi:questionType>\n];
          $xml .= qq[                <csi:questionPrompt>\n];
          $xml .= qq[                  <csi:promptText>Yes</csi:promptText>\n];
          $xml .= qq[                </csi:questionPrompt>\n];
          $xml .= qq[                <csi:questionPrompt>\n];
          $xml .= qq[                  <csi:promptText>No</csi:promptText>\n];
          $xml .= qq[                </csi:questionPrompt>\n];
          $xml .= qq[              </csi:question>\n];
        }
        elsif ($qText =~ /- Other \(explain\)/) {
          $xml .= qq[              <csi:question>\n];
          $xml .= qq[                <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
          $xml .= qq[                <csi:questionGUID>] . $questionGUID . qq[</csi:questionGUID>\n];
          $xml .= qq[                <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
          $xml .= qq[                <csi:questionType>T</csi:questionType>\n];
          $xml .= qq[              </csi:question>\n];
          $xml .= qq[            </csi:questionGroup>\n];
        }
        else { # Otherwise, assume it is a text field
          $xml .= qq[            <csi:question>\n];
          $xml .= qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
          $xml .= qq[              <csi:questionGUID>] . $questionGUID . qq[</csi:questionGUID>\n];
          $xml .= qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
          $xml .= qq[              <csi:questionType>T</csi:questionType>\n];
          $xml .= qq[            </csi:question>\n];
        }
      }
    }
    elsif ($sheet->{Name} =~ /^Documentation/) {
      my $questionGUID = ($sheet->{Cells}[$row][0]->{Val});
      my $qText = ($sheet->{Cells}[$row][1]->{Val});
      $qText = Encode::decode('UCS2', $qText) if $sheet->{Cells}[$row][1]->{Code} eq 'ucs2';
      $qText =~ s/Minimum password length at least eight characters:/Minimum password length at least eight characters?/;

      next if $qText =~ /Document Request/;


      if ($questionGUID =~ /\d/) {
        $xml .= qq[            <csi:question>\n];
        $xml .= qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
        $xml .= qq[              <csi:questionGUID>] . $questionGUID . qq[</csi:questionGUID>\n];
        $xml .= qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
        $xml .= qq[              <csi:questionType>T</csi:questionType>\n];
        $xml .= qq[            </csi:question>\n];
      }
    }
    elsif ($sheet->{Name} =~ /Lite/) {
      my $questionGUID = ($sheet->{Cells}[$row][0]->{Val});
      my $questionNumber = ($sheet->{Cells}[$row][1]->{Val});
      my $qText = ($sheet->{Cells}[$row][2]->{Val});
      $qText = Encode::decode('UCS2', $qText) if $sheet->{Cells}[$row][2]->{Code} eq 'ucs2';
      $qText =~ s/Minimum password length at least eight characters:/Minimum password length at least eight characters?/;

      next unless $questionGUID =~ /\d\s*$/;
      next unless $questionNumber =~ /\d\s*$/;

      if (defined($virtual{$questionGUID})) {
        $xml .= qq[            <csi:question>\n];
        $xml .= qq[              <csi:questionGUID>$questionGUID</csi:questionGUID>\n];
        $xml .= qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
        $xml .= qq[              <csi:questionNumber>] . $questionNumber . qq[</csi:questionNumber>\n];
        $xml .= qq[              <csi:questionType>V</csi:questionType>\n];
        $xml .= qq[            </csi:question>\n];
      }
      else {
        $xml .= qq[            <csi:question>\n];
        $xml .= qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
        $xml .= qq[              <csi:questionGUID>$questionGUID</csi:questionGUID>\n];
        $xml .= qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
        $xml .= qq[              <csi:questionNumber>] . $questionNumber . qq[</csi:questionNumber>\n];
        $xml .= qq[              <csi:questionType>S</csi:questionType>\n];
        $xml .= qq[              <csi:questionPrompt>\n];
        $xml .= qq[                <csi:promptText>Yes</csi:promptText>\n];
        $xml .= qq[              </csi:questionPrompt>\n];
        $xml .= qq[              <csi:questionPrompt>\n];
        $xml .= qq[                <csi:promptText>No</csi:promptText>\n];
        $xml .= qq[              </csi:questionPrompt>\n];
        $xml .= qq[              <csi:questionPrompt>\n];
        $xml .= qq[                <csi:promptText>N/A</csi:promptText>\n];
        $xml .= qq[                <csi:requireAdditionalInfo>1</csi:requireAdditionalInfo>\n];
        $xml .= qq[              </csi:questionPrompt>\n];
        $xml .= qq[            </csi:question>\n];
        $virtual{$questionGUID}{found} = 1;
        print STDERR "Found virtual question: $questionGUID\n";
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
      $qText =~ s/Minimum password length at least eight characters:/Minimum password length at least eight characters?/;
      $aup = Encode::decode('UCS2', $aup) if $sheet->{Cells}[$row][5]->{Code} eq 'ucs2';
      $iso = Encode::decode('UCS2', $iso) if $sheet->{Cells}[$row][6]->{Code} eq 'ucs2';
      $iso_name = Encode::decode('UCS2', $iso_name) if $sheet->{Cells}[$row][7]->{Code} eq 'ucs2';
      next unless $qText && $questionGUID =~ /\d/;

      $questions{$questionNumber} = $questionGUID;

      # SIG 6 incorrectly has a serial by the table headings
      next unless $row > 3;
      next if $qText =~ /Question\/Request/;

      my $queueDepth = ($sheet->{Cells}[$row][8]->{Val});
      if (!$queueDepth) {
        print STDERR "Could not get a queue depth for question with number: $questionNumber on page " . $sheet->{Name} . "\n";
      }
   
      foreach my $q (keys %lastQueueDepths) {
        if ($lastQueueDepths{$q} >= $queueDepth) {
          print STDERR "Deleting queue depth $q\n";
          delete $lastQueueDepths{$q};
        }
      }
      foreach my $q (keys %lastQueueDepths) {
        print STDERR "Adding disable target GUID $questionGUID that has questionNumber $questionNumber to $q\n";
        push @{$realDisable{$q}{disable}}, $questionGUID;
      }

      $questionNumber =~ /^(.+)\..+$/;
      my $parentQuestionNumber = $1;

      print STDERR "Trying $parentQuestionNumber for $questionNumber\n";
      if ($questions{$parentQuestionNumber}) {
        push @{$realDisable{$questions{$parentQuestionNumber}}{disable}}, $questionGUID;
        print STDERR "...succeeded\n";
        print STDERR "Adding disable target GUID $questionGUID that has questionNumber $questionNumber to $parentQuestionNumber with questionGUID $questions{$parentQuestionNumber}\n";
      }
      else {
        $questionNumber =~ /^(.+)\..+\..+$/;
        my $parentQuestionNumber = $1;
        print STDERR "Trying $parentQuestionNumber for $questionNumber\n";
        if ($questions{$parentQuestionNumber}) {
          push @{$realDisable{$questions{$parentQuestionNumber}}{disable}}, $questionGUID;
          print STDERR "...succeeded\n";
          print STDERR "Adding disable target GUID $questionGUID that has questionNumber $questionNumber to $parentQuestionNumber with questionGUID $questions{$parentQuestionNumber}\n";
        }
        else {
          print STDERR "...failed\n";
        }
      }
       
      if ($qText =~ /:\s*$/ && $qText !~ /\?/) {
        if (defined($virtual{$questionGUID})) {
          die ('There should not be a questionGroup for a virtual question guid:' . $questionGUID);
        }
        if ($in_question_group) {
          if (!$added_question ) {
            print STDERR "Did not add a question for questionGroup before question: $qText (question guid: $questionGUID)\n";
          }
          $xml .= qq[            </csi:questionGroup>\n];
          $in_question_group = 0;
          $added_question = 0;
        }

        $xml .= qq[            <csi:questionGroup>\n];
        $xml .= qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
        $xml .= qq[              <csi:questionGUID>] . $questionGUID . qq[</csi:questionGUID>\n];
        $xml .= qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
        $xml .= qq[              <csi:groupQuestionNumber>] . $questionNumber . qq[</csi:groupQuestionNumber>\n];
        if ($aup || ($iso && $iso ne 'N/A')) {
          $xml .= qq[              <csi:groupQuestionReferences>\n];
          if ($aup) {
            $xml .= qq[                <csi:reference>\n];
            $xml .= qq[                  <csi:shortName>AUP</csi:shortName>\n];
            $xml .= qq[                  <csi:referenceName>Agreed Upon Procedures</csi:referenceName>\n];
            $xml .= qq[                  <csi:item>] . encode_entities($aup) . qq[</csi:item>\n];
            $xml .= qq[                </csi:reference>\n];
          }
          if ($iso && $iso ne 'N/A') {
            $xml .= qq[                <csi:reference>\n];
            $xml .= qq[                  <csi:shortName>ISO</csi:shortName>\n];
            $xml .= qq[                  <csi:referenceName>ISO 17799:2005</csi:referenceName>\n];
            $xml .= qq[                  <csi:item>] . encode_entities("$iso $iso_name") . qq[</csi:item>\n];
            $xml .= qq[                </csi:reference>\n];
          }
          $xml .= qq[              </csi:groupQuestionReferences>\n];
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
              print STDERR "Did not add a question for questionGroup before question: $qText (question guid: $questionGUID)\n";
            }
            $xml .= qq[            </csi:questionGroup>\n];
            $in_question_group = 0;
          }
        }
        
        $added_question = 1;
        if (defined($virtual{$questionGUID})) {
          $real{$questionGUID}{guid} = $questionGUID;

          $lastQueueDepths{$questionGUID} = $queueDepth;
          $virtual{$questionGUID}{guid} = $questionGUID;
          $xml .= qq[$padding            <csi:question>\n];
          $xml .= qq[$padding              <csi:questionGUID>] . $questionGUID . qq[</csi:questionGUID>\n];
          $xml .= qq[$padding              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
          $xml .= qq[$padding              <csi:questionNumber>] . $questionNumber . qq[</csi:questionNumber>\n];
          $xml .= qq[$padding              <csi:questionType>V</csi:questionType>\n];
          $xml .= qq[$padding            </csi:question>\n];
        }
        else {
          $xml .= qq[$padding            <csi:question>\n];
          $xml .= qq[$padding              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
          $xml .= qq[$padding              <csi:questionGUID>] . $questionGUID . qq[</csi:questionGUID>\n];
          $xml .= qq[$padding              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
          $xml .= qq[$padding              <csi:questionNumber>] . $questionNumber . qq[</csi:questionNumber>\n];
          if ($aup || ($iso && $iso ne 'N/A')) {
            $xml .= qq[$padding              <csi:questionReferences>\n];
            if ($aup) {
              $xml .= qq[$padding                <csi:reference>\n];
              $xml .= qq[$padding                  <csi:shortName>AUP</csi:shortName>\n];
              $xml .= qq[$padding                  <csi:referenceName>Agreed Upon Procedures</csi:referenceName>\n];
              $xml .= qq[$padding                  <csi:item>] . encode_entities($aup) . qq[</csi:item>\n];
              $xml .= qq[$padding                </csi:reference>\n];
            }
            if ($iso && $iso ne 'N/A') {
              $xml .= qq[$padding                <csi:reference>\n];
              $xml .= qq[$padding                  <csi:shortName>ISO</csi:shortName>\n];
              $xml .= qq[$padding                  <csi:referenceName>ISO 17799:2005</csi:referenceName>\n];
              $xml .= qq[$padding                  <csi:item>] . encode_entities("$iso $iso_name") . qq[</csi:item>\n];
              $xml .= qq[$padding                </csi:reference>\n];
            }
            $xml .= qq[$padding              </csi:questionReferences>\n];
          }
          $xml .= qq[$padding              <csi:questionType>S</csi:questionType>\n];
          $xml .= qq[$padding              <csi:questionPrompt>\n];
          $xml .= qq[$padding                <csi:promptText>Yes</csi:promptText>\n];
          $xml .= qq[$padding              </csi:questionPrompt>\n];
          $xml .= qq[$padding              <csi:questionPrompt>\n];
          $xml .= qq[$padding                <csi:promptText>No</csi:promptText>\n];
          $xml .= qq[$padding              </csi:questionPrompt>\n];
          $xml .= qq[$padding              <csi:questionPrompt>\n];
          $xml .= qq[$padding                <csi:promptText>N/A</csi:promptText>\n];
          $xml .= qq[$padding                <csi:requireAdditionalInfo>1</csi:requireAdditionalInfo>\n];
          $xml .= qq[$padding              </csi:questionPrompt>\n];
          $xml .= qq[$padding            </csi:question>\n];
        }
      }
    }
  }
  if ($in_question_group) {
    if (!$added_question ) {
      print STDERR "Did not add a question for questionGroup before question: END\n";
    }
    $xml .= qq[            </csi:questionGroup>\n];
    $added_question = 0;
  }
  $xml .= qq[          </csi:questions>\n];
  $xml .= qq[        </csi:section>\n];
  $xml .= qq[      </csi:sections>\n];
  $xml .= qq[    </csi:page>\n];
}

$xml .= qq[  </csi:pages>\n];
$xml .= qq[</csi:questionnaire>];

my $second = '';
my @lines = split(/\n/, $xml);
for (my $i = 0; $i < @lines; $i++) {
  my $line = $lines[$i];
  if ($line =~ />(.+)<\/csi:questionGUID/) {
    my $guid = $1;
    my %count;
    my @dedup = grep { !$count{$_}++ } @{$realDisable{$guid}{disable}};
    if (defined($realDisable{$guid}{disable})) {
      for (my $y = 1; $y < 30; $y++) {
        last if $lines[$i+$y] =~ /<csi:question>/;
        if ($lines[$i+$y] =~ /^(.+)<csi:promptText.+No/) {
          my $padding = $1;
          foreach my $g (sort {$a <=> $b} @dedup) {
            next if $g eq $guid;
            $lines[$i+$y] .= "\n$padding<csi:disableQuestion>$g</csi:disableQuestion>";
          }
          last;
        }
        last if $lines[$i+$y] =~ /<\/csi:question>/;
      }
    }
    else {
      print STDERR "No disable targets found\n";
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
