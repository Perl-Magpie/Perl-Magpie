package PerlMagpie::Schema::ResultSet;
use v5.40.1;
use parent 'DBIx::Class::ResultSet';

__PACKAGE__->load_components(qw/
   Helper::ResultSet::Shortcut
   Helper::ResultSet::MoreShortcuts
/);

1;