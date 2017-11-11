# C3TT::CLient
#
# Copyright (c) 2010 Peter Große <pegro@fem-net.de>, all rights reserved
# This program is free software; you can redistribute it and/or
# modify it under the same terms as Perl itself.

package C3TT::Client;

=head1 NAME

C3TT::Client - Client for interacting with the C3 Ticket Tracker via XML-RPC

=head1 VERSION

Version 0.2

=cut

our $VERSION   = '0.2';

=head1 SYNOPSIS

Generic usage

    use C3TT::CLient;

    my $rpc = C3TT::Client->new( $uri, $prefix, $secret );

Set current project

    my $project = $rpc->setCurrentProject('myproject');

Call a remote method

    my $states = $rpc->getAllStates(1);

=head1 DESCRIPTION

C3TT::Client is a library for interacting with the C3TT via XML-RPC with automatic encoding 
of all arguments

=head1 METHODS

=head2 new ($url, $prefix, $secret)

=head2 setCurrentProject ($project_slug)

Create C3TT:Client object.

=cut

use strict;
use warnings;

use XML::RPC::Fast;
use vars qw($AUTOLOAD);

use Data::Dumper;
use Net::Domain qw(hostname hostfqdn);
use Digest::MD5 qw(md5_hex);

sub new {
    my $prog = shift;
    my $self;

    $self->{url} = shift;
    $self->{prefix} = shift;
    my $secret = shift;

	$self->{uid} = md5_hex(hostfqdn . "$secret");

    $self->{remote} = XML::RPC::Fast->new($self->{url}.'/'.$self->{uid}.'/'.hostfqdn);

    bless $self;

    return $self;
}

sub setCurrentProject {
	my $self = shift;
	$self->{project_slug} = shift;

	$self->{remote} = XML::RPC::Fast->new($self->{url}.'/'.$self->{uid}.'/'.hostfqdn.'/'.$self->{project_slug});

	bless $self;
}

sub AUTOLOAD {

    my $name = $AUTOLOAD;
    $name =~ s/.*://;

    if($name eq 'DESTROY') {
      return;
    }

    my $self = shift;

    if(!defined $self->{remote}) {
      print "No RPC available.";
      exit 1;
    }

   return $self->{remote}->call($self->{prefix}.'.'.$name, @_);
}

1;
