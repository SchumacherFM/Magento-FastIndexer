FastIndexer
===========

Makes indexing of your Magento store around x% faster!

Install (via modman)

Go to backend -> System -> Configuration -> Advanced -> @SchumacherFM -> FastIndexer and enable there the FastIndexer

Then run:
```
$ cd magento-site-folder
$ cd shell
$ php -f index.php reindexall
```


What it does?
-------------

Some magic


About
-----
- version: 1.0.0
- extension key: SchumacherFM_FastIndexer
- [extension on GitHub](https://github.com/SchumacherFM)
- [direct download link](https://github.com/SchumacherFM)


Compatibility
-------------
- Magento >= 1.x Every version which has the event resource_get_tablename implemented
- php >= 5.2.0

I'm using http://php-osx.liip.ch/ with version 5.4.10 and 5.3.19.

It could run with Magento < 1.5 but still not tested.


Todo / Next Versions
--------------------
- nothing ...

# Performance

On my MacBook Air Mid 2012 tested with the following stores.

Condition for all tests: no load on the frontend. Just indexing.

## Stoeckli

### catalog_url

Empty core rewrite table in the first run.

4 runs without FastIndexer:
```
$ time php indexer.php --reindex catalog_url
Catalog URL Rewrites index was rebuilt successfully
```

    :::text
    run real        user        sys
    1   1m25.264s   1m4.542s    0m2.363s
    2   3m50.983s   2m50.309s   0m7.267s
    3   3m48.984s   2m44.590s   0m7.080s
    4   3m50.500s   2m41.907s   0m7.003s

4 runs with enabled FastIndexer:

    :::text
    run real        user        sys
    1   2m9.640s    1m25.179s   0m2.756s
    2   1m45.722s   1m11.917s   0m2.159s
    3   1m44.896s   1m10.276s   0m2.070s
    4   1m44.914s   1m9.794s    0m2.101s

Bug: all entries where is_system=0 will be lost ...

### catalog_product_attribute

Condition: tables are not truncated.

```
$ time php indexer.php --reindex catalog_product_attribute
Product Attributes index was rebuilt successfully
```

FastIndexer disabled:

    :::text
    run real        user        sys
    1   0m36.035s   0m2.986s    0m0.171s
    2   0m34.434s   0m2.937s    0m0.156s
    3   0m35.485s   0m3.033s    0m0.155s

FastIndexer enabled:

    :::text
    run real        user        sys
    1   0m26.443s   0m2.807s    0m0.155s
    2   0m23.740s   0m2.793s    0m0.151s
    3   0m26.503s   0m3.203s    0m0.171s

### catalog_product_price

Condition: tables are not truncated.

```
$ time php indexer.php --reindex catalog_product_price
Product Prices index was rebuilt successfully
```

FastIndexer disabled:

    :::text
    run real        user        sys
    1   0m33.496s   0m1.052s    0m0.059s
    2   0m33.963s   0m0.997s    0m0.050s
    3   0m33.695s   0m1.010s    0m0.051s

FastIndexer enabled:

    :::text
    run real        user        sys
    1   0m22.719s   0m1.094s    0m0.059s
    2   0m21.737s   0m1.089s    0m0.056s
    3   0m21.404s   0m1.058s    0m0.053s

### catalog_product_flat

Condition: tables are not truncated. 8 Store Views.

```
$ time php indexer.php --reindex catalog_product_flat
```

FastIndexer disabled:

    :::text
    run real        user        sys
    1   0m36.161s   0m2.706s    0m0.356s
    2   0m35.819s   0m2.578s    0m0.349s
    3   0m36.285s   0m2.785s    0m0.359s

FastIndexer enabled:

    :::text
    run real        user        sys
    1
    2
    3


Support / Contribution
----------------------

Report a bug or send me a pull request.



Licence
-------
[OSL - Open Software Licence 3.0](http://opensource.org/licenses/osl-3.0.php)

Author
------

[Cyrill Schumacher](https://github.com/SchumacherFM)

[My pgp public key](http://www.schumacher.fm/cyrill.asc)

Made in Sydney, Australia :-)
