package PerlMagpie::Schema::Result;
use v5.40.1;
use parent 'DBIx::Class';

__PACKAGE__->load_components(qw/
   Core
   Numeric
/);

1;