package PerlMagpie::Schema;
use 5.40.1;
use parent 'DBIx::Class::Schema';

__PACKAGE__->load_components(qw/
   Schema::ResultSetNames
   Schema::Versioned::Inline
/);
__PACKAGE__->load_namespaces( default_resultset_class => 'ResultSet');

our $FIRST_VERSION = '0.001';
our $VERSION = '0.001';

1;