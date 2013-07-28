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

Performance
-----------

On my MacBook Air Mid 2012 tested with the following stores:

#### Stoeckli

3 runs without FastIndexer:

```
$ time php indexer.php --reindex catalog_url
Catalog URL Rewrites index was rebuilt successfully

real	8m57.344s
user	2m54.642s
sys	0m7.618s
$ time php indexer.php --reindex catalog_url
Catalog URL Rewrites index was rebuilt successfully

real	8m27.316s
user	2m54.498s
sys	0m7.712s
```



Support / Contribution
----------------------

Report a bug or send me a pull request.



Licence
-------
[OSL - Open Software Licence 3.0](http://opensource.org/licenses/osl-3.0.php)

Author
------

[Cyrill Schumacher](https://github.com/SchumacherFM)

Made in Sydney, Australia :-)
