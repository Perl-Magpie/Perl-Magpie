package PerlMagpie::Metabase;
use 5.40.1;

use Dancer2 appname => 'PerlMagpie';

# This module is to handle legacy Metabase URLs

prefix undef;

# This one doesn't matter
#  get '/' => sub {};

get '/tail/log.txt' => sub {

};

prefix '/api/v1';

post '/submit/CPAN-Testers-Report' => sub {
   my $p = request->content;
   use Data::Dumper; 
   my $result = from_json($p);
   my $report = from_json($result->{content});
   warn Dumper($report);

   # do we have the user uuid of the report on-board?  If so, great, get them; otherwise create, and attempt to fetch information from CPT
   
   return;
};


# Possibly an unused route?
post '/register' => sub {
   my $p = request->content;
   use Data::Dumper; 
   my $result = from_json($p);
   my $report = from_json($result->{content});
   warn Dumper($report);
   return;

};

get '/guid/:guid' => sub {

};

1;