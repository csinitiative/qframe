#!/usr/bin/perl

use strict;
use Spreadsheet::ParseExcel;
use Spreadsheet::ParseExcel::FmtUnicode;
use Spreadsheet::ParseExcel::FmtDefault;
use Data::Dumper;
binmode STDOUT, ":utf8";

my $DEBUG = 1;

my $excel = Spreadsheet::ParseExcel::Workbook->Parse('SIGv3 Final_v2_comp.xls');

print qq[<?xml version="1.0" encoding="UTF-8"?>
<csi:questionnaire xmlns:csi="http://www.csinitiative.com/ns/csi-regq" questionnaireName="CSI SIG" questionnaireVersion="3.00">
  <csi:tabs>
];

foreach my $sheet (@{$excel->{Worksheet}}) {
  $sheet->{MaxRow} ||= $sheet->{MinRow};
  print qq[    <csi:tab>
      <csi:tabHeader>] . encode_entities($sheet->{Name}) . qq[</csi:tabHeader>
      <csi:description></csi:description>
      <csi:headerText></csi:headerText>
      <csi:footerText></csi:footerText>
      <csi:sections>\n];
  my $in_section_flag = 0;
  my $in_question_group;
  foreach my $row ($sheet->{MinRow} .. $sheet->{MaxRow}) {
    
    # A thru M tabs
    if ($sheet->{Name} =~ /^\w\./) {
      my $referenceText = ($sheet->{Cells}[$row][2]->{Val});
      my $questionNumber = ($sheet->{Cells}[$row][3]->{Val});
      my $qText = ($sheet->{Cells}[$row][4]->{Val});
      $qText = Encode::decode('UCS2', $qText) if $sheet->{Cells}[$row][4]->{Code} eq 'ucs2';

      if ($questionNumber =~ /^\w\.\d+$/) {
        if ($in_question_group) {
          print qq[            </csi:questionGroup>\n];
          $in_question_group = '';
        }
        
        if ($in_section_flag) {
          print qq[          </csi:questions>\n];
          print qq[        </csi:section>\n];
        }

        print qq[        <csi:section>\n];
        print qq[          <csi:sectionHeader>] . ($2 || 'UNKNOWN') . qq[</csi:sectionHeader>\n];
        print qq[          <csi:questions>\n];
        print qq[            <csi:question>\n];
        print qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
        print qq[              <csi:questionGUID></csi:questionGUID>\n];
        print qq[              <csi:questionNumber>] . encode_entities($questionNumber) . qq[</csi:questionNumber>\n];
        print qq[              <csi:referenceTexts>\n];
        foreach my $rt (split(/[\r\n]+/, $referenceText)) {
          print qq[                <csi:referenceText>] . encode_entities($rt) . qq[</csi:referenceText>\n];
        }
        print qq[              </csi:referenceTexts>\n];
        print qq[              <csi:questionType>S</csi:questionType>\n];
        print qq[              <csi:questionPrompt>Y</csi:questionPrompt>\n];
        print qq[              <csi:questionPrompt>N</csi:questionPrompt>\n];
        print qq[            </csi:question>\n];
        $in_section_flag = 1;
      }
      elsif ($in_section_flag && ($questionNumber =~ /^\w\.\d+\.\d+/)) {
        my $padding = '';
        if ($in_question_group) {
          if ($in_question_group eq 'first') {
            ($in_question_group = $questionNumber) =~ s/^(.+)\..+$/$1/;
            $padding = '  ';
          }
          elsif ($questionNumber =~ /$in_question_group\./) {
            $padding = '  ';
          }
          else {
            print qq[              </csi:questionGroup>\n];
            $in_question_group = '';
          }
        }

        print qq[$padding            <csi:question>\n];
        print qq[$padding              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
        print qq[$padding              <csi:questionGUID></csi:questionGUID>\n];
        print qq[$padding              <csi:questionNumber>] . encode_entities($questionNumber) . qq[</csi:questionNumber>\n];
        print qq[$padding              <csi:referenceTexts>\n];
        foreach my $rt (split(/[\r\n]+/, $referenceText)) {
          print qq[$padding                <csi:referenceText>] . encode_entities($rt) . qq[</csi:referenceText>\n];
        }
        print qq[$padding              </csi:referenceTexts>\n];
        print qq[$padding              <csi:questionType>S</csi:questionType>\n];
        print qq[$padding              <csi:questionPrompt>Y</csi:questionPrompt>\n];
        print qq[$padding              <csi:questionPrompt>N</csi:questionPrompt>\n];
        print qq[$padding            </csi:question>\n];
      }
      elsif ($qText && $in_section_flag) {
        if ($in_question_group) {
          print qq[            </csi:questionGroup>\n];
        }
        print qq[            <csi:questionGroup>\n];
        print qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
        print qq[              <csi:questionGUID></csi:questionGUID>\n];
        print qq[              <csi:questionNumber>] . encode_entities($questionNumber) . qq[</csi:questionNumber>\n];
        print qq[              <csi:referenceTexts>\n];
        foreach my $rt (split(/[\r\n]+/, $referenceText)) {
          print qq[                <csi:referenceText>] . encode_entities($rt) . qq[</csi:referenceText>\n];
        }
        print qq[              </csi:referenceTexts>\n];
        $in_question_group = 'first';
      }
      else {
#        print STDERR "Can't parse: $questionNumber\n";
      }
    }
  }
  if ($in_question_group) {
    print qq[            </csi:questionGroup>\n];
  }
  if ($in_section_flag) {
    print qq[          </csi:questions>\n];
    print qq[        </csi:section>\n];
  }
  print qq[      </csi:sections>\n];
  print qq[    </csi:tab>\n];
}

print qq[  </csi:tabs>\n];
print qq[</csi:questionnaire>];

sub encode_entities {
  my $string = shift;
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
