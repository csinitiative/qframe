#!/usr/bin/perl

use strict;
use Encode;
use XML::XPath;
use XML::XPath::XMLParser;
use Spreadsheet::ParseExcel;
use Spreadsheet::ParseExcel::FmtUnicode;
use Spreadsheet::ParseExcel::FmtDefault;
use Data::Dumper;

my $excel_file = shift || die "Need excel file as argument";
my $instance_name = shift || die "Need instance Name as second argument";

my $excel = Spreadsheet::ParseExcel::Workbook->Parse($excel_file);
if (!defined($excel)) {
  die "Unable to load $excel_file";
}
my $sig_xml;
open (DATA, "sig-4-0-questionnaire-definition.xml") || die $!;
while (<DATA>) {
  $sig_xml .= $_;
}
my $xp = XML::XPath->new( xml => $sig_xml );

my %guids;
my %levelQuestionNumbers;

foreach my $sheet (@{ $excel->{Worksheet} }) {
  $sheet->{MaxRow} ||= $sheet->{MinRow};
  my $name = $sheet->{Name};
  $name =~ s/\_/ /g; # For some reason there are sometimes underscores in the names instead of spaces
  print STDERR "Processing $name\n";
  
  foreach my $row ($sheet->{MinRow} .. $sheet->{MaxRow}) {
    if ($name eq 'Documentation') {
      my $guid = ( $sheet->{Cells}[$row][0]->{Val} );
      my $response = ( $sheet->{Cells}[$row][2]->{Val} );
      next if $guid !~ /^\d+$/ || !$response;
      $response = Encode::decode('UCS2', $response) if $sheet->{Cells}[$row][2]->{Code} eq 'ucs2';
      $guids{$guid}{response} = $response;
    }
    # These sheets don't use serial numbers (guids) unfortunately but rather reference
    # question numbers from the lettered sheets.  Store those instead and map
    # to guids in the following else block.  This assumes we are reading the level sheets
    # before the lettered sheets.
    elsif ($name =~ /^SIG Lite Lv1/ || $name =~ /^Lv2 Questions/) {
      my $questionNumber = ( $sheet->{Cells}[$row][0]->{Val} );
      my $response = ( $sheet->{Cells}[$row][3]->{Val} );
      next if $questionNumber !~ /^[a-z]+\.\d+$/i || !$response;
      my $addlInfo = ( $sheet->{Cells}[$row][4]->{Val} );
      $response = Encode::decode('UCS2', $response) if $sheet->{Cells}[$row][3]->{Code} eq 'ucs2';
      $addlInfo = Encode::decode('UCS2', $response) if $sheet->{Cells}[$row][4]->{Code} eq 'ucs2';
      $levelQuestionNumbers{$questionNumber}{response} = $response;
      $levelQuestionNumbers{$questionNumber}{addlInfo} = $addlInfo;
    }
    else {
      my $guid = ( $sheet->{Cells}[$row][0]->{Val} );
      next if $guid !~ /^\d+$/ || $guids{$guid};
      my $questionNumber = ( $sheet->{Cells}[$row][1]->{Val} );
      my $response = ( $sheet->{Cells}[$row][3]->{Val} );
      my $addlInfo = ( $sheet->{Cells}[$row][4]->{Val} );
      if ($response) { 
        $response = Encode::decode('UCS2', $response) if $sheet->{Cells}[$row][3]->{Code} eq 'ucs2';
        $addlInfo = Encode::decode('UCS2', $response) if $sheet->{Cells}[$row][4]->{Code} eq 'ucs2';
      }
      # no response but maybe had one from level 1 or level 2 sheets
      elsif ($levelQuestionNumbers{$questionNumber}) {
        $response = $levelQuestionNumbers{$questionNumber}{response};
        $addlInfo = $levelQuestionNumbers{$questionNumber}{addlInfo};
      }
      else {
        next;
      }
      # Fix typo in SIG 4.0 spreadsheet
      if ($guid == 2898) {
        $response =~ s/Publicy/Publicly/;
      }

      $guids{$guid}{response} = $response;
      $guids{$guid}{addlInfo} = $addlInfo;
    }
  }
}
    
my $response = '';
my @xml = split(/\n/, $sig_xml);
foreach my $line (@xml) {
  $line .= "\n";
  if ($line =~ /^(<csi:questionnaire .+)>/o) {
    print $1 . qq[ instanceName="] . encode_entities($instance_name) . qq[">\n];
  }
  elsif ($line =~ /<csi:questionGUID>(.+)<\/csi:questionGUID>/o) {
    my $v = $1;
    $response = '';
    if ($guids{$v}) {
      $response = "<csi:responses>
                     <csi:state>1</csi:state>
                     <csi:additionalInfo>" . encode_entities($guids{$v}{addlInfo}) . "</csi:additionalInfo>
                     <csi:response>
                       <csi:responseDate>2000-01-01T00:00:00</csi:responseDate>
                       <csi:responseText>" . encode_entities($guids{$v}{response}) . "</csi:responseText>
                     </csi:response>
                   </csi:responses>\n";
    }
    print $line;
  }
  elsif ($line =~ /<\/csi:question>/o) {
    print $response;
    print $line;
  }
  # Don't import date types since this will almost certainly break the xml schema rules.
  # And don't add responses to virtual question types.
  elsif ($line =~ /<csi:questionType>[DV]<\/csi:questionType>/o) {
    print $line;
    $response = '';
  }
  else {
    print $line;
  }
}

sub encode_entities {
  my $string = shift;
  $string =~ s/‘/'/g;
  $string =~ s/’/'/g;
  $string =~ s/\s+$//;
  $string =~ s/^\s+//;
  $string =~ s/&/&amp;/g;
  $string =~ s/</&lt;/g;
  $string =~ s/>/&gt;/g;
  $string =~ s/'/&apos;/g;
  $string =~ s/"/&quot;/g;
  return $string;
}
