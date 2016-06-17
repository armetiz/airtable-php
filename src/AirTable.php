<?php

namespace Armetiz\AirTableSDK;

use Assert\Assertion;
use Buzz;
use Buzz\Message\Response;

/**
 * @author : Thomas Tourlourat <thomas@tourlourat.com>
 */
class AirTable
{
    /**
     * @var Buzz\Browser
     */
    private $browser;

    /**
     * @var string
     */
    private $base;

    /**
     * AirTable constructor.
     *
     * @param string $accessToken
     * @param string $base
     */
    public function __construct($accessToken, $base)
    {
        Assertion::string($accessToken);

        // @see https://github.com/kriswallsmith/Buzz/pull/186
        $listener = new Buzz\Listener\CallbackListener(function (Buzz\Message\RequestInterface $request, $response = null) use ($accessToken) {
            if ($response) {
                // postSend
            } else {
                // preSend
                $request->addHeader(sprintf('Authorization: Bearer %s', $accessToken));
            }
        });

        $this->browser = new Buzz\Browser(new Buzz\Client\Curl());
        $this->browser->addListener($listener);

        $this->base = $base;
    }

    public function createRecord($table, array $fields)
    {
        /** @var Response $response */
        $response = $this->browser->post(
            $this->getEndpoint($table),
            [
                "content-type" => "application/json",
            ],
            json_encode([
                "fields" => $fields,
            ])
        );

        $this->guardResponse($table, $response);
    }

    public function updateRecord($table, array $criteria = [], array $fields)
    {
        $record = $this->findRecord($table, $criteria);

        /** @var Response $response */
        $response = $this->browser->put(
            $this->getEndpoint($table, $record->getId()),
            [
                "content-type" => "application/json",
            ],
            json_encode([
                "fields" => $fields,
            ])
        );

        $this->guardResponse($table, $response);
    }

    public function containsRecord($table, array $criteria = [])
    {
        return !is_null($this->findRecord($table, $criteria));
    }

    public function flushRecords($table)
    {
        $records = $this->findRecords($table);

        /** @var Record $record */
        foreach ($records as $record) {
            /** @var Response $response */
            $response = $this->browser->delete(
                $this->getEndpoint($table, $record->getId()),
                [
                    "content-type" => "application/json",
                ]
            );

            $this->guardResponse($table, $response);
        }
    }

    public function deleteRecord($table, array $criteria = [])
    {
        $record = $this->findRecord($table, $criteria);

        /** @var Response $response */
        $response = $this->browser->delete(
            $this->getEndpoint($table, $record->getId()),
            [
                "content-type" => "application/json",
            ]
        );

        $this->guardResponse($table, $response);
    }

    /**
     * @param       $table
     * @param array $criteria
     *
     * @return Record|null
     */
    public function findRecord($table, array $criteria = [])
    {
        $records = $this->findRecords($table, $criteria);

        if (count($records) > 1) {
            throw new \RuntimeException(sprintf(
                "More than one records have been found from '%s:%s'.",
                $this->base, $table
            ));
        }

        if (count($records) === 0) {
            return null;
        }

        return current($records);
    }

    /**
     * @param       $table
     * @param array $criteria
     *
     * @return Record[]
     */
    public function findRecords($table, array $criteria = [])
    {
        $url = $this->getEndpoint($table);

        if (count($criteria) > 0) {
            $formulas = [];
            foreach ($criteria as $field => $value) {
                $formulas[] = sprintf("%s='%s'", $field, $value);
            }

            $url .= sprintf(
                "?filterByFormula=(%s)",
                join(" AND ", $formulas)
            );
        }

        /** @var Response $response */
        $response = $this->browser->get(
            $url,
            [
                "content-type" => "application/json",
            ]
        );

        $data = json_decode($response->getContent(), true);

        return array_map(function (array $value) {
            return new Record($value["id"], $value["fields"]);
        }, $data["records"]);
    }

    protected function getEndpoint($table, $id = null)
    {
        if ($id) {
            $urlPattern = "https://api.airtable.com/v0/%BASE%/%TABLE%/%ID%";

            return strtr($urlPattern, [
                '%BASE%'  => $this->base,
                '%TABLE%' => $table,
                '%ID%'    => $id,
            ]);
        }

        $urlPattern = "https://api.airtable.com/v0/%BASE%/%TABLE%";

        return strtr($urlPattern, [
            '%BASE%'  => $this->base,
            '%TABLE%' => $table,
        ]);
    }

    /**
     * @param string   $table
     * @param Response $response
     */
    protected function guardResponse($table, Response $response)
    {
        if (429 === $response->getStatusCode()) {
            throw new \RuntimeException(sprintf(
                    'Rate limit reach on "%s:%s".',
                    $this->base,
                    $table
                )
            );
        }

        if (200 !== $response->getStatusCode()) {
            $content = json_decode($response->getContent(), true);
            $message = "No details";
            if (isset($content["error"]["message"])) {
                $message = $content["error"]["message"];
            }

            throw new \RuntimeException(sprintf(
                    'An "%s" error occurred when trying to create record on "%s:%s" : %s',
                    $response->getStatusCode(),
                    $this->base,
                    $table,
                    $message
                )
            );
        }
    }
}