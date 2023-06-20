
# Database Usage
In this section we'll go over how to use the database engine with definitions and examples.

## Creating Documents

Creates a new document using its document `Array` and returns a new `id`

### Defintion
```
createDocument(String $store, Array $document) : String
```
|Param|Type|Description|
|-|-|-|
|$store|String|Store Name
|$document|Array|Your Document Struct
|RETURN|String|Created Document Id. Exception is thrown on error.

### Example
```
$id = $db->createDocument(
  'YOUR_STORE_NAME',
  [
    'title' => 'Ultimecia\'s Final Words',
    'description' => "Time... It will not wait...",
    'tags' => ['final-fantasy', 'ff8', 'ultimecia'],
    'slug' => 'ultimecias-final-words',
  ]
);
```

## Updating Documents

Updates a document using its `id` and a document `Array`

### Defintion
```
updateDocumentById(String $store, Array $document, String $id) : String
```
|Param|Type|Description|
|-|-|-|
|$store|String|Store Name
|$document|Array|Your Document Struct
|$id|String|UUIDv4 Document Id
|RETURN|String|Updated Document Id. Exception is thrown on error.

### Example
```
$id = $db->updateDocument(
  'YOUR_STORE_NAME',
  [
    'title' => 'Ultimecia\'s Final Words',
    'description' => "No matter... ...how hard you hold on. It escapes you...",
    'tags' => ['final-fantasy', 'ff8', 'ultimecia'],
    'slug' => 'ultimecias-final-words',
  ],
  'e023382c-b463-4f2e-a7a9-3cb7f2dd1d7f'
);
```

> **Note:** Updating documents requires the whole document to be sent back as if you were writing to a file buffer. Trying to do partial updates will destroy the document structure so be careful.

## Deleting Documents

Deletes a document using its `id`

### Defintion
```
deleteDocumentById(String $store, String $id) : Void
```
|Param|Type|Description|
|-|-|-|
|$store|String|Store Name
|$id|String|UUIDv4 Document Id
|RETURN|Void|Nothing is returned when successful. Exception is thrown on error.

### Example
```
$db->deleteDocumentById('YOUR_STORE_NAME', 'e023382c-b463-4f2e-a7a9-3cb7f2dd1d7f');
```

## Fetching Documents By Id

Fetches a document using its `id`

### Defintion
```
getDocumentById(String $store, String $id) : Array
```
|Param|Type|Description|
|-|-|-|
|$store|String|Store Name
|$id|String|UUIDv4 Document Id
|RETURN|Array|Document array struct is returned. Exception is thrown on error.

### Example
```
$db->getDocumentById('YOUR_STORE_NAME', 'e023382c-b463-4f2e-a7a9-3cb7f2dd1d7f');
```
## Fulltext Searching (Array Return)
This returns a documents in `Array` format using a fulltext search.

> ⚠️ **Warning:** This result can get chonky if you're returning a bunch of documents. I highly recommend using the **generator option instead** (defined below) for memory and process safety. However, I can see where this can be useful so it's here.

### Defintion
```
getDocumentsByFulltextSearch(String $store, String $search, Bool $optAnd = true, Int $limit = 0, Int $offset = 0) : Array
```
|Param|Type|Optional|Description|
|-|-|-|--|
|$store|String||Store Name|
|$search|String||Search String|
|$optAnd|Bool|✅| `true` (Default) is an AND search, `false` is an OR search.|
|$limit|Int|✅|Fetch limit, 0 = Unlimited
|$offset|Int|✅|Offset pager, 0 = No Offset
|RETURN|Array||An array of documents is returned. Will return `[]` if no documents are found. Exception is thrown on error.

### Example
```
$documents = $db->getDocumentsByFulltextSearch('YOUR_STORE_NAME', 'Ultimecia');
```

> **Reminder**: You must have your fulltext defined for your specific store for this feature to work, otherwise you will be thrown an error. Check your configuration to ensure you have this configured.

## Fulltext Searching (Generator Return)

This returns documents using a PHP generator by doing a fulltext search. This is more memory efficient as it will only pull one document at a time per loop. This is the recommended way to pull large sets of documents without eating memory.

### Defintion
```
getDocumentsByFulltextSearchGenerator(String $store, String $search, Bool $optAnd = true, Int $limit = 0, Int $offset = 0) : \Generator
```
|Param|Type|Optional|Description|
|-|-|-|--|
|$store|String||Store Name|
|$index|String||Index Name|
|$value|Mixed||Index Value|
|$limit|Int|✅|Fetch limit, 0 = Unlimited
|$offset|Int|✅|Offset pager, 0 = No Offset
|RETURN|\Generator||Will return a PHP \Generator class that yields on document pull. Will throw an exception on error.

### Example
```
$docGenerator = $db->getDocumentsByFulltextSearchGenerator('YOUR_STORE_NAME', 'Ultimecia');
foreach($docGenerator as $doc){
  // Code Loop Here
}
```

> **Reminder**: You must have your fulltext defined for your specific store for this feature to work, otherwise you will be thrown an error. Check your configuration to ensure you have this configured.

