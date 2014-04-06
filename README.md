FastIndexer
===========

No more empty results in the frontend due to a long taking reindex process!

- Integrates seamlessly into the existing Magento indexer process.
- Does not change the core Magento indexer logic!
- Only one class rewrite! (Adding an event in `Mage_Index_Model_Process::reindexAll()`)
- Indexing blocks the frontend for only ~0.003 seconds instead of minutes with the normal indexer.
- The frontend will not be affected anymore by any reindex process.
- Speeds up some indexing processes of your Magento store. 
- You can enable or disable the module in the backend and test the speed difference by yourself.
- Full reindexing now even under high frontend load possible (Citation/Test needed ... ).
- Limits the amount of SQL queries in some cases
- Even integrates into your custom indexer processes (theoretically, talk to me).
- Indexer (catalogpermissions & targetrule) for Enterprise Edition will also be considered

The FastIndexer is only available on the command line.

```
$ php/hhvm index.php reindexall
```

## Configuration options (Backend)

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

## Configuration options (local.xml)

### Changing the type instance

Please see the section *Verifying Installation of PDO class* above.

### Low Level Optimization

This feature can be switched on even when FastIndexer is turned off.

Low Level Optimization tries to convert stringified integer or float values into real integer resp. float values. This features applies on all queries. E.g. converts a query from `WHERE entity_id IN('433284', 433283)` to `WHERE entity_id IN(433284, 433283)` because mixing strings and integer values in a query will [MySQL slow down](http://www.mysqlperformanceblog.com/2006/09/08/why-index-could-refuse-to-work/). Query 1 needs: 0.0566s and optimized 0.0008s.

Use at your own risk. Test thoroughly.

To enable the low level quote() method optimization edit your local.xml and add the following entry in the node: `config/global/resources/default_setup/connection/fiQuoteOpt`.
Use for node `fiQuoteOpt` the value 1 for enable or any other value for disabled.

```
<config>
    <global>
		...
        <resources>
            ...
            <default_setup>
                <connection>
                    <host><![CDATA[localhost]]></host>
                    <username>...</username>
					...

                    <!--FastIndexer quote() optimization: 1/0-->
                    <fiQuoteOpt>1</fiQuoteOpt>

                </connection>
            </default_setup>
        </resources>
    </global>
</config>
```

It must be set in the local.xml file because quote() method is called even before the Magento configuration is available.

## How do the Magento default indexer work? (Full reindex)

Investigation of the logged SQL statements: Most indexer are completely deleting the index tables. Some of them only for a store view. But both cases are equal because each time the frontend customer has no access to the data (prices, stocks, search results ...) and gets empty results. Lost a customer and made less profit :-(


## Explaining the operation of FastIndexer

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

The current Magento database user must also have the ALTER and DROP privileges on the original table, and the SELECT,ALTER,DROP,CREATE and INSERT privileges on the new tables in the Shadow Databases.

Performance
-----------

On my MacBook Air Mid 2012 tested with the following stores.

Condition for all tests:

- No load on the frontend. 
- Just indexing of previous reindexed tables. 
- All indexe commands ran 3x and the median has been calculated. 
- `SET GLOBAL query_cache_type=OFF;` has been set.
- At the 4th run all queries have been counted in `Zend_Db_Statement_Pdo::_execute()`.

```
$ time -p php indexer.php --reindex <code>
```

### Magento 1.8 default installation

- 3 store views
- 27 categories
- 120 products

|Type       | FastIndexer | real     | user | sys| Query Count |
|-----------|-----------|----------|-------|----|--------------|
|reindexall | x  | 14.209s | 5.836s | 0.370s | 3275 |
|reindexall | ✔︎  |  7.490s |  4.265s | 0.179s | 2702 |


### Shop C: Magento EE 1.12

- 3 store views
- 66 categories
- 14088 products

|Type                      | FastIndexer | real     | user | sys| Query Count |
|--------------------------|-------------|----------|-------|----|--------------|
|catalog_product_attribute | x    | 29.69s | 8.14s | 0.40s | 208 |
|... | ✔︎ | 34.18s | 8.39s | 0.42s | 243 |
|catalog_product_price     | x    | 10.71s  |  1.56s | 0.07s | 173 |
|...     | ✔︎     |  9.64s  |  1.63s| 0.07s | 248 |
|catalog_product_flat 	    | x    | 184.31s  | 4.74s | 0.58s | 1,570 |
|... 	    | ✔︎    | 167.26s  | 2.60s | 0.11s | 530 |
|catalog_category_flat 	    | x    | 2.43s  | 1.84s | 0.07s | 80 |
|... 	    | ✔︎    | 2.52s  | 1.84s | 0.07s | 113 |
|catalog_category_product 	    | x    | 70.37s  | 1.91s | 0.07s | 117 |
|... 	    | ✔︎    | 31.46s  | 2.09s | 0.08s | 138 |
|catalogsearch_fulltext 	    | x    | 114.91s  | 2.21s | 0.07s | 8,769 |
|... 	                                  | ✔︎   | 114.24s  | 2.76s | 0.08s | 8,774 |
|cataloginventory_stock  | x    | 3.36s  | 1.50s | 0.06s | 32 |
|... 	                                 | ✔︎    | 3.16s  | 1.38s | 0.06s | 47 |
|catalog_url  (~245177 URLs) | x | 858.11s    | 637.34s  | 60.95s   | 524,748* |
|... 	                                 | ✔︎    | 819.44s  | 574.90s | 53.25s | 494,411 |

* added 3701 additional URLs due to the rewrite bug in the URL indexer


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

The FastIndexer will not run with Magento CE < 1.6.2 because elementary events are missing. If you are interested in running FastIndexer with lower Magneto version write me, there is a solution.


Support / Contribution
----------------------

Report a bug using the issue tracker.


Licence
-------

Don't know. Maybe still closed source but you'll get it with a donation to [http://www.seashepherd.org/](http://www.seashepherd.org/)

Author
------

[Cyrill Schumacher](https://github.com/SchumacherFM)

[My pgp public key](http://www.schumacher.fm/cyrill.asc)

Made in Sydney, Australia :-)
