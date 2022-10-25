# Devly Repository

Provides an object to store and access data arrays with dot notation support.

## Usage

A new data container instance can be created empty or from data provided as array, object or another `Devly\Repository` instance.

```php
$repository = new \Devly\Repository();

// Or create with initial data
$repository = new \Devly\Repository(['user' => [...]]);
```

Can also be created using the static method `Devly\Repository::new()`

### set()

```php
$repository->set('user.name', 'johndoe');

// Or as array
$repository['user.name'] = 'johndoe';

// Equivalent vanilla PHP
$array['user']['name'] = 'johndoe';
```

### get()

Retrieves data by key name 

```php
$username = $repository->get('user.name');

// Or as array
$username = $repository['user.name'];

// Equivalent vanilla PHP
$username = $array['user']['name'];
```

### has()

Check whether a key name exists

```php
$repository->has('user.name');

// Or as array
isset($repository['user.name']);

// Equivalent vanilla PHP
isset($array['user']['name']);
```

### all()

Returns all the data as array

```php
$array = $repository->all();
```

### remove()

Removes given key 

```php
$repository->remove('user.name');

// Or as array
unset($repository['user.name']);
```

### clear()

Removes all the data

```php
$repository->clear();
```

### count()

Counts the number of items in the data container

```php
$repository->clear();
```

### isEmpty()

Checks whether the container contains data

```php
$repository->remove('user.name');

// Or as array
unset($repository['user.name']);
```

### toJson()

Returns a JSON representation of the data

Returns the value of a given key as JSON:
```php
$repository->toJson('user');
```
Returns all the stored items as JSON:
```php
$repository->toJson();
```

### createFrom()

Create a new Repository instance from an existing item 

```php
$repository->createFrom('user');
```
