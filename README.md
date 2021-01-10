# Airtable for PHP

Basic SDK to deal with airtable records.

## Installation

Tell composer to require this bundle by running:

``` bash
composer require armetiz/airtable-php
```

## Usage

```php
$key   = "APP_KEY"; // Generated from : https://airtable.com/account
$base  = "BASE_ID"; // Find it on : https://airtable.com/api
$table = "TABLE_NAME"; // Find it on : https://airtable.com/api

$airtable = new Airtable($key, $base);

$records = $airtable->findRecords($table);
```

**Available methods**

* Airtable::createTableManipulator(string $table): TableManipulator
* Airtable::getRecord(string $table, string $id)
* Airtable::createRecord(string $table, array $fields)
* Airtable::setRecord(string $table, array $criteria = [], array $fields)
* Airtable::updateRecord(string $table, array $criteria = [], array $fields)
* Airtable::containsRecord(string $table, array $criteria = [])
* Airtable::flushRecords(string $table)
* Airtable::deleteRecord(string $table, array $criteria = [])
* Airtable::findRecord(string $table, array $criteria = [])
* Airtable::findRecords(string $table, array $criteria = [], string $view = null)

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
use Armetiz\AirtableSDK\Airtable as AirtableClient;

class MemberIndex
{
    private $airtable;

    public function __construct(AirtableClient $airtableClient, string $table)
    {
        $this->airtable = $airtableClient->createTableManipulator($table);
    }

    public function clear()
    {
        $this->airtable->flushRecords();
    }

    public function save(array $data)
    {
        $criteria = ["Id" => $data["id"]];
        $fields   = [
            "Id"                    => $data["id"],
            "Firstname"             => $data["firstName"],
            "Lastname"              => $data["lastName"],
            "Email"                 => $data["email"],
            "CreatedAt"             => (string)$data["createdAt"],
        ];

        if ($this->airtable->containsRecord($criteria)) {
            $this->airtable->updateRecord($criteria, $fields);
        } else {
            $this->airtable->createRecord($fields);
        }
    }

    public function delete($id)
    {
        $this->airtable->deleteRecord(["Id" => $id]);
    }
}
```

## Testing

Not implemented yet.

## License

This library is under the MIT license. [See the complete license](https://github.com/armetiz/airtable-php/blob/master/LICENSE).

## Credits

Original Author - [Thomas Tourlourat](http://www.wozbe.com)
