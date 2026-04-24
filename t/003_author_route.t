use strict;
use warnings;

use PerlMagpie;
use Test::More tests => 5;
use Plack::Test;
use HTTP::Request::Common;
use Ref::Util qw<is_coderef>;

my $app = PerlMagpie->to_app;
ok( is_coderef($app), 'Got app' );

my $test = Plack::Test->create($app);

# Test author page route is registered (not 404)
my $res = $test->request( GET '/author/TODDR' );
isnt( $res->code, 404, '[GET /author/TODDR] route exists' );

# Test author API route is registered (not 404)
$res = $test->request( GET '/api/author/TODDR' );
isnt( $res->code, 404, '[GET /api/author/TODDR] route exists' );

# Test case-insensitivity: lowercase author name should work
$res = $test->request( GET '/author/toddr' );
isnt( $res->code, 404, '[GET /author/toddr] route exists (lowercase)' );

$res = $test->request( GET '/api/author/toddr' );
isnt( $res->code, 404, '[GET /api/author/toddr] route exists (lowercase)' );
