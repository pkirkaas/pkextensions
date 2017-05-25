-   [<span class="toc-section-number">1</span> What are PkExtensions?](#what-are-pkextensions)
    -   [<span class="toc-section-number">1.1</span> What do PkExtensions consist of?](#what-do-pkextensions-consist-of)
    -   [<span class="toc-section-number">1.2</span> What is the current status/version?](#what-is-the-current-statusversion)
-   [<span class="toc-section-number">2</span> Extensions to existing Laravel Classes](#extensions-to-existing-laravel-classes)
    -   [<span class="toc-section-number">2.1</span> PkModel](#pkmodel)
        -   [<span class="toc-section-number">2.1.1</span> public function saveRelations(Array $array = \[\])](#public-function-saverelationsarray-array)
        -   [<span class="toc-section-number">2.1.2</span> public function saveM2MRelations(Array $array = \[\])](#public-function-savem2mrelationsarray-array)
        -   [<span class="toc-section-number">2.1.3</span> DB Table Definition to generate Migration Files, etc](#db-table-definition-to-generate-migration-files-etc)
        -   [<span class="toc-section-number">2.1.4</span> PkModel::$table\_field\_defs, PkModel::buildMigrationFieldDefs(), etc](#pkmodeltable_field_defs-pkmodelbuildmigrationfielddefs-etc)
        -   [<span class="toc-section-number">2.1.5</span> static::getStaticAttributeNames()](#staticgetstaticattributenames)
-   [<span class="toc-section-number">3</span> Eliminate 80% of the JavaScript you write](#eliminate-80-of-the-javascript-you-write)
    -   [<span class="toc-section-number">3.1</span> AJAX handling on click](#ajax-handling-on-click)
    -   [<span class="toc-section-number">3.2</span> jQuery UI Dialogs](#jquery-ui-dialogs)
    -   [<span class="toc-section-number">3.3</span> Binding one-&gt;many ORM objects, DB tables and forms](#binding-one-many-orm-objects-db-tables-and-forms)
-   [<span class="toc-section-number">4</span> More Features/Extensions](#more-featuresextensions)
    -   [<span class="toc-section-number">4.1</span> PkSearchModel](#pksearchmodel)

<span class="header-section-number">1</span> What are PkExtensions?
===================================================================

PkExtensions consist of multiple components, some very Laravel specific, others more generally applicable with PHP/JavaScript/CSS. This is the 'experimental' branch, NOT stable. 

The guiding principal is to provide a lot of reusable functionality for things developers do all the time - so you don't have to re-implement similar functionality every time. For example, enhanced form inputs (like, multi-select check boxes), bound by specific CSS class names to JavaScript/jQuery event handlers. Which support binding complex, nested, one to many to many forms with hidden templates and deletable data sets - so almost the only code you need to write to implement complex form to abstract DB data structures are the PHP/HTML templates.

<span class="header-section-number">1.1</span> What do PkExtensions consist of?
-------------------------------------------------------------------------------

They consist of:

**Laravel Extensions**: Classes that extend existing Laravel Classes, like PkModel, PkController, etc.

**New Utility Classes**: Stand-alone classes that I find really useful, that don't depend on Laravel - my favorite is "PartialSet" - an extension of ArrayObject that can contain almost anything and implements `__toString()` so you can just print / echo it out.

**Supporting PHP libraries**: PHP functions I find really useful. There can be some naming conflicts with other libraries.

**Supporting JavaScript, jQuery, CSS and SCSS Libraries**: I do lots of things where PHP and JS have to co-operate. These libraries help in a very generic way - like automatically building dialog boxes from PHP, just based on CSS class names and 'data-xxx' attribute names and values. Assumes jQuery & jQuery UI.

**Artisan helper scripts**: Just a couple of commands to:

-   Generate migration files from PkModel class definitions
-   Clear all caches, autoloads, everything in a single command

<span class="header-section-number">1.2</span> What is the current status/version?
----------------------------------------------------------------------------------

Very much Alpha - continually evolving, quite a bit of commented out code and experiments - but what works works pretty well and hopefully could be very useful to other developers.

See below for highlights of some of the more interesting features.

<span class="header-section-number">2</span> Extensions to existing Laravel Classes
===================================================================================

<span class="header-section-number">2.1</span> PkModel
------------------------------------------------------

This extends Eloquent Model and fills in what seemed to be some very obvious gaps.

### <span class="header-section-number">2.1.1</span> public function saveRelations(Array $array = \[\])

For one-to-many Objects, takes the multi-dimensional array of data and updates the Model instance and all its possessions/collections.

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

This method will:

-   Update any of the changed cart fields to the values of the corresponding array keys - like 'cart\_name'
-   Any key field in the data array that's not an attribute of $myCart is ignored (`_token`, for example.
-   Any attribute name that's part of the Cart definition but absent as a key in the data array is ignored.
-   Any Cart attribute name that is present as a key in the data array with a value of null is reset/nulled in the `$myCart->$attributeName = null`
-   When we get to a $key that is not an attribute but a key in the model's `static $load_relations[]` array, ('items'), iterate through any "relation" value arrays (items) and:
    -   If the 'items' key doesn't exist, don't do anything to the cart's items.
    -   If the 'items' key exists but has a value of null, empty string or \[\], delete all 'Item' Model/instances with `'cart_id'==7` (This cart has been emptied)
    -   If the 'item' key value is an indexed array of associative arrays, go through and:
        -   For any item data arrays that don't have an 'id' key - this is a new item added to the cart - create it with the associated data and 'cart\_id'
        -   If an item data array has an id, instantiate the corresponding 'Item' model/object, and if any of it's attributes are different from the corresponding attribute in the item value array, update it.
        -   If any item id's from the cart's original collection are missing from the arrays of items data, they have been removed and are deleted from the DB and the Cart.
    -   This is recursive, so if the "Item" model definition has collections/one-to-many relationships of its own, these are processed and persisted as well.

What is the value of that? In conjunction with `PkController`, (described below), it makes processing / editing of complex forms that map to a single model object with an arbitrary number and nesting of one-to-many collections/relations a snap. The Controller method to fully edit a Cart form, with adding/deleting/modifying its items, etc is:

    Class CartController extends PkController {
      pubic function edit(Cart $cart) {
        $this->processSubmit($cart);
        return view('cart.edit', ['cart'=>$cart]);
      }
    }

### <span class="header-section-number">2.1.2</span> public function saveM2MRelations(Array $array = \[\])

Conceptually similar to `saveRelations()`, but for many-to-many relationships, through a pivot table. Say, a Person following a Person through the Follow pivot class.

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

Here we obviously don't edit or delete the other side of the relationship (Followed Person), we edit/create/delete entries in the Pivot Model/Table.

### <span class="header-section-number">2.1.3</span> DB Table Definition to generate Migration Files, etc

It seems ridiculous that Eloquent Model classes don't know what attributes they have until they read the DB. Also, if you have a bunch of Migration files, it's hard to get an overview of all the DB table attributes by inspecting the code.

### <span class="header-section-number">2.1.4</span> PkModel::$table\_field\_defs, PkModel::buildMigrationFieldDefs(), etc

If you extend PkModel and include a static array member: $table\_field\_defs as follows (Example):

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

This will create the initial migration file to create the table `q_profiles`, and if you change/add/remove the Model field definitions in `static $table_field_defs`, the `artisan make:migration QProfile` command will do a reasonable job of creating the Migration Update scripts - though you might have to modify them by hand for complicated changes.

### <span class="header-section-number">2.1.5</span> static::getStaticAttributeNames()

Returns all the DB field names from the underlying table.

<span class="header-section-number">3</span> Eliminate 80% of the JavaScript you write
======================================================================================

**Managing Dialogs, AJAX calls, Form & DB handling, etc.**

You've already written almost the same code dozens of times with just minor variations. Just follow a few conventions with CSS class names and `data-XXX` attributes in your HTML, and it's all done. If you use Laravel, `PkFormBuilder` extends Laravel Collective `FormBuilder` with methods to create the HTML components that work with the jQuery libraries.

<span class="header-section-number">3.1</span> AJAX handling on click
---------------------------------------------------------------------

-   **Create a DOM node you want to trigger / process the AJAX**: Could be just a button or link, or a complex nested collection of HTML elements. Add an attribute called `data-pk-ajax-element`. It doesn't need a value, jQuery will attach to it. No problem if you have many unrelated AJAX handler components on the same page. If you are using Laravel, `PkForm::ajaxElement($options)` builds the HTML component for you.
-   **Add a few more `data-XXX` attributes**:
    -   `data-ajax-url`: The URL for the AJAX call (the only other required attribute - the rest are optional)
    -   `data-ajax-params`: An encoded query string of parameters and values. You don't have to encode them, though. Just set `$options['params'] =  ['param1'=>$val1, 'param1'=>$val2, ...]` and leave it to `PkForm::ajaxElement()`.
    -   Specify the other behavior you want in the $options values. What you want to do with the AJAX response, etc.
    -   See the documented source codes for all the details and options.
-   `$('body').on('click', '[data-pk-ajax-element]', function (event) {...` attaches to every element on the page with the 'data-pk-ajax-element' attribute, and submits the AJAX call based on the attributes defined in the target element.
-   The event handler also processes the AJAX result based on the DOM element attributes.

<span class="header-section-number">3.2</span> jQuery UI Dialogs
----------------------------------------------------------------

A typical dialog process involves a user clicking on something, which pops up a dialog to take some action.

-   **On all elements the user clicks to initiate a dialog**: Add the CSS class `js-dialog-button`. The JS event handler will attach to that/them.
-   **Add the attribute `data-dialog`**: to each, set the value to name of the hidden dialog template the click should activate.
-   **Create the dialog template(s)**: Assign them each the CSS class `js-dialog-content`. The included CSS sets `display: none;.      `
-   **To each template, add the attribute `data-dialog`**: Set it to the value of the corresponding attribute in the clickable element that should launch the dialog.
-   **But Wait!**: Maybe you have 50 identical buttons on a page, that should pop up the same dialog, only with some different parameters - like a table of users, each with an "Edit" button to bring up a "User Edit" form. But different `user_id`. So each button can have several `data-param` attributes, with value of the corresponding user\_id or whatever. The JS event handler substitutes the value from the button to the popped up dialog form. No extra JS required.

<span class="header-section-number">3.3</span> Binding one-&gt;many ORM objects, DB tables and forms
----------------------------------------------------------------------------------------------------

Edit/create/delete most data and relationships with almost no code.

<span class="header-section-number">4</span> More Features/Extensions
=====================================================================

<span class="header-section-number">4.1</span> PkSearchModel
------------------------------------------------------------

Supports building persistent complex user created searches/queries.

PkSearchModel extends PkModel and works with PkController and `BuildQueryTrait to build, execute and persist complex user created searches. Like for a dating site use case: `

`Find all women WHERE (age BETWEEN 27, 45) AND Zodiac Sign in one of ('aries', 'gemini', 'capricorn') AND  religion = "don't care"`

The abstract class PkSearchModel extends **PkModel** and uses **BuildQueryTrait**

Any Controller that wants to manage searches should also use **BuildQueryTrait**

For example, to create a search for Person objects, first define the Person model/class :

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

Note the static array `$table_field_defs`

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
        #...
    }
