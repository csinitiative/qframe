#!/usr/bin/perl

use strict;
use Spreadsheet::ParseExcel;
use Spreadsheet::ParseExcel::FmtUnicode;
use Spreadsheet::ParseExcel::FmtDefault;
use Data::Dumper;
use Encode;
binmode STDOUT, ":utf8";

my $DEBUG = 1;

my $excel = Spreadsheet::ParseExcel::Workbook->Parse('SIGv2.0.xls');

print qq[<?xml version="1.0" encoding="UTF-8"?>
<csi:questionnaire xmlns:csi="http://www.csinitiative.com/ns/csi-qframe" questionnaireName="CSI SIG" questionnaireVersion="2.0" revision="1" targetQFrameVersion="1.0">
  <csi:pages>
];

my $main_pages = 0;
my $questionSeqNumber = 1;
my $sectionSeqNumber = 1;
my $pageSeqNumber = 1;
my $questionGUID = 1;
my $sectionGUID = 1;
my $pageGUID = 1;
my @sub_cols;
my $added_question = 0;
foreach my $sheet (@{$excel->{Worksheet}}) {
  $sheet->{MaxRow} ||= $sheet->{MinRow};
  print qq[    <csi:page>
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
    
    if ($sheet->{Name} =~ /^Security Policy/) {
      $main_pages = 1;
    }

    if ($sheet->{Name} =~ /^Business Info/) {
      my $qText = ($sheet->{Cells}[$row][0]->{Val});
      $qText = Encode::decode('UCS2', $qText) if $sheet->{Cells}[$row][0]->{Code} eq 'ucs2';

      print qq[            <csi:question>\n];
      print qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
      print qq[              <csi:questionGUID>] . $questionGUID++ . qq[</csi:questionGUID>\n];
      print qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
      print qq[              <csi:questionType>T</csi:questionType>\n];
      print qq[            </csi:question>\n];
    }
    elsif ($sheet->{Name} =~ /^Documentation/) {
      my $qText = ($sheet->{Cells}[$row][0]->{Val});
      $qText = Encode::decode('UCS2', $qText) if $sheet->{Cells}[$row][0]->{Code} eq 'ucs2';

      if ($sheet->{Cells}[$row][0]->{Format}->{Indent} > 0) {
        print qq[            <csi:question>\n];
        print qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
        print qq[              <csi:questionGUID>] . $questionGUID++ . qq[</csi:questionGUID>\n];
        print qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
        print qq[              <csi:questionType>T</csi:questionType>\n];
        print qq[            </csi:question>\n];
      }
    }
    elsif ($main_pages) {
      my $qText = ($sheet->{Cells}[$row][2]->{Val});
      $qText = Encode::decode('UCS2', $qText) if $sheet->{Cells}[$row][2]->{Code} eq 'ucs2';

      next if $qText =~ /^FI Question/;

      if ($sheet->{Cells}[$row][3]->{Format}->{Rotate} > 0) {
        if ($in_question_group) {
          if (!$added_question ) {
            print STDERR "Did not add a question for questionGroup before question: $qText\n";
          }
          print qq[            </csi:questionGroup>\n];
          @sub_cols = ();
          $in_question_group = '';
          $added_question = 0;
        }

        print qq[            <csi:questionGroup>\n];
        print qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
        print qq[              <csi:questionGUID>] . $questionGUID++ . qq[</csi:questionGUID>\n];
        print qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
        $in_question_group = 1;

        @sub_cols = ();
        for (my $i = 3; $sheet->{Cells}[$row][$i]->{Format}->{Rotate} > 0 && $sheet->{Cells}[$row][$i]->{Val}; $i++) {
#          print "GREG: " . $sheet->{Cells}[$row][$i]->{Val} . "\n";
          push @sub_cols, $sheet->{Cells}[$row][$i]->{Val};
        }
      }
      elsif ($qText && $sheet->{Cells}[$row][3]->{Format}->{Fill}->[0] == 1 && $sheet->{Cells}[$row][3]->{Format}->{Fill}->[1] == 22 && $sheet->{Cells}[$row][3]->{Format}->{Fill}->[2] == 64) {
        if ($in_question_group) {
          if (!$added_question ) {
            print STDERR "Did not add a question for questionGroup before question: $qText\n";
          }
          print qq[            </csi:questionGroup>\n];
          @sub_cols = ();
          $in_question_group = '';
          $added_question = 0;
        }

        print qq[            <csi:questionGroup>\n];
        print qq[              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
        print qq[              <csi:questionGUID>] . $questionGUID++ . qq[</csi:questionGUID>\n];
        print qq[              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
        $in_question_group = 1;
      }
      elsif ($qText) {
        my $padding = '';
        if ($in_question_group) {
          if ($sheet->{Cells}[$row][2]->{Format}->{Indent} == 0 && !($sheet->{Cells}[$row][5]->{Format}->{Fill}->[0] == 1 && $sheet->{Cells}[$row][5]->{Format}->{Fill}->[1] == 41 && $sheet->{Cells}[$row][5]->{Format}->{Fill}->[2] == 64)) {
#            print "GREG: " . Dumper($sheet->{Cells}[$row][5]) . "\n";
            if (!$added_question ) {
              print STDERR "Did not add a question for questionGroup before question: $qText\n";
            }
            print qq[            </csi:questionGroup>\n];
            @sub_cols = ();
            $in_question_group = 0;
            $added_question = 0;
          }
          else {
            $padding = '  ';
          }
        }
        
        my @qTexts;
        if (@sub_cols > 0) {
          foreach my $col (@sub_cols) {
            my $temp = $qText;
            $temp =~ s/(\?)\s*$//;
            my $q = $1;
            push @qTexts, "$temp: $col?";
          }
        }
        else {
          push @qTexts, $qText;
        }

        foreach my $qText (@qTexts) {
          $added_question = 1;
          print qq[$padding            <csi:question>\n];
          print qq[$padding              <csi:qText>] . encode_entities($qText) . qq[</csi:qText>\n];
          print qq[$padding              <csi:questionGUID>] . $questionGUID++ . qq[</csi:questionGUID>\n];
          print qq[$padding              <csi:seqNumber>] . $questionSeqNumber++ . qq[</csi:seqNumber>\n];
          print qq[$padding              <csi:questionType>S</csi:questionType>\n];
          print qq[$padding              <csi:questionPrompt>\n];
          print qq[$padding                <csi:promptText>Yes</csi:promptText>\n];
          print qq[$padding              </csi:questionPrompt>\n];
          print qq[$padding              <csi:questionPrompt>\n];
          print qq[$padding                <csi:promptText>No</csi:promptText>\n];
          print qq[$padding              </csi:questionPrompt>\n];
          print qq[$padding              <csi:questionPrompt>\n];
          print qq[$padding                <csi:promptText>N/A</csi:promptText>\n];
          print qq[$padding                <csi:requireAdditionalInfo>1</csi:requireAdditionalInfo>\n];
          print qq[$padding              </csi:questionPrompt>\n];
          print qq[$padding            </csi:question>\n];
        }
      }

    }
  }
  if ($in_question_group) {
    if (!$added_question ) {
      print STDERR "Did not add a question for questionGroup before question: END\n";
    }
    print qq[            </csi:questionGroup>\n];
    @sub_cols = ();
    $added_question = 0;
  }
  print qq[          </csi:questions>\n];
  print qq[        </csi:section>\n];
  print qq[      </csi:sections>\n];
  print qq[    </csi:page>\n];
}

print qq[  </csi:pages>\n];
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
