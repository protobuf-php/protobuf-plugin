Protobuf for PHP
================

[![Build Status](https://travis-ci.org/protobuf-php/protobuf-plugin.svg?branch=master)](https://travis-ci.org/protobuf-php/protobuf-plugin)
[![Coverage Status](https://coveralls.io/repos/protobuf-php/protobuf-plugin/badge.svg?branch=master&service=github)](https://coveralls.io/github/protobuf-php/protobuf-plugin?branch=master)

Protobuf for PHP is an implementation of Google's Protocol Buffers for the PHP
language, supporting its binary data serialization and including a `protoc`
plugin to generate PHP classes from .proto files.

**NOTICE: THIS CLIENT IS UNDER ACTIVE DEVELOPMENT, USE AT YOUR OWN RISK**

## Installation

If you wish to compile ```.proto``` definitions to PHP,
you will need to install [Google's Protocol Buffers](https://github.com/google/protobuf) from your favorite package manager or from source.
This plugin currently supports protobuf 2.3.0. or later.

**Note**: *Google's Protocol Buffers and ```proto``` is not a runtime requirement for [protobuf-php/protobuf](https://github.com/protobuf-php/protobuf), It is only necessary if you wish to compile your definitions to PHP using [protobuf-php/protobuf-plugin](https://github.com/protobuf-php/protobuf-plugin).*


#### Installing Google's Protocol Buffers

* **OSX Install**

```console
$ brew install protobuf
```

* **Ubuntu**

```console
$ sudo apt-get install -y protobuf
```

Make sure you hame ```protoc``` available in the user's path:
```console
$ protoc --version
$ # libprotoc 2.6.1
```

**Note**: *For more information on how to install/compile Google's Protocol Buffers see : https://github.com/google/protobuf*


#### Composer install

To install the PHP plugin run the following `composer` commands:

```console
$ composer require "protobuf-php/protobuf-plugin"
```

#### Defining Your Protocol Format

To create your address book application, you'll need to start with a ```.proto``` file. The definitions in a ```.proto``` file are simple: you add a message for each data structure you want to serialize, then specify a name and a type for each field in the message. Here is the ```.proto``` file that defines your messages, ```addressbook.proto```.

```
package tutorial;
import "php.proto";
option (php.package) = "Tutorial.AddressBookProtos";

message Person {
  required string name = 1;
  required int32 id = 2;
  optional string email = 3;

  enum PhoneType {
    MOBILE = 0;
    HOME = 1;
    WORK = 2;
  }

  message PhoneNumber {
    required string number = 1;
    optional PhoneType type = 2 [default = HOME];
  }

  repeated PhoneNumber phone = 4;
}

message AddressBook {
  repeated Person person = 1;
}
```

As you can see, the syntax is similar to C++ or Java. Let's go through each part of the file and see what it does.
The ```.proto``` file starts with a package declaration, which helps to prevent naming conflicts between different projects.
In PHP, the package name is used as the PHP namespace unless you have explicitly specified a ```(php.package)```, as we have here.
Even if you do provide a ```(php.package)```, you should still define a normal package as well to avoid name collisions in the Protocol Buffers name space as well as in non PHP languages.

You'll find a complete guide to writing ```.proto``` files – including all the possible field types – in the [Protocol Buffer Language Guide](https://developers.google.com/protocol-buffers/docs/proto). Don't go looking for facilities similar to class inheritance, though – protocol buffers don't do that.


#### Compiling Your Protocol Buffers

Now that you have a ```.proto```, the next thing you need to do is generate the classes you'll need to read and write ```AddressBook``` (and hence ```Person``` and ```PhoneNumber```) messages. To do this, you need to run the protocol buffer plugin on your ```.proto```.

In this case:

```console
php ./vendor/bin/protobuf --include-descriptors -i . -o ./src/ ./addressbook.proto
```

This generates the following PHP classes in your specified destination directory

```console
src/
└── Tutorial
    └── AddressBookProtos
        ├── AddressBook.php
        ├── Person
        │   ├── PhoneNumber.php
        │   └── PhoneType.php
        └── Person.php
```

**Note**: *For more information on how to use the generated code see : [protobuf-php/protobuf](https://github.com/protobuf-php/protobuf)*
