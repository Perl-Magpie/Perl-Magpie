package PerlMagpie::Schema::Result::Login;
use v5.40.1;
use parent 'PerlMagpie::Schema::Result';

__PACKAGE__->table('login');

__PACKAGE__->add_columns(
   id             => { data_type => 'integer',   is_auto_increment => 1 },
   created_dt     => { data_type => 'timestamp', default_value     => \'current_timestamp' },
   username       => { data_type => 'text', },
   password       => { data_type => 'text',      is_nullable   => 1 },
   name           => { data_type => 'text',      is_nullable   => 1 },
   email          => { data_type => 'text',      is_nullable   => 1 },
   disabled       => { data_type => 'boolean',   default_value => \'false' },
   lastlogin      => { data_type => 'timestamp', is_nullable   => 1 },
   pw_changed     => { data_type => 'timestamp', is_nullable   => 1 },
   pw_change_code => { data_type => 'text',      is_nullable   => 1 },
);

__PACKAGE__->set_primary_key('id');

__PACKAGE__->has_many( login_roles => 'PerlMagpie::Schema::Result::LoginRole', 'login' );
__PACKAGE__->has_many( testers     => 'PerlMagpie::Schema::Result::LoginRole', 'login' );
__PACKAGE__->many_to_many( roles => 'login_roles', 'role' );

1;
