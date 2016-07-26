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

namespace C3TT;

class Client
{

    private $prefix = 'C3TT.';

    private $url;
    private $token;
    private $secret;
    private $hostname = 'localhost';

    /**
     * PhpXmlRpc Client instance
     *
     * @var null|\PhpXmlRpc\Client
     */
    private $xmlrpc_client = null;

    private $xmlrpc_encoder = null;

    public function __construct($uri, $worker_group_token, $secret)
    {
        $this->url = $uri;
        $this->token = $worker_group_token;
        $this->secret = $secret;
        $this->hostname = gethostname();

        $this->_createClient();
    }

    public function __call($name, $arguments)
    {
        if(!$this->xmlrpc_client) {
            $this->_createClient();
        }

        // assemble method name
        $method = $this->prefix . $name;

        // generate signature
        $arguments[] = $this->_generateSignature($method, $arguments);

        // call remote
        $response = $this->xmlrpc_client->send(new \PhpXmlRpc\Request($method, $this->xmlrpc_encoder->encode($arguments)));

        if ($response->faultCode()) {
            throw new \Exception($response->faultString(), $response->faultCode());
        }

        return $response->value();
    }

    private function _createClient()
    {
        if($this->xmlrpc_client) {
            return;
        }

        $this->xmlrpc_client = new \PhpXmlRpc\Client($this->url . '?group=' . $this->token . '&hostname=' . $this->hostname);
        $this->xmlrpc_client->return_type = 'phpvals';

        $this->xmlrpc_encoder = new \PhpXmlRpc\Encoder();
    }

    // generate method signature
    private function _generateSignature($method, $arguments)
    {
        // assemble arguments used for generating request signature
        $signature_arguments = array_merge([
            $this->url,
            $method,
            $this->token,
            $this->hostname
        ], $arguments);

        // encode arguments
        $args = array();
        foreach($signature_arguments as $argument) {
            $args[] = (is_array($argument))?
                http_build_query(
                    ['' => $argument],
                    null,
                    '&',
                    PHP_QUERY_RFC3986
                ) :
                rawurlencode($argument);
        }

        // generate signature
        $hash = hash_hmac(
            'sha256',
            implode('%26', $args),
            $this->secret
        );

        return $hash;
    }
}