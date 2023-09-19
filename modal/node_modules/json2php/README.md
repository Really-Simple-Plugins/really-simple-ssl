json2php
========

### Instalation

To install json2php you could clone the project from Github or use NPM to install it.

```bash
$ npm install json2php
```

### Usage

Convert JavaScript object/array/string/number/boolean to string that is the corresponding PHP representation.

#### String

When the content is just a string the output will be the same string.

```javascript
s = json2php('Hello World!')
// => s = 'Hello World!'
```

#### Number

Numbers are the same.

```javascript
s = json2php(123)
// => s = '123'
```

#### Boolean

```javascript
s = json2php( true )
// => s = 'true'
```

#### Undefined/Null

`null` and `undefined` are returned as `null`

```javascript
s = json2php(undefined)
// => s = 'null'
```

#### Array

```javascript
s = json2php([1, 2, 3])
// => s = 'array(1, 2, 3)'
```

#### Object

```javascript
s = json2php({a: 1, b: 2, c: 'text', false: true, undefined: null})
// => s = "array('a' => 1, 'b' => 2, 'c' => 'text', 'false': true, 'undefined': null)"
```

#### Non-valid JSON

```javascript
s = json2php(new Date())
// => s = "null"
```

### Pretty printing
Create custom 'printers' with `json2php.make`: 

```javascript
const printer = json2php.make({linebreak:'\n', indent:'\t', shortArraySyntax: true})
printer({one: 3, two: 20, three: [9, 3, 2]})

/* result:.
[
	'one' => 3, 
	'two' => 20, 
	'set' => [
		9, 
		3, 
		2
	]
]
*/
```

### For Contributors

#### Tests

To run test we use `mocha` framework.

```bash
$ npm test
```

#### CoffeeScript Source

But in any case you will depend on `coffee-script`

```bash
$ npm run build
```

### Changelog

#### 0.0.7
  * Add `shortArraySyntax` to pretty print options

#### 0.0.6
  * Add pretty print capability via `json2php.make` (thanks to @stokesman)

#### 0.0.5
  * Update and clean up (thanks to @SumoTTo)
  * Add boolean type (thanks to @SumoTTo)

#### 0.0.4
  * Fix for single quotes escaping (thanks to @ksky521)

#### 0.0.3
  * Fixed the case when non-valid JSON is passed
  * Fixing the bug with the object section

#### 0.0.2
  * Adding the package.json to Git repository, also package dependency
  * Changes into the file structure
  * Adding CoffeeScript source ( Not finished yet )
  * Adding Cakefile and task `test`
  * Adding Mocha for test framework.
  * Adding `test`, `src`, `lib` directory
  * Adding tests

#### 0.0.1
  * Init the project into NPM
  * module.exports for Node.js
  * Added json2php into the global scope with global.json2php