## Index Searching (Array Return)

This returns a documents in `Array` format doing a field index search.

> ⚠️ **Warning:** Same deal as the fulltext array search. This can become memory intensive of a lot of documents is pulled.  I highly recommend using the **generator option instead**.

### Defintion
```
getDocumentsByIndex(String $store, String $index, $value, Int $limit = 0, Int $offset = 0) : Array
```
|Param|Type|Optional|Description|
|-|-|-|--|
|$store|String||Store Name|
|$index|String||Index Name|
|$value|Mixed||Index Value|
|$limit|Int|✅|Fetch limit, 0 = Unlimited
|$offset|Int|✅|Offset pager, 0 = No Offset
|RETURN|Array||An `Array` of documents is returned. Will return `[]` if no documents are found. Exception is thrown on error.

### Example
```
$documents = $db->getDocumentsByIndex('YOUR_STORE_NAME', 'tags', 'final-fantasy');
```

> **Reminder**: You must have your field index defined in your configuration for this to work, otherwise you will be thrown an error. Check your configuration to ensure you have this configured.

## Index Searching (Generator Return)

This returns documents using a PHP generator by doing a index field search. This is more memory efficient as it will only pull one document at a time per loop. This is the recommended way to pull large sets of documents without eating memory.

### Defintion
```
getDocumentsByIndex(String $store, String $index, $value, Int $limit = 0, Int $offset = 0) : Array
```
|Param|Type|Optional|Description|
|-|-|-|--|
|$store|String||Store Name|
|$index|String||Index Name|
|$value|Mixed||Index Value|
|$limit|Int|✅|Fetch limit, 0 = Unlimited
|$offset|Int|✅|Offset pager, 0 = No Offset
|RETURN|\Generator||Will return a PHP \Generator class that yields on document pull. Will throw an exception on error.

### Example
```
$docGenerator = $db->getDocumentsByIndexGenerator('YOUR_STORE_NAME', 'tags', 'final-fantasy');
foreach($docGenerator as $doc){
  // Code Loop Here
}
```

> **Reminder**: You must have your field index defined in your configuration for this to work, otherwise you will be thrown an error. Check your configuration to ensure you have this configured.

## Count Values By Index

This allows you to count how many instances of an index value exists in a store. This is useful, for example, if you want to check for uniqueness such as `slugs` for URLs as well as getting counts for things where you might not need a whole distinct table calculation.

### Defintion
```
countValueByIndex(String $store, String $index, $value, String $id = '') : Int
```

|Param|Type|Optional|Description|
|-|-|-|--|
|$store|String||Store Name|
|$index|String||Index Name|
|$value|Mixed||Index Value|
|$id|String|✅|Exclude Document ID From Count|
|RETURN|Int||Count of documents with indexed value on field. Throws exception on error.

### Example
```
$count = $db->countValueByIndex(
  'YOUR_STORE_NAME',
  'tags',
  'ultimecia'
); // Returns 1

$count = $db->countValueByIndex(
  'YOUR_STORE_NAME',
  'tags',
  'ultimecia',
  'e023382c-b463-4f2e-a7a9-3cb7f2dd1d7f'
); // Returns 0
```

## Distinct Keys By Index
This allows you to retrieve the distinct keys given an index as well as their document count for each. Excellent for `tags` usage in a document. This returns an associative `Array` in `value:count` format.

### Defintion
```
getDistinctByIndex(String $store, String $index) : Array
```

|Param|Type|Optional|Description|
|-|-|-|--|
|$store|String||Store Name|
|$index|String||Index Name|
|RETURN|Array||Array of key value pairs with the key and count of each document. Throws exception on error.

### Usage
```
$tags = $db->getDistinctByIndex('YOUR_STORE_NAME', 'tags');
```

## Force Re-Index of Indexes
This function might be the most complicated of them all in terms of understanding what it's for if you're not familar with how databases work.

This reindexes all of the documents in the store. You may be asking yourself why is this useful? Since this database engine runs in realtime and not as a process daemon, schema changes don't automatically index existing documents. Attempting to do them realtime would be nightmareish from a client perspective and could cause processes or page loads to hang given enough documents.

So for example, let's say you decided you really needed to have an existing value indexed because you need to query on it, so you go and enable it in the configuration. Well, you then attempt to do a search and nothing returns. This is because the old documents are not indexed. This is where this function comes in handy.

How I recommend this being implemented is a few ways:

- In your CI/CD or deploy process when schema changes occur.
- Manually through command line as needed.
- A admin page that is locked behind an authentication gate to prevent bot abuse.

### Defintion
```
forceReindex(String $store) : Void
```

|Param|Type|Optional|Description|
|-|-|-|--|
|$store|String||Store Name|
|RETURN|Void||Returns nothing on success. Throws exception on error.

### Usage
```
ini_set('max_execution_time', SOME_LONG_TIME_VALUE);
$db->forceReindex('YOUR_STORE_NAME');
```

## Next Steps
Congrats, you should have your document store needs met. This next step is optional but recommended. Continue to the [Encapsulation](encapsulation.md) section for ideas on how to integrate this into your frameworks.
