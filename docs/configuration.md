# Configuration, Initialization, and Error Handling

## Configuration
Initializing your database requires just the following bit of code:
```
<?php

use \DelightfulDB\Driver as DelightfulDB;
use \DelightfulDB\Exceptions\Delightful as DelightfulException;

try {
  $db = new DelightfulDB(
    STORAGE_PATH <STRING>,
    STORE_CONFIG <ARRAY>,
    TIMEOUT <INT, DEFAULT=5>
  );
} catch (DelightfulException $e) {
  var_dump($e->getErrors());
} catch (\Exception $e) {
  var_dump($e->getMessage());
}
```

### Storage Path
It should be somewhere that is writable. Common ways to handle this would be places like:
- `getcwd() . '/storage'`
- `/var/db/delightful/<PROJECT_NAME>`
- `/srv/database/delightful/<PROJECT_NAME>`

You choose where you want to write your files to.

### Store Config
This holds the defined stores for your documents and all of your index configurations for your document fields. Below is an example:

```
[
  'recipes' => [
    '_fulltext' => [
      'fields' => [
        'title', 'description', 'tags',
      ]
    ],
    'slug' => [],
    'tags' => [
      'distinct' => true']
    ],
  ],
  'users' => [
    'username' => [],
    'email' => [],
  ],
];
```
The configuration schema is as such:
- [INDEXED_ARRAY]
  -  `%STORE_NAME%` [ARRAY]
    - `_fulltext` [ARRAY]
      - `fields` [INDEXED_ARRAY] List of fields you want to use for your fulltext search.
    - `%INDEX_FIELD%`[ARRAY]
      - `distinct` [BOOL] [Optional] Will do distinct counts for your fields.

Keep in mind `%STORE_NAME%` and `%INDEX_FIELD%` can only be alpha-numeric w/ underscores. Furthermore, `%INDEX_FIELD%` cannot be prefixed with underscores as that is system reserved.

### Timeout
This is the write lock timeout in the event there is a critical error and the lock file isn't removed. Default is 5 seconds.

## Error Handling
All errors are handled through exceptions, there will never be errored return values. DelightfulDB's exception handler offers very robust and verbose error handling using the `\DelightfulDB\Exceptions\Delightful::getErrors()` method which returns an object with a ton of information on what went wrong so you can navigate your own code accordingly. Below is an example:

```
[
  'token' => 'INDEXER_INDEX_WRITE_ERROR',
  'store' => 'docs',
  'index' => 'tags',
  'class' => 'DelightfulDB\Services\Indexer',
  'method' => '_writeIndex',
  'line' => 146,
  'id' => '68f72b55-3e56-4069-9bd2-67093241316f',
  'message' => 'DelightfulDB\Exceptions\Service [DelightfulDB\Services\Indexer::_writeIndex] INDEXER_INDEX_WRITE_ERROR'
]
```

Here is a list of errors that you can be presented with:
|Error|Description|
|--|--|
|CONFIG_INVALID_STORE|Store asked for from configuration object has not been defined.|
|CONFIG_ROOT_FAILED_DIRECTORY_CREATE| Could not create root storage directory if it didn't already exist.|
|CONFIG_ROOT_NOT_WRITABLE| Root storage directory isn't writable, check permissions.|
|STORE_KEY_MALFORMED| Defined store key does not conform to the alpha-numeric-understore limit.|
|STORE_FAILED_DIRECTORY_CREATE| Could not create store document directory.|
|STORE_NOT_WRITABLE|Store directory is not writable, check permissions.|
|STORE_LOCK_WRITE_FAILURE|Store write lock could not be written.|
|STORE_INDEX_INVALID_KEY|Index asked for from store does has not been defined.|
|INDEX_KEY_MALFORMED|Defined index key does not conform to the alpha-numeric-understore limit.|
|INDEX_FULLTEXT_NO_DISTINCT|`distinct` definition is not allowed on `_fulltext` fields.|
|INDEX_FULLTEXT_INVALID_FIELDS| Fields is not defined or is not a list of fields.|
|INDEX_FULLTEXT_EMPTY_FIELDS| Fields list is empty.|
|INDEX_FULLTEXT_INVALID_FIELDS_STRING|Field list contains objects that are not strings.|
|INDEX_SYSTEM_RESERVED| A defined custom index has a `_` prefix which is not allowed.|
|INDEX_INVALID_DISTINCT_VALUE|`distinct` value in configuration is not `bool`|
|INDEX_FAILED_DIRECTORY_CREATE| Could not create the index directory.|
|INDEX_NOT_WRITABLE|Index directory is not writable.|
|QUERY_ID_COLLISION|Id Collision When Creating Document (Should Never Happen, But Exists)|
|QUERY_WRITE_ERROR|Error Writing Document to Filesystem|
|QUERY_ID_MALFORMED|Document Id does not conform to UUIDv4|
|QUERY_FILE_MISSING|Document couldn't be found.|
|QUERY_NOT_WRITABLE|Document isn't writable, check permissions.|
|QUERY_DELETE_ERROR|Document could not be deleted.|
|QUERY_NOT_READABLE|Document could not be read, check permissions.|
|QUERY_READ_ERROR|Document could not be read.|
|INDEXER_CLEAN_ERROR| Could not clean document indexes, check permissions.|
|INDEXER_FULLTEXT_WRITE_ERROR|Fulltext index could not be written, check permissions.|
|INDEXER_INDEX_WRITE_ERROR|Index could not be written, check permissions.|
|INDEXER_DISTINCT_DELETE_ERROR|Distinct index could not be deleted, check permissions.|
|INDEXER_DISTINCT_WRITE_ERROR|Distinct index could not be written, check permissions.|

## Next Steps
Continue to the [Usage Section](usage.md) to start creating documents and querying them.
