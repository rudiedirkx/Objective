Objective
====

Objective is a very small, very fast PHP-JSON object store.

Get started
----

Copy `env.php.orig` to `env.php` and change the constant to where you want to keep
your super secret stores. They're JSON files, like `bfde0220.json` or
`yourname@gmail.com.json`.

Web API
----

Everything goes into `index.php` (probably your server's index script), so you can call
it at `example.com/Objective/`, or wherever you put it.

There's a mandatory param `store`, which functions as your username and password combined. One
store is one file, so that one file is yours alone. Make it something unguessable, like a UUID.

There are 5 web API methods (not HTTP verbs):

* *get*  
  requires 1 param: `get`, with the hierarchical tree of the value,
  e.g. `people.john.location.street`, which would look for:
  `{"people": {"john": {"location": {"street": ...}}}}`
  where the `...` can be anything.
* *put*  
  requires 2 params: `put` and `value`, where `put` is the same as `get` in the
  above method and `value` is a JSON encoded string of the value, e.g.: `"Elmstreet"`, or
  if you want to save john's whole location at once:
  `{"street": "Elmstreet", "number": "10a", "postal": "12345", "state": "XY", "country": "AB"}`
* *delete*  
  requires 1 param: `delete`, with the same format as `get` and `put` above. Takes an
  additional param `clean` to clean up the container, if it's empty after deletion.
* *push*  
  requires 2 params: `push` and `value`, where `push` is like `get`, and `value` is a JSON
  encoded **scalar** (string/int/float/bool). The path in `push` must be an array, or will
  be converted into one. The given `value` will be appended to the array and the entire array
  will be returned. There's an extra param `unique` to keep the array values unique.
* *pull*  
  works exactly like *push*, including `unique`, but will remove `value` from the array.

Both GET and POST requests are supported, meaning you can `put` a value using a GET request.

All JSON output will be prefixed with an anti-JSON-hijacking 'script': `while(1);` by default.
The length of this script will be added to HTTP header `X-anti-hijack` so your client can
substring the result and decode it.

Internal API
----

The `ObjectStore` PHP class a few functions:

* *encoding/decoding*  
  Since you might not want JSON, the `encode($data)` and `decode($data)` methods are public and
  overridable. They both accept 1 argument `$data` and return the result.
* *filesystem*  
  The store reads from and writes to the filesystem as little as possible. The entire store
  contents are cached in the store object, so both methods `load()` and `save()` take no
  arguments and the return values are irrelevant.
* *public*  
  To fetch or update anything from/in the store (cache), use `get($name, &$found)`,
  `put($name, $value)` and `delete($name)`. They behave like the web API. `&$found` and `delete()`'s
  return value will be bool depending on whether the requested var exists/existed in the store.
