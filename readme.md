---
title: 'ReadMe for PkExtensions for Laravel &gt;= 5.1'
---

-   [What are PkExtensions?](#what-are-pkextensions)
    -   [What do they consist of?](#what-do-they-consist-of)
    -   [What is the current
        status/version?](#what-is-the-current-statusversion)
-   [Extensions to existing Laravel
    Classes](#extensions-to-existing-laravel-classes)
    -   [PkModel](#pkmodel)
        -   [public function saveRelations(Array \$array
            = \[\])](#public-function-saverelationsarray-array)
        -   [public function saveM2MRelations(Array \$array
            = \[\])](#public-function-savem2mrelationsarray-array)
        -   [DB Table Definition to generate Migration Files,
            etc](#db-table-definition-to-generate-migration-files-etc)
        -   [PkModel::\$table\_field\_defs,
            PkModel::buildMigrationFieldDefs(),
            etc](#pkmodeltable_field_defs-pkmodelbuildmigrationfielddefs-etc)
        -   [static::getStaticAttributeNames()](#staticgetstaticattributenames)
    -   [PkSearchModel](#pksearchmodel)

What are PkExtensions?
======================

PkExtensions consist of multiple components, some very Laravel specific,
others more generally applicable with PHP/JavaScript/CSS I've found very
useful.

What do they consist of?
------------------------

They consist of:

**Laravel Extensions**: Classes that extend existing Laravel Classes,
like PkModel, PkController, etc.

**New Utility Classes**: Stand-alone classes that I find really useful,
that don't depend on Laravel - my favorite is "PartialSet" - an
extension of ArrayObject that can contain almost anything and implements
`__toString()` so you can just print / echo it out.

**Supporting PHP libraries**: PHP functions I find really useful. There
can be some naming conflicts with other libraries.

**Supporting JavaScript and SCSS Libraries**: I do lots of things where
PHP and JS have to co-operate. These libraries help in a very generic
way - like automatically building dialog boxes from PHP, just based on
css class names and 'data-xxx' attributes. Assumes jQuery & jQuery UI.

**Artisan helper scripts**: Just a couple of commands to:

-   Generate migration files from PkModel class definitions
-   Clear all caches, autoloads, everything in a single command

What is the current status/version?
-----------------------------------

Very much Alpha - changing often, quite a bit of commented out code and
experiments - but what works works pretty well and hopefully could be
very useful to other developers.

See below for highlights of some of the more interesting features.

Extensions to existing Laravel Classes
======================================

PkModel
-------

This extends Eloquent Model and is the biggest enhancement.

### public function saveRelations(Array \$array = \[\])

For one-to-many Objects, takes the multi-dimensional array of data and
updates the Model instance and all its possessions/collections.

For example, if you have:

    Class Cart extends PkModel {
      static $table_field_defs = [
          'id'=> 'increments',
          'cart_name'=>'string',
          'owner_id' => 'integer'
        ];

        public function items() {
          return hasMany('Models\\Item');
        }
      static $load_relations [ #Tells the class what one-to-many relationships it has
        'items' => 'Models\\Item',
        ];

        #...
    }

... and ...

    Class Item extends PkModel {
      static $table_field_defs = [
          'id'=> 'increments',
          'item_name'=>'string',
          'cart_id' => 'integer'
          'price' => 'integer',
        ];
      #...
    }

If you have an array of data representing the cart and its items, like:

    $data = [
      'id'=>7, #The cart ID - ignored because we know the cart
      'cart_name' => 'Happy Cart'
      'items' => [
          [
            'id'=>234, 
            'item_name'=>'Fishing Line',
            'cart_id' => 7, #Again ignored, we still know the cart
            'price' => 5,
          ],
          [
            #No item ID - this is new!
            'item_name'=>'Reel',
            'cart_id' => 7, #Again ignored, we still know the cart
            'price' => 22,
          ],
        ],
      ];

If you call:

    $myCart->updateRelations($data);

This command will:

-   Update any of the changed cart fields to the values of the
    corresponding array keys - like 'cart\_name'
-   Any key field in the data array that's not an attribute of \$myCart
    is ignored (`_token`, for example.
-   Any attribute name that's part of the Cart definition but absent as
    a key in the data array is ignored.
-   Any Cart attribute name that is present as a key in the data array
    with a value of null is reset/nulled in the
    `$myCart->$attributeName = null`
-   When we get to a \$key that is not an attribute but a key in the
    model's `static $load_relations[]` array, ('items'), iterate through
    any "relation" value arrays (items) and:
    -   If the 'items' key doesn't exist, don't do anything to the
        cart's items.
    -   If the 'items' key exists but has a value of null, empty string
        or \[\], delete all 'Item' Model/instances with `'cart_id'==7`
        (This cart has been emptied)
    -   If the 'item' key value is an indexed array of associative
        arrays, go through and:
        -   For any item data arrays that don't have an 'id' key - this
            is a new item added to the cart - create it with the
            associated data and 'cart\_id'
        -   If an item data array has an id, instantiate the
            corresponding 'Item' model/object, and if any of it's
            attributes are different from the corresponding attribute in
            the item value array, update it.
        -   If any item id's from the cart's original collection are
            missing from the arrays of items data, they have been
            removed and are deleted from the DB and the Cart.
    -   This is recursive, so if the "Item" model definition has
        collections/one-to-many relationships of its own, these are
        processed and persisted as well.

What is the value of that? In conjunction with `PkController`,
(described below), it makes processing / editing of complex forms that
map to a single model object with an arbitrary number and nesting of
one-to-many collections/relations a snap. The Controller method to fully
edit a Cart form, with adding/deleting/modifying its items, etc is:

    Class CartController extends PkController {
      pubic function edit(Cart $cart) {
        $this->processSubmit($cart);
        return view('cart.edit', ['cart'=>$cart]);
      }
    }

### public function saveM2MRelations(Array \$array = \[\])

Conceptually similar to `saveRelations()`, but for many-to-many
relationships, through a pivot table. Say, a Person following a Person
through the Follow pivot class.

For this, we define a static array variable on the class like:

    Class Person extends PkModel {
      public static $load_many_to_many = [
          'friends' =>
          [
              'other_model' => 'Models\Person',
              'pivot_model' => 'Models\Friend',
                 #OR
              'pivot_table' => 'friends',
              'my_key' => 'following_id', #Key for me (Follower) In the pivot table/model
              'other_key' => 'followed_id', #Key for who I'm following n the pivot table/model
          ],
      ];

Here we obviously don't edit or delete the other side of the
relationship (Followed Person), we edit/create/delete entries in the
Pivot Model/Table.

### DB Table Definition to generate Migration Files, etc

It seems sort of ridiculous that Model classes don't know what
attributes they have until they read the DB. Also, if you have a bunch
of Migration files, it's hard to get an overview of all the DB table
attributes by inspecting the code.

### PkModel::\$table\_field\_defs, PkModel::buildMigrationFieldDefs(), etc

If you extend PkModel and include a static array member:
\$table\_field\_defs as follows (Example):

    class QProfile extends PkModel {
      public static $table_field_defs = [
          'id' => 'increments',
          'user_id' => ['type' => 'integer', 'methods' => 'index'],
          'viewableby_id' => ['type' => 'integer', 'methods' => ['default' => 10]],
          'privatedescription' => ['type' => 'string', 'methods' => 'nullable'],
          'publicdescription' => ['type' => 'string', 'methods' => 'nullable'],
          'name' => ['type' => 'string', 'methods' => 'nullable'],
          'location' => ['type' => 'string', 'methods' => 'nullable'],
          'searchable' => ['type' => 'boolean', 'methods' => ['default' => false]],
          'about' => ['type' => 'text', 'methods' => 'nullable'],
      ];
      #...
    }

You can generate Migration files by running:

    php artisan make:migration QProfile

This will create the initial migration file to create the table
`q_profiles`, and if you change/add/remove the Model field definitions
in `static $table_field_defs`, the `artisan make:migration QProfile`
command will do a reasonable job of creating the Migration Update
scripts - though you might have to modify them by hand for complicated
changes.

### static::getStaticAttributeNames()

Returns all the DB field names from the underlying table.

PkSearchModel
-------------

This extends PkModel and helps to build and persist user created
searches - like for a dating site:

`Find all women WHERE (age gt 21 and age lt 45) and Zodiac Sign in one of ('aries', 'gemini', 'capricorn') and religion = "don't care"`

This abstract class extends **PkModel** and uses **BuildQueryTrait**

Any Controller that wants to manage searches should also use
**BuildQueryTrait**

To create a search for Person objects:

    class Person extends PkModel {
      public static $table_field_defs = [
        'id' => 'increments',
        'name' => 'string',
        'age' => 'integer',
        'religion_id' => 'integer', #Assumes reference map ID's => "Religion" strings
        'height' => 'integer',
        'zip' => 'integer',
        #...
      ];
      #...
    }

A QueryModel to save searches for a Person would look like:

    Class PersonSearch extends PkSearchModel {
      use PersonSearchTrait; #Trait because also used in PersonController::search() action
      public $targetModel = Person::class; #The model to perform the search on
      public static $table_field_defs = [
        'id' => 'increments',

        #Simple scalar comparison - greater than, less than, etc.
        'age_val' => 'integer', #What age is the comparison against?
        'age_crit' => 'string', #What is the criterion? Greater than age_val? Less than?

        # Pick multiple acceptable values
        'religion_id_val' => 'string', #JSON encoded array of acceptable values
        'religion_id_crit' => 'string', #IN? NOT IN?

         #A Range / 'BETWEEN' query:
        'height_crit' => 'string', #  'BETWEEN' or '0'/Don't Care
        'height_minval' => 'integer',
        'height_maxval' => 'integer',

        #Uses a custom method to calculate "within X Miles of ZIP"
        'withinzip_val' => 'integer',
        'withinzip_crit' => 'string',
