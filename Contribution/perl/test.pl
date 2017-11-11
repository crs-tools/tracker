#!/usr/bin/perl -T -I.
use C3TT::Client;
use Data::Dumper;

	my $secret = "XXXXXX";

    my $rpc = C3TT::Client->new("http://pegro.tracker.fem.tu-ilmenau.de/rpc",'C3TT',$secret);
    $rpc->setCurrentProject("iwut11");

	my $services = $rpc->getServices();
	print Dumper($services);

	my $test = {};
	$test->{'Test.Bla'}   = "Hallo";
	$test->{'Test.Blubb'} = "Sie";

	my $res = $rpc->setTicketProperties(39,$test);
	print Dumper($res);

	my $props = $rpc->getTicketProperties(39);
	print Dumper($props);

	my $cmd = $rpc->ping(null, 100, 'dont bother me');
	print Dumper($cmd);

	my $ticket = $rpc->assignNextUnassignedForState("merging");
	print Dumper($ticket);

	if($ticket) {
		my $test = $rpc->setTicketDone($ticket->{id},'kaputt');
		print Dumper($test);
	}
