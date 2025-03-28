package PerlMagpie::Schema::Result::Tester;
use v5.40.1;
use parent 'PerlMagpie::Schema::Result';

__PACKAGE__->table('tester');

__PACKAGE__->add_columns(
   uuid  => { data_type => 'text' },
   login => { data_type => 'integer', is_nullable => 1, is_foreign_key => 1 },
   name  => { data_type => 'text',    is_nullable => 1 },
   email => { data_type => 'text',    is_nullable => 1 },
);

__PACKAGE__->set_primary_key('uuid');
__PACKAGE__->belongs_to( login => 'PerlMagpie::Schema::Result::Login' );
__PACKAGE__->has_many( tests => 'PerlMagpie::Schema::Result::Test', 'tester');

1;
