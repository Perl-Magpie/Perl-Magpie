package PerlMagpie::Metabase;
use 5.40.1;

use Dancer2 appname => 'PerlMagpie';
use Dancer2::Plugin::DBIx::Class;
use Dancer2::Plugin::REST;
use UUID qw(uuid7);;

# This module is to handle legacy Metabase URLs

prefix undef;

# This one doesn't matter
#  get '/' => sub {};

get '/tail/log.txt' => sub {

};

prefix '/api/v1';

post '/submit/CPAN-Testers-Report' => sub {
   my $p = request->content;
   my $result = from_json($p);
   my $report = from_json($result->{content})->[0];
   my ($creator) = $report->{metadata}->{core}->{creator} =~ /^.*\:(.*)$/;
   my ($distro) = $report->{metadata}->{core}->{resource} =~ /.*\/(.*).tar.gz$/;
   my $full_report = from_json($report->{content});

   my $tester = rset('Tester')->find_or_create({ uuid => $creator });
   # TODO query the Metabase for user information with $creator uuid, get more details for $tester
   rset('Test')->create({
      guid => $report->{metadata}->{core}->{guid},
      test_ts => $report->{metadata}->{core}->{creation_time},
      tester => $tester->id,
      distribution => $distro,
      grade => uc $full_report->{grade},
      perl_version => $full_report->{perl_version},
      osname => $full_report->{osname},
      osversion => $full_report->{osversion},
      archname => $full_report->{archname},
      text_report => $full_report->{textreport},
   });
   
   return status_204;;
};

# Possibly an unused route?
post '/register' => sub {
   my $p = request->content;
   my $result = from_json($p);
   my $report = from_json($result->{content});
   warn Dumper($report);
   return;

};

get '/guid/:guid' => sub {

};

1;