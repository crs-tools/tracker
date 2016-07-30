#!/usr/bin/perl -I.
use C3TT::Client;
use Data::Dumper;
use POSIX qw(strftime);
use boolean ':all';

my $opt = shift;
my $debug = 0;
$debug = 1 if ($opt eq 'debug');

# init

unless (defined($ENV{'CRS_TRACKER'})) {
	print STDERR "\nyou need to give tracker credentials via env variables, \nsee test-profile.sh how to do that.\n\n"
	die;
}

# result printing

sub print_check {
	my ($value, $expected, undef) = @_;
	print "value:\n".Dumper($value) if ($debug);
	if (!defined($expected)) {
		if (defined($value)) {
			print "OK (returned something)\n";
		} else {
			print "FAIL: nothing returned\n";
			sleep 5;
		}
		return;
	}
	if ($value eq $expected) {
		print "OK\n";
	} else {
		print "FAIL\n";
		print "value:\n".Dumper($value) ."\n";
		print "expected:\n".Dumper($expected) ."\n";
		sleep 5;
	}
}

# Tests:

my $rpc = C3TT::Client->new();

print "testing getVersion(): ";
print_check($rpc->getVersion(), '4.0');

print "testing getEncodingProfiles(): ";
print_check($rpc->getEncodingProfiles());

my $tmp = $rpc->getEncodingProfiles();
$tmp = $$tmp[0]{id};
print "testing getEncodingProfiles($tmp): ";
print_check($rpc->getEncodingProfiles($tmp));

my $start_filter = {};
$start_filter->{'Record.StartedBefore'} = strftime('%FT%TZ', gmtime(time)); 
print "testing assignNextUnassignedForState('recording', 'recording', $start_filter): ";
my $ticket = $rpc->assignNextUnassignedForState('recording', 'recording', $start_filter);
print_check($ticket);

print Dumper($ticket) if($debug);

# There should be assigned tickets right now, because we have one
print "testing getAssignedForState('recording', 'recording', $start_filter): ";
print_check($rpc->getAssignedForState('recording', 'recording', $start_filter));

my $tid = $ticket->{'id'};
my $pid = $ticket->{'project_id'};
my $state = $ticket->{'ticket_state'};
my $type = $ticket->{'ticket_type'};

print "testing ping ($tid, 'foo'): ";
print_check($rpc->ping($tid, 'foo'), 'OK');

print "testing addLog ($tid, 'foo'): ";
print_check($rpc->addLog ($tid, 'foo'), true);

print "testing getNextState($pid, $type, $state): ";
print_check($rpc->getNextState($pid, $type, $state));

print "testing getPreviousState($pid, $type, $state): ";
print_check($rpc->getPreviousState($pid, $type, $state));

print "testing getTicketNextState($tid): ";
print_check($rpc->getTicketNextState($tid)->{'ticket_state'}, 'recorded');

print "testing setTicketNextState($tid): ";
print_check($rpc->setTicketNextState($tid), true);

### get another ticket

$ticket = $rpc->assignNextUnassignedForState('recording', 'recording', $start_filter);
$tid = $ticket->{'id'};

print "testing setTicketDone($tid): ";
print_check($rpc->setTicketDone($tid), true);


### get yet another ticket (encoding ticket)

print "testing assignNextUnassignedForState('encoding', 'encoding') (no filter): ";
$ticket = $rpc->assignNextUnassignedForState('encoding', 'encoding');
if (!$ticket) {
	print STDERR "need an encoding ticket in ready to encode state!\n";
	exit 1;
}
$tid = $ticket->{'id'};

print "testing getTicketProperites($tid): ";
print_check($rpc->getTicketProperties($tid));

my $token = time();
my $pattern = 'RpcTest.Token';
my $props = { };
$props->{$pattern} = $token;

print "testing setTicketProperties($tid, $props): ";
print_check($rpc->setTicketProperties($tid, $props), true);

print "testing getTicketProperties($tid, $pattern): ";
print_check($rpc->getTicketProperties($tid, $pattern), $token);
print "testing getTicketProperties($tid, $pattern) (different than documented): ";
print_check($rpc->getTicketProperties($tid, $pattern)->{$pattern}, $token);

print "testing getTicketProperties($tid): ";
print_check($rpc->getTicketProperties($tid)->{$pattern}, $token);

print "testing getJobFile($tid): ";
print_check($rpc->getJobFile($tid));

print "testing setTicketFailed($tid, 'Failtest'): ";
print_check($rpc->setTicketFailed($tid, 'Failtest'), true);

