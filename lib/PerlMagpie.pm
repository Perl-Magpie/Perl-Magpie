package PerlMagpie;
use Modern::Perl;
use Dancer2 appname => 'PerlMagpie';

use PerlMagpie::Metabase;

get '/' => sub {
    template 'index' => { 'title' => 'PerlMagpie' };
};

true;
