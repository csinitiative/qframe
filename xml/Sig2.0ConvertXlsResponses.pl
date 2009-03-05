#!/usr/bin/perl

use strict;
use Encode;
use XML::XPath;
use XML::XPath::XMLParser;
use Spreadsheet::ParseExcel;
use Spreadsheet::ParseExcel::FmtUnicode;
use Spreadsheet::ParseExcel::FmtDefault;
use Data::Dumper;
binmode STDOUT, ":utf8";

my $DEBUG = 1;

my $excel_file = shift || die "Need excel file as argument";
my $instance_name = shift || die "Need instance Name as second argument";

my $excel = Spreadsheet::ParseExcel::Workbook->Parse($excel_file);
if (!defined($excel)) {
  die "Unable to load $excel_file";
}
my $sig_xml;
open (DATA, "sig-2-0-questionnaire-definition.xml") || die $!;
while (<DATA>) {
  $sig_xml .= $_;
}
my $xp = XML::XPath->new( xml => $sig_xml );
my %seen;

MAIN:
foreach my $sheet (@{ $excel->{Worksheet} }) {
  $sheet->{MaxRow} ||= $sheet->{MinRow};
  my $name = $sheet->{Name};

  my $pageNodes = $xp->findnodes("/csi:questionnaire/csi:pages/csi:page[csi:pageHeader='$name']");
  my @sub_cols = ();
  
  foreach my $row ($sheet->{MinRow} .. $sheet->{MaxRow}) {
    my ($qText, $response, $addlInfo);
    if ($name eq 'Business Info' || $name eq 'Documentation') {
      $qText          = ( $sheet->{Cells}[$row][0]->{Val} );
      $response       = ( $sheet->{Cells}[$row][1]->{Val} );
      $qText = Encode::decode('UCS2', $qText) if $sheet->{Cells}[$row][0]->{Code} eq 'ucs2';
      $response = Encode::decode('UCS2', $response) if $sheet->{Cells}[$row][1]->{Code} eq 'ucs2';
    }
    else {
      $qText          = ( $sheet->{Cells}[$row][2]->{Val} );
      $response       = ( $sheet->{Cells}[$row][3]->{Val} );
      $addlInfo       = ( $sheet->{Cells}[$row][4]->{Val} );
      $qText = Encode::decode('UCS2', $qText) if $sheet->{Cells}[$row][2]->{Code} eq 'ucs2';
      $response = Encode::decode('UCS2', $response) if $sheet->{Cells}[$row][3]->{Code} eq 'ucs2';
      $addlInfo = Encode::decode('UCS2', $addlInfo) if $sheet->{Cells}[$row][4]->{Code} eq 'ucs2';
    }
    
    $qText =~ s/\x{2018}/'/g; # apostophre
    $qText =~ s/\x{2019}/'/g; # apostophre
    $qText =~ s/\x{201C}/"/g; # left double quote
    $qText =~ s/\x{201D}/"/g; # right double quote

    next unless $qText;
    $qText =~ s/\s+$//;
    $qText =~ s/^\s+//;
    $qText =~ s/^\n+//g;
    $qText =~ s/^\r+//g;

    if ($sheet->{Cells}[$row][5]->{Format}->{Fill}->[1] <= 1) {
      @sub_cols = ();
    }
    if ($sheet->{Cells}[$row][3]->{Format}->{Rotate} > 0) {
      for (my $i = 3; $sheet->{Cells}[$row][$i]->{Format}->{Rotate} > 0 && $sheet->{Cells}[$row][$i]->{Val}; $i++) {
        push @sub_cols, $sheet->{Cells}[$row][$i]->{Val};
      }
    }
    
    my @qTexts = ();
    push @qTexts, $qText;
    if (@sub_cols > 0) {
      $qText =~ s/\?\s*$//;
      foreach my $col (@sub_cols) {
        push @qTexts, $qText . ': ' . $col . '?';
      }
    }
    
    OUTER:
    foreach my $qText (@qTexts) {
      my $found = 0;
      foreach my $child ( $pageNodes->get_nodelist() ) {
        if ($child->getName() eq 'csi:page') {
          my $childNodes = $child->getChildNodes();
          foreach my $child (@$childNodes) {
            if ( $child->getName() eq 'csi:sections' ) {
              my $childNodes = $child->getChildNodes();
              foreach my $child (@$childNodes) {
                if ( $child->getName() eq 'csi:section' ) {
                  my $childNodes = $child->getChildNodes();
                  foreach my $child (@$childNodes) {
                    if ( $child->getName() eq 'csi:questions' ) {
                      my $childNodes = $child->getChildNodes();
                      foreach my $child (@$childNodes) {
                        if ( $child->getName() eq 'csi:question' ) {
                          my $childNodes = $child->getChildNodes();
                          my $hit = 0;
                          foreach my $child (@$childNodes) {
                            if ($child->getName() eq 'csi:qText' && !$found) {
                              if ($child->string_value() eq encode_entities($qText) || $child->string_value() eq $qText) {
                                $hit = 1;
                              }
                            }
                            elsif ($child->getName() eq 'csi:questionGUID' && $hit) {
                              my $v = $child->string_value();
                              if (!$seen{$v}) {
                                $found = 1;
                                print STDERR "FOUND: $qText\n" if $DEBUG;
                                if (length($response) > 0 && $response =~ /\w/o) {
                                  $seen{$v}{response} = $response;
                                  $seen{$v}{addlInfo} = $addlInfo;
                                }
                                $hit = undef;
                              }
                            }
                          }
                        }
                        elsif ($child->getName() eq 'csi:questionGroup') {
                          my $childNodes = $child->getChildNodes();
                          foreach my $child (@$childNodes) {
                            if ($child->getName() eq 'csi:qText') {
                              if ($child->string_value() eq encode_entities($qText) || $child->string_value() eq $qText) {
                                print STDERR "FOUND QG: $qText\n" if $DEBUG;
                                $found = 1;
                              }
                            }
                            elsif ($child->getName() eq 'csi:question') {
                              my $childNodes = $child->getChildNodes();
                              my $hit = 0;
                              foreach my $child (@$childNodes) {
                                if ($child->getName() eq 'csi:qText' && !$found) {
                                  if ($child->string_value() eq encode_entities($qText) || $child->string_value() eq $qText) {
                                    $hit = 1;
                                  }
                                }
                                elsif ($child->getName() eq 'csi:questionGUID' && $hit) {
                                  my $v = $child->string_value();
                                  if (!$seen{$v}) {
                                    $found = 1;
                                    print STDERR "FOUND: $qText\n" if $DEBUG;
                                    if (length($response) > 0 && $response =~ /\w/o) {
                                      $seen{$v}{response} = $response;
                                      $seen{$v}{addlInfo} = $addlInfo;
                                    }
                                    $hit = undef;
                                  }
                                }
                              }
                            }
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
      print STDERR "NOT FOUND: " . encode_entities($qText) . "\n" if (!$found && $DEBUG);
    }
  }
}


my $response;
my @xml = split(/\n/, $sig_xml);
foreach my $line (@xml) {
  $line .= "\n";
  if ($line =~ /^(<csi:questionnaire .+)>/o) {
    print $1 . qq[ instanceName="] . encode_entities($instance_name) . qq[">\n];
  }
  elsif ($line =~ /<csi:questionGUID>(.+)<\/csi:questionGUID>/o) {
    my $v = $1;
    $response = '';
    if ($seen{$v}) {
      $response = "<csi:responses>
                     <csi:state>1</csi:state>
                     <csi:additionalInfo>" . encode_entities($seen{$v}{addlInfo}) . "</csi:additionalInfo>
                     <csi:response>
                       <csi:responseDate>2000-01-01T00:00:00</csi:responseDate>
                       <csi:responseText>" . encode_entities($seen{$v}{response}) . "</csi:responseText>
                     </csi:response>
                   </csi:responses>\n";
    }
    print $line;
  }
  elsif ($line =~ /<\/csi:question>/o) {
    print $response;
    print $line;
  }
  elsif ($line =~ /<csi:questionType>D<\/csi:questionType>/) {
    print $line;
    $response = ''; # Don't import date types since this will almost certainly break the xml schema rules
  }
  else {
    $line =~ s/\x{2018}/'/g; # apostrophe
    $line =~ s/\x{2019}/'/g; # apostrophe
    $line =~ s/\x{201C}/"/g; # left double quote
    $line =~ s/\x{201D}/"/g; # right double quote
    print $line;
  }
}

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
