package PerlMagpie::Schema::Result::Role;
use v5.40.1;
use parent 'PerlMagpie::Schema::Result';

__PACKAGE__->table('role');

__PACKAGE__->add_columns(
   id   => { data_type => 'integer', is_auto_increment => 1 },
   role => { data_type => 'text' },
);

__PACKAGE__->set_primary_key('id');

__PACKAGE__->has_many( login_roles => 'PerlMagpie::Schema::Result::LoginRole', 'role' );
__PACKAGE__->many_to_many( logins => 'login_roles', 'login' );

1;
