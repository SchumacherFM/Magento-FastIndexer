FastIndexer
===========

- Integrates seamlessly into the existing Magento indexer process.
- Does not change the core Magento indexer logic! No class rewrites!
- Indexing blocks the frontend for only ~0.003 seconds
- Speeds up the whole indexing process of your Magento store. You can enable or disable the module in the backend and test the speed difference by yourself.
- The frontend will not be affected anymore by any reindex process.
- Full reindexing now even under high frontend load possible (Citation/Test needed ... ).
- Limits the amount of SQL queries
- Even integrates into your custom indexer processes (theoretically, talk to me).

The FastIndexer is only available on the command line.

```
$ php/hhvm index.php reindexall
```

Configuration options
---------------------

Accessible via *System -> Configuration -> System -> FastIndexer*

### Enable FastIndexer

Enable or disable the FastIndexer. This is useful to become yourself aware about the differences.

### Drop old tables

Default values: YES. For testing purposes or in development you can disable that option.

Disabling it also means that the process might be a little bit slower and you are collecting tons of data by generating old index tables.

### Shadow Databases

FastIndexer needs two Shadow Databases which must be on the same file system as the Magento store core database.

You have two text fields in the backend to enter the different names of the Shadow Databases.

The backend configuration section also checks for you if the current Magento core database user can access those two databases. If not it will display a warning. FastIndexer cannot create by itself those two databases automatically. Ask your DevOps for assistance.

### Verifying Installation of PDO class

FastIndexer comes with a custom `Pdo_MySQL` PHP class which also fixes two evil bugs in the default `Varien_Db_Adapter_Pdo_Mysql` class. It is 100% compatible. 

The FastIndexer PDO class must be configured in `app/etc/local.xml`.

If you have correctly installed the FastIndexer PDO class then a green sign shows up in the system configuration section otherwise you will see the installation instructions.

### URL Rewrites: Copy all custom URLs

Enable this option if you wish to copy all custom URLs. Before enabling this option be sure that the permanent rewrite generating bug in Magento ~<=1.7 has been fixed. Otherwise you will add tens of thousands useless rewrites with each reindexing. If this option is disabled only the rewrites created by the store maintainer will be copied. But this can be slow because a regular expression will be used to determine all custom rewrites. If set to yes no regex will be used.

##### Checking for system generated custom redirect permanent URLs

With the following SQL Query you can check the system generated custom redirect permanent URLs:

```SQL
SELECT * FROM `core_url_rewrite` WHERE is_system=0 AND id_path RLIKE '[0-9]+\_[0-9]+'
```

If that query returns nothing then you can set this option to **Yes**.

##### Checking for your custom created URLs

You can create custom URL redirects at *Catalog -> URL Rewrite Management*. With the following SQL Query you can check if you have custom URLs:

```SQL
SELECT * FROM `core_url_rewrite` WHERE is_system=0 AND id_path NOT RLIKE '[0-9]+\_[0-9]+'
```

Explaining the operation of FastIndexer
---------------------------------------

All index processes have one thing in common: They block the frontend during their whole index duration. That's why many store owners run a full reindex only during the night or even more seldom.

Some indexer truncates the index tables. If in that moment a potential customer wants to buy something he/she will fail because of empty tables.

Some indexer are doing complex operations for calculating differences between already indexed data and new data. This costs a lot of time and the index tables have a lock for updates.

#### Technial Explanations

Reindexing will be done in the so called Shadow Databases.

Therefore the table swapping operation is done atomically after the reindexing, which means that no other session can access any of the index/flat tables while the swapping is running. 

This swapping operation needs **~0.003 seconds**.

If there are any database triggers associated with an index/flat table which is swapped to a different Shadow Database, then the swapping operation will fail.

When the swapping operation is running and there are any locked tables or active transactions then the swapping will fail. 

**If the swapping fails nothing will break.** Just rerun the indexer.

The current Magento database user must also have the ALTER and DROP privileges on the original table, and the CREATE and INSERT privileges on the new tables in the Shadow Databases.

Performance
-----------

On my MacBook Air Mid 2012 tested with the following stores.

Condition for all tests: no load on the frontend. Just indexing of previous reindexed tables.

All tests run via:

```
$ time php indexer.php reindexall
Product Attributes index was rebuilt successfully
Product Prices index was rebuilt successfully
Catalog URL Rewrites index was rebuilt successfully
Product Flat Data index was rebuilt successfully
Category Flat Data index was rebuilt successfully
Category Products index was rebuilt successfully
Catalog Search Index index was rebuilt successfully
Stock Status index was rebuilt successfully
```

### Magento 1.8 default installation

- One store view
- ~ 15.500 categories
- ~ 45.400 products

| FastIndexer | real     | user | sys| Query Count |
|-----------|----------|-------|----|--------------|
| Disabled  | 0m14.209s | 0m5.836s | 0m0.370s | @todo |
| Enabled  | 0m7.490s | 0m4.265s | 0m0.179s | @todo |


### Shop C: Magento EE



### Shop Z: Magento 1.7

- One store view
- ~ 15.500 categories
- ~ 45.400 products


| FastIndexer | real     | user | sys| Query Count |
|-----------|----------|-------|----|--------------|
| Disabled  | 14m8.919s | 5m0.248s | 0m9.695s | @todo |
| Enabled  | 10m37.517s | 4m51.361s | 0m8.864s | @todo |


### catalog_url

```
$ time php indexer.php --reindex catalog_url
Catalog URL Rewrites index was rebuilt successfully
```

    :::text
    run real        user        sys



### catalog_product_attribute


```
$ time php indexer.php --reindex catalog_product_attribute
Product Attributes index was rebuilt successfully
```


    :::text
    run real        user        sys


### catalog_product_price


```
$ time php indexer.php --reindex catalog_product_price
Product Prices index was rebuilt successfully
```

    :::text
    run real        user        sys


### catalog_product_flat


```
$ time php indexer.php --reindex catalog_product_flat
```

    :::text
    run real        user        sys


About/History
-------------

Extension key: SchumacherFM_FastIndexer

Version 1.0.0

- Initial Release

Compatibility
-------------

- Magento CE >= 1.6.2
- php >= 5.2.0

The FastIndexer will not run with Magento CE < 1.6.2 because elementary events are missing.


Support / Contribution
----------------------

Report a bug using the issue tracker.


Licence
-------

Proprietary. Ask for the price.

Author
------

[Cyrill Schumacher](https://github.com/SchumacherFM)

[My pgp public key](http://www.schumacher.fm/cyrill.asc)

Made in Sydney, Australia :-)
