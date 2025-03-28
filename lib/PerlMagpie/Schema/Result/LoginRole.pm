package PerlMagpie::Schema::Result::LoginRole;
use v5.40.1;
use parent 'PerlMagpie::Schema::Result';

__PACKAGE__->table('login_role');

__PACKAGE__->add_columns(
   login => { data_type => 'integer', is_foreign_key => 1 },
   role  => { data_type => 'integer', is_foreign_key => 1 },
);

__PACKAGE__->set_primary_key(qw/login role/);

__PACKAGE__->belongs_to( login => 'PerlMagpie::Schema::Result::Login' );
__PACKAGE__->belongs_to( role  => 'PerlMagpie::Schema::Result::Role' );

1;
