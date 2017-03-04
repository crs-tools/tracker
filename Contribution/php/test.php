<?php
/**
 * This file is part of C3TT.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright 2016 Forschungsgemeinschaft elektronische Medien e.V. (http://fem.tu-ilmenau.de)
 * @lincense  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link      https://repository.fem.tu-ilmenau.de/trac/c3tt
 */

require_once __DIR__.'/vendor/autoload.php';

$token = 'XXXXXXXXXXXXXXXXXXXXX';
$secret = 'XXXXXXXXXXXXXXXXXXXXX';

// create client
$client = new \C3TT\Client('https://tracker-dev.fem-net.de/pegro/rpc', $token, $secret);

// example call
$version = $client->getVersion();
if($version == '4.0') {
	echo "API still works!\n";
}

// create ticket
$project_id = 18;
$fahrplan_id = 124;
$props = [
	'Fahrplan.Slug' => 'das-ist-ein-test'
];

try {
	if(!in_array($project_id, $client->getServiceableProjects())) {
		echo "Project " . $project_id . " is currently not serviceable\n";
		return;
	}
	
	// get assigned encoding profiles
	$profiles = $client->getEncodingProfiles($project_id);
	
	// create meta ticket
	$ticket = $client->createMetaTicket($project_id, 'Test video', $fahrplan_id, $props);
	var_export($ticket);
	
	// create encoding ticket
	$child_ticket = $client->createEncodingTicket($ticket['id'], $profiles[0]['encoding_profile_id'], ['Encoding.Test' => 12]);
	var_export($child_ticket);
	
	// set next state on encoding ticket
	$client->setCommenceTicketState($child_ticket['id']);
	
	// set next state on meta ticket
	$client->setCommenceTicketState($ticket['id']);
	
	// get ticket info
	var_export($client->getMetaTicketInfo($project_id, $fahrplan_id));
	
	// get ticket info (alternative way)
	var_export($client->getTicketInfo($ticket['id']));
	
} catch(Exception $e) {
	echo "ERROR: (" . $e->getCode() . ") " . $e->getMessage() . "\n";
	return;
}