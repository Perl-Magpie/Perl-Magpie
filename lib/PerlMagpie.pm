package PerlMagpie;
use 5.40.1;
use Dancer2 appname => 'PerlMagpie';
use Dancer2::Plugin::DBIx::Class;

use PerlMagpie::Metabase;
use PerlMagpie::Schema;

prefix undef;

#
# Routes
#

get '/' => sub {
   # TODO some sort of home page
};

get '/dist/:dist_name' => sub {
   my $dist_name = route_parameters->get('dist_name');
   my $test_count = db_tests->search({ distribution => $dist_name })->count;
   my $latest_version = 0;
   if (!$test_count) {
      #TODO strip the version off, and try a LIKE search, and repopulate $dist_name and set $latest_version if $test_count moves positive.
   }
   send_as html => template 'public/matrix_by_dist', {
      title          => "PerlMagpie Matrix for $dist_name",
      dist_name      => $dist_name,
      none_found     => $test_count > 0 ? 0 : 1,
      latest_version => $latest_version,
   };
};

get '/list' => sub {
   my $q = query_parameters;
   send_as html => template 'public/test_list', {
      title     => "PerlMagpie Tests for $q->{dist}",
      dist_name => $q->{dist},
      query     => $q,
   };
};

#
# API Routes
#

prefix '/api';

get '/dist/:dist_name' => sub {
   my $dist_name = route_parameters->get('dist_name');

   my $osnames = [
      rset('Test')->search(
         { 'me.distribution' => $dist_name },
         {
            select   => [ 'osname', \'COUNT(*)' ],
            as       => [ 'osname', 'count_total' ],
            group_by => ['osname'],
            order_by => [ { -desc => \'COUNT(*)' } ],
         }
      )->hri->all
   ];
   my $must_show_osnames = [
      rset('Test')->search(
         { 'me.distribution' => $dist_name, grade => 'FAIL' },
         {
            select   => [ 'osname', \'COUNT(*)' ],
            as       => [ 'osname', 'count_total' ],
            group_by => ['osname'],
            order_by => [ { -desc => \'COUNT(*)' } ],
         }
      )->hri->all
   ];
   my $perl_versions = [
      rset('Test')->search(
         { 'me.distribution' => $dist_name },
         {
            select   => [ 'perl_version', \'COUNT(*)' ],
            as       => [ 'perl_version', 'count_total' ],
            group_by => ['perl_version'],
            order_by => [ { -desc => 'perl_version' } ],
         }
      )->hri->all
   ];
   my $tests = [
      rset('Test')->search(
         { 'me.distribution' => $dist_name },
         {
            select => [
               'osname',
               'perl_version',
               { SUM => \'CASE WHEN grade = \'PASS\' THEN 1 END' },
               { SUM => \'CASE WHEN grade = \'FAIL\' THEN 1 END' },
               { SUM => \'CASE WHEN grade = \'NA\' THEN 1 END' },
               { SUM => \'CASE WHEN grade = \'UNKNOWN\' THEN 1 END' },
               \'COUNT(*)'
            ],
            as => [
               'osname',   'perl_version',  'count_pass', 'count_fail',
               'count_na', 'count_unknown', 'count_total'
            ],
            group_by => [ 'osname', 'perl_version' ],
         }
      )->hri->all
   ];
   send_as JSON => {
      osnames           => $osnames,
      must_show_osnames => [ { osname => 'freebsd', count_total => 1 }], #$must_show_osnames,
      perl_versions     => $perl_versions,
      tests             => $tests,
   };
};

#
# Public functions
#

#
# 'Private' functions
#

1;
