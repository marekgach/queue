You need to have table jobs present in your database. Please run the SQL below
to create it.

```
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `handler` text COLLATE utf8_czech_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8_czech_ci NOT NULL DEFAULT 'default',
  `attempts` int(10) unsigned NOT NULL DEFAULT '0',
  `run_at` datetime DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `locked_by` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `failed_at` datetime DEFAULT NULL,
  `error` text COLLATE utf8_czech_ci,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
```