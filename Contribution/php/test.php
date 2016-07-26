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
$client = new \C3TT\Client('http://pegro.tracker.fem-net.de/rpc', $token, $secret);

// example call
$version = $client->getVersion();
if($version == '4.0') {
    echo "API still works!\n";
}

// create ticket

$props = [
    'fahrplan_id' => 2337,
    'Fahrplan.Slug' => 'das-ist-ein-test'
];

try {
    $ticket = $client->createTicket(17, 'Test video', $props);

    var_export($ticket);

    // advance ticket state
    $client->setTicketNextState($ticket['id']);

    //var_export($client->getEncodingProfiles());

    $child_ticket = $client->createChildTicket($ticket['id'], 'encoding', ['encoding_profile_id' => 6]);

    var_export($child_ticket);

    $client->setTicketNextState($child_ticket['id']);

} catch(Exception $e) {
    echo "ERROR: (".$e->getCode().") ".$e->getMessage() ."\n";
    return;
}