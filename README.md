# Airtable for PHP

## Require airtable-php

Tell composer to require this bundle by running:

``` bash
composer require armetiz/airtable-php
```

## Use the SDK

```php
$key   = "APP_KEY"; // Generated from : https://airtable.com/account
$base  = "BASE_ID"; // Find it on : https://airtable.com/api
$table = "TABLE_NAME"; // Find it on : https://airtable.com/api

$airtable = new Airtable($key, $base);

$records = $airtable->findRecords($table);
```

**Available methods**

* Airtable::createRecord($table, array $fields)
* Airtable::setRecord($table, array $criteria = [], array $fields)
* Airtable::updateRecord($table, array $criteria = [], array $fields)
* Airtable::containsRecord($table, array $criteria = [])
* Airtable::flushRecords($table)
* Airtable::deleteRecord($table, array $criteria = [])
* Airtable::findRecord($table, array $criteria = [])
* Airtable::findRecords($table, array $criteria = [])

## Example

Simple member indexer that encapsulate Airtable within simple API.
Can be used to start a CRM on Airtable.

Note: Because Airtable doesn't allow schema manipulation using their public API, you should configure table using the WebUI with the following

* Id : text
* Firstname : text
* Lastname : text
* Email : email
* CreatedAt : Date and time
* Picture : Attachments


```php
$key   = "APP_KEY"; // Generated from : https://airtable.com/account
$base  = "BASE_ID"; // Find it on : https://airtable.com/api
$table = "TABLE_NAME"; // Find it on : https://airtable.com/api

$airtable = new Airtable($key, $base);

$records = $airtable->findRecords($table);
```

```php
use Armetiz\AirtableSDK\Airtable;

class MemberIndex
{
    /**
     * @var Airtable
     */
    private $airtable;

    private $table;

    /**
     * MemberIndexer constructor.
     *
     * @param Airtable $airtable
     * @param          $table
     */
    public function __construct(Airtable $airtable, $table)
    {
        $this->airtable = $airtable;
        $this->table    = $table;
    }

    public function clear()
    {
        $this->airtable->flushRecords($this->table);
    }

    public function save(array $data)
    {
        $this->guardData($data);

        $criteria = ["Id" => $data["id"]];
        $fields   = [
            "Id"                    => $data["id"],
            "Firstname"             => $data["firstName"],
            "Lastname"              => $data["lastName"],
            "Email"                 => $data["email"],
            "CreatedAt"             => (string)$data["createdAt"],
        ];

        if ($data["picture"]) {
            $record["Picture"] = [
                ["url" => $data["picture"]],
            ];
        }

        if ($this->airtable->containsRecord($this->table, $criteria)) {
            $this->airtable->updateRecord($this->table, $criteria, $fields);
        } else {
            $this->airtable->createRecord($this->table, $fields);
        }
    }

    public function delete($id)
    {
        $this->airtable->deleteRecord($this->table, ["Id" => $id]);
    }

    private function guardData(array $data)
    {
        $requiredKeys = [
            "id",
            "firstName",
            "lastName",
            "email",
            "picture",
            "createdAt",
        ];

        $availableKeys = array_keys($data);
        foreach ($requiredKeys as $requiredKey) {
            if (!in_array($requiredKey, $availableKeys)) {
                throw new \InvalidArgumentException(sprintf(
                    "Required keys '%s' from data is missing",
                    $requiredKey
                ));
            }
        }
    }
}
```

## Testing

Not implemented yet.

## License

This library is under the MIT license. [See the complete license](https://github.com/armetiz/airtable-php/blob/master/LICENSE).

## Credits

Author - [Thomas Tourlourat](http://www.wozbe.com)
