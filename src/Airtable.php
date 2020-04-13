<?php

namespace Armetiz\AirtableSDK;

use Assert\Assertion;
use Buzz;
use Buzz\Message\Response;

class Airtable
{
    /** @var Buzz\Browser */
    private $browser;

    /** @var string */
    private $base;

    public function __construct(string $accessToken, string $base)
    {
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

    public function createTableManipulator(string $table): TableManipulator
    {
        return new TableManipulator($this, $table);
    }

    public function createRecord(string $table, array $fields): void
    {
        /** @var Response $response */
        $response = $this->browser->post(
            $this->getEndpoint($table),
            [
                'content-type' => 'application/json',
            ],
            json_encode([
                'fields' => $fields,
            ])
        );

        $this->guardResponse($table, $response);
    }

    /**
     * This will update all fields of a table record, issuing a PUT request to the record endpoint. Any fields that are not included will be cleared ().
     *
     * @param string $table
     * @param array  $criteria
     * @param array  $fields
     *
     * @throws \Assert\AssertionFailedException
     */
    public function setRecord(string $table, array $criteria = [], array $fields): void
    {
        $record = $this->findRecord($table, $criteria);

        Assertion::notNull($record, 'Record not found');

        /** @var Response $response */
        $response = $this->browser->put(
            $this->getEndpoint($table, $record->getId()),
            [
                'content-type' => 'application/json',
            ],
            json_encode([
                'fields' => $fields,
            ])
        );

        $this->guardResponse($table, $response);
    }

    /**
     * This will update some (but not all) fields of a table record, issuing a PATCH request to the record endpoint. Any fields that are not included will not be updated.
     *
     * @param string $table
     * @param array  $criteria
     * @param array  $fields
     *
     * @throws \Assert\AssertionFailedException
     */
    public function updateRecord(string $table, array $criteria = [], array $fields): void
    {
        $record = $this->findRecord($table, $criteria);

        Assertion::notNull($record, 'Record not found');

        /** @var Response $response */
        $response = $this->browser->patch(
            $this->getEndpoint($table, $record->getId()),
            [
                'content-type' => 'application/json',
            ],
            json_encode([
                'fields' => $fields,
            ])
        );

        $this->guardResponse($table, $response);
    }

    public function containsRecord(string $table, array $criteria = []): bool
    {
        return $this->findRecord($table, $criteria) !== null;
    }

    public function flushRecords(string $table): void
    {
        $records = $this->findRecords($table);

        /** @var Record $record */
        foreach ($records as $record) {
            /** @var Response $response */
            $response = $this->browser->delete(
                $this->getEndpoint($table, $record->getId()),
                [
                    'content-type' => 'application/json',
                ]
            );

            $this->guardResponse($table, $response);
        }
    }

    public function deleteRecord(string $table, array $criteria = []): void
    {
        $record = $this->findRecord($table, $criteria);

        Assertion::notNull($record, 'Record not found');

        /** @var Response $response */
        $response = $this->browser->delete(
            $this->getEndpoint($table, $record->getId()),
            [
                'content-type' => 'application/json',
            ]
        );

        $this->guardResponse($table, $response);
    }

    public function getRecord(string $table, string $id): Record
    {
        $url = $this->getEndpoint($table, $id);

        /** @var Response $response */
        $response = $this->browser->get(
            $url,
            [
                'content-type' => 'application/json',
            ]
        );

        $data = json_decode($response->getContent(), true);

        return new Record($data['id'], $data['fields']);
    }

    public function findRecord(string $table, array $criteria = []): ?Record
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
     * TODO - Be able to loop over multiple pages. 
     * 
     * @return Record[]
     */
    public function findRecords(string $table, array $criteria = []): array
    {
        $url = $this->getEndpoint($table);

        if (count($criteria) > 0) {
            $formulas = [];
            foreach ($criteria as $field => $value) {
                $formulas[] = sprintf("%s='%s'", $field, $value);
            }

            $url .= sprintf(
                '?filterByFormula=(%s)',
                implode(' AND ', $formulas)
            );
        }

        /** @var Response $response */
        $response = $this->browser->get(
            $url,
            [
                'content-type' => 'application/json',
            ]
        );

        $data = json_decode($response->getContent(), true);

        return array_map(function (array $value) {
            return new Record($value['id'], $value['fields']);
        }, $data['records']);
    }

    protected function getEndpoint(string $table, ?string $id = null): string
    {
        if ($id) {
            $urlPattern = 'https://api.airtable.com/v0/%BASE%/%TABLE%/%ID%';

            return strtr($urlPattern, [
                '%BASE%'  => $this->base,
                '%TABLE%' => $table,
                '%ID%'    => $id,
            ]);
        }

        $urlPattern = 'https://api.airtable.com/v0/%BASE%/%TABLE%';

        return strtr($urlPattern, [
            '%BASE%'  => $this->base,
            '%TABLE%' => $table,
        ]);
    }

    protected function guardResponse(string $table, Response $response): void
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
            $message = $content['error']['message'] ?? 'No details';

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