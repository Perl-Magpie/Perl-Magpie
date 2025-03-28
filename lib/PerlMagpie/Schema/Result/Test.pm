package PerlMagpie::Schema::Result::Test;
use v5.40.1;
use parent 'PerlMagpie::Schema::Result';

__PACKAGE__->table('test');

__PACKAGE__->add_columns(
   id           => { data_type => 'integer', is_auto_increment => 1 },
   guid         => { data_type => 'text' },
   test_ts      => { data_type => 'timestamp' },
   tester       => { data_type => 'text', is_foreign_key => 1 },
   distribution => { data_type => 'text' },
   grade        => { data_type => 'text' },
   perl_version => { data_type => 'text'},
   osname       => { data_type => 'text'},
   osversion    => { data_type => 'text' },
   archname     => { data_type => 'text' },
   perl_patch   => { data_type => 'text', is_nullable    => 1 },
   text_report  => { data_type => 'bytea' },
);

__PACKAGE__->set_primary_key('id');
__PACKAGE__->belongs_to( tester => 'PerlMagpie::Schema::Result::Tester' );

sub sqlt_deploy_hook {
   my ( $self, $table ) = @_;
   $table->add_index( name => 'guid_idx',         fields => ['guid'] );
   $table->add_index( name => 'tester_idx',       fields => ['tester'] );
   $table->add_index( name => 'distribution_idx', fields => ['distribution'] );
   $table->add_index( name => 'osname_idx',       fields => ['osname'] );
   $table->add_index( name => 'osversion_idx',    fields => ['osversion'] );
   $table->add_index( name => 'archname_idx',     fields => ['archname'] );
   $table->add_index( name => 'perl_patch_idx',   fields => ['perl_patch'] );
   $table->add_index( name => 'perl_version_idx', fields => ['perl_version'] );
}

1;
